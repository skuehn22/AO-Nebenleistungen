<?php
/**
 * Bearbeitet die Datensätze und die Buchungsnummer einer bereits vorhandenen Programmbuchung
 *
 * @author Stephan.Krauss
 * @date 19.06.13
 * @file BestandsbuchungProgramme.php
 * @package front
 * @subpackage model
 */
class Front_Model_BestandsbuchungProgramme implements Front_Model_BestandsbuchungProgrammeInterface
{

    // Tabellen / Views
    private $db = null;

    // Fehler
    private $error_anzahl_datensaetze_buchungsnummer_falsch = 1660;

    // Konditionen
    private $condition_zaehler_aktuelle_buchung = 0;

    // Flags

    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $userId = null;
    protected $sessionId = null;
    protected $anzeigeSprache = null;

    public function __construct()
    {
        $this->db = Zend_Registry::get('front');

        return;
    }

    /**
     * @param $userId
     * @return Front_Model_BestandsbuchungProgramme
     */
    public function setUserId($userId)
    {
        $userId = (int) $userId;
        $this->userId = $userId;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_BestandsbuchungProgramme
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestandsbuchungProgramme
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $anzeigesprache
     * @return Front_Model_BestandsbuchungProgramme
     */
    public function setAnzeigeSprache($anzeigesprache)
    {
        $this->anzeigeSprache = $anzeigesprache;

        return $this;
    }

    /**
     * Übernimmt die Bestandsbuchung und setzt den Inhalt der Session
     *
     * + Buchungsnummern eines Benutzer
     * + Kontrolle gehört die Buchungsnummer dem Benutzer
     * + Session der Buchungsnummer
     * + registrieren der Bestandsbuchung in der Session
     * + löschen aktuelle Buchung / duplizieren einer Bestandsbuchung
     * + verändern Session und Eintrag in 'tbl_buchungsnummer'
     *
     * @return $this
     */
    public function bestandsbuchung()
    {
        // Buchungsnummern eines Benutzer
        $buchungsnummernIdArray = nook_ToolBuchungsnummer::findeAlleBuchungsnummernEinesKunden($this->userId);
        $buchungsnummern = nook_ToolBuchungsnummer::filternBuchungsnummern($buchungsnummernIdArray);

        // Kontrolle gehört die Buchungsnummer dem Benutzer
        $this->gehoertDieBuchungsnummerdemBenutzer($buchungsnummern);

        // Session der Buchungsnummer
        $this->sessionId = nook_ToolBuchungsnummer::findeSessionIdEinerBuchungsnummer($this->buchungsnummer);

        // registrieren der Bestandsbuchung in der Session
        $this->registrierenBuchungsnummerZaehlerSession($this->buchungsnummer, $this->zaehler);

        // löschen aktuelle Buchung / duplizieren einer Bestandsbuchung
        $this->umkopierenBestandsbuchung();

        // Kontrolle ob es eine Stornierung ist
        $toolStornierung = new nook_ToolStornierung();
        $flagIsStornierung = $toolStornierung
            ->setBuchungsnummer($this->buchungsnummer)
            ->setZaehler($this->zaehler)
            ->anzahlArtikelImWarenkorb()
            ->isStornierung();

        // wenn keine Stornierung
        if(true !== $flagIsStornierung){
            $this->anpassenSessionUndBuchungstabelle();
        }

        return $this;
    }

    /**
     * Verändert den Eintrag in 'tbl_buchungsnummer' und in der Session
     *
     */
    private function anpassenSessionUndBuchungstabelle()
    {
        // umschreiben Session Namespace 'buchung'
        $sessionNamespaceBuchung = array(
            'buchungsnummer' => $this->buchungsnummer,
            'zaehler' => $this->zaehler
        );

        nook_ToolSession::setParamsInSessionNamespace('buchung', $sessionNamespaceBuchung);

        // umschreiben Session Namespace 'translate'
        $sessionNamespaceTranslate = array(
            'translate' => $this->anzeigeSprache
        );

        nook_ToolSession::setParamsInSessionNamespace('translate', $sessionNamespaceTranslate);

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        // Kontrolle ob alte und neue Session identisch ist
        $colsSelect = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereSessionIdEqual = "session_id = '".Zend_Session::getId()."'";
        $whereIdEqual = "id = ".$this->buchungsnummer;
        $whereZaehlerId = "zaehler = ".$this->zaehler;

        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $colsSelect)
            ->where($whereSessionIdEqual)
            ->where($whereIdEqual)
            ->where($whereZaehlerId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        // Abbruch wenn Datensatz 'tbl_buchungsnummer' identisch
        if($rows[0]['anzahl'] == 1)
            return;

        // unsinnige Anzahl Datensaetze 'tbl_buchungsnummer'
        if($rows[0]['anzahl'] > 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_buchungsnummer_falsch);


        // umkopieren 'tbl_buchungsnummer'
        $update = array(
            'session_id' => Zend_Session::getId() // setzen Session ID in 'tbl_buchungsnummer'
        );

        $whereUpdate = array(
            'id = '.$this->buchungsnummer,
            'zaehler = '.$this->zaehler
        );

        $whereDelete = array(
            "session_id = '".Zend_Session::getId()."'"
        );

        // löschen alter aktiver Warenkorb
        $tabelleBuchungsnummer->delete($whereDelete);

        // update Bestandsbuchung
        $tabelleBuchungsnummer->update($update, $whereUpdate);

        return;
    }

    /**
     * Kontrolliert ob die Buchungsnummer dem benutzer gehört
     *
     * @param array $buchungsnummern
     * @return bool
     * @throws nook_Exception
     */
    private function gehoertDieBuchungsnummerdemBenutzer(array $buchungsnummern)
    {
        $kontrolleErfolgreich = false;

        foreach ($buchungsnummern as $buchungsnummer) {
            if ($buchungsnummer == $this->buchungsnummer) {
                $kontrolleErfolgreich = true;

                break;
            }
        }

        if ($kontrolleErfolgreich === false) {
            throw new nook_Exception($this->error);
        }

        return $kontrolleErfolgreich;
    }

    /**
     * Registriert in der Session in Namespace 'buchung'
     *
     * + die Buchungsnummer
     * + den Zaehler der Buchungsnummer
     *
     * @param $buchungsnummer
     * @param $zaehler
     */
    private function registrierenBuchungsnummerZaehlerSession($buchungsnummer, $zaehler)
    {
        $toolBuchungsnummer = new nook_ToolBuchungsnummer();
        $toolBuchungsnummer->registriereSessionBuchungsnummerZaehler($buchungsnummer, $zaehler);

        return $zaehler;
    }

    /**
     * Kopiert die Datensaetze einer Bestandsbuchung Programme
     *
     * + leeren des Warenkorbes
     * + kopieren Datensätze in tmp Tabelle
     * + verändern Datensätze in tmp auf Zähler 0
     * + einfügen in tbl_programmbuchung
     * + umschreiben Session Namespace 'buchung'
     *
     * @param $buchungsnummer
     */
    private function umkopierenBestandsbuchung()
    {
        /** @var  $db Zend_Db_Adapter_Mysqli */
        $db = $this->db;

        // leeren des Warenkorbes
        $sql = "delete from tbl_programmbuchung where buchungsnummer_id = " . $this->buchungsnummer . " and zaehler = " . $this->condition_zaehler_aktuelle_buchung;
        $db->query($sql);

        // kopieren Datensätze in tmp Tabelle
        $sql = "create TEMPORARY TABLE tmp select * from tbl_programmbuchung where buchungsnummer_id = " . $this->buchungsnummer . " and zaehler = " . $this->zaehler;
        $db->query($sql);

        // verändern Datensätze in tmp auf Zähler 0
        $sql = "update tmp set zaehler = " . $this->condition_zaehler_aktuelle_buchung . ", id = null";
        $db->query($sql);

        // einfügen in tbl_programmbuchung
        $sql = "insert into tbl_programmbuchung select * from tmp";
        $db->query($sql);

        return;
    }

} // end class

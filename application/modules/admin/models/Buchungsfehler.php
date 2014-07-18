<?php
/**
 * Darstellung der aufgetretenen Exception
 *
 * Die Kunden Id ist bekannt.
 * + Fehler / Exception 'tbl_exception' = 1
 * + Buchungsnummer ist bekannt
 * + kundenId ist bekannt
 *
 * @author stephan.krauss
 * @date 11.06.13
 * @file Fehlerermittlung.php
 * @package front | admin | tabelle | data | tools | plugins
 * @subpackage model | controller | filter | validator
 */
class Admin_Model_Buchungsfehler implements Admin_Model_BuchungsfehlerInterface{

    // Tabelle / Views
    private $tabelleException = null;
    private $tabelleAdressen = null;

    // Fehler
    private $error_anzahl_datensaetze_stimmt_nicht = 1630;
    private $error_anfangswerte_fehlen = 1631;

    // Flags

    // Konditionen
    private $condition_reaction_exception = 1; // Exception
    private $condition_kunden_id_unbekannt = 0;

    protected $fehler = array();
    protected $anzahlFehler = 0;
    protected $limit = 10;
    protected $start = 0;
    protected $select = null;
    protected $idBuchungsfehler = null;
    protected $statusBuchungsfehler = null;

    public function __construct()
    {
        /** @var tabelleException Application_Model_DbTable_exception */
        $this->tabelleException = new Application_Model_DbTable_exception();
        /** @var  tabelleAdressen Application_Model_DbTable_adressen */
        $this->tabelleAdressen = new Application_Model_DbTable_adressen();
    }

    /**
     * @param $id
     * @return Admin_Model_Buchungsfehler
     */
    public function setBuchungsfehlerId($id)
    {
        $id = (int) $id;
        $this->idBuchungsfehler = $id;

        return $this;
    }

    /**
     * @param $status
     * @return Admin_Model_Buchungsfehler
     */
    public function setBuchungsfehlerStatus($status)
    {
        $status = (int) $status;
        $this->statusBuchungsfehler = $status;


        return $this;
    }

    /**
     * Ermitteln der momentan vorhandenen Fehler
     *
     * + die Exception
     * + Kunden ID ist bekannt
     * + Buchungsnummer ist bekannt
     *
     * @return Admin_Model_Buchungsfehler
     */
    public function ermittelnAktuelleFehler()
    {
        $this->anzahlFehler();
        $fehler = $this->findException(); // finden Exception

        if($fehler)
            $this->aufbereitenFehlermeldungen($fehler); // Aufbereitung entsprechend Tabelle

        return $this;
    }

    /**
     * Kontrolliert das verändern des Buchungsstatus
     *
     * + ID Buchungsfehler
     * + Status Buchungsfehler
     *
     * @throws nook_Exception
     */
    public function buchungsfehlerStatus()
    {
        if(empty($this->idBuchungsfehler) or empty($this->statusBuchungsfehler))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $kontrolle = $this->statusBuchungsfehlerSetzen($this->idBuchungsfehler, $this->statusBuchungsfehler);

        return $kontrolle;
    }

    /**
     * Setzt den Status einer Fehlbuchung
     *
     * @param $id
     * @param $status
     * @return int
     */
    private function statusBuchungsfehlerSetzen($id, $status)
    {
        $update = array(
            'status' => $status
        );

        $where = "id = ".$id;

        $kontrolle = $this->tabelleException->update($update, $where);

        return $kontrolle;
    }

    /**
     * Ermittelt die Anzahl aller Fehler in 'tbl_eception'
     *
     * @return int / void
     */
    private function anzahlFehler()
    {
        $whereReaction = "reaction = '".$this->condition_reaction_exception."'";
        $whereKundenId = "kundenId > ".$this->condition_kunden_id_unbekannt;
        $whereBuchungsnummer = "buchungsnummer > 0";

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $select = $this->tabelleException->select();
        $select
            ->where($whereReaction)
            ->where($whereKundenId)
            ->where($whereBuchungsnummer)
            ->from($this->tabelleException, $cols);

        $rows = $this->tabelleException->fetchAll($select)->toArray();

        if($rows[0]['anzahl'] > 0){
            $this->anzahlFehler = $rows[0]['anzahl'];

            return $rows[0]['anzahl'];
        }


        return;
    }

    /**
     * @return array
     */
    public function getFehler()
    {
        return $this->fehler;
    }

    /**
     * Setzt den Startpunkt der Fehlerdarstellung
     *
     * @param $start
     * @return Admin_Model_Buchungsfehler
     */
    public function setStart($start)
    {
        $start = (int) $start;
        $this->start = $start;

        return $this;
    }

    /**
     * Setzt das Limit der Fehleranzeige
     *
     * @param $limit
     * @return Admin_Model_Buchungsfehler
     */
    public function setLimit($limit)
    {
        $limit = (int) $limit;
        $this->limit = $limit;

        return $this;
    }

    /**
     * Gibt die Anzahl der Fehler zurück
     *
     * @return int
     */
    public function getAnzahlFehler()
    {
        return $this->anzahlFehler;
    }

    /**
     * Ermittelt Name und Vorname des Kunden
     *
     * @return int
     */
    private function aufbereitenFehlermeldungen($fehler)
    {
        for ($i=0; $i < count($fehler); $i++) {
            $personenDaten = $this->findKundenDaten($fehler[$i]['kundenId']);

            $this->fehler[$i]['id'] = $fehler[$i]['id'];
            $this->fehler[$i]['date'] = $fehler[$i]['date'];
            $this->fehler[$i]['lastname'] = $personenDaten['lastname'];
            $this->fehler[$i]['firstname'] = $personenDaten['firstname'];
            $this->fehler[$i]['status'] = $fehler[$i]['status'];
            $this->fehler[$i]['buchungsnummer'] = $fehler[$i]['buchungsnummer'];
            $this->fehler[$i]['kundenId'] = $personenDaten['id'];
        }

        return $i;
    }

    /**
     * Findet die Personendaten entsprechend einer Kunden Id
     *
     * @param $kundenId
     * @return array
     * @throws nook_Exception
     */
    private function findKundenDaten($kundenId)
    {
        $personenDaten = $this->tabelleAdressen->find($kundenId)->toArray();

        if(count($personenDaten) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);

        return $personenDaten[0];
    }

    /**
     * Ermittelt die Exception die eine Kunden ID haben
     *
     * @return int
     */
    private function findException()
    {
        $whereReaction = "reaction = '".$this->condition_reaction_exception."'";
        $whereKundenId = "kundenId > ".$this->condition_kunden_id_unbekannt;
        $whereBuchungsnummer = "buchungsnummer > 0";

        $select = $this->tabelleException->select();
        $select
            ->where($whereReaction)
            ->where($whereKundenId)
            ->where($whereBuchungsnummer)
            ->limit($this->limit, $this->start)
            ->order('date desc');

        $rows = $this->tabelleException->fetchAll($select)->toArray();

        if(count($rows) > 0)
            return $rows;

        return false;
    }


} // end class

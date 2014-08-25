<?php
/**
 * Bestimmen der Kundendaten und der Buchungsnummern des Kunden.
 *
 * + Setzen der aktuellen Buchungsnummer
 * + Setzen des aktuellen Zaehler
 * + Gibt die Daten der Hotelbuchung zurück
 * + Ermittelt die Anzahl der Hotelbuchungen
 * + Ermittelt die Anzahl der Produktbuchungen
 * + Ermittelt die Anzahl der Programmbuchungen
 * + Ermittelt Kundendaten.
 * + Ermittelt die Rechnungsdaten aus Tabelle 'tbl_rechnungen' für die aktuelle Session
 * + Findet mit Session ID
 * + Findet die Hotelbuchungen
 * + Setzt die Mwst der Hotelbuchungen auf
 * + Findet die Produktbuchungen
 * + Ermittelt die Mehrwertsteuer
 * + Findet die Programmbuchungen einer Buchungsnummer
 * + Ermittelt die aktuelle Buchungsnummer
 * + Ermittelt die Kundendaten
 * + Setzt den Status der Buchungsdatensaetze
 * + Holt statische Texte der Firma
 *
 * @author Stephan.Krauss
 * @date 18.11.2013
 * @file Bestellung.php
 * @package front
 * @subpackage model
 */
class Front_Model_Bestellung extends nook_ToolModel implements arrayaccess
{

    // Font der Texte im Pdf
    protected $_fontTexte = array(
        "ueberschrift" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 10
        ),
        "text" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 8
        ),
        "minitext" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 6
        )
    );

    // allgemeine Angaben
    protected $aktuelleBuchungsnummer = null; // aktuelle Buchungsnummer
    protected $aktuellerZaehler = null; // aktueller Zaehler

    protected $_kundenId = null;
    protected $_kundenDaten = array(); // Personendaten Kunde
    protected $_buchungsnummernArray = array();
    protected $_buchungsnummernString = null;

    // Datensätze
    protected $_datenHotelbuchungen = array();
    protected $_datenProgrammbuchungen = array();
    protected $_datenProduktbuchungen = array();
    protected $_datenRechnungen = array();
    protected $_statischeFirmenTexte = array();

    // Fehler
    private $_error_kein_int = 1000;
    private $_error_kunden_id_unbekannt = 1001;
    private $_error_keine_kundendaten_vorhanden = 1002;
    private $_error_keine_buchungsnummern_vorhanden = 1003;
    private $_error_produkt_unbekannt = 1004;
    private $_error_keine_rechnungsdaten_vorhanden = 1005;

    // Tabellen / Views
    private $_tabelleAdressen = null;
    private $_tabelleBuchungsnummer = null;
    private $_tabelleRechnungen = null;
    private $_tabelleTextbausteine = null;

    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleProgrammbuchung = null;

    private $_tabelleProducts = null; // Hotelprodukte

    // Konditionen
    private $_condition_artikel_gebucht = 3;
    private $_condition_artikel_bearbeitet = 4;
    private $_condition_mwst_uebernachtung = 7; // Mwst Übernachtung, Deutschland

    private $_condition_zaehler_aktive_buchung = 0;
    private $_condition_erste_buchung = 1;
    private $condition_status_storniert = 10;
    private $condition_status_storniert_nacharbeit = 9;

    public function __construct()
    {
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabelleRechnungen Application_Model_DbTable_rechnungen */
        $this->_tabelleRechnungen = new Application_Model_DbTable_rechnungen();
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabellePreise Application_Model_DbTable_programmbuchung */
        $this->_tabellePreise = new Application_Model_DbTable_preise();
        /** @var _tabelleTextbausteine Application_Model_DbTable_textbausteine */
        $this->_tabelleTextbausteine = new Application_Model_DbTable_textbausteine();
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabellePreisebeschreibung = new Application_Model_DbTable_preiseBeschreibung();


        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array( 'db' => 'hotels' ));
    }

    /**
     * Setzen der aktuellen Buchungsnummer
     *
     * @param $aktuelleBuchungsnummer
     * @return Front_Model_Bestellung
     */
    public function setAktuelleBuchungsnummer($aktuelleBuchungsnummer)
    {
        $this->aktuelleBuchungsnummer = $aktuelleBuchungsnummer;

        return $this;
    }

    /**
     * Setzen des aktuellen Zaehler
     *
     * @param $aktuellerZaehler
     * @return $this
     */
    public function setAktuellerZaehler($aktuellerZaehler)
    {
        $this->aktuellerZaehler = $aktuellerZaehler;

        return $this;
    }

    /**
     * Gibt die Daten der Hotelbuchung zurück
     *
     * @return array
     */
    public function getHotelbuchungen()
    {
        return $this->_datenHotelbuchungen;
    }

    /**
     * @return array
     */
    public function getProgrammbuchungen()
    {
        return $this->_datenProgrammbuchungen;
    }

    /**
     * Ermittelt die Anzahl der Hotelbuchungen
     *
     * @return int
     */
    public function getCountDatenHotelbuchungen()
    {
        $anzahl = 0;

        if (!empty($this->_datenHotelbuchungen)) {
            $anzahl = count($this->_datenHotelbuchungen);
        }

        return $anzahl;
    }

    /**
     * Ermittelt die Anzahl der Produktbuchungen
     *
     * @return int
     */
    public function getCountDatenProduktbuchungen()
    {
        $anzahl = 0;

        if (!empty($this->_datenProduktbuchungen)) {
            $anzahl = count($this->_datenProduktbuchungen);
        }

        return $anzahl;
    }

    /**
     * Ermittelt die Anzahl der Programmbuchungen
     *
     * @return int
     */
    public function getCountDatenProgrammbuchungen()
    {
        $anzahl = 0;

        if (!empty($this->_datenProgrammbuchungen)) {
            $anzahl = count($this->_datenProgrammbuchungen);
        }

        return $anzahl;
    }

    /**
     * Steuerung der Ermittlung der Daten.
     *
     * + Ermittelt Buchungsdatensätze.

     */
    public function ermittelnBuchungen()
    {
        $this
            ->_findeKundenId()
            ->_ermittleKundenDaten()

            ->_ermittleBuchungsnummern() // Buchungsnummern des Kunden
            ->_findRechnungsdaten() // aktuelle Session / Buchungsnummer

            ->_findHotelbuchungen() // Daten Hotelbuchungen
            ->_setMwstHotelbuchungen()

            ->_findProduktbuchungen() // Daten Produktbuchungen
            ->_setMwstProdukte() // Mwst der Hotelprodukte

            ->_findProgrammbuchungen() // Programmbuchungen

            ->_findAllPreise()

            ->_findStatischeFirmenTexte() // Texte Kopfzeile Fusszeile

            ->_setStatusBearbeitetDurchSystem();

        return $this;
    }

    /**
     * Ermittelt die Rechnungsdaten aus Tabelle 'tbl_rechnungen' für die aktuelle Session
     * + kompletter Datensatz 'tbl_rechnungen'
     *
     * @return Front_Model_Bestellung
     */
    private function _findRechnungsdaten()
    {

        // $aktuelleBuchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $sessionBuchung = new Zend_Session_Namespace('buchung');
        $sessionBuchungVariablen = (array) $sessionBuchung->getIterator();
        $buchungsNummer = $sessionBuchungVariablen['buchungsnummer'];


        $select = $this->_tabelleRechnungen->select()->where("buchungsnummer_id = " . $buchungsNummer);
        $rechnungsDaten = $this->_tabelleRechnungen->fetchAll($select)->toArray();

        $this->_datenRechnungen = $rechnungsDaten[0];

        return $this;
    }

    private function _findAllPreise()
    {

        // $aktuelleBuchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $sessionBuchung = new Zend_Session_Namespace('buchung');
        $sessionBuchungVariablen = (array) $sessionBuchung->getIterator();
        $buchungsNummer = $sessionBuchungVariablen['buchungsnummer'];


        $select = $this->_tabelleProgrammbuchung->select()->where("buchungsnummer_id = " . $buchungsNummer);
        $allePreise = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();


        for ($i = 0; $i<count($allePreise); $i++) {


            //Preise holen
            $select = $this->_tabellePreise->select()->where("id = " .$allePreise[$i]['tbl_programme_preisvarianten_id']);
            $allePreiseVarianten = $this->_tabellePreise->fetchAll($select)->toArray();
            $allePreise[$i]['ek'] = $allePreiseVarianten[0]['einkaufspreis'];
            $allePreise[$i]['vk'] = $allePreiseVarianten[0]['verkaufspreis'];

            $select = $this->_tabelleProgrammbeschreibung->select();
            $select
                ->where("programmdetail_id = ".$allePreise[$i]['programmdetails_id'])
                ->where("sprache = 1");

            //Programmname holen
            $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();
            $allePreise[$i]['progname'] = $rows[0]['progname'];



            $select = $this->_tabellePreisebeschreibung->select();
            $select
                ->where("preise_id = ".$allePreise[$i]['tbl_programme_preisvarianten_id'])
                ->where("sprachen_id = 1");

            $rows = $this->_tabellePreisebeschreibung->fetchAll($select)->toArray();
            $allePreise[$i]['varName'] = $rows[0]['preisvariante'];

        }


        $_SESSION['allePreise'] = $allePreise;


        return $this;
    }

    /**
     * Findet mit Session ID
     * die Kundennummer in der
     * 'tbl_buchungsnummer'
     *
     * @return Front_Model_Bestellung
     */
    private function _findeKundenId()
    {
        $this->_kundenId = nook_ToolUserId::bestimmeKundenIdMitSession();

        return $this;
    }

    /**
     * Findet die Hotelbuchungen
     *
     * @return Front_Model_Bestellung
     */
    private function _findHotelbuchungen()
    {

        $whereBuchungsnummern = new Zend_Db_Expr("buchungsnummer_id IN (" . $this->_buchungsnummernString . ")");

        $select = $this->_tabelleHotelbuchung->select();
        $select->where($whereBuchungsnummern)->order('teilrechnungen_id ASC');

        $query = $select->__toString();

        $this->_datenHotelbuchungen = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Setzt die Mwst der Hotelbuchungen auf
     * 7% für Deutschland
     *
     * @return Front_Model_Bestellung
     */
    private function _setMwstHotelbuchungen()
    {

        for ($i = 0; $i < count($this->_datenHotelbuchungen); $i++) {
            $this->_datenHotelbuchungen[$i]['mwst'] = $this->_condition_mwst_uebernachtung;
        }

        return $this;
    }

    /**
     * Findet die Produktbuchungen
     *
     * @return Front_Model_Bestellung
     */
    private function _findProduktbuchungen()
    {

        $whereBuchungsnummern = new Zend_Db_Expr("buchungsnummer_id IN (" . $this->_buchungsnummernString . ")");

        $select = $this->_tabelleProduktbuchung->select();
        $select->where("status = " . $this->_condition_artikel_gebucht)->where($whereBuchungsnummern)->order(
            'teilrechnungen_id ASC'
        );

        $this->_datenProduktbuchungen = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Ermittelt die Mehrwertsteuer
     * der Hotel Zusatzprodukte
     *
     * @return Front_Model_Bestellung
     */
    private function _setMwstProdukte()
    {

        $cols = array(
            'vat'
        );

        /** @var $log Zend_Log_Writer_Db */
        $log = Zend_Registry::get('log');

        for ($i = 0; $i < count($this->_datenProduktbuchungen); $i++) {

            $select = $this->_tabelleProducts->select();
            $select->from($this->_tabelleProducts, $cols);
            $select->where("id = " . $this->_datenProduktbuchungen[$i]['products_id']);
            $produktDaten = $this->_tabelleProducts->fetchRow($select);

            if (is_object($produktDaten)) {
                $row = $produktDaten->toArray();
                $this->_datenProduktbuchungen[$i]['mwst'] = $row['vat'];
            } else {
                throw new nook_Exception($this->_error_produkt_unbekannt);
            }

        }

        return $this;
    }

    /**
     * Findet die Programmbuchungen einer Buchungsnummer
     * + fidet die Erstbuchung der Programme
     * + findet wenn vorhanden die aktuelle Programmbuchung
     * + sortiert nach den Zaehler
     *
     * @return Front_Model_Bestellung
     */
    private function _findProgrammbuchungen()
    {
        $whereBuchungsnummer = "buchungsnummer_id = " . $this->aktuelleBuchungsnummer;
        $whereAktuellerZaehler = "zaehler = " . $this->aktuellerZaehler;

        $select = $this->_tabelleProgrammbuchung->select();

        $select
            ->where($whereBuchungsnummer)
            ->where($whereAktuellerZaehler)
            ->order('programmdetails_id');

        $this->_datenProgrammbuchungen = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Ermittelt die aktuelle Buchungsnummer
     * des Kunden. Es wird nur die aktuelle Buchungsnummer ausgewertet
     *
     * @return Front_Model_Bestellung
     */
    private function _ermittleBuchungsnummern()
    {

        // $buchungsNummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $sessionBuchung = new Zend_Session_Namespace('buchung');
        $sessionBuchungVariablen = (array) $sessionBuchung->getIterator();
        $buchungsNummer = $sessionBuchungVariablen['buchungsnummer'];

        $this->_buchungsnummernArray[] = $buchungsNummer;
        // $this->_buchungsnummernString = implode(array_map('intval', $this->_buchungsnummernArray), ',');
        $this->_buchungsnummernString = $buchungsNummer;

        return $this;
    }

    /**
     * Ermittelt die Kundendaten
     * Fehler wenn Kunde unbekannt.
     *
     * @return Front_Model_Bestellung
     * @throws nook_Exception
     */
    private function _ermittleKundendaten()
    {
        $kundenDaten = $this->_tabelleAdressen->find($this->_kundenId)->toArray();

        if (count($kundenDaten) <> 1)
            throw new nook_Exception($this->_error_keine_kundendaten_vorhanden);

        $this->_kundenDaten = $kundenDaten[0];

        return $this;
    }

    /**
     * Setzt den Status der Buchungsdatensaetze
     * auf Status '4' .
     * Bearbeitet durch System.
     *
     * @return Front_Model_Bestellung
     */
    private function _setStatusBearbeitetDurchSystem()
    {

        $update = array(
            "status" => $this->_condition_artikel_bearbeitet
        );


        $where = array(
            new Zend_Db_Expr("buchungsnummer_id IN (" . $this->_buchungsnummernString . ")"),
            "status <> '".$this->condition_status_storniert."'",
            "status <> '".$this->condition_status_storniert_nacharbeit."'"
        );

        // Hotelbuchungen
        $this->_tabelleHotelbuchung->update($update, $where);
        // Produktbuchungen
        $this->_tabelleProduktbuchung->update($update, $where);
        // Programmbuchung
        $this->_tabelleProgrammbuchung->update($update, $where);

        return $this;
    }

    /**
     * Holt statische Texte der Firma
     * und fügt diese in die erste
     * Pdf - Seite des Dokumentes ein.
     *
     * @return Front_Model_Bestellung
     */
    private function _findStatischeFirmenTexte()
    {

        $cols = array(
            "text",
            "blockname"
        );

        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $select = $this->_tabelleTextbausteine->select();
        $select->from($this->_tabelleTextbausteine, $cols)->where("sprache_id = " . $anzeigeSpracheId);

        $statischeFirmenTexte = $this->_tabelleTextbausteine->fetchAll($select)->toArray();

        for ($i = 0; $i < count($statischeFirmenTexte); $i++) {
            $this->_statischeFirmenTexte[$statischeFirmenTexte[$i]['blockname']] = $statischeFirmenTexte[$i]['text'];
        }

        return $this;
    }

} // end class
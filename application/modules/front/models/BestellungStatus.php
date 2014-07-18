<?php
/**
* Setzt den Status in den Tabellen der Buchung
*
* + Setzt den Status in den Buchungstabellen
* + Ermitteln Session ID
* + Setzen des Status der Bestellung in
* + bestimmt die Registrierungs Nummer
* + Setzt den Status der Hotelbuchung auf gebucht
* + Setzt den Status der Produktbuchungen auf gebucht
* + Setzt den Status der Buchung in der Tabelle 'tbl_programmbuchung'
* + Setzt die HOB Nummer in 'tbl_xml_buchung'
*
* @author Stephan.Krauss
* @date 02.05.13
* @file BestellungStatus.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungStatus extends nook_ToolModel implements arrayaccess{

    // Tabellen / Views
    private $_tabelleBuchungsnummer = null;
    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleProgrammbuchung = null;
    private $_tabelleXmlBuchung = null;

    // Error
    private $_error_anzahl_datensaetze_stimmt_nicht = 1460;

    // Konditionen
    private $_condition_tabelle_buchungsnummer_status_uebermittelt = 4;
    private $_condition_tabelle_hotelbuchung_status_gebucht = 3;
    private $_condition_tabelle_programmbuchung_status_gebucht = 4;
    private $_condition_tabelle_produktbuchung_status_gebucht = 4;
    protected $condition_status_storniert = 10;
    protected $condition_status_storno_mit_nacharbeit = 9;
    protected $condition_preisvariante_storniert_anzahl = 0;

    // Flags

    protected $_sessionId = null;
    protected $_buchungsnummerId = null;
    protected $registrierungsNummer = null;

    /**
     * Übernahme der benötigten Tabellen
     */
    public function __construct ()
    {
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();
    }

    /**
     * Setzt den Status in den Buchungstabellen
     *
     * + Session ID
     * + Buchungsnummer bestimmen
     * + 'tbl_hotelbuchung'
     * + 'tbl_produktbuchung'
     * + 'tbl_programmbuchung'
     *
     * @return Front_Model_BestellungStatus
     */
    public function steuerungSetzenStatusBuchungen(){

        $this->_sessionIdBestimmen();
        $this->_buchungsnummerBestimmen();

        $this->setzenStatusTabelleBuchungsnummer();
        $this->besimmeRegistrierungsNummer();

        $where = array(
            "buchungsnummer_id = '".$this->_buchungsnummerId."'",
            "status <> '".$this->condition_status_storniert."'",
            "status <> '".$this->condition_status_storno_mit_nacharbeit."'"
        );

        $this->_setzenStatusTabelleHotelbuchung($where);
        $this->_setzenStatusTabelleProduktbuchung($where);
        $this->_setzenStatusTabelleProgrammbuchung($where);

        return $this;

    }

    /**
     * Ermitteln Session ID
     */
    private function _sessionIdBestimmen(){

        $this->_sessionId = Zend_Session::getId();

        return;
    }

    private function _buchungsnummerBestimmen(){

        $cols = array(
            'id'
        );

        $where = "session_id = '".$this->_sessionId."'";

        $select = $this->_tabelleBuchungsnummer->select();
        $select->from($this->_tabelleBuchungsnummer, $cols)->where($where);

        $rows = $this->_tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);

        $this->_buchungsnummerId = $rows[0]['id'];

        return;
    }

    /**
     * Setzen des Status der Bestellung in
     *
     * 'tbl_buchungsnummer'
     */
    private function setzenStatusTabelleBuchungsnummer(){

        $cols = array(
            'status' => $this->_condition_tabelle_buchungsnummer_status_uebermittelt
        );

        $where = "session_id = '".$this->_sessionId."'";

        $this->_tabelleBuchungsnummer->update($cols,$where);

        return;
    }

    /**
     * bestimmt die Registrierungs Nummer
     *
     * @return int
     */
    private function besimmeRegistrierungsNummer()
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsNummer = $toolRegistrierungsNummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        $this->registrierungsNummer = $registrierungsNummer;

        return $registrierungsNummer;
    }

    /**
     * Setzt den Status der Hotelbuchung auf gebucht
     */
    private function _setzenStatusTabelleHotelbuchung($where){

        $cols = array(
            'status' => $this->_condition_tabelle_hotelbuchung_status_gebucht
        );

        $where = "buchungsnummer_id = '".$this->_buchungsnummerId."'";

        $this->_tabelleHotelbuchung->update($cols,$where);

        return;
    }

    /**
     * Setzt den Status der Produktbuchungen auf gebucht
     */
    private function _setzenStatusTabelleProduktbuchung($where){

        $cols = array(
            'status' => $this->_condition_tabelle_produktbuchung_status_gebucht
        );

        $where = "buchungsnummer_id = '".$this->_buchungsnummerId."'";

        $this->_tabelleProduktbuchung->update($cols,$where);

        return;
    }

    /**
     * Setzt den Status der Buchung in der Tabelle 'tbl_programmbuchung'
     */
    private function _setzenStatusTabelleProgrammbuchung($where){

        $cols = array(
            'status' => $this->_condition_tabelle_programmbuchung_status_gebucht
        );

        $this->_tabelleProgrammbuchung->update($cols,$where);

        return;
    }
} // end class
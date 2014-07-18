<?php
/**
* Setzt den Status der Artikel eines Warenkorbes
*
* + Kontrolle ob AGB bestätigt
* + Steuert das kopieren der Session Daten ind die Tabelle 'tbl_buchungsnummer'
* + Kopiert die Session Daten
* + Kontrolle des Status der Anmeldung
* + Setzt des Status für die Tabellen
* + Eine Buchung vormerken
* + Setzt den Status der gebuchten Programme in 'tbl_programmbuchung'
* + Setzt den Status der Hotelbuchungen in 'tbl_hotelbuchung'
* + Setzt den Status der Hotelbuchungen in 'tbl_produktbuchung'
* + Erstellt neue Session ID
* + Erstellt eine neue Session.
* + Kopiert den Inhalt der alten Session
* + Kontrolliert ob Buchungen vorliegen
*
* @date 04.25.2013
* @file OrderdataStatus.php
* @package front
* @subpackage model
*/
class Front_Model_OrderdataStatus{

    // Konditionen
    private $_condition_warenkorb_aktiv = 0;

    // Fehler
    private $_error_nicht_int = 1070;
    private $_error_unzulaessiger_status = 1071;
    private $_error_agb_nicht_bestaetigt = 1072;
    private $_error_session_nicht_vorhanden = 1073;

    // Views und Tabellen
    private $_tabelleBuchungsnummer = null;
    private $_tabelleSessions = null;

    private $_tabelleProgrammbuchung = null;
    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;

    protected $statusWarenkorb = null;
    protected $registrierungsnummer = null;
    protected $benutzerId = null;

    public function __construct(){
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabelleSessions  */
        $this->_tabelleSessions = new Application_Model_DbTable_sessions();

        /** @var _tabelleProgrammbuchung  */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabelleHotelbuchung  */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** $var _tabelleProduktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

    }

    /**
     * Kontrolle ob AGB bestätigt
     *
     * @param bool $__agb
     * @return Front_Model_OrderdataStatus
     * @throws nook_Exception
     */
    public function checkAgb($__agb = false){
        if(empty($__agb) or $__agb != 'agb')
            throw new nook_Exception($this->_error_agb_nicht_bestaetigt);

        return $this;
    }

    /**
     * Steuert das kopieren der Session Daten ind die Tabelle 'tbl_buchungsnummer'
     *
     * @return Front_Model_OrderdataStatus
     */
    public function kopierenSessionDaten(){
        $this->_kopierenSessionDaten();

        return $this;
    }

    /**
     * Kopiert die Session Daten
     * in 'tbl_buchungsnummer'.
     *
     * @return Front_Model_OrderdataStatus
     * @throws nook_Exception
     */
    private function _kopierenSessionDaten(){
        $sessionId = Zend_Session::getId();

        $select = $this->_tabelleSessions->select();
        $select->where("sess_id = '".$sessionId."'");

        $rows = $this->_tabelleSessions
            ->fetchAll($select)
            ->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_session_nicht_vorhanden);

        $update = array(
            'sess_data' => $rows[0]['sess_data']
        );

        $where = "session_id = '".$sessionId."'";
        $ergebnis = $this->_tabelleBuchungsnummer->update($update, $where);

        return $this;
    }


    /**
     * Kontrolle des Status der Anmeldung
     *
     * @param $__kundenId
     * @return Front_Model_OrderdataStatus
     * @throws nook_Exception
     */
    public function checkStatus($__statusBestellung){

        $statusBestellung = (int) $__statusBestellung;

        if (!is_int($statusBestellung))
            throw new nook_Exception($this->_error_nicht_int);

        if($statusBestellung < 2 or $statusBestellung > 3)
            throw new nook_Exception($this->_error_unzulaessiger_status);


        return $this;
    }

    /**
     * Setzt des Status für die Tabellen
     *
     * @param $__status
     * @return Front_Model_OrderdataStatus
     */
    public function setStatus($__status){
        $this->statusWarenkorb = $__status;

        return $this;
    }

    /**
     * @param $benutzerId
     * @return Front_Model_OrderdataStatus
     */
    public function setBenutzerId($benutzerId = false)
    {
        if(empty($benutzerId))
            return;

        $benutzerId = (int) $benutzerId;
        $this->benutzerId = $benutzerId;

        return $this;
    }

    /**
     * Steuerung setzen Status Vormerkung einerEine Buchung
     *
     * + setzen Status Vormerkung in Buchungstabelle
     * + bestimmen Registrierungsnummer / HOB Nummer
     *
     * @return Front_Model_OrderdataStatus
     */
    public function setzenStatusTabelleBuchungsnummer(){
        $this->statusVormerkungBuchungstabelle();
        $this->bestimmenRegistrierungsnummer();

        return $this;
    }

    /**
     * Setzen Status in Buchungstabelle
     *
     * @return int
     */
    private function statusVormerkungBuchungstabelle()
    {
        $sessionId = Zend_Session::getId();

        // neuen Datensatz in 'tbl_buchungsnummer'
        $buchungsNummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        if($buchungsNummer === false){
            $toolNeueRegistrierungsnummer = new nook_ToolNeueRegistrierungsnummer();
            $buchungsNummer = $toolNeueRegistrierungsnummer
                ->setSessionId($sessionId)
                ->steuerungSetzenHobnummerInTblBuchungsnummer()
                ->getNeueHobNummer();
        }

        $update = array(
            "status" => $this->statusWarenkorb
        );

        if(!empty($this->benutzerId))
            $update['kunden_id'] = $this->benutzerId;

        $where = "session_id='".$sessionId."'";

        $this->_tabelleBuchungsnummer->update($update, $where);

        return $this->statusWarenkorb;
    }

    /**
     * Bestimmen Registrierungsnummer / HOB Nummer aus der Buchungstabelle
     *
     * @return int
     */
    private function bestimmenRegistrierungsnummer()
    {
        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsnummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        $this->registrierungsnummer = $registrierungsnummer;

        return $registrierungsnummer;
    }

    /**
     * Setzt den Status der gebuchten Programme in 'tbl_programmbuchung'
     *
     * + setzt Status auf 2
     *
     * @return Front_Model_OrderdataStatus
     */
    public function  setzenStatusTabelleProgrammbuchung()
    {
        $buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $where = array(
            "buchungsnummer_id = ".$buchungsnummerId,
            "zaehler = ".$this->_condition_warenkorb_aktiv
        );

        $update = array(
            'status' => $this->statusWarenkorb,
            'hobNummer' => $this->registrierungsnummer
        );

        $kontrolle = $this->_tabelleProgrammbuchung->update($update, $where);

        return $this;
    }

    /**
     * Setzt den Status der Hotelbuchungen in 'tbl_hotelbuchung'
     *
     * + setzt Status auf 2
     *
     * @return Front_Model_OrderdataStatus
     */
    public function setzenStatusTabelleHotelbuchung()
    {
        $buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $where = array(
            "buchungsnummer_id = ".$buchungsnummerId,
            "zaehler = ".$this->_condition_warenkorb_aktiv
        );

        $update = array(
            'status' => $this->statusWarenkorb,
            'hobNummer' => $this->registrierungsnummer
        );

        $kontrolle = $this->_tabelleHotelbuchung->update($update, $where);

        return $this;
    }

    /**
     * Setzt den Status der Hotelbuchungen in 'tbl_produktbuchung'
     *
     * + setzt Status auf 2
     *
     * @return Front_Model_OrderdataStatus
     */
    public function setzenStatusTabelleProduktbuchung()
    {
        $buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $where = array(
            "buchungsnummer_id = ".$buchungsnummerId,
            "zaehler = ".$this->_condition_warenkorb_aktiv
        );

        $update = array(
            'status' => $this->statusWarenkorb,
            'hobNummer' => $this->registrierungsnummer
        );

        $kontrolle = $this->_tabelleProduktbuchung->update($update, $where);

        return $this;
    }

    /**
     * Erstellt neue Session ID
     * Kopiert alle Session Werte der
     * 'alten' Session ID in die neue
     * Session
     *
     * @return Front_Model_OrderdataStatus
     */
    public function neueSession(){
        $this->_neueSession();

        return $this;
    }


    /**
     * Erstellt eine neue Session.
     * Übernimmt alle Werte der Session
     * unter einer neuen Session ID
     *
     * @return Front_Model_OrderdataStatus
     */
    private function _neueSession()
    {
        // alte Session ID
        $alteSessionId = Zend_Session::getId();

        $cols = array(
            'sess_data'
        );

        $where = "session_id = '".$alteSessionId."'";

        $select = $this->_tabelleBuchungsnummer->select();
        $select->from($this->_tabelleBuchungsnummer, $cols)->where($where);

        $rows = $this->_tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) == 1)
            $this->_vererbenInhaltSession($rows);
        elseif(count($rows) > 1)
            throw new nook_Exception($this->_error_session_nicht_vorhanden);

        return;
    }

    /**
     * Kopiert den Inhalt der alten Session
     * in die neue Session
     *
     * @param $__rows
     * @return int
     */
    private function _vererbenInhaltSession($__rows){

        // neue Session ID
        nook_ToolSession::erstelleNeueSession();

        // neue Session ID
        $neueSessionID = Zend_Session::getId();

        // Update Session Daten
        $update = array(
            "sess_data" => $__rows[0]['sess_data']
        );

        $where = "sess_id = '".$neueSessionID."'";

        $anzahl = $this->_tabelleSessions->update($update, $where);

        return $anzahl;
    }

    /**
     * Kontrolliert ob Buchungen vorliegen
     *
     * Liegen Buchungen vor = true
     * liegen keine Buchungen vor = false
     *
     * @return bool
     */
    public function checkExistBuchungen(){

        // Kontrolle Buchungsnummer
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        if(empty($buchungsnummer))
            return false;

        // Kontrolle Hotelbuchungen
        $kontrolleHotelbuchung = nook_ToolBuchungsnummer::existierenHotelbuchungen($buchungsnummer);

        // Kontrolle Programmbuchung
        $kontrolleProgrammbuchung = nook_ToolBuchungsnummer::existierenProgrammbuchungen($buchungsnummer);

        if( empty($kontrolleHotelbuchung) and empty($kontrolleProgrammbuchung) )
            return false;
        else
            return true;
    }
}
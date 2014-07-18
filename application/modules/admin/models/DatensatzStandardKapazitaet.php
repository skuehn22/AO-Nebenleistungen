<?php
/**
* Verwaltet die Tageskapazität eines Programmes
*
* + Übernimmt das deutsche Datum
* + Nimmt die aktuelle Zeit entgegen
* + Ermittelt die datensätze der Tabelle in Abhängigkeit des Startwertes und des Limit
* + Ermittelt Datensätze der Tabelle
* + Ermitteln der Anzahl der Datensaetze eines Programmes
* + Formatiert das Datum der Datensätze der
* + Gibt Anzahl der Datensätze
* + Trägt die Kapazität für ein Programm
* + Trägt die Kapazität für ein Programm
* + Trägt die Standard Kapazität
* + Trägt die Standard Kapazität
* + Übergabe der zu löschenden Kapazitäten
* + String der Kapazitäten
* + Löscht die kapazität
*
* @date 27.21.2013
* @file DatensatzStandardKapazitaet.php
* @package admin
* @subpackage model
*/
class Admin_Model_DatensatzStandardKapazitaet extends nook_ToolModel{

    // Fehler
    private $_error_kein_int = 1280;
    private $_error_daten_unvollstaendig = 1281;
    private $_error_anzahl_datensaetze_falsch = 1282;
    private $_error_datum_falsch = 1283;
    private $_error_zeit_falsch = 1284;

    // Tabellen / Views / Datenbanken
    private $_tabelleProgrammdetails = null;
    private $_tabelleProgrammdetailsKapazitaet = null;

    // Konditionen
    private $_condition_moegliche_anzahl_datensaetze = 1;
    private $_condition_limit_datensaetze = 10;

    // Flags


    protected $_programmId = null;
    protected $_kapazitaet = null;
    protected $_datum = null;
    protected $_zeit = null;
    protected $_start = null;
    protected $_anzahlDatensaetze = 0;
    protected $_datensaetzeTabelle = array();
    protected $_kapazitaetenId = array();

    public function __construct(){
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleProgrammdetailsKapazitaet Application_Model_DbTable_programmdetailsKapazitaet */
        $this->_tabelleProgrammdetailsKapazitaet = new Application_Model_DbTable_programmdetailsKapazitaet();
    }

    /**
     * @param $__programmId
     * @return Admin_Model_DatensatzStandardKapazitaet
     * @throws nook_Exception
     */
    public function setProgrammId($__programmId){

        $programmId = (int) $__programmId;
        if( ($programmId == 0) or (!is_int($programmId)) )
            throw new nook_Exception($this->_error_kein_int);

        $this->_programmId = $programmId;

        return $this;
    }

    /**
     * @param $__kapazitaet
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function setKapazitaet($__kapazitaet){

        $kapazitaet = (int) $__kapazitaet;
        if( ($kapazitaet == 0) or (!is_int($kapazitaet)) )
            throw new nook_Exception($this->_error_kein_int);

        $this->_kapazitaet = $kapazitaet;

        return $this;
    }

    /**
     * Übernimmt das deutsche Datum
     *
     *
     * @param $__deutschesDatum
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function setDatum($__deutschesDatum){

        $valdateDate = new Zend_Validate_Date(array('locale' => 'de'));
        if($valdateDate->isValid($__deutschesDatum) !== true)
            throw new nook_Exception($this->_error_datum_falsch);

        $this->_datum = $__deutschesDatum;

        return $this;
    }

    /**
     * Nimmt die aktuelle Zeit entgegen
     *
     * @param $__zeit
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function setZeit($__zeit){

        $validateZeit = new Zend_Validate_Date(array("format" => "H:i"));
        if($validateZeit->isValid($__zeit) !== true)
            throw new nook_Exception($this->_error_zeit_falsch);

        $this->_zeit = $__zeit;

        return $this;
    }

    /**
     * @param $__startWert
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function setStart($__startWert){

        $startWert = (int) $__startWert;
        $this->_start = $startWert;

        return $this;
    }

    /**
     * Ermittelt die datensätze der Tabelle in Abhängigkeit des Startwertes und des Limit
     *
     * + Datensätze der Tabelle mit Limit
     * + Anzahl aller Datensätze eines Programmes
     * + formatier Datum der Datensätze
     *
     * @return mixed
     * @throws nook_Exception
     */
    public function getDatensaetzeTabelle(){

        if( empty($this->_programmId) )
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->_getDatensaetzeTabelle(); // holt Datensätze der Tabelle
        $this->anzahlDatensaetzeProgramm(); // Anzahl aller Datensätze eines Programmes
        $this->_formatDatumDatensaetzeTabelle(); // formatier Datum der Datensätze

        return $this->_datensaetzeTabelle;
    }

    /**
     * Ermittelt Datensätze der Tabelle
     * entsprechend
     * + Programm ID
     * + Startwert
     * + Limit
     *
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    private function _getDatensaetzeTabelle(){

        $select = $this->_tabelleProgrammdetailsKapazitaet->select();
        $select
            ->where("programmdetails_id = ".$this->_programmId)
            ->order('datum')
            ->limit($this->_condition_limit_datensaetze, $this->_start);

        $this->_datensaetzeTabelle = $this->_tabelleProgrammdetailsKapazitaet->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Ermitteln der Anzahl der Datensaetze eines Programmes
     *
     * @return $this
     */
    private function anzahlDatensaetzeProgramm()
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $select = $this->_tabelleProgrammdetailsKapazitaet->select();
        $select
            ->from($this->_tabelleProgrammdetailsKapazitaet, $cols)
            ->where("programmdetails_id = ".$this->_programmId)
            ->order('datum');

        $rows = $this->_tabelleProgrammdetailsKapazitaet->fetchAll($select)->toArray();

        $this->_anzahlDatensaetze = $rows[0]['anzahl'];

        return $this;
    }

    /**
     * Formatiert das Datum der Datensätze der
     * Übersichtstabelle
     * + Datum
     * + Zeit
     * + Wochentag
     *
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    private function _formatDatumDatensaetzeTabelle(){

        $tag = array();

        $tag[0] = "Sonntag";
        $tag[1] = "Montag";
        $tag[2] = "Dienstag";
        $tag[3] = "Mittwoch";
        $tag[4] = "Donnerstag";
        $tag[5] = "Freitag";
        $tag[6] = "Samstag";

        for($i = 0; $i < count($this->_datensaetzeTabelle); $i++){

            $date = new DateTime($this->_datensaetzeTabelle[$i]['datum']);
            $nummerDesWochentages = $date->format("w");

            $this->_datensaetzeTabelle[$i]['zeit'] = $date->format("H:i");
            $this->_datensaetzeTabelle[$i]['wochentag'] = $tag[$nummerDesWochentages];
            $this->_datensaetzeTabelle[$i]['datum'] = $date->format("d.m.Y");
        }

        return $this;
    }

    /**
     * Gibt Anzahl der Datensätze
     * der Tabelle zurück
     *
     * @return int
     */
    public function getAnzahlDatensaetze(){

        return $this->_anzahlDatensaetze;
    }

    /**
     * Trägt die Kapazität für ein Programm
     * zu einem bestimmten Tag und einer Uhrzeit ein.
     *
     * @return bool
     * @throws nook_Exception
     */
    public function eintragenKapazitaet(){

        if( (empty($this->_programmId)) or (empty($this->_datum)) or (empty($this->_kapazitaet)) or (empty($this->_zeit)) )
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->_eintragenKapazitaet();

        return true;
    }

    /**
     * Trägt die Kapazität für ein Programm
     * zu einem bestimmten Tag und einer Uhrzeit ein.
     *
     */
    private function _eintragenKapazitaet(){

        $dateTime = nook_ToolDatum::erstelleDateTime($this->_datum, $this->_zeit);

        $cols = array(
            'programmdetails_id' => $this->_programmId,
            'datum' => $dateTime,
            'kapazitaet' => $this->_kapazitaet
        );

        $this->_tabelleProgrammdetailsKapazitaet->insert($cols);

        return;
    }

    /**
     * Trägt die Standard Kapazität
     * eines Programmes ein
     *
     * @return bool
     * @throws nook_Exception
     */
    public function writeKapazitaet(){

        if( ($this->_programmId == null) or ($this->_kapazitaet == null) )
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->_writeKapazitaet();

        return;
    }

    /**
     * Trägt die Standard Kapazität
     * eines Programmes in 'tbl_programmdetails'
     * ein.
     *
     */
    private function _writeKapazitaet(){

        $cols = array(
            'kapazitaet' => $this->_kapazitaet
        );

        $where = "id = ".$this->_programmId;

        $this->_tabelleProgrammdetails->update($cols, $where);

        return;
    }

    public function getStandardKapazitaet(){

        if(empty($this->_programmId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $cols = array(
            'kapazitaet'
        );

        $where = "id = ".$this->_programmId;

        $select = $this->_tabelleProgrammdetails->select();
        $select
            ->from($this->_tabelleProgrammdetails, $cols)
            ->where($where);

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();

        if(count($rows) <> $this->_condition_moegliche_anzahl_datensaetze)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        if(empty($rows[0]['kapazitaet']))
            $programmStandardKapazitaet['kapazitaet'] = 100;
        else
            $programmStandardKapazitaet['kapazitaet'] = $rows[0]['kapazitaet'];

        return $programmStandardKapazitaet;
    }

    /**
     * Übergabe der zu löschenden Kapazitäten
     *
     * @param $__stringZuLoeschendeKapazitaeten
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function setLoeschendeKapazitaeten($__stringZuLoeschendeKapazitaeten){
        $this->_zuLoeschendeKapazitaetenErmitteln($__stringZuLoeschendeKapazitaeten);

        return $this;
    }

    /**
     * String der Kapazitäten
     * wird in ein Array umgeformt.
     * Kontrolle auf Int.
     *
     * @param $__stringZuLoeschendeKapazitaeten
     * @throws nook_Exception
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    private function _zuLoeschendeKapazitaetenErmitteln($__stringZuLoeschendeKapazitaeten){
        $zuLoeschendeKapazitaeten = trim($__stringZuLoeschendeKapazitaeten);
        $zuLoeschendeKapazitaeten = explode(',',$zuLoeschendeKapazitaeten);

        for($i=0; $i < count($zuLoeschendeKapazitaeten); $i++){
            if(empty($zuLoeschendeKapazitaeten[$i]))
                continue;

            $zuLoeschendeKapazitaeten[$i] = (int) $zuLoeschendeKapazitaeten[$i];
            if($zuLoeschendeKapazitaeten[$i] == 0)
                throw new nook_Exception($this->_error_kein_int);

            $this->_kapazitaetenId[] = $zuLoeschendeKapazitaeten[$i];
        }

        return $this;
    }

    /**
     * Löscht die kapazität
     *
     * @return Admin_Model_DatensatzStandardKapazitaet
     */
    public function kapazitaetLoeschen(){

        for($i=0; $i < count($this->_kapazitaetenId); $i++){
            $delete = "id = ".$this->_kapazitaetenId[$i];
            $this->_tabelleProgrammdetailsKapazitaet->delete($delete);
        }

        return $this;
    }


}
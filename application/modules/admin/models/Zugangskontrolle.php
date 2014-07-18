<?php
/**
 * Darstellung der Zugriffskontrolle Bereich -> Controller -> Action
 *
 * Handelt den Zugriff auf die Action. Ermöglicht individuelle
 * Vergabe der Zugriffsrechte.
 *
 * @author Stephan.Krauss
 * @date 08.03.13
 * @file Zugangskontrolle.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_Zugangskontrolle{

    // Tabelle / Views
    private $_tabelleZugangskontrolle = null;
    private $_tabelleSystemRole = null;
    private $_tabelleRolleAction = null;

    // Error
    private $_error_keine_daten_vorhanden = 1330;
    private $_error_kein_int_wert = 1331;
    private $_error_daten_unvollstaendig = 1332;

    // Konditionen
    private $_condition_limit_darstellung_datensaetze = 20;

    // Flags

    protected $_data = array();
    protected $_start = 0; // Startpunkt Ermittlung Datensätze
    protected $_gesamtAnzahlDatensaetze = null; // Anzahl der vorhandenen Datensätze
    protected $_sucheBereich = null;
    protected $_sucheController = null;
    protected $_idAction = null; // ID 'tbl_zugangskontrolle'
    protected $_zugriffsrechteAction = array();

    public function __construct(){
        /** @var _tabelleZugangskontrolle Application_Model_DbTable_zugangskontrolle */
        $this->_tabelleZugangskontrolle = new Application_Model_DbTable_zugangskontrolle();
        /** @var _tabelleSystemRole Application_Model_DbTable_systemRole */
        $this->_tabelleSystemRole = new Application_Model_DbTable_systemRole();
        /** @var _tabelleRolleAction Application_Model_DbTable_rolle_action */
        $this->_tabelleRolleAction = new Application_Model_DbTable_rolleAction();
    }

    /**
     * @return array
     * @throws nook_Exception
     */
    public function getDatenTabelleActions(){

        if(empty($this->_data))
            throw new nook_Exception($this->_error_keine_daten_vorhanden);

        return $this->_data;
    }

    /**
     * @param $__start
     * @return Admin_Model_Zugangskontrolle
     * @throws nook_Exception
     */
    public function setStartDatensaetze($__start){

        $start = (int) $__start;
        $this->_start = $start;

        return $this;
    }

    /**
     * @param $__bereich
     * @return Admin_Model_Zugangskontrolle
     */
    public function setSucheBereich($__bereich){

        if(!empty($__bereich))
            $this->_sucheBereich = $__bereich;

        return $this;
    }

    /**
     * @param $__controller
     * @return Admin_Model_Zugangskontrolle
     */
    public function setSucheController($__controller){

        if(!empty($__controller))
            $this->_sucheController = $__controller;

        return $this;
    }

    /**
     * Ermitteln Datensätze für Tabelle.
     *
     * Ermitteln Datensätze zur Darstellung
     * in der Tabelle. Berücksichtigung des Limit;
     *
     * @return Admin_Model_Zugangskontrolle
     * @throws nook_Exception
     */
    public function ermittelnDatensaetze(){

        $select = $this->_tabelleZugangskontrolle->select();
        $select
            ->limit($this->_condition_limit_darstellung_datensaetze, $this->_start);

        $select = $this->_suchparameter($select);

        $rows = $this->_tabelleZugangskontrolle->fetchAll($select)->toArray();
        if(count($rows) > 0)
            $this->_data = $rows;

        return $this;
    }

    /**
     * Anzahl Datensätze
     *
     * Ermitteln Gesamtanzahl
     * aller Datensätze der Tabelle.
     *
     * @return Admin_Model_Zugangskontrolle
     */
    public function ermittelnAnzahlDatensaetze(){

        $select = $this->_tabelleZugangskontrolle->select();

        $select = $this->_suchparameter($select);

        $select->from($this->_tabelleZugangskontrolle, "count(id) as anzahl");

        $anzahl = $this->_tabelleZugangskontrolle->fetchRow($select)->toArray();
        $this->_gesamtAnzahlDatensaetze = $anzahl['anzahl'];

        return $this;
    }

    /**
     * Grenzt Suche mit Suchparameter ein.
     *
     * @param Zend_Db_Table_Select $__select
     * @return Zend_Db_Table_Select
     */
    private function _suchparameter(Zend_Db_Table_Select $__select){

        if(!empty($this->_sucheBereich))
            $__select->where("module like '%".$this->_sucheBereich."%'");

        if(!empty($this->_sucheController))
            $__select->where("controller like '%".$this->_sucheController."%'");

        return $__select;
    }

    /**
     * @return int
     */
    public function getAnzahlDatensaetze(){

        return $this->_gesamtAnzahlDatensaetze;
    }

    /**
     * ermittelt die vorhandenen Rollen des System
     * und übergibt ein Javascript Array 2 - dimensional.
     *
     * @return string
     */
    public function ermittelnRollen(){
        $javascriptRollen = "var memoryCheckboxenRollen = new Array();";

        $select = $this->_tabelleSystemRole->select();
        $select->order("id ASC");

        $rollen = $this->_tabelleSystemRole->fetchAll($select)->toArray();

        for($i=0; $i < count($rollen); $i++){
            $javascriptRollen .= "memoryCheckboxenRollen[".$i."] = new Array(".$rollen[$i]['id'].", '".$rollen[$i]['role_name']."');";
        }

        return $javascriptRollen;
    }

    /**
     * @param $__idAction
     * @return Admin_Model_Zugangskontrolle
     * @throws nook_Exception
     */
    public function setIdAction($__idAction){
        $idAction = (int) $__idAction;

        if(empty($idAction))
            throw new nook_Exception($this->_error_kein_int_wert);

        $this->_idAction = $idAction;

        return $this;
    }

    /**
     * Ermittelt die Zugriffsrechte an einer Action.
     *
     * Übernimmt aus Brückentabelle 'tbl_rolle_action'
     * die Zugriffsrechte an der Action eines Controllers.
     *
     * @return Admin_Model_Zugangskontrolle
     * @throws nook_Exception
     */
    public function ermittelnZugriffsrechteEinerAction(){

        if(empty($this->_idAction))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $where = "zugangskontrolle_id = ".$this->_idAction;

        $select = $this->_tabelleRolleAction->select();
        $select->where($where);

        $rows = $this->_tabelleRolleAction->fetchAll($select)->toArray();
        if(count($rows) > 0){

            foreach($rows as $key => $value){
                $rolleId = $value['rolle_id'];
                $checkBox = "checkbox".$rolleId;

                $this->_zugriffsrechteAction[$checkBox] = true;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getZugriffsrechteEinerAction(){

        return $this->_zugriffsrechteAction;
    }

    /**
     * @param array $__params
     * @return array|bool
     * @throws nook_Exception
     */
    public function mapCheckboxen(array $__params){

        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        $idAction = (int) $__params['idAction'];
        if(empty($idAction))
            throw new nook_Exception($this->_error_kein_int_wert);

        $this->_idAction = $__params['idAction'];
        unset($__params['idAction']);

        if(count($__params) == 0)
            return false;

        return $__params;
    }

    /**
     * Zugriffsrechte einer Action eintragen
     *
     * Trägt die Zugriffsrechte einer
     * Action eines Controllers ein.
     *
     * @param $__params
     * @return Admin_Model_Zugangskontrolle
     * @throws nook_Exception
     */
    public function eintragenZugriffsrechteAction($__params){

        if(empty($this->_idAction))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        // löschen Zugangskontrolle
        $where = "zugangskontrolle_id = ".$this->_idAction;
        $this->_tabelleRolleAction->delete($where);

        foreach ($__params as $key => $value) {

            $insert = array(
                'rolle_id' => substr($key, 8),
                'zugangskontrolle_id' => $this->_idAction
            );

            $this->_tabelleRolleAction->insert($insert);
        }

        return $this;
    }

    /**
     * Eitragen aller Action der Controller
     *
     * Trägt alle vorhandenen Action der Controller
     * in 'tbl_zugangskontrolle' ein. Achtung !
     * Prozess nimmt längere Zeit in Anspruch
     */
    public function eintragenAllerAction(){
        $toolZugangskontrolle = new nook_ToolZugangskontrolle();
        $toolZugangskontrolle
            ->buildModulesArray()
            ->buildControllerArrays()
            ->buildActionArrays()
            ->datensatzZugangskontrolle();

    }

} // end class
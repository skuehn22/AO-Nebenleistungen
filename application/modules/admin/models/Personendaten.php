<?php
class Admin_Model_Personendaten extends nook_ToolModel{

    // Konditionen
    private $_condition_bereich_hotel = 6;
    private $_condition_bereich_programme = 1;

    private $_condition_anbieter = 5;
    private $_condition_konzernadministrator = 6;
    private $_condition_redakteur_extern = 7;
    private $_condition_redakteur_intern = 8;
    private $_condition_buchhaltung = 9;
    private $_condition_administrator = 10;

    // Tabellen / Views
    private $_tabelleAdressen = null;

    // Fehler
    private $_error_keine_ganzzahl = 1110;
    private $_error_keine_datensaetze_vorhanden = 1011;
    private $_error_daten_unvollstaendig = 1012;

    protected  $_start = 0;
    protected  $_limit = 10;
    protected  $_suchparameter = array();
    protected $_idKunde = null;
    protected $_rolleKunde = null;

    /**
     *
     */
    public function __construct(){
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
		
		return;
	}

    /**
     * @param $__idKunde
     * @return Admin_Model_Personendaten
     * @throws nook_Exception
     */
    public function setIdKunde($__idKunde)
    {
       $__idKunde = (int) $__idKunde;

       if(empty($__idKunde))
           throw new nook_Exception($this->_error_keine_ganzzahl);

       $this->_idKunde = $__idKunde;

        return $this;
    }

    /**
     * @param $__rolleKunde
     * @return Admin_Model_Personendaten
     * @throws nook_Exception
     */
    public function setRolleKunde($__rolleKunde){
        $__rolleKunde = (int) $__rolleKunde;

        if( empty($__rolleKunde) )
            throw new nook_Exception($this->_error_keine_ganzzahl);

        $this->_rolleKunde = $__rolleKunde;

        return $this;
    }

    /**
     * Ändert die Rolle eines Benutzers
     *
     * @return int
     * @throws nook_Exception
     */
    public function aendernBenutzerRolle(){
        if( empty($this->_idKunde) or empty($this->_rolleKunde) )
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $anzahlGeaenderteDatensaetze = $this->_updateRolleEinesBenutzer($this->_idKunde,$this->_rolleKunde);

        return $anzahlGeaenderteDatensaetze;
    }


    /**
     * Ändert die Rolle eines Benutzers in der Tabelle 'tbl_adressen'
     *
     * @param $__kundeId
     * @param $__neueRolleId
     * @return int
     */
    private function _updateRolleEinesBenutzer($__kundeId, $__neueRolleId){

        $cols = array(
            'status' => $__neueRolleId
        );

        $where = "id = ".$__kundeId;

        $anzahlGeaenderteDatensaetze = $this->_tabelleAdressen->update($cols, $where);

        return $anzahlGeaenderteDatensaetze;
    }

    /**
     * Setzen von Start und Limit
     *
     * @param $__start
     * @param $__limit
     * @return Admin_Model_Personendaten
     */
    public function setStartwerte($__start, $__limit){
        $this->_start = $__start;
        $this->_limit = $__limit;

        return $this;
    }

    /**
     * @param array $__suchwerte
     * @return Admin_Model_Personendaten
     */
    public function setSuchwerte(array $__suchwerte){



        return $this;
    }



    /**
     * Setzt die Suchparameter
     *
     * @param array $__params
     * @return Admin_Model_Personendaten
     */
    public function setSuchparameter(array $__params){

        if(array_key_exists('lastname', $__params)){
            if(!empty($__params['lastname']))
                $this->_suchparameter['lastname'] = $__params['lastname'];
        }

        if(array_key_exists('email', $__params)){
            if(!empty($__params['email']))
                $this->_suchparameter['email'] = $__params['email'];
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPersonendaten(){

        $personendaten = $this->_getPersonendaten();

        return $personendaten;
    }

    /**
     * Ermittelt die Personendaten
     * in Abhängigkeit der Suchparameter
     * und der Startwerte
     *
     * @return array $personendaten
     */
    private function _getPersonendaten(){

        $select = $this->_tabelleAdressen->select();
        $select->limit($this->_limit, $this->_start);

        // Suchparameter
        $select = $this->_whereSuchparameter($select);

        $personenDatensaetze = $this->_tabelleAdressen->fetchAll($select)->toArray();

        return $personenDatensaetze;
    }

    /**
     * Ermittelt die Anzahl aller
     * Datensaetze der Tabelle 'tbl_adressen'
     *
     * @return array
     * @throws nook_Exception
     */
    public function getAnzahlDatensaetze(){

        $anzahlDatensaetze = $this->_getAnzahlDatensaetze();

        return $anzahlDatensaetze;

    }

    private function _getAnzahlDatensaetze(){
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $select = $this->_tabelleAdressen->select();
        $select->from($this->_tabelleAdressen, $cols);

        // Suchparameter
        $select = $this->_whereSuchparameter($select);

        $ergebnis = $this->_tabelleAdressen->fetchRow($select);

        if(empty($ergebnis))
            throw new nook_Exception($this->_error_keine_datensaetze_vorhanden);

        $anzahl = $ergebnis->toArray();

        return $anzahl['anzahl'];
    }

    /**
     * Ergänzt das 'select - Object' um die Suchparameter
     *
     * @param Zend_Db_Table_Select $__select
     * @return Zend_Db_Table_Select
     */
    private function _whereSuchparameter(Zend_Db_Table_Select $__select){

        foreach($this->_suchparameter as $key => $value){
            $__select->where($key." like '".$value."%'");
        }

        return $__select;
    }

}
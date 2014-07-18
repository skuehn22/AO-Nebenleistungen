<?php

class nook_city{

    protected $_datenbank;
    protected $_cityName;
    protected $_countCity;

    protected $_error_neue_stadt_konnte_nicht_eingetragen_werden = 440;
    protected $_error_stadt_ist_mehrfach_vorhanden = 441;

    private static $_instance;

    protected function __construct(){

    }

    static function getInstance(Zend_Db_Adapter_Pdo_Mysql $__datenbank){
        if( self::$_instance == null ){
            self::$_instance = new nook_city();
            self::$_instance->_datenbank = $__datenbank;
        }

        return self::$_instance;
    }

    public function setCityName($__cityName){
        $this->_cityName = $__cityName;
        $this->_countCities();
        if($this->_countCity == 0)
            $this->_insertNeueStadt();
        elseif($this->_countCity > 1)
            throw new nook_Exception();

        return $this;
    }

    protected function _insertNeueStadt(){
        $input = array();
        $input['AO_City'] = $this->_cityName;

        $kontrolle = $this->_datenbank->insert('tbl_ao_city', $input);
        if($kontrolle != 1){
            throw new nook_Exception($this->_error_neue_stadt_konnte_nicht_eingetragen_werden);
        }

        return $this;
    }

    protected function _countCities(){
        $sql = "select count(AO_City_ID) as anzahl from tbl_ao_city where AO_City = '". $this->_cityName ."'";
        $this->_countCity = $this->_datenbank->fetchOne($sql);

        return;
    }

    public function getLastInsertId(){
        return $this->_datenbank->lastInsertId();
    }
}

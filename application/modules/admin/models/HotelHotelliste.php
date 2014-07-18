<?php
class Admin_Model_HotelHotelliste extends nook_Model_model{

    private $_db_hotels;
    private $_auth;
    private $_suchParameter;
	
	public function __construct(){
		$this->_db_hotels = Zend_Registry::get('hotels');
        $this->_auth = new Zend_Session_Namespace('Auth');
		
		return;
	}

    public function setSearchparam($__suchParameter){
        $this->_suchParameter = $__suchParameter;

        return $this;
    }

    public function getHotelliste(){
        $sql = "select * from tbl_properties where property_name like '%".$this->_suchParameter."%'";

        $hotelliste = $this->_db_hotels->fetchAll($sql);
    }
}
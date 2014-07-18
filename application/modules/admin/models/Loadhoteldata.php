<?php
class Admin_Model_Loadhoteldata extends nook_Model_model{
	private $_db;
	private $_hotelData;
	
	public $error_hotel_not_exists = 250;
	
	// public $error_no_valid_zusatzartikel_items = 250;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
		
		return;
	}
	
	public function setHotelData($__xml){
		$this->_hotelData =  new SimpleXMLElement($__xml);
		
		$this->_checkHotelCode();
		$this->_availStatusMessage();
		
		return;
	}
	
	private function _checkHotelCode(){
		
		$hotelCode = $this->_hotelData->AvailStatusMessages['HotelCode'];
		$sql = "select count(property_code) from tbl_properties where property_code = '".$hotelCode[0]."'";
		$anzahl = $this->_db->fetchOne($sql);
		if($anzahl != 1)
			throw new nook_Exception($this->error_hotel_not_exists);

		return;
	}
	
	private function _availStatusMessage(){
		foreach($this->_hotelData->AvailStatusMessages->AvailStatusMessage as $rooms){
			$test = $rooms;
		}
		
		return;
	}
	
	
	
}
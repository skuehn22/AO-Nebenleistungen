<?php
class Admin_Model_Hauptschalter extends nook_Model_model{
	private $_db;

    private $_start = null;
    private $_limit = 20; // Zeilen pro Tabellenseite
	
	// public $error_no_hotel_insert = 290;
	
	public function __construct(){

        /** @var _db Zend_Db_Adapter_Mysqli */
		$this->_db = Zend_Registry::get('hotels');
		
		return;
	}

    /**
     * Ermittelt die Anzahl der vorhandenen Hotels
     *
     * @return mixed
     */
    public function getAnzahlHotels(){
        $sql = "select count(id) as anzahl from tbl_properties";
        $anzahl = $this->_db->fetchOne($sql);

        return $anzahl;
    }

    public function setStartpunkt($__params){

        if(array_key_exists('start', $__params))
            $this->_start = $__params['start'];
        else
            $this->_start = 0;

    }

    /**
     * Ermittelt die Hotels
     * und gibt diese Seitenweise zurück
     *
     * @return mixed
     */
    public function getHotels(){
		$sql = "select * from tbl_properties order by aktiv, property_name limit ".$this->_start.", ".$this->_limit;

		$hotels = $this->_db->fetchAll($sql);
		
		return $hotels;
	}
	
	public function setStatusHotel($__params){
		$update = array();
		$update['aktiv'] = $__params['status'];
		$control = $this->_db->update('tbl_properties', $update, "id = '".$__params['hotelId']."'");
		
		return $control;
	}
	
}
<?php
class Admin_Model_Availablerates extends nook_Model_model{
	private $_db;
	
	// public $error_no_hotel_insert = 270;

    public $hotelId = false;
    private $_searchHotelCode = false;
    private $_startDatum = false;
    private $_endDatum = false;
    private $_pageRows = 20; // Anzahl der Einträge
    private $_auth;
    private $_zugriffsKontrolle = array();

    private $_condition_role_provider = 5;

	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
        $this->_auth = new Zend_Session_Namespace('Auth');
		
		return;
	}

    /**
     * setzt den Hotel Code
     *
     * @param $__hotelCode
     * @return Admin_Model_Availablerates
     */
    public function setHotelCode($__hotelCode){
       $this->_searchHotelCode = $__hotelCode;

       return $this;
   }

    /**
     * setzt die Parameter der Zugriffskontrolle
     *
     * @param array $__zugriffsKontrolle
     * @return Admin_Model_Availablerates
     */
    public function setZugriffsKontrolle(array $__zugriffsKontrolle){
       $this->_zugriffsKontrolle = $__zugriffsKontrolle;

       return $this;
   }

    /**
     * Ermittelt die Anzahl der Raten eines Hotels
     *
     * @return mixed
     */
    public function getCountRatesAndCategories(){
		$sql = "select count(datum) from tbl_ota_rates_availability where `hotel_code` = '". $this->_searchHotelCode."'";

        if(!empty($this->_startDatum) and !empty($this->_endDatum)){
            $sql .= " and datum >= '".$this->_startDatum."'";
            $sql .= " and datum <= '".$this->_endDatum."'";
        }

		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}

    /**
     * Findet die Id des Hotels entspreachen des Hotel - Code
     *
     * @return Admin_Model_Availablerates
     */
    public function findHotelId(){
        $sql = "select id from tbl_properties where property_code = '".$this->_searchHotelCode."'";
        $this->hotelId = $this->_db->fetchOne($sql);

        return $this;
    }
	
	public function getRatesAndCategories($__start, $__limit){
		$start = 0;
		$limit = $this->_pageRows;
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

        $sql = "
            SELECT
                `tbl_ota_rates_availability`.`datum`
                , `tbl_ota_rates_availability`.`roomlimit`
                , `tbl_ota_rates_availability`.`min_stay`
                , `tbl_ota_rates_availability`.`arrival`
                , `tbl_ota_rates_availability`.`departure`
                , `tbl_ota_rates_availability`.`release_from`
                , `tbl_ota_rates_availability`.`release_to`
                , `tbl_ota_prices`.`amount`
                , `tbl_ota_prices`.`pricePerPerson`
                , `tbl_ota_rates_config`.`name`
                , `tbl_ota_rates_config`.`aktiv`
            FROM
                `tbl_ota_rates_availability`
                INNER JOIN `tbl_ota_prices`
                    ON (`tbl_ota_rates_availability`.`rate_code` = `tbl_ota_prices`.`rate_code`) AND (`tbl_ota_rates_availability`.`datum` = `tbl_ota_prices`.`datum`) AND (`tbl_ota_rates_availability`.`hotel_code` = `tbl_ota_prices`.`hotel_code`)
                INNER JOIN `tbl_ota_rates_config`
                    ON (`tbl_ota_rates_availability`.`hotel_code` = `tbl_ota_rates_config`.`hotel_code`) AND (`tbl_ota_rates_availability`.`rate_code` = `tbl_ota_rates_config`.`rate_code`)
            WHERE `tbl_ota_rates_availability`.`property_id` = ".$this->hotelId;



        if(!empty($this->_startDatum) and !empty($this->_endDatum)){
            $sql .= " and `tbl_ota_rates_availability`.`datum` >= '".$this->_startDatum."'";
            $sql .= " and `tbl_ota_rates_availability`.`datum` <= '".$this->_endDatum."'";
        }

        $sql .= " order by `tbl_ota_rates_availability`.`datum` asc";

		$sql .= " limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);

		return $result;		
	}
	
	public function getHotels($__stringDerHotels, $__alleHotels = false){


		$sql = "
			SELECT
			    `property_code`
			    , `property_name`
			FROM
			    `tbl_properties`";

        if(empty($__alleHotels))
            $sql .= " where id in (".$__stringDerHotels.")";

		$sql .= " ORDER BY `property_name` ASC";

        // Informationen
//        if(Zend_Registry::get('static')->firebug->firebug == 2){
//            $log = Zend_Registry::get('log');
//            $log->log('SQL: '.$sql, 5);
//        }

		$hotels = $this->_db->fetchAll($sql);
		
		return $hotels;
	}

    /**
     * Setzen des Start - und Enddatums der Suche
     *
     * @param $__startDatum
     * @param $__endDatum
     * @return Admin_Model_Availablerates
     */
    public function setSearchDate($__startDatum, $__endDatum){
        $dateItems = explode("T",$__startDatum);
        $this->_startDatum = $dateItems[0];

        $dateItems = explode("T",$__endDatum);
        $this->_endDatum = $dateItems[0];

        return $this;
    }
}
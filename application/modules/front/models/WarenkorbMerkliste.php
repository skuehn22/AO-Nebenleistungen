<?php
class Front_Model_WarenkorbMerkliste extends nook_Model_model
{
    private $_error_step_ist_nicht_integer = 420;

    private $_counterForHotelroomsToOrder = 0;

    private $_db_hotels;
    private $_db_front;

    public function __construct(){
        $this->_db_front = Zend_Registry::get('front');
        $this->_db_hotels = Zend_Registry::get('hotels');
    }

    public function getShoppingCart(){
        $warenkorb = new Zend_Session_Namespace('warenkorb');
		if(empty($warenkorb->kundenId))
			$shoppingCartHotelRooms = $this->_findOrdersBySessionId();
		else
			$shoppingCartHotelRooms = $this->_findOrdersByKundenId($warenkorb->kundenId);

        $this->_findCountHotelRooms($shoppingCartHotelRooms);
        $shoppingCartHotelRooms = $this->_findZusatzinformationenHotelrooms($shoppingCartHotelRooms);
        $shoppingCartHotelRooms = $this->_angabenZurStadt($shoppingCartHotelRooms);


        return $shoppingCartHotelRooms;
    }

    protected function _findZusatzinformationenHotelrooms($__shoppingCartHotelRooms){
        $translate = new Zend_Session_Namespace('translate');
        $language = $translate->language;

        for($i=0; $i<count($__shoppingCartHotelRooms); $i++){
            $sql = "
                SELECT
                    `tbl_ota_rates_config`.`name` as ratenName
                    , `tbl_properties`.`property_name` as hotelName
                    , `tbl_ota_rates_description`.`description_short` as beschreibungRate
                    , `tbl_ota_rates_description`.`image`
                FROM
                    `tbl_ota_rates_config`
                    INNER JOIN `tbl_properties`
                        ON (`tbl_ota_rates_config`.`properties_id` = `tbl_properties`.`id`)
                    LEFT JOIN `tbl_ota_rates_description`
                        ON (`tbl_ota_rates_config`.`id` = `tbl_ota_rates_description`.`rates_id`)
                WHERE (`tbl_ota_rates_config`.`id` = ".$__shoppingCartHotelRooms[$i]['otaRatesConfigId'].")";

            $zusatzinformationenHotel = $this->_db_hotels->fetchRow($sql);

            $__shoppingCartHotelRooms[$i] = array_merge($__shoppingCartHotelRooms[$i], $zusatzinformationenHotel);
        }

        return $__shoppingCartHotelRooms;
    }

    protected function _angabenZurStadt($__shoppingCartHotelRooms){

        for($i=0; $i<count($__shoppingCartHotelRooms); $i++){
            $sql = "select AO_City as cityName from tbl_ao_city where AO_City_ID = ". $__shoppingCartHotelRooms[$i]['cityId'];
            $cityName = $this->_db_front->fetchOne($sql);
            $__shoppingCartHotelRooms[$i]['cityName'] = $cityName;
        }

        return $__shoppingCartHotelRooms;
    }

    private function _findCountHotelRooms(array $__shoppingCartHotelRooms){
        $this->_counterForHotelroomsToOrder = count($__shoppingCartHotelRooms);

        return;
    }

    public function getCountHotelrooms(){
        return $this->_counterForHotelroomsToOrder;
    }

    private function _findOrdersByKundenId($__kundenId){
        $sql = "select id from tbl_buchungsnummer where kunden_id = '".$__kundenId."' order by id desc";
        $buchungsnummern = $this->_db_front->fetchAll($sql);

        $shoppingCartHotelRooms = $this->_findeZimmerBuchungen($buchungsnummern);
        return $shoppingCartHotelRooms;
    }

    protected function _findeZimmerBuchungen($buchungsnummern)
    {
        $shoppingCartHotelRooms = array();

        for ($i = 0; $i < count($buchungsnummern); $i++) {
            $sql = "
            SELECT
                *
            FROM
                `tbl_hotelbuchung`
            WHERE (`buchungsnummer_id` = '" . $buchungsnummern[$i]['id'] . "')";

            $shoppingCartHotelRoomsSingleBuchung = $this->_db_front->fetchAll($sql);

            if(is_array($shoppingCartHotelRoomsSingleBuchung))
                $shoppingCartHotelRooms = array_merge($shoppingCartHotelRooms, $shoppingCartHotelRoomsSingleBuchung);
        }

        return $shoppingCartHotelRooms;
    }

    private function _findOrdersBySessionId(){
        $sessionId = Zend_Session::getId();
        $sql = "select id from tbl_buchungsnummer where session_id = '".$sessionId."' order by id desc";
        $buchungsnummern = $this->_db_front->fetchAll($sql);

        $shoppingCartHotelRooms = $this->_findeZimmerBuchungen($buchungsnummern);

        return $shoppingCartHotelRooms;
    }
}

?>

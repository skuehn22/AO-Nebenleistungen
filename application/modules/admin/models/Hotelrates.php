<?php
class Admin_Model_Hotelrates extends nook_Model_model{
	private $_db;
    
    public $condition_hotel_new = 1;
    public $condition_rate_not_availability = 1;
    public $condition_rate_not_for_calculate_rooms = 1;
    public $condition_rate_for_calculate_rooms = 2;

    private $_condition_role_provider = 5;
	
	public $error_no_hotel_insert = 300;

	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
        
		return;
	}
    
    public function getHotelRates($__hotelId, $__start = false, $__limit = false){
    	$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

        $sql = "
            SELECT
                `tbl_rates`.`id`
                , `tbl_rates`.`aktiv`
                , `tbl_rates`.`name`
                , `tbl_rates`.`rate_code`
                , `tbl_categories`.`categorie_name`
            FROM
                `tbl_rates`
                INNER JOIN `tbl_categories`
                    ON (`tbl_rates`.`category_id` = `tbl_categories`.`id`)
            WHERE (`tbl_rates`.`properties_id` = '".$__hotelId."')";

		$rates = $this->_db->fetchAll($sql);
		
		return $rates;
    }
    
    public function getCountHotelRates($__hotelId){
        $anzahl = 0;

        $sql = "
            SELECT
                count(`id`) AS `anzahl`
            FROM
                `tbl_rates`
            WHERE (`properties_id` = '".$__hotelId."')";

    	$anzahl = $this->_db->fetchOne($sql);
    	
    	return $anzahl;
    }
    
    public function setHotelRateAktivPassiv($__params){
    	$errors = false;
    	$update = array();
    	$update['aktiv'] = $__params['check'];
    	
    	$control = $this->_db->update('tbl_rates', $update, "id = '".$__params['rateId']."'");
    	If(!$control)
    		$errors = true;
    	
    	return $errors;
    }
    
    public function getRateMasterData($__hotelId, $__rateId){
    	
    	$sql = "
    	SELECT
		    `tbl_rates`.`name`
		    , `tbl_rates`.`rate_code`
		    , `tbl_categories`.`categorie_name`
		    , if(`tbl_rates`.`calculatingCapacity` = 1, 'false', 'true') as calculatingCapacity
		FROM
		    `tbl_rates`
		    INNER JOIN `tbl_categories`
		        ON (`tbl_rates`.`category_id` = `tbl_categories`.`id`)
		WHERE (`tbl_rates`.`properties_id` = '".$__hotelId."'
		    AND `tbl_rates`.`id` = '".$__rateId."')";
			
    	
    	$formElemets = $this->_db->fetchRow($sql);
    	
    	return $formElemets;
    }
    
    public function getCategoriesFromHotel($__id){
    	$sql = "select id, categorie_name from tbl_categories where properties_id = '".$__id."'";
    	$categories = $this->_db->fetchAll($sql);
    	
    	return $categories;
    }
    
    public function getProductsFromHotel($__hotelId, $__rateId = false){
    	
    	$sql = "select id, product_name, property_id, price, vat from tbl_products where property_id = '".$__hotelId."'";
    	$hotelProducts = $this->_db->fetchAll($sql);
        for($i=0; $i < count($hotelProducts); $i++){
            $hotelProducts[$i]['checked'] = 'false';
        }
    	
    	if(!empty($__rateId)){
    		$sql = "select products_id from tbl_ota_rates_products where rates_id = '".$__rateId."'";
    		$hotelProductsChecked = $this->_db->fetchAll($sql);
            for($i=0; $i < count($hotelProducts); $i++){
                for($j=0; $j < count($hotelProductsChecked); $j++){
                    if($hotelProducts[$i]['id'] == $hotelProductsChecked[$j]['products_id'])
                        $hotelProducts[$i]['checked'] = 'true';
                }
            }
    	}
    	
    	return $hotelProducts;
    }
    
    public function saveNewRate($__params, $__products){
    	$errors = 0;

        $errors = $this->_checkDoubleRateCode($__params['hotelId'], $__params['rate_code']);
        if(!empty($errors))
            return $errors;

    	
    	$insert = array();
    	$insert['properties_id'] = $__params['hotelId'];
    	$insert['name'] = $__params['name'];
    	$insert['rate_code'] = $__params['rate_code'];
    	$insert['category_id'] = $__params['categorie_id'];
    	$insert['rate_avaibility'] = $this->condition_rate_not_availability;

        $insert['calculatingCapacity'] = $this->condition_rate_not_for_calculate_rooms;
        if(array_key_exists('calculatingCapacity',$__params))
            $insert['calculatingCapacity'] = $this->condition_rate_for_calculate_rooms;
    	
    	$this->_db->insert('tbl_rates', $insert);
    	$insertRatesId = $this->_db->lastInsertId();
    	if(!$insertRatesId)
    		$errors++;
    	
    	for($i=0; $i<count($__products); $i++){
    		$sql = "insert into tbl_rates_products set rates_id = '".$insertRatesId."', products_id = '".$__products[$i]."'";
    		$control = $this->_db->query($sql);
    		if(!$control)
    			$errors++;
    	}
    	
    	return $errors;
    	
    }

    private function _checkDoubleRateCode($__hotelId, $__rateCode){
        $errors = false;
        $sql = "select count(id) as anzahl from tbl_rates where rate_code = '".$__rateCode."' and properties_id = '".$__hotelId."'";
        $anzahl = $this->_db->fetchOne($sql);
        if($anzahl > 0){
            $errors[0]['id'] = "rate_code";
            $errors[0]['msg'] = "Rate Code bereits vergeben";

            return $errors;
        }

        return $errors;
    }

    /**
     * + löscht 'alte' Zuordnung der Produkte zu einer Rate
     * + speichert die 'neuen' Produkte einer Rate
     *
     * @param $__rateId
     * @param $produkte
     */
    public function setProdukteEinerRate($__rateId , $produkte){
        $sql = "delete from tbl_ota_rates_products where rates_id = '".$__rateId."'";
        $this->_db->query($sql);

        $insert = array();
        if(count($produkte) > 0){
            for($i=0; $i<count($produkte); $i++){
                $insert['rates_id'] = $__rateId;
                $insert['products_id'] = $produkte[$i];

                $this->_db->insert('tbl_ota_rates_products', $insert);
            }
        }

        return;
    }
		
}
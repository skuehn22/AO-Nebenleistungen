<?php
class Admin_Model_Availablecategories extends nook_Model_model{

    /**
    * @var Zend_Db_Adapter
    */
	public $_db;

	// public $error_no_hotel_insert = 270;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
		
		return;
	}
	
	public function getCountCategories(){
		$sql = "select count(datum) from tbl_categorie_availability";
		$anzahl = $this->_db->fetchOne($sql);

		return $anzahl;
	}
	
	public function getCategories($__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

		$sql = "
		SELECT
		    `tbl_properties`.`property_name`
		    , `tbl_properties`.`property_code`
		    , `tbl_categories`.`categorie_name`
		    , `tbl_categorie_availability`.`datum`
		    , `tbl_categorie_availability`.`roomlimit`
		    , `tbl_categorie_availability`.`availibility`
		    , `tbl_categorie_availability`.`min_stay`
		FROM
		    `tbl_properties`
		    INNER JOIN `tbl_categories`
		        ON (`tbl_properties`.`id` = `tbl_categories`.`properties_id`)
		    INNER JOIN `tbl_categorie_availability`
		        ON (`categories`.`id` = `tbl_categorie_availability`.`categories_id`)";

        // Rollen
        

		$sql .= "ORDER BY `properties`.`property_name` ASC, `categorie_availability`.`datum` ASC";
		$sql .= " limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);
		return $result;		
	}
}
<?php
class Admin_Model_Hotelkategories extends nook_Model_model{
	private $_db;
	
	public $error_no_hotel_insert = 270;
	public $error_no_hotel_kategorie_insert = 271;
    public $error_no_hotel_kategorie_description_insert = 272;
	
	public $condition_hotel_new = 1;
    public $condition_kategorie_angelegt = 1;
    public $condition_kategorie_erneuert = 2;

    private $_condition_role_provider = 5;
    private $_auth;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
		$this->_auth = new Zend_Session_Namespace('Auth');

		return;
	}
	
	public function getTableItemsHotels($__stringListeDerHotels, $__alleHotels = false, $__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}
		
		$sql = "select * from tbl_properties where aktiv > '".$this->condition_hotel_new."'";

        if(empty($__alleHotels))
            $sql .= " and id IN (". $__stringListeDerHotels .")";

        $sql .= "  order by property_name asc";
		$sql .= " limit ".$start.",".$limit;
		
		$hotels = $this->_db->fetchAll($sql);
		return $hotels;
	}
	
	public function getCountHotels($__stringListeDerHotels, $__alleHotels = false){
		$sql = "select count(id) from tbl_properties";

        if(empty($__alleHotels))
            $sql .= " where id IN (".$__stringListeDerHotels.")";

		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getCountHotelKategories($__hotelId){
		$sql = "select count(id) from tbl_categories where properties_id = '".$__hotelId."'";
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getTableItemsHotelKategories($__hotelId, $__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}
		
		$sql = "
		SELECT
		    `tbl_categories`.`id`
		    , `tbl_categories`.`categorie_code`
		    , `tbl_categories`.`categorie_name`
		    , `tbl_categories`.`aktiv`
		    , `tbl_properties`.`property_name`
		FROM
		    `tbl_properties`
		    INNER JOIN `tbl_categories`
		        ON (`tbl_properties`.`id` = `tbl_categories`.`properties_id`) where properties_id = '".$__hotelId."' order by categorie_name asc";
		
		$sql .= " limit ".$start.",".$limit;
		
		$kategories = $this->_db->fetchAll($sql);
		
		return $kategories;
	}
	
	public function buildHotelKategorie($__params){

        $__params['categorie_code'] = trim($__params['categorie_code']);
        $anzahl = $this->_checkDoubleHotelKategorie($__params['properties_id'], $__params['categorie_code']);

        if($anzahl == 0){
		    $control = $this->_db->insert('tbl_categories', $__params);
            return $this->condition_kategorie_angelegt;
        }
        elseif($anzahl == 1){

            $where = array();
            $where[0] = "properties_id = ". $__params['properties_id'];
            $where[1] = "categorie_code = '" .$__params['categorie_code']. "'";

            unset($__params['properties_id']);
            unset($__params['categorie_code']);

            $control = $this->_db->update('tbl_categories', $__params, $where);
            return $this->condition_kategorie_erneuert;
        }
        else
            throw new nook_Exception($this->error_no_hotel_kategorie_insert);
	}

	public function getHotelCategoryData($__hotelId, $__categoryId){
		$sql = "
			SELECT
			    `categorie_code`
			    , `categorie_name`
			    , `categorie_name_en`
			    , `standard_persons`
			    , `min_persons`
			    , `max_persons`
			    , `aktiv`
			    , `id`
			    , `properties_id`
			FROM
			    `tbl_categories`
			WHERE (`id` = '".$__categoryId."'
			    AND `properties_id` = '".$__hotelId."')";
		
		$categoryData = $this->_db->fetchRow($sql);
		$categoryData = $this->_removeNull($categoryData);
		
		return $categoryData;
	}
	
    private function _checkDoubleHotelKategorie($__hotelId, $__categoryCode){
        $sql = "select count(id) as anzahl from tbl_categories where properties_id = '".$__hotelId."' and categorie_code = '".$__categoryCode."'";
        $anzahl = $this->_db->fetchone($sql);

        return $anzahl;
    }

    public function setHotelCategoryData($__categoryId, $__sprache, $__params){
        unset($__params['controller']);
        unset($__params['action']);
        unset($__params['module']);
        unset($__params['hotelId']);

        unset($__params['kategorieId']);
        unset($__params['language']);


        $sql = "select count(id) as anzahl from tbl_categories_description where category_id = '".$__categoryId."' and speech = '".$__sprache."'";
        $anzahl = $this->_db->fetchOne($sql);

        if($anzahl == 0){
            $__params['category_id'] = $__categoryId;
            $__params['speech'] = $__sprache;
            $kontrolle = $this->_db->insert('tbl_categories_description', $__params);
        }
        elseif($anzahl == 1){
            $where = array();
            $where[] = "category_id = '".$__categoryId."'";
            $where[] = "speech = '".$__sprache."'";

            $kontrolle = $this->_db->update('tbl_categories_description', $__params, $where);
        }
        else
            throw new nook_Exception($this->error_no_hotel_kategorie_insert);

        return;
    }

    public function getBeschreibungEinerKategorie($__kategorieId, $__sprache){
        $sql = "select * from tbl_categories_description where  category_id = '".$__kategorieId."' and speech = '".$__sprache."'";
        $kategorieBeschreibung = $this->_db->fetchRow($sql);

        return $kategorieBeschreibung;
    }
	
}
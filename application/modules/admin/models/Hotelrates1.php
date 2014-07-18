<?php
class Admin_Model_Hotelrates1 extends nook_Model_model{

    protected  $_auth;

    /** @var $arrayhandling nook_arrayhandling */
    public $arrayhandling;

    // DB Adapter / Tabellen / Views
	private $_db;
    private $_tabelleProducts = null;
    private $_tabelleOtaRatesProducts = null;
    private $_tabelleCategories = null;

    // Fehler
	public $error_no_hotel_insert = 270;
	public $error_no_hotel_rate_insert = 271;
    public $error_no_categories_rates_insert = 272;
    public $error_no_hotel_rate_description_not_updatet = 273;
    public $error_keine_produkte_im_hotel_vorhanden = 274;
    private $_error_keine_kategorien_vorhanden = 275;

    // Konditionen
	public $condition_hotel_new = 1;
    public $condition_new_rate = 1;
    public $condition_update_rate = 2;
    private $_condition_min_anzahl_datensaetze = 1;

    private $_condition_role_provider = 5;

    private $_condition_rate_ist_neu = 1;
    private $_condition_rate_ist_passiv = 2;
    private $_condition_rate_ist_aktiv = 3;

	
	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
		$this->_auth = new Zend_Session_Namespace('Auth');

        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));
        /** @var _tabelleOtaRatesProducts Application_Model_DbTable_otaRatesProducts */
        $this->_tabelleOtaRatesProducts = new Application_Model_DbTable_otaRatesProducts(array('db' => 'hotels'));
        /** @var _tabelleCategories Application_Model_DbTable_categories */
        $this->_tabelleCategories = new Application_Model_DbTable_categories(array('db' => 'hotels'));

		return;
	}
	
	public function getTableItemsHotels($__stringListeDerHotels, $__alleHotels, $__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}
		
		$sql = "select * from tbl_properties where aktiv > '".$this->condition_hotel_new."'";

        if(empty($__alleHotels))
            $sql .= " and id IN (". $__stringListeDerHotels .")";

        $sql .= "  order by aktiv asc, property_name asc";
		$sql .= " limit ".$start.",".$limit;
		
		$hotels = $this->_db->fetchAll($sql);
		return $hotels;
	}
	
	public function getCountHotels($__stringListeDerHotels, $__alleHotels = false){
		$sql = "select count(id) from tbl_properties where";

        if(empty($__alleHotels))
            $sql .= " id IN (". $__stringListeDerHotels .") and";

        $sql .= " aktiv > '".$this->condition_hotel_new."'";

		$anzahl = $this->_db->fetchOne($sql);

		return $anzahl;
	}
	
	public function getAnzahlHotelKategorienUndRaten($__hotelId){
        $sql = "
            SELECT
                count(`id`) AS `anzahl`
            FROM
                `tbl_ota_rates_config`
            WHERE (`properties_id` = ".$__hotelId.");
        ";
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getHotelKategorienUndRaten($__hotelId, $__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

        $sql = "
            SELECT
                `tbl_ota_rates_config`.`name` AS `ratenName`
                , `tbl_ota_rates_config`.`id` AS `id`
                , `tbl_categories`.`id` AS `kategorieId`
                , `tbl_categories`.`categorie_name` AS `kategorieName`
                , `tbl_categories`.`categorie_code` AS `kategorieCode`
                , `tbl_ota_rates_config`.`rate_code` AS `ratenCode`
                , `tbl_ota_rates_config`.`aktiv` AS `aktiv`
            FROM
                `tbl_ota_rates_config`
                LEFT JOIN `tbl_categories`
                    ON (`tbl_ota_rates_config`.`category_id` = `tbl_categories`.`id`)
            WHERE (`tbl_ota_rates_config`.`properties_id` = ".$__hotelId.")";

        $sql .= " order by aktiv, categorie_name";
		$sql .= " limit ".$start.",".$limit;

		$kategorienUndRaten = $this->_db->fetchAll($sql);
		
		return $kategorienUndRaten;
	}
	
	public function buildRateEinesHotels($__params){

        $__params['rateCode'] = trim($__params['rateCode']);
        $anzahl = $this->_checkDoubleHotelRate($__params['properties_id'], $__params['rateCode']);

        if($anzahl == 0){
             $__params['category_id'] = $__params['categorie_id'];
            unset($__params['categorie_id']);
            $__params['rate_code'] = $__params['rateCode'];
            unset($__params['rateCode']);
            $__params['name'] = $__params['rateName'];
            unset($__params['rateName']);
            $__params['hotel_code'] = $__params['hotelCode'];
            unset($__params['hotelCode']);

            $control = $this->_db->insert('tbl_ota_rates_config', $__params);

            if($control != 1)
                throw new nook_Exception($this->error_no_hotel_rate_insert);

            $categories_rates = array();
            $categories_rates['rate_id'] = $this->_db->lastInsertId();
            $categories_rates['category_id'] = $__params['category_id'];
            $categories_rates['property_id'] = $__params['properties_id'];
            $control = $this->_db->insert('tbl_categories_rates', $categories_rates);

            return $this->condition_new_rate;
        }
        elseif($anzahl == 1){

            // ermittel HotelCode

            $where = array();
            $where[0] = "rate_code = '".$__params['rateCode']."'";
            $where[1] = "properties_id = ".$__params['properties_id'];

            $update['name'] = $__params['rateName'];
            $update['category_id'] = intval($__params['categoryId']);
            $update['aktiv'] = $__params['aktivschaltung'];
            $update['hotel_code'] = $__params['hotelCode'];

            $this->_db->update('tbl_ota_rates_config', $update, $where);

             if(array_key_exists('categoryId', $__params)){
                 $sql = "select id from tbl_ota_rates_config where ".$where[1]." and ".$where[0];
                 $rateId = $this->_db->fetchOne($sql);

                 // update Tabelle 'categories_rates'
                 $sql = "update tbl_categories_rates set category_id = ".$__params['categoryId']." where property_id = ".$__params['properties_id']." and rate_id = ".$rateId;
                 $kontrolle = $this->_db->query($sql);
             }

             // alle raten auf aktiv / passiv in 'ota_rates_availability'
             $where = array();
             $where[0] = "hotel_code = '".$__params['hotelCode']."'";
             $where[1] = "rate_code = '".$__params['rateCode']."'";

             $update = array();

            if($__params['aktivschaltung'] == $this->_condition_rate_ist_neu || $__params['aktivschaltung'] == $this->_condition_rate_ist_passiv){
                $update['aktiv'] = $this->_condition_rate_ist_passiv;
                $this->_db->update('tbl_ota_rates_availability',$update,$where);
            }

            if($__params['aktivschaltung'] == $this->_condition_rate_ist_aktiv){
                $update['aktiv'] = $this->_condition_rate_ist_aktiv;
                $this->_db->update('tbl_ota_rates_availability',$update,$where);
            }

            return $this->condition_update_rate;
        }
        else
            throw new nook_Exception($this->error_no_categories_rates_insert);
	}

	public function getStammdatenEinerRate($__hotelId, $__rateId){

        $sql = "
            SELECT
                `tbl_ota_rates_config`.`name` AS `rateName`
                , `tbl_ota_rates_config`.`id` AS `rateId`
                , `tbl_ota_rates_config`.`rate_code` AS `rateCode`
                , `tbl_ota_rates_config`.`aktiv` AS `aktiv`
                , `tbl_categories`.`id` as categorie_id
                , `tbl_categories`.`id` AS kategorieId
            FROM
                `tbl_ota_rates_config`
                INNER JOIN `tbl_categories_rates`
                    ON (`tbl_ota_rates_config`.`id` = `tbl_categories_rates`.`rate_id`)
                INNER JOIN `tbl_categories`
                    ON (`tbl_categories`.`id` = `tbl_categories_rates`.`category_id`) WHERE (`tbl_ota_rates_config`.`id` = ".$__rateId.")";

		$rateDaten = $this->_db->fetchRow($sql);

		return $rateDaten;
	}
	
    private function _checkDoubleHotelKategorie($__hotelId, $__categoryCode){
        $errors = false;
        $sql = "select count(id) as anzahl from tbl_categories where properties_id = '".$__hotelId."' and categorie_code = '".$__categoryCode."'";
        $anzahl = $this->_db->fetchone($sql);

        if($anzahl > 0){
            $errors[0]['id'] = "categorie_code";
            $errors[0]['msg'] = "Kategorie Code schon vergeben";
        }


        return $errors;
    }

    private function _checkDoubleHotelRate($__hotelId, $__ratenCode){
        $sql = "select count(id) as anzahl from tbl_ota_rates_config where properties_id = '".$__hotelId."' and rate_code = '".$__ratenCode."'";
        $anzahl = $this->_db->fetchone($sql);

        return $anzahl;
    }

    /**
     * Ermittelt die Kategorien eines Hotels
     * entsprechend der Hotel ID
     *
     * @param $__id
     * @return mixed
     */
    public function getKategorienEinesHotel($__id){
        $select = $this->_tabelleCategories->select();

        $cols = array(
            'id',
            'categorie_name'
        );

        $select
            ->from($this->_tabelleCategories, $cols)
            ->where("properties_id = ".$__id);

        $rows = $this->_tabelleCategories->fetchAll($select)->toArray();

        if(count($rows) < $this->_condition_min_anzahl_datensaetze)
            throw new nook_Exception($this->_error_keine_kategorien_vorhanden);

        return $rows;
    }

    public function getBeschreibungDerRate($__params){
        $sql = "select * from tbl_ota_rates_description where rates_id = '".$__params['rateId']."' and speech = '".$__params['language']."'";
        $ratenBeschreibung = $this->_db->fetchRow($sql);

        return $ratenBeschreibung;
    }

    public function setBeschreibungDerRate($__params){
       $sql = "select count(id) from tbl_ota_rates_description where rates_id = '".$__params['rateId']."' and speech = '".$__params['language']."'";
       $anzahl = $this->_db->fetchOne($sql);

       $beschreibungRate = array();
       $beschreibungRate['description_long'] = $__params['description_long'];
       $beschreibungRate['description_short'] = $__params['description_short'];
       $beschreibungRate['headline'] = $__params['headline'];

       if($anzahl == 0){
          $beschreibungRate['speech'] = $__params['language'];
          $beschreibungRate['rates_id'] = $__params['rateId'];
          $kontrolle = $this->_db->insert('tbl_ota_rates_description', $beschreibungRate);
       }
       elseif($anzahl == 1){
          $where = array();
          $where[] = "rates_Id = '".$__params['rateId']."'";
          $where[] = "speech = '".$__params['language']."'";
          $kontrolle = $this->_db->update('tbl_ota_rates_description', $beschreibungRate, $where);
       }
       else
           throw new nook_Exception($this->error_no_hotel_rate_description_not_updatet);

       return;
    }
    
     public function checkTypImage($__image){
        $check = false;

        if($__image['type'] == 'image/jpeg')
            $check = true;

        return $check;
    }

    /**
     * Ermittelt die
     * aktiven Raten eines Hotels
     *
     * @param $__hotelId
     * @param bool $__rateId
     * @return array
     */
    public function getProductsFromHotel($__hotelId, $__rateId = false){
        try{
            $select = $this->_tabelleProducts->select();
            $select
                ->where("property_id = ".$__hotelId)
                ->where("aktiv = ".$this->_condition_rate_ist_aktiv);

            $hotelProducts = $this->_tabelleProducts->fetchAll($select)->toArray();
            for($i=0; $i < count($hotelProducts); $i++){
                $hotelProducts[$i]['checked'] = false;
            }

            if(!empty($__rateId)){
                $cols = array(
                    'products_id'
                );

                $select = $this->_tabelleOtaRatesProducts->select();
                $select
                    ->from($this->_tabelleOtaRatesProducts, $cols)
                    ->where("rates_id = ".$__rateId);

                $hotelProductsChecked = $this->_tabelleOtaRatesProducts->fetchAll($select)->toArray();

                for($i=0; $i < count($hotelProducts); $i++){
                    for($j=0; $j < count($hotelProductsChecked); $j++){
                        if($hotelProducts[$i]['id'] == $hotelProductsChecked[$j]['products_id'])
                            $hotelProducts[$i]['checked'] = true;
                    }
                }
            }

            if(is_array($hotelProducts) and count($hotelProducts) > 0)
                $hotelProducts = $this->arrayhandling->sortHotelProductsByChecked($hotelProducts);
            else
                throw new nook_Exception($this->error_keine_produkte_im_hotel_vorhanden);

            return $hotelProducts;
        }
        catch(nook_Exception $e){
            switch($e->getMessage()){
                case '274':
                    return false;
                    break;
            }
        }

    }

}
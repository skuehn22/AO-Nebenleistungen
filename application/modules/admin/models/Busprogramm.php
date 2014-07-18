<?php
class Admin_Model_Busprogramm extends nook_Model_model{
	private $_db;
	
	// public $error_no_valid_zusatzartikel_items = 240;
	
	public $condition_is_min_border_bus = 3;
	public $condition_is_max_border_bus = 5;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('front');
		
		return;
	}
	
	public function getCountPrograms(){
		// $sql = "select count(Fa_Id) as anzahl from tbl_programmdetails where sachleistung >= ".$this->condition_is_min_border_bus." and sachleistung <= ".$this->condition_is_max_border_bus;
		$sql = "SELECT
		    count(`tbl_programmbeschreibung`.`Fa_Id`)
		    , `tbl_programmdetails`.`sachleistung`
		FROM
		    `tbl_programmbeschreibung`
		    INNER JOIN `tbl_programmdetails`
		        ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_programmdetails`.`Fa_ID`)
		WHERE (`tbl_programmdetails`.`sachleistung` >= ".$this->condition_is_min_border_bus."
		    AND `tbl_programmdetails`.`sachleistung` <= ".$this->condition_is_max_border_bus.")";
		
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getTableItems($__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

		$sql = "
			SELECT
			    `tbl_programmbeschreibung`.`progname`
			    , `tbl_programmdetails`.`prio_noko`
			    , `tbl_programmdetails`.`vk`
			    , `tbl_programmdetails`.`mwst_satz`
			    , `tbl_programmdetails`.`buchungsfrist`
			    , `tbl_programmdetails`.`sachleistung`
			    , `tbl_programmdetails`.`maxPersons`
			    , `tbl_programmdetails`.`permanent_zusatz`
			    , `tbl_programmbeschreibung`.`Fa_Id`
			    , `tbl_ao_city`.`AO_City`
			    , `tbl_ao_city`.`AO_City_ID`
			    , `tbl_prog_sprache`.`de` AS `sprache`
			    , `tbl_prog_sprache`.`id` AS `sprache_id`
			FROM
			    `tbl_programmbeschreibung`
			    INNER JOIN `tbl_programmdetails`
			        ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_programmdetails`.`Fa_ID`)
			    INNER JOIN `tbl_prog_sprache` 
			        ON (`tbl_programmbeschreibung`.`sprache` = `tbl_prog_sprache`.`id`)
			    INNER JOIN `tbl_ao_city` 
			        ON (`tbl_programmdetails`.`AO_City` = `tbl_ao_city`.`AO_City_ID`)
			WHERE `tbl_programmdetails`.`sachleistung` >= ".$this->condition_is_min_border_bus." and `tbl_programmdetails`.`sachleistung` <= ".$this->condition_is_max_border_bus."
			ORDER BY `tbl_programmdetails`.`sachleistung` ASC, `tbl_programmbeschreibung`.`Fa_Id` ASC";
		
		$sql .= " limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);
	
		return $result;		
	}
	
	public function getCities(){
		$sql = "
		SELECT
		    `AO_City_ID` AS `id`
		   ,`AO_City` AS `city`
		FROM
		    `tbl_ao_city`
		ORDER BY `city` ASC";
		
		$cities = $this->_db->fetchAll($sql);
		
		return $cities;
	}
	
	public function getSprache(){
		$sql = "
		SELECT
    		`id`,
    		`de` AS `sprache`
		FROM
    		`tbl_prog_sprache`
		ORDER BY `sprache` ASC";
		
		$languages = $this->_db->fetchAll($sql);
		return $languages;
	}
	
	public function updateBusProgramm($__data){
		$bjlWhereOptions = "Fa_Id = '".$__data['Fa_Id']."' and sprache = '".$__data['sprache']."'";
		$this->_updateBjl($__data['progname'], $bjlWhereOptions);
		
		$detailsWhereOptions = "Fa_ID = '".$__data['Fa_Id']."'";
		unset($__data['Fa_Id']);
		unset($__data['sprache']);
		unset($__data['progname']);
		$__data['permanent_zusatz'] = $__data['zusatzprogramm'];
		unset($__data['zusatzprogramm']);
		$__data['vk'] = str_replace(',','.',$__data['vk']);
		$__data['AO_City'] = $__data['city'];
		unset($__data['city']);
		$this->_updateBjlDetails($__data, $detailsWhereOptions);
		
		
		return;
	}
	
	private function _updateBjlDetails($__data, $__detailsWhereOptions){
		$this->_db->update('tbl_programmdetails', $__data, $__detailsWhereOptions);
		
		return;
	}
	
	private function _updateBjl($__data, $__bjlWhereOptions){
		$data = array();
		$data['progname'] = $__data;
		$this->_db->update('tbl_programmbeschreibung', $data, $__bjlWhereOptions);
		
		return;
	}
	
}
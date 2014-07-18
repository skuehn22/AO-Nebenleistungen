<?php
class Admin_Model_Zusatzartikel extends nook_Model_model{
	private $_db;
	
	public $error_no_valid_zusatzartikel_items = 210;
	
	public $condition_number_programms = 1;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('front');
		
		return;
	}
	
	public function getCountPrograms(){
		$sql = "select count(Fa_Id) as anzahl from tbl_programmbeschreibung where sprache = '1'";
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getTableItems($start, $limit, $progsearch){
		$start = 0;
		$limit = 20;	
		
		if(array_key_exists('limit', $_POST)){
			$start = $_POST['start'];
			$limit = $_POST['limit'];
		}
			
		$sql = "
		SELECT
		    `tbl_programmbeschreibung`.`progname`
		    , `tbl_programmbeschreibung`.`Fa_Id`
		    , `tbl_programmbeschreibung`.`wertigkeit`
		    , `tbl_programmdetails`.`minPersons`
		    , `tbl_programmdetails`.`maxPersons`
		    , `tbl_programmdetails`.`minDuration`
		    , `tbl_programmdetails`.`maxDuration`
		    , `tbl_programmdetails`.`permanent_zusatz`
		    , `tbl_programmdetails`.`sachleistung`
		    , `tbl_adressenfa`.`Ort` as ort
		FROM
		    `tbl_programmbeschreibung`
		    INNER JOIN `tbl_programmdetails`
		        ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_programmdetails`.`Fa_ID`)
		    INNER JOIN `tbl_adressenfa` 
		        ON (`tbl_programmdetails`.`Fa_ID` = `tbl_adressenfa`.`Fa_ID`)
		WHERE (`tbl_programmbeschreibung`.`sprache` = 1";
		
		if(!empty($progsearch))
			$sql .= " and (`tbl_programmbeschreibung`.`progname` like '%".$progsearch."%' OR `tbl_programmbeschreibung`.`Fa_Id` like '%".$progsearch."%')";
		
		$sql .= ") ORDER BY `tbl_adressenfa`.`Ort` ASC limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);
	
		return $result;		
	}
	
	public function setZusatzartikelItems($__params){
		
		$__filters = $this->_getFilters();
		$__validators = $this->_getValidators();
		
		$controlInput = new Zend_Filter_Input($__filters, $__validators, $__params);
		if(!$controlInput->isValid())
			throw new nook_Exception($this->error_no_valid_zusatzartikel_items);
		
		$this->_saveItemsZusatzartikel($__params);
		
		return;
	}
	
	private function _saveItemsZusatzartikel($__params){
		$Fa_Id = $__params['Fa_Id'];
		unset($__params['Fa_Id']);
		$this->_db->update('tbl_programmdetails', $__params, "Fa_ID = ".$Fa_Id);
		
		return;
	}
	
	private function _getFilters(){
		
		$filters = array(
			'Fa_Id' => 'Int',
			'minPersons' => 'Int',
			'maxPersons' => 'Int',
			'permanent_zusatz' => 'Int'
		);
		
		return $filters;
	}
	
	private function _getValidators(){
		$validators = array(
			'Fa_Id' => array(
				'Int',
				'presence' => 'required'
			),
			'minPersons' => array(
				'Int',
				'presence' => 'required'
			),
			'maxPersons' => array(
				'Int',
				'presence' => 'required'
			),
			'minDuration' => array(
				'presence' => 'required'
			),
			'maxDuration' => array(
				'presence' => 'required'
			),
			'permanent_zusatz' => array(
				'presence' => 'required'
			)
		);
		
		return $validators;
	}
}
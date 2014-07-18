<?php
class Admin_Model_Permanentzusatzartikel extends nook_Model_model{
	private $_db;
	
	public $error_no_valid_zusatzartikel_items = 220;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('front');
		
		return;
	}
	
	public function getCountPrograms(){
		$sql = "select count(Fa_Id) as anzahl from tbl_programmbeschreibung where sprache = '1'";
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getTableItems($start = false, $limit = false){
		$start = 0;
		$limit = 20;	
		
		if(array_key_exists('limit', $_POST)){
			$start = $_POST['start'];
			$limit = $_POST['limit'];
		}
			
        $sql = "
        SELECT
            `tbl_programmbeschreibung`.`progname`
            , `tbl_adressenfa`.`Ort`
            , `tbl_programmdetails`.`permanent_zusatz`
            , `tbl_programmbeschreibung`.`Fa_Id`
        FROM
            `tbl_programmbeschreibung`
            INNER JOIN `tbl_adressenfa` 
        ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_adressenfa`.`Fa_ID`)
            INNER JOIN `tbl_programmdetails`
        ON (`tbl_Adressenfa`.`Fa_ID` = `tbl_programmdetails`.`Fa_ID`)
        WHERE (`tbl_programmbeschreibung`.`sprache` = 1)
        ORDER BY `tbl_adressenfa`.`Ort` ASC  limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);
	
		return $result;		
	}
	
	public function setZusatzartikelItems($__params){
		$__filters = $this->_getFilters();
		$__validators = $this->_getValidators();
		
		$controlInput = new Zend_Filter_Input($__filters, $__validators, $__params);
		$control = $controlInput->isValid();
		if(!$controlInput->isValid())
			throw new nook_Exception($this->error_no_valid_zusatzartikel_items);
		
		$this->_saveItemsPermanentZusatzartikel($__params);
		
		return;
	}
	
	private function _saveItemsPermanentZusatzartikel($__params){
		$Fa_Id = $__params['Fa_Id'];
		unset($__params['Fa_Id']);
		$this->_db->update('tbl_programmdetails', $__params, "Fa_ID = ".$Fa_Id);
		
		return;
	}
	
	private function _getFilters(){
		
		$filters = array(
			'Fa_Id' => 'Int',
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
			'permanent_zusatz' => array(
				'Int',
				'presence' => 'required'
			)
        );
		
		return $validators;
	}
}
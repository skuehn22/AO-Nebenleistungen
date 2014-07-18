<?php
class Front_Model_Programmsuche extends nook_Model_model{
	
	public $error_no_correct_search_term = 130;
	public $error_no_existing_city = 131;
	
	public $condition_visible_one = 1;
	public $condition_visible_two = 2;
	
	
	private $_searchTerm;
	private $_selectLanguage;
	
	public function setSearchTerm($__searchTerm){
		$this->_searchTerm = $__searchTerm;
		$this->_selectLanguage = nook_Tool::findLanguage();
		
		$searchResult = $this->_search();
		$searchResult = nook_Tool::findCityIdByName($searchResult);
		
		return $searchResult;
	}
	
	private function _search(){
		$db = Zend_Registry::get('front');
		$ende = Zend_Registry::get('static')->items->programItemsPerPage;
		
		$sql = "
			SELECT
			    tbl_programmbeschreibung.Fa_Id,
			    tbl_programmbeschreibung.progname,
			    tbl_programmbeschreibung.sprache,
			    tbl_adressenfa.Ort,
			    tbl_programmdetails.prio_noko,
			    tbl_programmbeschreibung.txt,
			    tbl_programmbeschreibung.noko_kurz,
			    tbl_programmdetails.vk as Verkaufspreis,
			    tbl_programmdetails.mwst_satz as Mehrwertsteuer,
			    tbl_programmdetails.dauer as Dauer
			FROM
			    tbl_programmbeschreibung, tbl_adressenfa, tbl_programmdetails
			where
				tbl_programmbeschreibung.sprache = '".$this->_selectLanguage."'
				AND tbl_programmbeschreibung.progname like '%".$this->_searchTerm."%'
				AND tbl_programmbeschreibung.Fa_Id = tbl_adressenfa.Fa_ID
				AND tbl_programmbeschreibung.Fa_Id = tbl_programmdetails.Fa_ID
				AND (tbl_programmdetails.prio_noko = '".$this->condition_visible_one."' OR tbl_programmdetails.prio_noko = '".$this->condition_visible_two."')
				limit 0, ".$ende;
		
		$searchResult = $db->fetchAll($sql);
		
		return $searchResult;
	}
}
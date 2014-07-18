<?php
class Admin_Model_Fehlerdarstellung extends nook_Model_model{
	private $_db;
	
	public $error_test = 390;

	public function __construct(){
		$this->_db = Zend_Registry::get('front');

		return;
	}

    public function getCountFehlermeldungen(){
        $sql = "select count(id) as anzahl from tbl_exception";
        $anzahl = $this->_db->fetchOne($sql);

        return $anzahl;
    }

    public function getFehlermeldungen($__start, $__limit){
       $start = 0;
       $limit = 20;

       if(!empty($__start)){
           $start = $__start;
           $limit = $__limit;
       }

       $sql = "
           select `tbl_exception`.`id` as fehlernummer,`tbl_exception`.`date`, TRIM(TRAILING '.php' from file) as file, blockCode, reaction, line, variables,
           CONCAT_WS(' , ',`tbl_adressen`.`firstname`,`tbl_adressen`.`lastname`,`tbl_adressen`.`id`) AS kunde
           from tbl_exception
           LEFT JOIN `tbl_adressen`
           ON (`tbl_exception`.`kundenId` = `tbl_adressen`.`id`)
           order by `tbl_exception`.`id` desc limit ".$start.",".$limit;

       $fehlermeldungen = $this->_db->fetchAll($sql);

       $fehlermeldungen = $this->_pfadAufsplittenInModulUndKontroller($fehlermeldungen);

       return $fehlermeldungen;
    }
	
    private function _pfadAufsplittenInModulUndKontroller($__fehlermeldungen){
        for($i=0; $i<count($__fehlermeldungen); $i++){
            $teileFile = explode('\\',$__fehlermeldungen[$i]['file']);

            if(array_key_exists(6, $teileFile))
                $__fehlermeldungen[$i]['modul'] = $teileFile[6];
            if(array_key_exists(8, $teileFile))
                $__fehlermeldungen[$i]['model'] = $teileFile[8];
        }

        return $__fehlermeldungen;
    }

    private function _darstellungDerVariablen($__variablen){

        $variablen = array();
        $variablen = json_encode($__variablen);

        return $variablen;
    }

}
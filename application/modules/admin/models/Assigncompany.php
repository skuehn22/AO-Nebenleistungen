<?php
class Admin_Model_Assigncompany extends nook_Model_model{
	private $_db;
    private $_db_front;

    private $_rowsPerPage = 10;

    public $searchCompany;
    public $citySearchId;
    public $programSearchKeyword;
	
	// public $error_mailadress_already_exists = 330;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');
		
		return;
	}

    public function getCountCompanies(){
        $sql = "select count(id) from tbl_adressen where";

        if(!empty($this->searchCompany))
            $sql .= " company_name like '%".$this->searchCompany."%'";

        $anzahl = $this->_db_front->fetchOne($sql);

        return $anzahl;
    }

    public function getCountPrograms(){

        $sql = "SELECT
            COUNT(`tbl_programmdetails`.`adressen_id`) AS `anzahl`
            FROM
            `tbl_programmdetails`
            INNER JOIN `tbl_programmbeschreibung`
                ON (`tbl_programmdetails`.`id` = `tbl_programmbeschreibung`.`programmdetail_id`)
            WHERE `tbl_programmbeschreibung`.`sprache` = 1 AND adressen_id IS NULL";

        if(!empty($this->citySearchId))
            $sql .= " AND `tbl_programmdetails`.`AO_City` = '".$this->citySearchId."'";
        if(!empty($this->programSearchKeyword))
            $sql .= " AND `tbl_programmbeschreibung`.`progname` like '%".$this->programSearchKeyword."%'";

        $anzahl = $this->_db_front->fetchOne($sql);

        return $anzahl;
    }

     public function getPrograms($__params){
        $start = 0;
    	$limit = $this->_rowsPerPage;

        if(array_key_exists('limit', $__params)){
    		$start = $__params['start'];
    		$limit = $__params['limit'];
    	}

        $sql = "SELECT
            `tbl_programmbeschreibung`.`Fa_Id` AS `id`
            , `tbl_programmbeschreibung`.`progname`
            , `tbl_adressenfa`.`Ort` AS `city`
            , `tbl_adressenfa`.`Firma` AS `company`
        FROM
             .`tbl_programmbeschreibung`
        INNER JOIN .`tbl_adressenfa`
            ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_adressenfa`.`Fa_ID`)
        INNER JOIN .`tbl_programmdetails`
            ON (`tbl_adressenfa`.`Fa_ID` = `tbl_programmdetails`.`Fa_ID`)
        WHERE `tbl_programmbeschreibung`.`sprache` = 1 and `tbl_programmdetails`.`company_id` = '0'";

        if(!empty($this->citySearchId))
            $sql .= " AND `tbl_programmdetails`.`tbl_AO_City` = '".$this->citySearchId."'";
        if(!empty($this->programSearchKeyword))
            $sql .= " AND `tbl_programmbeschreibung`.`progname` like '%".$this->programSearchKeyword."%'";

        $sql .= " order by progname limit ".$start.",".$limit;

        $result = $this->_db_front->fetchAll($sql);

        return $result;
    }

    public function getCompanies($__params){
        $start = 0;
    	$limit = $this->_rowsPerPage;

        if(array_key_exists('limit', $__params)){
    		$start = $__params['start'];
    		$limit = $__params['limit'];
    	}

        $sql = "select id, company_name as company, city from tbl_company";

        if(!empty($this->searchCompany))
            $sql .= " where company_name like '%".$this->searchCompany."%'";

        $sql .= " order by company_name limit ".$start.",".$limit;

        $result = $this->_db_front->fetchAll($sql);

        return $result;
    }

    public function getCities(){
        $sql = "SELECT
            `AO_City_ID` as id
            , `AO_City` as city
        FROM
            .`tbl_ao_city`
        ORDER BY `AO_City` ASC";

        $result = $this->_db_front->fetchAll($sql);

        return $result;
    }

    public function setProgramsToCompany($__params){
        $errors = 0;
        $programs = array();
        $programs = json_decode($__params['programs']);
        $update = array();

        for($i=0; $i<count($programs); $i++){
            // $sql = "update tbl_programmdetails set company_id = '".$__params['hotelId']."' where Fa_ID = '".$programs[$i]."'";
            $update['company_id'] = $__params['hotelId'];

            $this->_db_front->update('tbl_programmdetails', $update, "Fa_ID = '".$programs[$i]."'");
        }
        

        return $errors;
    }

    public function findProgramsFromCompany($__hotelId){
        $sql = "SELECT
            `tbl_programmdetails`.`Fa_ID` AS `id`
            , `tbl_programmbeschreibung`.`progname`
            , `tbl_adressenfa`.`Firma` AS `company`
            , `tbl_adressenfa`.`Region` AS `region`
            , `tbl_adressenfa`.`Ort` AS `city`
            , `tbl_adressenfa`.`PLZ` AS `plz`
            , `tbl_adressenfa`.`Strasse` AS `street`
        FROM
            .`tbl_programmdetails`
            INNER JOIN .`tbl_programmbeschreibung`
                ON (`tbl_programmdetails`.`Fa_ID` = `tbl_programmbeschreibung`.`Fa_Id`)
            INNER JOIN .`tbl_adressenfa` 
                ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_adressenfa`.`Fa_ID`)
        WHERE (`tbl_programmdetails`.`company_id` = '".$__hotelId."'
            AND `tbl_programmbeschreibung`.`sprache` = 1)";

        $result = $this->_db_front->fetchAll($sql);

        return $result;
    }

    public function removeProgramsFromCompany($__companyId){
        $companies = array();
        $companies = json_decode($__companyId);

        $update = array();
        $update['company_id'] = 0;

        for($i=0; $i < count($companies); $i++){
            $this->_db_front->update('tbl_programmdetails', $update, "Fa_ID = '".$companies[$i]."'");
        }

        return;
    }

}
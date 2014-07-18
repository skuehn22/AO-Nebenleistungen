<?php
class Admin_Model_Stadtbeschreibung extends nook_Model_model{
    private $_db_front;
	
	// public $error_mailadress_already_exists = 600;

	public function __construct(){
        $this->_db_front = Zend_Registry::get('front');
		
		return;
	}

    public  function getStaedte(){
        $staedte = array();

        $sql = "
            SELECT
                `AO_City_ID` AS `id`
                , `AO_City` AS `stadt`
            FROM
                `tbl_ao_city`
            ORDER BY `stadt` ASC";

        $staedte = $this->_db_front->fetchAll($sql);

        return $staedte;
    }

    public function getstadtbeschreibung($__cityId, $__sprache){
        $stadtbeschreibung = array();

        $sql = "
            SELECT
                `stadtbeschreibung`
                , `programmbeschreibung`
                , `hotelbeschreibung`
            FROM
                `tbl_stadtbeschreibung`
            WHERE (`city_id` = ".$__cityId."
                AND `sprache_id` = '".$__sprache."')";

        $stadtbeschreibung = $this->_db_front->fetchRow($sql);

        return $stadtbeschreibung;
    }

    /**
     * Löscht die Stadtbeschreibung und legt diese neu an
     *
     * + löschen Stadtbeschreibung
     * + anlegen neue Stadtbeschreibung
     *
     * @param $__params
     */
    public function setstadtbeschreibung($__params)
    {

        $sql = "delete from tbl_stadtbeschreibung where city_id = ".$__params['city_id']." and sprache_id = '".$__params['sprache_id']."'";
        $this->_db_front->query($sql);

        $this->_db_front->insert('tbl_stadtbeschreibung', $__params);

        return;
    }

    public function map($__params){
        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        return $__params;
    }

}
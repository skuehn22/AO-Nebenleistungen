<?php
class nook_Standardstore{
    private $_db_front;

    public function __construct(){
        $this->_db_front = Zend_Registry::get('front');
    }

    public function getCities(){
        $sql = "SELECT
            `AO_City_ID` AS `id`
            , `AO_City` AS `city`
        FROM
            `tbl_ao_city`
        ORDER BY `city` ASC";

        $cities = $this->_db_front->fetchAll($sql);

        return $cities;
    }
}
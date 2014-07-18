<?php
/**
 * Created by JetBrains PhpStorm.
 * User: PC Nutzer
 * Date: 09.10.11
 * Time: 14:23
 * To change this template use File | Settings | File Templates.
 */
 
class nook_plz {

    static private $_instance;
    private $_datenbank;

    static public function getInstance($__datenbank){
       if(self::$_instance == null){
           self::$_instance = new nook_plz();
           self::$_instance->_datenbank = $__datenbank;
       }

       return self::$_instance;
    }

    public function getBundeslandEntsprechendPlz($__plz){
        $sql = "select region as bundesland from tbl_plz where zip = '".$__plz."'";
        return $this->_datenbank->fetchOne($sql);
    }

}

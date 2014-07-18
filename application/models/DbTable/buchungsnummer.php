<?php
 /**
  * Tabelle Buchungsnummer
  *
  * @author Stephan.Krauss
  * @date 25.09.2012
  * @file buchungsnummer.php
  * @package tabelle
  */
class Application_Model_DbTable_buchungsnummer extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_buchungsnummer';
    protected $_primary = 'id';

    /**
     * Kontrolle des Inhaltes einer Variable
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public function kontrolleValue($name, $value)
    {
        $kontrolle = false;

        switch($name){
            case "id":
                $kontrolle = filter_var($value, FILTER_VALIDATE_INT);
                break;
            default:
                $kontrolle = false;
        }

        if($kontrolle !== false)
            $kontrolle = true;

        return $kontrolle;
    }

} // end class

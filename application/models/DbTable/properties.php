<?php
/**
 * Tabelle properties
 *
 * @author Stephan.Krauss
 * @date 07.11.2012
 * @file properties.php
 * @package tabelle
 */
class Application_Model_DbTable_properties extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_properties';
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

<?php
/**
 * 30.07.12 14:54
 * Beschreibung der Klasse
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_programmbeschreibung extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_programmbeschreibung';
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
            case "programmdetail_id":
                $kontrolle = filter_var($value, FILTER_VALIDATE_INT);
                break;
            case "sprache":
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

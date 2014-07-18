<?php
/**
 * 20.09.2012
 * Tabelle 'tbl_preise_beschreibung'
 * Preisvarianten der vorhandenen Programme
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_preiseBeschreibung extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_preise_beschreibung';
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
            case "preise_id":
               $kontrolle = filter_var($value, FILTER_VALIDATE_INT);
               break;
            case "sprachen_id":
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

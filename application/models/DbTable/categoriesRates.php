<?php
/**
* Ermittlung Basisdaten einer Kategorie
*
* + Kontrolle des Inhaltes einer Variable
*
* @date 07.11.2012
* @file categories.php
* @package tools
*/
class Application_Model_DbTable_categoriesRates extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_categories_rates';
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
            case "properties_id":
                $kontrolle = filter_var($value, FILTER_VALIDATE_INT);
                break;
            case "category_id":
               $kontrolle = filter_var($value, FILTER_VALIDATE_INT);
               break;
            case "rate_id":
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

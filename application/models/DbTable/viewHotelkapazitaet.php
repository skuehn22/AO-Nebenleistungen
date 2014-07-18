<?php
/**
 * View der verfügbaren Kapazität der Hotels
 *
 * View mit roomlimit, datum,
 * cityId, Verfügbarkeit und ratenId
 *
 * @author Stephan.Krauss
 * @date 12.03.13
 * @file viewHotelkapazitaet.php
 * @package tabelle
 */
 
class Application_Model_DbTable_viewHotelkapazitaet extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_hotelkapazitaet';
    protected $_primary = 'id';
    

} // end class

<?php
/**
 * Holt aus Tabelle 'tbl_hotelbuchung'
 * die gebuchten Übernachtungen
 *
 *
 * @author Stephan Krauß
 */
class Application_Model_DbTable_viewHotelbuchung extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_hotelbuchung';
    protected $_primary = 'id';
    

} // end class

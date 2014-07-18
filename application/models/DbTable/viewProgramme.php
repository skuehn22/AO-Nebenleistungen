<?php
/**
 * Holt aus Tabelle 'tbl_hotelbuchung'
 * die gebuchten Übernachtungen
 *
 *
 * @author Stephan Krauß
 */
class Application_Model_DbTable_viewProgramme extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_programme';
    protected $_primary = 'programmId';
    

} // end class

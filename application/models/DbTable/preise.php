<?php
/**
 * 20.09.2012
 * Tabelle 'preise'
 * Preisvarianten der vorhandenen Programme
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_preise extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_preise';
    protected $_primary = 'id';
} // end class

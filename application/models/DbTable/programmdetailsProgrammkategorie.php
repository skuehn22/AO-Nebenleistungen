<?php
/**
 * Tabelle der Brückentabelle zwischen 'tbl_programmdetails' und 'tbl_programmkategorie'
 *
 * @author Stephan.Krauss
 * @date 06.43.2013
 * @file programmdetailsProgrammkategorie.php
 * @package tabelle
 */
class Application_Model_DbTable_programmdetailsProgrammkategorie extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_programmdetails_programmkategorie';
    protected $_primary = 'id';
}

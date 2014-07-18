<?php
/**
 * Brückentabelle 'tbl_superuser_kunden'
 *
 * Verknüpfung des Superuser auf seine Klienten / Kunden
 *
 *
 * @author Stephan.Krauss
 * @date 09.04.13
 * @file superuserKunden.php
 * @package tabelle
 */
 
class Application_Model_DbTable_superuserKunden extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_superuser_kunden';
    protected $_primary = 'id';
    

} // end class

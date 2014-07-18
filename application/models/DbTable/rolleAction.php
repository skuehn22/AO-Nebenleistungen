<?php
/**
 * Brückentabelle 'tbl_rolle_action'
 *
 * Definierte Zugriffsrechte der Rollen
 * des System auf die Action der Controller.
 *
 * @author Stephan.Krauss
 * @date 11.03.13
 * @file rolleAction.php
 * @package tabelle
 */
 
class Application_Model_DbTable_rolleAction extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_rolle_action';
    protected $_primary = 'id';
    

} // end class

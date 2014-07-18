<?php

/**
 * Registriert die Buchungen an die Hotelkette Meininger
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package tabelle
 */
class Application_Model_DbTable_schnittstelleMeininger extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_schnittstelle_meininger';
    protected $_primary = 'id';
    

} // end class

<?php
/**
 * Tabelle properties_days. Speicherung der Anreise und Abreisetage eines Hotels
 *
 * @author Stephan.Krauss
 * @date 10.04.2014
 * @file propertiesDays.php
 * @package tabelle
 */
class Application_Model_DbTable_propertiesDays extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_properties_days';
    protected $_primary = 'id';

}

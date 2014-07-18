<?php
/**
 * Verfügbarkeit der Raten
 *
 * Verfügbarkeit einer Rate eines Hotels
 * zu einem bestimmten datum
 *
 * @author Stephan.Krauss
 * @date 13.03.13
 * @file otaPrices.php
 * @package tabelle
 */
 
class Application_Model_DbTable_otaRatesAvailability extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_ota_rates_availability';
    protected $_primary = 'id';
} // end class

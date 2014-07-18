<?php
/**
 * Preise der Raten
 *
 * Preise der raten eines Hotels.
 * Aktueller Tagespreis und Zuordnung zu
 * Zimmerpreis und Personenpreis
 *
 * @author Stephan.Krauss
 * @date 13.03.13
 * @file otaPrices.php
 * @package tabelle
 */
 
class Application_Model_DbTable_otaPrices extends Zend_Db_Table_Abstract{
    protected  $_name = 'tbl_ota_prices';
    protected $_primary = 'id';
} // end class

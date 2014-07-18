<?php
/**
 * View zur Darstellung der Buchungsdetails
 * eines Programmes. Keine Sprachabhängigkeit.
 * Buchungsfrist, dauer, min. Personenanzahl ...
 *
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_viewProgrammeBuchungsdetails extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_programme_buchungsdetails';
    protected $_primary = 'id';
    

} // end class

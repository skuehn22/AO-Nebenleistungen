<?php
/**
 * Zeigt vorhandene Programme an.
 * + ID des Programmes
 * + deutscher Programmname
 * + Name der Stadt in der das Programm durchgeführt wird
 * + Firmenname
 * + ist das Programm aktiv ?
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_viewKommentareProgrammdetails extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_kommentare_programmdetails';
    protected $_primary = 'id';
    

} // end class

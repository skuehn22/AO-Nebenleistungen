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
 
class Application_Model_DbTable_viewVorhandeneProgramme extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_vorhandene_programme';
    protected $_primary = 'id';
    

} // end class

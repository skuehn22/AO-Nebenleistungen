<?php
/**
 * 30.07.12 14:54
 * View 'view_cart_programme'
 * Ermittelt die sprachabhängigen Texte der
 * Programme
 *
 * Basisdaten eines Programmes
 *
 * @author Stephan Krauß
 */
class Application_Model_DbTable_viewWarenkorbCartProgramme extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_cart_programme';
    protected $_primary = 'programmdetails_id';
    

} // end class
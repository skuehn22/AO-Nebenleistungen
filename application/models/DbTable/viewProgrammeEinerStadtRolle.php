<?php
/**
 * Tabelle viewProgrammeEinerStadtRolle
 *
 * + zeigt die Programme einer Stadt unter Berücksichtigung der Rolle des Administrator. Gesperrte Programme werden angezeigt.
 *
 *
 * @author Stephan Krauß
 */
class Application_Model_DbTable_viewProgrammeEinerStadtRolle extends Zend_Db_Table_Abstract{
    protected  $_name = 'view_programme_einer_stadt_rolle';
    protected $_primary = 'id';
    

}
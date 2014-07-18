<?php
/**
 * 04.06.2012
 * Fehlerbereich: 730
 * Stephan Krauss
 *
 * Beschreibung der Klasse:
 * Liest und schreibt die Zusatzinformation der Programme
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 */

class Admin_Model_DatensatzZusatzinformation extends Pimple_Pimple{

    // errors
    // private $_error_keine_programmId_vorhanden = 730;

    private $_db_groups = null;
    private $_db_hotel = null;


    /**
     * Übernimmt die Datenbankverbindungen
     *
     * @return void
     */
    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');
    }


    /**
     * Ermittelt mit der programmId die Zusatzinformation
     * des Programmes
     *
     * @param $__programmId
     * @return
     */
    public function getZusatzinformationProgramm($__programmId){
        $response = array();

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;
        $sql = "select worddocument from tbl_programmdetails where id = ".$__programmId;
        $worddocument = $db->fetchOne($sql);
        if(!empty($worddocument))
            $response['fieldZusatzinformation'] = $worddocument;

        return $response;
    }

    /**
     * speichert die Zusatzinformation eines programmes
     *
     * @param $__programmId
     * @param $__zusatzinformation
     * @return void
     */
    public function setZusatzinformationProgramm($__programmId, $__zusatzinformation){
        $update = array();
        $update['worddocument'] = $__zusatzinformation;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;
        $db->update('tbl_programmdetails', $update, "id = ".$__programmId);

        return;
    }

    /**
     * Löscht die Formatierung der
     * Zusatzinformation eines Programmes
     *
     * @param $__programmId
     * @return
     */
    public function cleanZusatzinformation($__zusatzinformation){
        $zusatzinformation = strip_tags($__zusatzinformation);
        $zusatzinformation = str_replace('&nbsp;','',$zusatzinformation);

        return $zusatzinformation;
    }


}
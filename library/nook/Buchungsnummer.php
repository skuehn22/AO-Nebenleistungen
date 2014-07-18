<?php
/**
 * Klasse zum kontrollieren und eintragen einer
 * Buchung in die Tabelle Buchungsnummer
 *
 * User: Stephan.Krauss
 * Date: 02.04.12
 * Time: 12:47
 * To change this template use File | Settings | File Templates.
 */
 
class nook_Buchungsnummer {

    /** @var $_db_front Zend_Db_Adapter_Mysqli */
    private $_db_front;

    public function __construct(){
        $this->_db_front = Zend_Registry::get('front');

        return;
    }

    /**
     * ermitteln der Buchungsnummer der aktuellen Buchung
     *
     * + ermitteln Kundennummer aus Session 'Auth'
     * + ermitteln Buchungsnummer wenn vorhanden
     * + wenn keine Buchungsnummer vorhanden, dann eintragen der Buchungsnummer
     *
     * @return $buchungsnummer_id
     */
    public function eintragenBuchungsnummer(){

        $auth = new Zend_Session_Namespace('Auth');
        $userDaten = $auth->getIterator();

        // Ist der Kunde bereits registriert und hat eine Buchungsnummer
        $sql = "select id from tbl_buchungsnummer where session_id = '".Zend_Session::getId()."'";

        $buchungsnummer_id = $this->_db_front->fetchOne($sql);

        // wenn keine Buchungsnummer vorhanden
        if(!$buchungsnummer_id){
            $sql = "insert into tbl_buchungsnummer set session_id = '".Zend_Session::getId()."'";
            $this->_db_front->query($sql);
            $buchungsnummer_id = $this->_db_front->lastInsertId();
        }

        // wenn Kunden ID bekannt
        if($userDaten['userId']){
            $update = array();
            $update['kunden_id'] = $userDaten['userId'];

            $this->_db_front->update('tbl_buchungsnummer', $update, "session_id = '".Zend_Session::getId()."'");
        }

        return $buchungsnummer_id;
    }

}

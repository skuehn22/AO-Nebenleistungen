<?php
/**
 * Beschreibung der Klasse
 *
 *
 * @author Stephan Krauß
 */

class Admin_Model_Whiteboard{

    private $_rollen = array(); // Rollen im System
    private $_benutzerRolleImSystem = null;
    private $_rollenIdDesBenutzers = null;

    private $_tabelleSystemRollen = null;
    private $_tabelleAdressen = null;

    private $_error_keine_daten = 940;

    public function __construct(){
        /** @var _tabelleSystemRollen Application_Model_DbTable_systemRole */
        $this->_tabelleSystemRollen = new Application_Model_DbTable_systemRole();
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();

        return;
    }

    /**
     * Gibt die vorhandenen Rollen
     * im System zurück
     */
    public function getRolleImSystem(){
        $rolleImSystem = $this
            ->_findRollenImSystem()
            ->_setRolleDesBenutzersImSystem()
            ->_getBenutzerRolleImSystem();

        return $rolleImSystem;
    }

    /**
     * Ermittelt die Rollen im System
     *
     * @return Admin_Model_Whiteboard
     */
    private function _findRollenImSystem(){
        $select = $this->_tabelleSystemRollen->select();
        $select->where('id > 4')->order('id');
        $rollenImSystemRaw = $this->_tabelleSystemRollen->fetchAll($select)->toArray();

        $rollenImSystem = array();

        array_walk($rollenImSystemRaw, function($value, $key) use(&$rollenImSystem){
            if($value['id'] <> 5){
                $rollenImSystem[$value['id']] = $value['role_name'];
            }
            else{
                $rollenImSystem[$value['id']] = 'Anbieter';
            }
        });

        $this->_rollen = $rollenImSystem;

        return $this;
    }

    /**
     * Welche Rolle hat der benutzer im System
     *
     * @return Admin_Model_Whiteboard
     */
    private function _setRolleDesBenutzersImSystem(){
        $auth = new Zend_Session_Namespace('Auth');

        $this->_benutzerRolleImSystem = 'sie sind angemeldet als "' . $this->_rollen[$auth->role_id] . '"<br>';

        return $this;
    }

    /**
     * Gibt Benutzerrolle zurück
     *
     * @return null
     */
    private function _getBenutzerRolleImSystem(){

        return $this->_benutzerRolleImSystem;
    }

    /**
     * Ermittelt
     * Anrede, Name und Vorname des benutzers
     */
    public function getpersonenDaten(){
        $auth = new Zend_Session_Namespace('Auth');
        $personenDaten = $auth->getIterator();
        $this->_rollenIdDesBenutzers = $personenDaten['role_id'];

        $cols = array(
            'title',
            'firstname',
            'lastname'
        );

        $select = $this->_tabelleAdressen->select();
        $select->from($this->_tabelleAdressen, $cols)->where('id = '.$personenDaten['userId']);
        $ergebnis = $this->_tabelleAdressen->fetchRow($select);

        if($ergebnis != null)
            $row = $ergebnis->toArray();
        else
            throw new nook_Exception($this->_error_keine_daten);

        return $row;
    }

    /**
     * Gibt die Rolle des RollenId des Benutzers zurück
     *
     * @return null
     */
    public function getRolleId(){

        return $this->_rollenIdDesBenutzers;
    }

}
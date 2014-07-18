<?php
/**
 * Fehlersuche mittels der Tabelle 'tbl_log_system'
 *
 * $fehlersuche = new nook_ToolFehlersuche($fehlernummer);
 * $params = $fehlersuche->getParams();
 *
 * @author Stephan Krauß
 */

class nook_ToolFehlersuche {

    private $_fehlernummer = null;
    private $_rawParams = null;
    private $_params = array();

    private $_tabelleLogSystem = null;
    private $_tabelleKunde = null;

    private $_sessionAuth = null;

    /**
     * Übernimmt die Fehlernummer
     * aus 'tbl_log_system'
     *
     * @param $__fehlernummer
     */
    public function __construct($__fehlernummer){

        $this->_fehlernummer = $__fehlernummer;
        $this->_tabelleLogSystem = new Application_Model_DbTable_logSystem(array('db' => 'front'));
        $this->_tabelleKunde = new Application_Model_DbTable_adressen(array('db' => 'front'));

        // bestimme Parameter
        $this
            ->_bestimmeParameterBaustein()
            ->_veraendereSession()
            ->_bestimmeKundenParameter()
            ->_umwandelnGespeicherteParameter();
    }

    /**
     * Bestimmt die Rohparameter aus der Tabelle
     * 'tbl_log_system'
     *
     * @return nook_ToolFehlersuche
     */
    private function _bestimmeParameterBaustein(){

        $cols = array(
            'variables',
            'session',
            'kundenId'
        );

        $select = $this->_tabelleLogSystem->select();
        $select->from($this->_tabelleLogSystem, $cols)->where("id = ".$this->_fehlernummer);

        $result = $this->_tabelleLogSystem->fetchRow($select);
        $this->_rawParams = $result->toArray();

        return $this;
    }

    /**
     * verändert die Session
     *
     * @return nook_ToolFehlersuche
     */
    private function _veraendereSession(){

        if(Zend_Session::getId() != $this->_rawParams['session']){
            session_write_close();
            session_id($this->_rawParams['session']);
            session_start();
        }

//        $neueSessionId = Zend_Session::getId();
//        $alteSessionId = $this->_rawParams['session'];

        $this->_sessionAuth = new Zend_Session_Namespace('Auth');
        $this->_sessionAuth->userId = $this->_rawParams['kundenId'];

        return $this;
    }

    /**
     * Ermittelt Kundenparameter
     *
     * @return nook_ToolFehlersuche
     */
    private function _bestimmeKundenParameter(){
        $kundenDaten = $this->_tabelleKunde->find($this->_rawParams['kundenId'])->toArray();
        $this->_sessionAuth->role_id = $kundenDaten['status'];

        return $this;
    }

    /**
     * Wandelt die Rohparametr in ein
     * Array um.
     *
     * @return nook_ToolFehlersuche
     */
    private function _umwandelnGespeicherteParameter(){
        $this->_params = json_decode($this->_rawParams['variables']);

        return $this;
    }

    /**
     * Gibt die Prameter zurück
     *
     * @return array
     */
    public function getParams(){
        $params = (array) $this->_params;

        return $params;
    }

} // end class

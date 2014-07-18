<?php
/**
 * 07.11.12 09:22
 * Ermitteln von Hoteldaten
 *
 *
 * @author Stephan Krauß
 */

class nook_ToolLogin {

    // Error
    private $_error_keine_mailadresse = 1240;
    private $_error_anzahl_zeichen_stimmt_nicht = 1241;
    private $_error_parameter_fehlen = 1242;
    private $_error_benutzer_mehrfach_vorhanden = 1243;

    // Tabellen , Views
    private $_tabelleAdressen = null;

    // Kondition
    private $_condition_mindestlaenge_passwort = 3;
    private $_condition_kunde_ist_aktiv = 3;

    protected $_mailadresse = null;
    protected $_passwort = null;
    protected $_userLogin = false;

    public function __construct(){
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
    }

    /**
     * @param $__mailadresse
     * @return nook_ToolLogin
     * @throws nook_Exception
     */
    public function setMailadresse($__mailadresse){

        if(! filter_var($__mailadresse, FILTER_VALIDATE_EMAIL))
            throw new  nook_Exception($this->_error_keine_mailadresse);

        $this->_mailadresse = $__mailadresse;

        return $this;
    }

    /**
     * @param $__passwort
     * @return nook_ToolLogin
     * @throws nook_Exception
     */
    public function setPasswort($__passwort){

        if(strlen($__passwort) < $this->_condition_mindestlaenge_passwort)
            throw new nook_Exception($this->_error_anzahl_zeichen_stimmt_nicht);

        $this->_passwort = $__passwort;

        return $this;
    }

    /**
     * Überprüft die Anmeldung des User
     *
     * @return bool
     * @throws nook_Exception
     */
    public function checkAuth()
    {

        if(empty($this->_mailadresse) or empty($this->_passwort))
            throw new nook_Exception($this->_error_parameter_fehlen);

        $benutzerId = $this->_authUser();

        return $benutzerId;
    }

    /**
     * Authentifikation des Users
     *
     * @return bool
     * @throws nook_Exception
     */
    private function _authUser(){
        $select = array(
            'id',
            'status'
        );

        // salzen Passwort
        $passwort = nook_ToolVerschluesselungPasswort::salzePasswort($this->_passwort);

        $select = $this->_tabelleAdressen->select();
        $select
            // ->from($this->_tabelleAdressen, $select)
            ->where("email = '".$this->_mailadresse."'")
            ->where("aktiv = ".$this->_condition_kunde_ist_aktiv)
            ->where("password = '".$passwort."'");

        $rows = $this->_tabelleAdressen->fetchAll($select)->toArray();

        if(count($rows) == 0)
            return false;
        elseif(count($rows) > 1)
            throw new nook_Exception($this->_error_benutzer_mehrfach_vorhanden);
        elseif(count($rows) == 1){
            $this->_setAuthInSession($rows[0]);

            return $rows[0]['id'];
        }
    }

    /**
     * Trägt Benutzer ID und
     * ID der Rolle in die
     * Session Namespace 'Auth' ein
     *
     * @param $__row
     * @return nook_ToolLogin
     */
    private function _setAuthInSession($__row){

        $sessionParams = array(
            'role_id' => $__row['status'],
            'userId' => $__row['id']
        );

        nook_ToolSession::setParamsInSessionNamespace('Auth', $sessionParams);

        return $this;
    }



} // end class

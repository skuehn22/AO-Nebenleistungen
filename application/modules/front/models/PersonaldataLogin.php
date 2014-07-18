<?php
class Front_Model_PersonaldataLogin{

	private $_error_fehler_email = 890;
    private $_error_fehler_passwort = 891;

    private $_tabelleKunde = null;

    public function __construct(){

        /** @var $_tabelleKunde Application_Model_DbTable_adressen */
        $this->_tabelleKunde = new Application_Model_DbTable_adressen();
    }

    /**
     * Kontrolliert die Anmeldeparameter
     *
     * @param $__params
     * @return void
     */
    public function checkInput($__params){
        $loginParams = array();

        // Submit Button
        unset($__params['login_anmelden']);

        foreach($__params as $key => $value){
            $teileKey = explode('_',$key);
            if(count($teileKey) == 2){

                if($teileKey[1] == 'email'){
                    if(!filter_var($value, FILTER_VALIDATE_EMAIL))
                        throw new nook_Exception($this->_error_fehler_email);
                }

                if($teileKey[1] == 'passwort'){
                    if(strlen($value) < 2)
                        throw new nook_Exception($this->_error_fehler_passwort);

                }

                $loginParams[$teileKey[1]] = $value;
            }
        }

        return $loginParams;
    }

    /**
     * Meldet den Benutzer an
     * Gibt die Kundendaten zurück
     *
     * @param $__loginParams
     * @return array
     */
    public function anmeldenUser($__loginParams){

        // gesalzene Passwort
        $passwort = nook_ToolStatic::berechnePasswort($__loginParams['passwort']);

        $select = $this->_tabelleKunde->select()->where("password = '".$passwort."'");
        $kundenDaten = $this->_tabelleKunde->fetchAll($select)->toArray();

        if(is_array($kundenDaten) and count($kundenDaten) == 1){
            $auth = new Zend_Session_Namespace('Auth');
            $auth->role_id = $kundenDaten[0]['status'];
            $auth->userId = $kundenDaten[0]['id'];
        }
        else
            $kundenDaten = false;

        return $kundenDaten[0];
    }

}
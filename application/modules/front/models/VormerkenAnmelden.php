<?php
/**
* Handelt die Anmeldung zur Vormerkung eines Warenkorbes
*
* + Ist der Kunde angemeldet ?
* + Kontrolliert die Anzahl der Mailadressen
* + Kontrolliert ob die Mailadresse
* + Kontrolliert die Parameter des Anmeldeformulares
* + Kontrolliert oder speichert
* + Kontrolliert eine Anmeldung wenn
* + Speichert die Anmeldung wenn
* + Mit User ID und Rolle ID
* + Kontrolliert das Login
*
* @date 04.29.2013
* @file VormerkenAnmelden.php
* @package front
* @subpackage model
*/
class Front_Model_VormerkenAnmelden extends nook_ToolModel implements ArrayAccess{

    // Error
    private $_error_mailadresse_mehrfach_vorhanden = 1190;
    private $_error_fehler_eingabe_mail = 1191;
    private $_error_passwort = 1192;
    private $_error_benutzer_mehrfach_vorhanden = 1193;
    private $_error_eintragen_fehlgeschlagen = 1194;
    private $_error_agb_nicht_ausgewaehlt = 1195;


    // Konditionen
    private $_condition_benutzer_noch_nicht_vorhanden = 0;
    private $_condition_benutzer_schon_vorhanden = 1;
    private $_condition_user_id_unbekannt = 0;
    private $_condition_rolle_user = 1;
    private $_condition_rolle_neuling = 2;
    private $_condition_mindestlaenge_passwort = 6;
    private $_condition_warenkorb_status_vormerkung = 2;


    // Tabelle / Views
    private $_tabelleAdressen = null;

    protected $_userId = null; // ID des Users
    protected $_rolle = null; // Rolle des Users

	public function __construct(){
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
	}

    /**
     * Ist der Kunde angemeldet ?
     *
     * @return bool
     */
    public  function checkKundeAnmeldung(){

        $userAngemeldet = nook_ToolUserId::kontrolleAnmeldung();

        return $userAngemeldet;
    }

    /**
     * Kontrolliert die Anzahl der Mailadressen
     *
     * @param $__mailAdresse
     * @return string
     */
    public function checkMailadresse($__mailAdresse){
        $benutzerVorhanden = $this->_checkMailadresse($__mailAdresse);

        if(empty($benutzerVorhanden))
            $antwort = 'false';
        else
            $antwort = 'true';

        return $antwort;
    }

    /**
     * Kontrolliert ob die Mailadresse
     * bereits registriert wurde.
     * Fehler wenn mehrfach vorhanden
     *
     * @param $__mailAdresse
     * @return bool
     * @throws nook_Exception
     */
    private function _checkMailadresse($__mailAdresse){
        $benutzerVorhanden = false;

        $mailAdresse = trim($__mailAdresse);

        if(empty($mailAdresse))
            return $benutzerVorhanden;

        $where = "email = '".$mailAdresse."'";

        $cols = array(
           'id' => new Zend_Db_Expr("count(id)")
        );

        $select = $this->_tabelleAdressen->select();
        $select
            ->from($this->_tabelleAdressen, $cols)
            ->where($where);

        $rows = $this->_tabelleAdressen->fetchAll($select)->toArray();

        if($rows[0]['id'] == $this->_condition_benutzer_noch_nicht_vorhanden)
            $benutzerVorhanden = false;
        else
            $benutzerVorhanden = true;

        if($rows[0]['id'] > $this->_condition_benutzer_schon_vorhanden)
            throw new nook_Exception($this->_error_mailadresse_mehrfach_vorhanden);

        return $benutzerVorhanden;
    }

    /**
     * Kontrolliert die Parameter des Anmeldeformulares
     *
     * @param $__params
     * @return array
     * @throws nook_Exception
     */
    public function checkAnmeldung($__params){
        $params = array();

            $checkEmail = filter_var($__params['email1'], FILTER_VALIDATE_EMAIL);
        if(empty($checkEmail))
            throw new nook_Exception($this->_error_fehler_eingabe_mail);

        if( $__params['email1'] != $__params['email2'] )
            throw new nook_Exception($this->_error_fehler_eingabe_mail);

        $params['email'] = $__params['email1'];

        if( empty($__params['passwort1']) or ($__params['passwort1'] != $__params['passwort2']) or strlen($__params['passwort1']) < $this->_condition_mindestlaenge_passwort)
            throw new nook_Exception($this->_error_passwort);

        $params['password'] = $__params['passwort1'];

        return $params;
    }

    /**
     * Kontrolliert oder speichert
     * die Anmeldung
     *
     * @param $__params
     */
    public function anmeldung($__params){

        $anmeldungVorhanden = $this->_checkMailadresse($__params['email']);

        // Mailadresse schon vorhanden
        if(!empty($anmeldungVorhanden))
            $vorhandeneAnmeldung = $this->_kontrolleAnmeldung($__params);
        // eintragen neue Anmeldung
        else
            $vorhandeneAnmeldung = $this->_eintragenAnmeldung($__params);

        // Anmeldung Benutzer
        if(!empty($this->_userId) and !empty($this->_rolle))
            $this->_freischaltenAuthSession();

        return $vorhandeneAnmeldung;
    }

    /**
     * Kontrolliert eine Anmeldung wenn
     * die Mail - Adresse bekannt ist.
     * Überprüft ob Mail und Passwort übereinstimmen.
     *
     * @param $__params
     */
    private function _kontrolleAnmeldung($__params){
        $vorhandeneAnmeldung = true;

        $whereMail = "email = '".$__params['email']."'";
        $wherePasswort = "password = '".nook_ToolVerschluesselungPasswort::salzePasswort($__params['password'])."'";

        $select = $this->_tabelleAdressen->select();
        $select
            ->where($whereMail)
            ->where($wherePasswort);

        $rows = $this->_tabelleAdressen->fetchAll($select)->toArray();

        // Benutzer nicht angemeldet
        if(count($rows) == 0)
            $vorhandeneAnmeldung = false;

        // Benutzer 1 mal vorhanden
        if(count($rows) == 1){

            // Werte für 'Auth' in Session
            $this->_userId = $rows[0]['id'];
            $this->_rolle = $rows[0]['status'];
        }

        // Benutzer mehrfach vorhanden
        if(count($rows) > 1)
            throw new nook_Exception($this->_error_benutzer_mehrfach_vorhanden);

        return $vorhandeneAnmeldung;
    }

    /**
     * Speichert die Anmeldung wenn
     * die Mailadresse unbekannt ist
     *
     * @param $__params
     * @return bool
     * @throws nook_Exception
     */
    private function _eintragenAnmeldung($__params){
        $vorhandeneAnmeldung = true;

        $passwortVerschluesselt = nook_ToolVerschluesselungPasswort::salzePasswort($__params['password']);

        $insert = array(
            'email' => $__params['email'],
            'password' => $passwortVerschluesselt,
            'status' => $this->_condition_rolle_neuling
        );

        $benutzerId = $this->_tabelleAdressen->insert($insert);

        // Werte für 'Auth' in Session
        $this->_userId = $benutzerId;
        $this->_rolle = $this->_condition_rolle_neuling;

        if(empty($benutzerId))
            throw new nook_Exception($this->_error_eintragen_fehlgeschlagen);

        return $vorhandeneAnmeldung;
    }

    /**
     * Mit User ID und Rolle ID
     * wird der Benutzer freigeschaltet
     *
     * @return Front_Model_VormerkenAnmelden
     */
    private function _freischaltenAuthSession()
    {
        // setzt User ID und Rolle ID in Namespace 'Auth'
        nook_ToolSession::setzeAuthInNamespace($this->_userId, $this->_rolle);

        return $this;
    }

    /**
     * Kontrolliert das Login
     * eines Benutzers beim
     * 'Vormerken'
     *
     * @param $__params
     */
    public function login($__params){

        $toolLogin = new nook_ToolLogin();
        $benutzerId = $toolLogin
            ->setMailadresse($__params['email'])
            ->setPasswort($__params['passwort'])
            ->checkAuth();

        return $benutzerId;
    }

    /**
     * @return int
     */
    public function getBenutzerId()
    {
        return $this->_userId;
    }
}
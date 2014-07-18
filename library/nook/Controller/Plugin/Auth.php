<?php
/**
* Authentifikation des Benutzer
*
* + Authentifiziert den User oder Superuser
* + Trägt den Kunden in die 'tbl_adressen_superuser'
* + Wenn Superuser festgestellt wurde,
* + Kontrolle Superuser
* + Authentifiziert einen Kunden
* + Kontrolliert Benutzerkennung und Passwort.
*
* @author Stephan.Krauss
* @date 04.05.2013
* @file Auth.php
* @package plugins
*/
class Plugin_Auth extends Zend_Controller_Plugin_Abstract
{

    // Konditionen
    private $_role;
    private static $ROLE_GUEST = 1;
    private static $ROLE_PROVIDER = 5;

    public function dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
    {
        $db = Zend_Registry::get('front');

        // Session Auth
        $auth = new Zend_Session_Namespace('Auth');

        // Parameter
        $params = $request->getParams();
        $module = $params['module'];
        $controller = $params['controller'];
        $action = $params['action'];

        // post variables from login
        $post = $request->getPost();

        // Authentifikation des Benutzer
        $authParams = $this->doAuthUser($post, $params);

        // registrieren der Authentifikation
        if(array_key_exists('login', $params)) {

            // Rolle des Kunden
            $auth->role_id = $authParams['roleId'];

            // Flag Superuser: 1 = kein Superuser, 2 = Superuser
            $auth->superuser = $authParams['superuser'];

            if(array_key_exists('anbieterId', $authParams)) {
                // Anbieter ID
                $auth->anbieter = $authParams['anbieterId'];

                // ID der Firma
                $auth->company_id = $authParams['companyId'];

                // ID des Kunden
                $auth->userId = $authParams['userId'];

                // speichern Werte des Users
                $model = new Front_Model_Merkliste();
                $model->checkUser($params);
            }
        }

        // ermitteln der Rechte an 'block'
        $sql = "select role_id from tbl_blocks where module = '" . $module . "' and controller = '" . $controller . "'";
        $control_role_id = $db->fetchOne($sql);

        if($auth->role_id < $control_role_id) {
            $request->setControllerName('Login');
            $request->setModuleName('front');
            $auth->role_id = self::$ROLE_GUEST;
        }

        // ausloggen
        if(array_key_exists('logout', $params)) {
            $request->setControllerName('Login');
            $request->setModuleName('front');
        }
    }

    /**
     * Authentifiziert den User oder Superuser
     *
     * + Kontrolle auf Superuser
     * + eintragen Superuser in 'tbl_buchungsnummer'
     * + fügt Flag Superuser hinzu
     *
     * @param bool $__post
     * @param $__params
     * @return array|mixed
     */
    protected function doAuthUser ($__post = false, $__params)
    {
        $authParams = array();
        $authParams[ 'roleId' ] = self::$ROLE_GUEST;

        $sessionAuth = new Zend_Session_Namespace('Auth');

        // Prüfe auf Login button
        if(array_key_exists('login', $__params)) {

            // Kontrolle Benutzerkennung und Passwort
            $arrayBenutzer = $this->_kontrolleBenutzerkennungPasswort($authParams, $__post);

            // Kontrolle auf Superuser
            $kontrolleSuperuser = $this->kontrolleSuperUser($arrayBenutzer[ 'benutzername' ],$arrayBenutzer[ 'passwort' ]);

            // eintragen in 'tbl_buchungsnummer' und 'tbl_adressenSuperuser'
            if(!empty($kontrolleSuperuser)) {
                $superuserId = $this->eintragenTabelleBuchungsnummer($kontrolleSuperuser['id']);
                $this->_eintragenTabelleAdressenSuperuser($superuserId, $kontrolleSuperuser['id']);
            }

            // einloggen User
            $authParams = $this->erkennungKunde($authParams, $arrayBenutzer['benutzername'], $arrayBenutzer['passwort'], $kontrolleSuperuser['password']);

        }
        // Prüfe auf Logout Button
        elseif(array_key_exists('logout', $__params)) {
            $sessionAuth->role_id = self::$ROLE_GUEST;
            $sessionAuth->anbieter = false;
            $sessionAuth->company_id = false;
            $sessionAuth->userId = false;

            Zend_Session::namespaceUnset('warenkorb');
        }
        // wenn kein Auth
        elseif(empty($sessionAuth->role_id)) {
            $sessionAuth->role_id = self::$ROLE_GUEST;
        }

        // fügt Flag Superuser hinzu
        if(!empty($kontrolleSuperuser) and is_array($kontrolleSuperuser))
            $authParams['superuser'] = 2;
        else
            $authParams['superuser'] = 1;

        return $authParams;
    }

    /**
     * Trägt den Kunden in die 'tbl_adressen_superuser' ein
     *
     * @param $superuserId
     * @param $kundenId
     * @return mixed
     */
    private function _eintragenTabelleAdressenSuperuser ($superuserId, $kundenId)
    {
        $whereAdressenId = "adressen_id = " . $kundenId;
        $whereSuperuserId = "superuser_id = " . $superuserId;

        $insert = array(
            'adressen_id'  => $kundenId,
            'superuser_id' => $superuserId
        );

        $tabelleAdressenSuperuser = new Application_Model_DbTable_adressenSuperuser();

        $select = $tabelleAdressenSuperuser->select();
        $select
            ->where($whereAdressenId)
            ->where($whereSuperuserId);

        $query = $select->__toString();

        $rows = $tabelleAdressenSuperuser->fetchAll($select)->toArray();

        // wenn noch nicht in Tabelle 'tbl_adressenSuperuser'
        if(count($rows) == 0) {
            $kontrolle = $tabelleAdressenSuperuser->insert($insert);
        }

        return $kontrolle;
    }

    /**
     * anlegen eines Warenkorbes in 'tbl_buchungsnummer'
     *
     * + nur wenn Warenkorb noch nicht existiert
     *
     * @param $kundenId
     * @return mixed
     */
    private function eintragenTabelleBuchungsnummer ($kundenId)
    {
        $sessionId = Zend_Session::getId();

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $select = $tabelleBuchungsnummer->select();
        $select->where("session_id = '" . $sessionId . "'");

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        $static = Zend_Registry::get('static');
        $superuserId = $static->geheim->idSuperuser;

        // neuer Datensatz
        if(count($rows) == 0){

            $insert = array(
                'session_id'   => $sessionId,
                'kunden_id'    => $kundenId,
                'superuser_id' => $superuserId
            );

            $tabelleBuchungsnummer->insert($insert);
        }

        // update bestehenden Datensatz
        if(count($rows) == 1){

            $update = array(
                'superuser_id' => $superuserId,
                'kunden_id'    => $kundenId
            );

            $whereSessionId = "session_id = '".$sessionId."'";

            $tabelleBuchungsnummer->update($update, $whereSessionId);
        }

        return $superuserId;
    }

    /**
     * Kontrolle Superuser
     *
     * + Wenn es ein Superuser ist, wird das gesalzene Passwort des Kunden zurückgegeben.
     * + wenn kein Superuser, return = false
     * + wenn Superuser, return password und id
     *
     * @param $username
     * @param $password
     * @return string
     */
    protected function kontrolleSuperUser ($username, $password)
    {
        $static = Zend_Registry::get('static');
        $passwortSuperuser = $static->geheim->superuser;

        // wenn Benutzer kein Superuser
        if($password !== $passwortSuperuser)
            return false;

        $tabelleAdressen = new Application_Model_DbTable_adressen();
        $select = $tabelleAdressen->select();

        $cols = array(
            'password',
            'id'
        );

        $whereEmail = "email = '" . $username . "'";

        $select
            ->from($tabelleAdressen, $cols)
            ->where($whereEmail);

        $rows = $tabelleAdressen->fetchAll($select)->toArray();

        return $rows[0];
    }

    /**
     * Authentifiziert einen Kunden
     *
     * @return mixed
     */
    protected function erkennungKunde ($authParamsBenutzer, $username, $password, $passwortKundeGesalzen = false)
    {
        // gesalzene Passwort wenn Kunde
        if(empty($passwortKundeGesalzen))
            $gesalzenePasswort = nook_ToolVerschluesselungPasswort::salzePasswort($password);
        // Superuser
        else
            $gesalzenePasswort = $passwortKundeGesalzen;

        $whereEmail = "email = '" . $username . "'";
        $wherePasswort = "password = '" . $gesalzenePasswort . "'";

        $cols = array(
            new Zend_Db_Expr("id as userId"),
            new Zend_Db_Expr('status as roleId'),
            new Zend_Db_Expr('id as companyId'),
            new Zend_Db_Expr('anbieter as anbieterId'),
            new Zend_Db_Expr('properties_id as hotelId')
        );

        // Bestimmung Benutzerdaten
        $tabelleAdressen = new Application_Model_DbTable_adressen();
        $select = $tabelleAdressen->select();
        $select
            ->from($tabelleAdressen, $cols)
            ->where($whereEmail)
            ->where($wherePasswort);

        $benutzerDaten = $tabelleAdressen->fetchAll($select)->toArray();

        // Zuordnung der companyId entsprechend des Bereiches des System
        // Bereich Programme
        if(is_null($benutzerDaten[0]['hotelId'])){
            unset($benutzerDaten[0]['hotelId']);
        }
        // Bereich Uebernachtung
        else{
            $benutzerDaten[0]['companyId'] = $benutzerDaten[0]['hotelId'];
            unset($benutzerDaten[0]['hotelId']);
        }

        // Benutzer unbekannt oder mehr als ein Benutzer
        if(count($benutzerDaten) != 1) {
            $authParamsBenutzer[ 'roleId' ] = self::$ROLE_GUEST;
        }
        // Benutzer erkannt
        else {
            $authParamsBenutzer = $benutzerDaten[0];

            // anlegen neue Buchungsnummer
            $buchungsnummer = $this->anlegenNeueBuchungsnummer($benutzerDaten);

            // eintragen in Session Namespace 'buchung'
            $this->korrekturNamespaceBuchung($buchungsnummer);
        }

        return $authParamsBenutzer;
    }

    /**
     * Anlegen einer neuen Buchungsnummer für den Benutzer, wenn noch keine Buchungsnummer vorhanden
     *
     * + Kontrolle Buchungsnummer schon vorhanden
     * + anlegen neue Buchungsnummer
     *
     * @param $benutzerDaten
     * @return int
     */
    protected function anlegenNeueBuchungsnummer($benutzerDaten)
    {
        // Kontrolle Buchungsnummer schon vorhanden
        $sessionId = Zend_Session::getId();

        $selectCols = array(
            'id'
        );

        $whereSessionId = "session_id = '".$sessionId."'";

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $selectCols)
            ->where($whereSessionId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) == 1)
            return $rows[0]['id'];

        // anlegen neue Buchungsnummer
        $insertCols = array(
            'zaehler' => 0,
            'kunden_id' => $benutzerDaten[0]['userId'],
            'session_id' => $sessionId
        );

        $buchungsnummer = $tabelleBuchungsnummer->insert($insertCols);

        return $buchungsnummer;
    }

    /**
     * Eintagen Daten Benutzer in Session Namespace 'buchung'
     *
     * @param $authParamsBenutzer
     */
    protected function korrekturNamespaceBuchung($buchungsnummer)
    {
        $sessionNamespaceBuchung = new Zend_Session_Namespace('buchung');
        $sessionNamespaceBuchung->buchungsnummer = $buchungsnummer;
        $sessionNamespaceBuchung->zaehler = 0;

        return;
    }

    /**
     * Kontrolliert Benutzerkennung und Passwort. Gibt Benutzerkennung und Passwort zurück.
     *
     * @param $authParams
     * @param $__post
     * @return array
     */
    private function _kontrolleBenutzerkennungPasswort ($authParams, $__post)
    {
        $arrayBenutzer = array();

        if(array_key_exists('username', $__post)) {
            $username = $__post[ 'username' ];
            $username = trim($username);
            if(empty($username)) {
                return $authParams;
            }
        }

        if(array_key_exists('password', $__post)) {
            $password = $__post[ 'password' ];
            $password = trim($password);
            if(empty($password)) {
                return $authParams;
            }
        }

        $arrayBenutzer[ 'passwort' ] = $password;
        $arrayBenutzer[ 'benutzername' ] = $username;

        return $arrayBenutzer;
    }
}

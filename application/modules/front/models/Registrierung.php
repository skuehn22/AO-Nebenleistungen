<?php
/**
 * Trägt die personendaten ein
 *
 * + Eintragen der Daten in die Tabelle 'tbl_adressen'
 * + Trägt die Daten in 'tbl_adressen'
 * + Kontrolle auf Superuser und Login
 * + Kontrolliert ob der Superuser den Kunden angelegt hat
 * + Trägt den User in die Tabelle 'tbl_buchungsnummer' ein
 * + Wenn das Passwort den Superuser ausweist,
 * + Setzt in der Session_Namespace 'Auth'
 * + Nimmt E-Mail Adresse entgegen und kontrolliert diese
 * + Ermittelt das vorhandensein einer Mailadresse
 *
 * @date 04.36.2013
 * @file Registrierung.php
 * @package front
 * @subpackage model
 */
class Front_Model_Registrierung extends nook_ToolModel implements ArrayAccess
{

    // Error
    private $_error_kein_passwort_vorhanden = 1410;
    private $_error_mail_adresse_leer = 1411;

    // Flags
    private $flag_mailadresse_leer = 1414;
    private $flag_keine_mailadresse = 1415;
    private $flag_mailadresse_schon_vorhanden = 1416;
    private $flag_mailadresse_nicht_vorhanden = 1417;

    // Konditionen
    private $condition_rolle_kunde = 3;

    // Flags

    // Container
    private $_pimple = null;

    protected $_passwort = null;
    protected $_userId = null;
    protected $_rolle_user = 3;

    /**
     * @param bool $__pimple
     */
    public function __construct($__pimple = false)
    {
        if (empty($__pimple)) {
            return;
        }

        $this->_pimple = $__pimple;
    }

    /**
     * Eintragen der Daten in die Tabelle 'tbl_adressen'
     *
     * @param array $__params
     * @return Front_Model_Registrierung
     */
    public function insertDatenInTabelleAdressen(array $__params)
    {

        // Personendaten in 'tbl_adressen'
        $this->_insertDatenInTabelleAdressen($__params);

        return $this;
    }

    /**
     * Trägt die Daten in 'tbl_adressen'
     *
     * @param $params
     * @return int
     */
    private function _insertDatenInTabelleAdressen($params)
    {
        $this->_passwort = $params['password'];

        /** @var $datenAdressen Application_Model_Adressen */
        $datenAdressen = $this->_pimple['datenAdressen'];

        $datenAdressen
            ->setTitle(trim($params['title']))
            ->setFirstname(trim($params['firstname']))
            ->setLastname(trim($params['lastname']))
            ->setRolleBenutzer($this->condition_rolle_kunde)
            ->setOfflinekunde($params['offlinekunde']);

        $params['company'] = trim($params['company']);
        if (!empty($params['company'])) {
            $datenAdressen->setCompany($params['company']);
        }

        $datenAdressen
            ->setCountry($params['country'])
            ->setCity(trim($params['city']))
            ->setZip($params['zip'])
            ->setStreet(trim($params['street']))
            ->setEmail(trim($params['email']));

        $datenAdressen->setPassword(trim($params['password']));

        // Telefonnummer geschäftlich
        $params['phonenumber'] = trim($params['phonenumber']);
        if (!empty($params['phonenumber'])) {
            $datenAdressen->setPhonenumber($params['phonenumber']);
        }

        // Telefonnummer privat
        $params['phonenumber1'] = trim($params['phonenumber1']);
        if (!empty($params['phonenumber1'])) {
            $datenAdressen->setPhonenumber1($params['phonenumber1']);
        }

        $userId = $datenAdressen
            ->setSchriftwechsel($params['schriftwechsel'])
            ->insert();

        $this->_userId = $userId;

        return $userId;
    }

    /**
     * Kontrolle auf Superuser und Login
     *
     * + Kontrolle Superuser
     *
     * @return Front_Model_Registrierung
     * @throws nook_Exception
     */
    public function loginUser()
    {

        if (empty($this->_passwort)) {
            throw new nook_Exception($this->_error_kein_passwort_vorhanden);
        }

        $this->_kontrolleSuperuser();
        $kundenId = $this->_anlegenBuchungsnummer();
        $this->_eintragenZuordnungSuperuser();
        $this->_loginKunde($kundenId);

        return $this;
    }

    /**
     * Kontrolliert ob der Superuser den Kunden angelegt hat
     */
    private function _kontrolleSuperuser()
    {

        /** @var $kontrolleSuperuser nook_ToolKontrolleSuperuser */
        $kontrolleSuperuser = $this->_pimple['kontrolleSuperuser'];
        $kontrolleSuperuser->kontrolleSuperuserMitPasswort($this->_passwort);

        return;
    }

    /**
     * Trägt den User in die Tabelle 'tbl_buchungsnummer' ein
     *
     * @return int
     */
    private function _anlegenBuchungsnummer()
    {
        $kontrolleSuperuser = $this->_pimple['kontrolleSuperuser'];
        $sessionId = Zend_Session::getId();

        /** @var $datenAdressen Application_Model_Adressen */
        $datenAdressen = $this->_pimple['datenAdressen'];
        $kundenId = $datenAdressen->getId();

        $cols = array(
            'session_id' => $sessionId,
            'kunden_id' => $kundenId
        );

        // anlegen Buchungsnummer
        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->_pimple['tabelleBuchungsnummer'];
        $tabelleBuchungsnummer->insert($cols);

        return $kundenId;
    }

    /**
     * Wenn das Passwort den Superuser ausweist,
     * dann trage diesen in die Tabelle 'tbl_buchungsnummer' ein
     *
     * @return string
     */
    private function _eintragenZuordnungSuperuser()
    {
        /** @var $kontrolleSuperuser nook_ToolKontrolleSuperuser */
        $kontrolleSuperuser = $this->_pimple['kontrolleSuperuser'];
        $sessionId = Zend_Session::getId();

        // wenn Superuser, dann Zuordnung in 'tbl_buchungsnummer'
        $kontrolleSuperuser->zuordnungBuchungZuSuperuser();

        return $sessionId;
    }

    /**
     * Setzt in der Session_Namespace 'Auth'
     * die Kunden - ID und die Rolle 'User'
     *
     * @param $__kunden_id
     * @return mixed
     */
    private function _loginKunde($__kunden_id)
    {
        $auth = new Zend_Session_Namespace('Auth');
        $auth->role_id = $this->_rolle_user;
        $auth->userId = $__kunden_id;

        return $__kunden_id;
    }

    /**
     * Nimmt E-Mail Adresse entgegen und kontrolliert diese
     * + Exception wenn es keine Mailadresse ist
     *
     * @param $email
     * @return string
     * @throws nook_Exception
     */
    public function setEmail($email)
    {

        if (empty($email)) {
            nook_ExceptionInformationRegistration::registerError($this->_error_mail_adresse_leer);

            return $this->flag_mailadresse_leer;
        }

        /** @var $dataAdressen Application_Model_Adressen */
        $dataAdressen = $this->_pimple['datenAdressen'];
        if (!$dataAdressen->validateEmail($email)) {
            nook_ExceptionInformationRegistration::registerError($this->flag_keine_mailadresse);

            return $this->flag_keine_mailadresse;
        } else {
            $dataAdressen->setEmail($email);

            return $this->flag_mailadresse_nicht_vorhanden;
        }
    }

    /**
     * Ermittelt das vorhandensein einer Mailadresse
     * Wenn keine Mailadresse, dann Rückgabe 0.
     * Wenn Mailadresse bereits vorhanden, dann Rückgabe 1
     *
     * @param $email
     * @return int
     */
    public function pruefeDoppelteMailadresse()
    {
        /** @var $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = $this->_pimple['tabelleAdressen'];

        /** @var $dataAdressen Application_Model_Adressen */
        $dataAdressen = $this->_pimple['datenAdressen'];

        $cols = array(
            new Zend_Db_Expr("count(email) as anzahl")
        );

        $where = "email = '" . $dataAdressen->getEmail() . "'";

        $select = $tabelleAdressen->select();
        $select->from($tabelleAdressen, $cols)->where($where);
        $rows = $tabelleAdressen->fetchAll($select)->toArray();

        if (empty($rows[0]['anzahl'])) {
            return $this->flag_mailadresse_nicht_vorhanden;
        }
        else {
            return $this->flag_mailadresse_schon_vorhanden;
        }
    }

}
<?php
/**
 * Daten Model für die Tabelle 'tbl_adressen'
 *
 * + Kontrolliert die Mailadresse
 * + Vorname ist nicht Pflicht
 * + Passwort wird verschlüsselt und gesalzen
 * + Telefonnummer geschäftlich
 * + Telefonnummer privat
 * + Kontrolliert ob Mailadresse nicht null
 * + Überprüft ob eine Mailadresse bereits vorhanden ist.
 * + eintragen einer Adresse
 * + eintragen Personendaten in Tabelle Adressen
 *
 * @date 10.37.2013
 * @file Adressen.php
 * @package tabelle
 */
class Application_Model_Adressen extends nook_ToolModel implements ArrayAccess
{

    // Error
    private $_error_daten_unvollstaendig = 1400;

    // Konditionen

    // Flags

    private $_pimple = null;

    protected $title;
    protected $firstname;
    protected $lastname;
    protected $dateOfBirth;
    protected $company;
    protected $city;
    protected $zip;
    protected $street;
    protected $email;
    protected $password;
    protected $schriftwechsel;
    protected $birthday;
    protected $id;
    protected $country;
    protected $phonenumber;

    protected $_tableRow = array();

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
     * @param bool $__pimple
     */
    public function setContainer($__pimple = false)
    {

        if (empty($__pimple)) {
            return;
        }

        $this->_pimple = $__pimple;
    }

    /**
     * @param bool $__dateofbirth_day
     * @param bool $__dateofbirth_month
     * @param bool $__dateofbirth_year
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setBirthday($__dateofbirth_day = false, $__dateofbirth_month = false, $__dateofbirth_year = false)
    {
        if (!filter_var($__dateofbirth_day, FILTER_VALIDATE_INT) or (!filter_var(
                $__dateofbirth_month,
                FILTER_VALIDATE_INT
            )) or (!filter_var($__dateofbirth_year, FILTER_VALIDATE_INT))
        ) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['birthday'] = $__dateofbirth_day . "." . $__dateofbirth_month . "." . $__dateofbirth_year;

        return $this;
    }

    /**
     * @param $__city
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setCity($__city)
    {
        if (empty($__city)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['city'] = $__city;

        return $this;
    }

    /**
     * @param $__company
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setCompany($__company)
    {
        if (empty($__company)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['company'] = $__company;

        return $this;
    }

    /**
     * @param $__country
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setCountry($__country)
    {
        $test = 123;

        if (empty($__country)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['country'] = $__country;

        return $this;
    }

    /**
     * @param $__email
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setEmail($__email)
    {
        if (!filter_var($__email, FILTER_VALIDATE_EMAIL)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['email'] = $__email;

        return $this;
    }

    /**
     * Kontrolliert die Mailadresse
     *
     * @param $__email
     * @return bool
     */
    public function validateEmail($__email)
    {

        if (filter_var($__email, FILTER_VALIDATE_EMAIL)) {

            return $__email;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->_tableRow['email'];
    }

    /**
     * Vorname ist nicht Pflicht
     *
     * @param $__firstname
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setFirstname($__firstname = false)
    {
        if (empty($__firstname)) {
            $this->_tableRow['firstname'] = ' ';

            return $this;
        }

        $this->_tableRow['firstname'] = $__firstname;

        return $this;
    }

    /**
     * @param $__lastname
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setLastname($__lastname)
    {
        if (empty($__lastname)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['lastname'] = $__lastname;

        return $this;
    }

    /**
     * @param $rolleBenutzerId
     * @return Application_Model_Adressen
     */
    public function setRolleBenutzer($rolleBenutzerId){
        $rolleBenutzerId = (int) $rolleBenutzerId;
        if($rolleBenutzerId == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->_tableRow['status'] = $rolleBenutzerId;

        return $this;
    }

    /**
     * @param $offlinekunde
     * @return Application_Model_Adressen
     */
    public function setOfflinekunde($offlinekunde)
    {
        $offlinekunde = (int) $offlinekunde;
        if($offlinekunde == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->_tableRow['offlinekunde'] = $offlinekunde;

        return $this;
    }

    /**
     * Passwort wird verschlüsselt und gesalzen
     *
     * @param $__password
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setPassword($__password)
    {
        if (strlen($__password) < 8) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $__password = nook_ToolVerschluesselungPasswort::salzePasswort($__password);

        $this->_tableRow['password'] = $__password;

        return $this;
    }

    /**
     * Telefonnummer geschäftlich
     *
     * @param $__phonenumber
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setPhonenumber($__phonenumber)
    {
        if (empty($__phonenumber)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['phonenumber'] = $__phonenumber;

        return $this;
    }

    /**
     * Telefonnummer
     *
     * @param $__phonenumber1
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setPhonenumber1($__phonenumber1)
    {
        if (empty($__phonenumber1)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['phonenumber1'] = $__phonenumber1;

        return $this;
    }

    /**
     * @param $__schriftwechsel
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setSchriftwechsel($__schriftwechsel)
    {
        if (empty($__schriftwechsel)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['schriftwechsel'] = $__schriftwechsel;

        return $this;
    }

    /**
     * @param $__street
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setStreet($__street)
    {
        if (empty($__street)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['street'] = $__street;

        return $this;
    }

    /**
     * @param $__title
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setTitle($__title)
    {
        if (empty($__title)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['title'] = $__title;

        return $this;
    }

    /**
     * @param $__zip
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function setZip($__zip)
    {
        if (empty($__zip)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_tableRow['zip'] = $__zip;

        return $this;
    }

    /**
     * @return int
     * @throws nook_Exception
     */
    public function getId()
    {

        if (empty($this->_tableRow['id'])) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        return $this->_tableRow['id'];
    }

    /**
     * Kontrolliert ob Mailadresse nicht null
     *
     * @return int
     * @throws nook_Exception
     */
    public function checkDoppelteMailadresse()
    {
        if (empty($this->_tableRow['email'])) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $kontrolle = $this->_checkDoppelteMailadresse();

        return $kontrolle;
    }

    /**
     * Überprüft ob eine Mailadresse bereits vorhanden ist.
     * Wenn vorhanden dann 1
     * wenn nicht vorhanden dann 0
     *
     * @return int
     */
    private function _checkDoppelteMailadresse()
    {
        $kontrolle = 0;

        $where = "email = '" . $this->_tableRow['email'] . "'";

        /** @var $tabelleAdressen Zend_Db_Table */
        $tabelleAdressen = $this->_pimple['tabelleAdressen'];
        $select = $tabelleAdressen->select();
        $select
            ->from($tabelleAdressen, array( new Zend_Db_Expr("count(email) as anzahl") ))
            ->where($where);

        $rows = $tabelleAdressen->fetchAll($select)->toArray();

        if ($rows[0]['anzahl'] == 1) {
            $kontrolle = 1;
        }

        return $kontrolle;
    }

    /**
     * eintragen einer Adresse
     *
     * @return Application_Model_Adressen
     * @throws nook_Exception
     */
    public function insert()
    {

        $kontrolle = 0;
        foreach ($this->_tableRow as $inhalt) {
            if (!empty($inhalt)) {
                $kontrolle++;
            }
        }

        if ($kontrolle == 0) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_insert();

        return $this;
    }

    /**
     * eintragen Personendaten in Tabelle Adressen
     *
     * @return int
     */
    private function _insert()
    {
        /** @var $tabelleAdressen Zend_Db_Table */
        $tabelleAdressen = $this->_pimple['tabelleAdressen'];
        $userId = $tabelleAdressen->insert($this->_tableRow);

        $this->_tableRow['id'] = $userId;

        return $userId;
    }
}
<?php
/**
* Trägt die Personendaten des Benutzer ein. Update der Daten ist möglich.
*
* + Holt Personaldaten aus der Session
* + Speichern der Personendaten
* + speichern der Kunden Daten
* + Update der Kunden Daten in 'tbl_adressen'
* + Kontrolliert ob die Mailadresse
*
* @date 17.10.2013
* @file WarenkorbStep2.php
* @package front
* @subpackage model
*/
class Front_Model_WarenkorbStep2 extends nook_Model_model
{

    public $error_no_correct_personal_data = 50;
    public $error_no_identical_email = 51;
    public $error_no_identical_password = 52;
    public $error_database_no_update = 53;

    private $_data;

    private function _changeDateToGerman($__params)
    {
        $dateItems = explode('/', $__params['datum']);
        $__params['datum'] = $dateItems[0] . "." . $dateItems[1] . "." . $dateItems[2];

        return $__params;
    }

    public function dateofbirth_day($__dateofbirth_day = 0, $transfer = false)
    {
        $date = array( 0, 0, 0 );
        if (empty($transfer)) {
            if (!empty($__dateofbirth_day)) {
                $date = nook_Tool::splitGermanedate($__dateofbirth_day);
            }
        } else {
            $date[0] = $__dateofbirth_day;
        }

        $days = array();
        for ($i = 1; $i < 32; $i++) {
            $days[$i]['day'] = $i;
            $days[$i]['checked'] = 0;
            if ($date[0] == $i) {
                $days[$i]['checked'] = 1;
            }
        }

        return $days;
    }

    public function dateofbirth_month($__dateofbirth_month = 0, $transfer = false)
    {
        $date = array( 0, 0, 0 );
        if (empty($transfer)) {
            if (!empty($__dateofbirth_month)) {
                $date = nook_Tool::splitGermanedate($__dateofbirth_month);
            }
        } else {
            $date[1] = $__dateofbirth_month;
        }

        $months = array();
        for ($i = 1; $i < 13; $i++) {
            $months[$i]['month'] = $i;
            $months[$i]['checked'] = 0;
            if ($date[1] == $i) {
                $months[$i]['checked'] = 1;
            }
        }

        return $months;
    }

    public function dateofbirth_year($__dateofbirth_year = 0, $transfer = false)
    {
        $date = array( 0, 0, 0 );
        if (empty($transfer)) {
            if (!empty($__dateofbirth_year)) {
                $date = nook_Tool::splitGermanedate($__dateofbirth_year);
            }
        } else {
            $date[2] = $__dateofbirth_year;
        }

        $years = array();
        for ($i = 1920; $i < 2013; $i++) {
            $years[$i]['year'] = $i;
            $years[$i]['checked'] = 0;
            if (!empty($date[2]) and $date[2] == $i) {
                $years[$i]['checked'] = 1;
            }
        }

        return $years;
    }

    public function getTitle($__title)
    {
        $titles[0]['title'] = translate('Frau');
        $titles[1]['title'] = translate('Herr');

        for ($i = 0; $i < count($titles); $i++) {
            $titles[$i]['checked'] = 0;
            if ($titles[$i]['title'] == $__title) {
                $titles[$i]['checked'] = 1;
            }
        }

        return $titles;
    }

    public function findCountries($__country = 0)
    {
        $db = Zend_Registry::get('front');
        $sql = "SELECT
				    `id`, `Name`
				FROM
				    `tbl_countries`
				ORDER BY `id` ASC";

        $countries = $db->fetchAll($sql);

        for ($i = 0; $i < count($countries); $i++) {
            $countries[$i]['checked'] = 0;
            if ($__country == $countries[$i]['id']) {
                $countries[$i]['checked'] = 1;
            }
        }

        return $countries;
    }

    /**
     * Holt Personaldaten aus der Session
     *
     * @return array
     */
    public function getPersonalData()
    {
        $personalDataFilter = $this->_buildFilters();
        unset($personalDataFilter['password']);
        unset($personalDataFilter['password_repeat']);

        $sessionWarenkorb = new Zend_Session_Namespace('warenkorb');
        $sessionPersonaldata = (Array) $sessionWarenkorb->getIterator();

        $personalDaten = array();
        foreach ($personalDataFilter as $key => $value) {
            $personalDaten[$key] = '';
            if (array_key_exists($key, $sessionPersonaldata)) {
                $personalDaten[$key] = $sessionPersonaldata[$key];
            }
        }

        return $sessionPersonaldata;
    }

    /**
     * Speichern der Personendaten
     *
     * + eintragen Werte in Session['Auth']
     *
     * @throws nook_Exception
     * @param $__params
     * @return
     */
    public function setPersonaldata($__params)
    {

        foreach ($__params as $key => $value) {
            $this->_data[$key] = $value;
        }

        $this->_controlIdentical();
        $filters = $this->_buildFilters();
        $validators = $this->_buildValidators();

        $controlInput = new Zend_Filter_Input($filters, $validators, $__params);
        $errors = $controlInput->getErrors();

        if (!$controlInput->isValid()) {
            throw new nook_Exception($this->error_no_correct_personal_data);
        }

        // ermittelt KundenId aus Auth
        $auth = new Zend_Session_Namespace('Auth');
        $kundenId = $auth->userId;

        if (!empty($kundenId)) {
            $kundenId = $this->_updatePersonalData($kundenId);
        } else {
            $kundenId = $this->_savePersonaldata();
        }

        // trägt UserId in die Session / Auth ein
        $auth->userId = $kundenId;

        return $kundenId;
    }

    /**
     * speichern der Kunden Daten
     *
     * + salzen Passwort
     * + eintragen 'tbl_adressen'
     * + Session ID in 'tbl_buchungsnummer'
     *
     * @throws nook_Exception
     * @return
     */
    private function _savePersonaldata()
    {
        $db = Zend_Registry::get('front');

        unset($this->_data['email_repeat']);
        unset($this->_data['password_repeat']);

        // verschlüsseln des Passwortes
        $saltPasswort = nook_ToolStatic::berechnePasswort($this->_data['password']);
        $this->_data['password'] = $saltPasswort;

        // eintragen in 'tbl_adressen'
        $db->insert('tbl_adressen', $this->_data);
        $lastId = $db->lastInsertId();

        $update = array(
            'kunden_id' => $lastId
        );

        // erstellen der Buchungsnummer
        $control = $db->update('tbl_buchungsnummer', $update, "session_id = '" . Zend_Session::getId() . "'");
        if (!$control) {
            throw new nook_Exception($this->error_database_no_update);
        }

        return $lastId;
    }

    /**
     * Update der Kunden Daten in 'tbl_adressen'
     *
     * + update 'tbl_adressen'
     * + update 'tbl_buchungsnummer'
     *
     * @param $__kundenId
     * @return mixed
     */
    private function _updatePersonaldata($__kundenId)
    {
        $db = Zend_Registry::get('front');

        unset($this->_data['email_repeat']);
        unset($this->_data['password_repeat']);

        $db->update('tbl_adressen', $this->_data, "id = '" . $__kundenId . "'");

        $update = array(
            'kunden_id' => $__kundenId
        );

        $db->update('tbl_buchungsnummer', $update, "session_id = '" . Zend_Session::getId() . "'");

        return $__kundenId;
    }

    private function _controlIdentical()
    {
        $password = new Zend_Validate_Identical($this->_data['password']);
        if (!$password->isValid($this->_data['password_repeat'])) {
            throw new nook_Exception($this->error_no_identical_password);
        }

        $email = new Zend_Validate_Identical($this->_data['email']);
        if (!$email->isValid($this->_data['email_repeat'])) {
            throw new nook_Exception($this->error_no_identical_email);
        }

        return;
    }

    private function _buildFilters()
    {
        $filters = array(
            'title' => 'StringTrim',
            'firstname' => 'StringTrim',
            'lastname' => 'StringTrim',
            'company' => 'StringTrim',
            'street' => 'StringTrim',
            'additionalAdress' => 'StringTrim',
            'city' => 'StringTrim',
            'zip' => 'Int',
            'region' => 'StringTrim',
            'country' => 'StringTrim',
            'email' => 'StringTrim',
            'email_repeat' => 'StringTrim',
            'phonenumber' => 'StringTrim',
            'password' => 'StringTrim',
            'password_repeat' => 'StringTrim'
        );

        return $filters;
    }

    private function _buildValidators()
    {

        $validators = array(
            'lastname' => array(
                'presence' => 'required',
                'StringLength' => 2
            ),
            'street' => array(
                'presence' => 'required',
                'StringLength' => 2
            ),
            'city' => array(
                'presence' => 'required',
                'StringLength' => 2
            ),
            'zip' => array(
                'Int',
                'presence' => 'required'
            ),
            'email' => array(
                'EmailAddress',
                'presence' => 'required'
            ),
            'password' => array(
                'StringLength' => 2,
                'presence' => 'required',
            )
        );

        return $validators;
    }

    /**
     * Kontrolliert ob die Mailadresse
     * bereits vorhanden ist.
     * 'true' Mailadresse bereits vorhanden
     * 'false' Mailadresse noch nicht vorhanden
     *
     * @param $__email
     * @return bool
     */
    public function controlIfEmailIsDouble($__email)
    {

        $warenkorb = new Zend_Session_Namespace('warenkorb');
        if (($__email == $warenkorb->email) and $warenkorb->status > 1) {
            return false;
        }

        $db = Zend_Registry::get('front');

        $sql = "
			SELECT
			    count(email) as anzahl
			FROM
			    `tbl_adressen`
			WHERE (`email` = '" . $__email . "'
			    AND `status` <> 1);
		";

        $control = $db->fetchOne($sql);
        if ($control > 0) {
            return true;
        } else {
            return false;
        }
    }

}
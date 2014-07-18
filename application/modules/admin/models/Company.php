<?php
/**
* Model Datensatz
*
* + Ermittelt die Anzahl der Firman vom Typ Programmanbieter
* + Ermittelt die Firmen der Programmanbieter
* + Bestimmt die Anzahl der vorhandenen Firmen.
* + Setzt den Suchparameter der
* + Kontrolliert die ankommenden Daten
* + Kontrolliert die Formulareingabe
* + Legt einen neuen
* + Löscht eine vorhandene Firma
* + Holt die Personendaten aus der Tabelle
* + Existiert die Mailadresse schon ???
* + Update der Firmendaten
* + Gibt den aktiv / passiv Zustand
* + Verändert den aktiv / passiv Zustand
*
* @date 22.10.2013
* @file Company.php
* @package admin
* @subpackage model
*/
class Admin_Model_Company extends nook_Model_model
{
    private $_db;
    private $_db_front;
    private $_suchparameterFirma = null;

    public $error_mailadress_already_exists = 330;
    public $error_plz_ist_nicht_valid = 331;
    public $error_user_daten_nicht_valid = 332;
    public $error_filtern_fehlgeschlagen = 333;
    public $error_neue_firma_nicht_angelegt = 334;

    private $_condition_user_is_anbieter = 5;
    private $_condition_user_ist_programmanbieter = 1;

    /**
     *
     */
    public function __construct ()
    {
        $this->_db = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');

        return;
    }

    public function checkPlz ($__plz)
    {

        $options = array(
            'options' => array(
                'regexp' => "#([0-9]{4,6})#"
            )
        );

        if(!filter_var($__plz, FILTER_VALIDATE_REGEXP, $options)) {
            throw new nook_Exception($this->error_plz_ist_nicht_valid);
        }

        return;
    }

    public function getBundeslandEntsprechendPlz ($__plz)
    {
        $themaPlz = nook_plz::getInstance($this->_db_front);
        return $themaPlz->getBundeslandEntsprechendPlz($__plz);
    }

    /**
     * Ermittelt die Anzahl der Firman vom Typ Programmanbieter
     *
     * @return mixed
     */
    private function _getCountCompaniesProgrammanbieter ()
    {
        $sql = "SELECT COUNT(id) AS anzahl FROM tbl_adressen WHERE anbieter = " . $this->_condition_user_ist_programmanbieter;

        if(!empty($this->_suchparameterFirma)) {
            $sql .= " and company like '%" . $this->_suchparameterFirma . "%'";
        }

        $anzahl = $this->_db_front->fetchOne($sql);

        return $anzahl;
    }

    /**
     * Ermittelt die Firmen der Programmanbieter
     * Verwendet einen Paginator
     *
     * @param $__params
     * @return mixed
     */
    private function _getCompaniesProgrammanbieter ($__params)
    {
        $start = 0;
        $limit = 20;

        if(array_key_exists('limit', $__params)) {
            $start = $__params[ 'start' ];
            $limit = $__params[ 'limit' ];
        }

        $sql = "SELECT id, company AS company_name, aktiv FROM `tbl_adressen` WHERE anbieter =  " . $this->_condition_user_ist_programmanbieter . " and company is not null";

        if(!empty($this->_suchparameterFirma)) {
            $sql .= " and company like '%" . $this->_suchparameterFirma . "%'";
        }

        $sql .= " ORDER BY  aktiv, company limit " . $start . "," . $limit;

        $programmanbieter = $this->_db_front->fetchAll($sql);

        return $programmanbieter;
    }

    /**
     * Bestimmt die Anzahl der vorhandenen Firmen.
     * Holt die Datensätze der Firmen.
     *
     * @param $__params
     * @return array
     */
    public function getCompanies ($__params)
    {

        $programmanbieter = array();

        // setzen Suchparameter
        if(array_key_exists('sucheFirma', $__params)) {
            $this->_setSuchparameterFirma($__params['sucheFirma']);
        }

        // Anzahl der Firmen
        $programmanbieter[ 'anzahl' ] = $this->_getCountCompaniesProgrammanbieter();

        // Datensätze der Firmen
        $programmanbieter[ 'data' ] = $this->_getCompaniesProgrammanbieter($__params);

        return $programmanbieter;
    }

    /**
     * Setzt den Suchparameter der
     * Firmensuche
     *
     * @param $suchParameter
     */
    private function _setSuchparameterFirma ($suchParameter)
    {
        $this->_suchparameterFirma = $suchParameter;

        return;
    }

    /**
     * Kontrolliert die ankommenden Daten
     *
     * @param $__userData
     * @return $this
     * @throws nook_Exception
     */
    public function checkUserData ($__userData)
    {
        if(empty($__userData[ 'kundeId' ])) {
            if(empty($__userData[ 'company_name' ])) {
                throw new nook_Exception($this->error_user_daten_nicht_valid);
            }
        }

        $this->checkPlz($__userData[ 'zip' ]);

        if(empty($__userData[ 'city' ]) or empty($__userData[ 'street' ])) {
            throw new nook_Exception($this->error_user_daten_nicht_valid);
        }

        if(empty($__userData[ 'password' ]) or strlen($__userData[ 'password' ]) < 8) {
            throw new nook_Exception($this->error_user_daten_nicht_valid);
        }

        if(empty($__userData[ 'phonenumber' ])) {
            throw new nook_Exception($this->error_user_daten_nicht_valid);
        }

        $kontrollArray = array(
            'email'      => array(
                'filter' => FILTER_VALIDATE_EMAIL
            ),
            'newsletter' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'aktiv'      => array(
                'filter' => FILTER_VALIDATE_INT
            )
        );

        $kontrolle = filter_var_array($__userData, $kontrollArray);
        $this->_checkKontrollErgebnis($kontrolle);

        return $this;
    }

    /**
     * Kontrolliert die Formulareingabe
     *
     * @param $__kontrolle
     * @throws nook_Exception
     */
    private function _checkKontrollErgebnis ($__kontrolle)
    {

        foreach($__kontrolle as $feldname => $feldinhalt) {
            if($feldinhalt == false) {
                throw new nook_Exception($this->error_filtern_fehlgeschlagen);
            }
        }

        return;
    }

    /**
     * Legt einen neuen
     * Programmanbieter an
     *
     * @param $__datenFirma
     * @return array
     */
    public function insertNewCompany ($__datenFirma)
    {

        // Kontrolle vorhandene Mailadresse
        $kontrolle = $this->checkExistMailAdress($__datenFirma[ 'email' ]);

        if(is_array($kontrolle)) {
            return $kontrolle;
        }

        // Fehlermeldung wenn Mailadresse schon vorhanden
        if($kontrolle === true) {
            $errors = array();
            $errors[ 0 ][ 'id' ] = 'email';
            $errors[ 0 ][ 'msg' ] = 'Mailadresse wird schon verwendet !';

            return $errors;
        }

        unset($__datenFirma[ 'kundeId' ]);
        $__datenFirma[ 'company' ] = $__datenFirma[ 'company_name' ];
        unset($__datenFirma[ 'company_name' ]);
        $__datenFirma['country'] = $__datenFirma['countryId'];
        unset($__datenFirma['countryId']);
        $__datenFirma[ 'anbieter' ] = $this->_condition_user_ist_programmanbieter;

        $this->_db_front->insert('tbl_adressen', $__datenFirma);

        return;
    }

    /**
     * Löscht eine vorhandene Firma
     * aus der Tabelle 'tbl_adressen'
     *
     * @param $__companyId
     */
    public function loescheFirma ($__companyId)
    {
        $this->_db_front->delete('tbl_adressen', 'id = ' . $__companyId);

        return;
    }

    /**
     * Holt die Personendaten aus der Tabelle
     * 'tbl_adressen'
     *
     * @param $__companyId
     * @return mixed
     */
    public function getPersonalDataFromCompany ($__companyId)
    {

        $sql = "
            SELECT
                *
            FROM
                tbl_adressen where id = " . $__companyId;

        $personalData = $this->_db_front->fetchRow($sql);
        $personalData[ 'company_name' ] = $personalData[ 'company' ];
        unset($personalData[ 'company' ]);
        unset($personalData[ 'password' ]);
        $personalData[ 'kundeId' ] = $personalData[ 'id' ];

        return $personalData;
    }

    public function kontrolleExistiertDieAngegebeneStadt ($__cityName)
    {
        $city = nook_city::getInstance($this->_db_front);
        $errors = $city->setCityName($__cityName)->getLastInsertId();

        return $errors;
    }

    /**
     * Existiert die Mailadresse schon ???
     *
     * @param $__email
     * @return array|bool
     */
    public function checkExistMailAdress ($__email)
    {
        $sql = "select count(id) from tbl_adressen where email = '" . $__email . "'";
        $anzahl = $this->_db_front->fetchOne($sql);

        if($anzahl == 1) {
            return true;
        } elseif($anzahl > 1) {
            $errors = array();
            $errors[ 0 ][ 'id' ] = 'email';
            $errors[ 0 ][ 'msg' ] = 'Mailadresse wird schon verwendet !';

            return $errors;
        } elseif($anzahl == 0) {
            return false;
        }
    }

    /**
     * Update der Firmendaten
     *
     * @param $__userData
     */
    public function updateUserData ($__userData)
    {

        $company = array();
        $company[ 'anbieter' ] = $this->_condition_user_ist_programmanbieter;
        $company[ 'company' ] = $__userData[ 'company_name' ];
        $company[ 'zusatz' ] = $__userData[ 'zusatz' ];
        $company[ 'title' ] = $__userData[ 'title' ];
        $company[ 'firstname' ] = $__userData[ 'firstname' ];
        $company[ 'lastname' ] = $__userData[ 'lastname' ];
        $company[ 'street' ] = $__userData[ 'street' ];
        $company[ 'city' ] = $__userData[ 'city' ];
        $company[ 'email' ] = $__userData[ 'email' ];
        $company[ 'zip' ] = $__userData[ 'zip' ];
        $company[ 'region' ] = $__userData[ 'region' ];
        $company[ 'status' ] = $this->_condition_user_is_anbieter;
        $company[ 'phonenumber' ] = $__userData[ 'phonenumber' ];
        $company[ 'mobile' ] = $__userData[ 'mobile' ];
        $company[ 'zusatzinformation' ] = $__userData[ 'zusatzinformation' ];

        if(!empty($company[ 'password' ])) {
            $company[ 'password' ] = nook_ToolVerschluesselungPasswort::salzePasswort($__userData[ 'password' ]);
        }

        $company[ 'aktiv' ] = $__userData[ 'aktiv' ];
        $company[ 'newsletter' ] = $__userData[ 'newsletter' ];
        $company[ 'country' ] = $__userData['countryId'];
        $company[ 'anbieter' ] = $this->_condition_user_ist_programmanbieter;

        $this->_db_front->update('tbl_adressen', $company, "id = " . $__userData[ 'companyId' ]);

        return;
    }

    /**
     * Gibt den aktiv / passiv Zustand
     * einer Programmanbieter Firma zurück
     *
     * @param $__params
     * @return mixed
     */
    public function getActivDataCompany ($__params)
    {

        $sql = "select id as companyId, aktiv, company as companyName from tbl_adressen where id = " . $__params[ 'companyId' ];
        $companyData = $this->_db_front->fetchRow($sql);

        return $companyData;
    }

    /**
     * Verändert den aktiv / passiv Zustand
     * einer Programmanbieter Firma
     *
     * @param $__params
     * @return bool
     */
    public function setActivDataCompany ($__params)
    {
        $update = array();
        $update[ 'aktiv' ] = $__params[ 'aktiv' ];

        $this->_db_front->update('tbl_adressen', $update, 'id = ' . $__params[ 'companyId' ]);

        return true;
    }

}
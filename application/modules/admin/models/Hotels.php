<?php
class Admin_Model_Hotels extends nook_Model_model{

    protected $_suchparameterHotelbeschreibung = array();
    protected $_suchParameterNameHotel = null;

    // Datenbanken, Tabellen, Views
    private $_db = null;
    private $_db_front = null;

    private $_tabelleAdressen = null;
    private $_tabelleAoCity = null;
    private $_tabellePropertyDetails = null;

    // Errors
	public $error_no_hotel_insert = 260;
	public $error_no_hotel_details_insert = 261;
	public $error_no_hotel_executiv_insert = 262;
    public $error_no_correct_params_hotel_description = 263;
    public $error_keine_stadt_vorhanden = 264;

    // Konditionen
	public $condition_status_anbieter = 5;
    public $condition_status_redakteur = 7;
    
	public $condition_status_anbieter_beherbergung = 6;

    public $condition_keine_ueberbuchung = 1;
    public $condition_ueberbuchung = 2;

    public $condition_neu_angelegtes_hotel = 'neu';
    public $condition_hotel_neu_angelegt = 1;
    public $condition_hotel_wurde_geupdatet = 2;
    public $codition_hotel_wurde_nicht_geupdatet = 3;

    public $condition_produkt_aktiv = 3;
    public $condition_is_basisproduct = 2;
	
	public function __construct(){
        // Datenbanken
		$this->_db = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');

        // Tabellen
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
        /** @var _tabelleAoCity Application_Model_DbTable_aoCity */
        $this->_tabelleAoCity = new Application_Model_DbTable_aoCity();
        /** @var _tabellePropertyDetails  Application_Model_DbTable_propertyDetails */
		$this->_tabellePropertyDetails = new Application_Model_DbTable_propertyDetails(array('db' => 'hotels'));

		return;
	}

    public function checkParameterHotelbeschreibung($__parameterHotelbeschreibung){

        $kontrollArray = array(
            'hotelId' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'sprache' => array(
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => array(
                    'regexp' => "#^([a-zA-Z]{2,2})$#"
                )
            )
        );

        $parameter = filter_var_array($__parameterHotelbeschreibung, $kontrollArray);
        $kontrolle = nook_Tool::kontrolleEingabeparameter($parameter);
        if(!$kontrolle)
            throw new nook_Exception($this->error_no_correct_params_hotel_description);

        $auth = new Zend_Session_Namespace('Auth');
        if($auth->role_id <= $this->condition_status_anbieter){
            if($auth->company_id != $__parameterHotelbeschreibung['hotelId'])
                throw new nook_Exception($this->error_no_correct_params_hotel_description);
        }

        return;
    }

    public function setParameterHotelbeschreibung($__parameterZurSucheDerHotelbeschreibung){
        $this->_suchparameterHotelbeschreibung = $__parameterZurSucheDerHotelbeschreibung;

        return $this;
    }

    public function getHotelbeschreibung(){

        $sql = "
            SELECT
                `tbl_properties`.`property_name` AS `ueberschrift`
                , `tbl_property_details`.`description_".$this->_suchparameterHotelbeschreibung['sprache']."` as hotelbeschreibung
            FROM
                `tbl_properties`
                INNER JOIN `tbl_property_details`
                    ON (`tbl_properties`.`id` = `tbl_property_details`.`properties_id`)
            WHERE (`tbl_properties`.`id` = ".$this->_suchparameterHotelbeschreibung['hotelId'].")";

        $hotelBeschreibung = $this->_db->fetchRow($sql);

        return $hotelBeschreibung;
    }

    public function getHoteldescription($__params){

        if($__params['sprache'] == 'de'){
            $sql = "SELECT
                    `description_de` AS `txt`
                    , `ueberschrift`
                FROM
                    `tbl_property_details`
                WHERE (`properties_id` = ".$__params['hotelId'].")";
        }
        elseif($__params['sprache'] == 'en'){
             $sql = "SELECT
                    `description_en` AS `txt`
                    , `ueberschrift`
                FROM
                    `tbl_property_details`
                WHERE (`properties_id` = ".$__params['hotelId'].")";
        }

        $row = $this->_db->fetchRow($sql);

        return $row;
    }

    public function updateHotelDescription($__editorParameter){
        // Sonderzeichen
        $__editorParameter['txt'] = str_replace("'", "", $__editorParameter['txt']);
        $__editorParameter['txt'] = str_replace("\"", "", $__editorParameter['txt']);

        $update = array();
        $update['ueberschrift'] = $__editorParameter['ueberschrift'];

        if($__editorParameter['sprache'] == 'de')
            $update['description_de'] = $__editorParameter['txt'];
        elseif($__editorParameter['sprache'] == 'en')
            $update['description_en'] = $__editorParameter['txt'];

        $control = $this->_db->update('tbl_property_details', $update, "properties_id = ".$__editorParameter['hotelId']);

        return;
    }

    /**
     * Ermittelt die Anzahl der Hotels
     * unter Berücksichtigung eines
     * Suchbegriffes
     *
     * @return mixed
     */
    public function getCountPrograms(){

        $listeDerHotels = $this->_checkListeDerHotels();

		$sql = "select count(id) from tbl_properties";

        if(!empty($listeDerHotels) or !empty($this->_suchParameterNameHotel))
            $sql .= " where";

        if(!empty($listeDerHotels))
            $sql .= " id IN (".$listeDerHotels.") and";

        if(!empty($this->_suchParameterNameHotel))
            $sql .= " property_name like '%".$this->_suchParameterNameHotel."%' and";

        if(!empty($listeDerHotels) or !empty($this->_suchParameterNameHotel))
            $sql = substr($sql, 0, -4);

		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}

    /**
     * Übernimmt den Suchparameter der
     * Hotelsuche
     *
     * @param $__suchParameterHotelname
     * @return Admin_Model_Hotels
     */
    public function setSuchparameterHotel($__suchParameterHotelname){

        if(!empty($__suchParameterHotelname))
            $this->_suchParameterNameHotel = $__suchParameterHotelname;

        return $this;
    }

    /**
     * Listet die vorhandenen Hotels
     * für die Tabelle auf
     *
     * @param bool $__start
     * @param bool $__limit
     * @return mixed
     */
    public function getTableItems($__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

        $listeDerHotels = $this->_checkListeDerHotels();

		$sql = "select * from tbl_properties";

        if(!empty($listeDerHotels) or !empty($this->_suchParameterNameHotel))
            $sql .= " where";

        if(!empty($listeDerHotels))
            $sql .= " id IN (".$listeDerHotels.") and";

        if(!empty($this->_suchParameterNameHotel))
            $sql .= " property_name like '%".$this->_suchParameterNameHotel."%' and";

        if(!empty($listeDerHotels) or !empty($this->_suchParameterNameHotel))
            $sql = substr($sql, 0, -4);
        
        $sql .= " order by aktiv , property_name asc";
		$sql .= " limit ".$start.",".$limit;
		
		$result = $this->_db->fetchAll($sql);
		return $result;		
	}

    /**
     * Überprüft ob der User Zugriff auf die Hotels hat
     * Gibt eine Liste der Hotels des Users zurück
     *
     * @return bool
     */
    private function _checkListeDerHotels(){
        $zugriffAufHotels = new nook_ZugriffAufHotels();
            $stringListeDerHotels = $zugriffAufHotels
                ->setKundenDaten()
                ->getStringHotels();

        if($zugriffAufHotels->alleHotels == true)
            return false;
        else
            return $stringListeDerHotels;
    }

	public function buildNewHotel(){
		$insert = array();
		$insert['property_name'] = 'neu';
		$insert['property_code'] = 'neu';
		$insert['aktiv'] = $this->condition_hotel_neu_angelegt;
		
		$control = $this->_db->insert('tbl_properties', $insert);
		if($control != 1)
			throw new nook_Exception($this->error_no_hotel_insert);
			
		$lastInsertId = $this->_db->lastInsertid($this->error_no_hotel_details_insert);
		
		$insertDetails = array();
		$insertDetails['properties_id'] = $lastInsertId;
		$control = $this->_db->insert('tbl_property_details', $insertDetails);
		if($control != 1)
			throw new nook_Exception($this->error_no_hotel_details_insert);

		$insertKunde = array();
		$insertKunde['properties_id'] = $lastInsertId;
		$insertKunde['status'] = $this->condition_status_anbieter;
		$insertKunde['anbieter'] = $this->condition_status_anbieter_beherbergung;
		
		$control = $this->_db_front->insert('tbl_adressen', $insertKunde);
		if($control != 1)
			throw new nook_Exception($this->error_no_hotel_executiv_insert);

        // eintragen der Basisprodukte eines Hotels
        $this->_setBasicProducts($lastInsertId);
		
		return $lastInsertId;
	}



    /**
     * Definition des touristischen Standard.
     * Für jedes Hotel werden die touristischen Grundleistungen eingetragen.
     *
     * + touristische Grundleistung = 2
     * + keine touristische Grundleistung = 1
     *
     *
     * @param $__hotelId
     */
    private function _setBasicProducts($__hotelId){

        $sql = "select product_name, product_name_en from tbl_products_basic";
        $basicProducts = $this->_db->fetchAll($sql);

        foreach($basicProducts as $key => $basicProduct){
            $basicProduct['property_id'] = $__hotelId;
            $basicProduct['aktiv'] = $this->condition_produkt_aktiv;
            $basicProduct['standardProduct'] = $this->condition_is_basisproduct;

            $this->_db->insert('tbl_products', $basicProduct);
        }

        return;
    }



    /**
     * Findet die in Deutschland vorhandenen
     * Bundesländer
     *
     * @return mixed
     */
    public function getCountryRegions(){
        $sql = "select bundesland as region from tbl_bundeslaender order by bundesland";

		$countryRegions = $this->_db_front->fetchAll($sql);
		
		return $countryRegions;
	}
	
	public function getCountryCities(){
		$sql = "select AO_City as city, AO_City_ID as cityId from tbl_ao_city order by AO_City";
		$countryCities = $this->_db_front->fetchAll($sql);
		
		return $countryCities;
	}
	
	public function getPersonalDataHotel($__hotelId){
		$sql = "select * from tbl_adressen where properties_id = '".$__hotelId."' and anbieter = '".$this->condition_status_anbieter_beherbergung."'";
		$dbFront = Zend_Registry::get('front');
		$personalData = $dbFront->fetchRow($sql);

        unset($personalData['password']);
        unset($personalData['country']);
        unset($personalData['city']);
        unset($personalData['region']);
        unset($personalData['title']);
        unset($personalData['newsletter']);

        return $personalData;
	}

    /**
     * Speichert die Personendaten des Hotelverantwortlichen
     *
     * @param $__params
     * @return array
     */
    public function savePersonalDataFromHotel($__params){
		$errors = array();
		
		$companyId = $__params['hotelId'];
		unset($__params['hotelId']);
		
		if(empty($__params['password']) or empty($__params['email'])){
			$errors[0]['id'] = 'email';
			$errors[0]['msg'] = 'Bitte korrigieren';
			
			$errors[1]['id'] = 'password';
			$errors[1]['msg'] = 'Bitte korrigieren';
			
			return $errors;
		}
		else{
			$controlMailAdress = $this->_controlMailAdress($__params['email'], $companyId);
			if(empty($controlMailAdress)){
				$errors[0]['id'] = 'email';
				$errors[0]['msg'] = 'Bitte andere Mailadresse verwenden';
				
				return $errors;
			}
		}
		
		unset($__params['module']);
		unset($__params['controller']);
		unset($__params['action']);

		if($__params['newsletter'] == 'aktiv')
            $__params['newsletter'] = 2;
        else
            $__params['newsletter'] = 1;
        
        $__params['anbieter'] = $this->condition_status_anbieter_beherbergung;
        $__params['status'] = $this->condition_status_anbieter;


        // Name der Stadt
        $__params['city'] = nook_ToolStadt::getStadtNameMitStadtId($__params['cityId']);
        unset($__params['cityId']);

        // Name der Firma
        $toolHotel = new nook_ToolHotel();
        $__params['company'] = $toolHotel->getHotelName($companyId);

        // Straße und Hausnummer kombinieren
        $__params['street'] = $__params['street'];

        // verschlüsseln Passwort
        $__params['password'] = nook_ToolVerschluesselungPasswort::salzePasswort($__params['password']);

        /*** eintragen in 'tbl_adressen' ***/
        $where = "properties_id = '".$companyId."'";
        $this->_tabelleAdressen->update($__params,$where);

        /*** eintragen in 'tbl_property_details' ***/
		$updatePropertyDetails = array();
        // ermittelt aus der Länder-ID den Landesnamen
        $toolLand = new nook_ToolLand();
        $updatePropertyDetails['country'] = $toolLand->convertLaenderIdNachLandName($__params['country']);

		$updatePropertyDetails['city'] = $__params['city'];
        $where = "properties_id = '".$companyId."'";

        $this->_tabellePropertyDetails->update($updatePropertyDetails, $where);

		return $errors;
	}

    private function _findIdFromCity($__cityName){
        $sql = "select AO_City_ID from tbl_ao_city where AO_City = '".$__cityName."'";
        $cityId = $this->_db_front->fetchOne($sql);

        return $cityId;
    }

	private function _controlMailAdress($__mailAdress, $__companyId){
		$control = true;
		
		$sql = "select count(email) as anzahl from tbl_adressen where properties_id <> '".$__companyId."' and email = '".$__mailAdress."'";
		$dbFront = Zend_Registry::get('front');
		$anzahl = $dbFront->fetchOne($sql);
		if($anzahl != 0)
			$control = false;
		
		return $control;
	}
}
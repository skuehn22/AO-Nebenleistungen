<?php
class Front_Model_Warenkorb extends nook_Model_model{
	

    // Fehler
    public $error_no_correct_data_entry = 40;
	public $error_no_correct_price = 41;
	public $error_no_correct_startDate = 42;
	public $error_no_correct_booking_deadline = 43;
	public $error_no_correct_season_start = 44;
	public $error_no_correct_season_end = 45;
	public $error_no_correct_database_connection = 46;
	public $error_no_integer_step = 47;
	public $error_offer_not_delete = 48;
	public $error_to_many_booking_sessions = 49;

    // Konditionen
	public $condition_day_in_seconds = 86400;
	public $condition_is_offer = 1;
    public $condition_noch_nicht_gebucht = 1;
    public $condition_bereich_hotel = 6;
    public $condition_bereich_programm = 1;

    // Flags

    // Tabellen / Views / Datenbanken
	
	private $_data = array();
	private $_language;
	private $_totalPrice = 0;
	
	private function _changeDateToGerman($__params){
		$dateItems = explode('/', $__params['datum']);
		$__params['datum'] = $dateItems[0].".".$dateItems[1].".".$dateItems[2];
		
		return $__params;
	}
	
	public function saveOrder(){
		$bookingNumber = $this->_setOrFindBookingNumber();
		
		$db = Zend_Registry::get('front');
		$buchung = array(
			'fa_id' => $this->_data['ProgrammId'],
			'datum' => $this->_data['datum'],
			'zeit' => $this->_data['stunde'].':'.$this->_data['minute'],
			'sprache' => $this->_data['languages'],
			'persons' => $this->_data['persons'],
			'price_persons' => $this->_data['pricePersons'],
			'buchungsnummer_id' => $bookingNumber,
			'condition' => $this->condition_is_offer
		);
		
		$control = $db->insert('tbl_buchung', $buchung);
		if($control != 1)
			throw new nook_Exception($this->error_no_correct_database_connection);
		
	}
	
	private function _setOrFindBookingNumber(){
		$db = Zend_Registry::get('front');
		$sessionId = Zend_Session::getId();
		
		$sql = "select count(id) from tbl_buchungsnummer where session_id = '".$sessionId."'";
		$count = $db->fetchOne($sql);
		if($count > 1)
			throw new nook_Exception($this->error_to_many_booking_sessions);
		
		if($count == 0){
			$insert = array(
				'session_id' => $sessionId 
			);
			
			$control = $db->insert('tbl_buchungsnummer', $insert);
			if($control != 1)
				throw new nook_Exception($this->error_no_correct_database_connection);
				
			$bookingNumber = $db->lastInsertId();
		}
		else{
			$sql = "select id from tbl_buchungsnummer where session_id = '".$sessionId."'";
			$bookingNumber = $db->fetchOne($sql);
		}
		
		return $bookingNumber;
	}
	
	public function getBookingNumber(){
		$db = Zend_Registry::get('front');
		
		$sql = "
			SELECT
		    	`tbl_buchungsnummer`.`id` AS `Buchungsnummer`
			FROM
			    `tbl_buchungsnummer`
			    INNER JOIN `tbl_buchung`
			        ON (`tbl_buchungsnummer`.`id` = `tbl_buchung`.`buchungsnummer_id`)
			WHERE (`tbl_buchungsnummer`.`session_id` = '".Zend_Session::getId()."')
			HAVING (count(`tbl_buchung`.`fa_id`) > 0)";
		
		$bookingNumber = $db->fetchOne($sql);
		
		return $bookingNumber;
	}
	
	public function setOfferDelete($__deleteOffer){
		$control = new Zend_Validate_Int();
		if(!$control->isValid($__deleteOffer))
			throw new nook_Exception($this->error_offer_not_delete);
		
		$db = Zend_Registry::get('front');
		$sql = "
			SELECT
			    count(`tbl_buchungsnummer`.`session_id`)
			FROM
			    `tbl_buchungsnummer`
			    INNER JOIN `tbl_buchung`
			        ON (`tbl_buchungsnummer`.`id` = `tbl_buchung`.`buchungsnummer_id`)
			WHERE (`tbl_buchungsnummer`.`session_id` = '".Zend_Session::getId()."'
			    AND `tbl_buchung`.`id` = '".$__deleteOffer."')";
		
		$control = $db->fetchOne($sql);
		if($control != 1)
			throw new nook_Exception($this->error_offer_not_delete);
		
		$where = array(
			"id = '".$__deleteOffer."'"
		);
		
		$control = $db->delete('tbl_buchung',$where);
		if($control != 1)
			throw new nook_Exception($this->error_offer_not_delete);
		
		return;
	}
	
	public function getTotalPrice(){
		
		return $this->_totalPrice;
	}
	
	public function setOrderItems($__params){
		$this->_language = Zend_Registry::get('language');
		
		if($this->_language != 'de')
			$__params = $this->_changeDateToGerman($__params);
		
		$filters = $this->_buildFilters();
		$validators = $this->_buildValidators();
		
		$controlInput = new Zend_Filter_Input($filters, $validators, $__params);
		if($controlInput->isValid())
			$this->_insertOrderItems($__params);
		else
			throw new nook_Exception($this->error_no_correct_data_entry);

		$controlDetails = $this->_controlDetails();
		$this->_controlPrice($controlDetails);
		$this->_controlData($controlDetails);
		
		

		return;
	}
	
	public function getShoppingCart(){
		$shoppingCart = $this->_findOrdersBySessionId();
		
		if(count($shoppingCart) > 0){
			$shoppingCart = nook_Tool::trimLongText($shoppingCart);
			$shoppingCart = $this->_offerPrice($shoppingCart);
			
			for($i=0; $i < count($shoppingCart); $i++){
				$shoppingCart[$i] = nook_ToolProgrammbilder::findImageFromProgram($shoppingCart[$i], 'mini');
			}
		}
		
		return $shoppingCart;
	}
	
	public function setAktiveStep($__step){
		$int = new Zend_Validate_Int();
		if(!$int->isValid($__step))
			throw new nook_Exception($this->error_no_integer_step);
		
		$aktiveStep = array();
		for($i=1; $i< 5; $i++){
			$aktiveStep['aktiveStep'.$i] = '';
			
			if($i == $__step)
				$aktiveStep['aktiveStep'.$i] = 'aktiveStep';
		}
		
		return $aktiveStep;
	}
	
	private function _offerPrice($__shoppingCart){
		for($i=0; $i < count($__shoppingCart); $i++){
			$offerprice = $__shoppingCart[$i]['price_persons'] * $__shoppingCart[$i]['persons'];
			$offerprice = number_format($offerprice, 2);
			$__shoppingCart[$i]['offerPrice'] = $offerprice;
			
			$this->_totalPrice += $offerprice;
		}
		
		return $__shoppingCart;
	}
	
	private function _findOrdersBySessionId(){
		$db = Zend_Registry::get('front');
		$language = nook_Tool::findLanguage();

		$sql = "
			SELECT
			    `tbl_buchungsnummer`.`session_id`
			    , `tbl_buchung`.`datum`
			    , `tbl_buchung`.`id`
			    , `tbl_buchung`.`fa_id`
			    , `tbl_buchung`.`zeit`
			    , `tbl_buchung`.`sprache` AS `Programmsprache`
			    , `tbl_buchung`.`treffpunkt`
			    , `tbl_buchung`.`persons`
			    , `tbl_buchung`.`price_persons`
			    , `tbl_prog_sprache`.`flag`
			    , `tbl_programmbeschreibung`.`progname`
			    , `tbl_programmbeschreibung`.`noko_kurz`
			    , `tbl_programmbeschreibung`.`sprache`
			    , `tbl_programmbeschreibung`.`txt`
			    , `tbl_programmdetails`.`dauer`
			    , `tbl_programmdetails`.`AO_City` as city
			    , `tbl_programmdetails`.`dauer`
			    , `tbl_AdressenFA`.`Ort`
			FROM
			    `tbl_buchungsnummer`
			    INNER JOIN `tbl_buchung`
			        ON (`tbl_buchungsnummer`.`id` = `tbl_buchung`.`buchungsnummer_id`)
			    INNER JOIN `tbl_prog_sprache` 
			        ON (`tbl_buchung`.`sprache` = `tbl_prog_sprache`.`id`)
			    INNER JOIN `tbl_programmbeschreibung`
			        ON (`tbl_buchung`.`fa_id` = `tbl_programmbeschreibung`.`Fa_Id`)
			    INNER JOIN `tbl_programmdetails`
			        ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_programmdetails`.`Fa_ID`)
			    INNER JOIN `tbl_AdressenFA` 
			        ON (`tbl_programmdetails`.`Fa_ID` = `tbl_AdressenFA`.`Fa_ID`)
			WHERE (`tbl_buchungsnummer`.`session_id` = '".Zend_Session::getId()."'
			    AND `tbl_programmbeschreibung`.`sprache` = '".$language."')";
		
		$shoppingCart = $db->fetchAll($sql);
		
		return $shoppingCart;
	}
	
	private function _buildFilters(){
		$filters = array(
			'ProgrammId' => 'Int',
			'datum' => 'StringTrim',
			'stunde' => 'StringTrim',
			'minute' => 'StringTrim',
			'pricePersons' => 'StringTrim',
			'languages' => 'StringTrim',
			'persons' => 'Int'
		);
		
		return $filters;
	}
	
	private function _buildValidators(){
		
		$validateDate = new Zend_Validate_Date(array('locale' => $this->_language));
		$validateFloat = new Zend_Validate_Float(array('locale' => 'en'));
		
		$validators = array(
			'ProgrammId' => 'Int',
			'datum' => $validateDate,
			'stunde' => 'Int',
			'minute' => 'Int',
			'pricePersons' => $validateFloat,
			'languages' => 'Int',
			'persons' => 'Int'
		);
		
		return $validators;
	}
	
	private function _insertOrderItems($__params){
		foreach($__params as $key => $value){
			$this->_data[$key] = $value;
		}
		
		return;
	}
	
	private function _controlDetails(){
		$sql = "
			SELECT
		    `vk` AS `Verkaufspreis`
		    , `mwst_satz` AS `Mehrwertsteuer`
		    , `buchungsfrist` AS `Buchungsfrist`
		    , `valid_from` AS `Saisonbeginn`
		    , `valid_thru` AS `Saisonende`
		    , `bjl_prio` AS `sichtbar`
		    , `Fa_ID`
		FROM
		    `tbl_programmdetails`
		WHERE (`Fa_ID` = '".$this->_data['ProgrammId']."')";
		
		$db = Zend_Registry::get('front');
		$controlDetails = $db->fetchRow($sql);
		
		
		return $controlDetails;
	}
	
	private function _controlPrice($__controlDetails){
		$__controlDetails = nook_Tool::addVat($__controlDetails);
		if($this->_data['pricePersons'] != $__controlDetails['Verkaufspreis'])
			throw new nook_Exception($this->error_no_correct_price);
		
		return;
	}
	
	private function _controlData($__controlDetails){
		$buchungsdatum = nook_Tool::buildTime($this->_data['datum']);
		$startDate = nook_Tool::buildTimeFromCompleteDate($__controlDetails['Saisonbeginn']);
		$endDate = nook_Tool::buildTimeFromCompleteDate($__controlDetails['Saisonende']);
		$nowDate = mktime(0,0,0);
		
		$buchungsfrist = $nowDate + ($this->condition_day_in_seconds * $__controlDetails['Buchungsfrist']);
		
		if($buchungsfrist > $buchungsdatum)
			throw new nook_Exception($this->error_no_correct_booking_deadline);
		if(!empty($startDate) and $startDate > $buchungsdatum)
			throw new nook_Exception($this->error_no_correct_season_start);
		if(!empty($endDate) and $endDate < $buchungsdatum)
			throw new nook_Exception($this->error_no_correct_season_end);
		
		return;
	}

    /**
     * Löschen aller Raten die sich im Warenkorb befinden.
     * Auswahl der zu löschenden Raten mittels SessionId oder KundenID.
     *
     * @return void
     */
    public function deleteAllHotelRates(){
        // ist eine Kunden ID vorhanden
        $auth = new Zend_Session_Namespace('Auth');
        $userAuth = $auth->getIterator();

        // Buchungsnummer mit Session
        if(empty($userAuth['userId'])){
            $sessionId = Zend_Session::getId();
            $sql = "select id as buchungsnummer from tbl_buchungsnummer where session_id = '".$sessionId."'";
        }
        // Buchungsnummern nach Kunden ID
        else
            $sql = "select id as buchungsnummer from tbl_buchungsnummer where kunden_id = '".$userAuth['userId']."'";


        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');
        $buchungsnummern = $db->fetchAll($sql);
        $this->_deleteHotelRate($buchungsnummern);
    }

    /**
     * Löschen der Rate eines Hotels
     *
     * @param array $__buchungsnummern
     * @return
     */
    private function _deleteHotelRate(array $__buchungsnummern){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        for($i=0; $i < count($__buchungsnummern); $i++){
            $sql = "delete from tbl_hotelbuchung where buchungsnummer_id = ".$__buchungsnummern[$i]['buchungsnummer']." and status = ".$this->condition_noch_nicht_gebucht;
            $db->query($sql);

            $sql = "delete from tbl_xml_buchung where buchungsnummer_id = ".$__buchungsnummern[$i]['buchungsnummer']." and status = ".$this->condition_noch_nicht_gebucht." and bereich = ".$this->condition_bereich_hotel;
            $db->query($sql);
        }

        return;
    }

    /**
     * Löscht eine einzelne Rate
     * mittels der buchungstabelleId
     *
     * @param $buchungstabelleId
     * @return
     */
    public function deleteSingleHotelRate($buchungstabelleId)
    {
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        $sql = "delete from tbl_hotelbuchung where id = ".$buchungstabelleId;
        $db->query($sql);

        $sql = "delete from tbl_xml_buchung where buchungstabelle_id = ".$buchungstabelleId;
        $db->query($sql);

        return;
    }

    /**
     * Löschen aller Programme einer Buchung
     * Auswahl der zu löschenden Raten mittels SessionId oder KundenID.
     *
     */
    public function deleteAllProgramms(){

        // ist eine Kunden ID vorhanden
        $auth = new Zend_Session_Namespace('Auth');
        $userAuth = $auth->getIterator();

        // Buchungsnummer mit Session
        if(empty($userAuth['userId'])){
            $sessionId = Zend_Session::getId();
            $sql = "select id as buchungsnummer from tbl_buchungsnummer where session_id = '".$sessionId."'";
        }
        // Buchungsnummern nach Kunden ID
        else
            $sql = "select id as buchungsnummer from tbl_buchungsnummer where kunden_id = '".$userAuth['userId']."'";

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');
        $buchungsnummern = $db->fetchAll($sql);

        // loeschen des Programmes
        $this->_deleteSingleProgramm($buchungsnummern);

        return;
    }

    /**
     * Löscht Programme mittels einer Buchungsnummer
     * Löscht wenn der Status 0 ist.
     */

    private function _deleteSingleProgramm(array $__buchungsnummern){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        for($i=0; $i < count($__buchungsnummern); $i++){
            $sql = "delete from tbl_programmbuchung where buchungsnummer_id = ".$__buchungsnummern[$i]['buchungsnummer'];
            $db->query($sql);

            $sql = "delete from tbl_xml_buchung where buchungsnummer_id = ".$__buchungsnummern[$i]['buchungsnummer']." and bereich = ".$this->condition_bereich_programm;
            $db->query($sql);
        }

        return;
    }

    /**
     * Speichert die Hotelbestellung in der Tabelle 'tbl_hotelbuchung'
     *
     * + gibt die ID der Teilrechnung zurück
     *
     * @param $__gebuchteRatenEinesHotels
     * @return int
     */
    public function saveHotelBuchungen($__gebuchteRatenEinesHotels){
        // speichern der Hotelbuchung
        $hotelbuchung = new Front_Model_WarenkorbHotelbuchung();

        $teilrechnungsId = $hotelbuchung
            ->checkRatenDesHotels($__gebuchteRatenEinesHotels)
            ->checkPersonenanzahl($__gebuchteRatenEinesHotels)
            ->setDataHotelbuchung($__gebuchteRatenEinesHotels)
            ->getTeilrechnungsId();

        return $teilrechnungsId;
    }

    /**
     * Update der bereits gebuchten Raten eines Hotels
     *
     * @param $__bereitsGebuchteRatenEinesHotels
     * @return
     */
    public function updateHotelBuchungen($__buchungsnummern){
        $modelWarenkorbHotelbuchung = new Front_Model_WarenkorbHotelbuchung();
        $bereitsGebuchteRaten = $modelWarenkorbHotelbuchung->getBereitsGebuchteRaten($__buchungsnummern);
        $bereitsGebuchteRaten = $modelWarenkorbHotelbuchung->ermittelnCodesRateUndHotel($bereitsGebuchteRaten);

        // Sind die Raten noch verfügbar ?
        $bereitsGebuchteRaten = $modelWarenkorbHotelbuchung->checkAvailabilityRates($bereitsGebuchteRaten);

        // Veränderung des Preises der Raten
        $modelWarenkorbHotelbuchung->checkPriceRates($bereitsGebuchteRaten);

        return;
    }
}
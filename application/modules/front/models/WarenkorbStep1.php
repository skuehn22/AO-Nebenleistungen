<?php
class Front_Model_WarenkorbStep1 extends nook_Model_model{
	
	public $error_no_correct_data_entry = 40;
	public $error_no_correct_price = 41;
	public $error_no_correct_startDate = 42;
	public $error_no_correct_booking_deadline = 43;
	public $error_no_correct_season_start = 44;
	public $error_no_correct_season_end = 45;
	public $error_no_correct_database_connection = 46;
	public $error_no_integer_step = 47;
	public $error_offer_not_delete = 48;
    public $error_programm_gehoert_nicht_dem_user = 49;
	
	public $condition_day_in_seconds = 86400;
	public $condition_is_offer = 1;
	public $condition_is_price_per_person = 1;
    public $condition_personen_einer_gruppe = 0;
	public $condition_is_programm_sachleistung = 1;
	
	
	private $_data = array();
	private $_language;
	private $_totalPrice = 0;
	public $counterForProgramsToOrder;
	
	private function _changeDateToGerman($__params){
		$dateItems = explode('/', $__params['datum']);
		$__params['datum'] = $dateItems[0].".".$dateItems[1].".".$dateItems[2];
		
		return $__params;
	}

    public function mapParams($__params){

        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        return $__params;
    }

	public function saveOrder(){
		$bookingNumber = $this->_setOrFindBookingNumber();

		$sachleistung = $this->condition_is_programm_sachleistung;
		$informationen = "";
		
		if(array_key_exists('sachleistung', $this->_data))
			$sachleistung = $this->_data['sachleistung'];
		
		if(array_key_exists('informationen', $this->_data))
			$informationen = $this->_data['informationen'];
		
		$db = Zend_Registry::get('front');

        // Treffpunkt
        $this->_bestimmeTreffpunkt();

        // Personenpreis
        if($this->_data['gruppenpreis'] == $this->condition_is_price_per_person){
            $buchung = array(
                'fa_id' => $this->_data['ProgrammId'],
                'datum' => $this->_data['datum'],
                'zeit' => $this->_data['stunde'].':'.$this->_data['minute'],
                'sprache' => $this->_data['languages'],
                'persons' => $this->_data['persons'],
                'price_persons' => $this->_data['pricePersons'],
                'buchungsnummer_id' => $bookingNumber,
                'sachleistung' => $sachleistung,
                'informationen' => $informationen,
                'treffpunkt' => $this->_data['treffpunkt']
            );
        }
        // Gruppenpreis
        else{

            $condition_sachleistung = 2;

            $buchung = array(
                'fa_id' => $this->_data['ProgrammId'],
                'datum' => $this->_data['datum'],
                'zeit' => $this->_data['stunde'].':'.$this->_data['minute'],
                'sprache' => $this->_data['languages'],
                'persons' => $this->condition_personen_einer_gruppe,
                'price_persons' => $this->_data['pricePersons'],
                'buchungsnummer_id' => $bookingNumber,
                'sachleistung' => $condition_sachleistung,
                'informationen' => $informationen,
                'treffpunkt' => $this->_data['treffpunkt']
            );
        }

		$control = $db->insert('tbl_buchung', $buchung);
		if($control != 1)
			throw new nook_Exception($this->error_no_correct_database_connection);
		
	}

    private function _bestimmeTreffpunkt(){

        $this->_data['treffpunkt'] = '';

        if(!empty($this->_data['eigenerTreffpunkt'])){
            $this->_data['treffpunkt'] = $this->_data['eigenerTreffpunkt'];
        }

        if(!empty($this->_data['treffpunktWahl'])){
            $db = Zend_Registry::get('front');
            $sql = "select treffpunkt from tbl_treffpunkt where id = ".$this->_data['treffpunktWahl'];
            $this->_data['treffpunkt'] = $db->fetchOne($sql);
        }

        return;
    }
	
	public function findCountries(){
		$db = Zend_Registry::get('front');
		$sql = "SELECT
				    `id`, `Name`
				FROM
				    `tbl_countries`
				ORDER BY `id` ASC";
		
		$countries = $db->fetchAll($sql);
		return $countries;
	}
	
	private function _setOrFindBookingNumber(){
		$db = Zend_Registry::get('front');
		$sessionId = Zend_Session::getId();
		
		$sql = "select count(id) from tbl_buchungsnummer where session_id = '".$sessionId."'";
		$anzahlBuchungen = $db->fetchOne($sql);
		
		$warenkorb = new Zend_Session_Namespace('warenkorb');
		$kundenId = $warenkorb->kundenId;
		
		
		if($anzahlBuchungen == 0){
			if(empty($kundenId)){
				$insert = array(
					'session_id' => $sessionId
				);
			}
			else{
				$insert = array(
					'session_id' => $sessionId,
					'kunden_id' => $kundenId
				);
			}
			
			$control = $db->insert('tbl_buchungsnummer', $insert);
			if($control != 1)
				throw new nook_Exception($this->error_no_correct_database_connection);
				
			$bookingNumber = $db->lastInsertId();
		}
		else{
			$sql = "select id from tbl_buchungsnummer where session_id = '".$sessionId."'";
			$bookingNumber = $db->fetchOne($sql);
			
			if(!empty($kundenId)){
				
				$update = array(
					"kunden_id" => $kundenId
				);
				
				$db->update('tbl_buchungsnummer', $update, "session_id = '".$sessionId."'");
			}
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

    /**
     * ermittelt die User ID
     * aus Zend:Session_Namespace('Auth')
     *
     * @return string
     */
	public function getKundenId(){
		$kundenId = translate('unbekannt');

        $auth = new Zend_Session_Namespace('Auth');
        $itemsAuth = $auth->getIterator();

		if(!empty($itemsAuth['userId']))
			$kundenId = $itemsAuth['userId'];
		
		return $kundenId;
	}
	
	public function setOfferDelete($__deleteOffer){
        
		$control = new Zend_Validate_Int();
		if(!$control->isValid($__deleteOffer))
			throw new nook_Exception($this->error_offer_not_delete);

        $this->_gehoertDasProgrammZuEinemBenutzer($__deleteOffer);
		
		$sql = "select count(id) from tbl_buchung where id = '".$__deleteOffer."'";
		
		$control = $this->_groupsDatabase->fetchOne($sql);
		if($control != 1)
			throw new nook_Exception($this->error_offer_not_delete);
		
		$control = $this->_groupsDatabase->delete('tbl_buchung', "id = '".$__deleteOffer."'");
		if($control != 1)
			throw new nook_Exception($this->error_offer_not_delete);
		
		return;
	}

    private function _gehoertDasProgrammZuEinemBenutzer($__programmId){

        $auth = new Zend_Session_Namespace('Auth');
        if(empty($auth->userId)){
            $sessionId = Zend_Session::getId();
            $sql = "select id from tbl_buchungsnummer where session_id = '".$sessionId."'";
        }
        else
            $sql = "select id from tbl_buchungsnummer where kunden_id = ".$auth->userId;

        $buchungsnummer = $this->_groupsDatabase->fetchOne($sql);
        $sql = "select count(id) from tbl_buchung where buchungsnummer_id = ".$buchungsnummer." and id = ".$__programmId;
        $anzahlDerZuLoeschendenProgramme = $this->_groupsDatabase->fetchOne($sql);

        if($anzahlDerZuLoeschendenProgramme > 1)
            throw new nook_Exception($this->error_programm_gehoert_nicht_dem_user);

        return;
    }
	
	public function getTotalPrice(){
		$totalPrice = number_format($this->_totalPrice, 2);
		
		return $totalPrice;
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

        // Flag für Gruppenbuchung
        $this->_data['gruppenpreis'] = $controlDetails['gruppenpreis'];

        $controlDetails = nook_Tool::addVat($controlDetails);
		$this->_kontrolleFristen($controlDetails);

		return;
	}
	
	public function getShoppingCartProgramme(){
		$this->counterForProgramsToOrder = 0;

		$warenkorb = new Zend_Session_Namespace('warenkorb');
		if(empty($warenkorb->kundenId))
			$shoppingCart = $this->_findOrdersBySessionId();
		else
			$shoppingCart = $this->_findOrdersByKundenId($warenkorb->kundenId);
		
		if(count($shoppingCart) > 0){
			$shoppingCart = nook_Tool::trimLongText($shoppingCart);

            // berechnen Gesamtpreis
			$shoppingCart = $this->_offerPrice($shoppingCart);
			
			for($i=0; $i < count($shoppingCart); $i++){
                // Überprüfen
				$shoppingCart[$i] = nook_ToolProgrammbilder::findImageFromProgram($shoppingCart[$i], 'midi');
				$shoppingCart[$i]['buchungsdatum'] = nook_Tool::buildCompleteGermanDate($shoppingCart[$i]['buchungsdatum']);
				// if($shoppingCart[$i]['status'] < 4)
				$this->counterForProgramsToOrder++;
			}
		}
		
		return $shoppingCart;
	}

    public function getShoppingCartZusatzprodukte(){

        $shoppingCartZusatzprodukte = new Front_Model_WarenkorbShoppingcartZusatzprodukte();
        $alleGebuchtenZusatzprodukte = $shoppingCartZusatzprodukte->getShoppingcartZusatzprodukte();

        return $alleGebuchtenZusatzprodukte;
    }

    /**
     * Darstellung des Verarbeitungsschrittes
     * 
     * @param $__bereich
     * @param $__step
     * @param array $__params
     * @return array
     */
	public function setAktiveStep($__bereich, $__step, array $__params){
		$breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();
		
		return $navigation;
	}
	
	private function _offerPrice($__shoppingCart){
		for($i=0; $i < count($__shoppingCart); $i++){
			
			if($__shoppingCart[$i]['sachleistung'] == $this->condition_is_price_per_person ){
				$offerprice = $__shoppingCart[$i]['price_persons'] * $__shoppingCart[$i]['persons'];
				$__shoppingCart[$i]['offerPrice'] = $offerprice;
			}
			else{
				$__shoppingCart[$i]['offerPrice'] = $__shoppingCart[$i]['price_persons'];
				$offerprice = $__shoppingCart[$i]['price_persons'];
			}
			
			$this->_totalPrice += $offerprice;
		}
		
		return $__shoppingCart;
	}
	
	private function _findOrdersBySessionId(){
		$db = Zend_Registry::get('front');
		$language = nook_Tool::findLanguage();

		$sql = "
			SELECT
			    `buchungsnummer`.`session_id`
			    , `tbl_buchung`.`datum`
			    , `tbl_buchung`.`date` as buchungsdatum
			    , `tbl_buchung`.`id`
			    , `tbl_buchung`.`fa_id`
			    , `tbl_buchung`.`zeit`
			    , `tbl_buchung`.`sprache` AS `Programmsprache`
			    , `tbl_buchung`.`treffpunkt`
			    , `tbl_buchung`.`persons`
			    , `tbl_buchung`.`status`
			    , `tbl_buchung`.`price_persons`
			    , `tbl_buchung`.`informationen`
			    , `tbl_buchung`.`sachleistung`
			    , `tbl_buchung`.`treffpunkt`
			    , `tbl_prog_sprache`.`flag`
			    , `tbl_programmbeschreibung`.`progname`
			    , `tbl_programmbeschreibung`.`noko_kurz`
			    , `tbl_programmbeschreibung`.`sprache`
			    , `tbl_programmbeschreibung`.`txt`
			    , `tbl_programmdetails`.`dauer`
			    , `tbl_programmdetails`.`AO_City` as city
			    , `tbl_adressenfa`.`Ort`
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
			    INNER JOIN `tbl_adressenfa` 
			        ON (`tbl_programmdetails`.`Fa_ID` = `tbl_adressenfa`.`Fa_ID`)
			WHERE (`tbl_buchungsnummer`.`session_id` = '".Zend_Session::getId()."'
			    AND `tbl_programmbeschreibung`.`sprache` = '".$language."' and `buchung`.`status` < 4)";
		
		$shoppingCart = $db->fetchAll($sql);
		
		return $shoppingCart;
	}
	
	private function _findOrdersByKundenId($__kundenId){
		$db = Zend_Registry::get('front');
		$language = nook_Tool::findLanguage();

		$sql = "
			SELECT
			    `buchungsnummer`.`session_id`
			    , `tbl_buchung`.`datum`
			    , `tbl_buchung`.`date` as buchungsdatum
			    , `tbl_buchung`.`id`
			    , `tbl_buchung`.`fa_id`
			    , `tbl_buchung`.`zeit`
			    , `tbl_buchung`.`sprache` AS `Programmsprache`
			    , `tbl_buchung`.`treffpunkt`
			    , `tbl_buchung`.`persons`
			    , `tbl_buchung`.`status`
			    , `tbl_buchung`.`price_persons`
			    , `tbl_buchung`.`informationen`
			    , `tbl_buchung`.`sachleistung`
			    , `tbl_buchung`.`treffpunkt`
			    , `tbl_prog_sprache`.`flag`
			    , `tbl_programmbeschreibung`.`progname`
			    , `tbl_programmbeschreibung`.`noko_kurz`
			    , `tbl_programmbeschreibung`.`sprache`
			    , `tbl_programmbeschreibung`.`txt`
			    , `tbl_programmdetails`.`dauer`
			    , `tbl_programmdetails`.`AO_City` as city
			    , `tbl_adressenfa`.`Ort`
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
			    INNER JOIN `tbl_adressenfa` 
			        ON (`tbl_programmdetails`.`Fa_ID` = `tbl_adressenfa`.`Fa_ID`)
			WHERE (`tbl_buchungsnummer`.`kunden_id` = '".$__kundenId."'
			    AND `tbl_programmbeschreibung`.`sprache` = '".$language."' and `tbl_buchung`.`status` < 4)";
		
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
			'ProgrammId' => array(
				'Int',
				'presence' => 'required',
			),
			'datum' => $validateDate,
			'stunde' => 'Int',
			'minute' => 'Int',
			'pricePersons' => array(
				$validateFloat
			),
			'languages' => array(
				'Int',
				'presence' => 'required'
			),
			'persons' => array(
				'Int'
			)
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
		    , `gruppenpreis`
		    , `Fa_ID`
		FROM
		    `tbl_programmdetails`
		WHERE (`Fa_ID` = '".$this->_data['ProgrammId']."')";
		
		$db = Zend_Registry::get('front');
		$controlDetails = $db->fetchRow($sql);
		
		return $controlDetails;
	}
	
	private function _kontrolleFristen($__controlDetails){
		// wenn keine Saison
		if(empty($__controlDetails['Saisonbeginn']) or empty($__controlDetails['Saisonende']))
			return;
		
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
	
}
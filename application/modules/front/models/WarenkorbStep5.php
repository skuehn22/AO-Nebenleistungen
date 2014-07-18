<?php
class Front_Model_WarenkorbStep5 extends nook_Model_model{
	
	public $error_no_confirmcode_exists = 80;
	public $error_no_personal_data = 81;
	public $error_no_booking_kunden_id = 82;
	public $error_no_booking_status_is_update = 83;
	public $error_bookings_already_confirmed = 84;
	public $error_no_booking_numbers_exists = 85;
	public $error_no_data_insert_in_zahlung = 86;
	public $error_no_data_in_table_zahlung = 87;
	public $error_no_data_in_table_zahlung_for_change_status = 88;
	public $error_no_payment_logged = 89;
	
	public $condition_copy_to_support = 2;
	public $condition_support_has_information = 4;
	public $condition_user_is_customer = 3;
	public $condition_is_price_per_person = 1;
	public $condition_confirm_order = 1;
	public $condition_confirm_flag = 2;
	public $condition_order_is_registered_but_not_confirmed = 1;
	public $condition_order_is_registered_and_confirmed = 2;
	
	public $confirmCode;
	private $_kundenId;
	public $kundenId;
	public $bookingNumber;
	
	public function getpersonalData(){
		$db = Zend_Registry::get('front');
		
		$sql = "select * from tbl_adressen where controlcode = '".$this->confirmCode."'";
		$personalData = $db->fetchRow($sql);
		if(!is_array($personalData))
			throw new nook_Exception($this->error_no_personal_data);
		
		$this->_kundenId = $personalData['id'];
			
		return $personalData;
	}

	public function setStatusUser($__actualStatus){
		
		if($__actualStatus >= $this->condition_user_is_customer)
			return;
		
		$db = Zend_Registry::get('front');
		
		$update = array(
			"status" => $this->condition_user_is_customer
		);
		
		$db->update('tbl_adressen', $update, "controlcode = '".$this->confirmCode."'");
		
		return;
	}
	
	public function getShoppingCart($__kundenId){
		$db = Zend_Registry::get('front');
		$language = nook_Tool::findLanguage();

		$sql = "
		SELECT
		    `tbl_buchung`.*
		    , `tbl_programmbeschreibung`.`noko_kurz`
		    , `tbl_programmbeschreibung`.`progname`
		    , `tbl_programmbeschreibung`.`Öffnungszeiten`
		    , `tbl_programmbeschreibung`.`treffpunkt`
		    , `tbl_programmbeschreibung`.`txt`
		    , `tbl_buchungsnummer`.`session_id`
		    , `tbl_buchungsnummer`.`kunden_id`
		    , `tbl_buchungsnummer`.`id` AS `buchungsnummer`
		    , `tbl_programmdetails`.`ek`
		    , `tbl_programmdetails`.`vk`
		    , `tbl_programmdetails`.`mwst_satz`
		FROM
		    `tbl_buchungsnummer`
		    INNER JOIN `tbl_buchung`
		        ON (`tbl_buchungsnummer`.`id` = `tbl_buchung`.`buchungsnummer_id`)
		    INNER JOIN `tbl_programmdetails`
		        ON (`tbl_buchung`.`fa_id` = `tbl_programmdetails`.`Fa_ID`)
		    INNER JOIN `tbl_programmbeschreibung`
		        ON (`tbl_programmdetails`.`Fa_ID` = `tbl_programmbeschreibung`.`Fa_Id`)
		WHERE (`tbl_programmbeschreibung`.`sprache` = '".$language."'
		    AND `tbl_buchung`.`status` < 4
		    AND `tbl_buchungsnummer`.`kunden_id` = '".$__kundenId."')";
		
		$shoppingCart = $db->fetchAll($sql);
		if(count($shoppingCart) == 0)
			throw new nook_Exception($this->error_bookings_already_confirmed);
		
		$shoppingCart = $this->_offerPrice($shoppingCart);
		
		for($i=0; $i < count($shoppingCart); $i++){
            // Überprüfen
			$shoppingCart[$i] = nook_ToolProgrammbilder::findImageFromProgram($shoppingCart[$i], 'mini');
			$shoppingCart = nook_Tool::trimLongText($shoppingCart);
		}
		
		$this->kundenId = $shoppingCart[0]['kunden_id'];
		
		return $shoppingCart;
	}
	
	public function setStatusBookingSendToSupport(){
		$db = Zend_Registry::get('front');
		
		$sql = "select id from tbl_buchungsnummer where kunden_id = '".$this->kundenId."'";
		$openBookingNumbers = $db->fetchAll($sql);
		
		if(!is_array($openBookingNumbers))
			throw new nook_Exception($this->error_no_booking_numbers_exists);
		
		$bookedOrder = 0;
		$update = array(
			"status" => $this->condition_support_has_information
		);
		
		for($i=0; $i<count($openBookingNumbers); $i++){
			$bookedOrder += $db->update('tbl_buchung', $update, "buchungsnummer_id = '".$openBookingNumbers[$i]['id']."' and status < 4");
		}
		
		if($bookedOrder == 0)
			throw new nook_Exception($this->error_no_booking_status_is_update);

		return;
	}
	
	private function _offerPrice($__shoppingCart){
		for($i=0; $i < count($__shoppingCart); $i++){
			
			if($__shoppingCart[$i]['sachleistung'] == $this->condition_is_price_per_person){
				$offerprice = $__shoppingCart[$i]['price_persons'] * $__shoppingCart[$i]['persons'];
				$offerprice = number_format($offerprice, 2);
				$__shoppingCart[$i]['offerPrice'] = $offerprice;
			}
			else{
				$offerprice = $__shoppingCart[$i]['price_persons'];
				$__shoppingCart[$i]['offerPrice'] = $offerprice;
			}
			
			$this->_totalPrice += $offerprice;
		}
		
		return $__shoppingCart;
	}
	
	public function registerPayment($__personalData, $__shoppingCart){
		$db = Zend_Registry::get('front');
		$sql = "SELECT
		    MAX(`zahlungsnummer`)
		FROM
		    `tbl_zahlung`";
		
		$paymentNumber = $db->fetchone($sql);
		$paymentNumber++;
		
		for($i=0; $i<count($__shoppingCart); $i++){
			
			$brutto = nook_Tool::calculateNettoBrutto($__shoppingCart[$i]['mwst_satz'], $__shoppingCart[$i]["vk"]);
			$mwst = $brutto - $__shoppingCart[$i]["vk"];
			$mwst = number_format($mwst, 2);
			
			$insert = array(
				"fa_id" => $__shoppingCart[$i]['fa_id'],
				"datum_programm" => $__shoppingCart[$i]['datum'],
				"ek" => $__shoppingCart[$i]['ek'],
				"vk" => $brutto,
				"buchungsnummer_id" => $__shoppingCart[$i]['buchungsnummer_id'],
				"kunden_id" => $__shoppingCart[$i]['kunden_id'],
				"zahlungsnummer" => $paymentNumber,
				"mwst_satz" => $__shoppingCart[$i]['mwst_satz'],
				"status" => $this->condition_order_is_registered_but_not_confirmed,
				"mwst" => $mwst,
				"buchungsnummer_program" => $__shoppingCart[$i]['id']
			);
			
			$control = $db->insert('tbl_zahlung', $insert);
			if($control != 1)
				throw new nook_Exception($this->error_no_data_insert_in_zahlung);
		}
		
		return $paymentNumber;
	}
	
	public function getBankData(){
		$bankData = Zend_Registry::get('static')->bank;
		
		return $bankData->toArray();
	}
	
	public function setControlCodeToPaymentNumber($__paymentNumber, $__controlCode){
		$db = Zend_Registry::get('front');
		
		$update = array(
			"controlcode" => $__controlCode
		);
		
		$control = $db->update('tbl_zahlung', $update, "zahlungsnummer = '".$__paymentNumber."'");
		if(empty($control))
			throw new nook_Exception($this->_error_no_data_in_table_zahlung);
		
		return;
	}
	
	public function setTableZahlungBestaetigt($__controlCode){
		$db = Zend_Registry::get('front');
		
		$update = array(
			"status" => $this->condition_order_is_registered_and_confirmed
		);
		
		$control = $db->update('tbl_zahlung', $update, "controlcode = '".$__controlCode."'");
		if(empty($control))
			throw new nook_Exception($this->error_no_data_in_table_zahlung_for_change_status);
		
		return;
	}
	
	public function setControlCodeToUser($__controlCode, $__kundenId){
		$db = Zend_Registry::get('front');
		$update = array(
			"controlcode" => $__controlCode
		);
		$control = $db->update('tbl_adressen', $update, "id = '".$__kundenId."'");
		
		return;
	}
	
	public function findPaymentNumber($__controlCode){
		$db = Zend_Registry::get('front');
		$sql = "select zahlungsnummer from tbl_zahlung where controlcode = '".$__controlCode."'";
		$paymentNumber = $db->fetchOne($sql);
		
		return $paymentNumber;
	}
	
	public function logPayment($__shoppingCart){
		nook_Tool::setMessageToLog("Programm wurden geordert", $__shoppingCart, $this->error_no_payment_logged);
		
		return;
	}
	
	public function logBookingIsConfirmed($__shoppingCart){
		nook_Tool::setMessageToLog("Programm wurden geordert und bestätigt", $__shoppingCart, $this->error_no_payment_logged);
		
		return;
	}
	
	public function generateVoucherPdf($__controlCode, $__paymentNumber, $__personalData, $__shoppingCart, $model4){
		$db = Zend_Registry::get('front');
		$pdf = new nook_Invoice();
		$pdf->start("../pdf/standard_voucher.pdf", true);
		
		// Anschrift
		$personalText  = $this->_buildAddressBlock($__personalData);
		$pdf->setTextBlock($personalText, 650, 10);
		
		// Zahlungsnummer
		$pdf->setText($__paymentNumber, 565);
		
		// Programme
		$programText = '';
		for($i=0; $i<count($__shoppingCart); $i++){
			// Programmanbieter
			$supplierData = $model4->findSupplierData($db, $__shoppingCart[$i]);	
			
			$programText .= translate('Buchungsnummer').": ".$__shoppingCart[$i]['buchungsnummer_id']."\n";
			$programText .= translate('Programmnummer').": ".$__shoppingCart[$i]['fa_id'].", ".$__shoppingCart[$i]['progname']."\n";
			$programText .= translate('Treffpunkt').$__shoppingCart[$i]['treffpunkt']."\n";			
			$programText .= translate('Personen').": ".$__shoppingCart[$i]['persons'].", ".translate('Datum').": ".$__shoppingCart[$i]['datum']." , ".$__shoppingCart[$i]['zeit'].translate('Uhr')."\n \n";
			$programText .= translate('zusätzliche Informationen').": \n".$__shoppingCart[$i]['informationen']."\n\n";
			
		}
		$pdf->setTextBlock($programText, 500, 10);
		
		$pdf->drawInvoice("./_pdf/".$__controlCode."_voucher.pdf");
		return;
	}
	
	public function generatePaymentPdf($__controlCode, $__paymentNumber, $__personalData, $__shoppingCart, $__bankData, $model4){
		$db = Zend_Registry::get('front');
		$pdf = new nook_Invoice();
		$pdf->start("../pdf/standard_invoice.pdf", true);
		
		// Adresse
		$personalText  = $this->_buildAddressBlock($__personalData);
		$pdf->setTextBlock($personalText, 650, 10);
		
		// Rechnungen der einzelnen Programme
		$paymentText = $this->_buildPaymentBlock($__shoppingCart);
		$pdf->setTextBlock($paymentText, 500, 10);
		
		// Gesamtsumme und Angabe Bankverbindung
		$totalPrice = nook_Tool::calculateTotalPrice($__shoppingCart);
		$totalPrice = nook_Tool::commaCorrection($totalPrice);
		$bankText = $this->_buildBankBlock($__bankData, $totalPrice);
		$pdf->setTextBlock($bankText, 200, 10);
		
		$pdf->drawInvoice("./_pdf/".$__controlCode."_rechnung.pdf");
		return;
	}
	
	private function _buildAddressBlock($__personalData){
		// Anschrift
		$personalText  = translate('Kundennummer').": ".$__personalData['id']."\n";
		$personalText .= $__personalData['title']." ".$__personalData['firstname']." ".$__personalData['lastname']."\n";
		if(!empty($__personalData['company']))
			$personalText .= translate('Firma').": ".$__personalData['company']."\n";
		$personalText .= $__personalData['street']." ".$__personalData['housenumber']."\n";
		$personalText .= $__personalData['zip']." ".$__personalData['city']."\n";
		$personalText .= $__personalData['region']."\n";
		$personalText .= nook_Tool::findCountryName($__personalData['country']);
		
		return $personalText;
	}
	
	private function _buildPaymentBlock($__shoppingCart){
		$paymentBlock = "";
		
		for($i=0; $i<count($__shoppingCart); $i++){
			$paymentBlock .= translate('Programmnummer').": ".$__shoppingCart[$i]['fa_id']."\n";
			$paymentBlock .= translate('Programmname').": ".$__shoppingCart[$i]['progname']."\n";
			$paymentBlock .= translate('Datum').": ".$__shoppingCart[$i]['datum']."\n";
			$paymentBlock .= translate('Anzahl Personen').": ".$__shoppingCart[$i]['persons']."\n";
			$paymentBlock .= translate('Preis pro Person').": ".nook_Tool::commaCorrection($__shoppingCart[$i]['price_persons'])." Euro \n";
			$paymentBlock .= translate('Preis für dieses Angebot').": ".nook_Tool::commaCorrection($__shoppingCart[$i]['offerPrice'])." Euro \n";
			$paymentBlock .= translate('Zeit').": ".$__shoppingCart[$i]['zeit']." Uhr \n";
			$sprache = nook_Tool::findLanguageName($__shoppingCart[$i]['sprache']);
			$paymentBlock .= translate('Sprache').": ".$sprache."\n\n";
			$paymentBlock .= translate('zusätzliche Informationen').": \n".$__shoppingCart[$i]['informationen']."\n\n";
		}
		
		
		return $paymentBlock;
	}
	
	private function _buildBankBlock($__bankData, $totalPrice){
		$bankBlock = translate("Bitte überweisen Sie den Gesamtpreis in Höhe von ").$totalPrice." Euro \n";
		$bankBlock .= translate("an folgende Bankverbindung.")." \n\n";
		
		$bankBlock .= translate("Name der Bank").": ".$__bankData['bank']." \n";
		$bankBlock .= translate("Bankleitzahl").": ".$__bankData['bankleitzahl']."\n";
		$bankBlock .= translate("Kontonummer").": ".$__bankData['kontonummer']."\n";
		$bankBlock .= translate("Zahlungsgrund").": ".$__bankData['paymentNumber'];
		
		
		return $bankBlock;
	}
}
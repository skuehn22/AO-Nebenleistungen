<?php
class Front_Model_WarenkorbStep4 extends nook_Model_model{

	public $error_no_mail_send = 70;
	public $error_no_supplier_mail_send = 71;
	public $error_no_booking_numbers_exists = 72;
	public $error_controll_code_for_pdf_not_exists = 73;

	public $condition_booking_is_reserved = 2;
	public $condition_booking_is_ordered = 3;
	public $condition_send_newsletter = 2;
	public $condition_send_mail_to_user = 2;
	public $condition_mail_address_support = 'kuehn.sebastian@gmail.com';
    public $condition_user_is_guest = 1;

	private $_data;

	public function setShoppingCartToReserved(){
		$db = Zend_Registry::get('front');
		$warenkorb = new Zend_Session_Namespace('warenkorb');
		$kundenId = $warenkorb->kundenId;

		$sql = "select id from tbl_buchungsnummer where kunden_id = '".$kundenId."'";
		$bookingNumbers = $db->fetchAll($sql);
		if(count($bookingNumbers) == 0)
			throw new nook_Exception($this->error_no_booking_numbers_exists);

		for($i=0; $i<count($bookingNumbers); $i++){
			$update = array(
				"status" => $this->condition_booking_is_reserved
			);

			$control = $db->update('tbl_buchung', $update, "buchungsnummer_id = '".$bookingNumbers[$i]['id']."' and status < 4");
		}

		return;
	}

    public function kontrolliereStatusUser($__kontrollStatus){
        $db = Zend_Registry::get('front');
        $warenkorb = new Zend_Session_Namespace('warenkorb');
		$kundenId = $warenkorb->kundenId;

        $sql = "select status from tbl_adressen where id = ".$kundenId;
        $aktuelleStatus = $db->fetchOne($sql);
        if($aktuelleStatus < $this->condition_user_is_guest){
            $sql = "update tbl_adressen set status = '".$this->condition_user_is_guest."' where id = ".$kundenId;
            $db->query($sql);
        }

        return;
    }

	public function setShoppingCartToOrderd(){
		$db = Zend_Registry::get('front');
		$warenkorb = new Zend_Session_Namespace('warenkorb');

		$kundenId = $warenkorb->kundenId;
		$sql = "select id from tbl_buchungsnummer where kunden_id = '".$kundenId."'";
		$bookingNumbers = $db->fetchAll($sql);
		if(count($bookingNumbers) == 0)
			throw new nook_Exception($this->error_no_booking_numbers_exists);

		for($i=0; $i<count($bookingNumbers); $i++){
			$update = array(
				"status" => $this->condition_booking_is_ordered
			);

			$control = $db->update('tbl_buchung', $update, "buchungsnummer_id = '".$bookingNumbers[$i]['id']."' and status < 4");

		}

		return;
	}

	public function orderNewsletter(){
		$sessionShoppingCart = new Zend_Session_Namespace('warenkorb');
		$kundenId = $sessionShoppingCart->kundenId;

		$db = Zend_Registry::get('front');

		$update = array(
			"newsletter" => $this->condition_send_newsletter
		);

		$db->update('tbl_adressen', $update, "id = '".$kundenId."'");

		return;
	}

	public function sendOrderMail($__personalData, $__shoppingCart, $__bankData){
		$db = Zend_Registry::get('front');
		$orderMail = raintpl_rainhelp::getRainTpl();

		$sql = "select Name from tbl_countries where id = '".$__personalData['country']."'";
		$__personalData['countryname'] = $db->fetchOne($sql);

		$orderMail->assign('kundenId', $__personalData['id']);
		$orderMail->assign('personalData', $__personalData);

		$__shoppingCart = $this->_findLanguageFlag($__shoppingCart);
		$orderMail->assign('shoppingCart', $__shoppingCart);
		$orderMail->assign('bankData', $__bankData);

		$generatedControlCode = $this->_generateControlCode($__personalData);
		$orderMail->assign('generatedControlCode', $generatedControlCode);

		if($this->condition_send_mail_to_user == 2)
			$orderMail->assign('copyForSupport', 1);
		else
			$orderMail->assign('copyForSupport', 2);

		$orderMail->assign('server', Zend_Registry::get('static')->server->server);

		$priceTotal = $this->_calculateTotalPrice($__shoppingCart);
		$orderMail->assign('priceTotal', $priceTotal);

		$htmlOrderMail = $orderMail->draw('orderMail', true);
		$this->_sendMailToUser($htmlOrderMail, $__personalData, $db);

		return $generatedControlCode;
	}

	private function _findLanguageFlag($__shoppingCart){
		for($i=0; $i<count($__shoppingCart); $i++){
			$__shoppingCart[$i]['flag'] = nook_Tool::findFlagForProgLanguage($__shoppingCart[$i]['sprache']);
		}

		return $__shoppingCart;
	}

	private function _sendMailToUser($__htmlOrderMail, $__personalData, $__db){
		$mail = new Zend_Mail('UTF-8');

		if($this->condition_send_mail_to_user == 2)
			$mail->addTo($__personalData['email'], 'Ihre Bestellung');
		else
			$mail->addTo($this->condition_mail_address_support, 'Ihre Bestellung');

		$mail->setBodyHtml($__htmlOrderMail);
		//$mail->setFrom('tickets@aohostels.com', 'Herden Studienreisen');
        $mail->setFrom('kuehn.sebastian@gmail.com', 'Herden Studienreisen');
		$mail->setSubject('Ihre Bestellung bei Studienreisen Herden');
		$control = $mail->send();

		if(empty($control))
			throw new nook_Exception($this->error_no_mail_send);

		if($this->condition_send_mail_to_user == 2){
			$generatedControlCode = $this->_generateControlCode($__personalData);

			$update = array(
				"controlcode" => $generatedControlCode
			);

			$control = $__db->update('tbl_adressen',$update, "id = '".$__personalData['id']."'");
		}

		return;
	}

	private function _generateControlCode($__personalData){
		$generatedControlCode = md5($__personalData['firstname'].$__personalData['email'].Zend_Registry::get('static')->geheim->salt.$__personalData['lastname'].time());

		return $generatedControlCode;
	}

	private function _calculateTotalPrice($__shoppingCart){
		$priceTotal = nook_Tool::calculateTotalPrice($__shoppingCart);

		return $priceTotal;
	}

	public function mailsToProgramSuppliers($__kundenId, $__personalData, $__shoppingCart){
		$__personalData['countryname'] = nook_Tool::findCountryName($__personalData['country']);

		$db = Zend_Registry::get('front');
		for($i=0; $i<count($__shoppingCart); $i++){

			$supplierData = $this->findSupplierData($db, $__shoppingCart[$i]);

			$suppliersMail = raintpl_rainhelp::getRainTpl();
			$suppliersMail->assign('kundenId', $__kundenId);
			$suppliersMail->assign('personalData', $__personalData);
			$suppliersMail->assign('supplierData', $supplierData);
			$suppliersMail->assign('shoppingCart', $__shoppingCart[$i]);
			$suppliersMail->assign('server', Zend_Registry::get('static')->server->server);
			$mailToSupplier = $suppliersMail->draw('supplierMail', true);

			$mail = new Zend_Mail('UTF-8');
			$mail->setBodyHtml($mailToSupplier);
			$mail->addTo($supplierData['email'], 'Kundenbestellung');
			//$mail->setFrom('tickets@aohostels.com', 'Herden Studienreisen');
            $mail->setFrom('kuehn.sebastian@gmail.com', 'Herden Studienreisen');
			$mail->setSubject('Bestellung eines Programmes');

			$control = $mail->send();
			if(empty($control))
				throw new nook_Exception($this->error_no_supplier_mail_send);
		}

		return;
	}

	public function findSupplierData($__db, $__shoppingCartRow){
		$sql = "
			SELECT
			    `tbl_programmdetails`.`email_programmanbieter` as email
			    , `tbl_adressenfa`.`Vorname`
			    , `tbl_adressenfa`.`Nachname`
			    , `tbl_adressenfa`.`Abteilung`
			    , `tbl_adressenfa`.`Titel`
			    , `tbl_adressenfa`.`Fa_ID`
			    , `tbl_adressenfa`.`Firma`
			    , `tbl_anrede`.`Anrede`
			FROM
			    `tbl_adressenfa`
			    INNER JOIN `tbl_programmdetails`
			        ON (`tbl_adressenfa`.`Fa_ID` = `tbl_programmdetails`.`Fa_ID`)
			    INNER JOIN `tbl_anrede`
			        ON (`tbl_adressenfa`.`Anrede` = `tbl_anrede`.`AnredeID`)
			WHERE (`tbl_adressenfa`.`Fa_ID` = '".$__shoppingCartRow['fa_id']."')";

			$supplierData = $__db->fetchRow($sql);
			return $supplierData;
	}

	public function mailWithVoucherAndBill($__kundenId, $__personalData, $__bankData, $__shoppingCart){
		$pdfVoucher = './_pdf/'.$__personalData['controlcode']."_voucher.pdf";

		if(!file_exists($pdfVoucher)) {
			throw new nook_Exception($this->error_controll_code_for_pdf_not_exists);
		}


		$__personalData['countryname'] = nook_Tool::findCountryName($__personalData['country']);

		$billMail = raintpl_rainhelp::getRainTpl();
		$billMail->assign('kundenId', $__kundenId);
		$billMail->assign('personalData', $__personalData);
		$billMail->assign('bankData', $__bankData);
		$priceTotal = $this->_calculateTotalPrice($__shoppingCart);
		$billMail->assign('priceTotal', $priceTotal);
		$billMail->assign('server', Zend_Registry::get('static')->server->server);

		$mailWithVoucherAndBill = $billMail->draw('billMail', true);

		$mail = new Zend_Mail('UTF-8');
		$mail->addTo($__personalData['email'], 'Ihre Rechnung');
		$mail->setBodyHtml($mailWithVoucherAndBill);
		//$mail->setFrom('tickets@aohostels.com', 'A&O Nebenleistungen');
        $mail->setFrom('kuehn.sebastioan@gmail.com', 'A&O Nebenleistungen');
		$mail->setSubject('Ihre Bestellung bei A&O Nebenleistungen');
		$control = $mail->send();
		if(empty($control))
			throw new nook_Exception($this->error_no_mail_send);
		return;
	}

}
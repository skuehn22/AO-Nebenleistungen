<?php
class Front_Model_Merkliste extends nook_Model_model{
	public $error_not_user = 90;
	public $error_not_correct_user_data = 91;
	
	private $_kundenId;
	private $_role_id;
	
	public function checkuser($__params){
		if(strlen($__params['password']) < 8)
			throw new nook_Exception($this->error_not_correct_user_data);
		
		$mailAdress = new Zend_Validate_EmailAddress();
		if(!$mailAdress->isValid($__params['username']))
			throw new nook_Exception($this->error_not_correct_user_data);
		
		$this->_findUserData($__params);
		$this->_correcturTableBuchungsnummerKundenId();
		
		return;
	}
	
	private function _findUserData($__params){
		$db = Zend_Registry::get('front');
		$sql = "
		SELECT *
			FROM
			    `tbl_adressen`
			WHERE (`email` = '".$__params['username']."'
			    AND `tbl_password` = '".$__params['password']."')";
		
		$userData = $db->fetchAll($sql);
        

		if(count($userData) != 1)
			throw new nook_Exception($this->error_not_user);
		
		$this->_setUserDataToSession($userData[0]);
		
		$this->_kundenId = $userData[0]['id'];
		$this->_role_id = $userData[0]['status'];
			
		return;
	}
	
	private function _setUserDataToSession($__userData){
		$sessionPersonaldata = new Zend_Session_Namespace('warenkorb');
		
		foreach($__userData as $key => $value){
			if($key == 'id')
				$key = 'kundenId';
				
			$sessionPersonaldata->$key = $value;
		}
		
		return;
	}
	
	private function _correcturTableBuchungsnummerKundenId(){
		$warenkorb = new Zend_Session_Namespace('warenkorb');
		$db = Zend_Registry::get('front');
		
		$update = array(
			"kunden_id" => $this->_kundenId
		);
		
		$db->update('tbl_buchungsnummer', $update, "session_id = '".Zend_Session::getId()."'");
		
		return;
	}
}
<?php
class Front_Model_WarenkorbStep3 extends nook_Model_model{
	
	public $error_no_personal_data = 60;
	public $error_no_captcha_answer_exist = 61;
	
	
	private $_data;

    /**
     * Holt aus der Tabelle 'tbl_adressen'
     * die Kundendaten
     *
     * @throws nook_Exception
     * @return
     */
	public function getPersonalData($__userId = false){

        if(empty($__userId))
            throw new nook_Exception($this->error_no_personal_data);

        $tabelleKunde = new Application_Model_DbTable_adressen(array('db' => 'front'));
        $personalData = $tabelleKunde->find($__userId)->toArray();

		return $personalData;
	}
	
	public function buildCaptcha(){
		$captcha = new Zend_Session_Namespace('captcha');
		$part1 =  rand(1,50);
		$captcha->captchaPart1 = $part1;
		$part2 = rand(1,9);
		$captcha->captchaPart2 = $part2;
		$captcha->captchaReply = $part1 + $part2;
		
		$captchaFrage = $part1." + ".$part2;
		
		return $captchaFrage;
	}
	
	public function evaluateCaptcha($__params){
		
		if(!array_key_exists('captchaAntwort', $__params))
			throw new nook_Exception($this->error_no_captcha_answer_exist);
		
		$captcha = new Zend_Session_Namespace('captcha');
		
		if($captcha->captchaReply != $__params['captchaAntwort'])
			return false;
		
		return true;
	}
	
	
}
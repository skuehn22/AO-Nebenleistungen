<?php

class Plugin_Language extends Zend_Controller_Plugin_Abstract {
	
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request){
    	$params = $request->getParams();
    	
    	$translate = new Zend_Session_Namespace('translate');
        $translate->module = $request->module;
        $translate->controller = $request->controller;
        $translate->action = $request->action;

    	if(array_key_exists('language_ger_x', $params)){
    		$language = 'de';
    	}
    	elseif(array_key_exists('language_eng_x', $params)){
    		$language = 'eng';
    	}
    	else{
    		$language = 'de';
    		if(!empty($translate->language))
    			$language = $translate->language;
    	}
        
    	Zend_Registry::set('language', $language);
    	$translate->language = $language;

    }
}

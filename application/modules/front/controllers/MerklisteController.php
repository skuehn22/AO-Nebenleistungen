<?php
/**
 * Darstellung der durch den Kunden
 * abgelegte Produkte im Warenkorb
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 * @subpackage Bausteinname
 */

class Front_MerklisteController extends Zend_Controller_Action{

    private $_realParams = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();
    }


    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();
    	
        try{
        	
        	if(array_key_exists('front_logout_x', $params)){
        		Zend_Session::destroy();
        	}
        	
        	if(array_key_exists('front_login_x', $params)){
	        	$model = new Front_Model_Merkliste();
	        	$model->checkUser($params);
        	}
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}
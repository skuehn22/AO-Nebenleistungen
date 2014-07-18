<?php
/**
 * Erneuert die User ID in 'tbl_buchungsnummer' für die aktuelle Session
 *
 * @author Stephan.Krauss
 * @date 20.43.2013
 * @file WarenkorbRefresh.php
 * @package plugins
 */
class Plugin_WarenkorbRefresh extends Zend_Controller_Plugin_Abstract {
	
	 public function preDispatch(Zend_Controller_Request_Abstract $request){

        /** @var $auth Zend_Session_Namespace */
        $auth = $auth = new Zend_Session_Namespace('Auth');
        $authParams = $auth->getIterator();

        // schreibt in 'tbl_buchungsnummer' zur Session ID die aktuelle User ID
        if(array_key_exists('userId', $authParams) and !empty($authParams['userId'])){
            /** @var $sessionId Zend_Session */
            $sessionId = Zend_Session::getId();

            $update = array();
            $update['kunden_id'] = $authParams['userId'];

            /** @var $db Zend_Db_Adapter_Pdo_Mysql */
            $db = Zend_Registry::get('front');
            $db->update('tbl_buchungsnummer', $update, "session_id = '".$sessionId."'");
        }

        return;
    }
}
?>

<?php
/**
* Stellt den Logout Button dar. Button erscheint nur bei den Rollen:
*
* + Bestimmt die Rolle des Benutzers.
*
* @date 10.10.2013
* @file Logout.php
* @package plugins
*/
class Plugin_Logout extends Zend_Controller_Plugin_Abstract {
	
	// Error
    private $_error = 1140;

    // Konditionen

    // Tabellen / Views


    /**
     * Bestimmt die Rolle des Benutzers.
     * Benutzer die
     * + User
     * + Anwärter
     * + Kunde sind
     *
     * können den Logout Button im Master Template verwenden
     *
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request){
        try{

            $logout = false;

            // Session Auth
            $auth = new Zend_Session_Namespace('Auth');
            $items = (array) $auth->getIterator();
            $items['role_id'] = (int) $items['role_id'];

            // Wenn Rolle stimmt
            if(!empty($items) and array_key_exists('role_id', $items) and $items['role_id'] > 1 and $items['role_id'] < 5){
                $logout = true;
            }

            Zend_Registry::set('logout', $logout);

            return;
        }
    	catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    	



    }
}

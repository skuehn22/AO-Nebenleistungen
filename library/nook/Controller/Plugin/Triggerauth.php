<?php
/**
 * Übermittelt die ID des Benutzers an die MySQL - Datenbanken
 *
 * + Möglichkeit der Verwendung der 'insert' und 'update' Trigger
 *
 * @author Stephan.Krauss
 * @date 16.01.13
 * @file Triggerauth.php
 */
class Plugin_Triggerauth extends Zend_Controller_Plugin_Abstract {
	
	// Error
    private $_error_eintragen_variable_fehlgeschlagen = 1130;

    // Konditionen


    /**
     * Setzt die Variable 'variableUserId' in den MySQL - Datenbanken
     *
     * @param Zend_Controller_Request_Abstract $request
     * @throws nook_Exception
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request){
        try{
            // Session Auth
            $auth = new Zend_Session_Namespace('Auth');
            $items = (array) $auth->getIterator();

            // baut SQL mit User ID
            if( (is_array($items)) and (array_key_exists('userId', $items)) ){
                $userId = (int) $items['userId'];

                $sql = "set @variableUserId = ".$userId;
            }
            else
                $sql = "set @variableUserId = 0";

            /** @var $db_front Zend_Db_Adapter_Mysqli */
            $db_front = Zend_Registry::get('front');
            $ergebnis = $db_front->query($sql);

            /** @var $db_hotels Zend_Db_Adapter_Mysqli */
            $db_hotels = Zend_Registry::get('hotels');
            $ergebnis = $db_hotels->query($sql);

            return;
        }
    	catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo 'Fehler';
            exit();
        }
    }
}

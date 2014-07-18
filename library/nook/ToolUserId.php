<?php
/**
* Bestimmung der UserId mittes SessionId
*
* + Bestimmen der UserId mittes
* + Ermittelt die Kunden ID
* + Kontrolliert ob der Benutzer angemeldet ist.
*
* @date 04.30.2013
* @file ToolUserId.php
* @package tools
*/
class nook_ToolUserId {

    /**
     * Bestimmen der UserId mittes
     * SessionId aus der Tabelle 'tbl_buchungsnummer'
     *
     */
    public static function bestimmeKundenIdMitSession(){

        $sessionId = Zend_Session::getId();

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array('db' => 'front'));
        $select = $tabelleBuchungsnummer->select();
        $select->where("session_id = '".$sessionId."'");

        $row = $tabelleBuchungsnummer->fetchRow($select);

        if($row == null or $row['kunden_id'] == null)
            $userId = false;
        else
            $userId = $row['kunden_id'];

        return $userId;
    }

    /**
     * Ermittelt die Kunden ID aus den Login - Daten der Session
     *
     */
    public static function kundenIdAusSession(){

        $session = new Zend_Session_Namespace('Auth');
        $sessionDaten = $session->getIterator();

        return $sessionDaten['userId'];
    }

    /**
     * Kontrolliert ob der Benutzer angemeldet ist.
     * Der Benutzer muss eine User ID und eine Rolle größer
     * als 'User' haben.
     *
     * @return bool
     */
    public static function kontrolleAnmeldung(){
        $userAngemeldet = false;

        $benutzerIdUnbekannt = 0;  // Definition
        $rolleUser = 1; // Definition

        $auth = new Zend_Session_Namespace('Auth');
        $authItems = $auth->getIterator();

        if( (!empty($authItems['userId'])) and ($authItems['userId'] > $benutzerIdUnbekannt) and ($authItems['role_id'] > $rolleUser) ){

            // ID des Benutzer nach Logout
            /** @var $static Zend_Config_Ini */
            $static = Zend_Registry::get('static');
            $maxId = $static->benutzer->maxId;

            if($authItems['userId'] == $maxId)
                $userAngemeldet = false;
            else
                $userAngemeldet = true;
        }

        return $userAngemeldet;
    }

} // end class

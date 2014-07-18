<?php
/**
 * 22.05.2012
 * Beschreibung der Klasse
 * statische Funktionen zum Handling
 * von Kundendaten
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 * @subpackage tool
 */

class nook_ToolKundendaten{

    public static $condition_kunde_nicht_angemeldet = 0;


    /**
     * Ermittelt die Kunden ID aus
     * Session / Auth.
     *
     * Die Kunden ID des Logout wird ausgeblendet !!!
     *
     * @static
     * @return int
     */
    static public function findKundenId(){
        $kundenId = 0;
        $auth = new Zend_Session_Namespace('Auth');
        $kundendaten = $auth->getIterator();

        if(!empty($kundendaten['userId']))
            $kundenId = $kundendaten['userId'];

        // ausblenden Kunden ID des Logout
        /** @var $static Zend_Config_Ini */
        $static = Zend_Registry::get('static');
        $maxId = $static->benutzer->maxId;

        if($kundendaten['userId'] == $maxId)
            $kundenId = self::$condition_kunde_nicht_angemeldet;


        return $kundenId;
    }
}
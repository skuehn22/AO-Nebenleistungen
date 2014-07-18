<?php
/**
 * 24.09.12 09:22
 *
 * Tool zum verschlüsseln des Passwortes
 * Verwendet 'static.ini'
 *
 * @author Stephan Krauß
 */

class nook_ToolVerschluesselungPasswort {

    /**
     * Verschlüsselung eines Passwortes
     *
     * @param $__passwort
     * @return string
     */
    public static function salzePasswort($__passwort){

        $static = Zend_Registry::get('static');
        $staticArray = $static->toArray();
        $gesalzenePasswort = md5($__passwort.$staticArray['geheim']['salt']);

        return $gesalzenePasswort;
    }

    /**
     * Gibt Salt - Wert
     * zur Passwortverschluesselung zurück
     *
     * @return mixed
     */
    public static function getSaltPasswort(){
        $static = Zend_Registry::get('static');
        $staticArray = $static->toArray();

        return $staticArray['geheim']['salt'];
    }



} // end class

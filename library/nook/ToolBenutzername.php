<?php
/**
 * Ermitteln Benutzername
 *
 * Ermittelt die Anrede, den Vornamen
 * und den Familienname.
 *
 * @author Stephan.Krauss
 * @date 08.04.13
 * @file ToolBenutzername.php
 * @package tools
 */
class nook_ToolBenutzername{


    /**
     * Ermittelt Benutzerdaten
     *
     * + Anrede
     * + Vorname
     * + Familienname
     *
     * @return string
     */
    public static function getBenutzerName(){

        $benutzername = '';

        $authRaw = new Zend_Session_Namespace('Auth');
        $authArray = (array) $authRaw->getIterator();

        if( !empty($authArray['userId'])){
            $tableAdressen = new Application_Model_DbTable_adressen();
            $personendaten = $tableAdressen->find($authArray['userId'])->toArray();

            $benutzername = "( ".$personendaten[0]['title']." ".$personendaten[0]['firstname']." ".$personendaten[0]['lastname']." )";
        }

        if($personendaten[0]['lastname'])
            return $benutzername;
        else
            return;
    }
}
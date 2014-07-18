<?php
/**
 * Ermittelt die Rolle eines Benutzers
 *
 * @author Stephan.Krauss
 * @date 16.28.2013
 * @file ToolBenutzerrolle.php
 * @package tools
 */
class nook_ToolBenutzerrolle{

    /**
     * Ermittelt die Rolle des Benutzers aus Session_Namespace('Auth');
     *
     * @param $__benutzerId
     * @return int
     */
    public static function getRolleDesBenutzers(){
        $rolle = 0;

        $authRaw = new Zend_Session_Namespace('Auth');
        if(!empty($authRaw->role_id)){
            $rolle = $authRaw->role_id;
        }

        return $rolle;
    }

    /**
     * Ermittelt die Rolle des Benutzers aus 'tbl_adressen'
     *
     * + wenn Benutzer unbekannt, return false
     * + gibt aus Tabelle 'tbl_adressen' den 'status' zurück
     *
     */
    public static function getRolleBenutzerTabelleAdressen($benutzerkennung, $passwort)
    {
        if(empty($benutzerkennung) or empty($passwort))
            return false;

        $whereEmail = "email = '".$benutzerkennung."'";
        $wherePasswort = "password = '".nook_ToolVerschluesselungPasswort::salzePasswort($passwort)."'";

        $cols = array(
            'status'
        );

        /** @var $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = new Application_Model_DbTable_adressen();
        $select = $tabelleAdressen->select();
        $select
            ->from($tabelleAdressen, $cols)
            ->where($whereEmail)
            ->where($wherePasswort);

        $rows = $tabelleAdressen->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            return false;

        return $rows[0]['status'];
    }

    /**
     * Ermittelt ob ein Offlinebucher angemeldet ist.
     *
     * + Wertet Session Namespace 'Auth' aus.
     * + superuser == 2, dann Offlinebucher
     * + superuser != 2, dann normale Buchung
     *
     * @return bool
     */
    public static function checkRolleOfflinebucher()
    {
        $authRaw = new Zend_Session_Namespace('Auth');
        $statusOfflinebucher = $authRaw->superuser;

        if($statusOfflinebucher == 2)
            return true;
        else
            return false;
    }

}
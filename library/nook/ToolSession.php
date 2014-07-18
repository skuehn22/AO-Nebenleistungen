<?php
/**
* Werkzeuge zum bearbeiten der Session
*
* + Kontrolliert ob der Warenkorb aktualisiert wurde.
* + kopiert den Inhalt der Session_Namespace('Auth), ändert die SessionId
* + Erstellt einer neuen Session.
* + Sessin ID in 'tbl_buchungsnummer'
* + Holt aus einem Namespace der Session die Variablen
* + Setzt Rolle ID und User ID im Namespace 'Auth' der Session
* + Ermittelt die Session ID der aktuellen Session
* + Updatet bzw. schreibt die Parameter eines Session Namespace
* + Ermittelt eine vorhandene Session ID mittels Buchungsnummer
* + Loescht den Namespace - Abschnitt einer Session
*
* @date 09.55.2013
* @file ToolSession.php
* @package tools
*/
class nook_ToolSession {

    /**
     * Kontrolliert ob der Warenkorb aktualisiert wurde.
     *
     * + false = Warenkorb wird nicht aktualisiert
     * + true = warenkorb wird aktualisiert
     *
     * @static
     * @param $__letzteAktualisierung
     * @return void
     */
    static public function warenkorbAktualisieren($__letzteAktualisierung){
        // Warenkorb wird nicht aktualisiert
        $kontrolle = false;

        $now = time();
        $warenkorbAktualisierungsZeitraum = Zend_Registry::get('static')->warenkorbaktualisierung->zeitraum;

        // wenn Warenkorb aktualisiert wird
        if( ($__letzteAktualisierung + $warenkorbAktualisierungsZeitraum) < $now)
            $kontrolle = true;

        return $kontrolle;
    }

    /**
     * kopiert den Inhalt der Session_Namespace('Auth), ändert die SessionId
     *
     * + legt Zend_Session_Namespace('Auth') an
     * + fügt Elemente der Authentifikation ein
     *
     * @static
     * @return
     */
    public static function erstelleNeueSessionKopiereAuth(){
        $auth = new Zend_Session_Namespace('Auth');
        $elementeAuth = $auth->getIterator();

        Zend_Session::regenerateId();
        $authNew = new Zend_Session_Namespace('Auth');

        foreach($elementeAuth as $key => $value){
            $authNew->$key = $value;
        }

        return;
    }

    /**
     * Erstellt einer neuen Session.
     *
     * @static
     * @return
     * @param bool / string $sessionId
     */
    public static function erstelleNeueSession()
    {
        Zend_Session::regenerateId();

        return;
    }

    /**
     * Sessin ID in 'tbl_buchungsnummer'
     *
     * + Ermittelt Session ID und trägt diese in 'tbl_buchungsnummer'.
     * + Wenn Kunden ID vorhanden, dann wird diese in 'tbl_buchungsnummer' eingetragen.
     *
     * @return int
     */
    public static function erstelleNeueSessionInBuchungstabelle(){

        $cols = array();

        $sessionId = Zend_Session::getId();
        $cols['session_id'] = $sessionId;

        $auth = new Zend_Session_Namespace('Auth');
        $authArray = (array) $auth->getIterator();

        if(!empty($authArray['userId']))
            $cols['kunden_id'] = $authArray['userId'];

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $anzahlDatensaetze = $tabelleBuchungsnummer->insert($cols);

        return $anzahlDatensaetze;
    }

    /**
     * Holt aus einem Namespace der Session die Variablen
     *
     * @param $sessionNamespaceName
     * @return array
     */
    public static function holeVariablenNamespaceSession($sessionNamespaceName){

        $namespace = new Zend_Session_Namespace($sessionNamespaceName);
        $sessionNamespaceVariablen = (array) $namespace->getIterator();

        return $sessionNamespaceVariablen;
    }

    /**
     * Setzt Rolle ID und User ID im Namespace 'Auth' der Session
     *
     * @param $__userId
     * @param $__roleId
     */
    public static function setzeAuthInNamespace($__userId, $__roleId)
    {
        $auth = new Zend_Session_Namespace('Auth');
        $auth->userId = $__userId;
        $auth->role_id = $__roleId;

        return;
    }

    /**
     * Ermittelt die Session ID der aktuellen Session
     *
     */
    public static function getSessionId(){

        $sessionId = Zend_Session::getId();

        return $sessionId;
    }

    /**
     * Updatet bzw. schreibt die Parameter eines Session Namespace
     *
     * @param $nameDesNamespaceDerSession
     * @param array $sessionNamespaceParameter
     */
    public static function setParamsInSessionNamespace($nameDesNamespaceDerSession, array $sessionNamespaceParameter){

        $namespace = new Zend_Session_Namespace($nameDesNamespaceDerSession);

        foreach($sessionNamespaceParameter as $key => $value){
            $namespace->$key = $value;
        }

        return;
    }

    /**
     * Ermittelt eine vorhandene Session ID mittels Buchungsnummer
     *
     * @param $buchungsnummer
     * @return mixed
     */
    public static function ermittelnVorhandeneSession($buchungsnummer)
    {
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $row = $tabelleBuchungsnummer->find($buchungsnummer);

        return $row['session_id'];
    }

    /**
     * Loescht den Namespace - Abschnitt einer Session
     *
     * @param $namespace
     */
    public function loescheNamespace($namespace)
    {
        if(Zend_Session::namespaceIsset($namespace)){
            Zend_Session::namespaceUnset($namespace);
        }

        return;
    }

} // end class

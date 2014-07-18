<?php
/**
 * Handling der Session. Übernahme der Session in die Datenbank. Umschreiben einer Session wenn eine Vormerkung vorliegt.
 *
 * + Übernimmt die ausgewählte Anzeigesprache
 *
 * @author Stephan.Krauss
 * @date 21.11.2013
 * @file Session.php
 * @package plugins
 */
class Plugin_Session extends Zend_Controller_Plugin_Abstract {

    protected $anzeigeSprache = null;

    /**
     * Kontrolliert ob eine Session als Vormerkung vorliegt
     *
     * + übernimmt die Session ID der Vormerkung
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $params = $request->getParams();

        // Prüft ob die Session entführt wurde
        // Notiz: noch einbauen
        // $this->sessionEntfuehrung();

        // wenn es eine Vormerkung ist
        if(array_key_exists('sessionIdVormerkung', $params)){

            $this->setzenAnzeigesprache($params['translate']);

            $sessionIdVormerkung = $params['sessionIdVormerkung'];
            $kundenId = $this->_kontrolleSessionId($sessionIdVormerkung); // Kontrolle Session ID

            // übernimmt die Session ID der Vormerkung
            Zend_Session::setId($sessionIdVormerkung);
        }

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        // setzt Adapter für Zend_Db_table
        Zend_Db_Table::setDefaultAdapter($db);

        $sessionConfig = array(
            'use_only_cookies' => true,
            'name' => 'tbl_sessions',
            'primary' => 'sess_id',
            'modifiedColumn' => 'sess_update',
            'lifetimeColumn' => 'sess_lifetime',
            'dataColumn' => 'sess_data'
        );

        Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($sessionConfig));

        Zend_Session::start();

        // Wenn eine Vormerkung gespeichert wird
        if(array_key_exists('sessionIdVormerkung', $params)){
            $this->inhaltSessionSetzen($sessionIdVormerkung, $kundenId);
        }
    }

    /**
     * Übernimmt die ausgewählte Anzeigesprache
     */
    private function speichernAnzeigesprache()
    {
        $sessionNamespaceTranslate = new Zend_Session_Namespace('translate');
        $variablenNamespace = $sessionNamespaceTranslate->getIterator();
        // $this->anzeigeSprache =

        return;
    }

    /**
     * Ermittelt den Fingerprint der Session und Kontrolle Fingerprint
     */
    private function sessionEntfuehrung()
    {

        return;
    }

    /**
     * Kontrolliert ob die Session ID in der Tabelle 'tbl_buchungsnummer' existiert
     *
     * @param $__sessionIdVormerkung
     * @throws Exception
     */
    private function _kontrolleSessionId($__sessionIdVormerkung){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        $sql = "
            SELECT
                kunden_id
            FROM
                tbl_buchungsnummer
            WHERE (session_id = '".$__sessionIdVormerkung."')";

        $ergebnis = $db->query($sql);

        if(empty($ergebnis))
            throw new Exception('Session falsch');

        $rows = $ergebnis->fetchAll();

        if(count($rows) <> 1)
            throw new Exception('Session falsch');

        return $rows[0]['kunden_id'];
    }

    /**
     * Setzt den Inhalt der Session
     *
     * + Übernahme der 'alten' Session Werte
     * + ergänzen der Werte der neuen Session um die Rolle
     *
     * @param $__sessionId
     * @return $this
     * @throws Exception
     */
    private function inhaltSessionSetzen($__sessionId, $kundenId){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        // ermitteln 'alte' Session Werte
        $sql = "select sess_data from tbl_buchungsnummer where session_id = '".$__sessionId."'";

        $ergebnis = $db->query($sql);
        $rows = $ergebnis->fetchAll();

        if(count($rows) <> 1)
            throw new Exception('keine Buchung vorhanden');

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');
        $sql = "select status from tbl_adressen where id = ".$kundenId;
        $ergebnis = $db->query($sql);
        $rows = $ergebnis->fetchAll();

        $auth = new Zend_Session_Namespace('Auth');
        $auth->role_id = $rows[0]['status'];
        $auth->userId = $kundenId;

        $translate = new Zend_Session_Namespace('translate');
        $translate->language = $this->anzeigeSprache;

        return $this;
    }

    /**
     * setzen Anzeigesprache in der neuen Session der Vormerkung
     */
    private function setzenAnzeigesprache($anzeigesprache)
    {
        $this->anzeigeSprache = $anzeigesprache;

        return;
    }

}

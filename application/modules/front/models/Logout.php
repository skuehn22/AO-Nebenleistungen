<?php
class Front_Model_Logout extends nook_Model_model{

    // Error
    private $_error = 1150;

    // Konditionen

    // Tabelle / Views
    private $_tabelleSessions = null;

	public function __construct(){
        /** @var _tabelleSessions Application_Model_DbTable_sessions */
        $this->_tabelleSessions = new Application_Model_DbTable_sessions();
	}

    /**
     * Meldet den Benutzer ab in dem
     * eine neue Session erstellt wird
     *
     * + setzen Sprache 'de'
     *
     * @return Front_Model_Logout
     */
    public function abmelden()
    {
        $toolSession = new nook_ToolSession();
        $toolSession->loescheNamespace('Auth');
        $toolSession->loescheNamespace('translate');
        $toolSession->loescheNamespace('portalbereich');
        $toolSession->loescheNamespace('programmsuche');
        $toolSession->loescheNamespace('hotelsuche');
        $toolSession->loescheNamespace('warenkorb');
        $toolSession->loescheNamespace('buchung');

        Zend_Session::regenerateId();

        // setzen Sprache 'de'
        $namespaceTranslate = new Zend_Session_Namespace('translate');
        $namespaceTranslate->language = 'de';

        return $this;
    }

    /**
     * Generiert die Logout Kunden ID
     * ID ist ein Fake
     *
     * @return mixed
     */
    private function _bestimmeLogoutKundenId(){

        return $maxKundenId = nook_ToolKonfiguration::getKonfigurationsVariable('benutzer', 'maxId');
    }
}
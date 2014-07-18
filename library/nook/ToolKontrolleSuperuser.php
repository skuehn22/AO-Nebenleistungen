<?php
/**
* Werkzeuge für den Superuser. Kontrolle ob der User ein Superuser ist.
*
* + Ruft _kontrolleSuperuserMitPasswort auf.
* + Ermittelt ob der User ein Superuser ist.
* + Eintragen Superuser in 'tbl_buchungsnummer'
* + Ermittelt Kunden ID aus Session
* + Eintragen Beziehung Superuser auf Kunde
* + Ordnet eine Buchung einem Superuser zu
*
* @author Stephan.Krauss
* @date 09.04.13
* @file ToolKontrolleSuperuser.php
* @package tools
*/
class nook_ToolKontrolleSuperuser {

    // Error
    private $_error_passwort_fehlerhaft = 1390;
    private $_error_kunden_id_nicht_vorhanden = 1391;

    // Conditions

    // Flag

    // Table / View
    private $_tabelleBuchungsnummer = null;
    private $_tabelleSuperuserKunden = null;
    private $_tabelleAdressenSuperuser = null;

    protected $_idSuperuser = null;
    protected $_idKunde = null;

    public function __construct(){
        /** @var _tabelleSuperuserKunden  */
        // $this->_tabelleSuperuserKunden = new Application_Model_DbTable_superuserKunden();
        /** @var _tabelleBuchungsnummer  */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabelleAdressenSuperuser Application_Model_DbTable_adressenSuperuser */
        $this->_tabelleAdressenSuperuser = new Application_Model_DbTable_adressenSuperuser();
    }

    /**
     * Ruft _kontrolleSuperuserMitPasswort auf.
     *
     * Kontrolliert den Inhalt des Passwortes
     *
     * @param $__passwort
     * @return nook_ToolKontrolleSuperuser
     * @throws nook_Exception
     */
    public function kontrolleSuperuserMitPasswort($__passwort){

        if(empty($__passwort))
            throw new nook_Exception($this->_error_passwort_fehlerhaft);

        $this->_kontrolleSuperuserMitPasswort($__passwort);

        return $this;
    }

    /**
     * Ermittelt ob der User ein Superuser ist.
     *
     * Kontrolliert das Passwort und gleicht mit
     * Eintrag in 'static.ini' ab.
     * Trägt in 'tbl_buchungsnummer' den Superuser ein.
     *
     * @param $__passwort
     * @return int
     */
    private function _kontrolleSuperuserMitPasswort($__passwort){

        $passwort = $__passwort;

        /** @var $staticData  */
        $staticData = Zend_Registry::get('static');
        $passwortSuperuser = $staticData->geheim->superuser;

        if($__passwort === $passwortSuperuser){
            $passwort = $passwortSuperuser;
            $idSuperuser = $staticData->geheim->idSuperuser;

            $this->_idSuperuser = $idSuperuser;
        }

        return $passwort;
    }

    /**
     * Eintragen Superuser in 'tbl_buchungsnummer'
     *
     * Kontrolliert ob Superuser registriert.
     * Ruft _zuordnungBuchungZuSuperuser auf.
     *
     * + bestimmen der Kunden ID
     * + Zuordnung Kunden ID in 'tbl_buchungsnummer' zu 'Superuser'
     * + Registrierung der Kunden ID zum Superuser in 'tbl_adressen_superuser'
     *
     * @return nook_ToolKontrolleSuperuser
     */
    public function zuordnungBuchungZuSuperuser(){

        if(empty($this->_idSuperuser))
            return;

        // bestimmen der Kunden ID
        $this->_bestimmeKundenId();

        // Zuordnung Kunden ID in 'tbl_buchungsnummer' zu 'Superuser'
        $this->_zuordnungBuchungZuSuperuser($this->_idSuperuser);

        // Registrierung der Kunden ID zum Superuser in 'tbl_adressen_superuser'
        $this->_registrierungKundeZuSuperuser($this->_idSuperuser);


        return $this;
    }

    /**
     * Ermittelt Kunden ID aus Session
     *
     * Kunde / Superuser ist angemeldet.
     * Identifikation Superuser über Passwort erfolgt.
     *
     * @throws nook_Exception
     */
    private function _bestimmeKundenId(){

        $kundenId = nook_ToolKundendaten::findKundenId();

        if(empty($kundenId))
            throw new nook_Exception($this->_error_kunden_id_nicht_vorhanden);

        $this->_idKunde = $kundenId;

        return;
    }

    /**
     * Eintragen Beziehung Superuser auf Kunde
     *
     * in 'tbl_adressen_superuser'
     *
     * @param $superuserId
     * @return int
     */
    private function _registrierungKundeZuSuperuser($superuserId)
    {
        $superuserId = (int) $superuserId;

        $cols = array(
            'adressen_id' => $this->_idKunde,
            'superuser_id' => $this->_idSuperuser
        );

        $this->_tabelleAdressenSuperuser->insert($cols);

        return $superuserId;
    }

    /**
     * Ordnet eine Buchung einem Superuser zu
     *
     * @param $__idUser
     * @param $__idSuperuser
     * @return int
     */
    private function _zuordnungBuchungZuSuperuser($__idSuperuser){

        $anzahlDatensaetze = 0;

        $sessionId = Zend_Session::getId();

        $cols = array(
            "superuser_id" => $__idSuperuser
        );

        $where = "session_id = '".$sessionId."'";

        $anzahlDatensaetze = $this->_tabelleBuchungsnummer->update($cols,$where);

        return $anzahlDatensaetze;
    }

} // end class

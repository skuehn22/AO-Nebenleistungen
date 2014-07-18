<?php
/**
 * Ermittelt Fingerprint der Session
 *
 * Zusätzliche Authentifikation des Users.
 * Absicherung der Session.
 *
 * @author Stephan.Krauss
 * @date 07.03.13
 * @file ToolSecureSession.php
 * @package tools
 */
class nook_ToolSecureSession {

    // Error
    private $_error_salt_string_zu_klein = 1310;
    private $_error_kein_bool_wert = 1311;
    private $_error_anzahl_ip_bloecke_falsch = 1312;
    private $_error_vergleich_fingerprint_falsch = 1313;
    private $_error_anzahl_datensaetze_falsch = 1314;

    // Tabellen , Views
    private $_tabelleSessions = null;

    // Konditionen
    private $_condition_mindest_laenge_salt = 8;

    // Flags

    // Soll der Browser überprüft werden
    protected $_check_browser = false;

    // Anzahl der Nummern der IP die im Fingerprint verwendet werden
    protected $_check_ip_blocks = 0;

    // Salt der Kontrolle
    protected $_strSalt = null;

    // berechneter Fingerprint
    protected $_fingerprint = null;

    // Session ID
    protected $_sessionId = null;

    public function __construct(){
        /** @var _tabelleSessions Application_Model_DbTable_sessions */
        $this->_tabelleSessions = new Application_Model_DbTable_sessions(array('db' => 'front'));
    }

    /**
     * @return nook_ToolSecureSession
     * @throws Exception
     */
    private function _startBerechneFingerprint(){

        if( (empty($this->_check_browser)) or (empty($this->_check_ip_blocks)) or (empty($this->_strSalt)) )
            throw new Exception("Werte fehlen");

        $this->_berechneFingerprint();

        return $this;
    }

    /**
     * Berechnet den md5 Fingerprint der Session
     *
     * @return string
     */
    private function _berechneFingerprint(){

        $fingerprint = '';

        if(!empty($this->_strSalt))
            $fingerprint .= $this->_strSalt;

        if ($this->_check_browser)
            $fingerprint .= $_SERVER['HTTP_USER_AGENT'];

        if ($this->_check_ip_blocks) {

            $num_blocks = abs(intval($this->_check_ip_blocks));

            if ($num_blocks > 4)
                $num_blocks = 4;

            $ipBlocks = explode('.', $_SERVER['REMOTE_ADDR']);

            for ($i = 0; $i < $num_blocks; $i++){
                $fingerprint .= $ipBlocks[$i] . '.';
            }
        }

        $this->_fingerprint = md5($fingerprint);

        return $this;
    }

    /**
     * @param bool $__checkBrowser
     * @return nook_ToolSecureSession
     * @throws nook_Exception
     */
    public function setCheckBrowser($__checkBrowser = false){

        if(!is_bool($__checkBrowser))
            throw new nook_Exception($this->_error_kein_bool_wert);

        $this->_check_browser = $__checkBrowser;

        return $this;
    }

    /**
     * Übernimmt den Salt Wert
     *
     * @param $__strSalt
     * @return nook_ToolSecureSession
     * @throws Exception
     */
    public function setSalt($__strSalt){

        if(strlen($__strSalt) < $this->_condition_mindest_laenge_salt)
            throw new Exception($this->_error_salt_string_zu_klein);

        $this->_strSalt = $__strSalt;

        return $this;
    }

    /**
     * @param $__numberIpBlocks
     * @return nook_ToolSecureSession
     * @throws nook_Exception
     */
    public function setIpBlocks($__numberIpBlocks){

        if(!is_int($__numberIpBlocks))
            throw new nook_Exception($this->_error_anzahl_ip_bloecke_falsch);

        $this->_check_ip_blocks = $__numberIpBlocks;

        return $this;
    }

    /**
     * Startet die Kontrolle des Fingerprint
     *
     * @return nook_ToolSecureSession
     */
    public function kontrolleFingerprint(){

        $this
            ->_startBerechneFingerprint()
            ->_eintragenUndKontrolleFingerprint();

        return $this;
    }

    /**
     * Kontrolle des Fingerprint
     *
     * Eintragen Fingerprint wenn in Tabelle
     * 'tbl_session' die Spalte fingerprint NULL.
     */
    private function _eintragenUndKontrolleFingerprint(){

        $this->_sessionId = Zend_Session::getId();
        $select = $this->_tabelleSessions->select();
        $select->where("sess_id = '".$this->_sessionId."'");

        $rows = $this->_tabelleSessions->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        // neuer Fingerprint eintragen
        if(empty($rows[0]['fingerprint']))
            $this->_eintragenNeuerFingerprint();
        else
            $this->_vergleichFingerprint($rows[0]['fingerprint']);

        return $this;
    }

    /**
     * Trägt den Fingerprint ein,
     * wenn Spalte 'fingerprint' = NULL
     *
     * @return nook_ToolSecureSession
     */
    private function _eintragenNeuerFingerprint(){

        $cols = array(
            'fingerprint' => $this->_fingerprint
        );

        $this->_tabelleSessions->update($cols, "sess_id = '".$this->_sessionId."'");

        return $this;
    }

    /**
     * Vergleicht den berechneten
     * Fingerprint mit dem
     * gespeicherten Fingerprint.
     * Wenn keine Übereinstimmung vorhanden wird
     * der Zugang verwehrt.
     *
     * @param $__gespeicherterFingerprint
     * @return nook_ToolSecureSession
     * @throws nook_Exception
     */
    private function _vergleichFingerprint($__gespeicherterFingerprint){

        if($this->_fingerprint != $__gespeicherterFingerprint)
            throw new nook_Exception($this->_error_vergleich_fingerprint_falsch);


        return $this;
    }

} // end class

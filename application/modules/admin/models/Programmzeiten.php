<?php
/**
* Verwaltung der Zeiten eines Programmes
*
* + Servicecontainer der Tabellen und Views
* + Übernimmt die Programmzeiten
* + Kontrolliert die ankommende Zeit
* +
* + Trägt die Programmzeiten in die
* + Speichert die Durchführungszeit des Programmes
* + Filtert die Zeiten und löscht diese
* + Ermitteln der vorhandenen Programmzeiten
* + Kappen der Zeitangabe auf Stunde und Minute
* + Gibt die Programmzeiten zurück
* + Ermittelt die Durchführungszeit
* + Ermittelt die Durchführungszeit
* + Aufsplitten der Zeit und umwandeln
* + Gibt den Typ der Programmzeiten zurück
* + Steuert die Ermittlung des Typ der Programmzeiten
* + Ermittelt den Typ der Öffnungszeiten eines Programmes
*
* @author Stephan.Krauss
* @date 02.05.13
* @file Programmzeiten.php
* @package admin
* @subpackage model
*/
class Admin_Model_Programmzeiten extends nook_Model_model
{

    // Tabelle / Views
    private $_tabelleProgrammdetailsZeiten = null;

    // Fehler
    private $_error_daten_unvollstaendig = 1440;
    private $_error_anzahl_datensaetze_stimmt_nicht = 1441;

    // Flags

    // Konditionen
    private $_condition_programmzeit_status_aktiv = 2;

    protected $pimple = null;
    protected $_programmdetailsId = null;
    protected $_programmzeiten = array();
    protected $_information = null;
    protected $_status = null;
    protected $typProgrammzeiten = null;

    /**
     * Servicecontainer der Tabellen und Views
     */
    private function servicecontainer()
    {
        if(empty($this->pimple)){
            /** @var $pimple Pimple_Pimple */
            $this->pimple = new Pimple_Pimple();
        }

        if(!$this->pimple->offsetExists('tabelleProgrammdetailsZeiten')){
            $this->pimple['tabelleProgrammdetailsZeiten'] = function(){
                return new Application_Model_DbTable_programmedetailsZeiten();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProgrammdetails')){
            $this->pimple['tabelleProgrammdetails'] = function(){
                return new Application_Model_DbTable_programmedetails();
            };
        }

    }

    /**
     * @param $eingabedaten
     * @return Admin_Model_Programmzeiten
     */
    public function setEingabedaten ($eingabedaten)
    {
        $this->_eingabedaten = $eingabedaten;

        return $this;
    }

    /**
     * @return array
     */
    public function getEingabedaten ()
    {
        return $this->_eingabedaten;
    }

    /**
     * Übernimmt die Programmzeiten
     *
     * @param $zeit
     * @return Admin_Model_Programmzeiten
     */
    public function setZeiten ($zeiten)
    {
        if(empty($this->_programmdetailsId)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        if(empty($this->_status)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        for($i = 0; $i < 11; $i++) {
            if(array_key_exists("programmzeit" . $i . "stunde", $zeiten) and array_key_exists("programmzeit" . $i . "minute",$zeiten)) {
                $this->_addZeiten($zeiten[ "programmzeit" . $i . "stunde" ], $zeiten[ "programmzeit" . $i . "minute" ]);
            }
        }

        return $this;
    }

    /**
     * Kontrolliert die ankommende Zeit
     *
     * und speichert diese im Array '_zeit'
     *
     * @param $stunde
     * @param $minute
     * @return bool|string
     */
    private function _addZeiten ($stunde, $minute)
    {

        $programmZeitArray = array();
        $programmzeit = $stunde . ":" . $minute;

        if(($stunde == 0) and ($minute == 0))
            return false;
        else{
            $programmZeitArray[ 'zeit' ] = $programmzeit;
            $programmZeitArray[ 'programmdetails_id' ] = $this->_programmdetailsId;
            $programmZeitArray[ 'status' ] = $this->_status;

            $this->_programmzeiten[ ] = $programmZeitArray;

            return $programmzeit;
        }

    }

    /**
     * @return time
     */
    public function getZeit ()
    {
        return $this->_programmzeiten;
    }

    /**
     * @param $programmzeiten
     * @return Admin_Model_Programmzeiten
     */
    public function setStatus ($programmzeiten)
    {
        $typProgrammzeiten = $programmzeiten['programmzeiten'];
        $this->_status = $typProgrammzeiten;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus ()
    {
        return $this->_status;
    }

    /**
     * @param $programmdetailsId
     * @return Admin_Model_Programmzeiten
     */
    public function setProgrammdetailsId ($programmdetailsId)
    {
        $this->_programmdetailsId = $programmdetailsId;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgrammdetailsId ()
    {
        return $this->_programmdetailsId;
    }

    /**
     * @param $information
     * @return Admin_Model_Programmzeiten
     */
    public function setInformation ($information)
    {
        $this->_information = $information;

        return $this;
    }

    /**
     * @return text
     */
    public function getInformation ()
    {
        return $this->_information;
    }

    /**
     * @param bool $programmzeit
     * @return bool
     */
    public function validateProgrammzeit ($programmzeit = false)
    {

        $programmzeit = trim($programmzeit);
        if(preg_match("#^([0-9]{2,2}:([0-9]{2,2}))$#", $programmzeit)) {
            return true;
        }

        return false;
    }

    /**
     * @param bool $programmDetailId
     * @return bool
     */
    public function validateProgrammdetailId ($programmDetailId = false)
    {

        $programmDetailId = trim($programmDetailId);
        $programmDetailId = (int) $programmDetailId;

        if(!empty($programmDetailId) and is_int($programmDetailId)) {
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function __construct ()
    {
        /** @var _tabelleProgrammdetailsZeiten Application_Model_DbTable_programmedetailsZeiten */
        $this->_tabelleProgrammdetailsZeiten = new Application_Model_DbTable_programmedetailsZeiten();
    }

    /**
     * Trägt die Programmzeiten in die
     *
     * Tabelle 'tbl_programmdetaile_zeiten'
     *
     * @return $this
     * @throws nook_Exception
     */
    public function insertProgrammzeiten ()
    {

        if(empty($this->_programmdetailsId)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        if(empty($this->_status)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $this->_insertProgrammzeiten();

        return $this;
    }

    /**
     * Speichert die Durchführungszeit des Programmes
     *
     * in 'tbl_programmdetails_zeiten'
     *
     * @return int
     *
     */
    private function _insertProgrammzeiten ()
    {
        $where = "programmdetails_id = " . $this->_programmdetailsId;
        $this->_tabelleProgrammdetailsZeiten->delete($where);

        $log = Zend_Registry::get('log');
        $log->log('Message: '.$message, 5);

        foreach($this->_programmzeiten as $programmzeit) {
            $kontrolle = $this->_tabelleProgrammdetailsZeiten->insert($programmzeit);
        }

        return $kontrolle;
    }

    /**
     * Filtert die Zeiten und löscht diese
     *
     * aus den übergebenen Werten
     *
     * @param $zeiten
     * @return array
     */
    public function reduceTermine ($zeiten)
    {

        $cleanDaten = array();

        foreach($zeiten as $key => $value) {
            if(!strstr($key, 'programmzeit')) {
                $cleanDaten[ $key ] = $value;
            }
        }

        return $cleanDaten;
    }

    /**
     * Ermitteln der vorhandenen Programmzeiten
     *
     * Rückgabe ist Array, wenn Programmzeiten vorhanden und Aktiv.
     * Wenn keine Programmzeiten vorhanden oder inaktiv = false
     *
     * @return array|bool
     */
    public function getProgrammzeitSelectBox ()
    {
        if(empty($this->_programmdetailsId)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        $programmzeiten = array();

        $programmzeitenRaw = $this->_ermittelnProgrammzeit();

        // wenn keine Programmzeit vorhanden
        if($programmzeitenRaw === false) {
            return;
        }

        for($i = 0; $i < count($programmzeitenRaw); $i++) {
            $programmzeiten[ ] = $this->_formatZeitangabe($programmzeitenRaw[ $i ]);
        }

        return $programmzeiten;
    }

    /**
     * Kappen der Zeitangabe auf Stunde und Minute
     *
     * @param array $zeitRaw
     * @return string
     */
    private function _formatZeitangabe (array $zeitRaw)
    {

        $zeit[ 'zeit' ] = nook_ToolZeiten::kappenZeit($zeitRaw[ 'zeit' ], 2);

        return $zeit;
    }

    /**
     * Gibt die Programmzeiten zurück
     *
     * + Wenn keine programmzeit, dann return false
     *
     * @return array|bool
     */
    private function _ermittelnProgrammzeit ()
    {
        $cols = (
            'zeit'
        );

        $whereProgrammId = "programmdetails_id = " . $this->_programmdetailsId;

        $select = $this->_tabelleProgrammdetailsZeiten->select();
        $select
            ->from($this->_tabelleProgrammdetailsZeiten, $cols)
            ->where($whereProgrammId)
            ->order("zeit ASC");

        $programmzeiten = $this->_tabelleProgrammdetailsZeiten->fetchAll($select)->toArray();

        if(count($programmzeiten) == 0) {
            return false;
        } else {
            return $programmzeiten;
        }
    }

    /**
     * Ermittelt die Durchführungszeit
     * eines Programmes
     *
     * @return array
     * @throws nook_Exception
     */
    public function getProgrammzeit ()
    {

        $aktuelleProgrammzeit = array();

        if(empty($this->_programmdetailsId)) {
            throw new nook_Exception($this->_error_daten_unvollstaendig);
        }

        // ermitteln Programmzeiten
        $programmzeiten = $this->_getProgrammzeit();

        if($this->_status == 2) {
            $programmzeiten[ 'programmzeiten' ] = 'programmzeiten';
        } else {
            $programmzeiten[ 'programmzeiten' ] = 'kundenzeit';
        }

        return $programmzeiten;
    }

    /**
     * Ermittelt die Durchführungszeit
     *
     * eines Programmes. Aufsplitten der Stunden und Minuten
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function _getProgrammzeit ()
    {

        $cols = array(
            new Zend_Db_Expr("DATE_FORMAT(zeit,'%H:%i') as zeit"),
            'status'
        );

        $select = $this->_tabelleProgrammdetailsZeiten->select();
        $select
            ->from($this->_tabelleProgrammdetailsZeiten, $cols)
            ->where("programmdetails_id = " . $this->_programmdetailsId);

        $programmzeiten = array();
        $programmzeitenRaw = $this->_tabelleProgrammdetailsZeiten->fetchAll($select)->toArray();

        // setzen Status
        $this->_status = $programmzeitenRaw[ 0 ][ 'status' ];

        for($i = 0; $i < count($programmzeitenRaw); $i++) {
            $programmzeit = $this->_splitProgrammzeiten($i, $programmzeitenRaw[ $i ]);
            $programmzeiten = array_merge($programmzeiten, $programmzeit);
        }

        return $programmzeiten;
    }

    /**
     * Aufsplitten der Zeit und umwandeln
     *
     * in Variablen des Formular
     *
     * @param $i
     * @param array $zeitRaw
     */
    private function _splitProgrammzeiten ($i, array $zeitRaw)
    {

        $programmzeit = array();
        $i++;

        $teileProgrammzeit = explode(':', $zeitRaw[ 'zeit' ]);

        $programmzeit[ 'programmzeit' . $i . 'stunde' ] = $teileProgrammzeit[ 0 ];
        $programmzeit[ 'programmzeit' . $i . 'minute' ] = $teileProgrammzeit[ 1 ];

        return $programmzeit;
    }

    /**
     * Gibt den Typ der Programmzeiten zurück
     *
     * @return int
     */
    public function getTypProgrammzeiten()
    {
        return $this->typProgrammzeiten;
    }

    /**
     * Steuert die Ermittlung des Typ der Programmzeiten
     *
     * @return Admin_Model_Programmzeiten
     * @throws nook_Exception
     */
    public function steuerungErmittlungTypProgrammzeiten()
    {
        $this->servicecontainer();

        if(empty($this->_programmdetailsId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->ermittlungTypProgrammzeiten();

        return $this;
    }

    /**
     * Ermittelt den Typ der Öffnungszeiten eines Programmes
     *
     * @throws nook_Exception
     */
    private function ermittlungTypProgrammzeiten()
    {
        /** @var $tabelleProgrammdetailsZeiten Application_Model_DbTable_programmedetailsZeiten */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $rows = $tabelleProgrammdetails->find($this->_programmdetailsId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);

        $this->typProgrammzeiten = $rows[0]['typOeffnungszeiten'];

        return;
    }

}
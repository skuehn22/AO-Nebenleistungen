<?php
/**
* Verwaltet die Programmkapazitäten
*
* + Gibt die Kondition des Status zurück;
* + Holt die Kapazitaet des Programmes. Unterscheidet wenn nötig zwischen Tageskapazitaet und Standardkapazitet.
* + Übernimmt die erste Tageskapazität
* + Ermittelt Standardkapazität
* + Übertrag der
* + Verändert die Tageskapazitaet eines Programmes
* + Verringert die Tageskapazität eines Programmes an einem betimmten Tag
* + Kontrolliert Datumsformat und wandelt dieses bei Bedarf. Ausgabe nach ISO 8601
*
* @date 26.28.2013
* @file ProgrammdetailKapazitaetManager.php
* @package front
* @subpackage model
*/
class Front_Model_ProgrammdetailKapazitaetManager extends nook_ToolModel implements Front_Model_ProgrammdetailKapazitaetManagerInterface
{
    // Tabellen / Views / Datenbanken
    private $_tabelleProgrammdetails = null;
    private $_tabelleProgrammdetailsKapazitaet = null;

    // Fehler
    private $_error_kein_int = 1290;
    private $_error_falsches_datum = 1291;
    private $_error_werte_unvollstaendig = 1292;
    private $_error_anzahl_datensaetze_falsch = 1293;
    private $_error_momentane_kapazitaet_kleiner_null = 1294;

    // Konditionen
    private $_condition_dummy_kapazitaet = 1000;
    private $condition_keine_ausreichende_kapazitaet = 1;
    private $condition_ausreichend_kapazitaet_vorhanden = 2;

    // Flags

    protected $_programmId = null;
    protected $_datum = null;

    protected $anzahlProgrammbuchungen = 0;

    protected $programmKapazitaet = null;
    protected $veraenderungProgrammKapazitaet = null;

    public function __construct(){
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleProgrammdetailsKapazitaet Application_Model_DbTable_programmdetailsKapazitaet */
        $this->_tabelleProgrammdetailsKapazitaet = new Application_Model_DbTable_programmdetailsKapazitaet();
    }

    /**
     * @param $__programmId
     * @return Front_Model_ProgrammdetailKapazitaetManager
     * @throws nook_Exception
     */
    public function setProgrammId($__programmId){

        $programmId = (int) $__programmId;
        if($programmId == 0)
            throw new nook_Exception($this->_error_kein_int);

        $this->_programmId = $programmId;

        return $this;
    }

    /**
     * @param $veraenderungProgrammKapazitaet
     * @return Front_Model_ProgrammdetailKapazitaetManager
     */
    public function setVeraenderungProgrammKapazitaet($veraenderungProgrammKapazitaet)
    {
        $veraenderungProgrammKapazitaet = (int) $veraenderungProgrammKapazitaet;
        $this->veraenderungProgrammKapazitaet = $veraenderungProgrammKapazitaet;

        return $this;
    }

    /**
     * @param $anzahlProgrammbuchungen
     * @return Front_Model_ProgrammdetailKapazitaetManager
     */
    public function setAnzahlProgrammbuchungen($anzahlProgrammbuchungen = 0)
    {
        $anzahlProgrammbuchungen = (int) $anzahlProgrammbuchungen;
        $this->anzahlProgrammbuchungen = $anzahlProgrammbuchungen;

        return $this;
    }

    /**
     * Gibt die Kondition des Status zurück;
     *
     * + 1 keine ausreichende Kapazität
     * + 2 ausreichende Kapazität
     *
     * @return int
     */
    public function getStatusKapazitaet()
    {
        $programmKapazitaet = (int) $this->programmKapazitaet;

        if($this->anzahlProgrammbuchungen > $programmKapazitaet)
            return $this->condition_keine_ausreichende_kapazitaet;
        else
            return $this->condition_ausreichend_kapazitaet_vorhanden;
    }

    /**
     * @param $__datum
     * @return Front_Model_ProgrammdetailKapazitaetManager
     * @throws nook_Exception
     */
    public function setDatum($__datum){

        $datumsKontrole = new Zend_Validate_Date(array('format' => 'yyyy-mm-dd'));

        if(!$datumsKontrole->isValid($__datum))
            throw new nook_Exception($this->_error_falsches_datum);

        $this->_datum = $__datum;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgrammKapazitaet()
    {
        return $this->programmKapazitaet;
    }

    /**
     * Holt die Kapazitaet des Programmes. Unterscheidet wenn nötig zwischen Tageskapazitaet und Standardkapazitet.
     *
     * + Tageskapazität
     * + Standard Kapazität des Programmes
     *
     * @return Front_Model_ProgrammdetailKapazitaetManager
     * @throws nook_Exception
     */
    public function berechneProgrammkapazitaet()
    {
        if( (empty($this->_programmId)) or (empty($this->_datum)) )
            throw new nook_Exception($this->_error_werte_unvollstaendig);

        // Tageskapazität
        $this->_berechneTagesKapazitaet();

        // Standardkapazität
        if( (empty($this->programmKapazitaet)) and ($this->programmKapazitaet !== 0) )
            $this->_berechneStandardKapazitaetProgramm();

        return $this;
    }

    /**
     * Übernimmt die erste Tageskapazität. Zeitkapazitäten noch nicht eingebaut !!!
     *
     * @return bool
     */
    private function _berechneTagesKapazitaet(){
        $kapazitaet = false;

        $cols = array(
            'kapazitaet'
        );

        $whereDatum = " datum BETWEEN '".$this->_datum."' and date_add('".$this->_datum."', INTERVAL 1 DAY)";

        $select = $this->_tabelleProgrammdetailsKapazitaet->select();
        $select
            ->from($this->_tabelleProgrammdetailsKapazitaet, $cols)
            ->where("programmdetails_id = ".$this->_programmId)
            ->where($whereDatum);

        $rows = $this->_tabelleProgrammdetailsKapazitaet->fetchAll($select)->toArray();
        if(count($rows) > 0)
            $this->programmKapazitaet = $rows[0]['kapazitaet'];

        return;
    }

    /**
     * Ermittelt Standardkapazität des Programmes
     *
     * + ermitteln Standardkapazität
     * + angenommene Kapazität wenn keine Standardkapazität
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function _berechneStandardKapazitaetProgramm()
    {
        $whereProgrammdetailsId = "id = ".$this->_programmId;

        $select = $this->_tabelleProgrammdetails->select();
        $select
            ->where($whereProgrammdetailsId);

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();

        //  Anzahl Programmdatensätze
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        // angenommene Kapazität wenn keine Standardkapazität
        if( (empty($rows[0]['kapazitaet'])) and ($rows[0]['kapazitaet'] !== 0) )
            $rows[0]['kapazitaet'] = $this->_condition_dummy_kapazitaet;

        $this->programmKapazitaet = $rows[0]['kapazitaet'];

        return $rows[0]['kapazitaet'];
    }

    /**
     * Übertrag der Standardkapazität in 'tbl_programmdetails_kapazitaet'
     *
     *
     *
     * @param $__kapazitaet
     * @return Front_Model_ProgrammdetailKapazitaetManager
     */
    private function _eintragenStandardkapazitaetFuerEinenTag($__kapazitaet){

        $insert = array(
            'programmdetails_id' => $this->_programmId,
            'datum' => $this->_datum,
            'kapazitaet' => $__kapazitaet
        );

        $this->_tabelleProgrammdetailsKapazitaet->insert($insert);

        return $this;
    }

    /**
     * Verändert die Tageskapazitaet eines Programmes
     *
     * + Kontrolle der benötigten Werte
     * + Verringerung der Tageskapazität
     *
     * @return Front_Model_ProgrammdetailKapazitaetManager
     */
    public function aendernTageskapatitaetEinesProgrammes()
    {
        // Kontrolle Ausgangswerte
        if(empty($this->_programmId))
            throw new nook_Exception($this->_error_werte_unvollstaendig);

        if(empty($this->_datum))
            throw new nook_Exception($this->_error_werte_unvollstaendig);

        if(empty($this->veraenderungProgrammKapazitaet) and $this->veraenderungProgrammKapazitaet != 0)
            throw new nook_Exception($this->_error_werte_unvollstaendig);

        $this->verringerungTageskapazitaetProgramm();

        return $this;
    }

    /**
     * Verringert die Tageskapazität eines Programmes an einem betimmten Tag
     *
     * + Kontrolliert das die Kapazität nicht kleiner 0
     *
     */
    private function verringerungTageskapazitaetProgramm()
    {
        $query = "UPDATE tbl_programmdetails_kapazitaet SET kapazitaet = kapazitaet - ".$this->veraenderungProgrammKapazitaet." WHERE programmdetails_id = ".$this->_programmId." AND datum = '".$this->_datum."'";

        /** @var $db_groups Zend_Db_Adapter_Mysqli */
        $db_groups = Zend_Registry::get('front');
        $veraenderungAnzahl = $db_groups->query($query);

        // wenn eine Tageskapazität verändert wurde
        if($veraenderungAnzahl){
            $momentaneKapazitaet = $this->berechneProgrammkapazitaet()->getProgrammKapazitaet();

            if($momentaneKapazitaet < 0)
                throw new nook_Exception($this->_error_momentane_kapazitaet_kleiner_null);
        }

        return;
    }

    /**
     * Kontrolliert Datumsformat und wandelt dieses bei Bedarf. Ausgabe nach ISO 8601
     *
     * + deutsches Datum
     * + englisches Datum
     * + amerikanisches Datum
     *
     * @param $__datum
     * @return mixed
     */
    public function  mapDatum($__datum){

        // deutsches Datum
        if(strstr($__datum, ".")){
            $teileDatum = explode(".",$__datum);
            $datum = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
        }
        // englisches Datum
        elseif(strstr($__datum,"/")){
            $teileDatum = explode("/",$__datum);
            $datum = $teileDatum[2]."-".$teileDatum[0]."-".$teileDatum[1];
        }
        // amerikanisches Datum
        else
            $datum = $__datum;

        return $datum;
    }

} // end class

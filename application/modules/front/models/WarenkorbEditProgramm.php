<?php
class Front_Model_WarenkorbEditProgramm{

	// Fehler
    private $_error_programmbuchung_gehoert_nicht_den_user = 850;
    private $_error_anzahl_datensaetze_stimmt_nicht = 851;

    // Konditionen
    private $_condition_sprache_deutsch = 1;
    private $_condition_sprache_englisch = 2;
    private $_condition_bereich_programme = 1;

    // Tabellen / Views
    private $_tabelleProgrammbuchung = null;
    private $_tabelleBuchungsnummer = null;
    private $_tabelleProgrammbeschreibung = null;
    private $_tabellePreiseBeschreibung = null;


    private $_preisvariante = array();
    private $_programmbuchung = array();

    public function __construct(){
        /** @var $_tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array('db' => 'front'));
        /** @var $_tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array('db' => 'front'));
        /** @var $_viewWarenkorbEditProgrammvariante Application_Model_DbTable_viewWarenkorbEditProgrammvariante */
        $this->_viewWarenkorbEditProgrammvariante = new Application_Model_DbTable_viewWarenkorbEditProgrammvariante(array('db' => 'front'));
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();
        /** @var _tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
        $this->_tabellePreiseBeschreibung = new Application_Model_DbTable_preiseBeschreibung();
    }

    /**
     * Kontrolliert ob die gebuchte Preisvariante zur Session gehört.
     * Speichert die Daten der Programmbuchung ab
     *
     * @throws nook_Exception
     * @param $__idTabelleProgrammbuchung
     * @return Front_Model_WarenkorbEditProgramm
     */
    public function kontrolleIdTabelleProgrammbuchung($__programmbuchungId){

        $buchungsNummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $select = $this->_tabelleProgrammbuchung->select();
        $select
            ->where("buchungsnummer_id = ".$buchungsNummer)
            ->where("id = ".$__programmbuchungId);

        $rows = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_programmbuchung_gehoert_nicht_den_user);
        else
            $this->_programmbuchung = $rows[0];

        return $this;
    }

    /**
     * Findet die Daten der Preisvariante zu einer Programmbuchung
     *
     * @return Front_Model_WarenkorbEditProgramm
     */
    public function findeWerteProgrammbuchung(){

        $datenGebuchtesProgramm = array();

        $programmbuchung = $this->_programmbuchung;
        $datenGebuchtesProgramm['programmbuchungId'] = $programmbuchung['id'];

        // ermitteln Kennziffer Anzeigesprache
        $kennzifferAnzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        // ermitteln Programmname
        $cols = array(
            'progname'
        );

        $select = $this->_tabelleProgrammbeschreibung->select();
        $select
            ->from($this->_tabelleProgrammbeschreibung, $cols)
            ->where("programmdetail_id = ".$programmbuchung['programmdetails_id'])
            ->where("sprache = ".$kennzifferAnzeigesprache);

        $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);

        $datenGebuchtesProgramm['id'] = $programmbuchung['programmdetails_id'];
        $datenGebuchtesProgramm['anzahl'] = $programmbuchung['anzahl'];
        $datenGebuchtesProgramm['progname'] = $rows[0]['progname'];
        $datenGebuchtesProgramm['zeit'] = $programmbuchung['zeit'];


        // Datum
        // deutsche Anzeigesprache
        if($kennzifferAnzeigesprache == 1)
            $programmbuchung['datum'] = nook_ToolDatum::wandleDatumEnglischInDeutsch($programmbuchung['datum']);

        $datenGebuchtesProgramm['datum'] = $programmbuchung['datum'];

        // Preisvariante
        $cols = array(
            'preisvariante'
        );

        // Beschreibung der Preisvariante
        $select = $this->_tabellePreiseBeschreibung->select();
        $select
            ->from($this->_tabellePreiseBeschreibung, $cols)
            ->where("sprachen_id = ".$kennzifferAnzeigesprache)
            ->where("preise_id = ".$programmbuchung['tbl_programme_preisvarianten_id']);

        $rows = $this->_tabellePreiseBeschreibung->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);

        $datenGebuchtesProgramm['preisvariante'] = $rows[0]['preisvariante'];

        $this->_preisvariante = $datenGebuchtesProgramm;

        return $this;
    }

    /**
     * Gibt die Werte der Preisvariante
     *
     * @return array
     */
    public function getPreisvariante(){
        return $this->_preisvariante;
    }

    /**
     * Verändert die Anzahl der gebuchten preisvarianten.
     * Ist die Anzahl == 0, dann wird die Preisvariante gelöscht.
     *
     * @return Front_Model_WarenkorbEditProgramm
     */
    public function updatePreisvariante($__neueAnzahl){

        $__neueAnzahl = (int) $__neueAnzahl;

        // Daten Programmbuchung
        $programmbuchung = $this->_programmbuchung;

        /** @var $modelCartProgramme Front_Model_CartProgramme */
        $modelCartProgramme = new Front_Model_CartProgramme();

        if($__neueAnzahl === 0)
            $modelCartProgramme->deleteItemWarenkorb($this->_condition_bereich_programme, $programmbuchung['id']);
        else{
            $this->_updateAnzahlPreisvariante($programmbuchung, $__neueAnzahl);
            $this->_updateXmlBlock($programmbuchung);
        }

        return $this;
    }

   /**
     * Verändert die Anzahl der Preisvarianten in der Tabelle 'tbl_programmbuchung'.
     * Schreibt den XML Buchungsblock der Programmbuchung neu.
     * @param array $__programmbuchung
     * @param $__neueAnzahl
     * @return
     */
   private function _updateAnzahlPreisvariante(array $__programmbuchung, $__neueAnzahl){

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->_tabelleProgrammbuchung;

        $cols = array(
            'anzahl' => $__neueAnzahl
        );

        $where = "id = ".$__programmbuchung['id'];

        $tabelleProgrammbuchung->update($cols, $where);

        return;
   }

    private function _updateXmlBlock($__programmbuchung){
        /** @var $modelXML Front_Model_ProgrammdetailXml */
        $modelXML = new Front_Model_ProgrammdetailXml();

        $modelXML
            ->setBuchungsnummerId($__programmbuchung['buchungsnummer_id'])
            ->setProgrammbuchungId($__programmbuchung['fa_id'])
            ->setAnzeigesprache(nook_ToolSprache::getAnzeigesprache())
            ->startSaveXmlBuchungsdatenProgramm();

        return;
    }

}
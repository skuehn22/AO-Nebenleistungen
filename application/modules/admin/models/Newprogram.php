<?php
class Admin_Model_Newprogram extends nook_Model_model{
	private $_db_front;
    private $_pageRows = 20;

    public $searchField = false;
    public $fieldErrors = false;

    private $_condition_anbieter_programme = 1;
    private $_condition_anbieter_ist_aktiv = 3;
    private $_condition_sprache_deutsch = 1;
    private $_condition_sprache_englisch = 2;

    private $_tabelleProgrammdetails = null;
    private $_tabelleProgrammdetailsZeiten = null;
    private $_tabelleProgrammbeschreibung = null;
    private $_tabelleReception = null;
	
	private $_error_fehler_eingabe_daten = 370;

	public function __construct(){
		$this->_db_front = Zend_Registry::get('front');
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();
        /** @var _tabelleProgrammdetailsZeiten Application_Model_DbTable_programmedetailsZeiten */
        $this->_tabelleProgrammdetailsZeiten = new Application_Model_DbTable_programmedetailsZeiten();
        /** @var _tabelleReception Application_Model_DbTable_reception */
        $this->_tabelleReception = new Application_Model_DbTable_reception();

		return;
	}

    /**
     * Bestimmt die Anzahl
     * der vorhandenen Firmen
     *
     * @return mixed
     */
    public function getCountCompanies(){
        $sql = "SELECT
           count(id) as anzahl
        FROM
            tbl_adressen
        WHERE (anbieter = ".$this->_condition_anbieter_programme."
            and company is not null
            AND aktiv = ".$this->_condition_anbieter_ist_aktiv.")";

        if(!empty($this->searchField))
            $sql .= " and company like '%".$this->searchField."%'";

        $count = $this->_db_front->fetchOne($sql);

        return $count;
	}
	
	public function getCompanies($__start, $__limit){
        $start = 0;
		$limit = $this->_pageRows;

		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}

        $sql = "SELECT
            id
            , company
            , city
            , aktiv
        FROM
            tbl_adressen
        WHERE (anbieter = ".$this->_condition_anbieter_programme."
            and company is not null
            AND aktiv = ".$this->_condition_anbieter_ist_aktiv.")";

        if(!empty($this->searchField))
            $sql .= " and company like '%".$this->searchField."%'";

        $sql .= " order by company asc limit ".$start.", ".$limit;

        $result = $this->_db_front->fetchAll($sql);

        return $result;
	}

    public function getCities(){
        $cities = new nook_Standardstore();
        $cities = $cities->getCities();

        return $cities;
    }

    /**
     * legt ein neues Programm an
     *
     * @param $__params
     */
    public function saveNewProgram($__params){
        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        $this->_controlFormInput($__params);
        if(!empty($this->fieldErrors)){

            return;
        }

        // speichern des neuen Programmes
        $this->_saveProgramInDatabase($__params);

        return;
    }

    /**
     * Speichert das neue Programm
     *
     * @param $__params
     */
    private function _saveProgramInDatabase($__params){

        // Tabelle 'tbl_programmdetails'
        $colsProgrammdetails = array(
            'adressen_id' => $__params['id'],
            'AO_City' => $__params['city']
        );

        $programmdetailId = $this->_tabelleProgrammdetails->insert($colsProgrammdetails);
        $programmdetailId = (int) $programmdetailId;

        // sprachversionen in Tabelle 'tbl_programmbeschreibung'
        if(!empty($programmdetailId) and is_int($programmdetailId)){

            // anlegen der Programmbeschreibung
            $this->_anlegenProgrammbeschreibung($__params, $programmdetailId);

            // anlegen der ersten Durchführungszeit des Programmes
            $this->_anlegenProgrammzeit($programmdetailId);

            // anlegen einer Zeile in 'tbl_reception'
            $this->anlegenZeileZusatzinformationReception($programmdetailId);

        }
        else
            throw new nook_Exception($this->_error_fehler_eingabe_daten);


        return;
    }

    /**
     * Anlegen einer neuen Zeile in der Tabelle 'tbl_reception'
     *
     * @param $programmdetailId
     * @throws nook_Exception
     */
    protected function anlegenZeileZusatzinformationReception($programmdetailId)
    {
        $cols = array(
            'programmdetails_id' => $programmdetailId
        );

        $kontrolle = $this->_tabelleReception->insert($cols);

        if(!$kontrolle)
            throw new nook_Exception("anlegen der Zusatzinformation für Reception in 'tbl_reception' fehlgeschlagen");

        return;
    }

    /**
     * Legt die erste Durchführungszeit des Programmes an
     *
     * @param $programmdetailId
     */
    private function _anlegenProgrammzeit ($programmdetailId)
    {
        $cols = array(
            'programmdetails_id' => $programmdetailId
        );

        $this->_tabelleProgrammdetailsZeiten->insert($cols);

        return;
    }

    /**
     * Legt die Programmbeschreibung eines Programmes an
     *
     * @param $__params
     * @param $programmdetailId
     */
    private function _anlegenProgrammbeschreibung ($__params, $programmdetailId)
    {
// deutsche Sprache
        $colsProgrammbeschreibungDeutsch = array(
            "programmdetail_id" => $programmdetailId,
            "progname"          => $__params[ 'programDe' ],
            "sprache"           => $this->_condition_sprache_deutsch
        );

        $this->_tabelleProgrammbeschreibung->insert($colsProgrammbeschreibungDeutsch);

        // englische Sprache
        $colsProgrammbeschreibungEnglisch = array(
            "programmdetail_id" => $programmdetailId,
            "progname"          => $__params[ 'programEn' ],
            "sprache"           => $this->_condition_sprache_englisch
        );

        $this->_tabelleProgrammbeschreibung->insert($colsProgrammbeschreibungEnglisch);

        return;
    }

    private function _controlFormInput($__params){
        $filters = $this->_buildFilters();
        $validators = $this->_buildValidators();

        $control = new Zend_Filter_Input($filters, $validators, $__params);
        $errors = $control->getErrors();

        if(!$control->isValid()){
            $errors = $control->getErrors();
            $errorMessages = new nook_Standardformerrors($errors);
            $this->fieldErrors = $errorMessages->getErrorMessages();
        }

        return;
    }

    private function _buildFilters(){
        $filters = array(
            'city' => 'Int',
            'programDe' => 'StringTrim',
            'programEn' => 'StringTrim'
        );

        return $filters;
    }

    private function _buildValidators(){
        $validators = array(
            'city' => array(
                'required' => true
            ),
            'programDe' => array(
                'required' => true
            ),
            'programEn' => array(
                'required' => true
            )
        );

        return $validators;
    }


}
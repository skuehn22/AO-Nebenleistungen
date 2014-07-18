<?php
/**
 * Ermitteln der Daten einer Category
 *
 * Bestimmt mittels der ID
 * der Category die Stammwerte dieser Category
 *
 *
 * @author Stephan.Krauss
 * @date 07.03.13
 * @file index.php
 * @package tools
 */
class nook_ToolCategory
{

    // Tabellen / View / Datenbank
    private $_tabelleCategories = null;
    private $_tabelleRatesConfig = null;

    // Konditionen

    // Fehler
    private $_error_kein_int = 1350;
    private $_error_id_category_unbekannt = 1351;
    private $_error_keine_category_daten_vorhanden = 1352;
    private $_error_id_rate_unbekannt = 1353;

    // Flags

    protected $_idCategory = null;
    protected $_idRate = null;
    protected $_datenCategory = array();

    function __construct()
    {
        /** @var _tabelleCategories Application_Model_DbTable_categories */
        $this->_tabelleCategories = new Application_Model_DbTable_categories(array('db' => 'hotels'));
        /** @var _tabelleRatesConfig Application_Model_DbTable_otaRatesConfig */
        $this->_tabelleRatesConfig = new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));

    }

    /**
     * @param $__idCategory
     * @return nook_ToolCategory
     * @throws nook_Exception
     */
    public function setIdCategory($__idCategory)
    {

        $idCategory = (int) $__idCategory;
        if(empty($idCategory))
            throw new nook_Exception($this->_error_kein_int);

        $this->_idCategory = $idCategory;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdCategory()
    {
        return $this->_idCategory;
    }

    /**
     * Ermitteln Grunddaten der Category
     *
     * @return nook_ToolCategory
     * @throws nook_Exception
     */
    private function _ermittelnGrunddatenCategory(){

        if(empty($this->_idCategory))
            throw new nook_Exception($this->_error_id_category_unbekannt);

        $row = $this->_tabelleCategories->find($this->_idCategory)->toArray();
        $this->_datenCategory = $row[0];

        return $this;
    }

    /**
     * Ermittelt die Grunddaten der Category
     *
     * Es muss die ID der Rate vorhanden sein.
     *
     * @return array
     * @throws nook_Exception
     */
    public function getDatenCategory()
    {

        $this
            ->_ermittleIdCategory()
            ->_ermittelnGrunddatenCategory();

        if(count($this->_datenCategory) == 0)
            throw new nook_Exception($this->_error_keine_category_daten_vorhanden);

        return $this->_datenCategory;
    }

    /**
     * @param $__rateId
     * @return nook_ToolCategory
     */
    public function setRateId($__ratenId){

        $ratenId = (int) $__ratenId;
        if(empty($ratenId))
            throw new nook_Exception($this->_error_kein_int);

        $this->_idRate = $ratenId;

        return $this;
    }

    /**
     * Findet die ID der Category
     *
     * mit der ID der rate
     *
     * @param $__rateId
     */
    private function _ermittleIdCategory(){

        if(empty($this->_idRate))
            throw new nook_Exception($this->_error_id_rate_unbekannt);

        $row = $this->_tabelleRatesConfig->find($this->_idRate)->toArray();

        $this->_idCategory = $row[0]['category_id'];

        return $this;
    }



} // end class

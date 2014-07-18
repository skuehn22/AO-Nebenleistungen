<?php
/**
* Tools zur Darstellung der Daten einer Rate eines Hotels
*
* + Setzen ID der Rate
* + Gibt die Raten Daten zurück
* + Ermittelt den datensatz einer Rate
* + Ermittelt den Kategorie Namen
* + Ermitteln der daten der Kategorie
*
* @date 21.11.2012
* @file ToolRate.php
* @package tools
*/
class nook_ToolRate{

    protected $_rateId = null;

    // Tabellen / Views
    private $_tabelleOtaRatesConfig = null;
    private $_tabelleCategories = null;

    // Error
    private $_error_kein_int = 1340;
    private $_error_daten_unvollstaendig = 1341;

    public function __construct(){

        /** @var _tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig */
        $this->_tabelleOtaRatesConfig = new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        /** @var _tabelleCategories Application_Model_DbTable_categories */
        $this->_tabelleCategories = new Application_Model_DbTable_categories(array('db' => 'hotels'));
    }

    /**
     * Setzen ID der Rate
     *
     * Setzen und kontrollieren
     * ID der Rate
     *
     * @param $__rateId
     * @return nook_ToolRate
     * @throws nook_Exception
     */
    public function setRateId($__rateId){

        $__rateId = (int) $__rateId;
        if(empty($__rateId))
            throw new nook_Exception($this->_error_kein_int);

        $this->_rateId = $__rateId;

        return $this;
    }

    /**
     * Gibt die Raten Daten zurück
     *
     * @return mixed
     */
    public function getRateData(){

        $rateDaten = $this->_getRateDaten();

        return $rateDaten;
    }

    /**
     * Ermittelt den datensatz einer Rate
     *
     * Mittels der ID der Rate
     * werden die Daten ermittelt
     *
     * @return mixed
     */
    private function _getRateDaten(){
        $rateDaten = $this->_tabelleOtaRatesConfig->find($this->_rateId)->toArray();

        return $rateDaten[0];
    }

    /**
     * Ermittelt den Kategorie Namen
     *
     * Mittels Raten ID werden die Daten der Rate ermittelt.
     *
     * @return mixed
     * @throws nook_Exception
     */
    public function getRateName(){

        if(empty($this->_rateId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $ratenDaten = $this->_getRateDaten();
        // $kategorieDaten = $this->_getKategorieDaten($ratenDaten['id']);

        return $ratenDaten['name'];
    }

    /**
     * Ermitteln der daten der Kategorie
     *
     * mittels der ID der Rate
     *
     * @param $__kategorieId
     * @return mixed
     */
    private function _getKategorieDaten($__kategorieId)
    {
        $kategorieDaten = $this->_tabelleCategories->find($__kategorieId)->toArray();

        return $kategorieDaten[0];
    }

} // end class
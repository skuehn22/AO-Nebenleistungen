<?php
class Admin_Model_zusatztexteFirma{

    private $_firmaId = null;

    private $_error_zusatztexte_kein_update = 920;

    private $_condition_update_ein_datensatz = 1;

    private $_tabelleAdressen = null;

    public function __construct(){
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
    }

    /**
     * Ermittelt die Zusatztexte einer Firma
     *
     * @return string
     */
    public function getZusatztexte(){

		return $this->_findZusatztexteEinerFirma();
	}

    /**
     * Übernimmt ID der Firma
     *
     * @param $__firmaId
     * @return Admin_Model_zusatztexteFirma
     */
    public function setFirmenId($__firmaId){
        $this->_firmaId = $__firmaId;

        return $this;
    }

    /**
     * editiert die Zusatztexte einer
     * Firma
     *
     * @param $__params
     */
    public function editZusatztexte($__params){

        // update
        if(!$this->_firmaId !== null){
            $updatedRows = $this->_updateZusatztexte($__params);
            if($updatedRows != $this->_condition_update_ein_datensatz)
                throw new nook_Exception($this->_error_zusatztexte_kein_update);
        }

        return;
    }

    /**
     * Liest Zusatztete einer Firma
     *
     * @return array
     */
    private function _findZusatztexteEinerFirma(){
        $cols = array(
            'confirm_1_dt',
            'confirm_1_en'
        );

        $select = $this->_tabelleAdressen->select();
        $select->from($this->_tabelleAdressen, $cols)->where('id = '.$this->_firmaId);
        $zusatztexte = $this->_tabelleAdressen->fetchRow($select)->toArray();

        return $zusatztexte;
    }

    private function _updateZusatztexte($__params){
        $cols = array(
            "confirm_1_dt" => $__params['confirm_1_dt'],
            "confirm_1_en" => $__params['confirm_1_en']
        );

        $updatedRows = $this->_tabelleAdressen->update($cols, 'id = '.$this->_firmaId);

        return $updatedRows;
    }
	
}
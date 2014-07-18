<?php
class Front_Model_Antwort extends nook_ToolModel implements ArrayAccess{

    // Error
    private $_error_kein_int_wert = 1180;
    private $_error_kontrolle_fehlgeschlagen = 1181;
    private $_error_update_tabelle_adressen = 1182;

    // Konditionen
    private $_condition_erstkunde = 2;

    // Tabelle / Views
    private $_tabelleAdressen = null;

    protected $_kontrollCode = null;


	public function __construct(){
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
	}

    /**
     * @param $__kontrollCode
     * @return Front_Model_Antwort
     * @throws nook_Exception
     */
    public function setKontrollCode($__kontrollCode){

        $kontrollCode = (int) $__kontrollCode;

        if(!is_int($kontrollCode))
            throw new nook_Exception($this->_error_kein_int_wert);

        $this->_kontrollCode = $__kontrollCode;

        return $this;
    }

    /**
     * Kontrolle der Anmeldung
     *
     * @return Front_Model_Antwort
     */
    public function bestaetigungAnmeldung(){

        $this
            ->_kontrolleAnmeldung()
            ->_setzeStatusErstkunde();

        return $this;
    }


    /**
     * Kontrolliert ob nur ein Datensatz in
     * 'tbl_adressen' vorhanden ist.
     *
     * @return Front_Model_Antwort
     * @throws nook_Exception
     */
    private function _kontrolleAnmeldung(){
        $where = "controlcode = ".$this->_kontrollCode;

        $select = $this->_tabelleAdressen->select();
        $select->where($where);

        $rows = $this->_tabelleAdressen
                    ->fetchAll($select)
                    ->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_kontrolle_fehlgeschlagen);

        return $this;
    }

    /**
     * Setzt Benutzer auf Status 2 / Erstkunde
     *
     * @return Front_Model_Antwort
     * @throws nook_Exception
     */
    private function _setzeStatusErstkunde(){

        $update = array(
            'status' => $this->_condition_erstkunde
        );

        $where = "controlcode = ".$this->_kontrollCode;

        $ergebnis = $this->_tabelleAdressen->update($update, $where);

        if($ergebnis != 1)
            throw new  nook_Exception($this->_error_update_tabelle_adressen);

        return $this;
    }


}
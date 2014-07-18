<?php
 
class Front_Model_ProgrammdetailStornofristenManager extends nook_ToolModel{

    // Tabellen / Views / Datenbanken
    private $_tabelleProgrammdetailsStornokosten = null;

    // Fehler
    private $_error = 1270;

    // Konditionen

    // Flags

    public function __construct(){
        /** @var _tabelleProgrammdetailsStornokosten Application_Model_DbTable_stornofristen */
        $this->_tabelleProgrammdetailsStornokosten = new Application_Model_DbTable_stornofristen();

    }

    /**
     * Ermittelt die Stornofristen eines Programmes
     *
     * @param $__programmId
     * @return array
     */
    public function getStornofristenEinesProgramm($__programmId){
        $stornofristen = $this->_getStornofristenEinesProgramm($__programmId);

        return $stornofristen;
    }

    /**
     * Ermittelt diem Stornofristen eines Programmes
     *
     * @param $__programmId
     * @return array|bool
     */
    private function _getStornofristenEinesProgramm($__programmId){
        $stornofristen = false;

        $cols = array(
            'tage',
            'prozente'
        );

        $select = $this->_tabelleProgrammdetailsStornokosten->select();
        $select
            ->from($this->_tabelleProgrammdetailsStornokosten, $cols)
            ->where("programmdetails_id = ".$__programmId);

        $rows = $this->_tabelleProgrammdetailsStornokosten->fetchAll($select)->toArray();
        if(count($rows) > 0)
            $stornofristen = $rows;

        return $stornofristen;
    }

} // end class

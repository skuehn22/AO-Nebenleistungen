<?php
/**
 * Ermitteln von Programmen / Hotel
 *
 * @author Stephan.Krauss
 * @date 18.04.13
 * @file ToolProgramm.php
 * @package tools
 */

class nook_ToolProgramm {

    // Error
    private $_error_programm_id_unbekannt = 1240;

    // Tabellen , Views
    private $_tabelleProgrammbeschreibung = null;

    // Konditionen


    protected  $_programmId = null;

    public function __construct(){
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();
    }

    /**
     * Übernimmt die Programm ID
     *
     * @param $__programmId
     * @return nook_ToolHotel
     */
    public function setProgrammId($__programmId){
        $this->_hotelId = $__programmId;

        return $this;
    }

    /**
     * Liefert die Grunddaten einers Programmes
     *
     * @return array
     * @throws nook_Exception
     */
    public function getGrunddatenHotel(){
        if(empty($this->_programmId))
            throw new nook_Exception($this->_error_programm_id_unbekannt);

        $grundDatenProgramm = $this->_tabelleProgrammbeschreibung->find($this->_programmId)->toArray();

        if(count($grundDatenProgramm) != 1)
            throw new nook_Exception($this->_error_programm_id_unbekannt);

        return $grundDatenProgramm[0];
    }

    /**
     * Ermittelt den Hoten namen mittels
     * Hotel ID
     *
     * @return mixed
     */
    public function getProgrammName($__programmId, $__sprache = 1){

        $cols = array(
            'progname'
        );

        $select = $this->_tabelleProgrammbeschreibung->select();
        $select
            ->from($this->_tabelleProgrammbeschreibung, $cols)
            ->where("programmdetail_id = ".$__programmId)
            ->where("sprache = ".$__sprache);

        $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_programm_id_unbekannt);


        return $rows[0]['progname'];
    }

} // end class

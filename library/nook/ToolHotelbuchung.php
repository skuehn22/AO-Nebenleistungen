<?php
/**
 * Handelt die Daten des Buchungsdatensatzes der Hotelbuchung
 *
 * bezüglich der Tabelle 'tbl_hotelbuchung'
 *
 * @author stephan.krauss
 * @date 27.05.13
 * @file ToolHotelbuchung.php
 * @package tools
 */
class nook_ToolHotelbuchung {

    // Views / Tabellen
    private $_tabelleHotelbuchung = null;

    // Errors
    private $_error_anfangsdaten_unvollstaendig = 1520;
    private $_error_anzahl_datensaetze_falsch = 1521;

    // Konditionen

    // Flags

    protected $_hotelbuchungId = null;
    protected $_datenHotelbuchung = null;

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
    }

    /**
     * @param $hotelbuchungId
     * @return nook_ToolHotelbuchung
     */
    public function setHotelbuchungId ($hotelbuchungId)
    {
        $this->_hotelbuchungId = $hotelbuchungId;

        return $this;
    }

    /**
     * @return array
     */
    public function getDatenHotelbuchung ()
    {
        return $this->_datenHotelbuchung;
    }

    /**
     * Ermittelt den datensatz einer Hotelbuchung / Rate
     *
     * @return nook_ToolHotelbuchung
     * @throws nook_Exception
     */
    public function ermittelnDatenHotelbuchung(){

        if(empty($this->_hotelbuchungId))
            throw new nook_Exception($this->_error_anfangsdaten_unvollstaendig);

        $datenHotelbuchung = $this->_ermittelnDatenHotelbuchung();
        $this->_datenHotelbuchung = $datenHotelbuchung;

        return $this;
    }

    /**
     * Ermittelt die Buchungsdaten einer gebuchten Hotel Rate
     *
     * @return array
     * @throws nook_Exception
     */
    private function _ermittelnDatenHotelbuchung()
    {
        $rows = $this->_tabelleHotelbuchung
            ->find($this->_hotelbuchungId)
            ->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        return $rows[0];
    }







} // end class

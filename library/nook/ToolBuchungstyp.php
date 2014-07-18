<?php
/**
 * Werkzeuge zur Bestimmung des Buchungstypes
 *
 *
 * @author Stephan.Krauss
 * @date 18.04.13
 * @file ToolBuchungstyp.php
 * @package tools
 */
class nook_ToolBuchungstyp
{

    // Error
    private $_error_kein_int = 1430;
    private $_error_fehlende_daten = 1431;
    private $_error_falsche_anzahl_datensaetze = 1432;

    // Tabellen , Views
    private $_tabelleProgrammdetails = null;

    // Konditionen
    protected $condition_buchungstyp_keine_buchungspauschale = 1;

    protected $_programmId = null;

    public function __construct ()
    {
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
    }

    /**
     * Übernahme Programm ID
     *
     * + Registrierung der Programm ID
     *
     * @param $programmId
     * @return nook_ToolBuchungstyp
     */
    public function setProgrammId ($programmId)
    {
        $this->_programmId = $programmId;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgrammId ()
    {
        return $this->_programmId;
    }

    /**
     * Kontolliert die ID des Programmes
     *
     * @param $programmId
     * @return nook_ToolBuchungstyp
     * @throws nook_Exception
     */
    public function isValidProgrammId ($programmId)
    {
        $programmId = (int) $programmId;

        if(empty($programmId)) {
            throw new nook_Exception($this->_error_kein_int);
        }

        return $this;
    }

    /**
     * Kontrolle Ermittlung Buchungstyp
     *
     * + wenn keine Programm ID übergeben wird, dann wird Buchungspuschale => 1 angenommen.
     *
     * @return int
     * @throws nook_Exception
     */
    public function ermittleBuchungstypProgramm ()
    {
        if(empty($this->_programmId))
            throw new nook_Exception('Es fehlt die Programm ID');

        return $this->_ermittleBuchungstypProgramm();
    }

    /**
     * Ermittlung Buchungstyp
     *
     * @return int
     * @throws nook_Exception
     */
    private function _ermittleBuchungstypProgramm ()
    {
        $cols = array(
            'buchungstyp'
        );

        $select = $this->_tabelleProgrammdetails->select();
        $select->from($this->_tabelleProgrammdetails, $cols)->where("id = " . $this->_programmId);

        $query = $select->__toString();

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_falsche_anzahl_datensaetze);


        return $rows[0]['buchungstyp'];
    }
}

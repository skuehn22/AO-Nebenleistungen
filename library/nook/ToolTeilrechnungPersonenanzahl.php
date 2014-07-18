<?php
/**
 * Ermittelt die Personenanzahl einer Teilrechnung
 *
 * Ermittelt die Personenanzahl einer Teilrechnung an Hand der Buchungsdatensätze in 'tbl_hotelbuchung'
 *
 * @author stephan.krauss
 * @date 27.05.13
 * @file nook_ToolTeilrechnungPersonenanzahl.php
 * @package tools
 */
class nook_ToolTeilrechnungPersonenanzahl
{

    // Tabellen/ Views
    private $_tabelleHotelbuchung = null;

    // Konditionen

    // Errors
    private $_error_ausgangswerte_fehlen = 1510;
    private $_error_keine_hotelbuchung_vorhanden = 1511;

    // Flags

    protected $_personenanzahl = null;
    protected $_teilrechnungId = null;

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
    }

    /**
     * @param $teilrechnungId
     * @return nook_ToolTeilrechnungPersonenanzahl
     */
    public function setTeilrechnungId ($teilrechnungId)
    {
        $teilrechnungId = (int) $teilrechnungId;
        $this->_teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * @param $personenanzahl
     * @return int
     */
    public function getPersonenanzahl ()
    {
        return $this->_personenanzahl;
    }

    /**
     * @return nook_ToolTeilrechnungPersonenanzahl
     * @throws nook_Exception
     */
    public function ermittelnPersonenanzahlTeilrechnung ()
    {
        if(empty($this->_teilrechnungId)) {
            throw new nook_Exception($this->_error_ausgangswerte_fehlen);
        }

        $this->_ermittelnPersonenanzahlTeilrechnung();

        return $this;
    }

    /**
     * Ermittelt die Personenanzahl einer Teilrechnung
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function _ermittelnPersonenanzahlTeilrechnung ()
    {
        $cols = array(
            new Zend_Db_Expr("sum(personNumbers) as anzahl")
        );

        $select = $this->_tabelleHotelbuchung->select();
        $select
            ->from($this->_tabelleHotelbuchung, $cols)
            ->where("teilrechnungen_id = " . $this->_teilrechnungId);

        $query = $select->__toString();

        $rows = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($rows) <> 1) {
            throw new nook_Exception($this->_error_keine_hotelbuchung_vorhanden);
        }

        $this->_personenanzahl = $rows[ 0 ][ 'anzahl' ];

        return $rows[ 0 ][ 'anzahl' ];
    }
} // end class

<?php
/**
 * 07.11.12 09:22
 * Ermitteln von Hoteldaten
 *
 *
 * @author Stephan Krauß
 */

class nook_ToolHotel
{

    // Error
    private $_error_hotel_id_unbekannt = 950;
    private $_error_kein_int = 951;
    private $_error_daten_unvollsaendig = 952;
    private $_error_tagespreis_nicht_vorhanden = 953;

    // Tabellen , Views
    private $_tabelleProperties = null;
    private $_tabelleOtaPrices = null;

    // Konditionen

    protected $_hotelId = null;
    protected $_hotelCode = null;
    protected $_rateCode = null;
    protected $_datum = null;
    protected $_tagespreisRate = null;

    public function __construct ()
    {
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array( 'db' => 'hotels' ));
        /** @var _tabelleOtaPrices */
        $this->_tabelleOtaPrices = new Application_Model_DbTable_otaPrices(array( 'db' => 'hotels' ));
    }

    /**
     * @return null
     */
    public function getTagespreisRate ()
    {
        if(empty($this->_tagespreisRate)){
            throw new nook_Exception($this->_error_daten_unvollsaendig);
        }

        return $this->_tagespreisRate;
    }

    /**
     * Ermittelt den Tagespreis einer Rate
     *
     * in einem Hotel
     * zu einem bestimmten Datum
     *
     * @return nook_ToolHotel
     * @throws nook_Exception
     */
    public function ermittleTagespreisEinerRate ()
    {
        if((empty($this->_hotelCode)) or (empty($this->_rateCode)) or (empty($this->_datum))) {
            throw new nook_Exception($this->_error_daten_unvollsaendig);
        }

        $this->_ermittleTagespreisEinerRate();

        return $this;
    }

    /**
     * Ermittelt den Tagespreis einer Rate
     * in einem Hotel
     * zu einem bestimmten Datum
     *
     * @return nook_ToolHotel
     * @throws nook_Exception
     */
    private function _ermittleTagespreisEinerRate ()
    {
        $this->_tagespreisRate = null;

        $cols = array(
            'amount'
        );

        $select = $this->_tabelleOtaPrices->select();
        $select
            ->from($this->_tabelleOtaPrices, $cols)
            ->where("hotel_code = '" . $this->_hotelCode . "'")
            ->where("datum = '" . $this->_datum . "'")
            ->where("rate_code = '" . $this->_rateCode . "'");

        $rows = $this->_tabelleOtaPrices->fetchAll($select)->toArray();

        if(count($rows) <> 1) {
            throw new nook_Exception('Der Tagespreis ist für HotelCode: '.$this->_hotelCode." Datum: ".$this->_datum." und RateCode: ".$this->_rateCode." nicht vorhanden !");
        }

        $this->_tagespreisRate = $rows[ 0 ][ 'amount' ];

        return $this;
    }

    /**
     * @param $__hotelCode
     * @return nook_ToolHotel
     */
    public function setHotelCode ($__hotelCode)
    {
        $this->_hotelCode = $__hotelCode;

        return $this;
    }

    /**
     * @param $__rateCode
     * @return nook_ToolHotel
     */
    public function setRateCode ($__rateCode)
    {
        $this->_rateCode = $__rateCode;

        return $this;
    }

    /**
     * @param $__datum
     * @return nook_ToolHotel
     */
    public function setDatum ($__datum)
    {
        $this->_datum = $__datum;

        return $this;
    }

    /**
     * Übernimmt die Hotel ID
     *
     * @param $__hotelId
     * @return nook_ToolHotel
     */
    public function setHotelId ($__hotelId)
    {
        $this->_hotelId = $__hotelId;

        return $this;
    }

    /**
     * Liefert die Grunddaten einers Hotels
     *
     * @return array
     * @throws nook_Exception
     */
    public function getGrunddatenHotel ()
    {
        if(empty($this->_hotelId)){
            throw new nook_Exception($this->_error_hotel_id_unbekannt);
        }

        $grundDatenHotel = $this->_ermittelnGrunddatenHotel();

        return $grundDatenHotel;
    }

    /**
     * Ermittelt die Grunddaten eines Hotels
     *
     * @return array
     */
    private function _ermittelnGrunddatenHotel ()
    {
        $grundDatenHotel = $this->_tabelleProperties->find($this->_hotelId)->toArray();

        return $grundDatenHotel[0];
    }

    /**
     * Ermittelt den Hoten namen mittels
     * Hotel ID
     *
     * @return mixed
     */
    public function getHotelName ($__hotelId)
    {

        $rows = $this->_tabelleProperties->find($__hotelId);

        if(count($rows) != 1) {
            throw new nook_Exception($this->_error_hotel_id_unbekannt);
        }

        return $rows[ 0 ][ 'property_name' ];
    }

    /**
     * Ermittelt den Hotel Code
     *
     * Hotelcode mit der ID des Hotel bestimmen.
     *
     * @param $__hotelId
     * @return mixed
     */
    public function getHotelCode ()
    {

        if(empty($this->_hotelId)) {
            throw new nook_Exception($this->_error_hotel_id_unbekannt);
        }

        $cols = array(
            'property_code'
        );

        $select = $this->_tabelleProperties->select();
        $select->from($this->_tabelleProperties, $cols)->where("id = " . $this->_hotelId);

        $row = $this->_tabelleProperties->fetchRow($select)->toArray();

        return $row[ 'property_code' ];
    }

} // end class

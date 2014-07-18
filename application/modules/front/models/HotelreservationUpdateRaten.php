<?php
/**
 * Ermittelt die Raten und Rateninhalte die
 * geupdatet werden sollen
 *
 * @author Stephan.Krauss
 * @date 31.01.13
 * @file HotelreservationSessionHotelsuche.php
 */
 
class Front_Model_HotelreservationUpdateRaten extends nook_ToolModel{

    // Errors
    private $_error_kein_int = 1210;
    private $_error_kein_datensatz_vorhanden = 1211;

    // Konditionen

    // Datenbanken, Tabellen und Views
    private $_tabelleHotelbuchung = null;
    private $_tabelleBuchungsnummer = null;

    protected $_nights = null; // Anzahl der Übernachtungen
    protected $_propertyId = null; // ID des Hotels
    protected $_from = null; // Anreisedatum der Gruppe

    protected $_buchungsnummer = null; // Buchungsnummer
    protected $_sessionId = null; // Session ID der zu bearbeitenden Buchung

    protected $_gebuchteRaten = array(); // bereits gebuchte Raten
    protected $_gesamtPersonenAnzahl = null;
    protected $_variablenNamespaceHotelsuche = array();
    private $anzeigeSpracheId = null;

    public function __construct(){
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    /**
     * Anzahl der Nächte
     *
     * @param $__anzahlNaechte
     * @return Front_Model_HotelreservationUpdateRaten
     * @throws nook_Exception
     */
    public function setNights($__anzahlNaechte){

        $__anzahlNaechte = (int) $__anzahlNaechte;
        if(!is_int($__anzahlNaechte))
            throw new nook_Exception($this->_error_kein_int);

        $this->_nights = $__anzahlNaechte;

        return $this;
    }

    /**
     * Setzt die ID des Hotels
     *
     * @param $__hotelId
     * @return Front_Model_HotelreservationUpdateRaten
     * @throws nook_Exception
     */
    public function setPropertyId($__hotelId){

        $__hotelId = (int) $__hotelId;
        if(!is_int($__hotelId))
            throw new nook_Exception($this->_error_kein_int);

        $this->_propertyId = $__hotelId;

        return $this;
    }

    /**
     * Anreisedatum der Gruppe / Teilgruppe
     *
     * + Anreisedatum wandeln entsprechend ISO 8601
     *
     * @param $anreiseDatum
     * @return Front_Model_HotelreservationUpdateRaten
     */
    public function setStartDate($anreiseDatum)
    {
        $anreiseDatum = trim($anreiseDatum);

        $this->_from = $anreiseDatum;

        return $this;
    }

    /**
     * ermittelt bereits gebuchte Raten eines Warenkorbes
     *
     * @return Front_Model_HotelreservationUpdateRaten
     */
    public function steuerungErmittlungGebuchteRaten()
    {
        $this->_findBuchungsnummer();
        $this->_findGebuchteRaten();
        $this->_ermittleGesamtanzahlPersonen();

        return $this;
    }

    /**
     * Ermittelt die Gesamtanzahl der Personen
     *
     * @return Front_Model_HotelreservationUpdateRaten
     */
    private function _ermittleGesamtanzahlPersonen(){

        foreach($this->_gebuchteRaten as $key => $value){
            $this->_gesamtPersonenAnzahl += $this->_gebuchteRaten[$key]['personNumbers'];
        }

        return $this;
    }

    /**
     * Gibt Gesamtpersonenanzahl zurück
     *
     * @return null
     */
    public function getGesamtanzahlpersonen(){

        return $this->_gesamtPersonenAnzahl;
    }

    /**
     * Findet die Buchungsnummer die zur
     * Session gehört
     *
     * @return Front_Model_HotelreservationUpdateRaten
     * @throws nook_Exception
     */
    private function _findBuchungsnummer()
    {
        $sessionId = Zend_Session::getId();
        $this->_sessionId = $sessionId;

        $cols = array(
            'id'
        );

        $where = "session_id = '".$this->_sessionId."'";

        $select = $this->_tabelleBuchungsnummer->select();
        $select
            ->from($this->_tabelleBuchungsnummer, $cols)
            ->where($where);

        $rows = $this->_tabelleBuchungsnummer->fetchAll($select)->toArray();
        if(count($rows) == 0)
            throw new nook_Exception($this->_error_kein_datensatz_vorhanden);

        $this->_buchungsnummer = $rows[0]['id'];

        return $this;
    }

    /**
     * Ermittelt bereits gebuchte Raten
     *
     * @return Front_Model_HotelreservationUpdateRaten
     * @throws nook_Exception
     */
    private function _findGebuchteRaten(){

        $select = $this->_tabelleHotelbuchung->select();
        $select
            ->where("buchungsnummer_id = ".$this->_buchungsnummer)
            ->where("propertyId = ".$this->_propertyId)
            ->where("nights = ".$this->_nights)
            ->where("startDate = '".$this->_from."'");

        $query = $select->__toString();

        $rows = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($rows) == 0)
            throw new nook_Exception($this->_error_kein_datensatz_vorhanden);

        $this->_gebuchteRaten = $rows;


        return $this;
    }

    /**
     * Gibt die bereits gebuchten Raten zurück
     *
     * @return array
     */
    public function getBereitsGebuchteRaten(){

        return $this->_gebuchteRaten;
    }

    /**
     * Verändert die Personenzahl der Rate
     *
     * @param $zimmerDesHotels
     * @param $bereitsGebuchteRaten
     * @return mixed
     */
    public function belegenPersonenanzahlDerGebuchtenraten($zimmerDesHotels, $bereitsGebuchteRaten)
    {
        // eintragen Anzahl Personenanzahl = 0
        foreach($zimmerDesHotels as $key => $value){
            $zimmerDesHotels[$key]['personenanzahl'] = 0;
        }
        // eintragen Personenanzahl entsprechend Buchung
        foreach($zimmerDesHotels as $key => $value){
            for($i=0; $i < count($bereitsGebuchteRaten); $i++){
                if($zimmerDesHotels[$key]['ratenId'] == $bereitsGebuchteRaten[$i]['otaRatesConfigId'] ){
                    $zimmerDesHotels[$key]['personenanzahl'] = $bereitsGebuchteRaten[$i]['personNumbers'];
                }
            }
        }

        return $zimmerDesHotels;
    }

    /**
     * Holt die Variablen aus den Namespace
     * 'hotelsuche'
     *
     * @return Front_Model_HotelreservationUpdateRaten
     */
    private function  _bestimmeSessionVariablen(){

        $sessionNamespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $sessionVariablen = (array) $sessionNamespaceHotelsuche->getIterator();

        $this->_variablenNamespaceHotelsuche = $sessionVariablen;

        return $this;
    }
}

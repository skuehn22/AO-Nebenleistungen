<?php
/**
 * Beschreibung der Klasse
 *
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 * 
 * 
 * @author stephan.krauss
 * @date 30.05.13
 * @file ZusatzprodukteTouristischeGrundleistung.php
 * @package front | admin | tabelle | data | tools | plugins
 * @subpackage model | controller | filter | validator
 */
class Front_Model_ZusatzprodukteTouristischeGrundleistung {

    // Tabelle / Views
    private $_tabelleHotelbuchung = null;
    private $_tabelleProducts = null;

    // Konditionen
    private $_condition_typ_touristische_grundleistung = 2;
    private $_condition_anzeige_sprache_deutsch = 1;

    // Fehler
    private $_error_anfangswerte_unvollstaendig = 1570;

    // Flags

    protected $_teilrechnungId = null;
    protected $_buchungsnummerId = null;
    protected $_hotelId = null;

    protected $_zimmerAnzahl = null;
    protected $_personenAnzahl = null;
    protected $_anzeigeSprache = null;

    protected $_touristischeGrundleistungenHotel = array();
    protected $_buchungsdatenTeilrechnung = array();

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array("db" => "hotels"));
    }

    /**
     * @return array
     */
    public function getGebuchteTouristischeGrundleistungen ()
    {
        return $this->_touristischeGrundleistungenHotel;
    }

    /**
     * @param $hotelId
     * @return Front_Model_ZusatzprodukteTouristischeGrundleistung
     */
    public function setHotelId ($hotelId)
    {
        $this->_hotelId = (int) $hotelId;

        return $this;
    }

    /**
     * @param $teilrechnungId
     * @return Front_Model_ZusatzprodukteTouristischeGrundleistung
     */
    public function setTeilrechnungId ($teilrechnungId)
    {
        $this->_teilrechnungId = (int) $teilrechnungId;

        return $this;
    }

    /**
     * Ermitteln der gebuchten touristischen Grundleistungen einer Teilrechnung
     *
     *
     *
     * @return Front_Model_ZusatzprodukteTouristischeGrundleistung
     * @throws nook_Exception
     */
    public function ermittelnProdukteTouristischeGrundleistung()
    {
        if(empty($this->_teilrechnungId) or empty($this->_hotelId))
            throw new nook_Exception($this->_error_anfangswerte_unvollstaendig);

        // Anzeigesprache ermitteln
        $this->_anzeigespracheErmitteln();

        // ermitteln der Produkte touristische Grundleistung eines Hotels
        $this->_produkteTouristischeGrundleistungHotel();

        // ermitteln Buchungsdaten der Teilrechnung
        $this->_ermittelnBuchungsDatenTeilrechnung();

        // Abgleich der Buchungsdaten mit Produkte touristische Grundleistung
        for($i = 0; $i < count($this->_touristischeGrundleistungenHotel); $i++){
            $this->_abgleichAnzahlProdukteTouristischeGrundleistung($i);
        }

        return $this;
    }

    /**
     * Ermittelt Anzeigesprache
     *
     * + 1 deutsch
     * + 2 englisch
     */
    private function _anzeigespracheErmitteln(){
        $this->_anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();

        return;
    }

    /**
     * Setzt die Anzahl der Produkte der touristischen Grundleistung
     *
     * + entsprechend Typ des Produktes
     */
    private function _abgleichAnzahlProdukteTouristischeGrundleistung($i)
    {
        switch($this->_touristischeGrundleistungenHotel[$i]['typ']){
            // je Person
            case 1:
                $this->_touristischeGrundleistungenHotel[$i]['anzahl'] = $this->_personenAnzahl;
            break;
            // je Zimmer
            case 2:
                $this->_touristischeGrundleistungenHotel[$i]['anzahl'] = $this->_zimmerAnzahl;
            break;
            // Personen und Nächte
            case 3:
                $this->_touristischeGrundleistungenHotel[$i]['anzahl'] = $this->_personenAnzahl * $this->_anzahlNaechte;
            break;
        }

        if($this->_anzeigeSprache == $this->_condition_anzeige_sprache_deutsch){
            $this->_touristischeGrundleistungenHotel[$i]['name'] = $this->_touristischeGrundleistungenHotel[$i]['product_name'];
            $this->_touristischeGrundleistungenHotel[$i]['beschreibung'] = $this->_touristischeGrundleistungenHotel[$i]['ger'];
        }
        else{
            $this->_touristischeGrundleistungenHotel[$i]['name'] = $this->_touristischeGrundleistungenHotel[$i]['product_name_en'];
            $this->_touristischeGrundleistungenHotel[$i]['beschreibung'] = $this->_touristischeGrundleistungenHotel[$i]['eng'];
        }

        return;
    }

    /**
     * Ermittelt die Personen Anzahl und Zimmeranzahl einer Teilrechnung
     *
     * + Zimmeranzahl
     * + Personenanzahl
     *
     * @return int
     */
    private function _ermittelnBuchungsDatenTeilrechnung()
    {
        $cols = array(
            new Zend_Db_Expr("sum(personNumbers) as personenAnzahl"),
            new Zend_Db_Expr("sum(roomNumbers) as zimmerAnzahl"),
            new Zend_Db_Expr("avg(nights) as anzahlNaechte")
        );

        $whereTeilrechnung = "teilrechnungen_id = ".$this->_teilrechnungId;

        $select = $this->_tabelleHotelbuchung->select();
        $select->from($this->_tabelleHotelbuchung, $cols)->where($whereTeilrechnung);

        $rows = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        $this->_personenAnzahl = $rows[0]['personenAnzahl'];
        $this->_zimmerAnzahl = $rows[0]['zimmerAnzahl'];
        $this->_anzahlNaechte = (int) $rows[0]['anzahlNaechte'];

        return $rows[0]['personenAnzahl'];
    }

    private function _produkteTouristischeGrundleistungHotel(){

        $select = $this->_tabelleProducts->select();
        $select
            ->where("property_id = ".$this->_hotelId)
            ->where("standardProduct = ".$this->_condition_typ_touristische_grundleistung);

        $this->_touristischeGrundleistungenHotel = $this->_tabelleProducts->fetchAll($select)->toArray();

        return count($this->_touristischeGrundleistungenHotel);
    }
} // end class

<?php
/**
 * Werkzeuge für die Zusatzprodukte
 *
 * Es wird die ID des Produktes übergeben
 *
 * @author Stephan.Krauss
 * @date 17.04.13
 * @file ToolZusatzprodukte.php
 * @package tools
 */
class nook_ToolZusatzprodukte{

    private $_produktId = null;
    private $_produktName = null;
    private $_produktTyp = 1;
    private $_stadtName = null;
    private $_hotelName = null;
    private $_hotelId = null;
    private $_stadtId = null;
    private $_mwst = null;

    // Fehler
    private $_error_kein_int = 1410;
    private $_error_produkt_id_fehlt = 1411;
    private $_error_falsche_anzahl_datensaetze = 1412;

    // Tabellen / Views
    private $_tabelleTeilrechnungen = null;
    private $_tabelleProperties = null;
    private $_tabelleProducts = null;
    private $_tabelleCity = null;

    // Konditionen

    // Flags

    public function __construct(){
        /** @var _tabelleTeilrechnungen Application_Model_DbTable_teilrechnungen */
        $this->_tabelleTeilrechnungen = new Application_Model_DbTable_teilrechnungen();
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
        /** @var _tabelleCity Application_Model_DbTable_aoCity */
        $this->_tabelleCity = new Application_Model_DbTable_aoCity();
    }

    /**
     * @param $__produktId
     * @return nook_ToolZusatzprodukte
     *
     * @throws nook_Exception
     */
    public function setProduktId($__produktId)
    {
        $produktId = (int) $__produktId;
        if(empty($produktId))
            throw new nook_Exception($this->_error_kein_int);

        $this->_produktId = $produktId;

        return $this;
    }

    /**
     * @return string
     */
    public function getStadtName()
    {
        return $this->_stadtName;
    }

    /**
     * @return string
     */
    public function getHotelName()
    {
        return $this->_hotelName;
    }

    /**
     * @return int
     */
    public function getHotelId()
    {
        return $this->_hotelId;
    }

    /**
     * @return string
     */
    public function getProduktName()
    {
        return $this->_produktName;
    }

    /**
     * @return int
     */
    public function getStadtId()
    {
        return $this->_stadtId;
    }

    /**
     * @return int
     */
    public function getMwst()
    {
        return $this->_mwst;
    }

    /**
     * Produkttyp
     *
     * + 1 = kein Verpflegungsprodukt
     * + 2 = Verpflegungsprodukt
     *
     * @return int
     */
    public function getproduktTyp()
    {
        return $this->_produktTyp;
    }

    /**
     * Ermitteln globale Informationen des Zusatzproduktes
     *
     * + Stadtname
     * + Hotelname
     * + Hotel ID
     * + Produkttyp 1 = keine Verpflegung, 2 = Produkt ist verpflegungstyp
     *
     * @return nook_ToolZusatzprodukte
     * @throws nook_Exception
     */
    public function ermittleGlobaleInformationenZusatzprodukt(){
        if(empty($this->_produktId))
            throw new nook_Exception($this->_error_produkt_id_fehlt);

        $this->_ermittelnDatenProdukt();
        $this->_ermittleHoteldaten();
        $this->_ermittleStadtname();
        $this->_ermittelnProdukttyp();

        return $this;
    }

    /**
     * Ermittelt ob das Produkt ein Verpflegungsprodukt ist
     *
     * + 1 = keine verpflegung
     * + 2 = Produkt ist vom typ Verpflegung
     */
    private function _ermittelnProdukttyp(){

    }

    /**
     * Ermitteln des Stadtnamen
     *
     * mit der Stadt ID
     *
     * @throws nook_Exception
     */
    private function _ermittleStadtname()
    {

        $cols = array(
            'AO_City'
        );
        $select = $this->_tabelleCity->select();
        $select->from($this->_tabelleCity, $cols)->where("AO_City_ID = ".$this->_stadtId);
        $rows = $this->_tabelleCity->fetchAll($select)->toArray();
        if(count($rows)<> 1)
            throw new nook_Exception($this->_error_falsche_anzahl_datensaetze);

        $this->_stadtName = $rows[0]['AO_City'];
    }

    /**
     * Ermitteln Hotel ID
     *
     * mit vorhandener Produkt ID
     *
     * @throws nook_Exception
     */
    private function _ermittelnDatenProdukt()
    {
        $cols = array(
            'property_id',
            'product_name',
            'vat',
            'verpflegung'
        );
        $select = $this->_tabelleProducts->select();
        $select->from($this->_tabelleProducts, $cols)->where('id = '.$this->_produktId);
        $rows = $this->_tabelleProducts->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_falsche_anzahl_datensaetze);

        $this->_hotelId = $rows[0]['property_id'];
        $this->_produktName = $rows[0]['product_name'];
        $this->_mwst = $rows[0]['vat'];
        $this->_produktTyp = $rows[0]['verpflegung'];

        return;
    }

    /**
     * Ermitteln Hotelname
     *
     * mit vorhandener Hotel ID
     *
     * @throws nook_Exception
     */
    private function _ermittleHoteldaten()
    {
        $cols = array(
            'property_name',
            'city_id'
        );

        $select = $this->_tabelleProperties->select();
        $select->from($this->_tabelleProperties, $cols)->where('id = '.$this->_hotelId);
        $rows = $this->_tabelleProperties->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_falsche_anzahl_datensaetze);

        $this->_hotelName = $rows[0]['property_name'];
        $this->_stadtId = $rows[0]['city_id'];

        return;
    }
}
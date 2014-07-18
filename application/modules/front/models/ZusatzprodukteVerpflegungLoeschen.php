<?php
/**
 * Löscht die Verpflegungsprodukte einer Teilrechnung
 *
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 *
 *
 * @author stephan.krauss
 * @date 30.05.13
 * @file ZusatzprodukteVerpflegungLoeschen.php
 * @package front | admin | tabelle | data | tools | plugins
 * @subpackage model | controller | filter | validator
 */
class Front_Model_ZusatzprodukteVerpflegungLoeschen
{

    // Tabellen / Views
    private $_tabelleProduktbuchung = null;
    private $_tabelleProducts = null;

    // Fehler
    private $_error_anfangswert_fehlen = 1560;

    // Konditionen
    private $_condition_produkt_typ_verpflegung = 2;

    // Flags

    protected $_hotelId = null;
    protected $_buchungsnummerId = null;
    protected $_teilrechnungsId = null;

    protected $_verpflegungsprodukteEinesHotel = array();

    function __construct ()
    {
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array( 'db' => 'hotels' ));
        /** @var _tabelleProduktbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
    }

    /**
     * @param $teilrechnungsId
     * @return Front_Model_ZusatzprodukteVerpflegungLoeschen
     */
    public function setTeilrechnungsId ($teilrechnungsId)
    {
        $this->_teilrechnungsId = $teilrechnungsId;

        return $this;
    }

    /**
     * @param $hotelId
     * @return Front_Model_ZusatzprodukteVerpflegungLoeschen
     */
    public function setHotelId ($hotelId)
    {
        $this->_hotelId = $hotelId;

        return $this;
    }

    /**
     * Löscht die Verpflegungsprodukte einer Teilrechnung
     *
     * + ermitteln Buchungsnummer
     * + ermitteln der Produkte eines Hotels vom Typ Verpflegung
     * + loeschen der Verpflegungsprodukte der Teilrechnung
     *
     * @return $this
     * @throws nook_Exception
     */
    public function loeschenVerpflegungsprodukte ()
    {

        if(empty($this->_teilrechnungsId)) {
            throw new nook_Exception($this->_error_anfangswert_fehlen);
        }
        if(empty($this->_hotelId)) {
            throw new nook_Exception($this->_error_anfangswert_fehlen);
        }

        // ermitteln Buchungsnummer
        $this->_ermittelnBuchungsnummer();

        // ermitteln der Produkte eines Hotels vom Typ Verpflegung
        $this->_ermittelnVerpflegungsProdukteEinesHotels();

        // loeschen der Verpflegungsprodukte der Teilrechnung
        foreach($this->_verpflegungsprodukteEinesHotel as $produktId) {
            $this->_loeschenVerpflegungsProdukteEinerTeilrechnung($produktId[ 'id' ]);
        }

        return $this;
    }

    /**
     * Findet die Buchungsnummer der aktuellen Session
     *
     * @return int
     */
    private function _ermittelnBuchungsnummer ()
    {
        $this->_buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        return $this->_buchungsnummerId;
    }

    /**
     * Löschen eines Verpflegungsproduktes
     *
     * + entsprechend Nummer Teilrechnung
     * + entsprechend Buchungsnummer
     *
     * @param $produktId
     * @return int
     */
    private function _loeschenVerpflegungsProdukteEinerTeilrechnung ($produktId)
    {
        $whereDelete = array(
            "buchungsnummer_id = " . $this->_buchungsnummerId,
            "teilrechnungen_id = " . $this->_teilrechnungsId,
            "products_id = " . $produktId
        );

        $anzahlGeloeschteZeilen = $this->_tabelleProduktbuchung->delete($whereDelete);

        return $anzahlGeloeschteZeilen;
    }

    /**
     * Ermittelt die Verpflegungsprodukte eines Hotels
     *
     * @return int
     */
    private function _ermittelnVerpflegungsProdukteEinesHotels ()
    {
        $cols = array(
            'id'
        );

        $whereHotelId = "property_id = " . $this->_hotelId;
        $whereverpflegungsTyp = "verpflegung = " . $this->_condition_produkt_typ_verpflegung;

        $select = $this->_tabelleProducts->select();
        $select
            ->from($this->_tabelleProducts, $cols)
            ->where($whereHotelId)
            ->where($whereverpflegungsTyp);

        $rows = $this->_tabelleProducts->fetchAll($select)->toArray();
        $this->_verpflegungsprodukteEinesHotel = $rows;

        return count($rows);
    }

} // end class

<?php
/**
 * Verwaltung der Zusatzprodukte der Hotelbuchungen einer Teilrechnung
 *
 * + Loeschen eines Zusatzproduktes
 * + Ermittelt die Session Id der aktuellen Buchung
 * + Löscht alle Produktbuchungen
 * + Löscht das XML der Zusatzprodukte
 * + Bestimmt die Buchungsnummer der Session
 * + Mappt nach 'verpflegung'
 *
 * @author stephan.krauss
 * @date 30.05.13
 * @file WarenkorbZusatzprodukte.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbZusatzprodukte extends nook_ToolModel implements ArrayAccess
{

    protected $_hotelId;
    protected $_sessionId;
    protected $_anzahlUebernachtungen;
    protected $_buchungsnummer;
    protected $_anreisedatum;
    protected $_gebuchteZusatzprodukte = array();
    protected $_teilrechnungsId = null;

    // Fehler
    private $_error_produkt_gehoert_nicht_dem_user = 450;
    private $_error_zu_viele_datensaetze_produktbuchung = 451;
    private $_error_anzahl_datensaetze_stimmt_nicht = 452;

    // Konditionen
    private $_condition_typ_zusatzprodukte = 2;

    // Tabellen / Views
    private $_tabelleBuchungsnummer = null;
    private $_tabelleZusatzprodukteHotel = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleXmlBuchung = null;

    public function __construct ()
    {
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array( 'db' => 'front' ));
        /** @var _tabelleZusatzprodukteHotel Application_Model_DbTable_products */
        $this->_tabelleZusatzprodukteHotel = new Application_Model_DbTable_products(array( 'db' => 'hotels' ));
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung(array( 'db' => 'front' ));
        /** @var _tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();

    }

    /**
     * @param $teilrechnungsId
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _setTeilrechnungsId ($teilrechnungsId)
    {
        $this->_teilrechnungsId = $teilrechnungsId;

        return $this;
    }

    /**
     * Ermittelt die Session Id der aktuellen Buchung
     *
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _setSessionId ()
    {
        $this->_sessionId = Zend_Session::getId();

        return $this;
    }

    /**
     * Loeschen eines Zusatzproduktes
     *
     * @param $__idZusatzprodukt
     */
    public function loescheZusatzprodukt ($__idZusatzprodukt)
    {
        $sql = "delete from tbl_produktbuchung where id = " . $__idZusatzprodukt;
        $this->_db_groups->query($sql);

        return;
    }

    /**
     * Löscht alle Produktbuchungen
     *
     * einer Buchungsnummer
     */
    public function loeschenAllerZusatzprodukteImWarenkorb ()
    {
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $where = array(
            'buchungsnummer_id' => $buchungsnummer
        );

        $this->_tabelleProduktbuchung->delete($where);

        return;
    }

    /**
     * Löscht das XML der Zusatzprodukte
     *
     * für die betreffenden Zusatzprodukte
     *
     */
    public function loeschenTeilrechnungXMLZusatzprodukte ($__teilrechnungsId)
    {

        // Buchungsnummer der Session
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $where = array(
            'buchungsnummer_id = ' . $buchungsnummer,
            'teilrechnungen_id = ' . $__teilrechnungsId,
            'buchungstyp = ' . $this->_condition_typ_zusatzprodukte
        );

        // löschen Datensätz in 'tbl_xml_buchung' der Zusatzprodukte einer Teilrechnung
        $this->_tabelleXmlBuchung->delete($where);

        return;
    }

    /**
     * Überprüft die Daten der Zusatzprodukte
     *
     * @param $__buchungsdatenZusatzprodukte
     * @return array|bool
     */
    public function checkBuchungsdatenZusatzprodukte ($__buchungsdatenZusatzprodukte)
    {

        if(array_key_exists('module', $__buchungsdatenZusatzprodukte)) {
            unset($__buchungsdatenZusatzprodukte[ 'module' ]);
        }
        if(array_key_exists('controller', $__buchungsdatenZusatzprodukte)) {
            unset($__buchungsdatenZusatzprodukte[ 'controller' ]);
        }
        if(array_key_exists('action', $__buchungsdatenZusatzprodukte)) {
            unset($__buchungsdatenZusatzprodukte[ 'action' ]);
        }
        if(array_key_exists('zusatzprodukte', $__buchungsdatenZusatzprodukte)) {
            unset($__buchungsdatenZusatzprodukte[ 'zusatzprodukte' ]);
        }
        if(array_key_exists('zusatzprodukte', $__buchungsdatenZusatzprodukte)) {
            unset($__buchungsdatenZusatzprodukte[ 'zusatzprodukte' ]);
        }

        // wenn keine Zusatzprodukte
        if(empty($__buchungsdatenZusatzprodukte)) {
            return false;
        }

        $zusatzprodukte = array();
        foreach($__buchungsdatenZusatzprodukte as $key => $value) {
            if($value > 0) {
                $zusatzprodukte[ $key ] = $value;
            }
        }

        // wenn Zusatzprodukte vorhanden sind
        if(count($zusatzprodukte) > 0) {
            return $zusatzprodukte;
        } else {
            return false;
        }
    }

    /**
     * speichert die gewaehlten Zusatzprodukte
     *
     * eines Hotels, einer Buchungsnummer und der Teilrechnung.
     *
     * @param $__zusatzprodukte
     */
    public function saveZusatzprodukteEinesHotel ($__zusatzprodukte)
    {

        $teilrechnungsId = $__zusatzprodukte[ 'teilrechnungId' ];
        unset($__zusatzprodukte[ 'teilrechnungsId' ]);

        $this->_setSessionId(); // Session ID
        $this->_bestimmeBuchungsNummer(); // bestimme Buchungsnummer
        $this->_setTeilrechnungsId($teilrechnungsId); // setzt die Teilrechnungs ID der Buchung
        $this->_datenHotelsuche(); // speichern Daten Hotelsuche
        $this->_produkteEinesHotels($__zusatzprodukte); // bestimmt Preise der produkte
        $this->_setProduktpreisUndAllgemeineAngaben(); // berechne Produktpreis und allgemeine Angaben
        $this->_loeschenVerpflegungsProdukte(); // loeschen verpflegung
        $this->_insertOderUpdateProdukteEinerBuchung(); // insert oder update

        return;
    }

    public function checkGehoertZusatzproduktDemUser ($__idGebuchtesProdukt)
    {
        $auth = new Zend_Session_Namespace('Auth');
        if(empty($auth->userId)) {
            $sessionId = Zend_Session::getId();
            $sql = "select id from tbl_buchungsnummer where session_id = '" . $sessionId . "'";
        } else {
            $sql = "select id from tbl_buchungsnummer where kunden_id = " . $auth->userId;
        }

        $buchungsnummer = $this->_db_groups->fetchOne($sql);

        $sql = "select count(id) from tbl_produktbuchung where buchungsnummer_id = " . $buchungsnummer . " and id = " . $__idGebuchtesProdukt;

        $anzahlZuLoeschendeProdukte = $this->_db_groups->fetchOne($sql);
        if($anzahlZuLoeschendeProdukte > 1) {
            throw new nook_Exception($this->_error_produkt_gehoert_nicht_dem_user);
        }

        return;
    }

    /**
     * Bestimmt die Buchungsnummer der Session
     *
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _bestimmeBuchungsNummer ()
    {

        $select = $this->_tabelleBuchungsnummer->select();
        $select->where("session_id = '" . $this->_sessionId . "'");

        $row = $this->_tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($row) <> 1) {
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);
        }

        $this->_buchungsnummer = $row[ 0 ][ 'id' ];

        return $this;
    }

    /**
     * Ermittelt Daten der Hotelsuche aus dem
     * Session Namespace 'hotelsuche'
     *
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _datenHotelsuche ()
    {
        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $this->_anzahlUebernachtungen = $hotelsuche->days;
        $this->_anreisedatum = $hotelsuche->suchdatum;
        $this->_hotelId = $hotelsuche->propertyId;

        return $this;
    }

    /**
     * Löschen der Verpflegungsprodukte einer Teilrechnung
     *
     * entsprechend der
     * + Teilrechnung ID
     * + Buchungsnummer ID
     *
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _loeschenVerpflegungsProdukte ()
    {
        $modelZusatzprodukteVerpflegungLoeschen = new Front_Model_ZusatzprodukteVerpflegungLoeschen();
        $modelZusatzprodukteVerpflegungLoeschen
            ->setHotelId($this->_hotelId)
            ->setTeilrechnungsId($this->_teilrechnungsId)
            ->loeschenVerpflegungsprodukte();

        return $this;
    }

    /**
     * Berechnung des Gesamtpreises des Produktes
     * in Abhängigkeit des Produkttypes
     *
     * + 1 = pro Person
     * + 2 = pro Zimmer
     * + 3 = pro Person und Anzahl Übernachtungen
     * + 4 = Anzahl
     * + 5 = Anzahl und Anzahl Übernachtungen
     *
     * Eintragen der Zusatzprodukte in
     * die Tabelle 'produktbuchung'
     *
     */
    private function _setProduktpreisUndAllgemeineAngaben ()
    {
        for($i = 0; $i < count($this->_gebuchteZusatzprodukte); $i++) {
            $this->_gebuchteZusatzprodukte[ $i ][ 'buchungsnummer_id' ] = $this->_buchungsnummer;
            $this->_gebuchteZusatzprodukte[ $i ][ 'uebernachtungen' ] = $this->_anzahlUebernachtungen;

            // Produkttyp 3 und Produkttyp 5
            if(($this->_gebuchteZusatzprodukte[ $i ][ 'produktTyp' ] == 3) or ($this->_gebuchteZusatzprodukte[ $i ][ 'produktTyp' ] == 5)) {
                $this->_gebuchteZusatzprodukte[ $i ][ 'summeProduktPreis' ] = $this->_gebuchteZusatzprodukte[ $i ][ 'aktuellerProduktPreis' ] * $this->_gebuchteZusatzprodukte[ $i ][ 'anzahl' ] * $this->_anzahlUebernachtungen;
            } else {
                $this->_gebuchteZusatzprodukte[ $i ][ 'summeProduktPreis' ] = $this->_gebuchteZusatzprodukte[ $i ][ 'aktuellerProduktPreis' ] * $this->_gebuchteZusatzprodukte[ $i ][ 'anzahl' ];
            }

            $this->_gebuchteZusatzprodukte[ $i ][ 'anreisedatum' ] = $this->_anreisedatum;
        }

        return $this;
    }

    /**
     * Trägt Produkte in 'tbl_produktbuchung'
     *
     * Kontrolliert ob Produkte der Buchung eingetragen
     * oder ge - updatet werden.
     * + löschen Verpflegungsprodukte
     *
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _insertOderUpdateProdukteEinerBuchung ()
    {
        for($i = 0; $i < count($this->_gebuchteZusatzprodukte); $i++) {

            // ermittelt Anzahl bereits gebuchter Produkte
            $cols = array(
                'id',
                'anzahl',
                'summeProduktPreis'
            );

            $select = $this->_tabelleProduktbuchung->select();

            $select
                ->from($this->_tabelleProduktbuchung, $cols)
                ->where("buchungsnummer_id = " . $this->_buchungsnummer)
                ->where('products_id = ' . $this->_gebuchteZusatzprodukte[ $i ][ 'products_id' ])
                ->where('teilrechnungen_id = ' . $this->_teilrechnungsId);

            $query = $select->__toString();

            $bereitsVorhandeneprodukte = $this->_tabelleProduktbuchung->fetchAll($select)->toArray();

            if(count($bereitsVorhandeneprodukte) > 1)
                throw new nook_Exception($this->_error_zu_viele_datensaetze_produktbuchung);

            // insert
            if(count($bereitsVorhandeneprodukte) == 0) {
                $this->_gebuchteZusatzprodukte[ $i ][ 'teilrechnungen_id' ] = $this->_teilrechnungsId;

                $this->_tabelleProduktbuchung->insert($this->_gebuchteZusatzprodukte[ $i ]);
            } // update
            else {
                $where = array(
                    "id = " . $bereitsVorhandeneprodukte[ 0 ][ 'id' ],
                    "teilrechnungen_id = " . $this->_teilrechnungsId
                );

                $update = array(
                    'anzahl'            => $this->_gebuchteZusatzprodukte[ $i ][ 'anzahl' ],
                    'summeProduktPreis' => $this->_gebuchteZusatzprodukte[ $i ][ 'summeProduktPreis' ]
                );

                $this->_tabelleProduktbuchung->update($update, $where);
            }
        }

        return $this;
    }

    /**
     * Bestimmt ob die Produkte zum Hotel gehören.
     * Ermittelt den Preis der Produkte
     *
     * @param $__buchungsdaten
     * @return Front_Model_WarenkorbZusatzprodukte
     */
    private function _produkteEinesHotels ($__buchungsdaten)
    {
        $gebuchteProdukteDesHotelsMitPreis = array();

        $cols = array(
            "id as products_id",
            "price as aktuellerProduktPreis",
            "typ as produktTyp"
        );

        // ermitteln aller Produkte eines Hotels mit Preis
        $select = $this->_tabelleZusatzprodukteHotel->select();
        $select->from($this->_tabelleZusatzprodukteHotel, $cols)->where("property_id = " . $this->_hotelId);
        $produkteDesHotelsMitPreis = $this->_tabelleZusatzprodukteHotel->fetchAll($select)->toArray();

        // bestimmt den Preis der gebuchten Produkte
        foreach($__buchungsdaten as $produktId => $anzahl) {

            // Vergleich mit den Produkten des Hotels
            for($i = 0; $i < count($produkteDesHotelsMitPreis); $i++) {
                if($produktId == $produkteDesHotelsMitPreis[ $i ][ 'products_id' ]) {
                    $produkteDesHotelsMitPreis[ $i ][ 'anzahl' ] = $anzahl;
                    $gebuchteProdukteDesHotelsMitPreis[ ] = $produkteDesHotelsMitPreis[ $i ];
                }
            }
        }

        // speichern der Produkte mit Preis
        $this->_gebuchteZusatzprodukte = $gebuchteProdukteDesHotelsMitPreis;

        return $this;
    }

    /**
     * Mappt nach 'verpflegung'
     *
     * Filtert aus den ankommenden
     * Parametern die Radio - Button aus.
     *
     * @param $params
     * @return array
     */
    public function mapOptionenVerpflegung ($params)
    {
        $zusatzprodukte = array();

        foreach($params as $key => $value) {
            // normale Produkte
            if(!strstr($key, 'verpflegung')) {
                if($value > 0)
                    $zusatzprodukte[ $key ] = $value;
            }
            // Verpflegungsprodukte
            else{
                $hotellist = new Zend_Session_Namespace('hotellist');
                $zusatzprodukte[$value] = $hotellist->adult;
            }
        }

        return $zusatzprodukte;
    }
}
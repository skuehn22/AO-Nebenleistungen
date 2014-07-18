<?php
class Front_Model_Zusatzprodukte extends nook_Model_model
{
    // Konditionen
    private $_condition_zusatzprodukt_typ_verpflegung = 2;
    private $_condition_produkt_ist_touristische_grundleistung = 2;
    protected $condition_status_warenkorb_aktiv = 1;

    // Fehler
    private $_error_keine_buchungsnummer_vorhanden = 1220;

    // Tabellen / Views
    private $_tabelleBuchungsnummer = null;
    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleProducts = null;


    private $_db_hotels;
    private $_db_groups;
    private $_hotelId;
    private $_zimmeranzahl;
    private $_anzahlPersonen;

    private $_teilrechnungId = null; // ID der Teilrechnung einer Teilbuchung

    // Update eines Produktes
    private $_idProduktbuchungFuerUpdate = null;
    private $_idZusatzproduktFuerUpdate = null;
    private $_anzahlZusatzprodukteUpdate = null;
    private $_anzahlUebernachtungenUpdate = null;
    private $_gesamtSummeProduktUpdate = null;

    private $_zusatzprodukteEinesHotels = array();
    private $_zimmerAllerBuchungenEinerSession = array();
    private $_suchparameterHotelsuche = array();
    private $_bereitsgebuchteProdukte = array();

    private $_informationBereitsGebuchteProdukte = array();
    private $_informationZusaetzlicheProdukteFuerBuchung = array();

    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotels = Zend_Registry::get('hotels');

        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array('db' => 'front'));
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung(array('db' => 'front'));
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung(array('db' => 'front'));
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));
    }

    /**
     * Erstellt die Bread Crumb Navigation
     *
     * @param $__bereich
     * @param $__step
     * @param $__params
     * @return array
     */
    public function breadcrumbNavigation($__bereich, $__step, $__params){
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Gibt bereits gebuchte Produkte der
     * Raten zurück
     *
     * @return array
     */
    public function getBereitsGebuchteProdukte(){

        return $this->_informationBereitsGebuchteProdukte;
    }

    /**
     * Ermittelt die Zusatzprodukte
     * eines Hotels entsprechend
     * der Hotel ID
     *
     * @param $__letzteBuchung
     * @return array
     */
    public function getZusatzprodukteEinesHotels(){

        $zusatzprodukteEinesHotels = $this
            ->_setZimmerBuchung() // Betten / Zimmerbuchung der Session
            ->_setSuchparameterHotelsuche() // Suchparameter Hotelsuche aus der Session
            ->_findeProdukteEinesHotel() // ermittelt die Zusatzprodukte eines Hotels
            ->_ermittleStandardBelegungUndZimmeranzahl() // entsprechen der gebuchten Zimmer -> Standardbelegung
            ->_findeBereitsGebuchteProdukte() // findet bereits gebuchte Produkte der Raten
            ->_ermittleZusatzprodukteFuerBuchung() // ermitteln weiterer Zusatzprodukte

            ->_vorbelegungAnzahlPersonenMitNull() // vorbelegung Anzahl Personen mit 0
            ->_ermittelnPersonenanzahlVerpflegungsprodukte() // ermittelt die Personenanzahl bei Verpflegungsprodukt
            ->_entfernenPassiverProdukte(); // entfernt die Produkte die passiv geschaltet wurden


		return $zusatzprodukteEinesHotels;
	}

    /**
     * Ermittelt die Anzahl der Personen einer Teilrechnung
     *
     * die standardmäßig für Verpflegungsprodukte geschaltet werden.
     * Ergänzt die Zusatzprodukte vom Typ Verpflegung mit der personenanzahl der Teilrechnung.
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _ermittelnPersonenanzahlVerpflegungsprodukte()
    {
        // ermitteln Personenanzahl der Teilrechnung
        $parameterHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $personenAnzahl = $parameterHotelsuche->adult;

        for($i=0; $i < count($this->_informationZusaetzlicheProdukteFuerBuchung); $i++){

            if($this->_informationZusaetzlicheProdukteFuerBuchung[$i]['verpflegung'] == $this->_condition_zusatzprodukt_typ_verpflegung)
                $this->_informationZusaetzlicheProdukteFuerBuchung[$i]['personenanzahl'] = $personenAnzahl;
        }

        return $this;
    }

    /**
     * Filtert die 'aktiven' = 3, Zustzprodukte
     * eines Hotels
     *
     * @return array
     */
    private function _entfernenPassiverProdukte(){

        $zusatzprodukteEinesHotels = array();

        foreach($this->_informationZusaetzlicheProdukteFuerBuchung as $value){
            if($value['aktiv'] == 3)
                $zusatzprodukteEinesHotels[] = $value;
        }

        return $zusatzprodukteEinesHotels;
    }

    /**
     * Belegt die Anzahl der Personen
     * in der Spalte 'Anzahl' mit 0
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _vorbelegungAnzahlPersonenMitNull(){
        for($i=0; $i < count($this->_informationZusaetzlicheProdukteFuerBuchung); $i++){
            $this->_informationZusaetzlicheProdukteFuerBuchung[$i]['personenanzahl'] = 0;
        }

        return $this;
    }

    /**
     * Ermittelt ein Zusatzprodukte eines Hotels
     * für das Update.
     *
     * @return array
     */
    public function getZusatzprodukteEinesHotelsFuerUpdate()
    {

        $zusatzproduktFuerUpdate = $this
            ->_findeProdukteEinesHotel() // alle Zusatzprodukte eines Hotels
            ->_reduziereZusatzprodukteFuerUpdate(); // einzelnes Zusatzprodukt und Zusatzinformationen

        return $zusatzproduktFuerUpdate;
    }

    /**
     * Ermittelt aus den Zusatzprodukten des Hotels
     * ein Zusatzprodukt fuer das Update.
     * Ergänzt die Anzahl der Personen und die gewählten Übernachtungen
     *
     */
    private function _reduziereZusatzprodukteFuerUpdate()
    {
        $zusatzproduktFuerUpdate = array();

        for($i=0; $i < count($this->_zusatzprodukteEinesHotels); $i++){

            if($this->_idZusatzproduktFuerUpdate == $this->_zusatzprodukteEinesHotels[$i]['id']){
                $this->_zusatzprodukteEinesHotels[$i]['personenanzahl'] = $this->_anzahlZusatzprodukteUpdate;
                $this->_zusatzprodukteEinesHotels[$i]['uebernachtungen'] = $this->_anzahlUebernachtungenUpdate;

                // Neuberechnung Preis entsprechend der Preisvariante
                $this->_zusatzprodukteEinesHotels[$i]['price'] = $this->_gesamtSummeProduktUpdate / $this->_anzahlZusatzprodukteUpdate;

                $zusatzproduktFuerUpdate[] = $this->_zusatzprodukteEinesHotels[$i];
            }
        }

        return $zusatzproduktFuerUpdate;
    }

    /**
     * löscht ein einzelnes Zusatzprodukt
     * aus dem Warenkorb
     */
    public function loeschenEinzelnesZusatzprodukt(array $__params){

        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        if(empty($buchungsnummer))
            throw new nook_Exception($this->_error_keine_buchungsnummer_vorhanden);

        $where = array(
            "buchungsnummer_id = ".$buchungsnummer,
            "products_id = ".$__params['idZusatzprodukt'],
            "teilrechnungen_id = ".$__params['teilrechnungId']
        );

        $this->_tabelleProduktbuchung->delete($where);

        return;
    }

    /**
     * Gibt allgemeine Informationen
     * zur Buchung zurück.
     * + Personenanzahl
     * + Zimmeranzahl
     * + Übernachtungen
     *
     * @return array
     */
    public function getInformationenZurBuchung(){
        $information = array();
        $information['personenanzahl'] = $this->_suchparameterHotelsuche['adult'];
        $information['zimmeranzahl'] = $this->_zimmeranzahl;
        $information['uebernachtungen'] = $this->_suchparameterHotelsuche['days'];
        $information['anreisetag'] = $this->_suchparameterHotelsuche['from'];
        $information['hotelId'] = $this->_suchparameterHotelsuche['propertyId'];

        $hotel = new nook_ToolHotel();
        $hoteldaten = $hotel->setHotelId($this->_suchparameterHotelsuche['propertyId'])->getGrunddatenHotel();
        $information['ueberschrift'] = $hoteldaten['property_name'];

        return $information;
    }

    /**
     * Ermitteln weiterer möglicher Zusatzprodukte
     *
     * entsprechend der bereits getätigten Buchung für die Buchung.
     *
     * + Kontrolle ob Zusatzprodukt eine touristische Grundleistung ist
     * + Produkte der touristischen Grundleistung '$this->_zusatzprodukteEinesHotels[$i]['standardProduct'] == 2' werden ignoriert.
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _ermittleZusatzprodukteFuerBuchung(){

        $k = 0;
        $j = 0;

        // Zusatzprodukte des Hotels
        for($i=0; $i<count($this->_zusatzprodukteEinesHotels); $i++){

            // bestimmen Produkt ID
            $produktId = $this->_zusatzprodukteEinesHotels[$i]['id'];

            // bereits gebuchte Produkte
            if(array_key_exists($produktId, $this->_bereitsgebuchteProdukte)){
                $this->_informationBereitsGebuchteProdukte[$j] = $this->_zusatzprodukteEinesHotels[$i];
                $this->_informationBereitsGebuchteProdukte[$j]['anzahl'] = $this->_bereitsgebuchteProdukte[$produktId]['istBereitsGebucht'];
                $j++;
            }

            // Kontrolle ob Zusatzprodukt eine touristische Grundleistung ist
            if($this->_zusatzprodukteEinesHotels[$i]['standardProduct'] == $this->_condition_produkt_ist_touristische_grundleistung)
                continue;

            // Typ 1, je Person
            if($this->_zusatzprodukteEinesHotels[$i]['typ'] == 1){
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k] = $this->_zusatzprodukteEinesHotels[$i];
            }
            // Typ 2, je Zimmer
            elseif($this->_zusatzprodukteEinesHotels[$i]['typ'] == 2){
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k] = $this->_zusatzprodukteEinesHotels[$i];
            }
            // Typ 3, Personen * Übernachtungen
            elseif($this->_zusatzprodukteEinesHotels[$i]['typ'] == 3){
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k] = $this->_zusatzprodukteEinesHotels[$i];

                // berechne Preis für Person und Anzahl der Übernachtungen
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k]['price'] = $this->_informationZusaetzlicheProdukteFuerBuchung[$k]['price'] * $this->_suchparameterHotelsuche['days'];
            }
            // Typ 4
            elseif($this->_zusatzprodukteEinesHotels[$i]['typ'] == 4){
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k] = $this->_zusatzprodukteEinesHotels[$i];
            }
            // Typ 5, Anzahl * Übernachtungen
            elseif($this->_zusatzprodukteEinesHotels[$i]['typ'] == 5){
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k] = $this->_zusatzprodukteEinesHotels[$i];

                // berechne Preis für Anzahl und Anzahl der Übernachtungen
                $this->_informationZusaetzlicheProdukteFuerBuchung[$k]['price'] = $this->_informationZusaetzlicheProdukteFuerBuchung[$k]['price'] * $this->_suchparameterHotelsuche['days'];
            }

            $k++;
        }

        return $this;
    }

    /**
     * Sichten der gebuchten Betten / Zimmer und
     *
     * Ermitteln der Standardbelegung der Zimmer
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _ermittleStandardBelegungUndZimmeranzahl(){
        $letzteBuchung = array();

        for($i = 0; $i < count($this->_zimmerAllerBuchungenEinerSession); $i++){

            $letzteBuchung[$i]['ratenId'] = $this->_zimmerAllerBuchungenEinerSession[$i]['otaRatesConfigId'];
            $letzteBuchung[$i]['personenAnzahl'] = $this->_zimmerAllerBuchungenEinerSession[$i]['personNumbers'];
            $letzteBuchung[$i]['naechteAnzahl'] = $this->_zimmerAllerBuchungenEinerSession[$i]['nights'];

            $sql = "
                SELECT
                    `tbl_categories`.`standard_persons`
                FROM
                    `tbl_ota_rates_config`
                    INNER JOIN `tbl_categories`
                        ON (`tbl_ota_rates_config`.`category_id` = `tbl_categories`.`id`)
                WHERE (`tbl_ota_rates_config`.`id` = ".$this->_zimmerAllerBuchungenEinerSession[$i]['otaRatesConfigId'].")";

            $letzteBuchung[$i]['standardBelegung'] = $this->_db_hotels->fetchOne($sql);
            $letzteBuchung[$i]['zimmerAnzahl'] = ceil($this->_zimmerAllerBuchungenEinerSession[$i]['personNumbers'] / $letzteBuchung[$i]['standardBelegung']);

            // Berechnung Zimmeranzahl
            $this->_zimmeranzahl += $letzteBuchung[$i]['zimmerAnzahl'];

            // Personenanzahl
            $this->_anzahlPersonen += $this->_zimmerAllerBuchungenEinerSession[$i]['personNumbers'];
        }

        $this->_zimmerAllerBuchungenEinerSession = $letzteBuchung;

        return $this;
    }

    /**
     * Findet bereits gebuchte Produkte der Rate
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _findeBereitsGebuchteProdukte(){
        // finden der Produkte der gebuchten Raten
        $bereitsGebuchteProdukteDerAktuellenBuchung = $this->_findeProdukteDerAktuellenBuchung();

        // zuordnen Zimmeranzahl, Personen und Übernachtungen zu den Produkten
        $produkteDerLetztenBuchungMitZimmeranzahlPersonenanzahlNaechte = $this->_zuordnungPersonenanzahlZimmeranzahlUebernachtungen($bereitsGebuchteProdukteDerAktuellenBuchung);

        // Berechnung Ist - Zustand der Produkte
        $this->_istZustandProdukt($produkteDerLetztenBuchungMitZimmeranzahlPersonenanzahlNaechte);

        return $this;
    }

    /**
     * Ermittelt die Produkte der Raten
     * Berechnet Anzahl der Raten entsprechend
     * der Buchungsform
     *
     * @param $__produkte
     * @return
     */
    private function _istZustandProdukt($__produkte){
        $istZustandProdukt = array();

        for($i=0; $i<count($__produkte); $i++){
            $produktId = $__produkte[$i]['produktId'];

            if($__produkte[$i]['typ'] == 1)
                $anzahl = $__produkte[$i]['personNumbers'];
            elseif($__produkte[$i]['typ'] == 2)
                $anzahl = $__produkte[$i]['roomNumbers'];
            elseif($__produkte[$i]['typ'] == 3)
                $anzahl = $__produkte[$i] = $__produkte[$i]['personNumbers'] * $__produkte[$i]['nightsNumbers'];
            elseif($__produkte[$i]['typ'] == 4)
                $anzahl = $__produkte[$i]['roomNumbers'];

            $istZustandProdukt[$produktId]['istBereitsGebucht'] = $anzahl;
        }

        // speichern der bereits gebuchten Produkte
        $this->_bereitsgebuchteProdukte = $istZustandProdukt;

        return;
    }

    /**
     * Ergänzung der Produkte einer Rate um
     *
     * + Zimmeranzahl
     * + Personenanzahl
     * + Nächte
     *
     * @param $__produkte
     * @return mixed
     */
    private function _zuordnungPersonenanzahlZimmeranzahlUebernachtungen($__produkte){

        // Produkte der Raten
        for($i=0; $i<count($__produkte); $i++){

            // gebuchte Raten
            for($j=0; $j<count($this->_zimmerAllerBuchungenEinerSession); $j++){
                if($__produkte[$i]['ratenId'] == $this->_zimmerAllerBuchungenEinerSession[$j]['ratenId']){

                    $__produkte[$i]['roomNumbers'] = $this->_zimmerAllerBuchungenEinerSession[$j]['zimmerAnzahl'];
                    $__produkte[$i]['personNumbers'] = $this->_zimmerAllerBuchungenEinerSession[$j]['personenAnzahl'];
                    $__produkte[$i]['nightsNumbers'] = $this->_zimmerAllerBuchungenEinerSession[$j]['naechteAnzahl'];

                    break;
                }
            }
        }

        return $__produkte;
    }

    /**
     * Findet die Produkte einer Rate
     *
     * @return array
     */
    private function _findeProdukteDerAktuellenBuchung(){
        $bereitsVorhandeneprodukte = array();

        for($i=0; $i < count($this->_zimmerAllerBuchungenEinerSession); $i++){

            $sql = "
                SELECT
                    `tbl_products`.`id` AS `produktId`
                    , `tbl_products`.`typ`
                FROM
                    `tbl_ota_rates_config`
                    INNER JOIN `tbl_ota_rates_products`
                        ON (`tbl_ota_rates_config`.`id` = `tbl_ota_rates_products`.`rates_id`)
                    INNER JOIN `tbl_categories`
                        ON (`tbl_ota_rates_config`.`category_id` = `tbl_categories`.`id`)
                    INNER JOIN `tbl_products`
                        ON (`tbl_ota_rates_products`.`products_id` = `tbl_products`.`id`)
                WHERE (`tbl_ota_rates_config`.`id` = ".$this->_zimmerAllerBuchungenEinerSession[$i]['ratenId'].")";

            $produkteEinerRate = $this->_db_hotels->fetchAll($sql);

            // Produkte einer Rate
            for($j=0; $j<count($produkteEinerRate); $j++){
                $produkteEinerRate[$j]['ratenId'] = $this->_zimmerAllerBuchungenEinerSession[$i]['ratenId'];
            }

            // zusammenfasung der Produkte einer Rate
            $bereitsVorhandeneprodukte = array_merge($bereitsVorhandeneprodukte, $produkteEinerRate);
        }

        return $bereitsVorhandeneprodukte;
    }

    /**
     * Speichert die
     * Suchparameter der Hotelsuche aus der Session
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _setSuchparameterHotelsuche(){
        $suchparameter = new Zend_Session_Namespace('hotelsuche');
        $this->_suchparameterHotelsuche = (array) $suchparameter->getIterator();

        return $this;
    }


    /**
     * ermittelt die gebuchten Raten / Zimmer
     * aller Hotelbuchungen einer Session
     *
     * @param $__letzteBuchung
     * @return Front_Model_Zusatzprodukte
     */
    private function _setZimmerBuchung()
    {
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsnummer;
        $whereTeilrechnungId = "teilrechnungen_id = ".$this->_teilrechnungId;

        $cols = array(
            'otaRatesConfigId',
            'roomNumbers',
            'personNumbers',
            'nights'
        );

        $select = $this->_tabelleHotelbuchung->select();
        $select
            ->from($this->_tabelleHotelbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereTeilrechnungId);

        $query = $select->__toString();

        $buchungen = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        $this->_zimmerAllerBuchungenEinerSession = $buchungen;

        return $this;
    }

    /**
     * setzen der Hotel ID
     *
     * @param $__hotelId
     * @return Front_Model_Zusatzprodukte
     */
    public function setHotelId($__hotelId){
        $this->_hotelId = $__hotelId;

        return $this;
    }

    /**
     * Setzt die ID der Teilrechnung
     *
     * @param $teilrechnungId
     * @return Front_Model_Zusatzprodukte
     */
    public function setTeilrechnungId($teilrechnungId){

        $this->_teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * Übernimmt die ID des Hotels
     * durch Auswertung der ID des
     * Zusatzproduktes
     *
     * @param $__idProduktbuchung
     * @return mixed
     */
    public function setHotelIdUndDatenZusatzprodukt($__idProduktbuchung){
        // ID Produktbuchung
        $this->_idProduktbuchungFuerUpdate = $__idProduktbuchung;

        // ermitteln ID Zusatzprodukt
        $datenProduktBuchung = $this->_tabelleProduktbuchung->find($__idProduktbuchung)->toArray();
        $this->_idZusatzproduktFuerUpdate = $datenProduktBuchung[0]['products_id'];

        // Anzahl Zusatzprodukte
        $this->_anzahlZusatzprodukteUpdate = $datenProduktBuchung[0]['anzahl'];

        // Anzahl Uebernachtungen
        $this->_anzahlUebernachtungenUpdate = $datenProduktBuchung[0]['uebernachtungen'];

        // Gesamtsumme Produkt Update
        $this->_gesamtSummeProduktUpdate = $datenProduktBuchung[0]['summeProduktPreis'];

        // Hotel ID
        $datenGebuchtesProdukt = $this->_findHoteldatenMitIdZusatzprodukt($datenProduktBuchung[0]['products_id']);
        $this->_hotelId = $datenGebuchtesProdukt['property_id'];

        return $this;
    }

    /**
     * Hotel ID wird mittels eines bereits gebuchten
     * Produktbuchung ermittelt.
     *
     * @param $__idZusatzprodukt
     * @return array $datenGebuchtesProdukt
     */
    private function _findHoteldatenMitIdZusatzprodukt($__idZusatzprodukt){
        $where = "id = ".$__idZusatzprodukt;

        $select = $this->_tabelleProducts->select();
        $select->where($where);

        $datenGebuchtesProdukt = $this->_tabelleProducts->fetchRow($select)->toArray();

        return $datenGebuchtesProdukt;
    }

    /**
     * Ermittelt die Zusatzprodukte eines Hotels.
     * Speichert die Zusatzprodukte entsprechend
     * der gewählten Sprache.
     * Wählt nur Produkte aus, die einen
     * Preis haben.
     *
     * @return Front_Model_Zusatzprodukte
     */
    private function _findeProdukteEinesHotel(){
		$translate = new Zend_Session_Namespace('translate');

        // deutsche Beschreibung
        if($translate->language == 'de'){
            $sql = "
            SELECT
                `price`
                , `typ`
                , `ger` as beschreibung
                , `vat`
                , `id`
                , `product_name` as name
                , `standardProduct`
                , `verpflegung`
                , `aktiv`
            FROM
                `tbl_products`
            WHERE (`property_id` = " .$this->_hotelId. "
                AND `price` > 0) ORDER BY verpflegung desc, typ, name";
        }
        // englische Beschreibung
        else{
            $sql = "
            SELECT
                `price`
                , `typ`
                , `vat`
                , `id`
                , `product_name_en` as name
                , `eng` as beschreibung
                , `standardProduct`
                , `verpflegung`
                , `aktiv`
            FROM
                `tbl_products`
            WHERE (`property_id` = ".$this->_hotelId."
                AND `price` > 0) ORDER BY verpflegung desc, typ, name";
        }

        $this->_zusatzprodukteEinesHotels = $this->_db_hotels->fetchAll($sql);

        return $this;
    }
}
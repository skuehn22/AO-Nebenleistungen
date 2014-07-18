<?php
class Front_Model_WarenkorbShoppingcartZusatzprodukte extends nook_ToolModel
{

    private $_kundenId = null;
    private $_sessionId = null;
    private $_buchungsNummern = array();
    private $_sprache = null;

    private $_zusatzProdukteAllerBestellungen = null;
    private $_zusatzProdukteAllerBestellungenNested = array();

    // Tabellen / Views
    private $_tabelleProduktbuchung = null;
    private $_tabelleProperties = null;

    // Fehler
    private $_error = 460;

    // Tabellen / Views

    // Konditionen
    private $_condition_artikel_bereits_gebucht = 3;

    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
    }


    public function getShoppingcartZusatzprodukte($__buchungsnummern){
        $this
            ->_setSessionId()  // Session ID
            ->_setKundenId() // Kunden ID wenn vorhanden
            ->_setSprache() // findet Anzeigesprache
            ->_setBuchungsnummern($__buchungsnummern) // setzt die bereits vorhandenen Buchungsnummern
            // ->_findBuchungsNummer()
            ->_getZusatzprodukte()
            ->_findAktuellePreisUndBeschreibung()
            ->_findStadtName();
            // ->_getZusatzprodukteNested();

        // return $this->_zusatzProdukteAllerBestellungenNested;

        return $this->_zusatzProdukteAllerBestellungen;
    }

    /**
     * Findet den Stadtnamen mittels der PropertyId des Hotels
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _findStadtName(){

        for($i=0; $i < count($this->_zusatzProdukteAllerBestellungen); $i++){

            $hotel = new nook_ToolHotel();
            $grundDatenHotel = $hotel
                ->setHotelId($this->_zusatzProdukteAllerBestellungen[$i]['property_id'])
                ->getGrunddatenHotel();

            $city = nook_ToolStadt::getStadtNameMitStadtId($grundDatenHotel['city_id']);
            $this->_zusatzProdukteAllerBestellungen[$i]['city'] = $city;
        }



        return $this;
    }

    /**
     * Speichert die vorhandenen Buchungsnummern
     *
     * @param $__buchungsnummern
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _setBuchungsnummern($__buchungsnummern){
        $this->_buchungsNummern = $__buchungsnummern;

        return $this;
    }


    /**
     * Ermittelt die Hotelbeschreibung
     * und die Gesamtsumme der gebuchten Zusatzprodukte.
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _findAktuellePreisUndBeschreibung(){
        for($i=0; $i<count($this->_zusatzProdukteAllerBestellungen); $i++){

            // Hotelbeschreibung deutsch
            if($this->_sprache == 'de'){
                $sql = "SELECT
                    `tbl_products`.`ger` AS `beschreibung`
                    , `tbl_products`.`product_name` AS `name`
                    , `tbl_products`.`price` AS `aktuellerProduktPreis`
                    , `tbl_products`.`property_id`
                    , `tbl_properties`.`property_name` AS `hotelName`
                FROM
                    `tbl_products`
                    INNER JOIN `tbl_properties`
                        ON (`tbl_products`.`property_id` = `tbl_properties`.`id`)
                WHERE (`tbl_products`.`id` = ".$this->_zusatzProdukteAllerBestellungen[$i]['products_id'].")";

            }
            // Hotelbeschreibung englisch
            else{
                $sql = "SELECT
                    `tbl_products`.`eng` AS `beschreibung`
                    , `tbl_products`.`product_name_en` AS `name`
                    , `tbl_products`.`price` AS `aktuellerProduktPreis`
                    , `tbl_products`.`property_id`
                    , `tbl_properties`.`property_name` AS `hotelName`
                FROM
                    `tbl_products`
                    INNER JOIN `tbl_properties`
                        ON (`tbl_products`.`property_id` = `tbl_properties`.`id`)
                WHERE (`tbl_products`.`id` = ".$this->_zusatzProdukteAllerBestellungen[$i]['products_id'].")";
            }

            $aktuelleInformationen = $this->_db_hotel->fetchRow($sql);
            $this->_zusatzProdukteAllerBestellungen[$i] = array_merge($this->_zusatzProdukteAllerBestellungen[$i], $aktuelleInformationen);

            // Fallunterscheidung zur Berechnung des Preises
            if( ($this->_zusatzProdukteAllerBestellungen[$i]['produktTyp'] == 3) or ($this->_zusatzProdukteAllerBestellungen[$i]['produktTyp'] == 5) )
                $this->_zusatzProdukteAllerBestellungen[$i]['summeProduktPreis'] = $this->_zusatzProdukteAllerBestellungen[$i]['anzahl'] * $this->_zusatzProdukteAllerBestellungen[$i]['aktuellerProduktPreis'] * $this->_zusatzProdukteAllerBestellungen[$i]['uebernachtungen'];
            else
                $this->_zusatzProdukteAllerBestellungen[$i]['summeProduktPreis'] = $this->_zusatzProdukteAllerBestellungen[$i]['anzahl'] * $this->_zusatzProdukteAllerBestellungen[$i]['aktuellerProduktPreis'];
        }

        return $this;
    }


    /**
     * findet die Anzeigesprache
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _setSprache(){

        $this->_sprache = nook_ToolSprache::getAnzeigesprache();

        return $this;
    }

    /**
     * Findet die Session ID
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _setSessionId(){
        $this->_sessionId = Zend_Session::getId();

        return $this;
    }

    /**
     * Findet Kunden ID wenn vorhanden
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _setKundenId(){
        $warenkorb = new Zend_Session_Namespace('warenkorb');
        if(!empty($warenkorb->kundenId))
            $this->_kundenId = $warenkorb->kundenId;

        return $this;
    }

    /**
     * Findet die Buchungsnummer
     * entweder über KundenId oder SessionId
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _findBuchungsNummer(){
        if(empty($this->_kundenId))
            $sql = "select id from tbl_buchungsnummer where session_id = '".$this->_sessionId."'";
        else
            $sql = "select id from tbl_buchungsnummer where kunden_id = ".$this->_kundenId;

        $buchungsnummern = $this->_db_groups->fetchAll($sql);
        $this->_buchungsNummern = $buchungsnummern;

        return $this;
    }

    /**
     * Ermittelt die Zusatzprodukte
     * entsprechend der Buchungsnummern.
     * Es werden nur die Zusatzprodukte angezeigt,
     * die noch nicht gebucht wurden.
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _getZusatzprodukte(){

        $zusatzProdukteAllerBestellungen = array();

        for($i=0; $i<count($this->_buchungsNummern); $i++){

            // Datensatz Produktbuchung
            $select = $this->_tabelleProduktbuchung->select();
            $select->where("buchungsnummer_id = ".$this->_buchungsNummern[$i]['id'])->where("status < ".$this->_condition_artikel_bereits_gebucht);
            $zusatzprodukteEinerBestellung = $this->_tabelleProduktbuchung->fetchAll($select)->toArray();

            $zusatzProdukteAllerBestellungen = array_merge($zusatzProdukteAllerBestellungen, $zusatzprodukteEinerBestellung);
        }

        $this->_zusatzProdukteAllerBestellungen = $zusatzProdukteAllerBestellungen;

        return $this;
    }

    /**
     * Gruppiert die Zusatzprodukte nach Hotel, Anreisedatum
     * und Übernachtungen
     *
     * @return Front_Model_WarenkorbShoppingcartZusatzprodukte
     */
    private function _getZusatzprodukteNested(){

        // Kontrolle und anlegen Hotelblock
        for($i=0; $i<count($this->_zusatzProdukteAllerBestellungen); $i++){
            $this->_kontrolleAnlegenNestedHotelblock($this->_zusatzProdukteAllerBestellungen[$i]);
        }

        // zuordnen der Zusatzprodukte zum Hotelblock
        for($i=0; $i<count($this->_zusatzProdukteAllerBestellungen); $i++){
            $this->_zuordnenZusatzproduktZumHotelblock($this->_zusatzProdukteAllerBestellungen[$i]);
        }

        return $this;
    }

    /**
     * Kontrolliert ob der Hotelblock existiert.
     * Wenn nicht, dann wird er neu angelegt.
     *
     * @param $__datensatzZusatzprodukt
     */
    private function _kontrolleAnlegenNestedHotelblock($__datensatzZusatzprodukt){

        $hotelBlockVorhanden = false;

        // Kontrolle ob Hotelblock existiert
        for($i=0; $i < count($this->_zusatzProdukteAllerBestellungenNested); $i++){
            // Kontrolle Hotel ID
            if( $this->_zusatzProdukteAllerBestellungenNested[$i]['propertyId'] ==   $__datensatzZusatzprodukt['property_id']){
                // Kontrolle Anreisedatum
                if( $this->_zusatzProdukteAllerBestellungenNested[$i]['anreisedatum'] ==   $__datensatzZusatzprodukt['anreisedatum']){
                    // Kontrolle Anzahl Übernachtungen
                    if($this->_zusatzProdukteAllerBestellungenNested[$i]['uebernachtungen'] == $__datensatzZusatzprodukt['uebernachtungen']){
                        $hotelBlockVorhanden = true;

                        break;
                    }
                }
            }
        }

        // anlegen neuer Hotelblock
        if($hotelBlockVorhanden === false){
            $this->_zusatzProdukteAllerBestellungenNested[] = array(
                'propertyId' => $__datensatzZusatzprodukt['property_id'],
                'anreisedatum' => $__datensatzZusatzprodukt['anreisedatum'],
                'uebernachtungen' => $__datensatzZusatzprodukt['uebernachtungen'],
                'buchungsnummerID' => $__datensatzZusatzprodukt['buchungsnummer_id'],
                'teilrechnungenID' => $__datensatzZusatzprodukt['teilrechnungen_id'],
                'buchungsdatum' => $__datensatzZusatzprodukt['buchungsdatum'],
                'hotelName' => $__datensatzZusatzprodukt['hotelName'],
                'city' => $__datensatzZusatzprodukt['city']
            );
        }

        return;
    }

    /**
     * Schreibt Zusatzprodukt zum bereits
     * existierenden Hotelblock.
     *
     * @param $__datensatzZusatzprodukt
     */
    private function _zuordnenZusatzproduktZumHotelblock($__datensatzZusatzprodukt){

        for($i=0; $i < count($this->_zusatzProdukteAllerBestellungenNested); $i++){
            // Kontrolle Hotel ID
            if( $this->_zusatzProdukteAllerBestellungenNested[$i]['propertyId'] ==   $__datensatzZusatzprodukt['property_id']){
                // Kontrolle Anreisedatum
                if( $this->_zusatzProdukteAllerBestellungenNested[$i]['anreisedatum'] ==   $__datensatzZusatzprodukt['anreisedatum']){
                    // Kontrolle Anzahl Übernachtungen
                    if($this->_zusatzProdukteAllerBestellungenNested[$i]['uebernachtungen'] == $__datensatzZusatzprodukt['uebernachtungen']){
                        $this->_zusatzProdukteAllerBestellungenNested[$i]['zusatzprodukte'][] = array(
                            'buchungsdatensatzId' => $__datensatzZusatzprodukt['id'],
                            'teilrechnungenId' => $__datensatzZusatzprodukt['teilrechnungen_id'],
                            'productsId' => $__datensatzZusatzprodukt['products_id'],
                            'aktuellerProduktPreis' => $__datensatzZusatzprodukt['aktuellerProduktPreis'],
                            'anzahl' => $__datensatzZusatzprodukt['anzahl'],
                            'summeProduktPreis' => $__datensatzZusatzprodukt['summeProduktPreis'],
                            'produktTyp' => $__datensatzZusatzprodukt['produktTyp'],
                            'status' => $__datensatzZusatzprodukt['status'],
                            'name' => $__datensatzZusatzprodukt['name'],
                            'beschreibung' => $__datensatzZusatzprodukt['beschreibung']
                        );
                    }
                }
            }
        }

        return;
    }

   

}
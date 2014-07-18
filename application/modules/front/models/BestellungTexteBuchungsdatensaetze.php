<?php
/**
 * 21.11.12 16:22
 * Erstellt die Texte der Buchungsdatensätze.
 * Texte werden in der Anzeigesprache erstellt.
 *
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 */

class Front_Model_BestellungTexteBuchungsdatensaetze{

    // Error
    private $_error_mwst_unbekannt = 1030;
    private $_error_bruttopreis_unbekannt = 1031;

    // Konditionen
    private $_condition_mwst_uebernachtung_deutschland = 7;
    private $_condition_einhundert_prozent = 100;

    // Tabellen / Views
    private $_tabelleProperties = null;
    private $_tabelleAoCity = null;
    private $_tabelleOtaRatesConfig = null;
    private $_tabelleProducts = null;

    public function __construct(){

        /** @var _tabelleCity Application_Model_DbTable_aoCity */
        $this->_tabelleAoCity = new Application_Model_DbTable_aoCity();
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
        /** @var _tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig */
        $this->_tabelleOtaRatesConfig = new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));
    }

    /**
     * Texte der Hotelbuchung
     *
     * @param $__hotelBuchung
     * @return array
     */
    public function texteHotelbuchungen($__hotelBuchung){

        $texteHotelbuchungen = array();

        $texteHotelbuchungen['stadt'] = translate('Stadt').": ".nook_ToolStadt::getStadtNameMitStadtId($__hotelBuchung['cityId']);

        $hotelDatenTool = new nook_ToolHotel();
        $datensatzHotel = $hotelDatenTool->setHotelId($__hotelBuchung['propertyId'])->getGrunddatenHotel();
        $texteHotelbuchungen['hotel'] = translate('Hotelname').": ".$datensatzHotel['property_name'];

        $rateDatenTool = new nook_ToolRate();
        $datensatzRate = $rateDatenTool->setRateId($__hotelBuchung['otaRatesConfigId'])->getRateData();
        $texteHotelbuchungen['rate'] = translate('Zimmerbezeichnung').": ".$datensatzRate['name'];

        $texteHotelbuchungen['naechte'] = translate('Nächte').": ".$__hotelBuchung['nights'];
        $texteHotelbuchungen['zimmeranzahl'] = translate('Zimmeranzahl').": ".$__hotelBuchung['roomNumbers'];
        $texteHotelbuchungen['personenanzahl'] = translate('Personenanzahl').": ".$__hotelBuchung['personNumbers'];

        // Datum nach Anzeigesprache
        $anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
        $texteHotelbuchungen['anreisedatum'] =  translate('Anreisedatum').": ".nook_ToolZeiten::generiereDatumNachAnzeigesprache($__hotelBuchung['startDate'], $anzeigeSprache);

        // Zimmerpreis oder Personenpreis
        if(!empty($__hotelBuchung['roomPrice']))
            $texteHotelbuchungen['preis'] = $__hotelBuchung['roomPrice'];
        else
            $texteHotelbuchungen['preis'] = $__hotelBuchung['personPrice'];



        // Berechnung Gesamtpreis
        if(!empty($__hotelBuchung['roomPrice']))
            $gesamtpreisBrutto = $__hotelBuchung['roomNumbers'] * $__hotelBuchung['nights'] * $texteHotelbuchungen['preis'];
        else
            $gesamtpreisBrutto = $__hotelBuchung['personNumbers'] * $__hotelBuchung['nights'] * $texteHotelbuchungen['preis'];

        $texteHotelbuchungen['leer1'] = "##"; // Leerzeile

        if(!empty($__hotelBuchung['roomPrice']))
            $texteHotelbuchungen['preis'] = translate('Zimmerpreis').": ".translatePricing($texteHotelbuchungen['preis'])." €";
        else
            $texteHotelbuchungen['preis'] = translate('Personenpreis').": ".translatePricing($texteHotelbuchungen['preis'])." €";

        $texteHotelbuchungen['mwst'] = translate("Mehrwertsteuer").": ".$__hotelBuchung['mwst']." %";

        $nettopreis = $this->_nettoPreis($gesamtpreisBrutto, $this->_condition_mwst_uebernachtung_deutschland);
        $texteHotelbuchungen['gesamtpreisNetto'] = translate('Netto Gesamtpreis').": ".translatePricing($nettopreis)." €";
        $texteHotelbuchungen['gesamtpreisBrutto'] = translate('Brutto Gesamtpreis').": ".translatePricing($gesamtpreisBrutto)." €";

        return $texteHotelbuchungen;
    }

    public function texteProduktbuchungen($__produktBuchung){

        $texteProduktbuchung = array();
        $anzeigeSprache = $language = Zend_Registry::get('language');

        // Daten des Produktes
        $datenDesProduktes = $this->_tabelleProducts->find($__produktBuchung['products_id'])->toArray();

        // Daten des Hotels
        $hotelDatenTool = new nook_ToolHotel();
        $datensatzHotel = $hotelDatenTool->setHotelId($datenDesProduktes[0]['property_id'])->getGrunddatenHotel();
        $texteProduktbuchung['hotel'] = translate('Hotelname').": ".$datensatzHotel['property_name'];

        // Daten der Stadt
        $cityId = $datensatzHotel['city_id'];
        $texteProduktbuchung['stadt'] = translate('Stadt').": ".nook_ToolStadt::getStadtNameMitStadtId($cityId);

        // Produktbeschreibung
        if($anzeigeSprache == 'de'){
            $texteProduktbuchung['produktbezeichnung'] = translate('zusätzlich gewählt').": ".$datenDesProduktes[0]['product_name'];
            $texteProduktbuchung['produktbeschreibung'] = $datenDesProduktes[0]['ger'];
        }

        else{
            $texteProduktbuchung['produktbezeichnung'] = translate('zusätzlich gewählt').": ".$datenDesProduktes[0]['product_name_en'];
            $texteProduktbuchung['produktbeschreibung'] = $datenDesProduktes[0]['eng'];
        }

        $texteProduktbuchung['anzahl'] = translate('Anzahl').": ".$__produktBuchung['anzahl'];

        $texteHotelbuchungen['leer1'] = "##"; // Leerzeile

        $texteProduktbuchung['mwst'] = translate('Mehrwertsteuer').": ".$__produktBuchung['mwst']." %";
        $nettoPreis = $this->_nettoPreis($__produktBuchung['summeProduktPreis'], $__produktBuchung['mwst']);
        $texteProduktbuchung['gesamtpreisNetto'] = translate('Netto Gesamtpreis').": ".translatePricing($nettoPreis)." €";
        $texteProduktbuchung['gesamtpreisBrutto'] = translate('Brutto Gesamtpreis').": ".translatePricing($__produktBuchung['summeProduktPreis'])." €";

        return $texteProduktbuchung;
    }

    /**
     * Berechnung Nettopreis
     *
     * @param $__mwst
     * @param $__bruttoPreis
     */
    private function _nettoPreis($__bruttoPreis = false, $__mwst = false){
        if(empty($__mwst))
            throw new nook_Exception($this->_error_mwst_unbekannt);

        if(empty($__bruttoPreis))
            throw new nook_Exception($this->_error_bruttopreis_unbekannt);

        // Berechnung Nettopreis
        $einProzent = $__bruttoPreis / ($this->_condition_einhundert_prozent + $__mwst);
        $nettopreis = $einProzent * $this->_condition_einhundert_prozent;

        return $nettopreis;
    }

} // end class

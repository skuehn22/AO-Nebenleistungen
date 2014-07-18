<?php
/**
* Verwaltung aller gebuchten Übernachtungen, editieren der Übernachtungen
 *
* + Setzt das Array der Buchungsnummern
* + Gibt alle Hotelbuchungen eines Kunden
* + Erstellt aus den Hotelbuchungen ein nested Template.
* + Ermittelt die Raten zu den vorhandenen
* + Ermittelt die Hotels für den 'nested' Block der
* + Hilfsfunktion.
* + Findet den Hotelnamen, die Hotelbeschreibung
* + Bestimmt die Anzeigesprache des Warenkorbes
* + Findet die gebuchten Übernachtungen der Buchungsnummern
* + findet die Kategori-Id der Raten
* + Berechnet den Gesamtpreis einer Rate
* + Kontrolliert ob User / Session die Buchung löschen darf.
* + Löscht die Hotelbuchung aus den Tabellen
* + Ermitteln des Hotelnamen
* + Beschreibung des Hotel in Abhängigkeit der Anzeigesprache
* + ermitteln des Namen der kategorie in Abhängigkeit der Anzeigesprache
*
* @author Stephan.Krauss
* @date 18.11.2013
* @file CartHotel.php
* @package front
* @subpackage model
*/
class Front_Model_CartHotel extends Pimple_Pimple{

    private $_idAnzeigesprache;
    private $_kennungAnzeigesprache;

    private $_buchungen = array();
    private $_buchungsnummern = array();

    // Tabellen / Views / Datenbanken
    private $_db_front;
    private $_db_hotel;

    private $_tabelleHotelbuchung = null;
    private $_tabelleOtaRatesConfig = null;
    private $_tabelleProperties = null;
    private $_tabellePropertyDetails = null;
    private $_tabelleCategories = null;

    private $_viewHotelbuchung = null;
    private $_viewHotelbeschreibung = null;

    // Fehler
    private $_error_hotelbuchung_nicht_geloescht = 640;
    private $_error_keine_hotelbeschreibung_vorhanden = 641;
    private $_error_keine_daten_vorhanden = 642;

    // Konditionen
    private $_condition_id_deutsche_sprache = 1;
    private $_condition_id_englische_sprache = 2;
    private $_condition_artikel_bereits_gebucht = 3;
    private $_condition_artikel_ausgebucht = 6;

    public function __construct(){ 
        // Datenbanken
        $this->_db_front = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        // Anzeige Sprache
        $this->_anzeigeSprache = Zend_Registry::get('language');

        // Dependency Injection Controller
        $this->offsetSet('WarenkorbHilfe', function(){
            return new nook_WarenkorbHilfe();
        });

        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig */
        $this->_tabelleOtaRatesConfig = new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));

        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
        /** @var _tabellePropertyDetails Application_Model_DbTable_propertyDetails */
        $this->_tabellePropertyDetails = new Application_Model_DbTable_propertyDetails(array('db' => 'hotels'));
        /** @var _tabelleCategories Application_Model_DbTable_categories */
        $this->_tabelleCategories = new Application_Model_DbTable_categories(array('db' => 'hotels'));

        /** @var _viewHotelbuchung Application_Model_DbTable_viewHotelbuchung */
        $this->_viewHotelbuchung = new Application_Model_DbTable_viewHotelbuchung();
        /** @var _viewHotelbeschreibung Application_Model_DbTable_viewHotelbeschreibung */
        $this->_viewHotelbeschreibung = new Application_Model_DbTable_viewHotelbeschreibung(array('db' => 'hotels'));

        return;
    }

    /**
     * Setzt das Array der Buchungsnummern
     * aller Buchungen des Kunden oder
     * die Buchungen einer Session
     *
     * @param array $__buchungsnummern
     * @return Front_Model_CartHotel
     */
    public function setBuchungsnummern(array $__buchungsnummern){
        $this->_buchungsnummern = $__buchungsnummern;

        return $this;
    }

    /**
     * Gibt alle Hotelbuchungen eines Kunden
     * oder einer Session zurück
     *
     * @return array
     */
    public function getHotelbuchungen(){
        $this
            ->_bestimmeAnzeigeSprache()
            ->_findHotelbuchungen()
            ->_findKategorieId()
            ->_findHotelbeschreibung()
            ->_berechnungGesamtpreisEinerRate();

        return $this->_buchungen;
    }

    /**
     * Erstellt aus den Hotelbuchungen ein nested Template.
     * Hotelinformationen werden von der rateninformation getrennt.
     * Darstellung im Templat als nested Set.
     *
     * @param $__shoppingCartHotel
     * @return
     */
    public function buildNestedTemplateHotelbuchung(){
        // vorhandene Hotels nested
        $shoppingCartHotel = $this->_buildNestedTemplateHotelbuchungHotels();

        // Zuordnung der Raten zu den Hotels
        $shoppingCartHotel = $this->_buildNestedTemplateHotelbuchungRaten($shoppingCartHotel);

        return $shoppingCartHotel;
    }

    /**
     * Ermittelt die Raten zu den vorhandenen
     * Hotelbloecken
     *
     * @param $__hotelBloecke
     * @return mixed
     */
    private function _buildNestedTemplateHotelbuchungRaten($__hotelBloecke){

        for($i=0; $i<count($this->_buchungen); $i++){
            for($j=0; $j < count($__hotelBloecke); $j++){
                if($__hotelBloecke[$j]['propertyId'] == $this->_buchungen[$i]['propertyId']){
                    if($__hotelBloecke[$j]['anreisedatum'] == trim($this->_buchungen[$i]['startDate'])){
                        $__hotelBloecke[$j]['raten'][] = $this->_buchungen[$i];

                        continue;
                    }
                }
            }
        }

        return $__hotelBloecke;
    }

    /**
     * Ermittelt die Hotels für den 'nested' Block der
     * Übernachtungen
     *
     * @return array
     */
    private function _buildNestedTemplateHotelbuchungHotels(){
        $shoppingCartHotel = array();
        $hotelInformation = array();

        for($i=0; $i<count($this->_buchungen); $i++){

            $hotelInformation['propertyId'] =  $this->_buchungen[$i]['propertyId'];
            $hotelInformation['city'] = $this->_buchungen[$i]['city'];
            $hotelInformation['cityId'] = $this->_buchungen[$i]['cityId'];
            $hotelInformation['hotelUeberschrift'] = $this->_buchungen[$i]['hotelUeberschrift'];
            $hotelInformation['hotelTxt'] = $this->_buchungen[$i]['hotelTxt'];
            $hotelInformation['anreisedatum'] = trim($this->_buchungen[$i]['startDate']);
            $hotelInformation['nights'] = $this->_buchungen[$i]['nights'];
            $hotelInformation['teilrechnung_id'] = $this->_buchungen[$i]['teilrechnung_id'];

            $hotelInformation['raten'] = array();

            /*** Aufbau der Hotelblöcke ***/
            $flagNeuesHotel = true;

            // Erststart
            if(count($shoppingCartHotel) == 0){
                $shoppingCartHotel[] = $hotelInformation;

                $flagNeuesHotel = false;
            }
            // neuer Hotelblock
            else{
                for($j=0; $j < count($shoppingCartHotel); $j++){
                    if( $shoppingCartHotel[$j]['propertyId'] == $hotelInformation['propertyId'] ){
                        if(  $shoppingCartHotel[$j]['anreisedatum'] == $hotelInformation['anreisedatum']){
                            $flagNeuesHotel = false;
                        }
                    }
                }
            }

            // eintragen neuer Hotelblock
            if(!empty($flagNeuesHotel)){
                $shoppingCartHotel[] = $hotelInformation;

                $flagNeuesHotel = true;
            }
        }

        return $shoppingCartHotel;
    }

    /**
     * Hilfsfunktion.
     * Ermittelt aus einer bereits vorhandenen
     * Shopping Cart / nested Format, die
     * Anzahl der gebuchten Raten und
     * den Gesamtpreis
     *
     * @param $__shoppingCartHotel
     * @return void
     */
    public function findProductsItems($__shoppingCartHotel){
        $items = array();
        $items['count'] = 0;
        $items['totalPrice'] = 0;

        for($i=0; $i<count($__shoppingCartHotel); $i++){
            foreach($__shoppingCartHotel[$i]['raten'] as $zaehler => $rate ){
                $items['count']++;
                $items['totalPrice'] += $rate['gesamtpreis'];
            }
        }

        return $items;
    }

    /**
     * Findet den Hotelnamen, die Hotelbeschreibung
     * und den Ratenname
     *
     * @return void
     */
    private function _findHotelbeschreibung(){

        for($i=0; $i < count($this->_buchungen); $i++){

            $beschreibung = array();

            // Hotelname
            $beschreibung = $this->ermittelnHotelbeschreibung($i, $beschreibung);

            // Hotelbeschreibung
            $beschreibung = $this->hotelbeschreibung($i, $beschreibung);

            // Kategorie
            $beschreibung = $this->ermittelnKategorieName($i, $beschreibung);

            $this->_buchungen[$i] = array_merge($this->_buchungen[$i], $beschreibung);
        }

        return $this;
    }

    /**
     * Bestimmt die Anzeigesprache des Warenkorbes
     *
     * @return Front_Model_CartHotel
     */
    private function _bestimmeAnzeigeSprache(){
        /** @var $translate Zend_Session_Namespace */
        $translate = new Zend_Session_Namespace('translate');
        $kennungSprache = $translate->getIterator();

        $this->_kennungAnzeigesprache = $kennungSprache['language'];

        if($this->_kennungAnzeigesprache == 'de')
            $this->_idAnzeigesprache = $this->_condition_id_deutsche_sprache;
        else
            $this->_idAnzeigesprache = $this->_condition_id_englische_sprache;

        return $this;
    }

    /**
     * Findet die gebuchten Übernachtungen der Buchungsnummern
     * eines Kunden. Es werden nur die Hotelbuchungen
     * angezeigt, die noch nicht gebucht wurden.
     *
     * @return Front_Model_CartHotel
     */
    private function _findHotelbuchungen(){

        for($i = 0; $i < count($this->_buchungsnummern); $i++){

            $whereStatus = new Zend_Db_Expr("status < '".$this->_condition_artikel_bereits_gebucht."' or status = '".$this->_condition_artikel_ausgebucht."'");

            $select = $this->_viewHotelbuchung->select();
            $select
                ->where('buchungsnummer_id = '.$this->_buchungsnummern[$i]['id'])
                ->where($whereStatus);

            $einzelbuchungen = $this->_viewHotelbuchung->fetchAll($select)->toArray();

            // Datumsformatierung und übernahme Preis
            for($j=0; $j < count($einzelbuchungen); $j++){

                // Übernahme Preis für View
                if($einzelbuchungen[$j]['zimmerpreis'] == 0)
                    $einzelbuchungen[$j]['preis'] = $einzelbuchungen[$j]['personenpreis'];
                else
                    $einzelbuchungen[$j]['preis'] = $einzelbuchungen[$j]['zimmerpreis'];

                 // Formatierung Datum
                $einzelbuchungen[$j]['buchungsdatum'] = nook_ToolZeiten::generiereDatumNachAnzeigesprache($einzelbuchungen[$j]['buchungsdatum'], $this->_idAnzeigesprache);
                $einzelbuchungen[$j]['startDate'] = nook_ToolZeiten::generiereDatumNachAnzeigesprache($einzelbuchungen[$j]['startDate'], $this->_idAnzeigesprache);

                $this->_buchungen[] = $einzelbuchungen[$j];
            }
        }

        return $this;
    }

    /**
     * findet die Kategori-Id der Raten
     *
     * @return Front_Model_CartHotel
     */
    private function _findKategorieId(){

        $cols = array(
            'category_id'
        );

        for($i = 0; $i < count($this->_buchungen); $i++){
            $where = "id = ".$this->_buchungen[$i]['otaRatesConfigId'];

            $select = $this->_tabelleOtaRatesConfig->select();
            $select->from($this->_tabelleOtaRatesConfig, $cols)->where($where);
            $categoryId = $this->_tabelleOtaRatesConfig->fetchRow($select)->toArray();
            $this->_buchungen[$i]['categoryId'] = $categoryId['category_id'];
        }

        return $this;
    }

    /**
     * Berechnet den Gesamtpreis einer Rate
     * in Abhängigkeit Personenpreis / Zimmerpreis
     *
     * @return Front_Model_CartHotel
     */
    private function _berechnungGesamtpreisEinerRate(){
        for($i = 0; $i < count($this->_buchungen); $i++){

            // Zimmerpreise
            if($this->_buchungen[$i]['personenpreis'] == '0'){
                $this->_buchungen[$i]['gesamtpreis'] = $this->_buchungen[$i]['preis'] * $this->_buchungen[$i]['nights'] * $this->_buchungen[$i]['zimmeranzahl'];

                // wenn Zimmer ausgebucht
                if($this->_buchungen[$i]['status'] == $this->_condition_artikel_ausgebucht)
                    $this->_buchungen[$i]['gesamtpreis'] = 0;
            }
            // Personenpreise
            else{
                $this->_buchungen[$i]['gesamtpreis'] = $this->_buchungen[$i]['personen'] * $this->_buchungen[$i]['personenpreis'] * $this->_buchungen[$i]['nights'];

                // wenn Zimmer ausgebucht
                if($this->_buchungen[$i]['status'] == $this->_condition_artikel_ausgebucht)
                    $this->_buchungen[$i]['gesamtpreis'] = 0;
            }

        }

        return $this;
    }

    /**
     * Kontrolliert ob User / Session die Buchung löschen darf.
     * Löscht eine Hotelbuchung aus dem Warenkorb.
     * Löscht den Eintrag in der Tabelle 'xml_buchung'
     *
     * @param $__bereich
     * @param $__idBuchungstabelle
     * @return void
     */
    public function deleteItemWarenkorb($__bereich, $__idBuchungstabelle){
        /** @var $warenkorbHilfe nook_WarenkorbHilfe */
        $warenkorbHilfe = $this->offsetGet('WarenkorbHilfe');
        $status = $warenkorbHilfe->kontrolleLoeschenBuchung($__bereich, $__idBuchungstabelle);
        $this->_loeschenHotelbuchung($__bereich, $__idBuchungstabelle);

        return;
    }

    /**
     * Löscht die Hotelbuchung aus den Tabellen
     * 'hotelbuchung' und 'xml_buchung'
     *
     * @param $__bereich
     * @param $__idBuchungstabelle
     * @return Front_Model_CartHotel
     */
    private function _loeschenHotelbuchung($__bereich, $__idBuchungstabelle){
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $db->beginTransaction();

        // loeschen in Tabelle 'hotelbuchung'
        $sql = "delete from tbl_hotelbuchung where id = ".$__idBuchungstabelle;
        $kontrolle1 = $db->query($sql);

        // loeschen in Tabelle 'xml_buchung'
        $sql = 'delete from tbl_xml_buchung where buchungstabelle_id = '.$__idBuchungstabelle." and bereich = ".$__bereich;
        $kontrolle2 = $db->query($sql);

        if($kontrolle1 and $kontrolle2)
            $db->commit();
        else{
            $db->rollBack();
            throw new nook_Exception();
        }


        return $this;
    }

    /**
     * Ermitteln des Hotelnamen
     *
     * @param $i
     * @param $beschreibung
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnHotelbeschreibung($i, $beschreibung)
    {
        $cols = array(
            new Zend_Db_Expr("property_name as hotelUeberschrift")
        );

        $select = $this->_tabelleProperties->select();
        $select
            ->from($this->_tabelleProperties, $cols)
            ->where("id = " . $this->_buchungen[$i]['propertyId']);

        $ergebnis = $this->_tabelleProperties->fetchRow($select);

        if ($ergebnis == null)
            throw new nook_Exception($this->_error_keine_daten_vorhanden);
        else
            $row = $ergebnis->toArray();

        $beschreibung['hotelUeberschrift'] = $row['hotelUeberschrift'];

        return $beschreibung;
    }

    /**
     * Beschreibung des Hotel in Abhängigkeit der Anzeigesprache
     *
     * @param $i
     * @param $beschreibung
     * @return mixed
     * @throws nook_Exception
     */
    private function hotelbeschreibung($i, $beschreibung)
    {
        // deutsche Anzeigesprache
        if ($this->_kennungAnzeigesprache == 'de') {
            $cols = array(
                new Zend_Db_Expr("description_de as hotelTxt")
            );
        } // englische Anzeigesprache
        else {
            $cols = array(
                new Zend_Db_Expr("description_en as hotelTxt")
            );
        }

        $select = $this->_tabellePropertyDetails->select();
        $select
            ->from($this->_tabellePropertyDetails, $cols)
            ->where("properties_id = " . $this->_buchungen[$i]['propertyId']);

        $ergebnis = $this->_tabellePropertyDetails->fetchRow($select);

        if ($ergebnis == null)
            throw new nook_Exception($this->_error_keine_daten_vorhanden);
        else
            $row = $ergebnis->toArray();

        $beschreibung['hotelTxt'] = $row['hotelTxt'];

        return $beschreibung;
    }

    /**
     * ermitteln des Namen der kategorie in Abhängigkeit der Anzeigesprache
     *
     * @param $i
     * @param $beschreibung
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnKategorieName($i, $beschreibung)
    {
        // deutsche Anzeigesprache
        if ($this->_kennungAnzeigesprache == 'de') {
            $cols = array(
                new Zend_Db_Expr("categorie_name as categoryName")
            );
        } // englische Anzeigesprache
        else {
            $cols = array(
                new Zend_Db_Expr("categorie_name_en as categoryName")
            );
        }

        $select = $this->_tabelleCategories->select();
        $select
            ->from($this->_tabelleCategories, $cols)
            ->where("id = " . $this->_buchungen[$i]['categoryId']);

        $ergebnis = $this->_tabelleCategories->fetchRow($select);

        if ($ergebnis == null)
            throw new nook_Exception($this->_error_keine_daten_vorhanden);
        else
            $row = $ergebnis->toArray();

        $beschreibung['categoryName'] = $row['categoryName'];

        return $beschreibung;
    }
}

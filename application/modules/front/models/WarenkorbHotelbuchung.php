<?php
/**
 * 23.05.2012 18:00
 * 
 * Fehlerbereich: 410
 *
 * Insert, Update und Delete von
 * Hotelbuchungen in den Tabellen
 * 'hotelbuchung' und 'xml_buchung'
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 */

class Front_Model_WarenkorbHotelbuchung extends nook_Model_model
{
    private $_db_hotel;
    private $_db_groups;
    private $_tagespreise = array();
    protected $_idDerBuchungsnummer = null;
    private $_idBuchungstabelle = array();
    private $ratenDerHotelbuchung = array();
    private $_personenJeRate = array();
    private $_idDesHotels;
    private $_language;
    private $_teilrechnungsId = null;

    // Konditionen
    private $_condition_anreisetag_erlaubt = 1;
    private $_condition_produkt_ist_im_warenkorb = 1;
    private $_condition_bereich_rate = 6;
    private $_condition_hotel_erlaubt_ueberbuchung = 2;
    private $_condition_mindestanzahl_personen_gruppe = 10;

    // Fehler
    private $_error_keine_preise_vorhanden = 410;
    private $_error_kein_eintrag_in_tabelle_hotelbuchung = 412;
    private $_error_rate_gehoert_nicht_zum_hotel = 413;
    private $_error_personenzahl_stimmt_nicht = 414;
    private $_error_ratenId_oder_personenanzahl_nicht_korrekt = 415;
    private $_error_zu_viele_buchungsdatensaetze_der_hotelbuchung = 416;

    // Tabellen und Views
    private $tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleOtaPrices = null;

    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleOtaPrices Application_Model_DbTable_otaPrices */
        $this->_tabelleOtaPrices = new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));
    }

    public function loescheUebernachtung($__idUebernachtung){
        // loeschen in 'hotelbuchung'
        $sql = "delete from tbl_hotelbuchung where id = ".$__idUebernachtung;
        $kontrolle = $this->_db_groups->query($sql);

        return;
    }

    public function setLanguage(){
        $translate = new Zend_Session_Namespace('translate'); 
        $this->_language = $translate->language;

        return;
    }

    /**
     * setzen der Teilrechnungs ID
     *
     * @param $teilrechnungsId
     * @return Front_Model_WarenkorbHotelbuchung
     */
    public function setTeilrechnungsId($teilrechnungsId){
       $this->_teilrechnungsId = $teilrechnungsId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTeilrechnungsId(){
        return $this->_teilrechnungsId;
    }

    /**
     * Registrierung der Session ID
     *
     * + gibt es schon diese Session_Id in 'buchungsnummer'
     * + bestimmen Buchungsnummer
     *
     * @return Front_Model_WarenkorbHotelbuchung
     */
    private function _registerSessionUndKundenId(){
        $auth = new Zend_Session_Namespace('Auth');
        $authDaten = $auth->getIterator();

        // gibt es schon diese Session_Id in 'buchungsnummer'
        $sql = "select count(session_id) as anzahl from tbl_buchungsnummer where session_id ='".Zend_Session::getId()."'";
        $anzahl = $this->_db_groups->fetchOne($sql);


        if($anzahl > 0){

            // eintragen Kunden ID
            if(!empty($authDaten->userId)){
                $sql = "update tbl_buchungsnummer set kunden_id = ".$authDaten->userId." where session_id = ".Zend_Session::getId();
                $this->_db_groups->query($sql);
            }

            // bestimmen Buchungsnummer
            $sql = "select id from tbl_buchungsnummer where session_id = '".Zend_Session::getId()."'";
            $idDerBuchungsnummer = $this->_db_groups->fetchOne($sql);
        }
        else{
            $insert = array();
            $insert['session_id'] = Zend_Session::getId();

            // Kunden ID
            if(isset($authDaten->UserId))
                $insert['kunden_id'] = $auth->UserId;

            $this->_db_groups->insert('tbl_buchungsnummer', $insert);
            $idDerBuchungsnummer = $this->_db_groups->lastInsertId();

        }

        $this->_idDerBuchungsnummer = $idDerBuchungsnummer;

        return $this;
    }

    /**
     * Kontrollliert die Raten des Hotel. Kontrolliert ob die Rate zum Hotel gehört
     *
     * @throws nook_Exception
     * @param $__bestellungHotel
     * @return Front_Model_WarenkorbHotelbuchung
     */
    public function checkRatenDesHotels($__bestellungHotel){
        $propertyId = $__bestellungHotel['propertyId'];
        unset($__bestellungHotel['propertyId']);

        if(array_key_exists('module', $__bestellungHotel))
            unset($__bestellungHotel['module']);
        if(array_key_exists('controller', $__bestellungHotel))
            unset($__bestellungHotel['controller']);
        if(array_key_exists('action', $__bestellungHotel))
            unset($__bestellungHotel['action']);
        if(array_key_exists('flagUpdate', $__bestellungHotel))
            unset($__bestellungHotel['flagUpdate']);
        if(array_key_exists('senden', $__bestellungHotel))
            unset($__bestellungHotel['senden']);

        foreach($__bestellungHotel as $key => $value){

            $value = (int) $value;
            if($value == 0)
                continue;

            if( (!intval($key) > 0) or (!intval($value) > 0) )
                throw new nook_Exception("Raten ID oder Anzahl Personen je Rate nicht korrekt");

//            $sql = "
//                SELECT
//                    count(`id`) AS `anzahl`
//                FROM
//                    `tbl_categories_rates`
//                WHERE (`rate_id` = ".$key."
//                    AND `property_id` = ".$propertyId.")";
        }

        return $this;
    }

    /**
     * Kontrolliert ob die Anzahl der Personen stimmt
     *
     * @throws nook_Exception
     * @param $__bestellungHotel
     * @return Front_Model_WarenkorbHotelbuchung
     */
    public function checkPersonenanzahl($__bestellungHotel){
        unset($__bestellungHotel['propertyId']);
        unset($__bestellungHotel['module']);
        unset($__bestellungHotel['controller']);
        unset($__bestellungHotel['action']);
        unset($__bestellungHotel['senden']);

        $personenzahl = 0;
        foreach($__bestellungHotel as $value){
            $personenzahl += $value;
        }

        // Kontrolle Personenanzahl einer Gruppe
        if($personenzahl < $this->_condition_mindestanzahl_personen_gruppe)
            throw new nook_Exception("Gesamtanzahl der Personen im Vergleich der Raten stimmt nicht");

        return $this;
    }

    /**
     * Übernimmt die Raten und die Grunddaten des Hotels
     *
     * + Grunddaten Hotel
     * + Raten des Hotels
     * + baut Array der Raten des Hotels '$this->ratenDerHotelbuchung' auf
     * + speichern der Zimmerbuchung
     *
     * @param $__bestellungHotel
     * @return Front_Model_WarenkorbHotelbuchung
     */
    public function setDataHotelbuchung($__bestellungHotel){
        $this->_idDesHotels = $__bestellungHotel['propertyId'];
        unset($__bestellungHotel['propertyId']);
        unset($__bestellungHotel['module']);
        unset($__bestellungHotel['controller']);
        unset($__bestellungHotel['action']);
        unset($__bestellungHotel['senden']);

        // baut Array der Raten des Hotels auf
        $ratesId = array();
        foreach($__bestellungHotel as $key => $value){
            $this->ratenDerHotelbuchung[$key] = $value;
        }

        // speichern der Zimmerbuchung
        $this
            ->_berechnungDerNotwendigenZimmer()
            ->_registerSessionUndKundenId()
            ->_speichernDerZimmerbuchungen();

        return $this;
    }

    /**
     * @return Front_Model_WarenkorbHotelbuchung
     */
    private function _berechnungDerNotwendigenZimmer(){

        // Umrechnung der Personenanzahl auf Zimmer
        foreach($this->ratenDerHotelbuchung as $ratenId => $anzahlPersonen){

            $sql = "
                SELECT
                    `tbl_categories`.`standard_persons` AS `standardpersonenzahl`
                FROM
                    `tbl_categories_rates`
                    INNER JOIN `tbl_categories`
                        ON (`tbl_categories_rates`.`category_id` = `tbl_categories`.`id`)
                WHERE (`tbl_categories_rates`.`rate_id` = ".$ratenId."
                    AND `tbl_categories_rates`.`property_id` = ".$this->_idDesHotels.")";
            
            $standardPersonenImZimmer = $this->_db_hotel->fetchOne($sql);
            // aufrunden
            $anzahlZimmer = ceil($anzahlPersonen / $standardPersonenImZimmer);
            $this->ratenDerHotelbuchung[$ratenId] = $anzahlZimmer;

            // speichern Anzahl der Personen
            $this->_personenJeRate[$ratenId] = $anzahlPersonen;

        }

        return $this;
    }

    /**
     * Speichern der Zimmerbuchung in der Tabelle
     * 'hotelbuchung'
     *
     * @throws nook_Exception
     * @return Front_Model_WarenkorbHotelbuchung
     */
    private function _speichernDerZimmerbuchungen(){

        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $startdatum = $hotelsuche->from;
        $korrigiertesStartdatum = nook_Tool::erstelleSuchdatumAusFormularDatum($startdatum);
        $this->_findenPreiseDerRatenFuerZeitraumImHotel($korrigiertesStartdatum);

        $buchungsNummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;

        foreach ( $this->ratenDerHotelbuchung as $rateId => $zimmerAnzahl) {

            // Update einer Rate
            if($zimmerAnzahl > 0){
                $tagespreis = $this->_findeTagespreisEinerRate($rateId);

                // ermitteln Suchparameter Hotelsuche
                $suchparameter = new nook_ToolSuchparameterHotel();
                $hotelsuche = $suchparameter->getSuchparameterHotelsuche();

                // ermittelt ob diese Rate bereits gebucht wurde
                $bereitsGebuchteRate = $this->insertOderUpdateEinerHotelbuchug($hotelsuche, $rateId);

                // Veränderung der Personen und Zimmeranzahl , Update
                if(!empty($bereitsGebuchteRate)){
                    $this->_teilrechnungsId = $bereitsGebuchteRate['teilrechnungen_id'];
                    $this->updateBereitsGebuchteRate($zimmerAnzahl, $rateId, $bereitsGebuchteRate);
                }

                // insert einen neuen Artikel,
                // teilnummer erhöht sich in 'tbl_buchungsnummer_teile'
                else{
                    $this->eintragenNeueRate($hotelsuche, $rateId, $zimmerAnzahl, $tagespreis, $db);
                }
            }
            // löschen bereits gebuchte Rate
            else{
                $this->loeschenEinerBereitsGebuchtenRate($rateId,$buchungsNummerId, $hotelsuche);
            }
        }

        return $this;
    }

    /**
     * Löscht eine bereits gebuchte Rate
     *
     * @param $rateId
     * @param $hotelsuche
     * @param $db
     * @return mixed
     */
    protected function loeschenEinerBereitsGebuchtenRate($rateId, $buchungsNummerId, $hotelsuche)
    {
        if(is_object($hotelsuche))
            return 0;

        $whereDelete = array(
            "otaRatesConfigId = ".$rateId,
            "propertyId = ".$hotelsuche['propertyId'],
            "startDate = '".$hotelsuche['from']."'",
            "buchungsnummer_id = ".$buchungsNummerId
        );

        $select = $this->tabelleHotelbuchung->select();
        for($i=0; $i < count($whereDelete); $i++){
            $select->where($whereDelete[$i]);
        }

        $query = $select->__toString();
        $rows = $this->tabelleHotelbuchung->fetchRow($select);

        if(empty($rows))
            return 0;
        else{
            $anzahlGeloeschteRaten = $rows->delete();

            return $anzahlGeloeschteRaten;
        }
    }

    /**
     * Kontrolliert ob die Rate eingetragen wird oder ob ein Update vorliegt.
     *
     * + Kontrolle über die Anzahl der vorhandenen Datensätze
     * + $flagUpdate = Array , ist ein 'update'
     * + $flagUpdate = false , ist ein 'insert'
     *
     */
    private function insertOderUpdateEinerHotelbuchug(array $hotelsuche, $rateId){
        $flagUpdate = false;

        $select = $this->tabelleHotelbuchung->select();
        $select
            ->where("buchungsnummer_id = ".$this->_idDerBuchungsnummer) // Buchungsnummer
            ->where("propertyId = ".$hotelsuche['propertyId']) // ID des Hotels
            ->where("otaRatesConfigId = ".$rateId) // ID der Rate
            ->where("startDate = '".$hotelsuche['suchdatum']."'") // Anreisedatum
            ->where("nights = ".$hotelsuche['days']); // Anzahl der Nächte

        $query = $select->__toString();

        $bereitsGebuchteRate = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        // update ist notwendig für Rate
        if(count($bereitsGebuchteRate) == 1)
            $flagUpdate = $bereitsGebuchteRate[0];

        // Fehler wenn zu viele Datensätze
        if(count($bereitsGebuchteRate) > 1)
            throw new nook_Exception('zu viele Buchungsdatensaetze Hotelbuchung');

        return $flagUpdate;
    }

    /**
     * Findet den aktuellen Preis einer Rate für den angegebenen Zeitraum
     *
     * @param $__startdatum
     * @throws nook_Exception
     */
    private function _findenPreiseDerRatenFuerZeitraumImHotel($__startdatum){

        $hotelSucheSession = new Zend_Session_Namespace('hotelsuche');
        $suchparameterHotelsuche = $hotelSucheSession->getIterator();

        $hotelTool = new nook_ToolHotel();
        $hotelCode = $hotelTool
            ->setHotelId($this->_idDesHotels)
            ->getHotelCode();

        $cols = array(
            'price' => new Zend_Db_Expr("AVG(amount)"),
            'pricePerPerson',
            'rateId' => new Zend_Db_Expr("rates_config_id")
        );

        $tagVorAnreise = new Zend_Db_Expr("datum > DATE_SUB('".$__startdatum."', interval 1 day)");
        $abreiseTag = new Zend_Db_Expr("datum < DATE_ADD('".$__startdatum."', interval ".$suchparameterHotelsuche['days']." day)");

        $select = $this->_tabelleOtaPrices->select();

        $select
            ->from($this->_tabelleOtaPrices, $cols)
            ->where("hotel_code = '".$hotelCode."'")
            ->where($tagVorAnreise)
            ->where($abreiseTag)
            ->group("hotel_code")
            ->group("rates_config_id");

        $query = $select->__toString();

        $ratenPreiseEinesZeitraumes = $this->_tabelleOtaPrices->fetchAll($select)->toArray();
        $this->_tagespreise = $ratenPreiseEinesZeitraumes;

        return;
    }

    /**
     * Übernimmt den berechneten Tagespreis
     *
     * @param $__rateId
     * @return array
     */
    private function _findeTagespreisEinerRate($__rateId){
        $ergebnis = array();

        for($i=0; $i<count($this->_tagespreise); $i++){            
            if(!empty($this->_tagespreise[$i]['rateId']) and ($this->_tagespreise[$i]['rateId'] == $__rateId)){

                // Personenpreis
                if($this->_tagespreise[$i]['pricePerPerson'] == 'true'){
                    $ergebnis['roomPrice'] = 0;
                    $ergebnis['personPrice'] = $this->_tagespreise[$i]['price'];

                    break;
                }
                // Zimmerpreis
                else{
                    $ergebnis['roomPrice'] = $this->_tagespreise[$i]['price'];
                    $ergebnis['personPrice'] = 0;

                    break;
                }
            }
        }

        return $ergebnis;
    }

    public  function getShoppingCartHotelzimmer(){
        $this->counterForProgramsToOrder = 0;
        $rohDatenBuchung = array();

        $warenkorbDaten = array();
		$warenkorb = new Zend_Session_Namespace('warenkorb');
        foreach($warenkorb->getIterator() as $key => $value){
            $warenkorbDaten[$key] = $value;
        }

        // Kunde ist nicht angemeldet
		if(empty($warenkorbDaten['kundenId'])){
			$buchungsNummernId = $this->_findOrdersBySessionId();
            $rohDatenBuchung = $this->_getBuchungsdatenHotelRates($buchungsNummernId);
        }
        // Kunde ist angemeldet
		else{
            $buchungsNummernId = $this->_findeOrdersByKundenId($warenkorbDaten['kundenId']);
            
            $j = 0;
            for($i=0; $i< count($buchungsNummernId); $i++){
                $datenBuchung = $this->_getBuchungsdatenHotelRates($buchungsNummernId[$i]['id']);

                // wenn Buchung keine Hotelbuchung
                if($datenBuchung == false)
                    continue;

                // mehrere Buchungen je Buchungsnummer
                for($k=0; $k < count($datenBuchung); $k++){
                    $rohDatenBuchung[$j] = $datenBuchung[$k];
                    $j++;
                }
            }
        }

        // wenn keine Buchungsdaten vorhanden
        if(count($rohDatenBuchung) == 0)
            return false;
        
        $rohDatenBuchung = $this->_getPreise($rohDatenBuchung);
        $datenBuchung = $this->_korrekturPreisUndDatum($rohDatenBuchung);
        $datenBuchung = $this->_ermittleDatenHotel($datenBuchung);
        $datenBuchung = $this->_ermittleDatenKategorie($datenBuchung);

        return $datenBuchung;
    }

    private function _findeOrdersByKundenId($__kundenId){
        $sql = "select id from tbl_buchungsnummer where kunden_id = ".$__kundenId;
        $buchungsNummernId = $this->_db_groups->fetchAll($sql);

        return $buchungsNummernId;
    }

    private function _ermittleDatenHotel($__datenBuchung){
        for($i=0; $i<count($__datenBuchung); $i++){
        
            $sql = "
                SELECT
                    `tbl_properties`.`property_name` AS `hotelName`";

            if($this->_language == 'de')
                $sql .= ", `tbl_property_details`.`description_de` AS `hotelTxt`";
            else
                $sql .= ", `tbl_property_details`.`description_en` AS `hotelTtxt`";


            $sql .= ", `tbl_property_details`.`ueberschrift` as hotelUeberschrift
                FROM
                    `tbl_properties`
                    INNER JOIN `tbl_property_details`
                        ON (`tbl_properties`.`id` = `tbl_property_details`.`properties_id`)
                WHERE (`tbl_properties`.`id` = ".$__datenBuchung[$i]['propertyId'].")";

            $hotelAngaben = $this->_db_hotel->fetchRow($sql);
            if(!empty($hotelAngaben))
                $__datenBuchung[$i] = array_merge($__datenBuchung[$i], $hotelAngaben);
        }

        return $__datenBuchung;
    }

    private function _ermittleDatenKategorie($__datenBuchung){
        for($i=0; $i<count($__datenBuchung); $i++){

            $__datenBuchung[$i]['kategorieText'] = ' ';
            $__datenBuchung[$i]['ratenName'] = ' ';

            if($this->_language == 'de'){
                $sql = "
                    SELECT
                        `headline`
                        , `description_long` as ratenTxt
                        , `headline` as ratenName
                    FROM
                        `tbl_ota_rates_description`
                    WHERE (`speech` = 'de'
                        AND `rates_id` = ".$__datenBuchung[$i]['otaRatesConfigId'].")";
            }
            else{
                $sql = "
                    SELECT
                        `headline`
                        , `description_long`
                        , `description_short`
                    FROM
                        `tbl_ota_rates_description`
                    WHERE (`speech` = 'en'
                        AND `tbl_rates_id` = ".$__datenBuchung[$i]['otaRatesConfigId'].")";
            }

            $ratenBeschreibung = $this->_db_hotel->fetchrow($sql);

            if(!empty($ratenBeschreibung))
                $__datenBuchung[$i] = array_merge($__datenBuchung[$i], $ratenBeschreibung);
        }

        return $__datenBuchung;
    }

    private function _korrekturPreisUndDatum($__rohDaten){
        for($i=0; $i<count($__rohDaten); $i++){
            if($this->_language == 'de'){
                $__rohDaten[$i]['buchungsdatum'] = nook_Tool::buildLongDateForLanguage($__rohDaten[$i]['buchungsdatum'], $this->_language);
                $__rohDaten[$i]['preis'] = nook_Tool::commaCorrection($__rohDaten[$i]['preis']);
                $__rohDaten[$i]['gesamtpreis'] = nook_Tool::commaCorrection($__rohDaten[$i]['gesamtpreis']);
            }
            else{
                $__rohDaten[$i]['buchungsdatum'] = nook_Tool::buildLongDateForLanguage($__rohDaten[$i]['buchungsdatum'], 'en');
            }
        }

        return $__rohDaten;
    }

    private function _getPreise($__rohDatenBuchung){
        $this->_gesamtpreisDerBuchung = 0;

        for($i=0; $i<count($__rohDatenBuchung); $i++){

            // Zimmerpreis
            if(!empty($__rohDatenBuchung[$i]['roomPrice'])){
                $__rohDatenBuchung[$i]['gesamtpreis'] = $__rohDatenBuchung[$i]['roomPrice'] * $__rohDatenBuchung[$i]['zimmeranzahl'] * $__rohDatenBuchung[$i]['nights'];
                $__rohDatenBuchung[$i]['preis'] = $__rohDatenBuchung[$i]['roomPrice'];
            }
            // Personenpreis
            else{
                $__rohDatenBuchung[$i]['gesamtpreis'] = $__rohDatenBuchung[$i]['personPrice'] * $__rohDatenBuchung[$i]['personen'] * $__rohDatenBuchung[$i]['nights'];
                $__rohDatenBuchung[$i]['preis'] = $__rohDatenBuchung[$i]['personPrice'] * $__rohDatenBuchung[$i]['personen'];
            }

            // $__rohDatenBuchung[$i]['gesamtpreis'] = number_format($__rohDatenBuchung[$i]['gesamtpreis'], 2);
            // $__rohDatenBuchung[$i]['preis'] = number_format($__rohDatenBuchung[$i]['preis'], 2);
        }

        return $__rohDatenBuchung;
    }

    private function _findOrdersBySessionId(){
        $sessionId = Zend_Session::getId();
        $sql = "select id as buchungsId from buchungsnummer where session_id = '".$sessionId."'";
        $buchungsNummerId = $this->_db_groups->fetchOne($sql);

        return $buchungsNummerId;
    }

    private function _getBuchungsdatenHotelRates($_buchungsNummerId){
        $sql = "select id, personNumbers as personen, roomNumbers as zimmeranzahl, personPrice, roomPrice, nights, buchungsdatum, status, propertyId, otaRatesConfigId from tbl_hotelbuchung where buchungsnummer_id = ".$_buchungsNummerId;
        $rohDatenBuchung = $this->_db_groups->fetchAll($sql);

        return $rohDatenBuchung;
    }

    /**
     * Ermitteln bereits gebuchter Datensätze der Hotelbuchung
     * in Tabelle 'hotelbuchung'
     *
     * @param array $__buchungsnummern
     * @return
     */
    public function getBereitsGebuchteRaten(array $__buchungsnummern){
        $stringBuchungsnummern = '';

        for($i=0; $i < count($__buchungsnummern); $i++){
            $stringBuchungsnummern .= $__buchungsnummern[$i]['id'].",";
        }

        $stringBuchungsnummern = substr($stringBuchungsnummern,0,-1);
        $sql = "select * from tbl_hotelbuchung where buchungsnummer_id IN (".$stringBuchungsnummern.") and status = ".$this->_condition_produkt_ist_im_warenkorb." order by propertyId";

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;
        $bereitsGebuchteRaten = $db->fetchAll($sql);

        return $bereitsGebuchteRaten;
    }

    /**
     * Ermittelt den Raten-Code und den Hotel-Code.
     * Bestimmt ob das Hotel eine Überbuchung zulässt.
     *
     * @param $__bereitsGebuchteRaten
     * @return
     */
    public function ermittelnCodesRateUndHotel($__bereitsGebuchteRaten){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_hotel;

        for($i=0; $i < count($__bereitsGebuchteRaten); $i++){
            $sql = "select property_code as hotel_code, overbook as ueberbuchung from tbl_properties where id = ".$__bereitsGebuchteRaten[$i]['propertyId'];
            $itemsProperty = $db->fetchRow($sql);

            // Hotel Code
            $__bereitsGebuchteRaten[$i]['hotel_code'] = $itemsProperty['hotel_code'];

            // Überbuchung möglich
            $__bereitsGebuchteRaten[$i]['ueberbuchung'] = $itemsProperty['ueberbuchung'];

            $sql = "select rate_code as rate_code from tbl_ota_rates_config where id = ".$__bereitsGebuchteRaten[$i]['otaRatesConfigId'];
            $__bereitsGebuchteRaten[$i]['rate_code'] = $db->fetchOne($sql);
        }

        return $__bereitsGebuchteRaten;
    }

    /**
     * Überprüft die Verfügbarkeit der Raten,
     * entsprechend der Ratenanzahl des betreffenden Tages
     * und der Stornofrist
     *
     * @param $__bereitsGebuchteRaten
     * @return
     */
    public function checkAvailabilityRates($__bereitsGebuchteRaten){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_hotel;

        $anzahlGebuchterRaten = count($__bereitsGebuchteRaten);

        for($i=0; $i < $anzahlGebuchterRaten; $i++){
            $flagLoeschen = false;

            $sql = "select roomlimit as ratenAnzahl, release_to as buchungsfrist from tbl_ota_rates_availability where";
            $sql .= " hotel_code = '".$__bereitsGebuchteRaten[$i]['hotel_code']."' and";
            $sql .= " rate_code = '".$__bereitsGebuchteRaten[$i]['rate_code']."' and";
            $sql .= " arrival = ".$this->_condition_anreisetag_erlaubt." and";
            $sql .= " datum = '".$__bereitsGebuchteRaten[$i]['startDate']."'";

            $verfuegbarkeit = $db->fetchRow($sql);
            $buchungsDatum = date_create($__bereitsGebuchteRaten[$i]['startDate']);
            $heutigesDatum = date("Y-m-d");
            $heutigesDatum = date_create($heutigesDatum);

            $buchungsDifferenz = date_diff($buchungsDatum, $heutigesDatum);
            $buchungsDifferenzTage = $buchungsDifferenz->d;

            // Kontrolle der Buchungsfrist
            if($buchungsDifferenzTage < $verfuegbarkeit['buchungsfrist']){
                $this->_loeschenBereitsGebuchterRaten($__bereitsGebuchteRaten[$i]);
                $flagLoeschen = true;
            }

            // lässt das Hotel eine Überbuchung zu ?
            if($__bereitsGebuchteRaten[$i]['ueberbuchung'] != $this->_condition_hotel_erlaubt_ueberbuchung){

                // loeschen einer Rate wenn vorhandene Raten kleiner als gebuchte Raten
                if( ($verfuegbarkeit['ratenAnzahl'] < $__bereitsGebuchteRaten[$i]['roomNumbers']) or (empty($verfuegbarkeit))){
                    $this->_loeschenBereitsGebuchterRaten($__bereitsGebuchteRaten[$i]);
                    $flagLoeschen = true;
                }
            }

            if($flagLoeschen == true)
               unset($__bereitsGebuchteRaten[$i]);
        }

        $__bereitsGebuchteRaten = array_merge($__bereitsGebuchteRaten);

        return $__bereitsGebuchteRaten;
    }

    /**
     * Löschen einer bereits gebuchten Rate in der Tabelle 'hotelbuchung' und 'xml_buchung'
     *
     * @param $__gebuchteRate
     * @param Zend_Db_Adapter_Mysqli $__db
     * @return
     */
    private function _loeschenBereitsGebuchterRaten(array $__gebuchteRate){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;

        $sql = "delete from tbl_hotelbuchung where id = ".$__gebuchteRate['id'];
        $db->query($sql);

        $sql = "delete from tbl_xml_buchung where buchungstabelle_id = ".$__gebuchteRate['id']." and bereich = ".$this->_condition_bereich_rate;
        $db->query($sql);

        return;
    }

    /**
     * Ermittlung der Werte zur Preisneuberechnung
     *
     * @param bool $__bereitsGebuchteRaten
     * @return
     */
    public function checkPriceRates($__bereitsGebuchteRaten = false){

        // wenn keine Raten vorhanden
        if(!is_array($__bereitsGebuchteRaten) or count($__bereitsGebuchteRaten) < 1)
            return;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_hotel;

        // Neuberechnung des Preises
        for($i=0; $i < count($__bereitsGebuchteRaten); $i++){
            $sql = "select amount, pricePerPerson from tbl_ota_prices where";
            $sql .= " hotel_code = '".$__bereitsGebuchteRaten[$i]['hotel_code']."'";
            $sql .= " and rate_code = '".$__bereitsGebuchteRaten[$i]['rate_code']."'";
            $sql .= " and datum = '".$__bereitsGebuchteRaten[$i]['startDate']."'";

            $neuerPreisInfos = $db->fetchRow($sql);
            $this->_updateNewPrice($neuerPreisInfos, $__bereitsGebuchteRaten[$i]['id']);
        }

        return;
    }

    /**
     * Fügt den neuen Preis in der Tabelle
     * 'hotelbuchung' ein.
     *
     * @param $__preisinformationen
     * @param $__vorhandeneRateId
     * @return
     */
    private function _updateNewPrice($__preisinformationen, $__vorhandeneRateId){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;

        // Personenpreis
        if($__preisinformationen['pricePerPerson'] == true)
            $sql = "update tbl_hotelbuchung set personPrice = '".$__preisinformationen['amount']."' where id = ".$__vorhandeneRateId;
        // Zimmerpreis
        else
            $sql = "update tbl_hotelbuchung set roomPrice = '".$__preisinformationen['amount']."' where id = ".$__vorhandeneRateId;

        $db->query($sql);

        return;
    }

    /**
     * Update einer bereits gebuchten Rate
     *
     * @param $zimmerAnzahl
     * @param $rateId
     * @param array $bereitsGebuchteRate
     * @return mixed
     */
    protected function updateBereitsGebuchteRate($zimmerAnzahl, $rateId, array $bereitsGebuchteRate)
    {
        $update = array(
            "roomNumbers" => $zimmerAnzahl,
            "personNumbers" => $this->_personenJeRate[$rateId]
        );

        $where = "id = " . $bereitsGebuchteRate['id'];

        $this->tabelleHotelbuchung->update($update, $where);

        $this->_idBuchungstabelle[] = $bereitsGebuchteRate['id'];

        return $bereitsGebuchteRate['id'];
    }

    /**
     * Trägt die neue Rate ein und ermittelt die ID der Teilrechnung
     *
     * + ermittelt ID der Teilrechnung
     * + eintragn neue Rate
     *
     * @param array $hotelsuche
     * @param $rateId
     * @param $zimmerAnzahl
     * @param $tagespreis
     * @param $db
     * @throws nook_Exception
     * @return int
     */
    protected function eintragenNeueRate(array $hotelsuche, $rateId, $zimmerAnzahl, $tagespreis, $db)
    {
        // ermitteln der ID der Teilrechnung
        $teilrechnung = new nook_ToolTeilrechnungen();

        $teilrechnungsId = $teilrechnung
            ->setBuchungsBereich($this->_condition_bereich_rate)
            ->setBuchungsnummer($this->_idDerBuchungsnummer)
            ->setOrtId($hotelsuche['propertyId'])
            ->setPersonenAnzahl($hotelsuche['adult'])
            ->setStartDatum($hotelsuche['suchdatum'])
            ->setZeitraum($hotelsuche['days'])
            ->getTeilrechnungsId();

        $this->setTeilrechnungsId($teilrechnungsId);

        // eintragen neue Rate
        $sql = "insert into tbl_hotelbuchung (propertyId, cityId, nights, startDate, otaRatesConfigId, roomNumbers, personNumbers, roomPrice, personPrice, status, buchungsnummer_id, teilrechnungen_id) values";
        $sql .= "('" . $hotelsuche['propertyId'] . "','" . $hotelsuche['city'] . "','" . $hotelsuche['days'] . "','" . $hotelsuche['suchdatum'] . "','" . $rateId . "', '" . $zimmerAnzahl . "', " . $this->_personenJeRate[$rateId] . ", '" . $tagespreis['roomPrice'] . "', '" . $tagespreis['personPrice'] . "', " . $this->_condition_produkt_ist_im_warenkorb . ", $this->_idDerBuchungsnummer, $teilrechnungsId)";

        $kontrolle = $db->query($sql);

        $this->_idBuchungstabelle[] = $db->lastInsertId();

        if (!$kontrolle)
            throw new nook_Exception('neue Rate wurde nicht eingetragen');

        return $kontrolle;
    }


}
<?php
class Front_Model_Hotelreservation extends nook_Model_model{

    // Error
	private $_error_keine_daten_vorhanden = 380;
    private $_error_kein_zimmerpreis_vorhanden = 381;

    // Datenbanken
    private $_db_front;
    private $_db_hotel;

    // Tabellen / Views
    private $_tabelleOtaRatesConfig = null;
    private $_tabelleCategories = null;
    private $_viewHotelkapazitaet = null;
    private $_tabelleOtaPrices = null;


    private $suchparameterHotel = array();
    private $_ratesUpdateId = false; // Rate Id für Update
    private $_ueberbuchung = false;
    private $_hotelCode = false;
    private $anzeigeSpracheId = null;

    private $_condition_zimmer_limit_fuer_hotel_mit_ueberbuchung = 1000;
    private $_condition_ueberbuchung_moeglich = 2;
    private $_condition_rate_ist_aktiv = 3;
    private $_condition_kein_raten_limit_vorhanden = 0;
    private $_condition_verwirrungsfaktor_anzahl_personen = 1.3;

    public function __construct(){

        // Datenbank Adapter
        $this->_db_front = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        // Tabellen
        /** @var _tabelleCategories Application_Model_DbTable_categories */
        $this->_tabelleCategories = new Application_Model_DbTable_categories(array('db' => 'hotels'));
        /** @var _tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig */
        $this->_tabelleOtaRatesConfig = new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        /** @var _viewHotelkapazitaet Application_Model_DbTable_viewHotelkapazitaet */
        $this->_viewHotelkapazitaet = new Application_Model_DbTable_viewHotelkapazitaet(array('db' => 'hotels'));
        /** @var _tabelleOtaPrices Application_Model_DbTable_otaPrices */
        $this->_tabelleOtaPrices = new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));

        // Anzeigesprache
        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    /**
     * @param array $__propertyId
     * @return Front_Model_Hotelreservation|nook_Model_model
     */
    public function setHotelId($__propertyId){

        $this->suchparameterHotel['propertyId'] = $__propertyId;

        return $this;
    }

    /**
     * listet die Raten eines Hotels auf
     *
     * @return array
     */
    public function getAuflistenVerfuegbareZimmer()
    {
        // Hotels ohne Überbuchung
        if($this->_ueberbuchung == false){
            $verfuegbareZimmer = $this->_hotelOhneUeberbuchungAuflistungZimmer();
            $verfuegbareZimmer = $this->kontrolleverfuegbarkeitRatenAnAllenTagen($verfuegbareZimmer);
        }
        // Hotels mit Überbuchung
        else
            $verfuegbareZimmer = $this->_hotelMitUeberbuchungAuflistungZimmer();



        // $verfuegbareZimmer = $this->_istRateUndPreisAktiv($verfuegbareZimmer);
        $verfuegbareZimmer = $this->_bestimmeZimmerpreis($verfuegbareZimmer);
        $verfuegbareZimmer = $this->_bestimmeAnzahlDerBettenImZimmer($verfuegbareZimmer);
        $verfuegbareZimmer = $this->_korrekturDezimaltrennzeichenUndNachkomma($verfuegbareZimmer);
        $verfuegbareZimmer = $this->_bestimmeCategoryName($verfuegbareZimmer);


        // Kontrolle auf Bettenanzahl und Preis, Kontrolle auf Logik der Eingabe Hotelverantwortlicher
        $verfuegbareZimmer = $this->_kontrollePreisUndBettenanzahl($verfuegbareZimmer);

        // Löschen von Raten ohne Zimmer Limit
        $verfuegbareZimmer = $this->_loeschenRatenOhneZimmerLimit($verfuegbareZimmer);

        // verändern der Zimmeranzahl
        $verfuegbareZimmer = $this->_veraenderungZimmeranzahl($verfuegbareZimmer);

        $verfuegbareZimmer = array_map(array($this, '_ergaenzeZimmerdatensatzUmHotelId'), $verfuegbareZimmer);

        return $verfuegbareZimmer;
    }

    /**
     * Kontrolle der Tagesverfügbarkeit der Zimmer
     *
     * @param array $verfuegbareZimmer
     * @return array
     */
    protected function kontrolleverfuegbarkeitRatenAnAllenTagen(array $verfuegbareZimmer)
    {
        $date = new DateTime($this->suchparameterHotel['suchdatum']);
        $date->add(new DateInterval('P'.$this->suchparameterHotel['days'].'D'));
        $abreisedatum = $date->format('Y-m-d');

        $frontModelTagesverfuegbarkeitRate = new Front_Model_TagesverfuegbarkeitRate();
        $verfuegbareZimmer = $frontModelTagesverfuegbarkeitRate
            ->setAnreisedatum($this->suchparameterHotel['suchdatum'])
            ->setAbreisedatum($abreisedatum)
            ->setAnzahlUebernachtungen($this->suchparameterHotel['days'])
            ->setRatenEinesZeitraumesInEinemHotel($verfuegbareZimmer)
            ->steuerungKontrolleRatenverfuegbarkeit()
            ->getGepruefteVerfuegbareRaten();

        return $verfuegbareZimmer;
    }

    /**
     * Verändert die Zimmeranzahl entsprechend der Personenanzahl.
     * Berücksichtigt ob ein Hotel den Überbuchungsmodus geschaltet hat.
     *
     * @param $verfuegbareZimmer
     * @return mixed
     */
    private function _veraenderungZimmeranzahl($__verfuegbareZimmer){
        $personenAnzahl = $this->suchparameterHotel['adult'];
        $personenAnzahl = $personenAnzahl * $this->_condition_verwirrungsfaktor_anzahl_personen;
        $personenAnzahl = ceil($personenAnzahl);

        for($i=0; $i < count($__verfuegbareZimmer); $i++){
            $benoetigteZimmeranzahl = ceil($personenAnzahl / $__verfuegbareZimmer[$i]['bettenanzahl']);

            // wenn Überbuchung möglich, dann 'anpassen der Zimmeranzahl'
            if($this->_ueberbuchung == true)
                $__verfuegbareZimmer[$i]['roomlimit'] = $benoetigteZimmeranzahl;
            else{

                // Verändern der Zimmeranzahl wenn keine Überbuchung möglich
                if($__verfuegbareZimmer[$i]['roomlimit'] >= ($benoetigteZimmeranzahl)){
                    $__verfuegbareZimmer[$i]['roomlimit'] = $benoetigteZimmeranzahl;
                }
            }


        }

        return $__verfuegbareZimmer;
    }

    /**
     * Löschen von Raten die keine
     * Zimmerkapazität haben.
     * Berücksichtigt Überbuchungsmodus des Hotels
     *
     * @param $__verfuegbareZimmer
     * @return array
     */
    private function _loeschenRatenOhneZimmerLimit($__verfuegbareZimmer){
        $verfuegbareZimmerMitLimit = array();

        foreach($__verfuegbareZimmer as $rate){

            if( ($this->_ueberbuchung == false) and ($rate['roomlimit'] == 0))
                continue;

            $verfuegbareZimmerMitLimit[] = $rate;
        }

        return $verfuegbareZimmerMitLimit;
    }

    /**
     * Ermittelt an hand der Raten ID die Categorie ID und Categorie Name entsprechend der Anzeigesprache
     *
     * + ermitteln Categorie ID
     * + ermitteln Anzeigesprache
     * + ergänzen Zimmer um Kategoriename
     *
     *
     * @param $verfuegbareZimmer
     * @return mixed
     */
    private function _bestimmeCategoryName($verfuegbareZimmer)
    {
        for($i = 0; $i < count($verfuegbareZimmer); $i++){

            // ermitteln Categorie ID
            $cols = array(
                'category_id'
            );

            $select = $this->_tabelleOtaRatesConfig->select();
            $select->from($this->_tabelleOtaRatesConfig, $cols)->where("id = ".$verfuegbareZimmer[$i]['ratenId']);

            $ergebnis = $this->_tabelleOtaRatesConfig->fetchRow($select);

            if($ergebnis == null)
                throw new nook_Exception($this->_error_keine_daten_vorhanden);
            else
                $row = $ergebnis->toArray();

            // Anzeigesprache
            $sessionTranslate = new Zend_Session_Namespace('translate');
            $translateVariablen = $sessionTranslate->getIterator();
            $translateVariablen = (array) $translateVariablen;


            // Kategorie Name entsprechend Anzeigesprache
            if($translateVariablen['language'] == 'de'){
                $cols = array(
                    'categorie_name'
                );
            }
            else{
                $cols = array(
                    new Zend_Db_Expr("categorie_name_en as categorie_name")
                );
            }

            $select = $this->_tabelleCategories->select();
            $select->from($this->_tabelleCategories, $cols)->where("id = ".$row['category_id']);

            $query = $select->__toString();

            $ergebnis = $this->_tabelleCategories->fetchRow($select);

            if($ergebnis == null)
                throw new nook_Exception($this->_error_keine_daten_vorhanden);
            else
                $row = $ergebnis->toArray();

            // ergänzen Zimmer um Kategoriename
            $verfuegbareZimmer[$i]['categorie_name'] = $row['categorie_name'];
        }

        return $verfuegbareZimmer;
    }

    /**
     * Übernimmt die ID der Rate die für das
     * Update zur Verfügung gestellt werden kann.
     *
     * @param $__updateRatesId
     * @return void
     */
    public function setRateFuerUpdate($__updateRatesId){
        $this->_ratesUpdateId = $__updateRatesId;

        return $this;
    }

    /**
     * Kontrolle Preis und Bettenanzahl
     *
     * @param $__verfuegbareZimmer
     * @return array
     */
    private function _kontrollePreisUndBettenanzahl($__verfuegbareZimmer){
        $kontrollierteZimmer = array();
        $j=0;

        foreach($__verfuegbareZimmer as $key => $value){

            // Kontrolle Preis
            if(!array_key_exists('preis', $value) or $value['preis'] == 0)
                continue;
            

            // Kontrolle Bettenanzahl
            if(!array_key_exists('bettenanzahl', $value) or $value['bettenanzahl'] == 0)
                continue;


            $kontrollierteZimmer[$j] = $value;
            $j++;
        }

        return $kontrollierteZimmer;
    }

    /**
     * Ermitteln der Zimmer in einem Hotel
     * ohne Überbuchung
     *
     * @return mixed
     */
    private function _hotelOhneUeberbuchungAuflistungZimmer(){
        $verfuegbareZimmer = $this->verfuegbareZimmerImHotel();

        return $verfuegbareZimmer;
    }

    /**
     * Ermittelt die Zimmer in einem Hotel
     * in der Variante 'Überbuchung möglich'
     *
     * @return array
     */
    private function _hotelMitUeberbuchungAuflistungZimmer(){
        $this->_ermittleHotelCode(); // ermittelt Hotelcode
        $verfuegbareZimmer = $this->_ermittleVorhandeneZimmerImHotel();
        $verfuegbareZimmer = array_map(array($this, '_ergaenzeVorhandeneZimmerUmHotelcode'), $verfuegbareZimmer);
        $verfuegbareZimmer = array_map(array($this, '_ergaenzeVorhandeneZimmerUmKategoriecode'), $verfuegbareZimmer);
        $verfuegbareZimmer = array_map(array($this, '_ergaenzeUmFiktivesZimmerlimit'), $verfuegbareZimmer);

        return $verfuegbareZimmer;
    }

    /**
     * Setzt die Zimmeranzahl für Hotels
     * mit Überbuchung auf 1000
     *
     * @param $__datensatzVerfuegbaresZimmer
     * @return mixed
     */
    private function _ergaenzeUmFiktivesZimmerlimit($__datensatzVerfuegbaresZimmer){
        $__datensatzVerfuegbaresZimmer['roomlimit'] = $this->_condition_zimmer_limit_fuer_hotel_mit_ueberbuchung; // 1000 Zimmer

        return $__datensatzVerfuegbaresZimmer;
    }

    /**
     * Ermittelt den Hotelcode an Hand der
     * PropertyId des Hotels
     */
    private function _ermittleHotelCode(){
        $sql = "
            SELECT
                `property_code`
            FROM
                `tbl_properties`
            WHERE (`id` = ".$this->suchparameterHotel['propertyId'].")";

        $this->_hotelCode = $this->_db_hotel->fetchOne($sql);

        return;
    }

    /**
     * Ermittelt die vorhandenen Zimmer in einem Hotel.
     * Eingrenzung über Hotel Code und ob
     * Zimmer 'aktiv' ist.
     *
     * @return mixed
     */
    private function _ermittleVorhandeneZimmerImHotel(){

        $sql = "
            SELECT
                tbl_ota_rates_config.rate_code
                , tbl_ota_rates_config.category_id
                , tbl_ota_rates_config.name AS ratenName
                , tbl_ota_rates_config.id AS ratenId
                , tbl_categories.categorie_name
            FROM
                tbl_ota_rates_config
                INNER JOIN tbl_categories
                    ON (tbl_ota_rates_config.category_id = tbl_categories.id)
            WHERE ((tbl_ota_rates_config.hotel_code = '".$this->_hotelCode."'
                AND tbl_ota_rates_config.aktiv = ".$this->_condition_rate_ist_aktiv.")";

        // zeigt einen einzelnen Datensatz zum Update an
        if(!empty($this->_ratesUpdateId))
            $sql .= " AND tbl_ota_rates_config.id = ".$this->_ratesUpdateId;

        $sql .= ")";

        $verfuegbareZimmer = $this->_db_hotel->fetchAll($sql);

        return $verfuegbareZimmer;
    }

    /**
     * Ergänzt Datensatz der Zimmer um den Hotel Code
     *
     * @param array $datensatzZimmer
     * @return array
     */
    private function _ergaenzeVorhandeneZimmerUmHotelcode(array $datensatzZimmer){
        $datensatzZimmer['hotel_code'] = $this->_hotelCode;

        return $datensatzZimmer;
    }

    /**
     * Ergänzt Datensätze der Zimmer um den Kategorie Code
     *
     * @param $datensatzZimmer
     * @return array
     */
    private function _ergaenzeVorhandeneZimmerUmKategoriecode(array $datensatzZimmer){

        $sql = "
            SELECT
                `categorie_code`
            FROM
                `tbl_categories`
            WHERE (`id` = " .$datensatzZimmer['category_id']. ")";

        $datensatzZimmer['category_code'] = $this->_db_hotel->fetchOne($sql);

        return $datensatzZimmer;
    }

    /**
     * Ergänzt Datensätze der Zimmer um die Hotel ID
     *
     * @param array $__angebotsDatensatzZimmer
     * @return array
     */
    private function _ergaenzeZimmerdatensatzUmHotelId(array $__angebotsDatensatzZimmer){
        $__angebotsDatensatzZimmer['propertyId'] = $this->suchparameterHotel['propertyId'];

        return $__angebotsDatensatzZimmer;
    }

    /**
     * Setzt in der Session die
     * ID des Hotels
     *
     * @param $propertyId
     * @return Front_Model_Hotelreservation
     */
    public function setRegisterPropertyInNamespaceHotelsuche($propertyId){

        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $hotelsuche->propertyId = $propertyId;
        
        return $this;
    }

    /**
     * Übernimmt die Suchparameter der Hotelsuche. Ermittelt im Update die Suchparameter aus dem Buchungsdatensatz
     *
     * + wandelt das Startdatum nach ISO 8601
     *
     * @param $suchparameter
     * @return Front_Model_Hotelreservation
     */
    public function setSuchparameterHotel($suchparameter, $flagUpdate)
    {
        // Update
        if($flagUpdate == 2){
            $updateSuchparameter = array();
            $updateSuchparameter['propertyId'] = $suchparameter['propertyId'];
            $updateSuchparameter['city'] = $suchparameter['cityId'];
            $updateSuchparameter['suchdatum'] = $suchparameter['from'];;
            $updateSuchparameter['days'] = $suchparameter['days'];
            $updateSuchparameter['adult'] = $suchparameter['adult'];

            $this->suchparameterHotel = $updateSuchparameter;

            // $this->setSuchparameterInSessionHotelsuche($suchparameter);
        }
        // normale Suche
        else{
            $this->suchparameterHotel = $suchparameter;
        }

        return $this;
    }

    /**
     * setzt die Suchparameter in der Session_Namespace 'hotelsuche'
     * entsprechend dem bereits gebuchten Datensatz
     *
     * @param $suchparameter
     * @return
     */
    private function setSuchparameterInSessionHotelsuche($suchparameter){

        $namespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
        
        $namespaceHotelsuche->suchdatum = $suchparameter['from'];
        $namespaceHotelsuche->days = $suchparameter['nights'];
        $namespaceHotelsuche->adult = $suchparameter['personNumbers'];
        $namespaceHotelsuche->propertyId = $suchparameter['propertyId'];
        $namespaceHotelsuche->buchungstabelle = $suchparameter['buchungstabelle'];
        $namespaceHotelsuche->rateId = $suchparameter['otaRatesConfigId'];

        return;
    }

    /**
     * Ermittelt ob ein Hotel eine Überbuchung zulässt
     *
     * @return Front_Model_Hotelreservation
     */
    public function setUberbuchungsModus(){

        $sql = "
            SELECT
                `overbook`
            FROM
                `tbl_properties`
            WHERE (`id` = " .$this->suchparameterHotel['propertyId']. ")";

        $moeglicheUberbuchung = $this->_db_hotel->fetchOne($sql);
        if($moeglicheUberbuchung == $this->_condition_ueberbuchung_moeglich)
            $this->_ueberbuchung = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUberbuchungsModus(){

        return $this->_ueberbuchung;
    }

    /**
     * Erkundet ob ein Zimmer einen
     *
     * @param $zimmer
     * @return array
     */
    public function checkRatenPreis($zimmer){

        $j=0;
        $zimmerMitPreis = array();
        for($i=0; $i < count($zimmer); $i++){
            if(($zimmer[$i]['personenpreis'] == null) and ($zimmer[$i]['preis'] == '0,00') )
                continue;

            $zimmerMitPreis[$j] = $zimmer[$i];
            $j++;
        }

        return $zimmerMitPreis;
    }

    /**
     * Korrektur der Darstellung des Zimmerpreises
     *
     * @param $verfuegbareZimmer
     * @return mixed
     */
    private function _korrekturDezimaltrennzeichenUndNachkomma(array $verfuegbareZimmer)
    {
        $translate = new Zend_Session_Namespace('translate');
        $language = $translate->language;

        for ($i = 0; $i < count($verfuegbareZimmer); $i++) {
            $verfuegbareZimmer[$i]['preis'] = number_format($verfuegbareZimmer[$i]['preis'], 2);

//            if ($language == 'de')
//                $verfuegbareZimmer[$i]['preis'] = str_replace('.', ',', $verfuegbareZimmer[$i]['preis']);
        }

        return $verfuegbareZimmer;
    }

    /**
     * Anzahl der betten im Zimmer
     *
     * @param array $verfuegbareZimmer
     * @return array
     */
    private function _bestimmeAnzahlDerBettenImZimmer(array $verfuegbareZimmer){

        $sql = "
            SELECT
                `standard_persons`
                , `categorie_code`
            FROM
                `tbl_categories`
            WHERE (`properties_id` = ".$this->suchparameterHotel['propertyId'].")";

        $bettenanzahlDerKategorienEinesHotels = array();
        $bettenanzahlDerKategorienEinesHotels = $this->_db_hotel->fetchAll($sql);

        for($i=0; $i<count($verfuegbareZimmer); $i++){
            $verfuegbareZimmer[$i] = $this->_ermittleBettenanzahlImZimmer($verfuegbareZimmer[$i], $bettenanzahlDerKategorienEinesHotels);
        }

        return $verfuegbareZimmer;
    }

    private function _ermittleBettenanzahlImZimmer($verfuegbaresZimmer, $bettenanzahlDerKategorienEinesHotels){

        for($i=0; $i<count($bettenanzahlDerKategorienEinesHotels); $i++){
            if($verfuegbaresZimmer['category_code'] == $bettenanzahlDerKategorienEinesHotels[$i]['categorie_code'])
                $verfuegbaresZimmer['bettenanzahl'] = $bettenanzahlDerKategorienEinesHotels[$i]['standard_persons'];
        }

        return $verfuegbaresZimmer;
    }

    /**
     * Ermittelt die verfügbaren Zimmer in einem
     * Hotel an Hand der tatsächlichen Verfügbarkeit.
     * Es wird die minimale Verfügbarkeit der Zimmer
     * entsprechend des gewählten Zeitraumes ermittelt
     *
     * @return mixed
     */
    private function verfuegbareZimmerImHotel()
    {
        $cols = array(
            'datum',
            'roomlimit',
            'property_id',
            'ratenId' => new Zend_Db_Expr("rates_config_id")
        );

        $whereHotelId = "property_id = ".$this->suchparameterHotel['propertyId'];
        $whereTagVorAnreise = new Zend_Db_Expr("DATE_SUB('" . $this->suchparameterHotel['suchdatum'] . "',Interval 1 Day)");
        $whereAbreisedatum = new Zend_Db_Expr("DATE_ADD('" . $this->suchparameterHotel['suchdatum'] . "',Interval " . $this->suchparameterHotel['days'] . " Day )");

        $select = $this->_viewHotelkapazitaet->select();
        $select
            ->from($this->_viewHotelkapazitaet, $cols)
            ->where($whereHotelId)
            ->where("datum > " . $whereTagVorAnreise)
            ->where("datum < " . $whereAbreisedatum)
            ->order('datum')
            ->order('ratenId');

        $query = $select->__toString();

        $verfuegbareZimmerEinesHotels = $this->_viewHotelkapazitaet->fetchAll($select)->toArray();

        $toolHotel = new nook_ToolHotel();
        $hotelCode = $toolHotel
            ->setHotelId($this->suchparameterHotel['propertyId'])
            ->getHotelCode();

        $toolRate = new nook_ToolRate();
        $toolCategory = new nook_ToolCategory();

        for($i=0; $i < count($verfuegbareZimmerEinesHotels); $i++){

            // Hotel Code
            $verfuegbareZimmerEinesHotels[$i]['hotel_code'] = $hotelCode;

            // ermitteln rate_code und ratenName
            $ratenDatensatz = $toolRate
                ->setRateId($verfuegbareZimmerEinesHotels[$i]['ratenId'])
                ->getRateData();

            $verfuegbareZimmerEinesHotels[$i]['rate_code'] = $ratenDatensatz['rate_code'];
            $verfuegbareZimmerEinesHotels[$i]['ratenName'] = $ratenDatensatz['name'];

            // ermitteln Category Code
            $categoryDatensatz = $toolCategory
                ->setRateId($verfuegbareZimmerEinesHotels[$i]['ratenId'])
                ->getDatenCategory();

            $verfuegbareZimmerEinesHotels[$i]['category_code'] = $categoryDatensatz['categorie_code'];
        }

        return $verfuegbareZimmerEinesHotels;
    }

    /**
     * Bestimmt den durchschnittlichen
     * Zimmerpreis in einem Zeitraum
     *
     * @param $__verfuegbareZimmer
     * @return mixed
     */
    private function _bestimmeZimmerpreis($__verfuegbareZimmer){

        for($i=0; $i<count($__verfuegbareZimmer); $i++){

            $cols = array(
                'pricePerPerson',
                'amount' => new Zend_Db_Expr("AVG(amount)")
            );

            $tagVorAnreise = new Zend_Db_Expr("datum > DATE_SUB('".$this->suchparameterHotel['suchdatum']."', interval 1 day)");
            $abreiseTag = new Zend_Db_Expr("datum < DATE_ADD('".$this->suchparameterHotel['suchdatum']."', interval ".$this->suchparameterHotel['days']." day)");


            $select = $this->_tabelleOtaPrices->select();

            $select
                ->from($this->_tabelleOtaPrices, $cols)
                ->where("hotel_code = '".$__verfuegbareZimmer[$i]['hotel_code']."'")
                ->where("rate_code = '".$__verfuegbareZimmer[$i]['rate_code']."'")
                ->where($tagVorAnreise)
                ->where($abreiseTag)
                ->group("hotel_code")
                ->group('rate_code');

            $preisinformationen = $this->_tabelleOtaPrices->fetchAll($select)->toArray();
            if(count($preisinformationen) <> 1)
                throw new nook_Exception($this->_error_kein_zimmerpreis_vorhanden);

            $__verfuegbareZimmer[$i]['preis'] = $preisinformationen[0]['amount'];
            $__verfuegbareZimmer[$i]['personenpreis'] = $preisinformationen[0]['pricePerPerson'];
        }

        return $__verfuegbareZimmer;
    }

    /**
     * Liefert die Hotelbeschreibung
     *
     * @return array
     */
    public function getHotelbeschreibung(){
        $hotelbeschreibung = $this->findeHotelBeschreibung();
        // $hotelbeschreibung = $this->_ergaenzeHotelbeschreibungUmSuchparameter($hotelbeschreibung);

        return $hotelbeschreibung;
    }

//    private function _ergaenzeHotelbeschreibungUmSuchparameter($__hotelbeschreibung){
//
//        $suchparameter = new Zend_Session_Namespace('hotelsuche');
//        $parameter = $suchparameter->getIterator();
//
//        $__hotelbeschreibung['anreisetag'] = $parameter['from'];
//
//        $this->suchparameterHotel['suchdatum'] = $parameter['suchdatum'];
//        $__hotelbeschreibung['startDate'] = $parameter['suchdatum'];
//
//        $__hotelbeschreibung['uebernachtungen'] = $parameter['days'];
//        $this->suchparameterHotel['days'] = $parameter['days'];
//
//        $this->suchparameterHotel['adult'] = $parameter['adult'];
//        $__hotelbeschreibung['adult'] = $parameter['adult'];
//
//        return $__hotelbeschreibung;
//    }

    /**
     * Ermittlung der Hotelbeschreibung
     *
     * @return array
     */
    private function findeHotelBeschreibung()
    {
        $language = Zend_Registry::get('language');
        if($language == 'eng')
            $sprache = 'en';
        else
            $sprache = 'de';


        $sql = "
        SELECT
            `tbl_property_details`.`description_" . $sprache . "` AS hotelbeschreibung
            , `tbl_properties`.`property_name` as ueberschrift
            , `tbl_property_details`.`city`
        FROM
            `tbl_properties`
            LEFT JOIN `tbl_property_details`
                ON (`tbl_properties`.`id` = `tbl_property_details`.`properties_id`)
        WHERE (`tbl_properties`.`id` = " . $this->suchparameterHotel['propertyId'] . ")";

        $hotelBeschreibung = $this->_db_hotel->fetchRow($sql);

        $hotelBeschreibung['hotelbeschreibung'] = nook_Tool::trimLongTextStandard($hotelBeschreibung['hotelbeschreibung']);
        
        return $hotelBeschreibung;
    }

    /**
     * Ermittlung der Zimmerbeschreibung
     *
     * @param $suchparameterZimmerbeschreibung
     * @return array
     */
    public function getZimmerbeschreibung($suchparameterZimmerbeschreibung)
    {
        if($suchparameterZimmerbeschreibung['sprache'] == 'de'){
            $sql = "
                SELECT
                    `tbl_categories_description`.`description_short` AS `kurztext`
                    , `tbl_categories_description`.`description_long` AS `langtext`
                    , `tbl_categories`.`categorie_name` as `ueberschrift`
                    , `tbl_ota_rates_config`.`id` as `ratenId`
                    , `tbl_categories`.`id` AS `kategorieId`
                FROM
                    `tbl_categories`
                    INNER JOIN `tbl_categories_description`
                        ON (`tbl_categories`.`id` = `tbl_categories_description`.`category_id`)
                    INNER JOIN `tbl_ota_rates_config`
                        ON (`tbl_ota_rates_config`.`category_id` = `tbl_categories`.`id`)
                WHERE (`tbl_categories_description`.`speech` = '" .$suchparameterZimmerbeschreibung['sprache']. "'
                    AND `tbl_ota_rates_config`.`rate_code` = '". $suchparameterZimmerbeschreibung['zimmerrate'] ."'
                    AND `tbl_ota_rates_config`.`properties_id` = ".$suchparameterZimmerbeschreibung['propertyId']. ")";
        }
        else{
            $sql = "
                SELECT
                    `tbl_categories_description`.`description_short` AS `kurztext`
                    , `tbl_categories_description`.`description_long` AS `langtext`
                    , `tbl_categories`.`categorie_name_en` as `ueberschrift`
                    , `tbl_ota_rates_config`.`id` as `ratenId`
                    , `tbl_categories`.`id` AS `kategorieId`
                FROM
                    `tbl_categories`
                    INNER JOIN `tbl_categories_description`
                        ON (`tbl_categories`.`id` = `tbl_categories_description`.`category_id`)
                    INNER JOIN `tbl_ota_rates_config`
                        ON (`tbl_ota_rates_config`.`category_id` = `tbl_categories`.`id`)
                WHERE (`tbl_categories_description`.`speech` = '" .$suchparameterZimmerbeschreibung['sprache']. "'
                    AND `tbl_ota_rates_config`.`rate_code` = '". $suchparameterZimmerbeschreibung['zimmerrate'] ."'
                    AND `tbl_ota_rates_config`.`properties_id` = ".$suchparameterZimmerbeschreibung['propertyId']. ")";
        }

        $datensatzBeschreibungKategorie = $this->_db_hotel->fetchRow($sql);

        return $datensatzBeschreibungKategorie;
    }

    /**
     * Ermittelt die Produkte einer Rate
     *
     * @param $__suchparamterProdukteEinerRate
     * @param $__ratenId
     * @return mixed
     */
    public function getProdukteEinerRate($__suchparamterProdukteEinerRate, $__ratenId){
        $sql = "SELECT";

        if($__suchparamterProdukteEinerRate['sprache'] == 'de')
            $sql .= " `tbl_products`.`id` as produktId, `tbl_products`.`ger` as beschreibung, `tbl_products`.`product_name` as produkt";
        else
            $sql .= " `tbl_products`.`id` as produktId, `tbl_products`.`eng` as beschreibung, `tbl_products`.`product_name_en` as produkt";

        $sql .= "
            FROM
                `tbl_products`
                INNER JOIN `tbl_ota_rates_products`
                    ON (`tbl_products`.`id` = `tbl_ota_rates_products`.`products_id`)
            WHERE (`tbl_ota_rates_products`.`rates_id` = " .$__ratenId. ")";

        $produkteEinerRate = $this->_db_hotel->fetchAll($sql);

        return $produkteEinerRate;
    }

    /**
     * Findet die ID eines Hotels entsprechend der
     * ID der Buchungstabelle
     *
     * @param $__idBuchungstabelle
     * @return
     */
    public function findIdHotel($__idBuchungstabelle){

        $sql = "select propertyId from tbl_hotelbuchung where id = ".$__idBuchungstabelle;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $idHotel = $db->fetchOne($sql);

        return $idHotel;
    }

    /**
     * Findet einen Buchungsdatensatz in der Tabelle
     * 'hotelbuchung'
     *
     * @param $__buchungstabelleId
     * @return array
     */
    public function findBuchung($__buchungstabelleId){
        $sql = "select * from tbl_hotelbuchung where id = ".$__buchungstabelleId;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $buchungsdatensatz = $db->fetchRow($sql);

        return $buchungsdatensatz;
    }

    /**
     * ermitteln Ueberbuchung
     *
     * Verfuegbarkeit Rate
     *
     * @param $__personenanzahl
     * @return
     */
    public function updateRate($__personenanzahl){

        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $suchparameter = $hotelsuche->getIterator();
        $suchparameter['adult'] = $__personenanzahl;

        // lässt Hotel eine Überbuchung zu
        $ueberbuchungsModusHotel = $this->bestimmeUeberbuchungsmodusHotel($suchparameter);
        $benoetigteZimmer = $this->bestimmeRoomlimitDerRate($suchparameter, $ueberbuchungsModusHotel);
        if($benoetigteZimmer > 0)
            $this->updateDatensatzRate($suchparameter, $benoetigteZimmer);

        return $benoetigteZimmer;
    }

    /**
     * Update des Datensatzes einer
     * bereits gebuchten Rate
     * in Tabelle 'hotelbuchung'
     *
     * @param $__suchparameter
     * @return
     */
    private function updateDatensatzRate($__suchparameter, $__benoetigteZimmer){
        $update = array();
        $update['roomNumbers'] = $__benoetigteZimmer;
        $update['personNumbers'] = $__suchparameter['adult'];

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $db->update('tbl_hotelbuchung', $update, "id = ".$__suchparameter['buchungstabelle']);

        return;
    }

    /**
     * Ermittelt die Verfuegbarkeit einer Rate
     *
     * @param $__suchparameter
     * @return string
     */
    private function bestimmeUeberbuchungsmodusHotel($__suchparameter){

        $sql = "select overbook from tbl_properties where id = ".$__suchparameter['propertyId'];

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_hotel;
        $ueberbuchungsModus = $db->fetchOne($sql);

        return $ueberbuchungsModus;
    }

    /**
     * Bestimme RoomLimit einer Rate
     *
     * @param $__suchparameter
     * @return int|string
     */
    private function bestimmeRoomlimitDerRate($__suchparameter, $__ueberbuchungsModusHotel){
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_hotel;

        // bestimme Kategorie
        $sql = "select category_id from tbl_categories_rates where property_id = ".$__suchparameter['propertyId']." and rate_id = ".$__suchparameter['rateId'];
        $categorieId = $db->fetchOne($sql);

        // bestimme rateCode
        $sql = "select rate_code from tbl_ota_rates_config where id = ".$__suchparameter['rateId'];
        $rateCode = $db->fetchOne($sql);

        // bestimme Zimmer der Rate
        $sql = "select standard_persons from tbl_categories where id = ".$categorieId;
        $bettenanzahl = $db->fetchOne($sql);
        $benoetigteZimmer = ceil($__suchparameter['adult'] / $bettenanzahl);

        // wenn Hotel keine Überbuchung hat
        if($__ueberbuchungsModusHotel != $this->_condition_ueberbuchung_moeglich){
            // bestimme Verfuegbarkeit der Rate
            $sql = "SELECT
                    `roomlimit`
                FROM
                    `tbl_ota_rates_availability`
                WHERE (`datum` = '".$__suchparameter['suchdatum']."'
                    AND `property_id` = ".$__suchparameter['propertyId']."
                    AND `rate_code` = '".$rateCode."'
                    AND `aktiv` = ".$this->_condition_rate_ist_aktiv.")";

            $roomLimit = $db->fetchOne($sql);
        }
        // Hotel hat Überbuchung
        else
            $roomLimit = $this->_condition_zimmer_limit_fuer_hotel_mit_ueberbuchung;

        if($roomLimit >= $benoetigteZimmer)
            return $benoetigteZimmer;
        else
            return $this->_condition_kein_raten_limit_vorhanden;
    }
}
<?php
/**
* Verwaltet den Datensatz der Programme
*
* + Findet die Sperrtage eines Programmes
* + Erstellt ein deutsches Datum
* + Trägt die Sperrtage eines Programmes ein
* + Löscht die Sperrtage eines Programmes
* + Decode JSON
* + Kontrolle Int der Sprachen
* + Ermittelt die Anzahl der Programme unter Berücksichtigung der Suchparameter
* + Übernimmt die Suchparameter für
* + Sucht die vorhandenen Programme entsprechend
* + Speichert die Beschreibung eines Programmes
* + Darstellen aller verfügbarer Programsprachen
* + findet die Programmsprachen eines Programmes
* + Ermittelt die Durchführungsdauer, den Hinweis,
* + Findet diverse Informationen eines Programmes
* + findet allgemeine Angaben in der Tabelle 'tbl_programmdetails'
* + Findet die Bestätigungstexte
* + Findet den Treffpunkt und die Hinweise ÖPNV
* + Update der Textinformationen / Diverses eines Programmes
* + Update allgemeiner Angaben eines
* + Deutscher und englischer Bestätigungstext
* + Update Treffpunkte und ÖPNV
*
* @author Stephan.Krauss
* @date 19.10.2013
* @file Datensatz.php
* @package admin
* @subpackage model
*/
class Admin_Model_Datensatz extends nook_Model_model
{

    // Konditionen
    private $_condition_is_german_languge = 1;
    private $_condition_is_english_languge = 2;

    private $_condition_user_is_provider = 5;
    private $_condition_user_is_admin = 10;
    private $_condition_deutsche_sprache = 1;
    private $_condition_erwartete_anzahl_datensaetze = 2;

    private $_condition_default_buchungsfrist = 7;
    private $_condition_default_stornofrist = 3;
    private $_condition_default_saisonende = '31.12.2013';

    // Fehler
    private $_error_var_is_not_int = 230;
    private $_error_sperrtage_wurde_nicht_aktualisiert = 231;
    private $_error_anzahl_datensaetze_stimmt_nicht = 232;
    private $_error_keine_daten_vorhanden = 233;

    // Tabellen / Views / Datenbanken
    private $_viewVorhandeneProgramme = null;
    private $_tabelleProgrammbeschreibung = null;
    private $_tabelleProgrammdetails = null;
    private $_db;

    // Flags
    // private $_flag;

    private $_auth;
    private $_dependency = array();

    public function __construct()
    {
        $this->_db = Zend_Registry::get('front');
        $this->_auth = new Zend_Session_Namespace('Auth');

        /** @var _viewVorhandeneProgramme Application_Model_DbTable_viewVorhandeneProgramme */
        $this->_viewVorhandeneProgramme = new Application_Model_DbTable_viewVorhandeneProgramme();
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();

        return;
    }

    public function setDependency($__name, $__objekt)
    {
        $this->_dependency[$__name] = $__objekt;

        return $this;
    }

    public function getProgrammVarianten($__start, $__limit, $__programmId)
    {
        $programmVarianten = $this->_dependency['gridPreisvarianten']
            ->setProgrammId($__programmId)
            ->setStartLimit($__start, $__limit)
            ->getPreisvarianten();

        return $programmVarianten;
    }

    public function setDeleteAlleZuschlaege($__programmId)
    {
        $this->_dependency['gridPreisvarianten']
            ->setProgrammId($__programmId)
            ->deleteZuschlaege();

        return;
    }

    public function setProgrammVarianten($__programmId, $__zuschlaege)
    {
        $this->_dependency['gridPreisvarianten']
            ->setProgrammId($__programmId)
            ->setZuschlaege($__zuschlaege);

        return;
    }

    public function getAnzahlProgrammvarianten()
    {
        $anzahl = $this->_dependency['gridPreisvarianten']->getAnzahlDatensaetze();

        return $anzahl;
    }

    /**
     * Findet die Sperrtage eines Programmes
     *
     * @param $__programmId
     * @return array
     */
    public function getSperrtageVonProgramm($__programmId)
    {
        $sql = "select id, tag, monat, jahr from tbl_sperrtage where programmdetails_id = " . $__programmId;
        $sperrtage = $this->_db->fetchAll($sql);

        $sperrtage = array_map(array( $this, '_buildDatum' ), $sperrtage);

        return $sperrtage;
    }

    /**
     * Erstellt ein deutsches Datum
     * aus Tag, Monat und Jahr
     *
     * @param $__rohdatum
     * @return array
     */
    private function _buildDatum($__rohdatum)
    {
        $deutschesDatum = array();
        $deutschesDatum['datumSperrtag'] = $__rohdatum['tag'] . "." . $__rohdatum['monat'] . "." . $__rohdatum['jahr'];

        return $deutschesDatum;
    }

    /**
     * Trägt die Sperrtage eines Programmes in 'tbl_sperrtage' ein
     *
     * @param $__programmId
     * @param $__stringSperrtage
     * @throws nook_Exception
     */
    public function setSperrtageFuerProgramm($__programmId, $__stringSperrtage)
    {

        // löschen bereits vorhandene Sperrtage
        $this->_loescheSperrtageProgramm($__programmId);

        $__stringSperrtage = trim($__stringSperrtage);
        if (empty($__stringSperrtage)) {
            return;
        }

        // $__stringSperrtage = substr($__stringSperrtage, 0, -1);
        $sperrtage = explode("#", $__stringSperrtage);

        $datenSperrtage = array();
        for ($i = 0; $i < count($sperrtage); $i++) {

            $sperrtageDatum = $sperrtage[$i];
            if(empty($sperrtageDatum))
                continue;

            $teileDatum = explode('.',$sperrtageDatum);

            $datenSperrtage['tag'] = $teileDatum[0];
            $datenSperrtage['monat'] = $teileDatum[1];
            $datenSperrtage['jahr'] = $teileDatum[2];

            $datenSperrtage['programmdetails_id'] = $__programmId;

            $controll = $this->_db->insert('tbl_sperrtage', $datenSperrtage);
            if (!$controll) {
                throw new nook_Exception($this->_error_sperrtage_wurde_nicht_aktualisiert);
            }
        }

        return;
    }

    /**
     * Löscht die Sperrtage eines Programmes
     *
     * @param $__programmId
     */
    private function _loescheSperrtageProgramm($__programmId)
    {
        $sql = "delete from tbl_sperrtage where programmdetails_id = " . $__programmId;
        $control = $this->_db->query($sql);

        return;
    }

    /**
     * Decode JSON
     *
     * @param $__languages
     */
    public function checkLanguages($__params)
    {
        $languages = json_decode($__params['languages']);
        array_filter($languages, array( $this, '_checkSingleLanguage' ));

        return $languages;
    }

    /**
     * Kontrolle Int der Sprachen
     *
     * @param $__language
     * @throws nook_Exception
     */
    private function _checkSingleLanguage($__language)
    {
        $__language = (int) $__language;
        if (!filter_var($__language, FILTER_VALIDATE_INT)) {
            throw new nook_Exception($this->_error_var_is_not_int);
        }
    }

    public function saveLanguagesFromProgram($__programmId, $__languages)
    {

        // loeschen alte Sprachzuordnung
        $sql = "delete from tbl_programmdetails_progsprachen where programmdetails_id = " . $__programmId;
        $this->_db->query($sql);

        // eintragen der Programmsprachen
        $sprachen = array();
        for ($i = 0; $i < count($__languages); $i++) {
            $sprachen['programmdetails_id'] = $__programmId;
            $sprachen['progsprache_id'] = $__languages[$i];

            $this->_db->insert('tbl_programmdetails_progsprachen', $sprachen);
        }

        return;
    }

    /**
     * Ermittelt die Anzahl der Programme unter Berücksichtigung der Suchparameter
     *
     * @param $__params
     * @return mixed
     */
    public function getCountPrograms($__params)
    {
        $auth = new Zend_Session_Namespace('Auth');

        $select = $this->_viewVorhandeneProgramme->select();

        $cols = array(
            "id" => new Zend_Db_Expr('count(id)')
        );

        $select->from($this->_viewVorhandeneProgramme, $cols);

        // Provider
//        if ($auth->role_id == $this->_condition_user_is_provider) {
//            $select->where("company_id = " . $auth->company_id);
//        }

        // Einschränkung nach Firma
        if (array_key_exists('company', $__params) and !empty($__params['company'])) {
            $select->where("company like '%" . $__params['company'] . "%'");
        }

        // Einschränkung nach Stadt
        if (array_key_exists('city', $__params) and !empty($__params['city'])) {
            $cityName = nook_ToolStadt::getStadtNameMitStadtId($__params['city']);
            $select->where("AO_City = '" . $cityName . "'");
        }

        // Einschränkung nach Programmbezeichnung
        if (array_key_exists('progsearch', $__params) and !empty($__params['progsearch'])) {
            $select->where("progname like '%" . $__params['progsearch'] . "%'");
        }

        // Einschränkung nach Programm ID
        if(array_key_exists('idSearch', $__params) and !empty($__params['idSearch'])){
            $select->where("id = ".$__params['idSearch']);
        }

        $query = $select->__toString();

        $row = $this->_viewVorhandeneProgramme->fetchRow($select)->toArray();

        return $row['id'];
    }

    /**
     * Übernimmt die Suchparameter für die Programmtabelle
     *
     * +
     * +
     *
     * @param $__params
     * @return mixed
     */
    public function setSearchItems($__params)
    {
        $start = false;
        $limit = false;
        $progsearch = false;
        $city = false;
        $company = false;

        if (array_key_exists('limit', $__params)) {
            $start = $__params['start'];
            $limit = $__params['limit'];
        }

        if (array_key_exists('progsearch', $__params)) {
            $progsearch = $__params['progsearch'];
        }

        if (array_key_exists('city', $__params)) {
            $city = $__params['city'];
        }

        if (array_key_exists('company', $__params)) {
            $company = $__params['company'];
        }

        if (array_key_exists('idSearch', $__params) and !empty($__params['idSearch'])) {
            $id = $__params['idSearch'];
        }

        // Abfrage der vorhandenen Programme
        $result = $this->getTableItems($start, $limit, $progsearch, $city, $company, $id);

        // Ergänzung um die Kooperationen
        $result = $this->ermittlungKooperationen($result);


        return $result;
    }

    /**
     * Ermittelt die vorhandenen Kooperationen und fügt diese zu den programmen hinzu
     *
     * @param array $programme
     * @return array
     */
    protected function ermittlungKooperationen(array $programme)
    {
        $adminModelKooperationenEinesProgrammes = new Admin_Model_KooperationenEinesProgrammes();

        // Abfragen der Kooperationen
        for($i=0; $i < count($programme); $i++){

            $kooperationen = $adminModelKooperationenEinesProgrammes
                ->setprogrammId($programme[$i]['id'])
                ->steuerungErmittlungKooperationen()
                ->getKooperationen();

           $vorhandeneKooperationen = array(
                'kooperationHob' => 1,
                'kooperationAustria' => 1,
                'kooperationAo' => 1
            );

            // Zuordnung der Kooperationen zum Programm
            for($j=0; $j < count($kooperationen); $j++){
                if($kooperationen[$j]['filiale'] == 1)
                    $vorhandeneKooperationen['kooperationHob'] = 2;

                if($kooperationen[$j]['filiale'] == 2)
                    $vorhandeneKooperationen['kooperationAustria'] = 2;

                if($kooperationen[$j]['filiale'] == 3)
                    $vorhandeneKooperationen['kooperationAo'] = 2;
            }

            $programme[$i] = array_merge($programme[$i], $vorhandeneKooperationen);
        }

        return $programme;
    }

    /**
     * Sucht die vorhandenen Programme entsprechend
     * der Suchparameter
     *
     * @param bool $__start
     * @param bool $__limit
     * @param bool $__progsearch
     * @param bool $__city
     * @param bool $__company
     * @return mixed
     */
    private function getTableItems(
        $__start = false,
        $__limit = false,
        $__progsearch = false,
        $__city = false,
        $__company = false,
        $__id = false
    ) {

        $start = 0;
        $limit = 20;

        if (!empty($__start)) {
            $start = $__start;
            $limit = $__limit;
        }

        $select = $this->_viewVorhandeneProgramme->select();

        // Einschränkung nach Programm ID
        if(!empty($__id)){
            $select->where("id = ".$__id);
        }

        // Einschränkung nach Programmbezeichnung
        if (!empty($__progsearch)) {
            $select->where("progname like '%" . $__progsearch . "%'");
        }

        // Einschränkung nach Stadt
        if (!empty($__city)) {
            $cityName = nook_ToolStadt::getStadtNameMitStadtId($__city);
            $select->where("AO_City = '" . $cityName . "'");
        }

        // Einschränkung nach Firma
        if (!empty($__company)) {
            $select->where("company like '%" . $__company . "%'");
        }

        $auth = new Zend_Session_Namespace('Auth');

//        if ($auth->role_id == $this->_condition_user_is_provider) {
//            $select->where("company_id = " . $auth->company_id);
//        }

        $select->order('progname')->order('company');
        $select->limit($limit, $start);

        $result = $this->_viewVorhandeneProgramme->fetchAll($select)->toArray();

        return $result;
    }

    public function getCellValue($__programmId, $__cell, $__sprache)
    {
        $sql = "
        SELECT
            `" . $__cell . "`
        FROM
            `tbl_programmbeschreibung`
        WHERE (`sprache` = '" . $__sprache . "'
            AND `Fa_Id` = '" . $__programmId . "')";

        $cellValue = $this->_db->fetchOne($sql);

        return $cellValue;
    }

    /**
     * Speichert die Beschreibung eines Programmes
     *
     * @param $__params
     */
    public function setValueDescriptionProgramm($__params)
    {

        // entfernen von überflüssigen Tags
        // erlaubt sind <b> <u> <br> <p> <a>
        $__params['txt'] = strip_tags($__params['txt'], "<b><a><br><p><u><i><span>");

        $update = array();
        $update['txt'] = $__params['txt'];
        $update['progname'] = $__params['progname'];

        $where = "programmdetail_id = " . $__params['programmId'] . " and sprache = '" . $__params['sprache'] . "'";
        $this->_db->update('tbl_programmbeschreibung', $update, $where);

        // Treffpunkte und Öpnv
        $this->_updateTreffpunktUndOpnv($__params['programmId'], $__params);

        return;
    }

    /**
     * Darstellen aller verfügbarer Programsprachen
     *
     * @param $__programmId
     * @return mixed
     */
    public function getAvailableProgramLanguages($__programmId)
    {
        $sql = "
	    	SELECT
			    `id`
			    , `de`
			    , `flag`
			FROM
			    `tbl_prog_sprache`
			ORDER BY `de` ASC";

        $allProgramLanguages = $this->_db->fetchAll($sql);

        // findet die Programmsprachen eines Programmes
        $actualProgramLanguages = $this->_findProgramLanguage($__programmId);

        // gleicht alle vorhandenen Sprachen mit ausgewählten Programmsprachen ab
        for ($i = 0; $i < count($allProgramLanguages); $i++) {
            $check = 1;
            for ($j = 0; $j < count($actualProgramLanguages); $j++) {
                if ($allProgramLanguages[$i]['id'] == $actualProgramLanguages[$j]['progsprache_id']) {
                    $check = 2;
                    break;
                }
            }

            $allProgramLanguages[$i]['check'] = $check;
        }

        return $allProgramLanguages;
    }

    /**
     * findet die Programmsprachen eines Programmes
     *
     * @param $__programmId
     * @return mixed
     */
    private function _findProgramLanguage($__programmId)
    {
        $sql = "select * from tbl_programmdetails_progsprachen where programmdetails_id = '" . $__programmId . "'";
        $languages = $this->_db->fetchAll($sql);

        return $languages;
    }

    /**
     * Ermittelt die Durchführungsdauer, den Hinweis,
     * die Saisondauer sowie die Buchungs und Stornofrist.
     * Korrigiert automatisch fehlende Buchungs und Stornofristen,
     * sowie das saisonende.
     *
     * @param $__programmId
     * @return mixed
     */
    public function findSchedulesFromProgram($__programmId)
    {
        $sql = "
	    	SELECT
			    `buchungsfrist`
			    , `minDuration`
			    , `maxDuration`
			    , `stornofrist`
			    , `hinweisDeutsch`
			    , `abfahrtszeit`
                , `hinweisEnglisch`
			    , DATE_FORMAT(`valid_from`, '%Y-%m-%d') AS valid_from
			    , DATE_FORMAT(`valid_thru`, '%Y-%m-%d') AS valid_thru
			FROM
			    `tbl_programmdetails`
			WHERE `id` = '" . $__programmId . "'";

        $ergebnis = $this->_db->fetchRow($sql);

        // Default Werte
        if (empty($ergebnis['valid_from'])) {
            $ergebnis['valid_from'] = nook_Tool::buildGermanDateNow();
        }

        if (empty($ergebnis['valid_thru'])) {
            $ergebnis['valid_thru'] = $this->_condition_default_saisonende;
        }

        if (empty($ergebnis['buchungsfrist'])) {
            $ergebnis['buchungsfrist'] = 0;
        }

        if (empty($ergebnis['stornofrist'])) {
            $ergebnis['stornofrist'] = $this->_condition_default_stornofrist;
        }

        $ergebnis['minDuration'] = number_format($ergebnis['minDuration'], 2, ':', '.');
        $ergebnis['maxDuration'] = number_format($ergebnis['maxDuration'], 2, ':', '.');

        return $ergebnis;
    }

    public function setSchedulesFromProgram($__termineEinesProgrammes)
    {
        $programmId = $__termineEinesProgrammes['programmId'];
        unset($__termineEinesProgrammes['programmId']);
        unset($__termineEinesProgrammes['module']);
        unset($__termineEinesProgrammes['controller']);
        unset($__termineEinesProgrammes['action']);

        $minDuration = str_replace(':', '.', $__termineEinesProgrammes['minDuration']);
        $__termineEinesProgrammes['minDuration'] = floatval($minDuration);

        $minDuration = str_replace(':', '.', $__termineEinesProgrammes['maxDuration']);
        $__termineEinesProgrammes['maxDuration'] = floatval($minDuration);

        $__termineEinesProgrammes['valid_from'] = nook_Tool::splitGermanDateToEnglishDate(
            $__termineEinesProgrammes['valid_from']
        );
        $__termineEinesProgrammes['valid_thru'] = nook_Tool::splitGermanDateToEnglishDate(
            $__termineEinesProgrammes['valid_thru']
        );

        $this->_db->update('tbl_programmdetails', $__termineEinesProgrammes, "id = '" . $programmId . "'");

        return;
    }

    /**
     * Findet diverse Informationen eines Programmes
     *
     * @param $__programmId
     * @return array
     */
    public function getDiversesFromProgram($__programmId)
    {

        // allgemeine Programmdetails des Programmes
        $standardInformationenDiverses = $this->_findDiversesProgrammdetails($__programmId);

        $gesamtDiverses = array_merge($standardInformationenDiverses, $opnvAndMeetingpoint);

        // Textbausteine Bestätigungstexte
        $bestaetigungsTexte = $this->_findBestaetigungstexteEinerProgrammbeschreibung($__programmId);
        $gesamtDiverses = array_merge($gesamtDiverses, $bestaetigungsTexte);

        return $gesamtDiverses;
    }

    /**
     * findet allgemeine Angaben in der Tabelle 'tbl_programmdetails'
     *
     * @param $__programmId
     * @return array
     */
    private function _findDiversesProgrammdetails($__programmId)
    {
        $cols = array(
            "id",
            "minPersons",
            "maxPersons",
            "prio_noko",
            "AO_City",
            "aktiv"
        );

        $select = $this->_tabelleProgrammdetails->select();
        $select->from($this->_tabelleProgrammdetails, $cols)->where("id = " . $__programmId);

        $ergebnis = $this->_tabelleProgrammdetails->fetchRow($select);

        if ($ergebnis != null) {
            $standardInformationenDiverses = $ergebnis->toArray();
        } else {
            throw new nook_Exception($this->_error_keine_daten_vorhanden);
        }

        return $standardInformationenDiverses;
    }

    /**
     * Findet die Bestätigungstexte
     * einer Programmbeschreibung
     *
     * @param $__programmId
     * @return array
     * @throws nook_Exception
     */
    private function _findBestaetigungstexteEinerProgrammbeschreibung($__programmId)
    {
        $cols = array(
            "confirm_1",
            "confirm_2",
            "confirm_3",
            "an_prog_1",
            "sprache",
            "buchungstext"
        );

        $select = $this->_tabelleProgrammbeschreibung->select();
        $select->from($this->_tabelleProgrammbeschreibung, $cols)->where('programmdetail_id = ' . $__programmId);
        $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if (count($rows) <> $this->_condition_erwartete_anzahl_datensaetze) {
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);
        }

        $bestaetigungstexte = array();

        for ($i = 0; $i < $this->_condition_erwartete_anzahl_datensaetze; $i++) {
            if ($rows[$i]['sprache'] == $this->_condition_is_german_languge) {
                $bestaetigungstexte['confirm_1_de'] = $rows[$i]['confirm_1'];
                $bestaetigungstexte['confirm_2_de'] = $rows[$i]['confirm_2'];
                $bestaetigungstexte['confirm_3_de'] = $rows[$i]['confirm_3'];
                $bestaetigungstexte['an_prog_1_de'] = $rows[$i]['an_prog_1'];
                $bestaetigungstexte['buchungstext_de'] = $rows[$i]['buchungstext'];
            } else {
                $bestaetigungstexte['confirm_1_en'] = $rows[$i]['confirm_1'];
                $bestaetigungstexte['confirm_2_en'] = $rows[$i]['confirm_2'];
                $bestaetigungstexte['confirm_3_en'] = $rows[$i]['confirm_3'];
                $bestaetigungstexte['an_prog_1_en'] = $rows[$i]['an_prog_1'];
                $bestaetigungstexte['buchungstext_en'] = $rows[$i]['buchungstext'];
            }
        }

        return $bestaetigungstexte;
    }

    /**
     * Findet den Treffpunkt und die Hinweise ÖPNV
     * zu einem Programm.
     * Gibt Sprachvarianten zurück
     *
     * @param $__programmId
     * @return array
     */
    private function _findOpnvAndMeetingpoint($__programmId)
    {
        $sql = "select treffpunkt, OePNV, sprache from tbl_programmbeschreibung where programmdetail_id = " . $__programmId . " order by sprache";
        $ergebnis = $this->_db->fetchAll($sql);

        $opnvAndMeetingpoint = array();
        $opnvAndMeetingpoint['opnv_de'] = $ergebnis[0]['OePNV'];
        $opnvAndMeetingpoint['treffpunkt_de'] = $ergebnis[0]['treffpunkt'];
        $opnvAndMeetingpoint['opnv_en'] = $ergebnis[1]['OePNV'];
        $opnvAndMeetingpoint['treffpunkt_en'] = $ergebnis[1]['treffpunkt'];

        return $opnvAndMeetingpoint;
    }

    public function findCountriesForProgramms()
    {
        $sql = "select id, de, flag from tbl_prog_sprache order by de";

        $countries = $this->_db->fetchAll($sql);

        return $countries;
    }

    public function findBundeslaenderForProgramms()
    {
        $sql = "select * from tbl_bundeslaender order by bundesland";

        $bundeslaender = $this->_db->fetchAll($sql);

        return $bundeslaender;
    }

    /**
     * Update der Textinformationen / Diverses eines Programmes
     *
     * @param $__programmDetailsDiverses
     * @return array
     */
    public function updateDiversesEinesProgrammes($__programmDetailsDiverses)
    {
        // Tabelle Programmdetails
        $this->_updateDiversesTabelleProgrammdetails(
            $__programmDetailsDiverses,
            $__programmDetailsDiverses['programmId']
        );

        // Bestätigungstexte eintragen
        $this->_updateBestaetigungstexteEinerProgrammbeschreibung(
            $__programmDetailsDiverses,
            $__programmDetailsDiverses['programmId']
        );

        // Treffpunkte und Öpnv
        $this->_updateTreffpunktUndOpnv($__programmDetailsDiverses['programmId'], $__programmDetailsDiverses);

        return;
    }

    /**
     * Update allgemeiner Angaben eines
     * Programmes in Tabelle 'tbl_programmdetails'
     *
     * @param $programmDetailsDiverses
     * @param $programmId
     */
    private function _updateDiversesTabelleProgrammdetails(array $programmDetailsDiverses, $programmId)
    {
        $details = array();
        $details['AO_City'] = $programmDetailsDiverses['AO_City'];
        $this->_db->update('tbl_programmdetails', $details, "id = " . $programmId);

        return;
    }

    /**
     * Deutscher und englischer Bestätigungstext
     *
     * @param $__diverses
     * @param $__programmId
     */
    private function _updateBestaetigungstexteEinerProgrammbeschreibung($__diverses, $__programmId)
    {

        $cols_de = array(
            "confirm_1" => $__diverses['confirm_1_de'],
            "confirm_2" => $__diverses['confirm_2_de'],
            "confirm_3" => $__diverses['confirm_3_de'],
            "an_prog_1" => $__diverses['an_prog_1_de'],
            "buchungstext" => $__diverses['buchungstext_de']
        );

        $where_de = array(
            "programmdetail_id = " . $__programmId,
            "sprache = " . $this->_condition_is_german_languge
        );

        $cols_en = array(
            "confirm_1" => $__diverses['confirm_1_en'],
            "confirm_2" => $__diverses['confirm_2_en'],
            "confirm_3" => $__diverses['confirm_3_en'],
            "an_prog_1" => $__diverses['an_prog_1_en'],
            "buchungstext" => $__diverses['buchungstext_en']
        );

        $where_en = array(
            "programmdetail_id = " . $__programmId,
            "sprache = " . $this->_condition_is_english_languge
        );

        // deutsche Bestätigungstexte
        $this->_tabelleProgrammbeschreibung->update($cols_de, $where_de);

        // englische Bestätigungstexte
        $this->_tabelleProgrammbeschreibung->update($cols_en, $where_en);

        return;
    }

    /**
     * Update Treffpunkte und ÖPNV
     *
     * @param $programmId
     * @param $__params
     */
    private function _updateTreffpunktUndOpnv($programmId, $__params)
    {
        $treffpunktUndOpnvDe = array();
        $treffpunktUndOpnvDe['treffpunkt'] = $__params['treffpunkt_de'];
        $treffpunktUndOpnvDe['OePNV'] = $__params['opnv_de'];

        $treffpunktWhereDe = array();
        $treffpunktWhereDe[0] = "programmdetail_id = " . $programmId;
        $treffpunktWhereDe[1] = 'sprache = ' . $this->_condition_is_german_languge;

        $this->_db->update('tbl_programmbeschreibung', $treffpunktUndOpnvDe, $treffpunktWhereDe);

        $treffpunktUndOpnvEn = array();
        $treffpunktUndOpnvEn['treffpunkt'] = $__params['treffpunkt_en'];
        $treffpunktUndOpnvEn['OePNV'] = $__params['opnv_en'];

        $treffpunktWhereEn = array();
        $treffpunktWhereEn[0] = "programmdetail_id = " . $programmId;
        $treffpunktWhereEn[1] = 'sprache = ' . $this->_condition_is_english_languge;

        $this->_db->update('tbl_programmbeschreibung', $treffpunktUndOpnvEn, $treffpunktWhereEn);

        return;
    }
}
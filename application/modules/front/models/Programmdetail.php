<?php
// namespace front\models\programmdetail;

class Front_Model_Programmdetail extends nook_Model_model
{
    // Tabellen / Views
    private $_tabelleProgrammePreisvarianten = NULL;
    private $_tabelleProgrammbuchung = NULL;
    private $_tabelleSperrtage = NULL;
    private $_viewProgrammeBuchungsdetails = NULL;

    // Konditionen
    private $condition_language_ger = 1;
    private $condition_language_en = 2;
    private $condition_programm_status_angefragt = 2;
    private $condition_zaehler_aktive_buchung = 0;
    private $condition_programm_im_warenkorb = 1;

    // Fehler
    public $error_not_correct_programId = 30;
    public $error_buchungsdaten_nicht_korrekt = 31;
    public $error_buchungsid_gehoert_nicht_den_kunden = 32;
    public $error_fehlender_anfangswert = 33;

    // Container
    public $dic = array();

    protected $_kundenId;
    protected $_buchungsnummerId = false;
    protected $_insertProgrammdatensatzId = NULL;
    protected $_programmzeit = "00:00";
    protected $_programId;
    protected $_selectLanguage;
    protected $gebuchtesProgrammId = NULL;

    public function __construct()
    {
        $buchungsnummer = new nook_Buchungsnummer();

        // dependency injection controller
        $this->dic['buchungsnummer'] = $buchungsnummer;
        $this->dic['preisberechnung'] = new Front_Model_ProgrammdetailPreismanager();
        $this->dic['programmvarianten'] = new Front_Model_ProgrammdetailProgrammvarianten();
        $this->dic['modelXML'] = new Front_Model_ProgrammdetailXml();

        /** @var $_tabelleProgrammePreisvarianten Application_Model_DbTable_preise */
        $this->_tabelleProgrammePreisvarianten = new Application_Model_DbTable_preise(array( 'db' => 'front' ));
        /** @var $_tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array( 'db' => 'front' ));
        /** @var _viewProgrammeBuchungsdetails Application_Model_DbTable_viewProgrammeBuchungsdetails */
        $this->_viewProgrammeBuchungsdetails = new Application_Model_DbTable_viewProgrammeBuchungsdetails();
        /** @var _tabelleSperrtage Application_Model_DbTable_sperrtage */
        $this->_tabelleSperrtage = new Application_Model_DbTable_sperrtage();
    }

    /**
     * @param $gebuchtesProgrammBuchungstabelleId
     *
     * @return Front_Model_Programmdetail
     */
    public function setGebuchtesProgramm($gebuchtesProgrammBuchungstabelleId)
    {
        $gebuchtesProgrammBuchungstabelleId = (int) $gebuchtesProgrammBuchungstabelleId;
        $this->gebuchtesProgrammId = $gebuchtesProgrammBuchungstabelleId;

        return $this;
    }

    /**
     * Steuert das löschen einer vorhandenen Programmbuchung
     *
     * @return Front_Model_Programmdetail
     * @throws nook_Exception
     */
    public function steuerungLoeschenGebuchtesProgramm()
    {
        if (empty($this->gebuchtesProgrammId) or !is_int($this->gebuchtesProgrammId)) {
            throw new nook_Exception($this->error_fehlender_anfangswert);
        }

        $this->loeschenGebuchtesProgramm();

        return $this;
    }

    /**
     * Löscht eine vorhandene Programmbuchung
     */
    private function loeschenGebuchtesProgramm()
    {
        /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->_tabelleProgrammbuchung;
        $tabelleProgrammbuchung->delete("id = " . $this->gebuchtesProgrammId);

        return;
    }

    /**
     * Findet die gewählte Anzeigesprache
     *
     * @param bool $language
     *
     * @return
     */
    public function findLanguage($language = false)
    {
        if (empty($language)) {
            $language = Zend_Registry::get('language');
            if ($language == 'de') {
                $this->_selectLanguage = $this->condition_language_ger;
            }
            else {
                $this->_selectLanguage = $this->condition_language_en;
            }

            return;
        }
        else {
            $this->_selectLanguage = $language;

            return;
        }
    }

    public function setLanguage($__languageId)
    {
        $this->_selectLanguage = $__languageId;

        return;
    }

    public function setProgramId($programId)
    {
        $programId = (int) $programId;

        if (!$programId = intval($programId)) {
            throw new nook_Exception($this->error_not_correct_programId);
        }

        $validator = new Zend_Validate_Int();
        if (!$validator->isValid($programId)) {
            throw new nook_Exception($this->error_not_correct_programId);
        }

        $this->_programId = $programId;
    }

    /**
     * Findet die Programmdetails in
     * Abhängigkeit der gewählten Sprache.
     * Ermittelt
     *
     * @return array
     */
    public function getProgramDetails()
    {
        $programDetails = $this->_getProgramDetails();
        $bildId = nook_ToolProgrammbilder::findImageFromProgram($this->_programId, 'midi');
        $programDetails['midiImage'] = nook_ToolProgrammbilder::getImagePathForProgram($bildId);

        return $programDetails;
    }

    /**
     * Ermittelt die Programmdetails
     *
     * @return mixed
     */
    private function _getProgramDetails()
    {

        $sql = "select progname , txt, OePNV as opnv_de, treffpunkt as treffpunkt_de, buchungstext from tbl_programmbeschreibung where programmdetail_id = '" . $this->_programId . "' and sprache = '" . $this->_selectLanguage . "'";
        $db = Zend_Registry::get('front');
        $programDetails = $db->fetchRow($sql);
        if (!empty($programDetails['noko_lang'])) {
            $programDetails['txt'] = $programDetails['noko_lang'];
        }

        // Inhalt Worddokument
        $sql = "select worddocument from tbl_programmdetails where id = " . $this->_programId;
        $programDetails['worddokument'] = $db->fetchOne($sql);

        $sql = "select abfahrtszeit from tbl_programmdetails where id = " . $this->_programId;
        $programDetails['abfahrtszeit'] = $db->fetchOne($sql);
        $sql = "select personenzahlregel from tbl_programmdetails where id = " . $this->_programId;
        if ($db->fetchOne($sql)!=""){
            $programDetails['personenzahlregel'] = $db->fetchOne($sql);
        }else{
            $programDetails['personenzahlregel'] = "keine";
        }


        $sql = "select freiplatzregel from tbl_programmdetails where id = " . $this->_programId;
        if ($db->fetchOne($sql)!=""){
            $programDetails['freiplatzregel'] = $db->fetchOne($sql);
        }else{
            $programDetails['freiplatzregel'] = "keine";
        }

        return $programDetails;
    }

    /**
     * Ermittelt die sprachunabhängigen Details einer
     * Programmbuchung
     *
     * @return mixed
     */
    public function getBookingDates()
    {
        $select = $this->_viewProgrammeBuchungsdetails->select();
        $select->where('id = ' . $this->_programId);
        $bookingDetails = $this->_viewProgrammeBuchungsdetails->fetchRow($select)->toArray();

        if (!empty($bookingDetails['Dauer'])) {
            $bookingDetails['Dauer'] = nook_ToolZeiten::aendereDarstellungZeit($bookingDetails['Dauer']);
        }

        $bookingDetails = nook_Tool::addVat($bookingDetails);
        $bookingDetails = $this->bookingDeadlineFrom($bookingDetails);
        $bookingDetails = $this->bookingDeadlineTo($bookingDetails);

        return $bookingDetails;
    }

    /**
     * Ermittelt Saisonbeginn und frühest mögliche Buchung eines Programmes
     *
     * + errechnet den frühest möglichen Zeitpunkt der Buchung unter Berücksichtigung der Sperrtage und der Saison
     * + frühest möglicher Buchungstag
     * + frühest möglicher Buchungsmonat
     * + frühest möglicher Buchungsjahr
     *
     * @param $bookingDetails
     *
     * @return mixed
     */
    private function bookingDeadlineFrom($bookingDetails)
    {
        // momentanes Datum in Sekunden
        $datum = time();

        // Berücksichtigung der Buchungsfrist des Programmes
        if (!empty($bookingDetails['Buchungsfrist'])) {
            $datum += (86400 * $bookingDetails['Buchungsfrist']);
        }
        else {
            $datum += (86400 * 0);
        }

        // Kontrolle ob das Datum auf einen Sperrtag und in die Saison fällt. Automatische Datumskorrektur.
        // Berechnet nur für 10 Tage
        $datum = $this->kontrolleFruehesterBuchungstermin($datum, $bookingDetails['ProgrammId']);

        // Berechnung Saison nächstes Jahr
        if (false === $datum) {
            // $this->saisonNaechstesJahr($bookingDetails, $datum);
            $datum = time();
        }

        // Kontrolle ob in 'tbl_programmdetails_oeffnungszeiten'
        $wochentagZiffer = date("w", $datum);
        // Korrektur Sonntag
        if ($wochentagZiffer == 0) {
            $wochentagZiffer = 7;
        }

        // ermitteln Typ der Öffnungszeiten
        $toolStartdatumBuchungProgramme = new nook_ToolStartdatumBuchungProgramm();
        $typOeffnungszeitProgramm = $toolStartdatumBuchungProgramme
            ->setZeitSekunden($datum)
            ->setProgrammId($bookingDetails['ProgrammId'])
            ->steuerungErmittlungErsterBuchungstag()
            ->getTypOeffnungszeitenProgramm();

        // Typ der Oeffnungszeit
        if ($typOeffnungszeitProgramm < 4) {
            $datum = $toolStartdatumBuchungProgramme->getErsterMoeglicherTagProgrammbuchungSekunden();

            // frühest mögliche Buchungsfrist in Tagen
            $bookingDetails['fromJahr'] = date("Y", $datum);
            $bookingDetails['fromMonat'] = date("m", $datum);
            $bookingDetails['fromTag'] = date("d", $datum);
        }
        else {
            $bookingDetails['fromJahr'] = 2020;
            $bookingDetails['fromMonat'] = 12;
            $bookingDetails['fromTag'] = 31;
        }

        // Saisonbeginn
        if (!empty($bookingDetails['Saisonbeginn'])) {
            // Trennung von Datum und Zeit
            $teile = explode(' ', $bookingDetails['Saisonbeginn']);
            // zerlegen Datum
            $teileDateFrom = explode("-", $teile[0]);

            // Unix Datum Saisonstart
            $saisonstart = mktime(0, 0, 0, $teileDateFrom[1], $teileDateFrom[2], $teileDateFrom[0]);

            if ($saisonstart > $datum) {
                $bookingDetails['fromJahr'] = $teileDateFrom[0];
                $bookingDetails['fromMonat'] = $teileDateFrom[1];
                $bookingDetails['fromTag'] = $teileDateFrom[2];
            }
        }

        $bookingDetails['fromJahr'] = intval($bookingDetails['fromJahr']);
        $bookingDetails['fromMonat'] = intval($bookingDetails['fromMonat']);
        $bookingDetails['fromTag'] = intval($bookingDetails['fromTag']);

        return $bookingDetails;
    }

    /**
     * Ermittelt den frühest möglichen Buchungstermin, schliest die Sperrtage aus
     *
     * + wenn Sperrtag dann werden die nächsten 10 Tage kontrolliert
     * + wenn diese 10 Tage Sperrtage sind, dann wird ein false zurückgegeben
     *
     * @param nook_ToolSperrtageUndSaisonProgramm $toolSperrtageUndSaisonProgramm
     * @param $datumInSekunden
     *
     * @return mixed
     */
    private function kontrolleFruehesterBuchungstermin($datumInSekunden, $programmId)
    {
        $toolSperrtageUndSaisonProgramm = new nook_ToolSperrtageUndSaisonProgramm($programmId);

        for ($i = 0; $i < 10; $i++) {

            $datumInSekunden += ($i * 86400);

            $flagBuchungMoeglich = $toolSperrtageUndSaisonProgramm
                ->setDatumInSekunden($datumInSekunden)
                ->sucheMoeglichesBuchungsdatum()
                ->getFlagBuchungMoeglich();

            if (true === $flagBuchungMoeglich) {
                break;
            }

        }

        if (true === $flagBuchungMoeglich) {
            return $datumInSekunden;
        }
        else {
            return false;
        }
    }

    /**
     * Berechnet das Saisonende
     *
     * @param $__bookingDetails
     *
     * @return mixed
     */
    private function bookingDeadlineTo($__bookingDetails)
    {
        $deatDate = time();

        // saisonende
        if (empty($__bookingDetails['Saisonende'])) {
            $deatDate += (86400 * 364);

            $__bookingDetails['toJahr'] = date("Y", $deatDate);
            $__bookingDetails['toMonat'] = date("m", $deatDate);
            $__bookingDetails['toTag'] = date("d", $deatDate);
        }
        else {
            $teile = explode(' ', $__bookingDetails['Saisonende']);
            $teileDateTo = explode("-", $teile[0]);

            $__bookingDetails['toJahr'] = $teileDateTo[0];
            $__bookingDetails['toMonat'] = $teileDateTo[1];
            $__bookingDetails['toTag'] = $teileDateTo[2];
        }

        $__bookingDetails['toJahr'] = intval($__bookingDetails['toJahr']);
        $__bookingDetails['toMonat'] = intval($__bookingDetails['toMonat']);
        $__bookingDetails['toTag'] = intval($__bookingDetails['toTag']);

        return $__bookingDetails;
    }

    /**
     * Ermittelt den Stadtnamen
     *
     * @param $__params
     *
     * @return string
     */
    public function getCityCrumb($__params)
    {
        include_once('../library/nook/breadcrumb.php');
        $breadcrumb = new breadcrumb($__params);

        return $breadcrumb->getCityName();
    }

    /*** speichern der Buchungsdaten ***/

    public function kontrolleProgrammvarianten($__buchungsdaten)
    {

        /** @var $programmVarianten Front_Model_ProgrammdetailProgrammvarianten */
        $programmVarianten = $this->dic['programmvarianten'];
        $gebuchteProgrammVarianten = $programmVarianten->kontrolleAnkommendeProgrammVarianten($__buchungsdaten);

        // speichern gebuchte Programmvarianten
        $this->dic['gebuchteProgrammvarianten'] = $gebuchteProgrammVarianten;

        return;
    }

    /**
     * Ermittelt die Sperrtage eines Programmes und gibt diese zurück.
     *
     * + Korrektur des Monats, führende 0
     *
     * @param $__programmId
     *
     * @return string
     */
    public function getSperrtageEinesProgrammes($__programmId)
    {
        $select = $this->_tabelleSperrtage->select();
        $select->where('programmdetails_id = ' . $__programmId);
        $sperrtageArray = $this->_tabelleSperrtage->fetchAll($select)->toArray();

        $sperrtageEinesProgrammes = '';
        for ($i = 0; $i < count($sperrtageArray); $i++) {

            // führende 0 eines Monat
            if($sperrtageArray[$i]['monat'] < 10)
                $sperrtageArray[$i]['monat'] = "0".$sperrtageArray[$i]['monat'];

            $sperrtageEinesProgrammes .= "'" . $sperrtageArray[$i]['jahr'] . "-" . $sperrtageArray[$i]['monat'] . "-" . $sperrtageArray[$i]['tag'] . "',";
        }

        $sperrtageEinesProgrammes = substr($sperrtageEinesProgrammes, 0, -1);

        return $sperrtageEinesProgrammes;
    }

    /**
     * Berechnet an Hand der Buchungsdaten den tatsächlichen Verkaufspreis.
     * Verwendet zur Preisneuberechnung den Programmdetail Preismanager.
     *
     * @return null
     */
    private function _berechnungPreis()
    {

        for ($i = 0; $i < count($this->dic['gebuchteProgrammvarianten']); $i++) {

            /** @var $select Zend_Db_Table_Select */
            $select = $this->_tabelleProgrammePreisvarianten->select();
            $select->from($this->_tabelleProgrammePreisvarianten, array( 'verkaufspreis' ))->where(
                "id = " . $this->dic['gebuchteProgrammvarianten'][$i]['programmVariante']
            );

            $verkaufspreis = $this->_tabelleProgrammePreisvarianten->fetchRow($select)->toArray();

            $this->dic['gebuchteProgrammvarianten'][$i]['verkaufspreis'] = $verkaufspreis['verkaufspreis'];
        }

        return;
    }

    /**
     * Eintragen der Buchungsdaten einer Programmvariante
     * eines Programmes
     * in die Tabelle 'produktbuchung'
     * Kontrolle und eintragen der Buchung
     * in die Tabelle 'buchungsnummer'
     *
     * @param $__buchungsdaten
     *
     * @return Front_Model_Programmdetail
     */
    public function startSpeichernBuchungsdatenProgramm($__buchungsdaten)
    {

        // Berechnung Preis der Programmvariante
        $this->_berechnungPreis();

        /** @var $bestimmeBuchungsnummer nook_buchungsnummer */
        $bestimmeBuchungsnummer = $this->dic['buchungsnummer'];
        $this->_buchungsnummerId = $bestimmeBuchungsnummer->eintragenBuchungsnummer();

        // speichern der Buchungsdaten
        $this->_saveBuchungsdatenProgramm($__buchungsdaten);

        // speichern Programmvarianten als XML
        $this->saveXmlBuchungsdatenProgramm($__buchungsdaten);

        return;
    }

    /**
     * Update eines bereits gebuchten Programmdatensatzes
     *
     * + Veränderung der Anzahl der Preisvarianten
     *
     * @param $buchungsdaten
     */
    public function updateProgrammdatensatz(array $updateArtikel)
    {
        $this->_updateBereitsGebuchtePreisvarianten($updateArtikel);

        return $this;
    }

    /**
     * Speichert die Buchungsdaten in einem XML Block
     *
     * @param $__buchungsdaten
     *
     * @return
     */
    private function saveXmlBuchungsdatenProgramm($__buchungsdaten)
    {
        $sprache = nook_ToolSprache::getAnzeigesprache();

        /** @var $modelXML Front_Model_ProgrammdetailXml */
        $modelXML = $this->dic['modelXML'];
        $modelXML
            ->setBuchungsnummerId($this->_buchungsnummerId)
            ->setProgrammbuchungId($__buchungsdaten['ProgrammId'])
            ->setAnzeigesprache($sprache)
            ->startSaveXmlBuchungsdatenProgramm();

        return;
    }

    /**
     * Speichert die gebuchten Programme in der Tabelle 'tbl_programmbuchung'.
     *
     * Kontrolliert ob eine Programmbuchung schon vorhanden ist.
     * Wenn eine Programmbuchung schon vorhanden ist,
     * erfolgt ein Update der Anzahl der Programme.
     *
     * @param array $__buchungsdaten
     *
     * @return
     */
    private function _saveBuchungsdatenProgramm(array $__buchungsdaten)
    {
        $tabelleProgrammbuchung = $this->_tabelleProgrammbuchung;

        for ($i = 0; $i < count($this->dic['gebuchteProgrammvarianten']); $i++) {

            $gebuchteProgrammvariante = $this->dic['gebuchteProgrammvarianten'][$i];

            $toolBuchungstyp = new nook_ToolBuchungstyp();
            $buchungsTypOfflinebuchung = $toolBuchungstyp
                ->setProgrammId($__buchungsdaten['ProgrammId'])
                ->ermittleBuchungstypProgramm();

            $insert = array(
                'programmdetails_id'              => $__buchungsdaten['ProgrammId'],
                'datum'                           => nook_Tool::erstelleSuchdatumAusFormularDatum(
                        $gebuchteProgrammvariante['programmDatum']
                    ),
                'anzahl'                          => $gebuchteProgrammvariante['programmAnzahl'],
                'buchungsnummer_id'               => $this->_buchungsnummerId,
                'tbl_programme_preisvarianten_id' => $gebuchteProgrammvariante['programmVariante'],
                'zeit'                            => $gebuchteProgrammvariante['programmZeit'],
                'status'                          => $this->condition_programm_im_warenkorb,
                'sprache'                         => $__buchungsdaten['sprache'],
                'offlinebuchung'                  => $buchungsTypOfflinebuchung
            );

            // findet bereits gebuchte Programme
            $anzahlBereitsGebuchteProgrammvarianten = $this->findeBereitsGebuchtePreisvarianten($insert);

            // neu eintragen Preisvariante eines Programmes
            if (empty($anzahlBereitsGebuchteProgrammvarianten)) {
                $tabelleProgrammbuchung->insert($insert);
            }
            // updaten Preisvariante eines Programmes
            else {
                $frontModelUpdateProgramm = new Front_Model_ProgrammUpdate();
                $anzahlUpdateProgrammbuchung = $frontModelUpdateProgramm
                    ->setBuchungsdaten($insert)
                    ->setKorrekturAnzahlProgramme($anzahlBereitsGebuchteProgrammvarianten)
                    ->steuerungKontrolleProgrammBereitsgebucht()
                    ->getAnzahlUpdateProgrammbuchung();
            }
        }

        return;
    }

    /**
     * Updatet die Anzahl einer
     * bereits vorhandenen Preisvariante
     *
     * @param $datenUpdateProgramm
     *
     * @return void
     */
    private function _updateBereitsGebuchtePreisvarianten(array $datenUpdateProgramm)
    {
        $namespaceTranslate = new Zend_Session_Namespace('translate');
        $namespaceTranslateVariablen = (array) $namespaceTranslate->getIterator();

        if ($namespaceTranslateVariablen['language'] == 'de') {
            $datumIso = nook_ToolDatum::wandleDatumDeutschInEnglisch($datenUpdateProgramm['datum']);
        }
        else {
            $teileDatum = explode('/', $datenUpdateProgramm['datum']);
            $datumIso = $teileDatum[2] . '-' . $teileDatum[0] . '-' . $teileDatum[1];
        }

        if (!array_key_exists('sprache', $datenUpdateProgramm)) {
            $datenUpdateProgramm['sprache'] = 0;
        }

        if ($datenUpdateProgramm['zeitmanagerMinute'] == 0) {
            $datenUpdateProgramm['zeitmanagerMinute'] = '00';
        }

        $datenUpdateProgramm['zeit'] = $datenUpdateProgramm['zeitmanagerStunde'] . ":" . $datenUpdateProgramm['zeitmanagerMinute'] . ":00";

        $where = array(
            'id = ' . $this->gebuchtesProgrammId
        );

        $update = array(
            'anzahl'  => $datenUpdateProgramm['anzahl'],
            'datum'   => $datumIso,
            'zeit'    => $datenUpdateProgramm['zeit'],
            'sprache' => $datenUpdateProgramm['sprache']
        );

        $this->_tabelleProgrammbuchung->update($update, $where);

        return;
    }

    /**
     * Überprüft ob der Kunde bereits diese Preisvariante gespeichert hat.
     * Korrektur Zeitstempel um Angabe Sekunden
     * + Kontrolle Buchungsnummer
     * + Kontrolle Programm ID
     * + Kontrolle Datum
     * + Kontrolle Zeit
     * + Programmvariante
     *
     * @param $i
     * @param $__programmId
     *
     * @return int
     */
    private function findeBereitsGebuchtePreisvarianten($buchungsdatenProgramm)
    {
        $whereBuchungsnummer = "buchungsnummer_id = " . $buchungsdatenProgramm['buchungsnummer_id'];
        $whereProgrammdetails = "programmdetails_id = " . $buchungsdatenProgramm['programmdetails_id'];
        $wherePreisvariante = "tbl_programme_preisvarianten_id = " . $buchungsdatenProgramm['tbl_programme_preisvarianten_id'];
        $whereZaehlerAktiveBuchung = "zaehler = " . $this->condition_zaehler_aktive_buchung;

        $whereDatum = "datum = '" . $buchungsdatenProgramm['datum'] . "'";
        $whereZeit = "zeit = '" . $buchungsdatenProgramm['zeit'] . "'";
        $whereSprache = "sprache = " . $buchungsdatenProgramm['sprache'];

        $cols = array(
            'anzahl'
        );

        $select = $this->_tabelleProgrammbuchung->select();
        $select
            ->from($this->_tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereProgrammdetails)
            ->where($wherePreisvariante)
            ->where($whereZaehlerAktiveBuchung)
            ->where($whereDatum)
            ->where($whereZeit)
            ->where($whereSprache);

        $query = $select->__toString();

        $rows = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();

        if (empty($rows[0]['anzahl'])) {
            $anzahlBereitsVorhandenePreisvarianten = 0;
        }
        else {
            $anzahlBereitsVorhandenePreisvarianten = $rows[0]['anzahl'];
        }

        return $anzahlBereitsVorhandenePreisvarianten;
    }

    /**
     * Gibt die Buchungsnummer zurück
     *
     * @return bool
     */
    public function getBuchungsnummerId()
    {

        return $this->_buchungsnummerId;
    }

    /**
     * Findet Informationen zu einer Buchungs ID aus der Tabelle
     * 'programmbuchung'.
     * Gibt ID der Stadt zurück
     *
     * @param $__idBuchungstabelle
     *
     * @return $cityId Int
     */
    public function getBuchungsDetail($__idBuchungstabelle)
    {
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        $sql = "
            SELECT
                `tbl_programmbuchung`.`fa_id` AS `programId`
                , `tbl_programmdetails`.`AO_City` AS `city`
                , `tbl_programmbuchung`.`buchungsnummer_id` as buchungsnummer
            FROM
                `tbl_programmbuchung`
                INNER JOIN `tbl_programmdetails`
                    ON (`tbl_programmbuchung`.`fa_id` = `tbl_programmdetails`.`Fa_ID`)
            WHERE (`tbl_programmbuchung`.`id` = " . $__idBuchungstabelle . ")";

        $daten = $db->fetchRow($sql);



        return $daten;
    }

    /**
     * Berechnet die Saison des nächsten Jahres
     *
     * @param $bookingDetails
     * @param $toolSperrtageUndSaisonProgramm
     * @param $datum
     */
    private function saisonNaechstesJahr(&$bookingDetails, &$datum)
    {
        $toolSaisonNaechstesJahr = new nook_ToolSaisonNaechstesJahr();
        $toolSaisonNaechstesJahr->setSaisondaten($bookingDetails)->steuerungSaisonNaechstesJahr();

        $bookingDetails['Saisonbeginn'] = $toolSaisonNaechstesJahr->getSaisonStartKommendesJahr();
        $bookingDetails['Saisonende'] = $toolSaisonNaechstesJahr->getSaisonEndeKommendesJahr();

        $datum = $toolSaisonNaechstesJahr->getErstesMoeglichesBuchungsdatumInSekunden();

        return;
    }
}
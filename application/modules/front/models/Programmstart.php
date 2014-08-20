<?php
class Front_Model_Programmstart{
    private $cityId;
    private $_cityName;
    private $selectLanguage;
    private $actualPage;
    private $programmsuche = null;
    protected $anzahlProgrammeProSeite = 0;
    protected $programmkategorieId = null;
    protected $filialeId = null;

    // Programme in einer Stadt
    protected $programmeCity = array();

    // Konditionen
    private $condition_language_ger = 1;
    private $condition_language_en = 2;
    private $condition_visible_one = 1;
    private $condition_visible_two = 2;
    private $condition_is_zusatzartikel = 2;

    // Fehler
    public $error_not_integer = 20;
    public $error_not_valid_Fa_id = 21;
    public $error_not_valid_persons_number = 22;

    // Tabellen / Views
    private $viewProgrammeEinerStadt = null;
    private $viewProgrammsprachenEinesProgrammes = null;
    private $tabelleProgrammdetails = null;
    private $tabelleStadtbeschreibung = null;

    public function __construct(){
        /** @var viewProgrammeEinerStadt Application_Model_DbTable_viewProgrammeEinerStadt */
        $this->viewProgrammeEinerStadt = new Application_Model_DbTable_viewProgrammeEinerStadt();
        /** @var viewProgrammsprachenEinesProgrammes Application_Model_DbTable_viewProgrammsprachenEinesProgrammes */
        $this->viewProgrammsprachenEinesProgrammes = new Application_Model_DbTable_viewProgrammsprachenEinesProgrammes();
        /** @var tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var tabelleStadtbeschreibung Application_Model_DbTable_stadtbeschreibung */
        $this->tabelleStadtbeschreibung = new Application_Model_DbTable_stadtbeschreibung();
    }

    /**
     * Ermitteln der aktuellen Anzeigesprache
     */
    public function findLanguage()
    {
        $language = Zend_Registry::get('language');
        if($language == 'de')
            $this->selectLanguage = $this->condition_language_ger;
        else
            $this->selectLanguage = $this->condition_language_en;
    }

    /**
     * Ermitteln Stadt - Name
     *
     * @param $cityId
     * @return Front_Model_Programmstart
     * @throws nook_Exception
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;

        $db = Zend_Registry::get('front');
        $sql = "select AO_City from tbl_ao_city where AO_City_ID = '".$cityId."'";
        $this->_cityName = $db->fetchOne($sql);

        return $this;
    }

    /**
     * @param $filialeId
     * @return Front_Model_Programmstart
     */
    public function setFilialeId($filialeId)
    {
        $this->filialeId = $filialeId;

        return $this;
    }

    /**
     * @param $anzahlProgrammeProSeite
     * @return Front_Model_Programmstart
     */
    public function setAnzahlProgrammeProSeite($anzahlProgrammeProSeite)
    {
        $anzahlProgrammeProSeite = (int) $anzahlProgrammeProSeite;
        $this->anzahlProgrammeProSeite = $anzahlProgrammeProSeite;

        return $this;
    }

    /**
     * @param $programm
     * @return Front_Model_Programmstart
     * @throws nook_Exception
     */
    public function setSuchparameterProgramm($programm)
    {
        $this->programmsuche = $programm;

        return $this;
    }

    /**
     * @param $__fa_id
     * @return Front_Model_Programmstart
     * @throws nook_Exception
     */
    public function setFaId($__fa_id)
    {
        $control = new Zend_Validate_Int();
        if(!$control->isValid($__fa_id))
            throw new nook_Exception($this->error_not_valid_Fa_id);

        $this->_Fa_Id = $__fa_id;

        return $this;
    }

    /**
     * Ermittelt den Namen der Stadt
     * mit der cityId
     *
     * @return mixed
     */
    public function getStadtName(){
        $stadtName = nook_ToolStadt::getStadtNameMitStadtId($this->cityId);

        return $stadtName;
    }

    /**
     * @param $__persons
     * @return Front_Model_Programmstart
     * @throws nook_Exception
     */
    public function setPersons($__persons)
    {
        $control = new Zend_Validate_Int();
        if(!$control->isValid($__persons))
            throw new nook_Exception($this->error_not_valid_persons_number);

        $this->_persons = $__persons;

        return $this;
    }

    /**
     * @param $city
     * @return mixed
     */
    public function getAdditionalItems($city)
    {
        $db = Zend_Registry::get('front');
        $ort = nook_Tool::findCityNameById($city);

        // $start = ($this->actualPage - 1) * Zend_Registry::get('static')->items->programItemsPerPage;
        // $ende = Zend_Registry::get('static')->items->programItemsPerPage;

        $sql = "
        SELECT
            `tbl_programmbeschreibung`.`progname`
            , `tbl_programmbeschreibung`.`sprache`
            , `tbl_programmdetails`.`prio_noko`
            , `tbl_programmbeschreibung`.`txt`
            , `tbl_programmbeschreibung`.`noko_kurz`
            , `tbl_programmdetails`.`vk` AS `Verkaufspreis`
            , `tbl_programmdetails`.`mwst_satz` AS `Mehrwertsteuer`
            , `tbl_programmdetails`.`dauer` AS `Dauer`
            , `tbl_adressenfa`.`Ort`
            , `tbl_programmdetails`.`minPersons`
            , `tbl_programmdetails`.`maxPersons`
            , `tbl_programmdetails`.`dauer`
        FROM
            `tbl_programmbeschreibung`
            INNER JOIN `tbl_programmdetails`
                ON (`tbl_programmbeschreibung`.`Fa_Id` = `tbl_programmdetails`.`Fa_ID`)
            INNER JOIN `tbl_adressenfa`
                ON (`tbl_programmdetails`.`Fa_ID` = `tbl_adressenfa`.`Fa_ID`)
        WHERE (`tbl_programmdetails`.`permanent_zusatz` = '".$this->condition_is_zusatzartikel."'
            AND `tbl_programmbeschreibung`.`sprache` = '".$this->selectLanguage."'
            AND `tbl_adressenfa`.`Ort` = '".$ort."'
            AND `tbl_programmdetails`.`minPersons` <= '".$this->_persons."'
            AND `tbl_programmdetails`.`maxPersons` >= '".$this->_persons."'
            AND `tbl_programmdetails`.`sachleistung` = '".$this->condition_is_not_sachleistung."'
            AND `tbl_programmdetails`.`Fa_ID` <> '".$this->_Fa_Id."'
            AND (`tbl_programmdetails`.`prio_noko` = '".$this->condition_visible_one."' OR `tbl_programmdetails`.`prio_noko` = '".$this->condition_visible_two."'))
            order by `tbl_programmbeschreibung`.`wertigkeit` desc
            limit 0, 10";

        $additionalItems = $db->fetchAll($sql);

        if (is_array($additionalItems) and count($additionalItems) >= 1) {
            $additionalItems = $this->styleItems($additionalItems);
        }

        return $additionalItems;
    }

    /**
     * @param $programmkategorieId
     * @return Front_Model_Programmstart
     */
    public function setProgrammkategorieStadt($programmkategorieId){
        $programmkategorieId = (int) $programmkategorieId;
        $this->programmkategorieId = $programmkategorieId;

        return $this;
    }

    /**
     * setzt die aktuelle Seite
     *
     * @param $actualPage
     * @return Front_Model_Programmstart
     * @throws nook_Exception
     */
    public function setActualPages($actualPage)
    {
        $validator = new Zend_Validate_Int();
        if(!$validator->isValid($actualPage))
            throw new nook_Exception('kein Int Wert');

        // aktuelle Seite
        $this->actualPage = $actualPage;

        return $this;
    }

    /**
     * Ermittelt die Programme einer Stadt
     *
     * @return array
     */
    public function getCityEvents()
    {
        return $this->programmeCity;
    }

    /**
     * Steuert die Ermittlung der Programme in einer Stadt
     *
     * + berücksichtigt die Sichtbarkeiten in 'tbl_programmdetails'
     *
     * @return Front_Model_Programmstart
     * @throws Exception
     */
    public function steuerungErmittlungProgrammeInEinerStadt()
    {
        try{
            $events = array();

            // Suche mit einem Suchbegriff
            if(!is_null($this->programmsuche) and strlen($this->programmsuche) < 3)
                return $events;

            $start = ($this->actualPage - 1) * $this->anzahlProgrammeProSeite;
            $ende = $this->anzahlProgrammeProSeite;

            $toolProgrammeInEinerStadt = new nook_ToolProgrammeInEinerStadt();
            $toolProgrammeInEinerStadt
                ->setAnzeigeSpracheId($this->selectLanguage)
                ->setStart($start)
                ->setEnde($ende);

            // Suchbegriff
            if(!is_null($this->programmsuche))
                $toolProgrammeInEinerStadt->setSuchbegriff($this->programmsuche);

            // Programmkategorie ID
            if(!is_null($this->programmkategorieId))
                $toolProgrammeInEinerStadt->setProgrammkategorie($this->programmkategorieId);

            // City ID
            if(!is_null($this->cityId))
                $toolProgrammeInEinerStadt->setCityId($this->cityId);

            // Rolle des Benutzer
            $rolleBenutzer = nook_ToolBenutzerrolle::getRolleDesBenutzers();

            // Offlinebucher
            $statusOfflinebucher = nook_ToolBenutzerrolle::checkRolleOfflinebucher();
            if($statusOfflinebucher === true)
                $toolProgrammeInEinerStadt->setStatusOfflinebucher($statusOfflinebucher);

            $toolProgrammeInEinerStadt->setRolleBenutzer($rolleBenutzer);

            // wenn Filiale 'austria' erkannt wurde
            if(!is_null($this->filialeId))
                $toolProgrammeInEinerStadt->setFilialeId($this->filialeId);

            // Programme einer Stadt
            $events = $toolProgrammeInEinerStadt
                ->steuerungErmittlungProgrammeInEinerStadt()
                ->getProgrammeEinerStadt();

            // anpassen der Texte und Bilder der Programme
            array_walk($events, array($this,'_styleItems'));
            // kürzen langer Texte
            $events = $this->trimLongText($events);

            // entfernen der 'Tags' im Text
            array_walk($events,array($this,'_stripTags'));

            // hinzufügen der city ID
            $events = $this->addCityId($events);

            // bestimmt den Werbepreis der Programme
            $events = $this->bestimmeWerbepreisDerProgramme($events);

            $this->programmeCity = $events;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Entfernt aus dem Text
     * überflüsige Tags
     *
     * @param $__programm
     * @param $key
     */
    private function _stripTags(&$__programm, $key){
        $__programm['txt'] = strip_tags($__programm['txt'],'<p><br><u><b><a><ul><li>');

        return;
    }

    /**
     * Stylt die Ausgabe eines Programmes
     *
     * @param $__items
     * @return array
     */
    private function _styleItems(&$__event, $__key)
    {

        // Bild des Programmes
        $bildId = $this->_controlExistsProgramMiniImages($__event['id']);
        $bildPfad = nook_ToolProgrammbilder::getImagePathForProgram($bildId);
        $__event['image'] = $bildPfad;

        // Sprachen des Programmes
        $__event['programmsprachen'] = $this->_getLanguagesEvent($__event);

        return;
    }

    /**
     * Bestimmt den Werbepreis der Programme aus der
     * Tabelle 'tbl_programmdetails'
     *
     * @param $__programme
     * @return
     */
    private function bestimmeWerbepreisDerProgramme($__programme)
    {

        $cols = array(
            'werbepreis',
            'werbepreistyp'
        );

        for ($i = 0; $i < count($__programme); $i++) {
            $select = $this->tabelleProgrammdetails->select()->from($this->tabelleProgrammdetails, $cols)->where("id = ".$__programme[$i]['id']);
            $row = $this->tabelleProgrammdetails->fetchRow($select)->toArray();
            $__programme[$i]['werbepreis'] = $row['werbepreis'];
            if ($row['werbepreistyp'] == "1"){
                $__programme[$i]['werbepreistyp'] = "pro Person";
            }

            if ($row['werbepreistyp'] == "2"){
                $__programme[$i]['werbepreistyp'] = "pro Gruppe";
            }


        }

        return $__programme;
    }

    /**
     * Fügt den Programmen die City ID
     * hinzu.
     *
     * @param $__events
     * @return mixed
     */
    private function addCityId($__events)
    {
        for ($i=0; $i < count($__events); $i++) {
            $__events[$i]['cityId'] = $this->cityId;
        }

        return $__events;
    }

    /**
     * Passt einen langen Text an
     *
     * @param $__events
     * @return mixed
     */
    public function trimLongText($__events)
    {
        $__events = nook_Tool::trimLongText($__events);

        return $__events;
    }

    /**
     * Findet ein empfohlenes Programm
     * in der Stadt
     *
     * @param $cityId
     * @return string
     */
    public function getStadtbeschreibung($cityId)
    {
        if(is_null($cityId))
            return '';

        $sprache_id = nook_ToolSprache::ermittelnKennzifferSprache();
        $cols = array(
            'stadtbeschreibung'
        );

        $select = $this->tabelleStadtbeschreibung->select();
        $select
            ->from($this->tabelleStadtbeschreibung, $cols)
            ->where('sprache_id = '.$sprache_id)
            ->where('city_id = '.$cityId);

        $rows = $this->tabelleStadtbeschreibung->fetchAll($select)->toArray();

        if(!is_array($rows))
            return " ";
        else
            return $rows[0]['stadtbeschreibung'];
    }

    /**
     * Ermittelt die Programmsprachen
     * eines Programmes
     *
     * @param $__events
     * @return mixed
     */
    private function _getLanguagesEvent($__programm)
    {
        $programmsprachenEinesProgrammes = $this->viewProgrammsprachenEinesProgrammes
            ->find($__programm['id'])
            ->toArray();

        return $programmsprachenEinesProgrammes;
    }

    /**
     * Überprüft ob ein Minibild
     * für ein Programm vorhanden ist.
     *
     * @param $__programm
     * @return $bildId Int
     */
    private function _controlExistsProgramMiniImages($__programm)
    {
        $bildId = nook_ToolProgrammbilder::findImageFromProgram($__programm, 'midi');

        return $bildId;
    }

}

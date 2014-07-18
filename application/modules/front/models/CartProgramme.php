<?php
/**
 * Ermittelt die gebuchten Programme und
 * zeigt diese im Warenkorb an.
 *
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 02.04.12
 * Time: 17:30
 * To change this template use File | Settings | File Templates.
 */
 
class Front_Model_CartProgramme extends Pimple_Pimple
{
    // Fehler
    private $_error_keine_berechtigung_zum_loeschen = 630;
    private $_error_programm_konnte_nicht_geloescht_werden = 631;
    private $_error_status_nicht_geupdatet = 632;
    private $_error_anzahl_datensaetze_falsch = 633;

    protected $errorMessage = null;

    // Tabellen und Views
    private $_tabelleProgrammbuchung = null;
    private $_viewCartProgramme = null;
    private $_tabelleProgrammePreisvarianten = null;
    private $_tabelleProgrammePreisvariantenBeschreibung = null;
    private $_tabelleXmlBuchung = null;

    // Konditionen
    private $_condition_tag_in_sekunden = 86400;
    private $_condition_stornofrist_abgelaufen = 6;
    private $_condition_bereiche = array(
        '1' => 'tbl_programmbuchung',
        '6' => 'tbl_hotelbuchung',
        '7' => 'tbl_produktbuchung'
    );
    private $_condition_aktuelle_buchung = 0;
    private $_condition_keine_programmsprache_gebucht = 0;

    // Konditionen Error Message
    protected $conditionErrorMessagePreisvariante = 'Für ein Programm existiert ein Preis nicht mehr.';

    // Flags

    // Allgemein
    private $_db_front = null;
    private $_anzeigeSprache = null;
    private $_buchungen = array();
    private $_programme = array();

    /**
     *  Datenbankverbindung
     *
     */
    public function __construct(){

        // Datenbank
        $this->_db_front = Zend_Registry::get('front');

        // Anzeige Sprache
        $this->_anzeigeSprache = Zend_Registry::get('language');

        // Dependency Injection Controller
        $this->offsetSet('WarenkorbHilfe', function(){
            return new nook_WarenkorbHilfe();
        });

        // Manager aus 'Programmdetail'
        $this->offsetSet('ProgrammdetailOeffnungszeiten', function(){
            return new Front_Model_ProgrammdetailOeffnungszeitenmanager();
        });

        $this->offsetSet('ProgrammdetailSperrtage', function(){
            return new Front_Model_ProgrammdetailSperrtagemanager();
        });

        $this->offsetSet('ProgrammdetailTreffpunkte', function(){
            return new Front_Model_ProgrammdetailTreffpunktmanager();
        });

        /** @var $_tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array('db' => 'front'));
        /** @var $_viewCartProgramme  Application_Model_DbTable_viewWarenkorbCartProgramme */
        $this->_viewCartProgramme = new Application_Model_DbTable_viewWarenkorbCartProgramme(array('db' => 'front'));
        /** @var $_tabelleProgrammePreisvarianten Application_Model_DbTable_preise */
        $this->_tabelleProgrammePreisvarianten = new Application_Model_DbTable_preise(array('db' => 'front'));
        /** @var _tabelleProgrammePreisvariantenBeschreibung Application_Model_DbTable_preiseBeschreibung */
        $this->_tabelleProgrammePreisvariantenBeschreibung = new Application_Model_DbTable_preiseBeschreibung();
        /** @var $_tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung(array('db' => 'front'));

        return;
    }

    /**
     * Rückgabe Error Message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if(!is_null($this->errorMessage))
            return $this->errorMessage;
        else
            return;
    }

    /**
     * Findet die gebuchten Programme eines Kunden
     *
     * @return Front_Model_CartProgramme
     */
    public function findProgrammeEinesKunden()
    {
        $this->_findeBuchungsdatenDesKunden();
        $this->_findFixProgrammdaten();
        $this->findGebuchteSprachenDerProgramme();
        $this->findStornofristenProgramme();
        $this->_findPreisvariante();
        // $this->entferneProgrammeOhnePreisvariante();

            // ->_kontrolliereStatusProgramm()
        $this->_berechneGesamtpreisEinesProgrammes();
        $this->_formatFixprogrammdaten();
            // ->_findOeffnungszeiten()
            // ->_findTreffpunkt();
        $this->_kontrolliereVorhandeneProgrammkapazitaet();

        return $this;
    }

    /**
     * Ermittelt die Stornofristen der Programme
     */
    protected function findStornofristenProgramme()
    {
        $toolErmittlungAbweichendeStornofristenKosten = new nook_ToolErmittlungAbweichendeStornofristenKosten();

        for($i=0; $i < count($this->_programme); $i++){
            $stornoFristenProgramm = $toolErmittlungAbweichendeStornofristenKosten
                ->setProgrammId($this->_programme[$i]['programmdetails_id'])
                ->ermittleStornofristenProgramm()
                ->getStornofristen();

            $this->_programme[$i]['tageStornofristen'] = $stornoFristenProgramm;
        }

        return $stornoFristenProgramm;
    }

    /**
     * ermittelt die gebuchte Sprache eines Programmes
     *
     * + hat das Programm Sprachvarianten ?
     * + wurde eine Sprache für das Programm gebucht ?
     *
     */
    private function findGebuchteSprachenDerProgramme()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammdetailsProgsprachen'] = function()
        {
            return new Application_Model_DbTable_programmedetailsProgsprachen();
        };

        $pimple['tabelleProgSprache'] = function()
        {
            return new Application_Model_DbTable_progSprache();
        };

        $pimple['tabelleProgrammbuchung'] = function()
        {
            return new Application_Model_DbTable_programmbuchung();
        };

        $modelProgrammSprachen = new Front_Model_ProgrammSprache($pimple);

        $buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $modelProgrammSprachen
            ->setBuchungsnummerId($buchungsnummerId)
            ->setZaehler($this->_condition_aktuelle_buchung);

        for($i=0; $i < count($this->_programme); $i++){
            $programmsprachen = $modelProgrammSprachen
                ->setProgrammId($this->_programme[$i]['programmdetails_id'])
                ->setPreisvarianteId($this->_programme[$i]['tbl_programme_preisvarianten_id'])
                ->setZeit($this->_programme[$i]['zeit'])
                ->setDatum($this->_programme[$i]['datum'])
                ->steuernErmittelnProgrammsprachen()
                ->getProgrammsprachen();

            // wenn eine Programmsprache gebucht wurde
            if(is_array($programmsprachen) and count($programmsprachen) > 0){
                $gebuchteProgrammspracheId = $modelProgrammSprachen->steuerungErmittelnGebuchteProgrammsprache();
            }
            // keine Programmsprachen vorhanden
            else
                $gebuchteProgrammspracheId = $this->_condition_keine_programmsprache_gebucht;

            $this->_programme[$i]['gebuchteProgrammsprache'] = $gebuchteProgrammspracheId;
        }
        return;
    }

    /**
     * Kontrolliert die Anzahl der vorhandenen Programme, setzt Status des Programmes auf
     *
     * + 'ausgebucht' = 6 , wenn keine Kapazität vorhanden
     */
    private function _kontrolliereVorhandeneProgrammkapazitaet()
    {
        foreach($this->_programme as $key => $programm){

            // kombination von Datum und Zeit
            $programm = $this->_kombinationDatumZeit($programm);

            $warenkorbProgrammeKapazitaet = new Front_Model_WarenkorbProgrammeKapazitaet();
            $warenkorbProgrammeKapazitaet
                ->setProgrammDaten($programm)
                ->checkKapazitaet();
        }
    }

    /**
     * Kombiniert Datum und Zeit
     *
     * @param array $programm
     * @return array
     */
    private function _kombinationDatumZeit (array $programm)
    {
        if(array_key_exists('datum', $programm)) {

            $anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $programm[ 'datum' ] = nook_ToolDatum::konvertDatumInDate(
                $anzeigeSprache,
                $programm[ 'datum' ]
            );

            if(array_key_exists('zeit', $programm))
                $programm['datum'] = $programm['datum']." ".$programm['zeit'];

            return $programm;
        }

        return $programm;
    }

    /**
     * Setzen der Buchungsnummern eines Kunden
     *
     * @param array $__buchungen
     * @return $this
     */
    public function setBuchungsnummernEinesKunden(array $__buchungen){
        // Buchungsnummern des Kunden
        $this->_buchungen = $__buchungen;

        return $this;
    }

    /**
     * Gibt die gebuchten Programme des Kunden zurück
     *
     * @return array
     */
    public function getGebuchteProgramme(){

        return $this->_programme;
    }

    /**
     * Gibt die gebuchten Programme nach Stadt 'nested' zurück
     *
     * + bestimmt die Städte der Programme
     * + ordnet die Programme den Städten zu
     *
     * @param bool $flagNurAktiveProgramme
     * @return mixed
     */
    public function getGebuchteProgrammeNested($flagNurAktiveProgramme = false){
        $gebuchteProgrammeNested = $this->bestimmeStaedteDerProgramme($flagNurAktiveProgramme);
        $gebuchteProgrammeNested = $this->zuordnenDerProgrammeZuDenStaedten($gebuchteProgrammeNested, $flagNurAktiveProgramme);

        return $gebuchteProgrammeNested;
    }

    /**
     * Ordnet die Programme zu den Staedten zu.
     *
     * + bei Bedarf werden nur die 'aktiven' Programme übernommen
     * + Jede Stadt hat einen Knoten 'programme'
     * + diesen Knoten werden die Programme zugeordnet
     *
     * @param $vorhandeneStaedte
     * @param $flagNurAktiveProgramme
     * @return array
     */
    private function zuordnenDerProgrammeZuDenStaedten($vorhandeneStaedte, $flagNurAktiveProgramme){
        for($i=0; $i < count($this->_programme); $i++){
            $programm = $this->_programme[$i];

            for($j=0; $j < count($vorhandeneStaedte); $j++){
                if($this->_programme[$i]['city_id'] == $vorhandeneStaedte[$j]['city_id'])
                    // nur aktive Programme eines Warenkorbes
                    if($flagNurAktiveProgramme === true and $programm['anzahl'] > 0)
                        $vorhandeneStaedte[$j]['programme'][] = $programm;
                    // alle Programme eines Warenkorbes
                    elseif($flagNurAktiveProgramme === false)
                        $vorhandeneStaedte[$j]['programme'][] = $programm;
            }
        }

        return $vorhandeneStaedte;
    }

    /**
     * Ermittelt die Städte der gebuchten Programme.
     *
     * + Bei Bedarf werden nur Städte übernommen zu denen 'aktive' Programme existieren
     * + $flagNurAktiveProgramme = true, es werden nur die Städte gesucht zu denen 'aktive' Programme vorhanden sind
     *
     * @return array
     */
    private  function bestimmeStaedteDerProgramme($flagNurAktiveProgramme = false){
        $gebuchteProgrammeNested = array();
        $j = 0;

        for($i=0; $i < count($this->_programme); $i++){
            $programm = $this->_programme[$i];

            if($flagNurAktiveProgramme === true and $programm['anzahl'] > 0)
                $gebuchteProgrammeNested = $this->zuordnenStaedteZumProgramm($programm, $gebuchteProgrammeNested, $j);
            elseif($flagNurAktiveProgramme === false)
                $gebuchteProgrammeNested = $this->zuordnenStaedteZumProgramm($programm, $gebuchteProgrammeNested, $j);
        }

        return $gebuchteProgrammeNested;
    }

    /**
     * Bestimmt die Staedte der Programme
     *
     * @param $programm
     * @param $gebuchteProgrammeNested
     * @return array
     */
    private function zuordnenStaedteZumProgramm(array $programm, array $gebuchteProgrammeNested, &$j)
    {
        // erste Stadt
        if(count($gebuchteProgrammeNested) == 0){
            $gebuchteProgrammeNested[$j]['city_id'] = $programm['city_id'];
            $gebuchteProgrammeNested[$j]['stadtname'] = $programm['stadtname'];
            $gebuchteProgrammeNested[$j]['programme'] = array();
        }

        // weitere Städte
        foreach($gebuchteProgrammeNested as $stadt){

            if($stadt['city_id'] != $programm['city_id']){
                $gebuchteProgrammeNested[$j]['city_id'] = $programm['city_id'];
                $gebuchteProgrammeNested[$j]['stadtname'] = $programm['stadtname'];
                $gebuchteProgrammeNested[$j]['programme'] = array();
            }
        }

        $j++;

        return $gebuchteProgrammeNested;
    }

    /**
     * Berechnet den Gesamtpreis entsprechend der Anzahl
     * der Preisvariante eines Programmes
     *
     * @return Front_Model_CartProgramme
     */
    private function _berechneGesamtpreisEinesProgrammes(){
        for($i = 0; $i < count($this->_programme); $i++){
            $gesamtpreis = $this->_programme[$i]['preisvariantePreis'] * $this->_programme[$i]['anzahl'];
            $this->_programme[$i]['preisvarianteGesamtpreis'] = number_format($gesamtpreis,2,',','');
        }

        return $this;
    }

    /**
     * Findet den Preis der Preisvariante des gebuchten Programmes. Findet die Bezeichnung der Preisvariante entsprechend der Anzeigesprache.
     *
     * + ermittelt den Preis der Preisvariante
     *
     * @return Front_Model_CartProgramme
     */
    private function _findPreisvariante(){

        /** @var $tabellePreisvarianten Application_Model_DbTable_preise */
        $tabellePreisvarianten = $this->_tabelleProgrammePreisvarianten;

        $kennungAnzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        for($i = 0; $i < count($this->_programme); $i++){
            // Preis der Preisvariante
            $preis = $tabellePreisvarianten->find($this->_programme[$i]['tbl_programme_preisvarianten_id'])->toArray();

            // Information
            if(count($preis) < 1){
                $nookExceptionInformationRegistration = nook_ExceptionInformationRegistration::registerError($this->_programme[$i]['tbl_programme_preisvarianten_id'].' für diese Preisvariante existiert kein Preis');
                $this->errorMessage = $this->conditionErrorMessagePreisvariante;

                continue;
            }

            // Fehler
            if(count($preis) > 1)
                throw new nook_Exception($this->_programme[$i]['tbl_programme_preisvarianten_id'].' für diese Preisvariante existiert mehr als ein Preis',1);

                $this->_programme[$i]['preisvariantePreis'] = $preis[0]['verkaufspreis'];

            // Beschreibung der Preisvariante
            $select = $this->_tabelleProgrammePreisvariantenBeschreibung->select();
            $select
                ->where('preise_id = '.$preis[0]['id'])
                ->where('sprachen_id = '.$kennungAnzeigesprache);

            $rows = $this->_tabelleProgrammePreisvariantenBeschreibung->fetchAll($select)->toArray();

            // speichert im fehlerfall die Query
            if(count($rows) <> 1){
                $query = $select->__toString();
                throw new nook_Exception($query);
            }

            $this->_programme[$i]['preisvarianteName'] = $rows[0]['preisvariante'];
        }

        return $this;
    }

    /**
     * Entfernt Programme die keine Preisvriante haben
     *
     */
    protected function entferneProgrammeOhnePreisvariante()
    {
        for($i=0; $i < count($this->_programme); $i++)
        {
            $programm = $this->_programme[$i];

            if($programm['preisvariantePreis'] == 0)
                unset($this->_programme[$i]);
        }

        $this->_programme = array_merge($this->_programme);

        return;
    }

    /**
     * Der Status der gefundenen Programme wird überprüft.
     * Programme deren Status = 2 = 'vorgemerkt' werden
     * auf 6 gesetzt wenn die Stornofrist des Programmes erreicht wurde,
     * XMl Buchungsdatensatz wird auf Status 6 gesetzt.
     *
     * @throws nook_Exception
     * @return Front_Model_CartProgramme
     */
    private function _kontrolliereStatusProgramm(){

        // ermitteln momentanes Datum
        $now = time();

        for($i=0; $i<count($this->_programme); $i++){
        
            // ermitteln Datum der Programmdurchführung
            $unixDurchfuehrung = nook_ToolZeiten::erstelleUnixAusDate($this->_programme[$i]['datum']);

            // ermitteln Stornofrist des Programmes
            $stornofrist = $this->_condition_tag_in_sekunden * $this->_programme[$i]['stornofrist'];

            $differenzDatum = $unixDurchfuehrung - $now;

            // wenn Stornierung durch System, dann verändern Status und updaten Datensatz
            if( ($this->_programme[$i]['status'] < 3) and ($differenzDatum < $stornofrist) ){
                $this->_programme[$i]['status'] = $this->_condition_stornofrist_abgelaufen;

                $update = array();
                $update['status'] = $this->_condition_stornofrist_abgelaufen;

                /** @var $db Zend_Db_Adapter_Mysqli */
                $db = $this->_db_front;
                $db->beginTransaction();

                $update1 = $this->_tabelleProgrammbuchung->update($update, "id = ".$this->_programme[$i]['id']);

                $where = 'bereich = 1 and buchungstabelle_id = '.$this->_programme[$i]['id'];
                $update2 = $this->_tabelleXmlBuchung->update($update, $where);

                if(!empty($update1) and !empty($update2))
                    $db->commit();
                else{
                    $db->rollBack();
                    throw new nook_Exception($this->_error_status_nicht_geupdatet);
                }
            }
        }

        return $this;
    }

    private function _formatFixprogrammdaten(){
        $kennzifferAnzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
        if($kennzifferAnzeigeSprache == 1)
            $anzeigeSprache = 'de';
        else
            $anzeigeSprache = 'en';

        for($i=0; $i<count($this->_programme); $i++){

            // Stadtname
            $this->_programme[$i]['stadtname'] = nook_Tool::findCityNameById($this->_programme[$i]['city_id']);

            // Buchungsdatum
            $this->_programme[$i]['buchungsdatum'] = nook_ToolZeiten::generiereDatumNachAnzeigesprache($this->_programme[$i]['buchungsdatum'], $kennzifferAnzeigeSprache);

            // Programmbild
            $bildId = nook_ToolProgrammbilder::findImageFromProgram($this->_programme[$i]['programmdetails_id']);
            $this->_programme[$i]['bild'] = nook_ToolProgrammbilder::getImagePathForProgram($bildId);

            // Hinweis entsprechend der Sprache
            if($kennzifferAnzeigeSprache == 1)
                $this->_programme[$i]['hinweis'] = $this->_programme[$i]['hinweisDeutsch'];
            else
                $this->_programme[$i]['hinweis'] = $this->_programme[$i]['hinweisEnglisch'];

            // Durchführungsdatum
            $this->_programme[$i]['datum'] = nook_Tool::buildDateForLanguage($this->_programme[$i]['datum'], $anzeigeSprache);
        }

        // kürzen Texte
        $this->_programme = nook_Tool::trimLongText($this->_programme);

        return $this;
    }

    /**
     * Findet die Buchungsdaten der Programme
     *
     * @return Front_Model_CartProgramme
     */
    private function _findeBuchungsdatenDesKunden()
    {
        $cols = array(
            'id',
            'buchungsnummer_id',
            'programmdetails_id',
            'tbl_programme_preisvarianten_id',
            'anzahl',
            'datum',
            'status',
            'zeit'
        );

        foreach($this->_buchungen as $key => $buchung){

            $select = $this->_tabelleProgrammbuchung->select();
            $select
                ->from($this->_tabelleProgrammbuchung, $cols)
                ->where("buchungsnummer_id = ".$buchung['id'])
                ->where('zaehler = '.$this->_condition_aktuelle_buchung)
                ->order('datum')
                ->order('zeit');

            $programmBuchungenEinerSession = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();

            // Buchungsdatum
            for($i=0; $i<count($programmBuchungenEinerSession); $i++){
                $programmBuchungenEinerSession[$i]['buchungsdatum'] = $buchung['buchungsdatum'];
                $this->_programme[] = $programmBuchungenEinerSession[$i];
            }
        }

        return $this;
    }

    /**
     * Findet die fixen Programmdaten in Abhängigkeit der
     * anzuzeigenden Sprache.
     *
     * @return Front_Model_CartProgramme
     */
    private function _findFixProgrammdaten(){
        $kennzifferAnzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();

        $cols = array(
            'progname',
            'txt',
            'treffpunktText',
            'oepnv',
            'oeffnungszeiten',
            'city_id',
            new Zend_Db_Expr('adressen_id as company_id'),
            'hinweisDeutsch',
            'hinweisEnglisch',
            'adressen_id',
            'stornofrist',
            'buchungstyp',
            'typOeffnungszeiten'
        );

        foreach($this->_programme as $key => $programm){

            $select = $this->_viewCartProgramme->select();
            $select
                ->from($this->_viewCartProgramme, $cols)
                ->where('sprache = '.$kennzifferAnzeigeSprache)
                ->where('programmdetails_id = '.$programm['programmdetails_id']);

            $query = $select->__toString();

            $fixInformationProgramm = $this->_viewCartProgramme->fetchRow($select)->toArray();
            $this->_programme[$key] = array_merge($programm, $fixInformationProgramm);
        }

        return $this;
    }

    /**
     * Kontrolle ob der User das
     * Löschen eines Programmes aus dem Warenkorb.
     *
     * @throws nook_Exception
     * @param $__buchungsId ID des Eintrages in der Buchungstabelle
     * @param $__bereichId ID des Bereiches
     * @return
     */
    public function deleteItemWarenkorb($__bereichId, $__idBuchungstabelle){

        // Kontrolle loeschen Programm
        /** @var $kontrolleLoeschen nook_WarenkorbHilfe */
        $kontrolleLoeschen = $this->offsetGet('WarenkorbHilfe');
        $kontrolleLoeschen->kontrolleLoeschenBuchung($__bereichId, $__idBuchungstabelle);

        // löschen der Buchung
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        
        $db->beginTransaction();
        $sql = "delete from ". $this->_condition_bereiche[$__bereichId] ." where id = ".$__idBuchungstabelle;
        $kontrolle1 = $db->query($sql);
        $sql = "delete from tbl_xml_buchung where buchungstabelle_id = ".$__idBuchungstabelle." and bereich = ".$__bereichId;
        $kontrolle2 = $db->query($sql);
        if($kontrolle1 and $kontrolle2)
            $db->commit();
        else{
            $db->rollBack();
            throw new nook_Exception($this->_error_programm_konnte_nicht_geloescht_werden);
        }
        

        return;
    }

    /**
     * Kontrolliert ob dieses Programm gelöscht werden darf.
     * Kontrolliert ob die User ID oder die Session ID das Programm
     * löschen darf.
     *
     * @throws nook_Exception
     * @param $__buchungsId
     * @return
     */
    private function _kontrolleLoeschenBuchung($__bereichId, $__idBuchungstabelle){ 
        $auth = new Zend_Session_Namespace('Auth');
        $authItems = $auth->getIterator();

        $sql = "
            SELECT
                `tbl_buchungsnummer`.`kunden_id` as kundenId
              , `status`
              , `tbl_buchungsnummer`.`session_id` as sessionId
            FROM
                `tbl_buchungsnummer`
                INNER JOIN `".$this->_condition_bereiche[$__bereichId]."`
                    ON (`tbl_buchungsnummer`.`id` = `".$this->_condition_bereiche[$__bereichId]."`.`buchungsnummer_id`)
            WHERE (`".$this->_condition_bereiche[$__bereichId]."`.`id` = ". $__idBuchungstabelle .")";

        $kontrolleLoeschen = $this->_db_front->fetchRow($sql);

        if(!empty($authItems['userId'])){
            if($authItems['userId'] != $kontrolleLoeschen['kundenId'])
                throw new nook_Exception($this->_error_keine_berechtigung_zum_loeschen);
        }
        else{
            $session = Zend_Session::getId();
            if($session != $kontrolleLoeschen['sessionId'])
                throw new nook_Exception($this->_error_keine_berechtigung_zum_loeschen);
        }

        return $kontrolleLoeschen['status'];
    }
}

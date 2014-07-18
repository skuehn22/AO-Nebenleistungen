<?php
/**
* Darstellung der allgemeinen Information eines Programmes
*
* @author Stephan.Krauss
* @date 26.09.2013
* @file ProgrammdetailController.php
* @package front
* @subpackage controller
*/
class Front_ProgrammdetailController extends Zend_Controller_Action
{
    // Konditionen
    private $condition_typ_oeffnungszeit_liste_startzeiten = 3;

    // Flags
    private $flagEditProgrammbuchung = false;

    private $_programId;
    private $_showBlock = false;
    private $_realParams = array();
    private $datenBestandsbuchung = null;
    private $pimple = null;

    private $requestUrl = null;

    // Konditionen
    protected $condition_bereich_programme = 1;
    protected $condition_flag_ist_rabattProgramm = 2;
    protected $condition_flag_ist_kein_rabattProgramm = 1;


    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();

        $this->pimple = $this->getInvokeArg('bootstrap')->getResource('Container');
    }

    /*** Bereich  Erststart ***/

    /**
     * stellt Seite zum Erststart dar
     *
     * @return void
     */
    public function indexAction()
    {

        $params = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Service der Anzeige
            $modelProgrammdetail = new Front_Model_Programmdetail();
            $this->serviceAnzeige($params, $raintpl, $modelProgrammdetail);

            // schalten der Blöcke wenn Bestandsbuchung
            if ($this->datenBestandsbuchung) {
                $raintpl = $this->setBestandsbuchung($raintpl);
            }

            // Anzeige Buchungsdatum im Datepicker
            $raintpl = $this->buchungsDatumDatepicker($raintpl);

            // ermitteln Buchungstyp eines Programmes, Online / Offline
            $raintpl = $this->ermittelnBuchungstyp($params, $raintpl);

            // Programmdetails - Basisdaten 'tbl_programmbeschreibung'
            $modelProgrammdetail->setProgramId($params['programId']);
            $programDetails = $modelProgrammdetail->getProgramDetails();
            $raintpl->assign('programmdetails', $programDetails);

            // Buchungsdetails
            $bookingDetails = $modelProgrammdetail->getBookingDates();

            // Preis Buchungspauschale
            $static = Zend_Registry::get('static');
            $bookingDetails['preisBuchungspauschale'] = $static->buchungspauschale->preis;

            // übernahme Booking Details
            $raintpl->assign('bookingdetails', $bookingDetails);

            // Preisvarianten eines Programmes
            $modelPreisvarianten = new Front_Model_ProgrammdetailProgrammvarianten();

            // gebuchte Zeit eines Programmes
            $raintpl = $this->gebuchteZeit($raintpl);

            // setzen der ID Preisvariante eines vorhandenen Programmes
            if ($this->datenBestandsbuchung) {
                $modelPreisvarianten->setBestandsbuchungProgrammId($this->datenBestandsbuchung['preisvariante']);
            }

            // Auswahl der gebuchten Preisvariante
            if ($this->datenBestandsbuchung) {
                $raintpl->assign('gebuchtePreisvariante', $this->datenBestandsbuchung['preisvariante']);
            } else {
                $raintpl->assign('gebuchtePreisvariante', 0);
            }

            // Preisvarianten
            $modelPreisvarianten->getPreisvariantenEinesProgrammes($params['programId']);
            $preisvarianten = $modelPreisvarianten->getPreiseDerProgrammvarianten();
            $raintpl->assign('preiseProgrammVarianten', $preisvarianten);

            // Preis der ersten Programmvariante
            $raintpl->assign('startpreis', $modelPreisvarianten->getStartpreis());

            // Tabelle der Preisvarianten
            $bestellTabelle = $modelPreisvarianten->getBestellTabelle();
            $raintpl->assign('programmvarianten', $bestellTabelle);

            // Anzahl der Programmvarianten eines Programmes
            $raintpl->assign('anzahlPreisvarianten', $modelPreisvarianten->getAnzahlPreisvarianten());

            // Stornofristen
            $stornofristen = $this->ermittelnStornofristenProgramm($params['programId']);
            $raintpl->assign('stornofristen', $stornofristen);

            // Sperrtage eines Programmes
            $raintpl->assign('sperrtage', $modelProgrammdetail->getSperrtageEinesProgrammes($params['programId']));

            // Oeffnungszeiten
            $oeffnungszeiten = $this->getOeffnungszeiten($params['programId']);
            $raintpl->assign('oeffnungszeiten', $oeffnungszeiten);

            // Geschäftstage
            $geschaeftstage = $this->getGeschaeftstage($params['programId']);
            $raintpl->assign('geschaeftstage', $geschaeftstage);

            // Zeitmamanger
            $raintpl = $this->_zeitmanager($params, $raintpl);

            // Vertragspartner
            $raintpl = $this->vertragspartner($params, $raintpl);

            // Programmsprachen Manager
            $programmsprachen = $this->getProgrammsprachen($params['programId']);
            $raintpl->assign('sprachenmanager', $programmsprachen);

            // Informationsblock bereits gebuchte Programme
            $raintpl = $this->blockBereitsGebuchteProgramme($raintpl);

            // Typ der Programmzeiten eines Programmes
            $typProgrammzeitenEinesProgrammes = $this->bestimmeTypProgrammzeiten($params['programId']);
            $raintpl->assign('typProgrammzeiten', $typProgrammzeitenEinesProgrammes);

            // Block Eingabe Einkaufs und Verkaufspreis, Anzeige des Blockes
            $raintpl = $this->anzeigeBlockRabatt($raintpl, $params['programId']);

            // Template
            $this->view->content = $raintpl->draw("Front_Programmdetail_Index", true);

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Anzeige des Rabatt Block
     *
     * + ist das Programm ein Rabatt - Programm entsprechend 'static.ini'
     * + ist der Benutzer mindestens ein Offlinebucher
     *
     * @param $raintpl
     * @return mixed
     */
    protected function anzeigeBlockRabatt($raintpl, $programId)
    {
        $staticWerte = nook_ToolStatic::getStaticWerte();
        $rabattProgrammId = $staticWerte['rabatt']['programmId'];

        $auth = nook_ToolSession::holeVariablenNamespaceSession('Auth');
        $rolleDesBenutzer = $auth['role_id'];
        $superuser = $auth['superuser'];

        if( ($programId == $rabattProgrammId) and ( ($rolleDesBenutzer > 8) or ($superuser == 2 ) ) )
            $raintpl->assign('flagRabattProgramm', $this->condition_flag_ist_rabattProgramm);
        else
            $raintpl->assign('flagRabattProgramm', $this->condition_flag_ist_kein_rabattProgramm);

        return $raintpl;
    }

    /**
     * Ermittelt den Typ der Öffnungszeiten eines Programmes
     *
     * @param $programId
     * @return int
     */
    private function bestimmeTypProgrammzeiten($programId)
    {
        $frontModelProgrammzeiten = new Front_Model_Programmzeiten();
        $typProgrammzeitenProgramm = $frontModelProgrammzeiten
            ->setProgrammId($programId)
            ->steuerungErmittlungProgrammzeitenTyp()
            ->getTypOeffnungszeiten();

        return $typProgrammzeitenProgramm;
    }

    /**
     * Darstellen des Blockes 'bereits gebuchte Programme'
     *
     * + Informationsblock breits gebuchter Programme
     * + Wird nur bei einem bereits gebuchten Programm angezeigt
     *
     * @param $raintpl
     * @return mixed
     */
    private function blockBereitsGebuchteProgramme($raintpl)
    {

        if($this->flagEditProgrammbuchung === false)
            return $raintpl;

        $this->pimple['tabelleTextbausteine'] = function ($c) {
            return new Application_Model_DbTable_textbausteine();
        };

        $toolBereitsGebuchteProgramme = new nook_ToolStandardtexte();
        $blockInformationBestandsbuchungProgramme = $toolBereitsGebuchteProgramme
            ->setPimple($this->pimple)
            ->setBlockname('bestandsbuchung_programme')
            ->steuerungErmittelnText()
            ->getText();

        $raintpl->assign('blockInformationBestandsbuchungProgramme', $blockInformationBestandsbuchungProgramme);

        return $raintpl;
    }

    /**
     * Gibt die vorhandenen Programmsprachen eines Programmes zurück
     *
     * + bei einer Bestandsbuchung wird die gewählte Sprache markiert
     * + Gibt die Programmsprachen eines Programmes zurück
     *
     * @param $programmId
     */
    private function getProgrammsprachen($programmId, $sprachWahlId = false)
    {
        $pimple = $this->pimple;

        $pimple['tabelleProgrammdetailsProgsprachen'] = function ($c) {
            return new Application_Model_DbTable_programmedetailsProgsprachen();
        };

        $pimple['tabelleProgSprache'] = function ($c) {
            return new Application_Model_DbTable_progSprache();
        };

        $modelProgrammsprachen = new Front_Model_ProgrammSprache($pimple);
        $modelProgrammsprachen->setProgrammId($programmId);

        if (!empty($sprachWahlId)) {
            $modelProgrammsprachen->setSprachwahlId($sprachWahlId);
        }

        $programmsprachen = $modelProgrammsprachen
            ->steuernErmittelnProgrammsprachen()
            ->getProgrammsprachen();

        return $programmsprachen;
    }

    /**
     * Übernimmt die Zeit eines gebuchten Programmes.
     * Darstellung des Zeitmanagers
     * + wenn neue Buchung, dann 'zeitmanagerSelect' = 0
     *
     * @param $raintpl
     */
    private function gebuchteZeit($raintpl)
    {
        // neue Programmbuchung
        if (!$this->datenBestandsbuchung) {
            $raintpl->assign('zeitmanagerSelect', 0);
            $raintpl->assign('zeitmanagerStunde', 0);
            $raintpl->assign('zeitmanagerMinute', '00');

        } // bereits gebuchtes Programm
        else {
            $raintpl->assign('zeitmanagerSelect', $this->datenBestandsbuchung['zeitmanagerSelect']);
            $raintpl->assign('zeitmanagerStunde', $this->datenBestandsbuchung['zeitmanagerStunde']);
            $raintpl->assign('zeitmanagerMinute', $this->datenBestandsbuchung['zeitmanagerMinute']);
        }

        return $raintpl;
    }

    /**
     * Ermittelt die Adresse des Vertragspartners
     *
     * @param $params
     * @param $raintpl
     * @return mixed
     */
    protected function vertragspartner($params, $raintpl)
    {
        $frontModelVertragspartner = new Front_Model_Vertragspartner();
        $adresseVertragspartner = $frontModelVertragspartner
            ->setBereich($this->condition_bereich_programme)
            ->setProgrammId($params['programId'])
            ->steuerungErmittlungAdresseVertragspartner()
            ->getAdresse();

        $raintpl->assign('vertragspartner', $adresseVertragspartner);

        return $raintpl;
    }

    /**
     * Ermittlung der Stornofristen eines Programmes
     *
     * @param $programId
     * @return array
     */
    protected function ermittelnStornofristenProgramm($programId)
    {
        $toolErmittlungAbweichendeStornofristenKosten = new nook_ToolErmittlungAbweichendeStornofristenKosten();
        $stornoFristen = $toolErmittlungAbweichendeStornofristenKosten
            ->setProgrammId($programId)
            ->ermittleStornofristenProgramm()
            ->getStornofristen();

        return $stornoFristen;
    }


    /**
     * Ermittelt die Programmzeit und aktiviert den Zeitmanager.
     *
     * + ermitteln Typ Zeitmanager
     * + ermitteln der Liste der Startzeiten eines Programmes
     *
     * @param $params
     * @param $raintpl
     * @return object raintpl
     */
    private function _zeitmanager($params, $raintpl)
    {
        $modelProgrammdetailZeiten = new Admin_Model_Programmzeiten();
        $typOeffnungszeiten = $modelProgrammdetailZeiten
            ->setProgrammdetailsId($params['programId'])
            ->steuerungErmittlungTypProgrammzeiten()
            ->getTypProgrammzeiten();

        $raintpl->assign('typOeffnungszeit', $typOeffnungszeiten);

        if($typOeffnungszeiten == $this->condition_typ_oeffnungszeit_liste_startzeiten){
            $programmzeit = $modelProgrammdetailZeiten
                ->getProgrammzeitSelectBox();

            $raintpl->assign('zeitmanager', $programmzeit);
        }

        return $raintpl;
    }

    /**
     * Findet die Tageskapazität eines Programmes
     *
     */
    public function findeTageskapazitaetAction()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Kapazität Manager
            $frontModelKapazitaetProgramm = new Front_Model_ProgrammdetailKapazitaetManager();

            // wandelt bei Bedarf deutsches Datum in englisches Datum
            $datum = $frontModelKapazitaetProgramm->mapDatum($this->realParams['datum']);

            $programmKapazitaet = $frontModelKapazitaetProgramm
                ->setProgrammId($this->realParams['programmId'])
                ->setDatum($datum)
                ->setAnzahlProgrammbuchungen($this->realParams['anzahlProgrammbuchungen'])
                ->berechneProgrammkapazitaet()
                ->getProgrammKapazitaet();

            echo $programmKapazitaet;
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /*** speichern der Programmdaten ***/

    /**
     * Übernimmt die Daten der Programmbuchung und speichert diese ab
     *
     * + bestimmt die Anzahl der Programmsprachen, wenn keine Programmsprachen vorhanden dann sprache = 0
     *
     * @return void
     */
    public function insertProgrammbuchungAction()
    {
        $request = $this->getRequest();
        $buchungsdaten = $request->getParams();

        unset($buchungsdaten['module']);
        unset($buchungsdaten['controller']);
        unset($buchungsdaten['action']);
        unset($buchungsdaten['warenkorb']);

        try {
            // Veränderung EK und VK einer Preisvariante
            if( (array_key_exists('einkaufspreis', $buchungsdaten)) and (array_key_exists('verkaufspreis', $buchungsdaten)) )
                $buchungsdaten = $this->aendernPreiseRabattProgramm($buchungsdaten);


            // Bestimmung Anzahl Programmsprachen
            $pimple = new Pimple_Pimple();

            $frontModelProgrammSprache = new Front_Model_ProgrammSprache($pimple);
            $anzahlProgrammsprachen = $frontModelProgrammSprache
                ->setProgrammId($buchungsdaten['ProgrammId'])
                ->steuernErmittelnProgrammsprachen()
                ->getAnzahlProgrammsprachen();

            // wenn keine Programmsprache gewählt wurde
            if($anzahlProgrammsprachen == 0)
                $buchungsdaten['sprache'] = 0;

            $modelProgrammdetail = new Front_Model_Programmdetail();

            // Kontrolle Programmvarianten
            $modelProgrammdetail->kontrolleProgrammvarianten($buchungsdaten);

            // speichern Buchungsdaten / Start
            $modelProgrammdetail->startSpeichernBuchungsdatenProgramm($buchungsdaten);

            /** @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = $this->_helper->getHelper('Redirector');
            $redirector->gotoUrl('/front/warenkorb/index/');

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Veraenderung des Einkauf EK und Verkaufspreis VK des allgemeinen Rabattprogrammes
     *
     * @param array $buchungsdaten
     * @return array
     */
    protected function aendernPreiseRabattProgramm(array $buchungsdaten)
    {
        $buchungsdaten['einkaufspreis'] = str_replace(',','.',$buchungsdaten['einkaufspreis']);
        $buchungsdaten['einkaufspreis'] = floatval($buchungsdaten['einkaufspreis']);

        $buchungsdaten['verkaufspreis'] = str_replace(',','.',$buchungsdaten['verkaufspreis']);
        $buchungsdaten['verkaufspreis'] = floatval($buchungsdaten['verkaufspreis']);

        $pimple = new Pimple_Pimple();

        $pimple['anzahlPreisvarianten'] = $buchungsdaten[0];
        $pimple['preisVarianteId'] = $buchungsdaten['programmvariante_0'];
        $pimple['einkaufspreis'] = $buchungsdaten['einkaufspreis'];
        $pimple['verkaufspreis'] = $buchungsdaten['verkaufspreis'];

        $pimple['tabellePreise'] = function(){
            return new Application_Model_DbTable_preise();
        };

        $frontModelAenderungPreisePreisvariante = new Front_Model_AenderungPreisePreisvariante();
        $frontModelAenderungPreisePreisvariante
            ->setPimple($pimple)
            ->steuerungUpdatePreiseRabattprogramm();

        unset($buchungsdaten['verkaufspreis']);
        unset($buchungsdaten['einkaufspreis']);

        return $buchungsdaten;
    }

    /**
     * Update Programmdatensatz
     *
     * + bestimmt die Anzahl der Programmsprachen, wenn keine Programmsprachen vorhanden dann sprache = 0
     * + löscht bereits vorhandenes Programm
     * + trägt neues / neue Buchung ein
     */
    public function updateProgrammbuchungAction()
    {
        $request = $this->getRequest();
        $buchungsdaten = $request->getParams();

        unset($buchungsdaten['module']);
        unset($buchungsdaten['controller']);
        unset($buchungsdaten['action']);
        unset($buchungsdaten['warenkorb']);

        try {
            // Veränderung EK und VK einer Preisvariante
            if( (array_key_exists('einkaufspreis', $buchungsdaten)) and (array_key_exists('verkaufspreis', $buchungsdaten)) )
                $buchungsdaten = $this->aendernPreiseRabattProgramm($buchungsdaten);

            // Bestimmung Anzahl Programmsprachen
            $pimple = new Pimple_Pimple();
            $frontModelProgrammSprache = new Front_Model_ProgrammSprache($pimple);
            $anzahlProgrammsprachen = $frontModelProgrammSprache
                ->setProgrammId($buchungsdaten['ProgrammId'])
                ->steuernErmittelnProgrammsprachen()
                ->getAnzahlProgrammsprachen();

            if($anzahlProgrammsprachen == 0)
                $buchungsdaten['sprache'] = 0;

            $modelProgrammdetail = new Front_Model_Programmdetail();

            // Kontrolle Programmvarianten
            $modelProgrammdetail->kontrolleProgrammvarianten($buchungsdaten);

            // Session Namespace Buchung
            $sessionNamespaceBuchung = new Zend_Session_Namespace('buchung');
            $arraySessionNamespaceBuchung = (array) $sessionNamespaceBuchung->getIterator();
            $gebuchtesProgrammBuchungstabelleId = $arraySessionNamespaceBuchung['editProgramm'];
            unset($sessionNamespaceBuchung->editProgramm);


            // Id des Datensatzes Tabelle 'tbl_programmbuchung'
            $modelProgrammdetail->setGebuchtesProgramm($gebuchtesProgrammBuchungstabelleId);

            // update Programmdatensatz
            $update = array(
                'datum' => $buchungsdaten['datum'],
                'zeitmanagerStunde' => $buchungsdaten['zeitmanagerStunde'],
                'zeitmanagerMinute' => $buchungsdaten['zeitmanagerMinute'],
                'spracheManager' => $buchungsdaten['spracheManager'],
                'anzahl' => $buchungsdaten[0]
            );

            $modelProgrammdetail->updateProgrammdatensatz($update);

            /** @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = $this->_helper->getHelper('Redirector');
            $redirector->gotoUrl('/front/warenkorb/index/');

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Holt bereits gespeicherte Werte der Seite
     *
     * @return void
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        unset($params['editProgramm_x']);
        unset($params['editProgramm_y']);

        try {
            $model = new Front_Model_Programmdetail();

            // bestimmen der Buchungsdetails
            $daten = $model->getBuchungsDetail($params['idBuchungstabelle']);

            $params = array_merge($params, $daten);

            $this->_helper->redirector->gotoSimple('index', 'programmdetail', 'front', $params);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /*** Ajax Bereich ***/

    public function preisneuberechnungAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Preismanager
            $modelPreisNeuBerechnung = new Front_Model_ProgrammdetailPreismanager();

            // Kontrolle 2
            $vorhanden = array( 'programmId' );
            $modelPreisNeuBerechnung->checkDatenVorhanden($params, $vorhanden);
            $modelPreisNeuBerechnung->checkDatenInhalt($params);

            $faktorenPreisgestaltung = $modelPreisNeuBerechnung->mapData($params);

            $modelPreisNeuBerechnung->setDaten($faktorenPreisgestaltung);
            $neuBerechneterPreis = $modelPreisNeuBerechnung->getNeuberechneterPreis();

            // Response
            if (is_array($neuBerechneterPreis)) {
                if ($params['sprache'] == 'de') {
                    $neuBerechneterPreis['information'] = $neuBerechneterPreis['de'];
                    unset($neuBerechneterPreis['eng']);
                    unset($neuBerechneterPreis['de']);
                } else {
                    $neuBerechneterPreis['information'] = $neuBerechneterPreis['eng'];
                    unset($neuBerechneterPreis['de']);
                    unset($neuBerechneterPreis['eng']);
                }

                echo json_encode($neuBerechneterPreis);
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Ermittelt die Werte eines gebuchten Programmes
     * + ermittelt Werte
     * + ruft anschließend die Index - Action auf


     */
    public function editProgrammbuchungAction()
    {
        try {
            $params = $this->realParams;

            $shadowProgrammdetailEditBestandsbuchung = new Front_Model_ProgrammdetailEditProgrammbuchungShadow();

            // ermitteln Preisvariante
            $preisvariante = $shadowProgrammdetailEditBestandsbuchung->ermittelnPreisvariante($params['idBuchungstabelle']);

            // ermitteln ursprüngliche Anzahl gebuchte Programme
            $originalBuchungsdatensatzProgramm = $shadowProgrammdetailEditBestandsbuchung->ermittelnOriginalProgrammbuchungsdaten($preisvariante);

            // ermitteln Preis der Preisvariante
            $preisDerPreisvariante = $shadowProgrammdetailEditBestandsbuchung->ermittelnPreisDerpreisvariante($originalBuchungsdatensatzProgramm['tbl_programme_preisvarianten_id']);

            // ermitteln der Zeiten für den Zeitmanager
            $preisvariante = $shadowProgrammdetailEditBestandsbuchung->zeitmanager($preisvariante);

            // ermitteln Preis

            // mappen der Werte
            $datenBestandsbuchung = $shadowProgrammdetailEditBestandsbuchung->mappenBestandsbuchung(
                $preisvariante,
                $preisDerPreisvariante,
                $originalBuchungsdatensatzProgramm
            );

            $this->datenBestandsbuchung = $datenBestandsbuchung;
            $this->realParams['programId'] = $preisvariante['id'];

            // speichern zu editierendes Programm in Session
            $sessionNamespaceBuchung = new Zend_Session_Namespace('buchung');
            $sessionNamespaceBuchung->editProgramm = $params['idBuchungstabelle'];

            $this->flagEditProgrammbuchung = true;
            $this->indexAction();

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    private function buchungsDatumDatepicker($raintpl)
    {
        $gebuchtesDatum = 0;

        if ($this->datenBestandsbuchung) {
            $datum = $this->datenBestandsbuchung['datum'];
            $gebuchtesDatum = nook_ToolDatum::verkuerzeDatumJahreszahl($datum);
        }

        $raintpl->assign('gebuchtesDatum', $gebuchtesDatum);

        return $raintpl;
    }

    /**
     * Serviceaufgaben des Template
     * + Krümelnavigation / darstellen des Navigationsablaufes
     * + zuschalten allgemeiner Blöcke
     * + Darstellungssprache
     *
     * @param $params
     * @param $raintpl
     * @param $modelProgrammdetail
     */
    private function serviceAnzeige($params, $raintpl, $modelProgrammdetail)
    {
        // Breadcrumb
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep(1, 3)
            ->setParams($params)
            ->getNavigation();
        $raintpl->assign('breadcrumb', $navigation);

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        // zuschalten der allgemeinen Blöcke
        $showBlock = true;
        $this->_showBlock = $showBlock;
        $raintpl->assign('showBlock', $showBlock);

        // finden Sprache und schalten der Sprache
        $modelProgrammdetail->findLanguage();
        $raintpl->assign('sprache', Zend_Registry::get('language'));
    }

    /**
     * Schalten der Blöcke des Template
     * + wenn es eine Bestandsbuchung ist, wird 'buchungsDetailShow' = 2
     *
     * @param $raintpl
     * @return mixed
     */
    private function setBestandsbuchung($raintpl)
    {
        $raintpl->assign('buchungsDetailShow', 2);

        // Daten der Bestandsbuchung
        $raintpl->assign('bestandsbuchung', $this->datenBestandsbuchung);

        return $raintpl;
    }

    /**
     * Ermitteln der Öffnungszeiten eines Programmes
     *
     * @param $programmId
     * @return array
     */
    private function getOeffnungszeiten($programmId)
    {
        $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        $frontModelOefnungszeiten = new Front_Model_OeffnungszeitenProgramm();
        $oeffnungszeiten = $frontModelOefnungszeiten
            ->setProgrammId($programmId)
            ->setAnzeigespracheId($anzeigesprache)
            ->steuerungErmittlungOeffnungszeitenProgramm()
            ->darstellenGanztaegigeOeffnung()
            ->korrekturMitternacht()
            ->getOeffnungszeiten();

        return $oeffnungszeiten;
    }

    private function getGeschaeftstage($programmId)
    {
        $frontModelOefnungszeiten = new Front_Model_OeffnungszeitenProgramm();
        $geschaeftstage = $frontModelOefnungszeiten
            ->setProgrammId($programmId)
            ->steuerungErmittlungGeschaeftstageProgramm()
            ->getGeschaeftstage();

        return $geschaeftstage;
    }

    /**
     * Kontrolliert die eingegebene Öffnungszeit durch den Kunden
     *
     * + Vergleich mit der Öffnungszeit am jeweiligen Wochentag
     *
     */
    public function kontrolleOeffnungszeitenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $anzeigespracheId = nook_ToolSprache::ermittelnKennzifferSprache();

            $toolDatum = new nook_ToolDatum();
            $datum = $toolDatum->konvertDatumInDate($anzeigespracheId, $params['datum']);

            $stunde = trim($params['stunde']);
            if($params['stunde'] < 10)
               $stunde = '0'.$stunde;

            $minute = trim($params['minute']);
            if($minute < 10)
                $minute = '0'.$minute;

            $zeit = $stunde.":".$minute.":00";

            $frontModelOeffnungszeiten = new Front_Model_OeffnungszeitenProgramm();
            $flagKontrolleOeffnungszeit = $frontModelOeffnungszeiten
                ->setProgrammId($params['programmId'])
                ->setDatum($datum)
                ->setZeit($zeit)
                ->steuerungUeberpruefungOeffnungszeit()
                ->getKontrolleOeffnungszeit();

            echo $flagKontrolleOeffnungszeit;
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Ermittelt den Buchungstyp eines Programmes
     *
     * @param $params
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnBuchungstyp($params, $raintpl)
    {
        // Programmdetails - Basisdaten 'tbl_programmdetails'
        $toolBuchungstyp = new nook_ToolBuchungstyp();
        $buchungsTyp = $toolBuchungstyp
            ->setProgrammId($params['programId'])
            ->ermittleBuchungstypProgramm();

        $raintpl->assign('buchungsTyp', $buchungsTyp);

        return $raintpl;
    }
}

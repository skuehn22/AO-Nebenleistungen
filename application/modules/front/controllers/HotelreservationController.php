<?php
/**
 * Auflistung der Hotels in einer Stadt die über eine Kapazität zur betreffenden Zeit verfügen.
 *
 * @author Stephan.Krauss
 * @date 18.24.2013
 * @file HotelreservationController.php
 * @package front
 * @subpackage controller
 */
class Front_HotelreservationController extends Zend_Controller_Action{

    // Konditionen
    private $_condition_flag_update = 2;
    private $_condition_flag_insert = 1;

    private $anzeigeSpracheId = null;

    protected $_realParams = array();
    protected $bereitsGebuchteRaten = array();
    protected $pimple = null;
    protected $requestUrl = null;

    // kennung Typ Hotelprodukte 'Hop - Top'
    protected $typZusatzprodukteHopTop = array(3);

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();

        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $this->servicecontainer();
    }

    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleTeilrechnungen'] = function(){
            return new Application_Model_DbTable_teilrechnungen();
        };

        $pimple['tabelleCategories'] = function()
        {
            return new Application_Model_DbTable_categories(array('db' => 'hotels'));
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * Darstellung der Raten eines Hotels
     * und Darstellung bereits gewählter Raten einer Buchung
     *
     */
    public function indexAction()
    {
		$params = $this->realParams;
        $params = $this->mapperIndex($params);

        try{
        	$raintpl = raintpl_rainhelp::getRainTpl();
            $frontModelHotelreservation = new Front_Model_Hotelreservation();

            // Breadcrumb
            $params['city'] = (int) $params['city'];
            $navigation = $this->navigation(6, 4, $params);

            $raintpl->assign('breadcrumb', $navigation);

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            // PropertyId / Hotel ID
            $raintpl->assign('propertyId', $params['propertyId']);

            // ermitteln Suchparameter der Hotelsuche
            $suchparameterHotelsuche = $this->suchparameterHotelsuche();
            $params['city'] = $suchparameterHotelsuche['city'];

            // setzen Suchparameter
            $frontModelHotelreservation = $this->setzenSuchparamter($params, $suchparameterHotelsuche, $frontModelHotelreservation);

            // ermitteln Hotelbeschreibung und Abreisetag
            $hotelbeschreibung = $this->findHotelBeschreibung($params, $suchparameterHotelsuche, $frontModelHotelreservation);
            $raintpl->assign('hotelbeschreibung', $hotelbeschreibung);

            // City Id
            $raintpl->assign('cityId', $params['city']);

            // Flag flagUpdate Datensatz = Update der Raten
            $raintpl = $this->setzenFlagUpdate($raintpl);

            // Auflistung der Zimmer
            $zimmer = $frontModelHotelreservation->getAuflistenVerfuegbareZimmer();

            // nzeige der bereits gebuchten Raten
            $modelBereitsGebuchteRaten = new Front_Model_HotelreservationUpdateRaten();
            $zimmer = $modelBereitsGebuchteRaten->belegenPersonenanzahlDerGebuchtenraten($zimmer, $this->bereitsGebuchteRaten);
            $raintpl->assign('zimmer', $zimmer);

            // Überbuchung des Hotels abprüfen
            $raintpl = $this->_checkUeberbuchung($frontModelHotelreservation, $raintpl);

            // Blöcke anzeigen
            $raintpl->assign('showBlock', true);

            $this->view->content = $raintpl->draw( "Front_Hotelreservation_Index", true );
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Mappen Daten Action 'index'
     *
     * + Mappen Startdatum
     * + entfernen überflüssiger Daten
     *
     * @param array $params
     * @return array
     */
    protected function mapperIndex(array $params)
    {
        // Update
        if(count($this->bereitsGebuchteRaten) > 0){
// Startdatum deutsch
            if($this->anzeigeSpracheId == 1){
                $teileDatum = explode(".",$params['from']);
                $params['from'] = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
            }
            // Startdatum englisch
            else{
                $teileDatum = explode("/",$params['from']);
                $params['from'] = $teileDatum[2]."-".$teileDatum[0]."-".$teileDatum[1];
            }
        }
        else{
            // Anreisedatum
            $hotelsucheNamespace = new Zend_Session_Namespace('hotelsuche');
            $parameterHotelsuche = (array) $hotelsucheNamespace->getIterator();

            // Hotel ID
            $hotelsucheNamespace->propertyId = $params['propertyId'];

            $params['from'] = $parameterHotelsuche['from'];

            // Startdatum deutsch
            if($this->anzeigeSpracheId == 1){
                $teileDatum = explode(".",$params['from']);
                $params['from'] = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
            }
            // Startdatum englisch
            else{
                $teileDatum = explode(".",$params['from']);
                $params['from'] = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
            }
        }

        return $params;
    }

    /**
     * Stellt die Navigation des Baustein dar
     *
     * @param $bereich
     * @param $step
     * @param array $params
     * @return array
     */
    private function navigation($bereich, $step,array $params)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($bereich, $step)
            ->setParams($params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Ermitteln ob das Hotel eine Überbuchung zulässt
     *
     * @param Front_Model_Hotelreservation $__model
     * @param $__raintpl
     * @return $__raintpl
     */
    private function _checkUeberbuchung(Front_Model_Hotelreservation $__model, $__raintpl){
        // Ueberbuchungsmodus ermitteln
        $ueberbuchungMoeglich = $__model->getUberbuchungsModus();

        if($ueberbuchungMoeglich)
        $__raintpl->assign('roomlimitUeberbuchung', 1000);
        else
        $__raintpl->assign('roomlimitUeberbuchung', 0);

        $__raintpl->assign('ueberbuchungMoeglich', $ueberbuchungMoeglich);

        return $__raintpl;
    }

    /**
     * Setzen der Suchparamter der Hotelsuche
     *
     * + update einer Hotelbuchung
     * + neue Hotelbuchung
     *
     * @param $frontModelHotelreservation
     * @return obj
     */
    private function setzenSuchparamter(array $params,array $suchparameterHotelsuche, Front_Model_Hotelreservation $frontModelHotelreservation)
    {
        // Update
        if(count($this->bereitsGebuchteRaten) > 0){
            $flagUpdate = 2;

            $adult = 0;
            foreach($this->bereitsGebuchteRaten as $rate){
                $adult += $rate['personNumbers'];
            }

            $params['adult'] = $adult;

            $frontModelHotelreservation->setSuchparameterHotel($params, $flagUpdate);
        }
        // neue Hotelbuchung
        else{
            $flagUpdate = 1;
            $frontModelHotelreservation->setSuchparameterHotel($suchparameterHotelsuche, $flagUpdate);
        }

        // Überbuchungsmodus
        $frontModelHotelreservation->setUberbuchungsModus();

        return $frontModelHotelreservation;
    }

    /**
     * Findet die Hotelbeschreibung für neue Buchung oder ein Update der Buchung
     *
     * + ermittelt die Gesamtanzahl der Personen einer Buchung
     *
     * @param array $params
     * @param Front_Model_Hotelreservation $modelHotelreservation
     * @return array
     */
    private function findHotelBeschreibung(array $params, array $suchparameterHotelsuche, Front_Model_Hotelreservation $modelHotelreservation)
    {
        $hotelbeschreibung = $modelHotelreservation->getHotelbeschreibung();
        $hotelbeschreibung['hotelId'] = $params['propertyId'];

        $sessionNamespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $hotelsucheParameter = (array) $sessionNamespaceHotelsuche->getIterator();

        // Update
        if( count($this->bereitsGebuchteRaten) > 0){
            $hotelbeschreibung['personenanzahl'] = $suchparameterHotelsuche['adult'];
            $hotelbeschreibung['uebernachtungen'] = $suchparameterHotelsuche['days'];
            $hotelbeschreibung['anreisetag'] = $suchparameterHotelsuche['suchdatum'];

            $suchdatum = $suchparameterHotelsuche['suchdatum'];
        }
        // neue Buchung
        else{
            $hotelbeschreibung['personenanzahl'] = $hotelsucheParameter['adult'];
            $hotelbeschreibung['uebernachtungen'] = $hotelsucheParameter['days'];
            $hotelbeschreibung['anreisetag'] = $hotelsucheParameter['suchdatum'];

            $suchdatum = $hotelsucheParameter['suchdatum'];
        }

        // berechnen Abreisetag
        $date = date_create($suchdatum);
        date_add($date, date_interval_create_from_date_string($hotelsucheParameter['days'].' days'));
        $abreisetag = date_format($date, 'Y-m-d');
        $hotelbeschreibung['abreisetag'] = $abreisetag;

        return $hotelbeschreibung;
    }

    /**
     * Stellt die Daten der Zimmerbeschreibung zur Verfügung
     *
     * @return void
     */
    public function zimmerbeschreibungAction(){
		$suchparameterZimmer = $this->realParams;

        // mappen Parameter
        if($suchparameterZimmer['sprache'] == 'eng')
            $suchparameterZimmer['sprache'] = 'en';

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

        	$raintpl = raintpl_rainhelp::getRainTpl();
            $modelHotelreservation = new Front_Model_Hotelreservation();

            $beschreibungZimmer = $modelHotelreservation->getZimmerbeschreibung($suchparameterZimmer);
            if(!empty($beschreibungZimmer['ueberschrift'])){


                // Ermitteln der Bild ID des Kategorie Bildes
                $frontModelBilderKategorie = new Front_Model_BilderKategorie($this->pimple);
                $beschreibungZimmer['kategorieBildId'] = $frontModelBilderKategorie
                    ->setKategorieId($beschreibungZimmer['kategorieId'])
                    ->steuerungErmittlungKategorieBildId()
                    ->getKategorieBildId();

                $raintpl->assign('showBlock', true);
                $raintpl->assign('beschreibungZimmer', $beschreibungZimmer);

                $produkteDerRate = $modelHotelreservation->getProdukteEinerRate($suchparameterZimmer, $beschreibungZimmer['ratenId']);
                if(is_array($produkteDerRate)){
                    $raintpl->assign('showProdukte', true);
                    $raintpl->assign('produkte', $produkteDerRate);
                }
                else
                    $raintpl->assign('showProdukte', false);
            }
            else{
                $raintpl->assign('showBlock', false);
                $raintpl->assign('showBlock', false);
            }

            $templat = $raintpl->draw( "Front_Hotelreservation_Zimmerbeschreibung", true );
            echo $templat;

        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Ändert eine bereits vorhandene Hotelbuchung
     * 
     * @return void
     */
    public function changerateAction(){
        $request = $this->getRequest();
		$params = $request->getParams();

        try{
            $model = new Front_Model_Hotelreservation();
            $propertyId = $model->findIdHotel($params['buchungstabelle']);

            $redirector = $this->_helper->getHelper('Redirector');
            $redirector->gotoSimple('index','hotelreservation','front',array(
                'propertyId' => $propertyId,
                'buchungstabelle' => $params['buchungstabelle']
            ));
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /***** speichern der Raten ******/

    /**
     * Speichern einer neuen Hotelreservierung.
     * Speichern der Upates der bereits gebuchten
     * Raten eines Hotels.
     *
     *  private $_condition_flag_update = 2;
     *  private $_condition_flag_insert = 1;
     * + Neuberechnung Personenanzahl
     * + speichern der Raten
     * + speichern der Raten als XML
     * + Übergang auf nächstes Modul
     *
     */
    public function saveAction()
    {
        $gebuchteRatenEinesHotels = $this->realParams;

        try{
            $flagUpdate = $gebuchteRatenEinesHotels['flagUpdate'];
            unset($gebuchteRatenEinesHotels['flagUpdate']);

            // $gebuchteRatenEinesHotels = $this->leereRatenLoeschen($gebuchteRatenEinesHotels);

            // Neuberechnung Personenanzahl und eintragen in Namespace 'hotelsuche'
            $personenanzahl = $this->_neuberechnungPersonenanzahl($gebuchteRatenEinesHotels);

            // speichern der Raten
            $teilrechnungId = $this->_speichernHotelbuchung($gebuchteRatenEinesHotels);

            // speichern der Suchparameter Hotelsuche
            $toolSpeichernWerteSessionVormerkungHotel = new nook_ToolSpeichernWerteSessionVormerkungHotel();
            $toolSpeichernWerteSessionVormerkungHotel
                ->setTeilrechnungenId($teilrechnungId)
                ->steuerungSpeichernWerteHotelsuche();

            // wenn es ein Update der Hotelbuchung ist, werden die 'Hop oder Top' Zusatzprodukte in der Anzahl geaendert
            if($this->_condition_flag_update == $flagUpdate){
                $hotelsuche = new Zend_Session_Namespace('hotelsuche');
                $anzahlNaechte = $hotelsuche->days;
                $this->verenderungAnzahlBereitsGebuchterProdukte($personenanzahl, $anzahlNaechte, $teilrechnungId);
            }

            // speichern der Raten als XML
            $this->_speichernHotelbuchungXML();

            // speichern Teilrechnung ID in Session
            $hotelsuche = new Zend_Session_Namespace('hotelsuche');

            if(!empty($teilrechnungId))
                $hotelsuche->teilrechnungsId = $teilrechnungId;

            $hotelsuche->propertyId = $gebuchteRatenEinesHotels['propertyId'];
            $this->_redirect('/front/zusatzprodukte/index/');
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Löscht leere Raten einer Hotelbuchung und gibt bereinigtes Array zurück
     *
     * @param array $gebuchteRatenEinesHotels
     * @return array
     */
    protected function leereRatenLoeschen(array $gebuchteRatenEinesHotels)
    {
        $gebuchteRatenEinesHotelsBereinigt = array();

        foreach($gebuchteRatenEinesHotels as $key => $value){
            if($value == 0)
                continue;

            $gebuchteRatenEinesHotelsBereinigt[$key] = $value;
        }

        return $gebuchteRatenEinesHotelsBereinigt;
    }

    /**
     * Berechnet die Personenanzahl. Korrektur Überbuchung der raten
     *
     * @param $gebuchteRatenEinesHotels
     * @return int
     */
    private function _neuberechnungPersonenanzahl ($gebuchteRatenEinesHotels)
    {
        // Übernahme der Personenanzahl / Erhöhung Personenanzahl
        $personenanzahl = 0;
        foreach($gebuchteRatenEinesHotels as $key => $value) {
            $key = (int) $key;
            if($key > 0)
                $personenanzahl += $value;
        }

        $toolSuchparameterHotel = new nook_ToolSuchparameterHotel();
        $toolSuchparameterHotel->setPersonenanzahlHotelsuche($personenanzahl);

        return $personenanzahl;
    }

    /**
     * Speichern der Hotelbuchungen in Tabelle
     *
     * 'tbl_hotelbuchung'.
     * Gibt Teilrechnungs ID zurück
     *
     * @param array $__gebuchteRatenEinesHotels
     * @return int
     */
    private function _speichernHotelbuchung(array $__gebuchteRatenEinesHotels){
        $model = new Front_Model_Warenkorb();
        $teilrechnungsId = $model->saveHotelBuchungen($__gebuchteRatenEinesHotels);

        return $teilrechnungsId;
    }

    /**
     * Speichern der Hotelbuchung als XMl Block
     * in Tabelle 'tbl_xml_buchung'
     *
     * @param array $__gebuchteRatenEinesHotels
     */
    private function _speichernHotelbuchungXML(){

         // speichern der Raten als XML Block
        $modelHotelbuchungXML = new Front_Model_BuchungsuebersichtHotelbuchungXML();
        $modelHotelbuchungXML->setDebugModus(false);
        $modelHotelbuchungXML->saveXML();

        return;
    }

    /**
     * Ermittelt die bereits gebuchten Raten
     *
     * + Wandelt bei Bedarf das englische Datumsformat ins deutsche um
     */
    public function updateShowAction()
    {
        $params = $this->realParams;

        try{
            $params = $this->mapperUpdateShow($params);

            // holt die Suchparameter der Hotelsuche für die Teilrechnung
            $toolSpeichernWerteSessionVormerkungHotel = new nook_ToolSpeichernWerteSessionVormerkungHotel();
            $toolSpeichernWerteSessionVormerkungHotel
                ->setTeilrechnungenId($params['teilrechnungen_id'])
                ->steuerungErmittelnWerteHotelsuche();

            $modelBereitsGebuchteRaten = new Front_Model_HotelreservationUpdateRaten();
            $bereitsGebuchteRaten = $modelBereitsGebuchteRaten
                ->setNights($params['days'])
                ->setPropertyId($params['propertyId'])
                ->setStartDate($params['from']) // anpassen Startdatum
                ->steuerungErmittlungGebuchteRaten()
                ->getBereitsGebuchteRaten();

            $this->bereitsGebuchteRaten = $bereitsGebuchteRaten;

            // Ergänzen / Update Session
//            $sessionParams = array(
//                'city' => $params['cityId'],
//                'from' => $params['from'],
//                'days' => $params['days'],
//                'propertyId' => $params['propertyId'],
//                'adult' => $modelBereitsGebuchteRaten->getGesamtanzahlpersonen(),
//                'teilrechnungsId' => $params['teilrechnungen_id']
//            );
//
//            nook_ToolSession::setParamsInSessionNamespace('hotelsuche', $sessionParams);

            $this->indexAction();
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Holt die Suchparameter der Hotelsuche
     *
     * @return Zend_Session_Namespace
     */
    private function suchparameterHotelsuche()
    {
        $namespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $suchparameterHotelsuche = (array) $namespaceHotelsuche->getIterator();

        return $suchparameterHotelsuche;
    }

    /**
     * Mappen der Daten
     *
     * @param array $params
     * @return array
     */
    protected function mapperUpdateShow(array $params)
    {
// deutsches Anreisedatum
        if (strstr($params['from'], '.')) {
            $teileDatum = explode('.', $params['from']);
            $params['from'] = $teileDatum[2] . '-' . $teileDatum[1] . '-' . $teileDatum[0];
        }

        // englisches Anreisedatum
        if (strstr($params['from'], '/')) {
            $teileDatum = explode('/', $params['from']);
            $params['from'] = $teileDatum[2] . '-' . $teileDatum[0] . '-' . $teileDatum[1];

            return $params;
        }

        return $params;
    }

    /**
     * Löschen der 'alten' Raten einer Teilbuchung
     *
     * @param array $gebuchteRatenEinesHotels
     * @return array
     */
    protected function loeschenAlteTeilrechnung($gebuchteRatenEinesHotels)
    {
        $namespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $inhaltSessionHotelsuche = $namespaceHotelsuche->getIterator();
        $teilrechnungsId = $inhaltSessionHotelsuche['teilrechnungsId'];

        // löschen der alten Teilrechnung
        $toolLoeschenTeilrechnungHotel = new nook_ToolHotelbuchungTeilrechnungLoeschen($this->pimple);
        $anzahlGeloeschteRaten = $toolLoeschenTeilrechnungHotel
            ->setTeilrechnungId($teilrechnungsId)
            ->steuerungLoeschenTeilrechnung()
            ->getAnzahlGeloeschteRaten();

        return $anzahlGeloeschteRaten;
    }

    /**
     * Typ der Zusatzprodukte die je Person und Nacht
     * von der gesamten Gruppe gebucht werden
     *
     * @param $personenanzahl
     * @param $teilrechnungId
     */
    protected function verenderungAnzahlBereitsGebuchterProdukte($personenanzahl, $anzahlNaechte, $teilrechnungId)
    {
        $toolUpdateAnzahlGebuchteZusatzprodukteHotel = new nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel();
        $anzahlGeanderteZusatzprodukte = $toolUpdateAnzahlGebuchteZusatzprodukteHotel
            ->setPersonenanzahl($personenanzahl)
            ->setTeilrechnungId($teilrechnungId)
            ->setAnzahlNaechte($anzahlNaechte)
            ->setTypZusatzprodukte($this->typZusatzprodukteHopTop)
            ->steuerungVeraendernZusatzprodukteHopTop()
            ->getAnzahlGeanderteZusatzprodukte();
    }

    /**
     * Ermittelt den Abreisetag der Gruppe
     *
     * @param $hotelbeschreibung
     * @return string
     */
    private function ermittelnAbreisetag($hotelbeschreibung)
    {
        $toolAbreisetag = new nook_ToolAbreisetag();
        $abreisetag = $toolAbreisetag
            ->setAnreiseDatum($hotelbeschreibung['anreisetag'])
            ->setAnzahlUebernachtungen($hotelbeschreibung['uebernachtungen'])
            ->berechneAbreisetag()
            ->getAbreiseDatum();

        return $abreisetag;
    }

    /**
     * Setzen Flag Update wenn bereits gebuchte Raten vorhanden
     *
     * @param $raintpl
     */
    private function setzenFlagUpdate($raintpl)
    {
        if (count($this->bereitsGebuchteRaten) > 0)
            $raintpl->assign('flagUpdate', $this->_condition_flag_update);
        // Flag normales einfügen
        else
            $raintpl->assign('flagUpdate', $this->_condition_flag_insert);

        return $raintpl;
    }
}


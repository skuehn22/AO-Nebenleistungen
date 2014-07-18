<?php
/**
 * Auflistung der Übernachtungsmöglichkeiten
 * in einer Stadt
 *
 *
 * @author Stephan Krauss
 */
class Front_HotellistController extends Zend_Controller_Action
{

    // Parameter Aufruf Baustein
    private $_realParams = null;
    private $requestUrl = null;

    // Anzeigesprache
    private $anzeigeSpracheId = null;

    public function init()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        $this->requestUrl = $this->view->url();

        $bausteinVariablen = new nook_ToolBausteinvariablen(); // Notiz: Überprüfen

        // Anzeigesprache ID
        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        $this->realParams = $bausteinVariablen->ablaufBereichStep($params, 6, 2); // speichern ankommende Variablen
    }

    public function indexAction()
    {
        $sucheHotelsInEinerStadt = $this->realParams;

        try {
            // umwandeln Datumsformat der Suchparameter ins deutsche Datumsformat
            if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sucheHotelsInEinerStadt['from'], $parts) == true)
                $sucheHotelsInEinerStadt = $this->umwandelnIsoDatum($sucheHotelsInEinerStadt);
            else
                $sucheHotelsInEinerStadt = $this->englischesDatumInDeutschesDatum($sucheHotelsInEinerStadt);

            // Abfrage Verfügbarkeiten Meininger
            $this->verfuegbarkeitMeininger($sucheHotelsInEinerStadt['city'], $sucheHotelsInEinerStadt['from'], $sucheHotelsInEinerStadt['to']);

            // umschalten Anzeigesprache
            $this->umschaltenAnzeigesprache($sucheHotelsInEinerStadt);

            $modelHotellist = new Front_Model_Hotellist(); // Model
            $raintpl = raintpl_rainhelp::getRainTpl(); // Template Engine

            // Breadcrumb
            $navigation = $modelHotellist->getBreadCrumb(6, 3, $sucheHotelsInEinerStadt);
            $raintpl->assign('breadcrumb', $navigation);

            // Kontrolle der Suchparameter
            $sucheHotelsInEinerStadt = $modelHotellist->mapSuchdaten($sucheHotelsInEinerStadt);
            $modelHotellist->setDatenSuchanfrage($sucheHotelsInEinerStadt);

            // Liste der Hotels in einer Stadt
            $business = new Front_Model_Hotellistbusiness();
            $modelHotellist->setDic('business', $business);

            // Kontrolle der Raten der Hotels
            $ratenkontrolle = new nook_ratenkontrolle();
            $modelHotellist->setDic('kontrolleRaten', $ratenkontrolle);

            // Breadcrumb
            $this->view->crumb1 = $modelHotellist->getCityCrumb($sucheHotelsInEinerStadt);

            // Ermitteln der Hotels einer Stadt
            $hotelsMitBeschreibung = $modelHotellist
                ->setDatenDerSuchanfrage($sucheHotelsInEinerStadt)
                ->getHotelsMitBeschreibung();

            // aktueller Stadtname
            $stadtname = nook_Tool::findCityNameById($sucheHotelsInEinerStadt['city']);
            $raintpl->assign('city', $stadtname);

            // Abreisetag
            $toolAbreisetag = new nook_ToolAbreisetag();
            $abreisetag = $toolAbreisetag
                ->setAnreiseDatum($sucheHotelsInEinerStadt['from'])
                ->setAnzahlUebernachtungen($sucheHotelsInEinerStadt['days'])
                ->berechneAbreisetag()
                ->getAbreiseDatum();


            $sucheHotelsInEinerStadt['abreisetag'] = $abreisetag;

            // konvertiert das Datum wenn Anzeige in englisch
            if($this->anzeigeSpracheId == 2)
                $sucheHotelsInEinerStadt = $this->konvertierenDatumsanzeigeEnglisch($sucheHotelsInEinerStadt);

            $raintpl->assign('parameter', $sucheHotelsInEinerStadt);

            // Block Hotelbeschreibung
            if (!empty($hotelsMitBeschreibung)) {
                $raintpl->assign('hotels', $hotelsMitBeschreibung);
                $raintpl->assign('information', false); // Information ausblenden
            } else
                $raintpl->assign('information', true); // Information anzeigen

            // ID der City
            $raintpl->assign('ort', $sucheHotelsInEinerStadt['city']);

            $this->view->content = $raintpl->draw("Front_Hotellist_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Ermittelt die Verfügbarkeit und die Preise der Hotels in einer Stadt
     *
     * @param $cityId
     */
    protected function verfuegbarkeitMeininger($cityId, $anreiseDatum, $abreiseDatum)
    {
        // notice: Ab und Zuschaltung Ermittlung Raten 'Meininger'
        $config = Zend_Registry::get('static');
        $nutzungMeininger = $config->filialen->meininger;

        if($nutzungMeininger <> 2)
            return;

        $applicationConfigMeininger = new Application_Model_Configs_Meininger();
        $hotelsMeininger = $applicationConfigMeininger->getDataHotelsMeininger();

        // Hat Meininger ein Hotel in der Stadt ?
        $hotelInCity = false;
        foreach($hotelsMeininger as $key => $hotel){

            if($hotelsMeininger[$key]['cityId'] == $cityId){
                $hotelInCity = true;

                break;
            }

        }

        if($hotelInCity === false)
            return;

        $anreiseDatum = nook_Tool::erstelleSuchdatumAusFormularDatum($anreiseDatum);
        $abreiseDatum = nook_Tool::erstelleSuchdatumAusFormularDatum($abreiseDatum);

        // Pimple
        $pimpleObj = new Pimple_Pimple();

        // Datenbank
        $pimpleObj['tabelleOtaPrices'] = function()
        {
            return new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));
        };

        $pimpleObj['tabelleOtaRatesAvailability'] = function()
        {
            return new Application_Model_DbTable_otaRatesAvailability(array('db' => 'hotels'));
        };

        $pimpleObj['tabelleOtaRatesConfig'] = function(){
            return new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        };

        // Objekte
        $pimpleObj['convertDataObj'] = function(){
            return new nook_ToolPhpJsonXmlArrayStringInterchanger();
        };

        $pimpleObj['serveranfrageObj'] = function(){
            return new nook_ToolMeiningerServeranfrage();
        };

        $pimpleObj['eintragenVerfuegbarkeitObj'] = function(){
            return new Front_Model_MeiningerEintragenVerfuegbarkeit();
        };

        // Array
        $pimpleObj['urlErweiterung'] = $applicationConfigMeininger->getUrlErweiterung();
        $pimpleObj['hotelsMeininger'] = $applicationConfigMeininger->getDataHotelsMeininger();
        $pimpleObj['vereinbarteRaten'] = $applicationConfigMeininger->getVereinbarteRaten();

        // Werte
        $pimpleObj['skey'] = $applicationConfigMeininger->getKey();
        $pimpleObj['meiningerIp'] = $applicationConfigMeininger->getMeiningerIp();
        $pimpleObj['anreiseDatum'] = $anreiseDatum;
        $pimpleObj['abreiseDatum'] = $abreiseDatum;

        $pimpleObj['cityId'] = $cityId;

        // Filialen Meininger ist aktiv
        $static = Zend_Registry::get('static');
        if($static->filialen->meininger == 2){
            $frontModelMeiningerVerfuegbarkeit = new Front_Model_MeiningerVerfuegbarkeit();
            $flagVerbindungZumServerMeininger = $frontModelMeiningerVerfuegbarkeit
                ->setPimple($pimpleObj)
                ->steuerungAvaibilityHotels()
                ->getFlagVerbindungServermeininger();
        }

        return $flagVerbindungZumServerMeininger;
    }


    /**
     * Wandelt Datum ISO 8601 ins deutsche Datum
     *
     * @param $suchparameter
     * @return mixed
     */
    protected function umwandelnIsoDatum($suchparameter)
    {
        foreach($suchparameter as $key => $value){
            if( ($key == 'from') or ($key == 'to') ){
                $teile = explode('-', $value);
                $suchparameter[$key] = $teile[2].'.'.$teile[1].'.'.$teile[0];
            }
        }

        return $suchparameter;
    }

    /**
     * Konvertiert ein deutsches Datum in das englische Datum
     *
     * @param array $sucheHotelsInEinerStadt
     * @return array
     */
    private function konvertierenDatumsanzeigeEnglisch(array $sucheHotelsInEinerStadt)
    {
        $sucheHotelsInEinerStadt['from'] = str_replace('.','/',$sucheHotelsInEinerStadt['from']);
        $sucheHotelsInEinerStadt['to'] = str_replace('.','/',$sucheHotelsInEinerStadt['to']);

        return $sucheHotelsInEinerStadt;
    }

    /**
     * Verlinken auf /front/login wenn ein umschalten der Anzeigesprache erfolgt.
     *
     * @param $sucheHotelsInEinerStadt
     */
    private function umschaltenAnzeigesprache($sucheHotelsInEinerStadt)
    {
        if(!array_key_exists('adult', $sucheHotelsInEinerStadt)){
            $this->_redirect("/front/login");
            exit();
        }

        return;
    }

    /**
     * Wandelt die Suchparameter Datumsangaben vom englischen Format ind deutsche Format
     *
     * @param $suchparameter
     * @return mixed
     */
    private function englischesDatumInDeutschesDatum(array $suchparameter)
    {
        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        if($anzeigeSpracheId == 1)
            return $suchparameter;

        $suchparameter['from'] = nook_ToolDatum::wandleEnglischesDatumInsDeutscheDatum($suchparameter['from']);
        $suchparameter['to'] = nook_ToolDatum::wandleEnglischesDatumInsDeutscheDatum($suchparameter['to']);

        return $suchparameter;
    }
}


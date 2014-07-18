<?php
/**
 * Auswahl der möglichen Bereiche der Buchungsmaschine und Darstellung der Information zur Stadt
 *
 * + Darstellen der Informationen zu einer Stadt
 *
 * @author Stephan.Krauss
 * @date 24.02.2013
 * @file StadtController.php
 * @package front
 * @subpackage controller
 */
class Front_StadtController extends Zend_Controller_Action{

    private $realParams = array();
    private $requestUrl = null;
    protected $raintpl = null;

    protected $condition_premium_programme_typ = 1;

    public function init(){
        $raintpl = raintpl_rainhelp::getRainTpl();

        $frontModelServiceAustria = new Front_Model_ServiceTemplate();
        $service = $frontModelServiceAustria->getServiceTemplate();
        $raintpl = raintpl_rainhelp::getRainTpl();
        $raintpl->assign('service', $service);
        $this->raintpl = $raintpl;

        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    /**
     * Darstellen der Informationen zu einer Stadt
     *
     * + wenn nötig neuer Warenkorb
     * + Navigation
     * + Informationen zu einer Stadt
     *
     */
    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();
    	
        try{
            $raintpl = $this->raintpl;


            // Navigation
            $raintpl = $this->erzeugenNavigation($raintpl, $params);

            // Informationen der Stadt
            $raintpl = $this->informationenZurStadt($params, $raintpl);

            // Premiumprogramme
            $raintpl = $this->premiumProgrammeEinerStadt($params['city'], $raintpl);

            // Ausgabe Template
            $this->view->content = $raintpl->draw( "Front_Stadt_Index", true );
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * ermittelt die Premiumprogramme einer Stadt
     *
     * @param $city
     * @param $raintpl
     * @return mixed
     */
    protected function premiumProgrammeEinerStadt($city, $raintpl)
    {
        $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
        $raintpl->assign('anzeigesprache', $anzeigesprache);

        $pimple = new Pimple_Pimple();
        $pimple['cityId'] = $city;
        $pimple['premiumProgrammKategorieId'] = $this->condition_premium_programme_typ;
        $pimple['anzeigeSpracheId'] = $anzeigesprache;

        $pimple['viewProgrammeEinerStadt'] = function(){
            return new Application_Model_DbTable_viewProgrammeEinerStadt();
        };

        $frontModelPremiumprogrammeEinerStadt = new Front_Model_PremiumProgrammeEinerStadt();
        $premiumProgrammeEinerStadt = $frontModelPremiumprogrammeEinerStadt
            ->setPimple($pimple)
            ->steuerungErmittelnPremiumprogrammeEinerStadt()
            ->getPremiumProgrammeEinerStadt();

        $raintpl->assign('premiumProgrammeEinerStadt', $premiumProgrammeEinerStadt);

        return $raintpl;
    }

    /**
     * Ermittelt die Informationen zur Stadt
     *
     * @param $params
     * @param $raintpl
     * @return mixed
     */
    private function informationenZurStadt($params, $raintpl)
    {
        $frontModelStadt = new Front_Model_Stadt();
        $frontModelStadt->setCityId($params['city']);

        $cityName = $frontModelStadt->getCityName();
        $raintpl->assign('cityname', $cityName);

        // Events in einer Stadt
        $citybeschreibung = $frontModelStadt->getCityTextLang();
        $raintpl->assign('citybeschreibung', $citybeschreibung);

        $raintpl->assign('cityId', $params['city']);

        return $raintpl;
    }

    /**
     * generiert Navigation
     *
     * @param $raintpl
     * @param $params
     * @return mixed
     */
    private function erzeugenNavigation($raintpl, $params)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep(1, 1)
            ->setParams($params)
            ->getNavigation();

        $raintpl->assign('breadcrumb', $navigation);

        return $raintpl;
    }

    /**
     * Kontrolliert ob der Warenkorb eine Stornierung ist
     *
     * + Buchungsnummer und Zaehler des aktuellen Warenkorbes
     * + Kontrolle ob es eine Stornierung ist
     * + wenn Stornierung, neue Buchungsnummer = $flagIsStornierung = true
     */
    private function kontrolleWarenkorb()
    {
        // Buchungsnummer und Zaehler des aktuellen Warenkorbes
        $sessionNamespaceBuchung = (array) nook_ToolSession::holeVariablenNamespaceSession('buchung');

        // wenn keine Buchungsnummer vergeben
        if(!array_key_exists('buchungsnummer', $sessionNamespaceBuchung))
            return;

        // Kontrolle ob es eine Stornierung ist
        $toolStornierung = new nook_ToolStornierung();
        $flagIsStornierung = $toolStornierung
            ->setBuchungsnummer($sessionNamespaceBuchung['buchungsnummer'])
            ->setZaehler($sessionNamespaceBuchung['zaehler'])
            ->anzahlArtikelImWarenkorb()
            ->isStornierung();

        // wenn Stornierung, neue Buchungsnummer
        if(true === $flagIsStornierung)
            $this->umschreibenWarenkorb($sessionNamespaceBuchung);

        return;
    }

    /**
     * Vergibt für den Warenkorb eine neue Buchungsnummer
     *
     * + neue Session ID
     * + neue Buchungsnummer
     * + umschreiben Namespace 'buchung'
     *
     * @param $sessionNamespaceBuchung
     */
    private function umschreibenWarenkorb($sessionNamespaceBuchung)
    {
        // neue Session ID
        Zend_Session::regenerateId();

        // neue Buchungsnummer
        $buchungsnummer = nook_ToolBuchungsnummer::umkopierenBuchungsnummer($sessionNamespaceBuchung['buchungsnummer']);

        $sessionNamespaceBuchung = array(
            'buchungsnummer' => $buchungsnummer,
            'zaehler' => 0
        );

        // umschreiben Namespace 'buchung'
        nook_ToolSession::setParamsInSessionNamespace('buchung', $sessionNamespaceBuchung);

        return;
    }
}


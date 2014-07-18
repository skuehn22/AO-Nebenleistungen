<?php
/**
 * Ankündigung der Eröffnung eines neuen Hotels in einer Stadt
 *
 * + Darstellen der Informationen zu einer Stadt
 *
 * @author Stephan.Krauss
 * @date 24.02.2013
 * @file StadtController.php
 * @package front
 * @subpackage controller
 */
class Front_AnkuendigungController extends Zend_Controller_Action{

    private $_realParams = array();
    private $requestUrl = null;

    public function init(){
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
        	$raintpl = raintpl_rainhelp::getRainTpl();

            // wenn nötig neuer Warenkorb
            $this->kontrolleWarenkorb();

            // Navigation
            $raintpl = $this->erzeugenNavigation($raintpl, $params);

            // Informationen der Stadt
            $raintpl = $this->informationenZurStadt($params, $raintpl);

            $this->view->content = $raintpl->draw( "Front_Ankuendigung_Index", true );
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
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


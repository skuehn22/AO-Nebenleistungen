<?php
/**
* Der 'Neuling' kann seine Personendaten vervollständigen, so daß er eine Buchung abschliessen kann
*
* + Initialisierung der Model und Kontrolle auf Sicherheit
* + Darstellen der View / Formular Personendaten während des Erststart
* + Ermitteln der Kundendaten während des Erststart
* + speichert die veränderten Kundendaten in 'tbl_adressen'
* + Speichert die veränderten persönlichen Daten des Kunden
* + Kontrolliert die zu speichernden Daten
*
* @date 13.47.2013
* @file PersonalienController.php
* @package front
* @subpackage controller
*/
class Front_PersonalienController extends Zend_Controller_Action implements nook_ToolCrudController
{

    // Konditionen
    private $condition_benutzerrolle_neuling = 1;

    // Flags

    protected $realParams = array();
    protected $pimple = null;
    protected $anzeigeSpracheId = null;
    private $requestUrl = null;

    /**
     * Initialisierung der Model und Kontrolle auf Sicherheit
     *
     */
    public function init()
    {
        try {
            $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

            $this->pimple = new Pimple_Pimple();
            $this->requestUrl = $this->view->url();

            $this->pimple['frontModelPersonalData'] = function () {
                return new Front_Model_Personaldata();
            };

            $request = $this->getRequest();
            $this->realParams = $request->getParams();

            // Zugriff auf die Action
            $nutzungAction = array(
                'indexAction' => $this->condition_benutzerrolle_neuling,
                'editAction' => $this->condition_benutzerrolle_neuling,
                'deleteAction' => $this->condition_benutzerrolle_neuling,
                'viewAction' => $this->condition_benutzerrolle_neuling
            );

            $toolZugriffController = new nook_ToolZugriffController();
            $toolZugriffController
                ->setZugriffAction($nutzungAction)
                ->setActionName($this->realParams['action'])
                ->steuerungKontrolleZugriffAction();

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Darstellen der View / Formular Personendaten während des Erststart
     *
     * + ermitteln Kundendaten
     *
     */
    public function viewAction()
    {
        try {

            $rolleDesBenutzers = nook_ToolBenutzerrolle::getRolleDesBenutzers();
            if($rolleDesBenutzers > $this->condition_benutzerrolle_neuling)
                $this->_redirect("/front/orderdata/index/");

            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            // ermitteln Kundendaten
            $raintpl = $this->index($raintpl);

            $this->view->content = $raintpl->draw("Front_Personalien_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Ermitteln der Kundendaten während des Erststart
     *
     * + Bereich 'personaldata'
     * + Bereich 'titles'
     * + Bereich 'country'
     *
     * @param $raintpl
     * @return mixed
     */
    private function index(RainTPL $raintpl)
    {
        $frontModelKundendatenFactory = new Front_Model_KundendatenFactory('index', $this->anzeigeSpracheId);

        /** @var $variante Front_Model_KundendatenIndex */
        $variante = $frontModelKundendatenFactory->getVariante();

        $raintpl = $variante
            ->setPimple($this->pimple)
            ->servicecontainer()
            ->steuerungErmittlungDatenpersonenFormular($raintpl)
            ->getRainTpl();

        return $raintpl;
    }

    /**
     * speichert die veränderten Kundendaten in 'tbl_adressen'
     *
     * + Kontrolliert die Rolle des Benutzers
     * + Kontrolle der Daten
     * + speichern der Daten
     * + erneute Darstellung der Personendaten
     * + lenkt um zu /front/orderdata/index/
     */
    public function editAction()
    {
        try {
            $params = $this->realParams;

            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $params = $this->realParams;

            // Kontrolle der Daten
            $params = $this->checkParamsSave($params);

            // speichern der geänderten Daten
            $this->save($params);

            $this->_redirect("/front/orderdata/index/");
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Speichert die veränderten persönlichen Daten des Kunden
     *
     */
    private function save(array $params)
    {
        $frontModelKundendatenFactory = new Front_Model_KundendatenFactory('update', $this->anzeigeSpracheId);

        /** @var $variante Front_Model_KundendatenUpdate */
        $variante = $frontModelKundendatenFactory->getVariante();

        $variante
            ->setFormularDaten($params)
            ->steuerungUpdateKundendaten();

        return;
    }

    /**
     * Kontrolliert die zu speichernden Daten
     *
     * @param $params
     * @return array
     */
    private function checkParamsSave($params)
    {
        $frontModelKundendatenKontrolle = new Front_Model_KundendatenKontrolle();
        $params = $frontModelKundendatenKontrolle->kontrolleKundendaten($params);

        return $params;
    }

    public function deleteAction()
    {
    }

    public function indexAction()
    {
    }
}
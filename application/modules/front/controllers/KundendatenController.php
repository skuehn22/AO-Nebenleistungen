<?php
/**
 * Darstellung der Personendaten eines Benutzers.
 *
 * + Initialisierung der Model und Kontrolle auf Sicherheit
 * + Darstellen der View / Formular Personendaten während des Erststart
 * + Ermitteln der Kundendaten während des Erststart
 * + speichert die veränderten Kundendaten in 'tbl_adressen'
 * + Speichert die veränderten persönlichen Daten des Kunden
 * + Kontrolliert die zu speichernden Daten
 *
 * @date 05.15.2013
 * @file PersonendatenController.php
 * @package front
 * @subpackage controller
 */
class Front_KundendatenController extends Zend_Controller_Action implements nook_ToolCrudController
{

    // Konditionen
    private $condition_benutzerrolle_neuling = 2;

    // Flags
    private $flagUpdateKundendaten = 1;

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
     */
    public function viewAction()
    {
        $params = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $raintpl = $this->index($raintpl);

            $this->view->content = $raintpl->draw("Front_Kundendaten_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Ermitteln der Kundendaten während des Erststart
     *
     * + Bereich 'personaldata'
     * + Bereich 'titles'
     * + Bereich 'country'
     * + anzeigen Information Update Kundendaten
     *
     * @param $raintpl
     * @return mixed
     */
    private function index(RainTPL $raintpl)
    {
        /** @var $variante Front_Model_KundendatenIndex */
        $frontModelKundendatenFactory = new Front_Model_KundendatenFactory('index', $this->anzeigeSpracheId);
        $variante = $frontModelKundendatenFactory->getVariante();

        $raintpl = $variante
            ->setPimple($this->pimple)
            ->servicecontainer()
            ->steuerungErmittlungDatenpersonenFormular($raintpl)
            ->getRainTpl();

        // anzeigen Information Update Kundendaten
        $raintpl->assign('updateKundendatenErfolgt', $this->flagUpdateKundendaten);

        return $raintpl;
    }

    /**
     * speichert die veränderten Kundendaten in 'tbl_adressen'
     *
     * + Kontrolle der Daten
     * + speichern der Daten
     * + erneute Darstellung der Personendaten
     */
    public function editAction()
    {
        $params = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $params = $this->realParams;

            // Kontrolle der Daten
            $params = $this->checkParamsSave($params);

            // speichern der geänderten Daten
            $this->save($params);

            // löschen alter Datensatz 'tbl_xml_kundendaten' und anlegen neuer Datensatz
            $this->updateTabelleXmlKundendaten($params);

            // gehe zu Erststart
            $this->flagUpdateKundendaten = 2;
            $raintpl = $this->index($raintpl);

            $this->view->content = $raintpl->draw("Front_Kundendaten_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Löscht Datensatz 'tbl_xml_kundendaten' und neu anlegen in 'tbl_xml_kundendaten'. XML Kundendaten
     *
     * + löschen Datensatz 'tbl_xml_kundendaten'
     * + anlegen Datensatz 'tbl_xml_kundendaten'
     *
     * @param array $params
     */
    private function updateTabelleXmlKundendaten(array $kundenDaten)
    {
        $kundenId = nook_ToolKundendaten::findKundenId();

        $toolCountry = new nook_ToolLand();
        $kundenDaten['country'] = $toolCountry->convertLaenderIdNachLandName($kundenDaten['country']);

        $modelWarenkorbPersonaldataXml = new Front_Model_WarenkorbPersonalDataXML();
        $kundeVorhanden = $modelWarenkorbPersonaldataXml->checkExistPersonalDataXml($kundenId);
        if (!$kundeVorhanden) {
            $modelWarenkorbPersonaldataXml
                ->setKundenDaten($kundenId, $kundenDaten)
                ->saveKundenDatenXML();
        }

        return;
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
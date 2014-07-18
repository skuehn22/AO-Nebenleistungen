<?php

/**
 * Der Benutzer erhält eine Übersicht über die bereits gebuchten Programme und Zimmer
 *
 * @author Stephan Krauss
 * @date 03.07.2014
 * @package front
 * @subpackage controller
 */
class Front_ReisekorbController extends Zend_Controller_Action
{

    private $realParams = null;
    private $pimple = null;
    protected $requestUrl = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
        $this->pimple = new Pimple_Pimple();
    }

    /**
     * Test des laden eines Subtemplate
     */
    public function indexAction()
    {
        $raintpl = raintpl_rainhelp::getRainTpl();

        $this->view->content = $raintpl->draw("Front_Reisekorb_Index", true);
    }

    /**
     * Ermittelt die Werte für den Reisekorb
     */
    public function readAction()
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();
            $this->getResponse()->clearBody();

//            $params = array();
//            $params['buchungsNummerId'] = nook_ToolBuchungsnummer::findeBuchungsnummer();
//            $params['anzeigeSpracheId'] = nook_ToolSprache::ermittelnKennzifferSprache();
//
//            // Kontrolle Parameter
//            $params = $this->checkSelectParams($params);
//
//            // Servicecontainer
//            $this->pimple['tabelleProgrammbuchung'] = function () {
//                return new Application_Model_DbTable_programmbuchung();
//            };
//
//            $this->pimple['tabelleProgrammbeschreibung'] = function(){
//                return new Application_Model_DbTable_programmbeschreibung();
//            };
//
//            $this->pimple['tabellePreiseBeschreibung'] = function(){
//                return new Application_Model_DbTable_preiseBeschreibung();
//            };
//
//            $this->pimple['tabelleHotelbuchung'] = function () {
//                return new Application_Model_DbTable_hotelbuchung();
//            };
//
//            // Buchungsdaten Programmbuchung
//            $this->pimple = $this->ermittelnProgrammbuchung($params, $this->pimple);
//
//            // Buchungsdaten Hotelbuchung
//            $this->pimple = $this->ermittelnHotelbuchung($params, $this->pimple);

//            $daten = array(
//                'datenProgrammbuchung' => $this->pimple['datenProgrammbuchung'] ,
//                'datenHotelbuchung' => $this->pimple['datenHotelbuchung']
//            );

            $daten = array(
                'author' => 'Joe Bloggs',
                'date' => '25th May 2013',
                'post' => 'This is the contents of my post'
            );

            $daten = json_encode($daten);
            echo $daten;
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Kontrolliert die ankommenden Parameter
     *
     * @param $params
     * @return mixed
     * @throws nook_Exception
     */
    protected function checkSelectParams($params)
    {
        if (!filter_var($params['buchungsNummerId'], FILTER_SANITIZE_NUMBER_INT))
            throw new nook_Exception('ID Buchungsnummer falsch');

        if (!filter_var($params['anzeigeSpracheId'], FILTER_SANITIZE_NUMBER_INT))
            throw new nook_Exception('ID Anzeigesprache falsch');

        return $params;
    }

    /**
     * Ermitteln der Grunddaten der Programmbuchungen eines Warenkorbes
     *
     * @param $params
     * @param $pimple
     * @return mixed
     */
    protected function ermittelnProgrammbuchung($params, $pimple)
    {
        $frontModelProgrammbuchungGrunddaten = new Front_Model_ProgrammbuchungGrunddaten();
        $pimple['datenProgrammbuchung'] = $frontModelProgrammbuchungGrunddaten
            ->setPimple($pimple)
            ->setBuchungsNummerId($params['buchungsNummerId'])
            ->setAnzeigeSpracheId($params['anzeigeSpracheId'])
            ->steuerungErmittlungGrunddatenProgrammbuchung()
            ->getGrunddatenProgrammbuchung();

        return $pimple;
    }

    /**
     * Ermittelt die Grunddaten der Hotelbuchungen eines Reisekorbes
     *
     * @param $params
     * @param $pimple
     * @return mixed
     */
    protected function ermittelnHotelbuchung($params, $pimple)
    {
        $frontModelProgrammbuchungGrunddaten = new Front_Model_HotelbuchungGrunddaten();
        $pimple['datenHotelbuchung'] = $frontModelProgrammbuchungGrunddaten
            ->setPimple($pimple)
            ->setBuchungsNummerId($params['buchungsNummerId'])
            ->setAnzeigeSpracheId($params['anzeigeSpracheId'])
            ->steuerungErmittlungGrunddatenHotelbuchung()
            ->getGrunddatenHotelbuchung();

        return $pimple;
    }
}
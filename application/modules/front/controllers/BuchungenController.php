<?php
/**
* Zeigt die bereits getätigten Buchungen eines Kunden an
*
* + Darstellen der Buchungsliste
* + Ermittel der bereits getätigten Buchungen des Kunden
* + Ermitteln der Kunden ID
*
* @author Stephan.Krauss
* @date 02.05.13
* @file BuchungenController.php
* @package front
* @subpackage controller
*/
class Front_BuchungenController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $_realParams = array();
    private $pimple = null;
    private $requestUrl = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();

        $this->pimple = $this->getInvokeArg('bootstrap')->getResource('Container');
    }

    /**
     * Darstellen der Buchungsliste
     */
    public function indexAction()
    {
        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Kunden ID
            $kundenId = $this->_ermittelnKundendatenAuth();

            // ermitteln Buchungen
            $buchungen = $this->_ermittelnBuchungen($kundenId);
            $raintpl->assign('buchungen', $buchungen);

            $this->view->content = $raintpl->draw("Front_Buchungen_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }

    }

    /**
     * Ermittel der bereits getätigten Buchungen des Kunden
     *
     * + Buchungen des Kunden
     * + es können nur Buchungen mit aktiven Artikeln aufgerufen werden
     *
     * @param $kundenId
     * @return array|bool
     */
    private function _ermittelnBuchungen($kundenId)
    {
        $modelBuchungen = new Front_Model_Buchungen();
        if ($modelBuchungen->validateKundenId($kundenId)) {
            $buchungen = $modelBuchungen
                ->setKundenId($kundenId)
                ->steuernErmittelnBuchungen()
                ->getBuchungsHistorie();
        }

        return $buchungen;
    }

    /**
     * Ermitteln der Kunden ID
     *
     * @return int
     */
    private function _ermittelnKundendatenAuth()
    {
        $kundenId = nook_ToolKundendaten::findKundenId();

        return $kundenId;
    }

    public function editAction()
    {
    }

    public function deleteAction()
    {
    }

    public function neueBuchungAction()
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelNeueBuchungsnummer = Front_Model_NeueBuchungsnummer::getInstance($this->pimple);

            $modelNeueBuchungsnummer
                ->aktiveBuchungLoeschen()
                ->neueBuchungsnummerAnlegen();

            $this->_redirect("/front/login");
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

}


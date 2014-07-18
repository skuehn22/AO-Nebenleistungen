<?php
/**
 * Der Benutzer kann Formulare und Informationen als Pdf downloaden
 *
 * @author Stephan Krauss
 * @date 27.02.2014
 * @package front
 * @subpackage controller
 */
class Front_ServiceController extends Zend_Controller_Action{

    private $realParams = array();
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    /**
     * Parent Controller. Darstellung des Templare 'Front_Service_Index.html'
     */
    public function indexAction(){
        try{
        	$raintpl = raintpl_rainhelp::getRainTpl();

            // Sprache
            $nookToolSprache = new nook_ToolSprache();
            $kennzifferSprache = $nookToolSprache->ermittelnKennzifferSprache();

            $raintpl->assign('anzeigesprache', $kennzifferSprache);

            $this->view->content = $raintpl->draw( "Front_Service_Index", true );
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }
}


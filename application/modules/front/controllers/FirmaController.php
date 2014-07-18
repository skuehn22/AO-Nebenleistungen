<?php
/**
 * Darstellung allgemeiner Informationen zur Firma
 *
 * @author Stephan.Krauss
 * @date 26.14.2013
 * @file FirmaController.php
 * @package front
 * @subpackage controller
 */
class Front_FirmaController extends  Zend_Controller_Action{

    private $_realParams = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    public function indexAction(){
        $params = $this->realParams;

        try{
            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $this->view->content = $raintpl->draw( "Front_Firma_Index", true );
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }


} // end class

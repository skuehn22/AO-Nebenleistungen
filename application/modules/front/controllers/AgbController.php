<?php
/**
 * Anzeige der AGB entsprechend der Anzeigesprache
 *
 * @author Stephan.Krauss
 * @date 16.10.2013
 * @file AgbController.php
 * @package front
 * @subpackage controller
 */
class Front_AgbController extends  Zend_Controller_Action{

    private $requestUrl = null;
    private $_realParams = null;
    protected $flagBlockZustimmenAgb = false;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    public function indexAction(){
        $params = $this->realParams;

        try{
            $this->flagBlockZustimmenAgb = false;

            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $raintpl->assign('blockZustimmenAgb', $this->flagBlockZustimmenAgb);

            $template = $raintpl->draw( "Front_Agb_Index", true );

            $this->view->content = $template;
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Zeigt den Inhalt der AGB an. Anzeige ohne Layout
     */
    public function fensterAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $this->flagBlockZustimmenAgb = true;

            $raintpl = raintpl_rainhelp::getRainTpl();

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $raintpl->assign('blockZustimmenAgb', $this->flagBlockZustimmenAgb);

            $agbContent = $raintpl->draw( "Front_Agb_Index", true );
            echo $agbContent;
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}

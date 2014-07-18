<?php
/**
 * Dem Benutzer werden die Premiumprogramme einer Stadt angezeigt
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package front
 * @subpackage controller
 */
class Front_VorschlagController extends  Zend_Controller_Action{

    private $requestUrl = null;
    private $realParams = null;
    protected $raintpl = null;

    // Konditionen
    protected $condition_premium_programme_typ = 1;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();

        $frontModelServiceAustria = new Front_Model_ServiceTemplate();
        $service = $frontModelServiceAustria->getServiceTemplate();
        $raintpl = raintpl_rainhelp::getRainTpl();
        $raintpl->assign('service', $service);
        $this->raintpl = $raintpl;
    }

    /**
     * zeigt die Premiumprogramme einer Stadt an
     */
    public function viewAction(){
        $params = $this->realParams;

        try{
            $raintpl = $this->raintpl;

            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $anzeigesprache);

            $pimple = new Pimple_Pimple();
            $pimple['cityId'] = $params['city'];
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

            $raintpl->assign('programme', $premiumProgrammeEinerStadt);

            $template = $raintpl->draw( "Front_Vorschlag_View", true );

            $this->view->content = $template;
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
} // end class

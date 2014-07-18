<?php
/**
 * Bestätigung der Registrierung eines Kunden
 *
 * @author Stephan.Krauss
 * @date 21.01.13
 * @file AntwortController.php
 */

class Front_AntwortController extends Zend_Controller_Action implements nook_ToolCrudController {

    private $_realParams = null;
    private $requestUrl = null;

    public function init(){
        try{
            $request = $this->getRequest();
            $this->realParams = $request->getParams();

            $this->requestUrl = $this->view->url();
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Erststart des System
     * 
     * @return void
     */
    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();

        try{
            $raintpl = raintpl_rainhelp::getRainTpl();

            $modelAntwort = new Front_Model_Antwort();
            $modelAntwort
                ->setKontrollCode($params['anmeldung'])
                ->bestaetigungAnmeldung();



            $this->view->content = $raintpl->draw( "Front_Antwort_Index", true );
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    public function editAction(){}

    public function deleteAction(){}




}

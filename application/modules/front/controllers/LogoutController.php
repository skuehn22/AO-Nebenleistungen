<?php
/**
 * Logout vom System
 *
 * +
 *
 * @author Stephan.Krauss
 * @date 16.01.13
 * @file LogoutController.php
 */

class Front_LogoutController extends Zend_Controller_Action implements nook_ToolCrudController {

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

            $model = new Front_Model_Logout();
            $model->abmelden();

            $this->view->content = $raintpl->draw( "Front_Logout_Index", true );
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    public function editAction(){}

    public function deleteAction(){}




}

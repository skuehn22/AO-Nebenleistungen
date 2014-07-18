<?php
/**
 * Fehlerbereich:
 * Beschreibung der Klasse
 *
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 */

class Front_BuchungsuebersichtController extends Zend_Controller_Action{
	
	private $_session;
    private $requestUrl = null;
    private $_realParams = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }


    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();
		
		try{
			$raintpl = raintpl_rainhelp::getRainTpl();
			$model = new Front_Model_Buchungsuebersicht();
            $model->buchenHotelprodukteWarenkorb();

            echo 'Darstellung des Inhaltes des Warenkorbes';
            exit();
			

			// verwende Templat des Warenkorbes
			// $this->view->content = $raintpl->draw( "Front_Transport_Transfer", true );
		}
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}


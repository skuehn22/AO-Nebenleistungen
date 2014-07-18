<?php
/**
 * Eingabe allgemeiner Buchungsdaten
 * zur Übernachtungssuche
 * in einer Stadt
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 */

class Front_HotelsearchController extends Zend_Controller_Action{

    private $_realParams = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();
    }

    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();

		try{
            $portalbereich = new Zend_Session_Namespace('portalbereich');
            $portalbereich->bereich = 'hotels';

            $selectdCityId = false;
            if(array_key_exists('city', $params))
                $selectdCityId = $params['city'];

			$raintpl = raintpl_rainhelp::getRainTpl();
			$model = new Front_Model_Hotelsearch();

            // Breadcrumb
			$navigation = $model->getNavigationCrumb(6, 2, $params);
            $raintpl->assign('breadcrumb', $navigation);

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            // Stadtname
            $ort = nook_Tool::findCityNameById($params['city']);
            $raintpl->assign('ort', $ort);

            // Übernachtungen / Highlight der Stadt
            $uebernachtungenStadt = $model->getHighlightUebernachtungDerStadt($selectdCityId);
            $raintpl->assign('uebernachtungenStadt', $uebernachtungenStadt);

            $raintpl->assign('city', $params['city']);

			$this->view->content = $raintpl->draw( "Front_Hotelsearch_Index", true );
		}
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}


<?php
/**
 * Suchmaschine zum suchen von Programmen in einer Stadt
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package front
 * @subpackage controller
 */
class Front_ProgrammsucheController extends Zend_Controller_Action
{

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
			$raintpl = raintpl_rainhelp::getRainTpl();
			$raintpl->assign('city', 'leer');

			$model = new Front_Model_Programmsuche();
			if(!empty($params['searchTerm'])){
				$searchResult = $model->setSearchTerm($params['searchTerm']);

				if(is_array($searchResult) and count($searchResult) >= 1){

					$modelStart = new Front_Model_Programmstart();
					$searchResult = $modelStart->styleItems($searchResult);

					$raintpl->assign("cityEvents", $searchResult);
					$raintpl->assign("actualPageNumber", 1);
					$raintpl->assign("Ort", '');
				}
			}
			
			// Templat
			$this->view->content = $raintpl->draw( "Front_Programmstart_Index", true );
		}
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }


}


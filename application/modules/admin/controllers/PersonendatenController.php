<?php
/**
 * Darstellung und Änderung ausgewählter Personendaten
 *
 * @author Stephan.Krauss
 * @date 13.58.2013
 * @file PersonendatenController.php
 * @package admin
 * @subpackage controller
 */
class Admin_PersonendatenController extends Zend_Controller_Action implements nook_ToolCrudController{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        try{



        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
    }
	
	public function indexAction(){
        $request = $this->getRequest();
        $params = $request->getParams();
		
        try{
        	$raintpl = raintpl_rainhelp::getRainTpl();

        	$this->view->content = $raintpl->draw( "Admin_Personendaten_Index", true );
        }
        catch (Exception $e){
        	$e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
			Zend_Session::destroy();
			$this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
	}

    /**
     * Lädt die Datensätze in Tabelle Personen in der View
     */
    public function showAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(!array_key_exists('start', $params))
                $params['start'] = 0;
            if(!array_key_exists('limit', $params))
                $params['limit'] = 10;

            $model = new Admin_Model_Personendaten();
            $personendaten = $model
                ->setStartwerte($params['start'], $params['limit'])
                ->setSuchparameter($params)
                ->getPersonendaten();

            $anzahl = $model->getAnzahlDatensaetze();

            echo "{success: true, data: ".json_encode($personendaten).", anzahl: ".$anzahl."}";


        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
    }

    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();


        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
    }

    public function deleteAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();



        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
    }

    public function rolleAendernAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelPersonendaten = new Admin_Model_Personendaten();
            $anzahlGeaenderteDatensaetze = $modelPersonendaten
                ->setIdKunde($params['idKunde'])
                ->setRolleKunde($params['rolle'])
                ->aendernBenutzerRolle();

            if( ($anzahlGeaenderteDatensaetze == 1) or ($anzahlGeaenderteDatensaetze == 0) )
                echo "{success: true}";
            else
                echo "{success: false}";
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e->getMessage());
        }
    }
}

?>
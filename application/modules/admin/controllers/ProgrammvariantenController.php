<?php

class Admin_ProgrammvariantenController extends Zend_Controller_Action
{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $this->view->content = $raintpl->draw("Admin_Programmvarianten_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gridprogrammvariantenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $gridProgrammvarianten = new Admin_Model_ProgrammvariantenGrid();

            $model = new Admin_Model_Programmvarianten();
            $model
                ->setDependency('grid', $gridProgrammvarianten);

            // löschen
            if(array_key_exists('deleteId', $params))
                $model->loeschenPreisvariante($params['deleteId']);

            // laden / blättern
            if(!array_key_exists('start', $params)){
                $start = 0;
                $limit = 10;
            }
            else{
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $gridRows = $model->getPreisvarianten($start, $limit);
            $anzahl = $model->getAnzahlDatensaetze();

            echo "{succss: true, data: ".json_encode($gridRows).", anzahl: ".$anzahl."}";


        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function formprogrammvariantenAction(){
        $request = $this->getRequest();
        $formularwerte = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $formPreise = new Admin_Model_ProgrammvariantenForm();
            $model = new Admin_Model_Programmvarianten();
            $model->setDependency('form', $formPreise);

            if(array_key_exists('loadId', $formularwerte) and !empty($formularwerte['loadId'])){
                $datenPreisvariante = $model ->getFormData($formularwerte['loadId']);
                echo "{success: true, data: ". json_encode($datenPreisvariante) ."}";
            }
            elseif(array_key_exists('id', $formularwerte) and !empty($formularwerte['id'])){
                $model->updatePreisvariante($formularwerte);
            }
            else{
                $model->setFormData($formularwerte);
                echo "{success: true}";
            }
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }
}
<?php
/**
 * Beschreibung der Klasse
 *
 * Verwaltet die Stornofristen eines Programmes
 *
 *
 * @author Stephan Krauß
 */

class Admin_DatensatzStornofristenController extends Zend_Controller_Action{

    private $_realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Anzeige der Stornofristen
     */
    public function viewAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzStornofristen();
            $stornofristen = $model
                ->kontrolleProgrammId($params['programmdetailsId'])
                ->stornofristenEinesProgrammes();

            if(!empty($stornofristen))
                echo "{success: true, data: ".json_encode($stornofristen)."}";
            else
                echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Editieren der Stornofristen
     */
    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzStornofristen();
            $model
                ->setProgrammId($params['programmId'])
                ->kontrolleStornofristen($params)
                ->speichernStornofristen();

            echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }

    public function deleteAction(){}

}


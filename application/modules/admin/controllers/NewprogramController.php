<?php

class Admin_NewprogramController extends Zend_Controller_Action
{
    private $_realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Erststart des Baustein 'newprogram'
     */
    public function indexAction()
    {

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Newprogram_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Gibt die bereits vorhandenen Firmen
     * der Programmanbieter zurück
     */
    public function getexistingcompaniesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $searchField = false;

            $model = new Admin_Model_Newprogram();

            if (!empty($params['searchField']))
                $searchField = $params['searchField'];

            $model->searchField = $searchField;

            // ermittelt die Anzahl der bereits vorhandenen Firmen
            $anzahl = $model->getCountCompanies();

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $ausgabe = $model->getCompanies($start, $limit);

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Fügt ein neues Programm zur Firma hinzu
     */
    public function addnewprogramAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Newprogram();
            $model->saveNewProgram($params);

            if (empty($model->fieldErrors))
                echo "{success: true}";
            else
                echo "{success: false, errors: " . json_encode($model->fieldErrors) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcitiesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Newprogram();
            $cities = $model->getCities();

            echo "{success: true, data: " . json_encode($cities) . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


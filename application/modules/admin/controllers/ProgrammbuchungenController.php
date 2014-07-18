<?php

class Admin_ProgrammbuchungenController extends Zend_Controller_Action
{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Darstellung des parent View der gebuchten
     * Programme
     *
     */
    public function indexAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Programmbuchungen_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die verfügbaren Programmbuchungen.
     * Über Parameter kann gezielt nach Buchungen gesucht werden
     *
     * @return void
     */
    public function getprogrammbuchungenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Programmbuchungen();
            $programme = $model->setParameterTabelleProgrammbuchungen($params);

            echo "{success: true, data: " . json_encode($programme['gebuchteProgramme']) . ", anzahl: " . $programme['anzahl'] . "}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Grunddaten eines Programmes
     * mittels der Prrogramm ID
     *
     */
    public function programmgrunddatenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Programmbuchungen();
            $programmId = $model->checkProgrammId($params['programmBuchungId']);
            $programmBuchungGrunddaten = $model->getProgrammGrundDaten($programmId);

            echo "{success: true, data: " . json_encode($programmBuchungGrunddaten) . "}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Verändert den Status eines Programmes
     *
     * @return void
     */
    public function setstatusAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Programmbuchungen();
            $model->setParameterNeuerStatus($params);

            echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}
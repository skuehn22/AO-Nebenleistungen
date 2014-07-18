<?php

class Admin_HauptschalterController extends Zend_Controller_Action implements nook_ToolCrudController{

    private $_realParams = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * stellt Templat dar
     */
    public function indexAction(){
        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Hauptschalter_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * listet die vorhandenen Hotels auf
     */
    public function viewAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hauptschalter();
            $model->setStartpunkt($params);

            $anzahl = $model->getAnzahlHotels();
            $hotels = $model->getHotels();

            if (empty($errors))
                echo "{success: true, data: " . json_encode($hotels) . ", anzahl: ".$anzahl."}";
            else
                echo "{success: false, errors: " . json_encode($errors) . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * verändert Status des Hotels
     * + aktiv
     * + passiv
     *
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hauptschalter();
            $control = $model->setStatusHotel($params);

            if (!empty($control))
                echo "{success: true}";
            else
                echo "{success: false}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function deleteAction(){}

}


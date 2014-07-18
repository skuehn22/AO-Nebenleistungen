<?php

class Admin_StadtbeschreibungController extends Zend_Controller_Action implements nook_ToolCrudController
{
    private $_realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Stellt Templat dar
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $raintpl->assign('user', 10);

            $this->view->content = $raintpl->draw("Admin_Stadtbeschreibung_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die vorhandenen Städte
     */
    public function storestadtbeschreibungAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Stadtbeschreibung();
            $staedte = $model->getStaedte();

            echo "{success: true, data: ".json_encode($staedte)."}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Beschreibungen einer Stadt
     */
    public function viewAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Stadtbeschreibung();
            $stadtbeschreibung = $model->getstadtbeschreibung($params['city'], $params['sprache']);

            echo "{success: true, data: ".json_encode($stadtbeschreibung)."}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichern der Stadtbeschreibung
     */
    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $adminModelStadtbeschreibung = new Admin_Model_Stadtbeschreibung();
            $params = $adminModelStadtbeschreibung->map($params);
            $adminModelStadtbeschreibung->setstadtbeschreibung($params);

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function deleteAction(){

    }
}


<?php

class Admin_PermanentzusatzartikelController extends Zend_Controller_Action
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

            $this->view->content = $raintpl->draw("Admin_Permanentzusatzartikel_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Admin_PermanentzusatzartikelController::tabelitemsAction()
     *
     * @return void
     */
    public function tabelitemsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Permanentzusatzartikel();
            $anzahl = $model->getCountPrograms();

            if (array_key_exists('limit', $params))
                $ausgabe = $model->getTableItems($params['start'], $params['limit']);
            else
                $ausgabe = $model->getTableItems();

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


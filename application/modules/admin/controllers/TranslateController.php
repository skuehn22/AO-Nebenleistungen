<?php
/**
* Steuert die Übersetzung der Textbausteine in den Ansichten der Front - Bausteine
*
* + Holt die vorhandenen Übersetzungen
* + Trägt die Übersetzung
*
* @date 18.10.2013
* @file TranslateController.php
* @package admin
* @subpackage controller
*/
class Admin_TranslateController extends Zend_Controller_Action
{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    public function indexAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $this->view->content = $raintpl->draw("Admin_Translate_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e->getMessage());
        }
    }

    public function deleteAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelTranslate = new Admin_Model_Translate();
            $modelTranslate
                ->setDeleteId($params['deleteId'])
                ->loescheTranslate();

            echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e->getMessage());
        }
    }


    /**
     * Holt die vorhandenen Übersetzungen
     */
    public function gettranslateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelTranslate = new Admin_Model_Translate();

            $anzahl = $modelTranslate
                ->setSucheBaustein($params['baustein'])
                ->setSucheBegriff($params['begriff'])
                ->setTranslateId($params['translateId'])
                ->getCountTranslate();

            $start = 0;
            $limit = 20;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $uebersetzungen = $modelTranslate->getTranslate($start, $limit);

            echo "{success: true, data: " . json_encode($uebersetzungen) . ", anzahl: " . $anzahl . "}";
        }
       catch (Exception $e) {

            if ($e->getMessage() == $modelTranslate->error_test){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
            }


        }
    }

    /**
     * Trägt die Übersetzung
     * in Tabelle 'tbl_translate' ein.
     *
     */
    public function setuebersetzungAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Translate();
            $kontrolle = $model->setUebersetzung($params);

            if ($kontrolle)
                echo "{success: true}";
        }
       catch (Exception $e) {

            if ($e->getMessage() == $model->error_test){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
            }
        }
    }
}


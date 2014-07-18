<?php
/**
 * Notschalter des System. Der Administrator kann die Buchung von Programmen und Übernachtungen stoppen
 *
 * @author Stephan.Krauss
 * @date 22.43.2013
 * @file NotschalterController.php
 * @package admin
 * @subpackage controller
 */
class Admin_NotschalterController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $realParams = array();
    private $pimple = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->serviceContainer();
    }

    /**
     * Bereitet den Servicecontainer vor
     *
     */
    public function serviceContainer(){
        $this->pimple = new Pimple_Pimple();

        $this->pimple['tabelleSystemparameter'] = function(){
            return new Application_Model_DbTable_systemparameter();
        };

        return;
    }

    /**
     * Darstellen Template
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Notschalter", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
       }
    }

    /**
     * Übernimmt die Werte zum editieren der Systemvariablen
     *
     */
    function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            $adminModelNotschalter = new Admin_Model_Notschalter();
            $adminModelNotschalter
                ->setPimple($this->pimple)
                ->setNotschalter($params)
                ->steuerungUpdateNotschalter();

            echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    function deleteAction(){}

    /**
     * Lädt das Formular während des Erststart
     *
     */
    function viewAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $adminModelNotschalter = new Admin_Model_Notschalter();
            $notschalter = $adminModelNotschalter
                ->setPimple($this->pimple)
                ->steuerungErmittlungNotschalter()
                ->getNotschalter();

            echo "{success: true, data: " . json_encode($notschalter)."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

}


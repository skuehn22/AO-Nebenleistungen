<?php
/**
 * Userstory der Klasse
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package admin
 * @subpackage controller
 */

class Admin_ZusatzartikelController extends Zend_Controller_Action
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

            $this->view->content = $raintpl->draw("Admin_Zusatzartikel_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function tabelitemsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Zusatzartikel();
            $anzahl = $model->getCountPrograms();

            $progsearch = false;
            $start = 0;
            $limit = 20;

            if (array_key_exists('progsearch', $params))
                $progsearch = $params['progsearch'];

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $ausgabe = $model->getTableItems($start, $limit, $progsearch);

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function updaterecordAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['progname']);

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Zusatzartikel();
            $model->setZusatzartikelItems($params);


            echo "{success: true}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }
}


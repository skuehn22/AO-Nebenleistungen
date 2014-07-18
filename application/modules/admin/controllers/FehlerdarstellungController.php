<?php
/**
 * Darstellung der Fehlermeldungen der Wächter
 *
 * + Fehlernummer
 * + Datum
 * + Angaben zum Fehler
 *
 * @author stephan.krauss
 * @date 03.06.13
 * @file FehlerdarstellungController.php
 * @package admin
 * @subpackage controller
 */

class Admin_FehlerdarstellungController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $this->view->content = $raintpl->draw("Admin_Fehlerdarstellung_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getfehlermeldungenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Fehlerdarstellung();
            $anzahl = $model->getCountFehlermeldungen();

            $start = 0;
            $limit = 20;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $fehlermeldungen = $model->getFehlermeldungen($start, $limit);

            echo "{success: true, data: " . json_encode($fehlermeldungen) . ", anzahl: " . $anzahl . "}";
        }
       catch (Exception $e) {

            if ($e->getMessage() == $model->error_test){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
            }
        }
    }
}


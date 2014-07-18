<?php
/**
 * Verwaltung der Zugangskontrolle zu den Action der Controller
 *
 * Administration der Benutzerrechte
 * zu den Action aller Controller.
 * Individuelles schalten der Rechte an den Action
 * durch den Administrator
 *
 * @author Stephan.Krauss
 * @date 14.03.13
 * @file ZugangskontrolleController.php
 * @package admin
 * @subpackage controller
 */


class Admin_ZugangskontrolleController extends Zend_Controller_Action{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Anzeigen Tabelle und Formular
     */
    public function indexAction(){
        $params = $this->realParams;
        try{

//            /*** Start Profiling ***/
//            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);


            $raintpl = raintpl_rainhelp::getRainTpl();
            $modelZugangsKontrolle = new Admin_Model_Zugangskontrolle();
            $javascriptRollen = $modelZugangsKontrolle->ermittelnRollen();

            $raintpl->assign('javascriptRollen', $javascriptRollen);
            $this->view->content = $raintpl->draw("Admin_Zugangskontrolle_Index", true);


//            /*** Stop Profiling ***/
//            $xhprof_data = xhprof_disable();


//            define('XHPROF_LIB_ROOT', 'c:/xampp/htdocs/hob/library/xhprof/xhprof_lib/');
//            include_once('xhprof/xhprof_lib/config.php');
//            include_once('xhprof/xhprof_lib/utils/xhprof_lib.php');
//            include_once('xhprof/xhprof_lib/utils/xhprof_runs.php');
//
//            $xhprof_runs = new XHProfRuns_Default();
//            $run_id =$xhprof_runs->save_run($xhprof_data, "xhprf_foo");
//
//            echo "<pre><a href='xhprof/xhprof_html/index.php?run=$run_id&source=xhprof_foo'>XHProf für diese Kontrolle</a>";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermitteln Bereiche, Controller und Action
     */
    public function ermittelnAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{

            $raintpl = raintpl_rainhelp::getRainTpl();

            $modelZugangskontrolle = new nook_ToolZugangskontrolle();
            $modelZugangskontrolle
                ->buildModulesArray()
                ->buildControllerArrays()
                ->buildActionArrays()
                ->datensatzZugangskontrolle();

            $this->view->content = $raintpl->draw("Admin_Zugangskontrolle_Index", true);
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }



    /**
     * lesen des Tabelleninhaltes
     */
    public function showAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(!array_key_exists('start', $params))
                $params['start'] = 0;

            $modelZugangsKontrolle = new Admin_Model_Zugangskontrolle();

            $data = $modelZugangsKontrolle
                ->setSucheBereich($params['sucheBereich'])
                ->setSucheController($params['sucheController'])
                ->setStartDatensaetze($params['start'])
                ->ermittelnDatensaetze()
                ->getDatenTabelleActions();

            $anzahlDatensaetze = $modelZugangsKontrolle
                ->ermittelnAnzahlDatensaetze()
                ->getAnzahlDatensaetze();

            echo "{success: true, data: ".json_encode($data).", anzahl: ".$anzahlDatensaetze."}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Updatet die Zugriffsrechte einer Action
     */
    public function updateZugriffsrechteAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelZugangsKontrolle = new Admin_Model_Zugangskontrolle();
            $params = $modelZugangsKontrolle->mapCheckboxen($params);
            $modelZugangsKontrolle->eintragenZugriffsrechteAction($params);

            echo "{success: true}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die Zugriffsrechte einer Action
     */
    public function ermittleZugriffsrechteAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelZugangsKontrolle = new Admin_Model_Zugangskontrolle();
            $zugriffsrechte = $modelZugangsKontrolle
                ->setIdAction($params['id'])
                ->ermittelnZugriffsrechteEinerAction()
                ->getZugriffsrechteEinerAction();

            echo "{success: true, data: ".json_encode($zugriffsrechte)."}";


        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function actionEintragenAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelZugangsKontrolle = new Admin_Model_Zugangskontrolle();
            $modelZugangsKontrolle->eintragenAllerAction();

            echo "{success: true}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


<?php
/**
 * Verwaltet die Standardkapazität eine Programmes
 *
 */

class Admin_DatensatzStandardKapazitaetController extends Zend_Controller_Action implements nook_ToolCrudController{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    public function indexAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();


        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Zeigt den Inhalt der Tabelle an
     */
    public function showAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(empty($params['start'])){
                $params['start'] = 0;
            }

            $modelProgrammKapazitaet = new Admin_Model_DatensatzStandardKapazitaet();
            $datensaetzeTabelle = $modelProgrammKapazitaet
                ->setProgrammId($params['programmId'])
                ->setStart($params['start'])
                ->getDatensaetzeTabelle();

            $anzahlDatensaetze = $modelProgrammKapazitaet->getAnzahlDatensaetze();

            echo "{success: true, data: ".json_encode($datensaetzeTabelle).", anzahl: ".$anzahlDatensaetze."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Eintragen der Kpazität eines Programmes
     * an einem bestimmten Tag und zu einer
     * bestimmten Zeit
     *
     */
    public function createAction(){
       $params = $this->realParams;

       try {
           $this->_helper->viewRenderer->setNoRender();
           $this->_helper->layout->disableLayout();

           $modelProgrammKapazitaet = new Admin_Model_DatensatzStandardKapazitaet();
           $eintragenKapazitaet = $modelProgrammKapazitaet
               ->setProgrammId($params['programmId'])
               ->setKapazitaet($params['kapazitaet'])
               ->setDatum($params['datum'])
               ->setZeit($params['zeit'])
               ->eintragenKapazitaet();

            if($eintragenKapazitaet)
                echo "{success: true}";
       }
       catch (Exception $e) {
           $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
           echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
       }
    }

    public function editAction(){}

    public function deleteAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelProgrammKapazitaet = new Admin_Model_DatensatzStandardKapazitaet();
            $modelProgrammKapazitaet->setLoeschendeKapazitaeten($params['loeschen'])->kapazitaetLoeschen();


            echo "{success: true}";


        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Eingabe der Standard - Kapazität eines Programmes
     */
    public function setDefaultAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelProgrammKapazitaet = new Admin_Model_DatensatzStandardKapazitaet();
            $success = $modelProgrammKapazitaet
                ->setProgrammId($params['programmId'])
                ->setKapazitaet($params['kapazitaet'])
                ->writeKapazitaet();

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Standard Kapazität
     * eines Programmes
     */
    public function getDefaultAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelProgrammKapazitaet = new Admin_Model_DatensatzStandardKapazitaet();
            $data = $modelProgrammKapazitaet->setProgrammId($params['programmId'])->getStandardKapazitaet();

            echo "{success: true, data: ".json_encode($data)."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


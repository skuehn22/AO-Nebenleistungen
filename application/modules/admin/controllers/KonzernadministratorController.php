<?php

class Admin_KonzernadministratorController extends Zend_Controller_Action
{

    private $_realParams = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * anzeigen Templat
     *
     */
    public function indexAction()
    {


        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Konzernadministrator_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Legt neuen Konzernadministrator an
     * + neuer Administrator
     * + update Daten Konzernverantwortlicher
     */
    public function newkonzernverantwortlicherAction()
     {
           $request = $this->getRequest();
           $datenKonzernVerantwortlicher = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               unset($datenKonzernVerantwortlicher['module']);
               unset($datenKonzernVerantwortlicher['controller']);
               unset($datenKonzernVerantwortlicher['action']);

               $model = new Admin_Model_Konzernadministratoren();

               // update
               if(array_key_exists('id', $datenKonzernVerantwortlicher) and !empty($datenKonzernVerantwortlicher['id'])){
                   $updateIdKonzernverantwortlicher = $datenKonzernVerantwortlicher['id'];
                   unset($datenKonzernVerantwortlicher['id']);

                   $model->updateDatenKonzernverantwortlicher($updateIdKonzernverantwortlicher, $datenKonzernVerantwortlicher);
               }
               // neu eintragen
               else{
                    $errors = $model->checkDoubleMailAdress($datenKonzernVerantwortlicher);
                    if(is_array($errors))
                        echo "{success: false, errors:" . json_encode($errors) . "}";
                    else{
                        $kontrolle = $model
                            ->setDatenKonzernVerantwortlicher($datenKonzernVerantwortlicher)
                            ->getKontrolleSpeichernDatenKonzernverantwortlicher();

                        echo "{success: true}";
                    }
               }
           }
          catch (Exception $e) {
               $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
               echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }
     }

    /**
     * Holt die Daten des
     * Konzernverantwortlichen
     */
    public function datenkonzernverantwortlicherAction()
     {
           $request = $this->getRequest();
           $suchdatenKonzernVerantwortlicher = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               $model = new Admin_Model_Konzernadministratoren();
               $data = $model->getDatenKonzernverantwortlicher($suchdatenKonzernVerantwortlicher);

               echo "{success: true, data: ".json_encode($data)."}";

           }
          catch (Exception $e) {
               $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
               echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }
     }

    /**
     * Füllt die Tabelle
     * der Konzernadministratoren
     */
    public function gridkonzernadministratorAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(!array_key_exists('start', $params)){
                $start = 0;
                $limit = 10;
            }
            else{
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $model = new Admin_Model_Konzernadministratoren();
            $administratoren = $model
                ->setDependency()
                ->setStartdaten($start, $limit)
                ->getKonzernAdministratoren();

            $anzahl = $model->getAnzahlAdministratoren();

            echo "{success: true, data: " . json_encode($administratoren) . ", anzahl: " . $anzahl . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gridhotelsAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(!array_key_exists('start', $params)){
                $start = 0;
                $limit = 10;
            }
            else{
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $model = new Admin_Model_Konzernadministratoren();
            $hotels = $model
                ->setDependency()
                ->setStartdaten($start, $limit)
                ->getHotels($params['id']);

            $anzahl = $model->getAnzahlHotels();

            echo "{success: true, data: " . json_encode($hotels) . ", anzahl: " . $anzahl . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function sethotelsAction(){
        $request = $this->getRequest();
        $parameterGewaehlteHotels = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $parameterGewaehlteHotels['hotels'] = json_decode($parameterGewaehlteHotels['hotels']);

            $model = new Admin_Model_Konzernadministratoren();
            $model
                ->setDataGewaehlteHotels($parameterGewaehlteHotels);

            echo "{success: true}";


        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function loeschehotelsAction(){
        $request = $this->getRequest();
        $loeschParameter = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $loeschParameter['hotels'] = json_decode($loeschParameter['hotels']);

            $model = new Admin_Model_Konzernadministratoren();
            $kontrolleLoeschen = $model
                ->setDatenZumLoeschenHotels($loeschParameter)
                ->getKontrolleLoeschenZugeordneteHotels();

            if($kontrolleLoeschen)
                echo "{success: true}";
            else
                echo "{success: false}";


        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}
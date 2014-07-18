<?php

class Admin_AvailableratesController extends Zend_Controller_Action
{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Stellt Templat zur Verfügung
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $this->view->content = $raintpl->draw("Admin_Availablerates_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die Raten und Kategorien
     * eines Hotels.
     *
     * + Suchparameter werden berücksichtigt
     */
    public function getratesandcategoriesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Pattern
            $zugriff = new nook_ZugriffAufHotels();
            $zugriffVars = $zugriff->getObjectVars();

            $model = new Admin_Model_Availablerates();

            $model
                ->setZugriffsKontrolle($zugriffVars)
                ->setHotelCode($params['searchHotelCode'])
                ->findHotelId();

            if (array_key_exists('startDatum', $params) and array_key_exists('endDatum', $params))
                $model->setSearchDate($params['startDatum'], $params['endDatum']);

            $anzahl = $model->getCountRatesAndCategories();

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $ausgabe = $model->getRatesAndCategories($start, $limit);

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gethotelsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $zugriffAufHotels = new nook_ZugriffAufHotels();
            $stringListeDerHotels = $zugriffAufHotels
                ->setKundenDaten()
                ->getStringHotels();

            $alleHotels = $zugriffAufHotels->alleHotels;


            $model = new Admin_Model_Availablerates();
            if(!empty($stringListeDerHotels) or !empty($alleHotels)){

               $hotels = $model->getHotels($stringListeDerHotels, $alleHotels);
               echo "{success: true, data: " . json_encode($hotels) . "}";
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


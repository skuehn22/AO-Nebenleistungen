<?php

class Admin_Hotelrates1Controller extends Zend_Controller_Action
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

            $this->view->content = $raintpl->draw("Admin_Hotelrates1_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die aktiven Produkte eines
     * Hotels
     *
     */
    public function getproductsfromhotelAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates1();
            $model->arrayhandling = new nook_arrayhandling();

            $rateId = false;
            if (array_key_exists('rateId', $params))
                $rateId = $params['rateId'];

            $hotelId = 0;
            if (array_key_exists('hotelId', $params))
                $hotelId = $params['hotelId'];

            $hotelProducts = $model->getProductsFromHotel($hotelId, $rateId);

            // wenn Produkte im Hotel vorhanden sind
            if(is_array($hotelProducts))
                echo "{success: true, data: " . json_encode($hotelProducts) . "}";
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

            $model = new Admin_Model_Hotelrates1();

            $zugriffAufHotels = new nook_ZugriffAufHotels();
            $stringListeDerHotels = $zugriffAufHotels
                ->setKundenDaten()
                ->getStringHotels();

            $alleHotels = $zugriffAufHotels->alleHotels;

            // Kontrollfunktion
            // $dependency->checkIstZugriffAufHotelErlaubt();

            $anzahl = $model->getCountHotels($stringListeDerHotels, $alleHotels);

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $hotels = $model->getTableItemsHotels($stringListeDerHotels, $alleHotels, $start, $limit);

            echo "{success: true, data: " . json_encode($hotels) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die verfügbaren raten eines Hotels
     *
     */
    public function gethotelkategorienundratenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if (!array_key_exists('hotelId', $params))
                return;

            $model = new Admin_Model_Hotelrates1();
            $anzahl = $model->getAnzahlHotelKategorienUndRaten($params['hotelId']);

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $kategorienUndRaten = $model->getHotelKategorienUndRaten($params['hotelId'], $start, $limit);

            echo "{success: true, data: " . json_encode($kategorienUndRaten) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermitteltb die Kategorien eines Hotel
     */
    public function getcategoriesfromhotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates1();

            $hotelCategories = $model->getKategorienEinesHotel($params['hotelId']);

            if (!empty($hotelCategories))
                echo "{success: true, data: " . json_encode($hotelCategories) . "}";
            else
                echo "{success: false}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function newhotelrateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);
            $params['properties_id'] = $params['hotelId'];
            unset($params['hotelId']);

            $model = new Admin_Model_Hotelrates1();
            $status = $model->buildRateEinesHotels($params);

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Stammwerte einer Rate
     */
    public function getratenstammwerteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates1();
            $categoryData = $model->getStammdatenEinerRate($params['hotelId'], $params['ratenId']);

            echo "{success: true, data: " . json_encode($categoryData) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getdescriptionAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates1();
            $beschreibungDerRate = $model->getBeschreibungDerRate($params);
            echo "{success: true, data: " . json_encode($beschreibungDerRate) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function setdescriptionAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // einpflegen der Daten
            $model = new Admin_Model_Hotelrates1();
            $model = $model->setBeschreibungDerRate($params);

            // Bild einfügen
            if($_FILES){
                $image = $_FILES['rateImage'];
                $imageName = "ratePic_" . $params['rateId'];
                $imagePath = ABSOLUTE_PATH . "/images/rateImages/";

                $uploadImage = nook_upload::getInstance();
                $kontrolleImageTyp = $uploadImage->setImage($image)->setImagePath($imagePath)->setImageName($imageName)->checkImageTyp();

                $kontrolleImageTyp = $uploadImage->setImage($image)->setImagePath($imagePath)->setImageName($imageName)->checkImageTyp();
                if($kontrolleImageTyp){
                    $kontrolleMove = $uploadImage->moveImage();
                }
            }

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichert die Produkte einer Rate
     */
    public function setprodukteeinerrateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $produkte = array();
            $produkte = json_decode($params['produkte']);

            $model = new Admin_Model_Hotelrates();
            $model->setProdukteEinerRate($params['rateId'], $produkte);
            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


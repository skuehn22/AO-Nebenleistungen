<?php

class Admin_HotelproductsController extends Zend_Controller_Action
{

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Darstellen des Templat
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Hotelproducts_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Auflisten der Hotels
     */
    public function gethotelsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelproducts();

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

            $hotels = $model->getHotels($stringListeDerHotels, $alleHotels, $start, $limit);

            echo "{success: true, data: " . json_encode($hotels) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Darstellen der Produkte eines Hotels
     *
     */
    public function getproductsfromhotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $start = 0;
            $limit = 10;
            if(array_key_exists('limit', $params)){
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $model = new Admin_Model_Hotelproducts();
            $hotelProducts = $model->getProductsFromHotel($start, $limit, $params);
            $anzahlHotelProdukte = $model->getAnzahlProdukteHotel($params);

            echo "{success: true, anzahl: ".$anzahlHotelProdukte.", data: " . json_encode($hotelProducts) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Gibt die Werte eines einzelnen
     * Produktes zurück.
     *
     */
    public function getproductdataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelproducts();
            $singleProductFromHotel = $model->getSingleProductFromHotel($params['productId']);

            echo "{success: true, data: " . json_encode($singleProductFromHotel) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Speichern eines Produktes eines Hotels
     *
     */
    public function setproductspropertiesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelHotelprodukte = new Admin_Model_Hotelproducts();
            $params = $modelHotelprodukte->mapPropertiesFromSingleProduct($params); // mappen der Parameter
            $message = $modelHotelprodukte->checkProduktCode($params); // kontrolliert ob Produkt Code bereits vergeben

            // wenn Produkt Code im Hotel bereits vergeben
            if(!empty($message)){
                echo "{success: false, message: '".$message."'}";
                exit();
            }

            // wenn Produkt Code noch nicht vorhanden
            if($_FILES)
                $modelHotelprodukte->uploadProduktImage($params); // Upload Bild Produkt

            $message = $modelHotelprodukte->updateSingleHotelProduct($params); // Update Hotelprodukt

            echo "{success: true, message: '".$message."'}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function newhotelproductAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelproducts();
            $errors = $model->newHotelProduct($params['hotelId']); // neues Produkt eines Hotel

            if (empty($errors))
                echo "{success: false}";
            else
                echo "{success: true}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


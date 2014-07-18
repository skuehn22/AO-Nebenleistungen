<?php

class Admin_HotelratesController extends Zend_Controller_Action
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

            $this->view->content = $raintpl->draw("Admin_Hotelrates_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gethotelratesAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if (!array_key_exists('id', $params))
                return;

            $model = new Admin_Model_Hotelrates();
            $anzahl = $model->getCountHotelRates($params['id']);

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $rates = $model->getHotelRates($params['id'], $start, $limit);

            echo "{success: true, data: " . json_encode($rates) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function sethotelrateaktivAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates();

            $errors = $model->setHotelRateAktivPassiv($params);

            if (empty($errors))
                echo "{success: true}";
            else
                echo "{success: false}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function loadrateformAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates();

            $formElements = $model->getRateMasterData($params['hotelId'], $params['rateId']);

            if (!empty($formElements))
                echo "{success: true, data: " . json_encode($formElements) . "}";
            else
                echo "{success: false}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }

    public function getcategoriesfromhotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates();

            $hotelCategories = $model->getCategoriesFromHotel($params['hotelId']);

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

    public function getproductsfromhotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelrates();

            $rateId = false;
            if (array_key_exists('rateId', $params))
                $rateId = $params['rateId'];

            $hotelId = 0;
            if (array_key_exists('hotelId', $params))
                $hotelId = $params['hotelId'];

            $hotelProducts = $model->getProductsFromHotel($hotelId, $rateId);

            echo "{success: true, data: " . json_encode($hotelProducts) . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function savenewrateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $products = json_decode($params['products']);

            $model = new Admin_Model_Hotelrates();
            $errors = $model->saveNewRate($params, $products);

            if (empty($errors))
                echo "{success: true}";
            else
                echo "{success: false, errors: " . json_encode($errors) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


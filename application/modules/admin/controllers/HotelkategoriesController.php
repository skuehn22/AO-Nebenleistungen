<?php

class Admin_HotelkategoriesController extends Zend_Controller_Action
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
            $this->view->content = $raintpl->draw("Admin_Hotelkategories_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function tabelitemshotelsAction()
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

            $model = new Admin_Model_Hotelkategories();
            $anzahl = $model->getCountHotels($stringListeDerHotels, $alleHotels);

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $hotels = $model->getTableItemsHotels( $stringListeDerHotels, $alleHotels, $start, $limit);

            echo "{success: true, data: " . json_encode($hotels) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function tabelitemshotelkategoriesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if (!array_key_exists('id', $params))
                return;

            $model = new Admin_Model_Hotelkategories();
            $anzahl = $model->getCountHotelKategories($params['id']);

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $kategories = $model->getTableItemsHotelKategories($params['id'], $start, $limit);

            echo "{success: true, data: " . json_encode($kategories) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function newhotelkategoryAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        $information = array();
        $information[1] = 'Hotelkategorie wurde angelegt';
        $information[2] = 'Hotelkategorie wurde erneuert';

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            // mapping
            $params['properties_id'] = $params['hotelId'];
            unset($params['hotelId']);

            $model = new Admin_Model_Hotelkategories();
            $messageCode = $model->buildHotelKategorie($params);

            // echo "{success: true, message: '".$information[$messageCode]."'}";
             echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcategorydataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelkategories();
            $categoryData = $model->getHotelCategoryData($params['hotelId'], $params['categoryId']);

            echo "{success: true, data: " . json_encode($categoryData) . "}";

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

            $model = new Admin_Model_Hotelkategories();
            $kontrolle = $model->setHotelCategoryData($params['kategorieId'], $params['language'], $params);

            // Bild
            if($_FILES){
                $image = $_FILES['kategorieImage'];
                $imagePath = ABSOLUTE_PATH . "/images/kategorieImages/midi/kategoriePic_";
                $imageName = $params['kategorieId'];


                $uploadImage = nook_upload::getInstance();
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

    public function getdescriptionAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotelkategories();
            // Daten
            $kategoriebeschreibung = $model->getBeschreibungEinerKategorie($params['kategorieId'], $params['sprache']);

            if (count($kategoriebeschreibung) > 0)
                echo "{success: true, data:" . json_encode($kategoriebeschreibung) . "}";
            else
                echo "{success: true}";


        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

}


<?php

class Admin_HotelsController extends Zend_Controller_Action
{

    private $_condition_is_administrator = 10;
    private $_condition_hotel_is_updatet = 2;

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

            $auth = new Zend_Session_Namespace('Auth');
            $authDesKunden = (array) $auth->getIterator();

            // schliessen Fenster
            $raintpl->assign('showCloseButton', 'false');

            // Blöcke abschalten
            $raintpl->assign('showBlock', false);

            if($authDesKunden['role_id'] == $this->_condition_is_administrator){
                $raintpl->assign('showCloseButton', 'true');
                $raintpl->assign('showBlock', true);
            }

            $this->view->content = $raintpl->draw("Admin_Hotels_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * stellt die vorhandenen Hotels dar
     */
    public function tabelitemsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();

            if(array_key_exists('hotelSuche', $params))
                $model->setSuchparameterHotel($params['hotelSuche']);

            $anzahl = $model->getCountPrograms(); // ??????????

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $ausgabe = $model->getTableItems($start, $limit);

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Füllt das Formular der Stammdaten des Hotels mit Daten des Hotels
     */
    public function loadformAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = new Pimple_Pimple();

            $pimple['tabelleProperties'] = function(){
                return new Application_Model_DbTable_properties(array('db' => 'hotels'));
            };
            $pimple['tabellePropertiesDays'] = function(){
                return new Application_Model_DbTable_propertiesDays(array('db' => 'hotels'));
            };
            $pimple['params'] = $params;

            $adminModelHotelStammdaten = new Admin_Model_HotelStammdaten();
            $hotelData = $adminModelHotelStammdaten
                ->setPimple($pimple)
                ->steuerungErmittlungStammdatenHotel()
                ->getHotelStammdaten();

            if (!empty($hotelData))
                echo "{success: true, data: " . json_encode($hotelData) . "}";
            else
                echo "{success: false}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * anlegen eines neuen Hotels
     */
    public function hotelneuAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $hotelId = $model->buildNewHotel();
            
            echo "{success: true, hotelId: ".$hotelId."}";
        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function updatehotelsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {

            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = new Pimple_Pimple();

            $pimple['tabelleProperties'] = function(){
                return new Application_Model_DbTable_properties(array('db' => 'hotels'));
            };
            $pimple['tabellePropertiesDays'] = function(){
                return new Application_Model_DbTable_propertiesDays(array('db' => 'hotels'));
            };

            $pimple['hotelCode'] = $params['property_code'];
            $pimple['params'] = $params;

            $adminModelHotelStammdaten = new Admin_Model_HotelStammdaten();
            $adminModelHotelStammdaten->setPimple($pimple);

            $statusVerwendungHotelcode = $adminModelHotelStammdaten->findDoubleHotelCode($params['property_code']);
            if($statusVerwendungHotelcode === true)
                $statusUpdate = $adminModelHotelStammdaten
                    ->steuerungUpdateStammdatenHotel()
                    ->getStatusUpdate();

            if($statusUpdate)
                echo "{success: true}";
            else
                echo "{success: false}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcountryregionsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $countryRegions = $model->getCountryRegions();

            echo "{success: true, data: " . json_encode($countryRegions) . "}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die AO Cities
     */
    public function getcountrycitiesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $countryCities = $model->getCountryCities();

            echo "{success: true, data: " . json_encode($countryCities) . "}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichern der Personendaten des
     * Hotelverantwortlichen
     */
    public function savepersonaldataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();

            // umschreiben CountryId auf country
            $errors = $model->savePersonalDataFromHotel($params);

            if (count($errors) == 0)
                echo "{success: true}";
            else
                echo "{success: false, errors:" . json_encode($errors) . "}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Daten eines Verantwortlichen eines
     * Hotels
     */
    public function getpersonaldatahotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $personalData = $model->getPersonalDataHotel($params['hotelId']);

            $toolLand = new nook_ToolLand();
            $personalData['country'] = $toolLand->convertLaenderNameNachLaenderId($personalData['country']);

            if (!empty($personalData))
                echo "{success: true, data: " . json_encode($personalData) . "}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcitiesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $personalData = $model->getProperty($params['hotelId']);

            if (!empty($personalData))
                echo "{success: true, data: " . json_encode($personalData) . "}";

        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gettemplateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $model->checkParameterHotelbeschreibung($params);
            $hotelDescription = $model
                    ->setParameterHotelbeschreibung($params)
                    ->getHotelbeschreibung();
            
            $hotelDescription['hotelId'] = $params['hotelId'];

            $raintpl = raintpl_rainhelp::getRainTpl();

            $raintpl->assign('hotel', $hotelDescription);
            $raintpl->assign('showBlock', false);
            $raintpl->assign('propertyId', $params['hotelId']);
            $raintpl->assign('hotelbeschreibung', $hotelDescription);

            $template = $raintpl->draw("Front_Hotelreservation_Index", true);

            echo $template;

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getvalueAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $cellValue = $model->getHoteldescription($params);

            echo "{success: true, data: " . json_encode($cellValue) . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function setvalueAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Hotels();
            $model->updateHotelDescription($params);

            if(!empty($_FILES['bild']['name'])){
                $this->_uploadimage($_FILES, $params);
            }

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*** 'parent' id: hotelsGrid , Liste der vorhandenen Hotels ***/

    public function getsinglehotelAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {

            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_HotelHotelliste();
            $hotelliste = $model
                ->setSearchparam($params['sucheHotel'])
                ->getHotelliste();

            echo "{success: true, data: ".json_encode($hotelliste)."}";
        }
       catch (Exception $e) {
            echo "{success: false}";
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Upload Image
     *
     * @param $bilder
     * @param $params
     */
    private function _uploadimage($bilder, $params){

        $image = $bilder['bild'];
        $imageName = $params['hotelId'];
        $imagePath = $newImage = ABSOLUTE_PATH . "/images/propertyImages/midi/";

        $uploadImage = nook_upload::getInstance();
        $kontrolleImageTyp = $uploadImage
                                ->setImage($image)
                                ->setImagePath($imagePath)
                                ->setImageName($imageName)
                                ->checkImageTyp();

        if($kontrolleImageTyp){
            $kontrolleMove = $uploadImage->moveImage();
            if($kontrolleMove)
                return;
            else
                echo "{success: false}";
        }
        else{
            echo "{success: false}";
        }
    }

    /********** Zahlungsfristen *********************/

     public  function holezahlungszieleAction(){
           $request = $this->getRequest();
           $params = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               $model = new Admin_Model_HotelZahlungsfristen();
               $hotelId = $model->kontrolleHotelId($params['selectHotelId']);
               $zahlungsziele = $model->zahlungszieleEinesHotels($hotelId);

               echo "{success: true, data: ".json_encode($zahlungsziele)."}";
           }
           catch(Exception $e){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }
    }

    public  function speicherezahlungszieleAction(){
           $request = $this->getRequest();
           $params = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               $model = new Admin_Model_HotelZahlungsfristen();

               $params['FaId'] = $model->kontrolleHotelId($params['selectHotelId']);
               $zahlungsziele = $params = $model->kontrolleZahlungsziele($params);
               $kontrolle = $model->speichernZahlungsziele($zahlungsziele);

               if($kontrolle === true)
                    echo "{success: true}";
           }
           catch(Exception $e){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }
    }

    /***************** Stornofristen **************/

     public  function holestornofristenAction(){
           $request = $this->getRequest();
           $params = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               $model = new Admin_Model_HotelsStornofristen();
               $HotelId = $model->kontrolleHotelId($params['HotelId']);
               $stornofristen = $model->stornofristenEinesHotels($HotelId);

               echo "{success: true, data: ".json_encode($stornofristen)."}";
           }
           catch(Exception $e){
                $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
                echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }
    }

     public function speicherestornofristenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
           $this->_helper->viewRenderer->setNoRender();
           $this->_helper->layout->disableLayout();

           $model = new Admin_Model_HotelsStornofristen();

           $params['HotelId'] = $model->kontrolleHotelId($params['HotelId']);
           $stornofristen = $params = $model->kontrolleStornofristen($params);
           $kontrolle = $model->speichernStornofristen($stornofristen);

           if($kontrolle === true)
                echo "{success: true}";

        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}
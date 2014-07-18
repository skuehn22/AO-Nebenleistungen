<?php

class Admin_RatenverfuegbarkeitController extends Zend_Controller_Action
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
            $this->view->content = $raintpl->draw("Admin_Ratenverfuegbarkeit_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*** View Rateneingabe ***/

    public function hotellistAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_RatenverfuegbarkeitRateneingabe();
            $zugriff = new nook_ZugriffAufHotels();
            $model->zugriff = $zugriff;
            $listeDerHotels = $model->getHotels();

            echo "{success: true, data: ".json_encode($listeDerHotels)."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }

    public function getratenhotelAction(){
           $request = $this->getRequest();
           $params = $request->getParams();

           try {
               $this->_helper->viewRenderer->setNoRender();
               $this->_helper->layout->disableLayout();

               $model = new Admin_Model_RatenverfuegbarkeitRateneingabe();
               $zugriff = new nook_ZugriffAufHotels();
               $model->zugriff = $zugriff;
               $model->checkZugriffAufHotel($params['hotelId']);
               $listeDerRaten = $model->getListeDerRaten($params['hotelId']);

               echo "{success: true, data: ".json_encode($listeDerRaten)."}";
           }
           catch (Exception $e) {
               $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
               echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
           }

    }

    public function saverateAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_RatenverfuegbarkeitRateneingabe();
            $model->zugriff = new nook_ZugriffAufHotels();
            $model->checkZugriffAufHotel($params['hotelId']);
            $kontrolle = $model->checkStartEnddatum($params['von'],$params['bis']);
            $params = $model->mapData($params);

            if(empty($kontrolle)){
                echo "{success: false, message: 'falsche Eingabe Start und Endzeit'}";

                return;
            }

            $kontrolle = $model->buildRaten($params);

            if(!empty($kontrolle))
                echo "{success: true}";
            else{
                echo "{success: false, message: 'Rate konnte nicht eingetragen werden'}";
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


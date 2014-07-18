<?php
/**
 * Auflistung der Hotels in einer Stadt die über eine Kapazität zur betreffenden Zeit verfügen.
 *
 * @author Stephan.Krauss
 * @date 18.24.2013
 * @file HotelreservationController.php
 * @package front
 * @subpackage controller
 */
class Front_HotelreservationGruppenrabattController extends Zend_Controller_Action{

    private $realParams = null;
    private $pimple = null;
    private $raten = array();
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();

        // Zugangskontrolle
        $this->zugangskontrolle();
        // Servicecontainer
        $this->servicecontainer();
    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProperties'] = function(){
            return new Application_Model_DbTable_properties(array('db' => 'hotels'));
        };

        $pimple['tabelleCategories'] = function(){
            return new Application_Model_DbTable_categories(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaRatesConfig'] = function(){
            return new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaPrices'] = function(){
            return new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));
        };

        $pimple['toolHotelrabatt'] = function($pimple){
            return new nook_ToolHotelrabatt($pimple);
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * Zugang zu den Action des Controller in Abhängigkeit der rolleId
     */
    private function zugangskontrolle()
    {
        $zugang = array(
            'index' => array(
                1,5,33
            )
        );

        return;
    }

    /**
     * Anpassen der übermittelten Raten / Mapping
     *
     * +
     */
    private function mappenDaten()
    {
        // gebuchte Raten
        if($this->realParams['raten']){
            $raten = explode("#", $this->realParams['raten']);

            $i=0;
            foreach($raten as $rate){
                if(!empty($rate)){
                    $werteRaten = explode('&', $rate);

                    if($werteRaten[1] > 0){
                        $this->raten[$i]['ratenId'] = $werteRaten[0];
                        $this->raten[$i]['anzahl'] = $werteRaten[1];
                    }

                    $i++;
                }
            }
        }

        // Suchparameter der Hotelsuche
        $sessionHotelsuche = new Zend_Session_Namespace('hotelsuche');
        $sessionHotelsucheDaten = (array) $sessionHotelsuche->getIterator();

        $this->realParams['suchdatum'] = $sessionHotelsucheDaten['suchdatum'];
        $this->realParams['hotelId'] = $sessionHotelsucheDaten['propertyId'];
        $this->realParams['uebernachtungen'] = $sessionHotelsucheDaten['days'];

        return;
    }

    /**
     * Berechnung des Gruppenrabatt einer Gruppenbuchung.
     *
     */
    public function indexAction(){
        $this->mappenDaten();
		$params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $frontModelHotelreservationGruppenrabatt = new Front_Model_HotelreservationGruppenrabatt($this->pimple);
            $rabattHotelRabattInformation = $frontModelHotelreservationGruppenrabatt
                ->setRealParams($this->realParams)
                ->steuerungErmittlungGruppenrabatt($this->raten);

            $rabattHotelRabattInformationJson = json_encode($rabattHotelRabattInformation);

            echo $rabattHotelRabattInformationJson;
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }



}


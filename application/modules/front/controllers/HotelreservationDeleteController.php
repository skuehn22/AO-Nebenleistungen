<?php
/**
 * Löschen einer Teilrechnunng der Hotelbuchungen und / oder der Produktbuchungen der Teilrechnung einer Hotelbuchung eines Warenkorbes
 *
 * @author Stephan.Krauss
 * @date 18.12.2013
 * @file HotelreservationController.php
 * @package front
 * @subpackage controller
 */
class Front_HotelreservationDeleteController extends Zend_Controller_Action{

    private $realParams = array();
    protected $pimple = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->servicecontainer();

        $this->requestUrl = $this->view->url();
    }

    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleTeilrechnungen'] = function(){
            return new Application_Model_DbTable_teilrechnungen();
        };

        $pimple['tabelleProduktbuchung'] = function(){
            return new Application_Model_DbTable_produktbuchung();
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * Löscht die Raten / Hotelbuchung einer Teilrechnung des Warenkorbes
     */
    public function hotelbuchungAction()
    {
		$params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // löschen Hotelbuchung
            $steuerungLoeschen = 1;

            $frontModelHotelreservationDelete = new Front_Model_HotelreservationDelete($this->pimple);
            $kontrolleLoeschenTeilrechnung = $frontModelHotelreservationDelete
                ->setFlagLoeschen($steuerungLoeschen)
                ->setTeilrechnungId($params['teilrechnungenId'])
                ->steuerungLoeschenTeilrechnungHotelbuchung()
                ->getKontrolleLoeschenTeilrechnung();

            $this->_redirect("/front/warenkorb/");
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Löscht die Produkte einer Hotelbuchung einer Teilrechnung des Warenkorbes
     */
    public function produktbuchungAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // löschen Produktbuchung
            $steuerungLoeschen = 2;

            $frontModelHotelreservationDelete = new Front_Model_HotelreservationDelete($this->pimple);
            $kontrolleLoeschenTeilrechnung = $frontModelHotelreservationDelete
                ->setFlagLoeschen($steuerungLoeschen)
                ->setTeilrechnungId($params['teilrechnungenId'])
                ->steuerungLoeschenTeilrechnungHotelbuchung()
                ->getKontrolleLoeschenTeilrechnung();

            $this->_redirect("/front/warenkorb/");
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}


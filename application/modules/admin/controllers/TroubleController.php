<?php
/**
 * Darstellung der aufgetretenen Fehler während der Buchung
 *
 * Die Kunden ID ist bekannt.
 * Es werden Exception gehandelt die zum Abbruch des Programmes führten.
 *
 * + laden des Template
 * + Darstellung der aufgetretenen Fehler
 * + Darstellung der Kontaktdaten
 * + Darstellung erfolgter Hotelbuchungen
 * + Darstellung gebuchter Hotelprodukte
 * + Darstellung gebuchter Programme
 *
 *
 * @author stephan.krauss
 * @date 11.06.13
 * @file TroubleController.php
 * @package front | admin | tools | plugins | schnittstelle | tabelle
 * @subpackage controller | model | mapper | interface
 */

class Admin_TroubleController extends Zend_Controller_Action
{

    private $realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    public function indexAction()
    {
        $params = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $this->view->content = $raintpl->draw("Admin_Trouble_Index", true);
        }
        catch (Exception $e) {
        $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
        echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die vorhandenen Buchungsfehler und stellt diese dar
     *
     * + Seitenblättern
     */
    public function getFehlerAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelBuchungsfehler = new Admin_Model_Buchungsfehler();

            if(isset($params['start'])){
                $modelBuchungsfehler
                    ->setStart($params['start'])
                    ->setLimit($params['limit']);
            }

            $vorhandeneFehler = $modelBuchungsfehler->ermittelnAktuelleFehler()
                ->getFehler();

            $anzahlFehler = $modelBuchungsfehler->getAnzahlFehler();

            echo "{success: true, daten: ".json_encode($vorhandeneFehler).", anzahl: ".$anzahlFehler."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Setzt den Status einer fehlgeschlagenen Buchung
     */
    public function setBuchungsfehlerStatusAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelBuchungsfehler = new Admin_Model_Buchungsfehler();
            $kontrolle = $modelBuchungsfehler
                ->setBuchungsfehlerId($params['id'])
                ->setBuchungsfehlerStatus($params['status'])
                ->buchungsfehlerStatus();

            if(is_int($kontrolle) and $kontrolle > 0)
                echo "{success: true}";
            else
                echo "{success: false}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getKontaktAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

            $pimple[ 'adminModelPersonendaten' ] = function ($c) {
                return new Admin_Model_Personendaten();
            };

            $pimple['tabelleAdressen'] = function($c){
                return new Application_Model_DbTable_adressen();
            };

            $modelPersonendatenBuchungsfehler = new Admin_Model_PersonendatenBuchungsfehler($pimple);
            $kundendaten = $modelPersonendatenBuchungsfehler
                ->setPersonendaten($params['kundenId'])
                ->findPersonendaten()
                ->getKundendaten();

            echo json_encode($kundendaten);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Übergibt die Hotelbuchungen einer Buchungsnummer
     */
    public function getHotelbuchungAction()
    {
        $params = $this->realParams;
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

            $pimple['tabelleHotelbuchung'] = function($c)
            {
                return new Application_Model_DbTable_hotelbuchung();
            };

            $pimple['tabelleProperties'] = function($c)
            {
                return new Application_Model_DbTable_properties(array('db' => 'hotels'));
            };

            $pimple['tabelleAoCity'] = function($c)
            {
                return new Application_Model_DbTable_aoCity();
            };

            $pimple['tabelleOtaRatesConfig'] = function($c)
            {
                return new Application_Model_DbTable_otaRatesConfig();
            };

            $modelHotelbuchungen = new Admin_Model_Hotelbuchungen($pimple);
            $hotelbuchungen = $modelHotelbuchungen
                ->setBuchungsnummerId($params['buchungsnummerId'])
                ->ermittelnHotelbuchungen()
                ->getHotelbuchungen();

            echo "{success: true, daten: ".json_encode($hotelbuchungen)."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getHotelprodukteAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();


        }
        catch (Exception $e) {
        $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
        echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getProgrammeAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();


        }
        catch (Exception $e) {
        $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
        echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }



} // end class


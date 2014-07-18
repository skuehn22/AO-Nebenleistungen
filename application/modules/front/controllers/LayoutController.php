<?php
/**
* allgemeine Aufgaben resultierend aus dem Front Layout
*
* + Ermittelt die Anzahl der Artikel die aktuell im Warenkorb liegen
*
* @date 16.10.2013
* @file LayoutController.php
* @package front
* @subpackage controller
*/
class Front_LayoutController extends  Zend_Controller_Action{

    // Konditionen
    private $condition_status_im_warenkorb = 1;
    private $condition_aktueller_warenkorb = 0;

    private $realParams = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();
    }

    /**
     * Servicecontainer zur Bestimmung der Logout Variante
     *
     * @return Pimple_Pimple
     */
    protected function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammbuchung'] = function(){
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        return $pimple;
    }

    /**
     * Ermittelt den Typ des Warenkorbes und loggt aus
     *
     * + Erkennung, kein Warenkorb vorhanden
     * + Erkennung, neuer Warenkorb
     * + Erkennung, Warenkorb entstand aus einer Vormerkung
     * + Erkennung, Warenkorb entstand aus einer Bestandsbuchung
     *
     * + '0' = 'status_kein_warenkorb_aktiv',
     * + '1' = 'status_neuer_warenkorb',
     * + '2' = 'status_warenkorb_vormerkung',
     * + '4' = 'status_warenkorb_bestandsbuchung'
     */
    public function statusWarenkorbAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = $this->servicecontainer();
            $pimple['sessionId'] = Zend_Session::getId();

            // bestimmt Datensatz aus 'tbl_buchungsnummer'
            $frontModelBestimmungDatensatzBuchung = new Front_Model_BestimmungDatensatzBuchung();
            $frontModelBestimmungDatensatzBuchung
                ->setPimple($pimple)
                ->steuerungErmittlungWerteBuchung();

            $hobNummer = $frontModelBestimmungDatensatzBuchung->getHobNummer();
            $zaehler = $frontModelBestimmungDatensatzBuchung->getZaehler();
            $buchungsNummer = $frontModelBestimmungDatensatzBuchung->getBuchungsnummer();

            // ermittelt Anzahl Artikel im Warenkorb
            if(!empty($buchungsNummer)){
                $toolAnzahlArtikelWarenkorb = new nook_ToolAnzahlArtikelAktuellerWarenkorb();
                $anzahlArtikelWarenkorb = $toolAnzahlArtikelWarenkorb
                    ->setBuchungsnummer($buchungsNummer)
                    ->setZaehler($zaehler)
                    ->steuerungErmittelnAnzahlArtikel()
                    ->getAnzahlAllerArtikelImWarenkorb();
            }
            else
                $hobNummer = 0;

            // Bestellvorgang beendet !!!
            if( (($hobNummer == 0) and  ($anzahlArtikelWarenkorb == 0)) or (empty($buchungsNummer)) ){
                echo $hobNummer;
            }
            // Abmeldung während des Bestellprozesses
            else{
                $pimple['hobNummer'] = (int) $hobNummer;
                $pimple['buchungsnummer'] = $buchungsNummer;

                $frontModelVarianteLogout = new Front_Model_VarianteLogout();
                $textLogoutPopup = $frontModelVarianteLogout
                    ->setPimple($pimple)
                    ->steuerungErmittlungTypBuchungImWarenkorb()
                    ->getTextLogoutPopup();

                echo $textLogoutPopup;
            }
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }


} // end class
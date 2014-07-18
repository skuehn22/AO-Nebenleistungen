<?php
/**
* Der Benutzer kann Artilkel und komplette Warenkörbe löschen.
*
* + Füllen Servicecontainer mit benötigten Tabellen
* + Anzeige des Template
* + Ermittelt den Inhalt des Template
* + Steuerung löschen eines einzelnen Artikel im aktuellen Warenkorbes
* + Steuerung löschen aktueller Warenkorb
* + Ruft die Model der Stornierung eines Warenkorbes auf und übergibt die Artikel
* + Ermittelt die Anzahl der Datensaetze des aktuellen Warenkorbes
*
* @date 30.38.2013
* @file StornierungController.php
* @package front
* @subpackage controller
*/

class Front_StornierungController extends Zend_Controller_Action
{
    // Flags
    private $flagAnzahlArtikelBestandsbuchung = 0;

    // Konditionen

    // Fehler

    // Informationen

    protected $realParams = null;
    /** @var $pimple Pimple_Pimple  */
    protected $pimple = null;
    protected $buchungsnummer = null;

    private $requestUrl = null;

    protected $condition_ohne_storno = true;



    public function init()
    {

        $request = $this->getRequest();
        $params = $request->getParams();
        $this->realParams = $params;

        /** @var $pimple Pimple_Pimple */
        $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

        $pimple = $this->servicecontainerTabelle($pimple);
        $pimple = $this->servicecontainerModel($pimple);

        $this->pimple = $pimple;

        $this->requestUrl = $this->view->url();

        // Bestimmung Rolle des Benutzers
//        $toolBenutzeranmeldung = new nook_ToolBenutzeranmeldung();
//        $this->rolleBenutzer = $toolBenutzeranmeldung->killNobody()->getRolleId();
    }

    /**
     * Füllen Servicecontainer mit benötigten Tabellen
     *
     * @param Pimple_Pimple $pimple
     * @return Pimple_Pimple
     */
    private function servicecontainerTabelle(Pimple_Pimple $pimple)
    {
        $pimple['tabelleProgrammbuchung'] = function()
        {
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function()
        {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function()
        {
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple['tabelleXmlBuchung'] = function()
        {
            return new Application_Model_DbTable_xmlBuchung();
        };

        $pimple['tabelleProgrammdetailsStornokosten'] = function()
        {
            return new Application_Model_DbTable_stornofristen();
        };

        $pimple['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        return $pimple;
    }

    private function servicecontainerModel(Pimple_Pimple $pimple)
    {
        $pimple['modelStornierungTabelleProgrammbuchung'] = function(){
            return new Front_Model_StornierungTabelleProgrammbuchung();
        };

        $pimple['modelStornierungTabelleHotelbuchung'] = function(){
            return new Front_Model_StornierungTabelleHotelbuchung();
        };

        $pimple['modelStornierungTabelleProduktbuchung'] = function(){
            return new Front_Model_StornierungTabelleProduktbuchung();
        };

        $pimple['modelStornierungTabelleXmlBuchung'] = function(){
            return new Front_Model_StornierungTabelleXmlBuchung();
        };

        $pimple['modelStornierungTabelleBuchungsnummer'] = function(){
            return new Front_Model_StornierungTabelleBuchungsnummer();
        };



        return $pimple;
    }

    /**
     * Anzeige des Template
     */
    public function viewAction()
    {
        $buchungsdaten = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $idAnzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $raintpl->assign('anzeigesprache', $idAnzeigesprache);
            $raintpl->assign('anzahlArtikelBestandsbuchung', $this->flagAnzahlArtikelBestandsbuchung);
            $raintpl->assign('buchungsnummer', $this->buchungsnummer);

            $this->view->content = $raintpl->draw("Front_Stornierung_View", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Steuerung löschen eines einzelnen Artikel im aktuellen Warenkorbes
     *
     * + ermitteln Artikel zum löschen / stornieren
     * + verändern der Buchungstabellen
     * + Kontrolliert Anzahl der Datensätze und verzweigt
     */
    public function artikelDeleteAction()
    {
        try {
            $paramsLoeschenArtikel = $this->realParams;

            $frontModelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer();
            $frontModelZaehlerBuchungsnummer->findBuchungsnummerUndZaehler();
            $buchungsnummer = $frontModelZaehlerBuchungsnummer->getBuchungsnummer();
            $zaehler = $frontModelZaehlerBuchungsnummer->getZaehler();

            $this->buchungsnummer = $buchungsnummer;

            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // ermitteln Artikel zum löschen / stornieren
            $frontModelStornieren = new Front_Model_StornierenArtikelWarenkorbBestimmen($this->pimple);
            $artikelStornierungWarenkorb = $frontModelStornieren
                ->setBuchungsnummer($buchungsnummer)
                ->setZaehler($zaehler)
                ->setFlagBereich($paramsLoeschenArtikel['bereich'])
                ->setArtikelId($paramsLoeschenArtikel['idBuchungstabelle'])
                ->bestimmeArtikelWarenkorb()
                ->getArtikelStornierungWarenkorb();

            // verändern der Buchungstabellen
            $this->observeWarenkorbStornierung($buchungsnummer, $zaehler, $artikelStornierungWarenkorb);

            // Anzahl Artikel der Bestandsbuchung im neuen Warenkorb
            $frontModelAnzahlArtikelBestandsbuchung = new Front_Model_AnzahlArtikelBestandsbuchung();
            $neueZaehler = 0;

            // Anzahl der Artikel im aktuellen Warenkorb ohne Stornierung
            $anzahlArtikelBuchung = $frontModelAnzahlArtikelBestandsbuchung
                ->setBuchungsnummer($buchungsnummer)
                ->setZaehler($neueZaehler)
                ->setTabelleProgrammbuchung($this->pimple['tabelleProgrammbuchung'])
                ->setTabelleHotelbuchung($this->pimple['tabelleHotelbuchung'])
                ->steuerungErmittlungAnzahlArtikelBestandsbuchung()
                ->getAnzahlArtikelBestandsbuchung();

            // neuer Warenkorb
            if($paramsLoeschenArtikel['typ'] == 'loeschen'){
                if($anzahlArtikelBuchung > 0)
                    $this->_redirect("/front/warenkorb/index");
                else
                    $this->_redirect("/front/login/");
            }
            // Bestandsbuchung
            else{
                // Artikel der Bestandsbuchung sind im Warenkorb
                if($anzahlArtikelBuchung > 0)
                    $this->_redirect("/front/warenkorb/index");
                else
                    $this->_redirect("/front/bestellung/index");
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Steuerung löschen aktueller Warenkorb
     *
     * + Zähler und Buchungsnummer betimmen
     * + ermitteln Artikel zum löschen / stornieren
     * + verändern der Buchungstabellen
     * + Kontrolliert Anzahl der Datensätze und verzweigt
     * + Anzahl Artikel Bestandsbuchung
     */
    public function warenkorbDeleteAction()
    {
       try {
           $raintpl = raintpl_rainhelp::getRainTpl();

           // Zähler und Buchungsnummer bestimmen
           $frontModelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer();
           $frontModelZaehlerBuchungsnummer->findBuchungsnummerUndZaehler();

           $buchungsnummer = $frontModelZaehlerBuchungsnummer->getBuchungsnummer();
           $zaehler = $frontModelZaehlerBuchungsnummer->getZaehler();

           $this->buchungsnummer = $buchungsnummer;

           // ermitteln Artikel zum löschen / stornieren
            $frontModelStornieren = new Front_Model_StornierenArtikelWarenkorbBestimmen($this->pimple);
            $artikelStornierungWarenkorb =  $frontModelStornieren
                ->setBuchungsnummer($buchungsnummer)
                ->setZaehler($zaehler)
                ->bestimmeArtikelWarenkorb()
                ->getArtikelStornierungWarenkorb();

            // Anzahl Artikel Bestandsbuchung
            if($frontModelStornieren->getAnzahlArtikelBestandsbuchung() > 0)
                $this->flagAnzahlArtikelBestandsbuchung = $frontModelStornieren->getAnzahlArtikelBestandsbuchung();

            // verändern Buchungstabellen
            $this->observeWarenkorbStornierung($buchungsnummer, $zaehler, $artikelStornierungWarenkorb);

           // Anzeigesprache
           $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
           $raintpl->assign('anzeigesprache', $anzeigesprache);

           if($this->flagAnzahlArtikelBestandsbuchung == 0){
               // Controller Login
               $this->_redirect('/front/login/');
           }
           else{
               // Controller Bestellung
               $this->_redirect('/front/bestellung/');
           }
       }
       catch (Exception $e) {
           $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
           $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
       }
    }

    /**
     * Ruft die Model der Stornierung eines Warenkorbes auf und übergibt die Artikel
     *
     * + nur die Model
     * + übergibt Servicecontainer
     * + setzt Flag Bestandsbuchung
     * + übergibt Artikel
     * + Verarbeitung
     * + übernimmt die Rückmeldung der Model
     *
     * @return Front_Model_StornierenArtikelWarenkorbBestimmen
     */
    private function observeWarenkorbStornierung($buchungsnummerId, $zaehler, array $artikelWarenkorb)
    {
        if($zaehler > 0)
            $flagBestandsbuchung = true;

        $pimpleValues = $this->pimple->getValues();

        // läuft durch Model / Observer
        foreach($pimpleValues as $key => $value){

            if(  ($key == 'modelStornierungTabelleBuchungsnummer') and ($this->flagAnzahlArtikelBestandsbuchung == 0) )
                continue;

            if(strstr($key, 'model')){
                $model = $this->pimple->offsetGet($key);
                $kontrolle = $model
                    ->setPimple($this->pimple)
                    ->setFlagBestandsbuchung($flagBestandsbuchung)
                    ->setArtikelWarenkorb($artikelWarenkorb)
                    ->setBuchungsnummer($buchungsnummerId)
                    ->setZaehler($zaehler)
                    ->work()
                    ->getStatusWork();
            }
        }

        return;
    }

    /**
     * Ermittelt die Anzahl der Datensaetze des aktuellen Warenkorbes
     *
     * + alle Datensätz des aktuellen Warenkorbes
     *
     * @return int
     */
    private function anzahlDatensaetzeAktuellerWarenkorb($buchungsnummer, $zaehler)
    {
        $toolAnzahlArtikelAktuellerWarenkorb = new nook_ToolAnzahlArtikelAktuellerWarenkorb();
        $toolAnzahlArtikelAktuellerWarenkorb
            ->setBuchungsnummer($buchungsnummer)
            ->setZaehler($zaehler);

        $anzahlArtikel = $toolAnzahlArtikelAktuellerWarenkorb
            ->steuerungErmittelnAnzahlArtikel()
            ->getAnzahlAllerArtikelImWarenkorb();

        return $anzahlArtikel;
    }

    /**
     * Verändertn den Zähler der Buchungsnummer wenn der Warenkorb eine Stornierung ist.
     *
     * + neuer Zähler in Session
     * + neuer Zähler in 'tbl_buchungsnummer'
     * + umschreiben der Datensätze mit Zaehler 0 in den Buchungstabellen auf neuen Zaehler
     *
     * @param $buchungsnummer
     * @param $zaehler
     */
    private function neueZaehlerBuchungsnummer($buchungsnummer, $zaehler)
    {
        $neuerZaehler = $zaehler + 1;

        $toolErhoehenZaehlerWarenkorb = new nook_ToolVeraendernZaehlerWarenkorb();
        $toolErhoehenZaehlerWarenkorb
            ->setBuchungsnummer($buchungsnummer)
            ->setMomentanerZaehler($zaehler)
            ->setNeuerZaehler($neuerZaehler)
            ->neuerZaehlerSessionUndTabelleBuchungsnummer()
            ->neuerZaehlerBuchungstabellen();

        return;
    }

    /**
     * Anlegen neuer Warenkorb
     *
     * @param $buchungsnummer
     */
    protected function neuerWarenkorbAnlegen($buchungsnummer)
    {
        $sessionId = Zend_Session::getId();

        $frontModelWarenkorbNeu = new Front_Model_WarenkorbNeu();
        $neueBuchungsnummer = $frontModelWarenkorbNeu
            ->setTabelleBuchungsnummer($this->pimple['tabelleBuchungsnummer'])
            ->setAlteBuchungsnummerId($buchungsnummer)
            ->setSession($sessionId)
            ->steuerungAnlegenNeuerWarenkorbTabelleBuchungsnummer()
            ->getBuchungsnummerNeu();

        return;
    }
}
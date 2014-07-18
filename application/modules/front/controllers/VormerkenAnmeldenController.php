<?php
/**
* Meldet den User zur Vormerkung an und schreibt Warenkorb zur Vormerkung um
*
* + Anmeldung zur Vormerkung eines Warenkorbes
* + Anmeldung zur Vormerkung
* + speichern der Anmeldung und umschreiben eines Warenkorbes zur Vormerkung
* + Kontrolle der Mailadresse
* + Anmelden eines Benutzers im verkürzten Modus.
* + Wenn Artikel im Warenkorb, dann werden diese Artikel zu einer Vormerkung umgeschrieben
*
* @author Stephan.Krauss
* @date 21.01.13
* @file VormerkenAnmeldenController.php
*/
class Front_VormerkenAnmeldenController extends Zend_Controller_Action implements nook_ToolCrudController {

    // Konditionen
    private $condition_rolle_neuling = 2;
    private $condition_rolle_gast = 1;

    private $_realParams = null;
    private $requestUrl = null;

    protected $condition_status_vormerkung = 2;

    protected $pimple = null;

    public function init(){
        try{
            $request = $this->getRequest();
            $this->realParams = $request->getParams();

            $this->servicecontainer();

            $this->requestUrl = $this->view->url();
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    protected function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammbuchung'] = function()
        {
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function()
        {
            return new Application_Model_DbTable_produktbuchung();
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * Anmeldung zur Vormerkung eines Warenkorbes
     *
     * + sind die AGB gecheckt
     * + 'status' = 2, Status in Tabelle 'tbl_buchungsnummer'
     * + übermittelt 'tbl_programmbuchung'.'status' = 2
     * 
     * @return void
     */
    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();

        try{
            $raintpl = raintpl_rainhelp::getRainTpl();

            $modelAnmeldungVormerkung = new Front_Model_VormerkenAnmelden();

            // Umlenkung wenn Benutzer angemeldet
            $userAnmeldung = $modelAnmeldungVormerkung->checkKundeAnmeldung();

            // Benutzer ist angemeldet
            if(!empty($userAnmeldung)){
                // Definition 'agb' = checked
                // setzt / definiert 'status' = 2, Status in Tabelle 'tbl_buchungsnummer'
                $this->_redirect("/front/orderdata-vormerken/edit/status/2/agb/agb/");
            }
            else{
                $this->view->content = $raintpl->draw( "Front_VormerkenAnmelden_Index", true );
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Anmeldung zur Vormerkung
     */
    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $raintpl = raintpl_rainhelp::getRainTpl();

            $modelAnmeldungVormerkung = new Front_Model_VormerkenAnmelden();

            $this->view->content = $raintpl->draw( "Front_VormerkenAnmelden_Index", true );
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    public function deleteAction(){}

    /**
     * speichern der Anmeldung und umschreiben eines Warenkorbes zur Vormerkung
     *
     * + verkürzte Anmeldung
     * + Umschreiben Warenkorb zur Vormerkung
     *+ Umlenkung zur Vormerkung
     */
    public function saveAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{

            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Eintrag in 'tbl_adressen'
            $modelAnmeldungVormerkung = new Front_Model_VormerkenAnmelden();
            $params = $modelAnmeldungVormerkung->checkAnmeldung($params);
            $anmeldungErfogreich = $modelAnmeldungVormerkung->anmeldung($params);

            // wenn Eintrag in 'tbl_adressen'
            if($anmeldungErfogreich === true){
                $benutzerId = $modelAnmeldungVormerkung->getBenutzerId();
                $sessionId = Zend_Session::getId();

                // wenn kein Eintrag in 'tbl_buchungsnummer' dann neuer Eintrag
                $frontModelBuchungstabelleNeuerEintrag = new Front_Model_BuchungstabelleNeuerEintrag();
                $buchungsId = $frontModelBuchungstabelleNeuerEintrag
                    ->setBenutzerId($benutzerId)
                    ->setSessionId($sessionId)
                    ->steuerungEintragenBuchungstabelle()
                    ->getBuchungsId();

                $flagEintragBuchungstabelle = $frontModelBuchungstabelleNeuerEintrag->getFlagEintragBuchungstabelle();

                // sind Artikel im Warenkorb ???
                $toolAanzahlArtikelImWarenkorb = new nook_ToolAnzahlArtikelWarenkorb($this->pimple);
                $anzahlArtikelImWarenkorb = $toolAanzahlArtikelImWarenkorb
                    ->setBuchungsnummer($buchungsId)
                    ->setZaehler(0)
                    ->steuerungErmittlungAnzahlArtikelImWarenkorb()
                    ->getAnzahlAllerArtikelImWarenkorb();

                // wenn eine Buchung vorliegt
                if( ($flagEintragBuchungstabelle === true) and ($anzahlArtikelImWarenkorb > 0) ){
                    // schreibt den Warenkorb zur Vormerkung um
                    $this->warenkorbAlsVormerkung($benutzerId);
                    //

                    // neuer Warenkorb
                    $this->neuerWarenkorb();

                    // Umlenkung wenn Anmeldung erfolgreich
                    $this->_redirect("/front/vormerkung/");
                }
                // wenn keine Buchung vorhanden
                else
                    $this->_redirect("/front/login/");
            }
            else
                $this->_redirect("/front/login/");
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Kontrolle der Mailadresse
     */
    public function mailadresseAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelAnmeldungVormerkung = new Front_Model_VormerkenAnmelden();
            $antwort = $modelAnmeldungVormerkung->checkMailadresse($params['mail']);

            echo $antwort;
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Anmelden eines Benutzers im verkürzten Modus.
     *
     * + Speichern von benutzer ID und Rolle ID in der Session_Namespace['Auth'].
     * + schreibt die Artikel im Warenkorb auf eine Vormerkung um
     * + Umlenkung zur Vormerkung
     */
    public function loginAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelAnmeldungVormerkung = new Front_Model_VormerkenAnmelden();
            $benutzerId =  $modelAnmeldungVormerkung->login($params);

            // Session
            $auth = new Zend_Session_Namespace('Auth');

            // zum Baustein Übersicht der Vormerkungen
            if(!empty($benutzerId)){
                $benutzerRolle = nook_ToolBenutzerrolle::getRolleBenutzerTabelleAdressen($params['email'], $params['passwort']);

                // geht zu den Vormerkungen
                if($benutzerRolle > $this->condition_rolle_gast){
                    $auth->role_id = $benutzerRolle;

                    // schreibt den Warenkorb zur Vormerkung um
                    $this->warenkorbAlsVormerkung($benutzerId);

                    // neuer Warenkorb
                    $this->neuerWarenkorb($benutzerId);

                    // gibt es Vormerkungen ?
                    $anzahlVormerkungen = nook_ToolVormerkungen::bestimmeAnzahlVormerkungen($auth->userId);

                    if($anzahlVormerkungen > 0)
                        $this->_redirect('/front/vormerkung/');
                    else
                        $this->_redirect('/front/login/index/');
                }
                // bleibt im Baustein
                else{
                    $auth->role_id = $this->condition_rolle_gast;
                    $this->_redirect('/front/vormerken-anmelden/index/');
                }
            }
            // bleibt im Baustein
            else{
                $auth->role_id = $this->condition_rolle_gast;
                $this->_redirect('/front/vormerken-anmelden/index/');
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Wenn Artikel im Warenkorb, dann werden diese Artikel zu einer Vormerkung umgeschrieben
     *
     * + umschreiben Warenkorb zur Vormerkung
     *
     */
    private function warenkorbAlsVormerkung($benutzerId)
    {
        $modelOrderdataStatus = new Front_Model_OrderdataStatus();

        // umschreiben Warenkorb zur Vormerkung
        $modelOrderdataStatus
            ->setStatus($this->condition_status_vormerkung) // setzen Status
            ->setBenutzerId($benutzerId)
            ->setzenStatusTabelleBuchungsnummer() // setzen Status des Warenkorbes 'tbl_buchungsnummer'
            ->setzenStatusTabelleProgrammbuchung() // setzt den Status des Warenkorbes 'tbl_programmbuchung'
            ->setzenStatusTabelleHotelbuchung() // setzen Status der Hotelbuchungen
            ->setzenStatusTabelleProduktbuchung(); // setzen Status produktbuchungen

        return;
    }

    /**
     *  neuer Warenkorb anlegen
     *
     * + anlegen neuer Warenkorb
     *
     * @return null
     */
    protected function neuerWarenkorb($benutzerId = false)
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $buchungsnummerAltId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        // anlegen neuer Warenkorb
        $frontModelWarenkorbNeu = new Front_Model_WarenkorbNeu();
        $neueBuchungsnummer = $frontModelWarenkorbNeu
            ->setSession($sessionId)
            ->setAlteBuchungsnummerId($buchungsnummerAltId)
            ->setTabelleBuchungsnummer($tabelleBuchungsnummer)
            ->steuerungAnlegenNeuerWarenkorbTabelleBuchungsnummer()
            ->getBuchungsnummerNeu();

        return $neueBuchungsnummer;
    }

}

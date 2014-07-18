<?php
/**
* Registrierung der Personedaten eines Users. Speichern der Personendaten eines Users der nicht angemeldet ist.
*
* + erstellt ein leeres Formular der Personendaten eines Kunden
* + Stellt ein leeres Formular dar.
* + Löscht den Inhalt der Session und bildet neue Session ID
* + Legt einen neuen Benutzer an
* + Erstellt den XMl Block der Benutzerdaten
* + Überprüft ob eine Mailadresse bereits vorhanden ist
*
* @author Stephan.Krauss
* @date 10.04.13
* @file RegistrierungController.php
* @package front
* @subpackage controller
*/
class Front_RegistrierungController extends Zend_Controller_Action implements nook_ToolCrudController
{

    // Konditionen
    private $condition_rolle_benutzer = 1;

    // Flags

    protected $realParams = array();
    protected $pimple = null;
    protected $anzeigeSpracheId = null;

    private $requestUrl = null;

    public function init()
    {
        try {
            $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

            $this->pimple = new Pimple_Pimple();
            $this->requestUrl = $this->view->url();

            $this->pimple['frontModelPersonalData'] = function () {
                return new Front_Model_Personaldata();
            };

            $request = $this->getRequest();
            $this->realParams = $request->getParams();

            // Zugriff auf die Action
            $nutzungAction = array(
                'indexAction' => $this->condition_rolle_benutzer,
                'createAction' => $this->condition_rolle_benutzer,
                'existiert-mailadresseAction' => $this->condition_rolle_benutzer
            );

            $toolZugriffController = new nook_ToolZugriffController();
            $toolZugriffController
                ->setZugriffAction($nutzungAction)
                ->setActionName($this->realParams['action'])
                ->steuerungKontrolleZugriffAction();

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * erstellt ein leeres Formular der Personendaten eines Kunden
     *
     * + ermittelt ID der Anzeigesprache
     * + füllt 'raintpl' mit Comboboxen
     * + erstellt neue Session ID
     */
    public function indexAction()
    {

        try {
            /** @var $raintpl RainTPL */
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Anzeigesprache
            $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

            // Comboboxen leeres Formular
            $raintpl = $this->erststart($raintpl);

            // verändern der Session ID
            $this->neueSessionId();

            $this->view->content = $raintpl->draw("Front_Registrierung", true);

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Stellt ein leeres Formular dar.
     *
     * + Factory 'Kundendaten'
     * + ermittelt Comboboxen des leeren Personendaten Formular
     *
     * @param $raintpl
     * @return RainTpl
     */
    private function erststart($raintpl)
    {
        $frontModelKundendatenFactory = new Front_Model_KundendatenFactory('leer', $this->anzeigeSpracheId);
        /** @var $variante Front_Model_KundendatenLeer */
        $variante = $frontModelKundendatenFactory->getVariante();
        $raintpl = $variante
            ->steuerungDarstellungLeeresFormularPersonendaten($raintpl)
            ->getRainTpl();

        return $raintpl;
    }

    /**
     * Löscht den Inhalt der Session und bildet neue Session ID
     *
     */
    private function neueSessionId()
    {
        $modelLogout = new Front_Model_Logout();
        $modelLogout->abmelden();

        return;
    }

    public function editAction()
    {
    }

    public function deleteAction()
    {
    }

    /**
     * Legt einen neuen Benutzer an
     *
     * + anlegen neuer Benutzer
     * + Kontrolle auf Superuser
     * + erstellt XMl Block der Benutzerdaten
     * + Login User in Namespace 'Auth'
     */
    public function createAction()
    {
        $params = $this->realParams;

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

            $pimple['tabelleAdressen'] = function ($c) {
                return new Application_Model_DbTable_adressen();
            };

            $pimple['tabelleBuchungsnummer'] = function ($c) {
                return new Application_Model_DbTable_buchungsnummer();
            };

            $pimple['datenAdressen'] = $pimple->share(
                function ($c) {
                    return new Application_Model_Adressen();
                }
            );

            $pimple['kontrolleSuperuser'] = $pimple->share(
                function ($c) {
                    return new nook_ToolKontrolleSuperuser();
                }
            );

            $pimple['datenAdressen']->setContainer($pimple);

            // eintragen in 'tbl_adressen'
            $modelRegistrierung = new Front_Model_Registrierung($pimple);
            $modelRegistrierung
                ->insertDatenInTabelleAdressen($params) // eintragen Userdaten
                ->loginUser(); // Login User in Namespace 'Auth'

            // Kundendaten in XML
            $this->erstellenXmlKundendaten($params);

            // $this->view->content = $raintpl->draw("Front_Registrierung", true);
            $this->_redirect('/front/login');
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Erstellt den XMl Block der Benutzerdaten
     *
     * @param $kundenDaten
     */
    private function erstellenXmlKundendaten($kundenDaten)
    {
        $kundenId = nook_ToolKundendaten::findKundenId();

        $toolCountry = new nook_ToolLand();
        $kundenDaten['country'] = $toolCountry->convertLaenderIdNachLandName($kundenDaten['country']);

        $modelWarenkorbPersonaldataXml = new Front_Model_WarenkorbPersonalDataXML();
        $kundeVorhanden = $modelWarenkorbPersonaldataXml->checkExistPersonalDataXml($kundenId);
        if (!$kundeVorhanden) {
            $modelWarenkorbPersonaldataXml->setKundenDaten($kundenId, $kundenDaten)->saveKundenDatenXML();
        }

        return;

    }

    public function updateAction()
    {
    }

    /**
     * Überprüft ob eine Mailadresse bereits vorhanden ist
     *
     * + sendet Hinweis wenn Mailadrese bereits verwandt wird
     */
    public function existiertMailadresseAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

            $pimple['tabelleAdressen'] = function ($c) {
                return new Application_Model_DbTable_adressen();
            };

            $pimple['datenAdressen'] = $pimple->share(
                function ($c) {
                    return new Application_Model_Adressen();
                }
            );

            $modelRegistrierung = new Front_Model_Registrierung($pimple);
            $informationCode = $modelRegistrierung->setEmail($params['mail']);

            // Mailadresse nicht vorhanden
            if ($informationCode == '1417') {
                $informationCode = $modelRegistrierung->pruefeDoppelteMailadresse();
            }

            echo $informationCode;
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }
}

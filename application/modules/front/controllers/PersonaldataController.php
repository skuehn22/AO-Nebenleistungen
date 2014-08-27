<?php
/**
 * Registrierung der Kundendaten.
 *
 * + Darstellung des Formulares der Eingabe der Kundendaten
 * + Ermittelt den Inhalt der Comboboxen für ein leeres Formular
 * + Kunde meldet sich mit Benutzername und Passwort an
 * + Kontrolliert das vorhandensein einer Mailadresse
 * + Kontrolliert die Formulardaten ob der Benutzer bereits vorhanden ist.
 *
 * @author Stephan.Krauss
 * @date 10.29.2013
 * @file PersonaldataController.php
 * @package front
 * @subpackage controller
 */
class Front_PersonaldataController extends Zend_Controller_Action
{
    // Konditionen
    private $condition_benutzerrolle_benutzer = 1;

    private $_form = array();
    private $_realParams = array();
    private $pimple = null;
    private $anzeigeSpracheId = null;

    private $requestUrl = null;

    public function init()
    {

        try {
            $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

            $this->pimple = new Pimple_Pimple();
            $this->requestUrl = $this->view->url();

            $request = $this->getRequest();
            $this->realParams = $request->getParams();

            if ($request->getPost()) {
                $form = array();

                // einzelne Formularblöcke
                foreach ($this->realParams as $key => $value) {
                    $keyParts = explode('_', $key);
                    if (count($keyParts) == 2) {
                        $form[$keyParts[0]][$keyParts[1]] = $value;
                    }
                }

                $this->_form = $form;
            }

            // Zugriff auf die Action
            $nutzungAction = array(
                'indexAction' => $this->condition_benutzerrolle_benutzer,
                'checkexistmailAction' => $this->condition_benutzerrolle_benutzer,
                'loginAction' => $this->condition_benutzerrolle_benutzer,
                'checkuserdataAction' => $this->condition_benutzerrolle_benutzer
            );

            $toolZugriffController = new nook_ToolZugriffController();
            $toolZugriffController
                ->setZugriffAction($nutzungAction)
                ->setActionName($this->realParams['action'])
                ->steuerungKontrolleZugriffAction();

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Darstellung des Formulares der Eingabe der Kundendaten
     *
     * Hilfsformular für den Superuser
     * zur Suche nach einem Kunden.
     *
     * @return void
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $model = new Front_Model_Personaldata();

            $navigation = $model->getAktiveStep(1, 11, $params);
            $raintpl->assign('breadcrumb', $navigation);

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            // Kunden ID
            $raintpl->assign('kundenId', $model->getUserIdInformation());

            $raintpl = $this->erststart($raintpl);

            // Info Kontrolle Personendaten
            $raintpl->assign('checkInfo', '');

            $this->view->content = $raintpl->draw("Front_Personaldata_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Ermittelt den Inhalt der Comboboxen für ein leeres Formular
     *
     * + ermitteln Anzeigesprache
     *
     * @param $raintpl
     * @return RainTpl
     */
    private function erststart($raintpl)
    {

        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $frontModelKundendatenFactory = new Front_Model_KundendatenFactory('leer', $anzeigeSpracheId);

        /** @var $variante Front_Model_KundendatenLeer */
        $variante = $frontModelKundendatenFactory->getVariante();

        $raintpl = $variante
            ->steuerungDarstellungLeeresFormularPersonendaten($raintpl)
            ->getRainTpl();

        return $raintpl;
    }

    /**
     * Kunde meldet sich mit Benutzername und Passwort an
     *
     * + erstellt XML Block Kundendaten
     *
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {

            $model = new Front_Model_PersonaldataLogin();
            $loginParams = $model->checkInput($params);
            $kundenDaten = $model->anmeldenUser($loginParams);

            // wenn Kunde bekannt ist
            if (!empty($kundenDaten)) {

                // Kontrolle XML - Block Kundendaten
                $xmlKundendaten = new Front_Model_WarenkorbPersonalDataXML();
                $xmlBlockKundendatenVorhanden = $xmlKundendaten->checkExistPersonalDataXml($kundenDaten['id']);

                // erstellen XML - Block Kundendaten
                if (empty($xmlBlockKundendatenVorhanden)) {
                    // erstellt XML Block Kundendaten
                    $xmlKundendaten->setKundenDaten($kundenDaten['id'], $kundenDaten);
                    $xmlKundendaten->saveKundenDatenXML();
                }

                // umlenken auf Bestandsliste
                $this->_redirect('/front/orderdata/edit/status/3/agb/agb');
            } // wenn keine Kundendaten vorhanden
            else {
                $this->_redirect('/front/personaldata/index/');
            }
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Kontrolliert das vorhandensein einer Mailadresse
     *
     * @return void
     */
    public function checkexistmailAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Front_Model_Personaldata();
            $response = $model->controlIfEmailIsDouble($params['mail']);

            // Mailadresse nicht vorhanden
            if (empty($response)) {
                echo 0;
            } // Mailadresse mindestens schon einmal vorhanden
            else {
                echo 1;
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Kontrolliert die Formulardaten ob der Benutzer bereits vorhanden ist.
     *
     */
    public function checkuserdataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $model = new Front_Model_Personaldata();

            // Info Kontrolle Personendaten
            if (!nook_ToolSuperuser::findSuperuserPerEmail($this->_form['pruefen']['superuser'])) {
                $raintpl->assign('checkInfo', translate('Bitte als Superuser anmelden'));
            } else {
                $userId = $model->findUserByParams($this->_form['pruefen']);

                if ($userId == 0) {
                    $raintpl->assign('checkInfo', translate('Benutzer ist unbekannt'));
                } elseif ($userId != 0) {
                    $raintpl->assign('checkInfo', translate('Sie wurden als Benutzer angemeldet'));
                }
            }

            $this->view->content = $raintpl->draw("Front_Personaldata_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }
}


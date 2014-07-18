<?php
/**
 * Verwaltet das anlegen von neuen Firmen der Programmanbieter
 *
 * + Darstellen Template
 * + Holt die vorhandenen Firmen
 * + Legt eine Programmanbieter Firma an. Trägt Personendaten und Adresse der Firma ein.
 * + Updatet die Angaben der Programmanbieter Firma
 * + Schaltet eine Firma aktiv / passiv
 *
 * @date 11.19.2013
 * @file CompanyController.php
 * @package admin
 * @subpackage controller
 */
class Admin_CompanyController extends Zend_Controller_Action
{

    private $_realParams = array();
    protected $condition_benutzerrolle_anbieter = 5;

    public function init()
    {
        try{
            $request = $this->getRequest();
            $this->_realParams = $request->getParams();

            $nutzungAction = array(
                'findregionAction' => $this->condition_benutzerrolle_anbieter,
                'getactivdatacompanyAction' => $this->condition_benutzerrolle_anbieter,
                'getcompaniesAction' => $this->condition_benutzerrolle_anbieter,
                'getpersonaldataAction' => $this->condition_benutzerrolle_anbieter,
                'indexAction' => $this->condition_benutzerrolle_anbieter,
                'indexAction' => $this->condition_benutzerrolle_anbieter,
                'newcompanyAction' => $this->condition_benutzerrolle_anbieter,
                'saveresponsibledataAction' => $this->condition_benutzerrolle_anbieter,
                'setactivdatacompanyAction' => $this->condition_benutzerrolle_anbieter,
                'updateadmincompanyAction' => $this->condition_benutzerrolle_anbieter
            );

            $toolZugriffController = new nook_ToolZugriffController();
            $toolZugriffController
                ->setZugriffAction($nutzungAction)
                ->setActionName($this->_realParams['action'])
                ->steuerungKontrolleZugriffAction();
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->_realParams);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Ermittelt die Zugehörigkeit zu einer Kooperation.
     *
     * + Programme
     * + Firma
     *
     * @param bool $companyId
     * @param bool $programmId
     */
    protected function checkKooperation($companyId = false, $programmId = false)
    {
        $toolProgrammeEinerKooperation = new nook_ToolProgrammeEinerKooperation();

        // Company
        if($companyId)
            $toolProgrammeEinerKooperation->setCompanyId($companyId);
        // Programm
        elseif($programmId)
            $toolProgrammeEinerKooperation->setprogrammId($programmId);


        $flagZugriffErlaubt = $toolProgrammeEinerKooperation
            ->steuerungErmittlungZugriffAufDieAction()
            ->getFlagZugriffErlaubt();

        if($flagZugriffErlaubt === false){
            echo "{success: false, message: 'Firma gehört nicht zur Kooperation'}";
            exit();
        }

        return;
    }

    /**
     * Darstellen Template
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Company_Index", true);
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die vorhandenen Firmen
     */
    public function getcompaniesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $firmen = $model->getCompanies($params);

            echo "{success: true, data: " . json_encode($firmen['data']) . ", anzahl: " . $firmen['anzahl'] . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Legt eine Programmanbieter Firma an. Trägt Personendaten und Adresse der Firma ein.
     *
     * + salzt Passwort
     *
     */
    public function newcompanyAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);
            unset($params['kundenId']);

            $model = new Admin_Model_Company();

            $model->checkUserData($params);

            // salzen Passwort
            $params['password'] = nook_ToolVerschluesselungPasswort::salzePasswort($params['password']);

            $errors = $model->insertNewCompany($params);

            if (is_array($errors)) {
                echo "{success: false, errors: " . json_encode($errors) . "}";

                return;
            } else {
                echo "{success: true}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Updatet die Angaben der Programmanbieter Firma
     *
     *
     */
    public function updateadmincompanyAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            $model = new Admin_Model_Company();
            $model->checkUserData($params);
            $errors = $model->checkExistMailAdress($params['email'], $params['companyId']);

            if (is_array($errors)) {
                echo "{success: false, errors: " . json_encode($errors) . "}";
            } else {
                $model->updateUserData($params);

                echo "{success: true}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Schaltet eine Firma aktiv / passiv
     */
    public function getactivdatacompanyAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $companyActivData = $model->getActivDataCompany($params);

            if (is_array($companyActivData)) {
                echo "{success: true, data: " . json_encode($companyActivData) . "}";
            } else {
                echo "{success: false}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function setactivdatacompanyAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $kontrolle = $model->setActivDataCompany($params);

            if ($kontrolle == true) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getpersonaldataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $personalData = $model->getPersonalDataFromCompany($params['companyId']);

            echo "{success: true, data: " . json_encode($personalData) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * ??????????????
     */
    public function saveresponsibledataAction()
    {
        $request = $this->getRequest();
        $adressdatenFirma = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $errors = $model->checkExistMailAdress($adressdatenFirma['email'], $adressdatenFirma['companyId']);
            if (is_array($errors)) {
                echo "{success: false, errors: " . json_encode($errors) . "}";

                return;
            }

            $model->kontrolleExistiertDieAngegebeneStadt($adressdatenFirma['city']);

            $errors = $model->saveResponsiblePersonalDataFromCompany($adressdatenFirma);
            if (is_array($errors)) {
                echo "{success: false, errors: " . json_encode($errors) . "}";
            } else {
                echo "{success: true}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $adressdatenFirma);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function findregionAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Company();
            $model->checkPlz($params['plz']);
            $region = $model->getBundeslandEntsprechendPlz($params['plz']);

            echo "{success: true, region: " . json_encode($region) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


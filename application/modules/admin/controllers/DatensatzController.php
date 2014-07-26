<?php
/**
* Verwaltet den Datensatz der Programme
*
* @author Stephan.Krauss
* @date 19.07.2013
* @file DatensatzController.php
* @package admin
* @subpackage controller
*/
class Admin_DatensatzController extends Zend_Controller_Action
{
    private $_condition_is_admin = 10;
    private $_condition_is_provider = 5;
    private $realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        
    }

    /**
     * holt die Daten der Tabelle der Programme
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $raintpl->assign('showBlock', false);
            $raintpl->assign('showCloseButton', 'false');

            $Auth = new Zend_Session_Namespace('Auth');

            // Ist der Benutzer ein Admin ???
            if ($Auth->role_id >= $this->_condition_is_admin) {
                $raintpl->assign('showBlock', true);
                $raintpl->assign('showCloseButton', 'true');
            }

            // Vorauswahl Firma
            if (array_key_exists('company', $params)) {
                $raintpl->assign('vorauswahlCompany', $params['company']);
            } else {
                $raintpl->assign('vorauswahlCompany', '');
            }

            $this->view->content = $raintpl->draw("Admin_Datensatz_Index", true);
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*** View Index ***/

    /**
     * Lädt Inhalt der Tabelle der vorhandenen Programme
     * während des Erststart.
     * Berücksichtigt die Suchparameter.

     */
    public function tabelitemsAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $anzahl = $model->getCountPrograms($params);
            $result = $model->setSearchItems($params);

            echo "{success: true, data: " . json_encode($result) . ", anzahl: " . $anzahl . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcitiesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Busprogramm();
            $cities = $model->getCities();

            echo "{data: " . json_encode($cities) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Update der Programmsprachen eines Programmes
     *
     * @return string
     */
    public function switchlanguageAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $languages = $model->checkLanguages($params);
            $model->saveLanguagesFromProgram($params['programId'], $languages);

            return "{success: true}";

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt ein vorhandenes Templat
     */
    public function gettemplateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Front_Model_Programmdetail();
            // setzen der Sprache
            $model->setLanguage($params['sprache']);

            // Programmdetails
            $model->setProgramId($params['programId']);
            $programDetails = $model->getProgramDetails();

            echo "{
                success: true, data: " . json_encode($programDetails) . "
            }";

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getvalueAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $cellValue = $model->getCellValue($params['programmId'], $params['cell'], $params['sprache']);

            echo "{success: true, data: " . json_encode($cellValue) . "}";

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichert die Daten der Programmbeschreibung
     */
    public function setvalueAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            $model = new Admin_Model_Datensatz();
            $model->setValueDescriptionProgramm($params);


            // Bild
            if (!empty($_FILES['miniBild']['name'])) {
                $kontrolle = $this->_uploadImage($params);
            } else {
                $kontrolle = true;
            }

            if ($kontrolle === true) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    private function _uploadImage($params)
    {
        try {
            if (!empty($_FILES['miniBild'])) {

                $uploadImage = nook_upload::getInstance();
                $image = $_FILES['miniBild'];
                $imageName = $image['name'];
                $uploadImage->create_dir("files/".$params['programmId']);
                //$imageName = $params['programmId'];
                $imagePath = ABSOLUTE_PATH . "/files/".$params['programmId']."/";

                $kontrolleImageTyp = $uploadImage->setImage($image)->setImagePath($imagePath)->setImageName(
                    $imageName
                )->checkImageTyp();
                if ($kontrolleImageTyp) {
                    $kontrolleMove = $uploadImage->moveImage();
                    if ($kontrolleMove) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die vorhandenen Programmsprachen eines Programmes

     */
    public function getlanguagesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $languages = $model->getAvailableProgramLanguages($params['programmId']);

            echo "{success: true, data: " . json_encode($languages) . "}";

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Findet Termine eines Programmes
     * + Öffnungszeiten

     */
    public function findschedulesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Termine eines Programmes
            $adminModelDatensatz = new Admin_Model_Datensatz();
            $termine = $adminModelDatensatz->findSchedulesFromProgram($params['programmId']);

            // Zeiten eines Programmes
            $programmZeit = $this->_ermittleProgrammzeitEinesProgrammes($params);
            $termine = array_merge($termine, $programmZeit);

            // Typ der Öffnungszeiten eines Programmes
            $toolTypOeffnungszeiten = new nook_ToolStartdatumBuchungProgramm();
            $termine['programmzeiten'] = $toolTypOeffnungszeiten
                ->setProgrammId($params['programmId'])
                ->setZeitSekunden(time()) // Dummy
                ->steuerungErmittlungErsterBuchungstag()
                ->getTypOeffnungszeitenProgramm();

            echo "{success: true, data: " . json_encode($termine) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die Durchführungszeit eines Programmes
     *
     * @param $params
     * @return array
     */
    private function _ermittleProgrammzeitEinesProgrammes($params)
    {
        $modelProgrammzeiten = new Admin_Model_Programmzeiten();

        if ($modelProgrammzeiten->validateProgrammdetailId($params['programmId'])) {

            $programmZeit = $modelProgrammzeiten
                ->setProgrammdetailsId($params['programmId'])
                ->getProgrammzeit();

        }

        return $programmZeit;
    }

    /**
     * speichert Termine eines Programmes
     * + Saison und Dauer des Programm
     * + Sperrtage des Programm
     * + Durchführungszeiten des Programmes

     */
    public function setschedulesAction()
    {
        $request = $this->getRequest();
        $termine = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            unset($termine['selectSperrtag']);
            $sperrtage = $termine['sperrtageFuerProgramm'];
            unset($termine['sperrtageFuerProgramm']);
            $typProgrammzeiten = $termine['programmzeiten'];

            $model = new Admin_Model_Datensatz();

            // eintragen Programmzeit
            $termine = $this->_eintragenProgrammzeit($termine);

            // Saison und Dauer des Programm
            $response = $model->setSchedulesFromProgram($termine);

            // Sperrtage des Programm
            $response = $model->setSperrtageFuerProgramm($termine['programmId'], $sperrtage);

            // Typ Programmzeiten
            $frontModelProgrammzeiten = new Front_Model_Programmzeiten();
            $frontModelProgrammzeiten
                ->setProgrammId($termine['programmId'])
                ->setTypOeffnungszeiten($typProgrammzeiten)
                ->steuerungEintragenTypOeffnungszeit();

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Eintrgane Zeiten des Programmes
     *
     * @param $termine
     */
    private function _eintragenProgrammzeit($termine)
    {
        $modelProgrammzeiten = new Admin_Model_Programmzeiten();

        if ($modelProgrammzeiten->validateProgrammdetailId($termine['programmId'])) {
            $termine = $modelProgrammzeiten
                ->setStatus($termine)
                ->setProgrammdetailsId($termine['programmId'])
                ->setZeiten($termine)
                ->insertProgrammzeiten()
                ->reduceTermine($termine);
        }

        unset($termine['programmzeitInformation']);
        unset($termine['programmzeit']);
        unset($termine['programmzeitAktiv']);

        return $termine;
    }

    /**
     * Findet diverse Informationen eines Programmes.
     * Textbausteine eines Programmes werden angezeigt
     */
    public function finddiversesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $diverses = $model->getDiversesFromProgram($params['programmId']);

            echo "{success: true, data: " . json_encode($diverses) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Sperrtage eines Programmes

     */
    public function getsperrtageAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $sperrtage = $model->getSperrtageVonProgramm($params['programmId']);

            echo "{success: true, data: " . json_encode($sperrtage) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function findcountriesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $countries = $model->findCountriesForProgramms();

            echo "{success: true, data: " . json_encode($countries) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function deletezuschlaegeAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $preisvarianten = new Admin_Model_ProgrammvariantenGrid();
            $model = new Admin_Model_Datensatz();

            $model->setDependency('gridPreisvarianten', $preisvarianten);
            $model->setDeleteAlleZuschlaege($params['programmId']);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function setzuschlaegeAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $params['zuschlaege'] = substr($params['zuschlaege'], 0, -1);
            $zuschlaege = explode("#", $params['zuschlaege']);

            $preisvarianten = new Admin_Model_ProgrammvariantenGrid();

            $model = new Admin_Model_Datensatz();
            $model->setDependency('gridPreisvarianten', $preisvarianten);
            $model->setProgrammVarianten($params['programmId'], $zuschlaege);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function findbundeslandAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $bundeslaender = $model->findBundeslaenderForProgramms();

            echo "{success: true, data: " . json_encode($bundeslaender) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichert Diverse Angaben eines Programmes

     */
    public function setdiversesAction()
    {
        $request = $this->getRequest();
        $diversesProgrammDatensatz = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Datensatz();
            $model->updateDiversesEinesProgrammes($diversesProgrammDatensatz);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Preisvarianten eines Programmes
     */
    public function getpreisvariantenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if (array_key_exists('start', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            } else {
                $start = 0;
                $limit = 5;
            }

            $model = new Admin_Model_DatensatzPreisvarianten();
            $model->checkProgrammId($params['programmId']);
            $preisvariantenEinesProgrammes = $model->vorhandenePreisvariantenEinesProgrammes($start, $limit);

            echo "{success: true, data: " . json_encode(
                    $preisvariantenEinesProgrammes['data']
                ) . ", anzahl: " . $preisvariantenEinesProgrammes['anzahl'] . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**** Preisvarianten *****/

    /**
     * Update der Preisvariante einer
     * Preisvariante eines Programmes
     */
    public function bearbeitenpreisvarianteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzPreisvarianten();
            $params = $model->checkDatenPreisvariante($params);
            $kontrolle = $model->bearbeitenVorhandenePreisvariante($params);

            if ($kontrolle === true) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function loeschenpreisvarianteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzPreisvarianten();
            $model->checkDatenPreisvarianteId($params['preisvarianteId']);
            $kontrolle = $model->loeschePreisvariante($params['preisvarianteId']);

            if ($kontrolle === true) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function speichernpreisvarianteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzPreisvarianten();
            $params = $model->checkDatenPreisvariante($params);
            $kontrolle = $model->speichernNeuePreisvariante($params);

            if ($kontrolle === true) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt den Werbepreis zur Darstellung
     *
     * @return void
     */
    public function getbesonderheitenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzWerbepreis();
            $data = $model->getBesonderheiten($params['programmId']);

            echo "{success: true, data: " . json_encode($data) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /************* Werbepreis **************/

    /**
     * Setzt den Werbepreis und die Buchungspauschale
     *
     * @return void
     */
    public function besonderheitenPreisvarianteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzWerbepreis();
            $params = $model->checkProgrammWerbepreis($params);

            $werbepreis = $model->setBesonderheitenPreisvariante($params);

            echo "{success: true, data: " . json_encode($werbepreis) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*********** Stornofristen ******************/

    public function holestornofristenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzStornofristen();
            $FaId = $model->kontrolleProgrammId($params['FaId']);
            $zahlungsziele = $model->stornofristenEinesProgrammes($FaId);

            echo "{success: true, data: " . json_encode($zahlungsziele) . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function speicherestornofristenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzStornofristen();

            $params['FaId'] = $model->kontrolleProgrammId($params['FaId']);
            $zahlungsziele = $params = $model->kontrolleStornofristen($params);
            $kontrolle = $model->speichernStornofristen($zahlungsziele);

            if ($kontrolle === true) {
                echo "{success: true}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*** Sicht 'Öffnungszeiten ***/
    public function setzeitenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzOeffnungszeiten();
            $model->checkOeffnungszeiten($params);
            $oeffnungszeiten = $model->mapOeffnungszeiten($params);
            $model->setOeffnungszeiten($oeffnungszeiten);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt die Öffnungszeiten eines Programmes

     */
    public function getzeitenAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzOeffnungszeiten();
            $model->checkOeffnungszeiten($params);
            $form = $model->getOeffnungszeiten();

            if (count($form) > 0) {
                echo "{success: true, data: " . json_encode($form) . "}";
            } else {
                echo "{success: true}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Holt eine vorhandene Zusatzinformation

     */
    public function getzusatzinformationAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzZusatzinformation();
            $response = $model->getZusatzinformationProgramm($params['programmId']);

            if (array_key_exists('fieldZusatzinformation', $response)) {
                echo "{success: true, data: " . json_encode($response) . "}";
            } else {
                echo "{success: true}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /*** Sicht Zusatzinformation ***/

    public function setzusatzinformationAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzZusatzinformation();
            $model->setZusatzinformationProgramm($params['programId'], $params['fieldZusatzinformation']);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function clearzusatzinformationAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzZusatzinformation();
            $zusatzinformation = $model->cleanZusatzinformation($params['fieldZusatzinformation']);
            $model->setZusatzinformationProgramm($params['programId'], $zusatzinformation);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /********** Bestätigungstexte Preisvarianten ************/

    public function preisbeschreibungViewAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzPreisbeschreibung();

            $confirmTexte = array(
                "confirm_1_de" => ' ',
                "confirm_1_en" => ' '
            );

            $bestaetigungsTexte = $model
                ->setProgrammId($params['programmId'])
                ->setPreisvarianteId($params['preisvarianteId'])
                ->getPreisvarianteBestaetigungstexte();

            if (!empty($bestaetigungsTexte['confirm_1_de'])) {
                $confirmTexte['confirm_1_de'] = $bestaetigungsTexte['confirm_1_de'];
            }

            if (!empty($bestaetigungsTexte['confirm_1_en'])) {
                $confirmTexte['confirm_1_en'] = $bestaetigungsTexte['confirm_1_en'];
            }

            if (!empty($confirmTexte)) {
                echo "{success: true, data: " . json_encode($confirmTexte) . "}";
            } else {
                echo "{success: true}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * editieren der preisbeschreibung
     */
    public function preisbeschreibungEditAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_DatensatzPreisbeschreibung();
            $model
                ->setProgrammId($params['programmId'])
                ->setPreisvarianteId($params['preisvarianteId'])
                ->setConfirmTexte($params);

            echo "{success: true}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Löscht ein Programmbild
     */
    public function loeschenProgrammBildAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $params = $this->realParams;

            $modelDatensatzProgrammbild = new Admin_Model_DatensatzProgrammbild();
            $kontrolle = $modelDatensatzProgrammbild
                ->setProgrammId($params['programmId'])
                ->loeschenProgrammbild();

            if ($kontrolle == 1) {
                echo "{success: true}";
            } else {
                echo "{success: false}";
            }
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Kontrolliert ob ein
     * Programmbild vorhanden ist.
     * Wenn ja, dann programId = Programmnummer.
     * Wenn nein, dann 0.

     */
    public function kontrolleBildVorhandenAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $modelDatensatzProgrammbild = new Admin_Model_DatensatzProgrammbild();
            $bildId = $modelDatensatzProgrammbild->checkExistProgrammBild($params['programId'], $params['bildTyp']);

            // Rückmeldung Bild URL
            echo "{success: true, bildId: " . $bildId . "}";
        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Wechselt den Buchungstyp
     * eines vorhandenen Programmes

     */
    public function wechselBuchungstypAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

            $pimple['tabelleProgrammdetails'] = function ($c) {
                return new Application_Model_DbTable_programmedetails();
            };

            $modelDatensatzBuchungstyp = new Admin_Model_DatensatzBuchungstyp($pimple);
            if ($modelDatensatzBuchungstyp->isValidProgrammId($params['programmId'])) {
                $kontrolle = $modelDatensatzBuchungstyp
                    ->setProgrammId($params['programmId'])
                    ->wechselBuchungstypProgramm();

                // Rückmeldung wechsel Buchungstyp
                if ($kontrolle) {
                    echo "{success: true}";
                } else {
                    echo "{success: false}";
                }
            } else {
                echo "{success: false}";
            }

        } catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Schaltet den Modus des Programmes zwischen Aktiv und Passiv
     *
     * + aktiv = Programm wird im Frontend angezeigt
     */
    public function wechselAktivAction(){
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $kontrolle = false;
            $kooperationId = 0;

            // wechsel der Zugehörigkeit einer Kooperation
            if(strstr($params['spalte'],'sichtbar')){

                if(strstr($params['spalte'], 'Hob'))
                    $kooperationId = 1;
                elseif(strstr($params['spalte'], 'Austria'))
                    $kooperationId = 2;
                elseif(strstr($params['spalte'], 'Ao'))
                    $kooperationId = 3;

                if($kooperationId > 0){
                    $adminModelKooperationEintragenZugehoerigkeit = new Admin_Model_KooperationEintragenZugehoerigkeit();
                    $adminModelKooperationEintragenZugehoerigkeit
                        ->setKooperationId($kooperationId)
                        ->setProgrammId($params['programmId'])
                        ->steuerungSetzenKooperationEinesProgrammes();
                }
            }
            // Statuswechsel
            else{
                $adminModelDatensatzAktivPassiv = new Admin_Model_DatensatzAktivPassiv();
                $fehlermeldung = $adminModelDatensatzAktivPassiv
                    ->setProgrammId($params['programmId'])
                    ->setSpalte($params['spalte'])
                    ->steuerungWechselStatusAktiv()
                    ->getMessage();
            }



            if(count($fehlermeldung) == 0)
                $kontrolle = true;

            if($kontrolle == true)
                echo "{success: true}";
            else
                echo "{success: false}";

        } catch (Exception $e) {
           $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
           echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    private function _getPimple(array $classes)
    {

        $pimple = new Pimple_Pimple();

        foreach ($classes as $className => $classElements) {
            if (isset($classElements['options'])) {
                $object = new $classes[$className]['path']($classes[$className]['options']);
            } else {
                $object = new $classes[$className]['path']();
            }

            $pimple->offsetSet($className, $object);
        }

        return $pimple;
    }
}
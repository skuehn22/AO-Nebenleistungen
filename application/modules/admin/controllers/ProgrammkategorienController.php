<?php
/**
 * Der Administrator kann einem Programm ein oder mehrere Programmkategorien zuordnen
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package admin
 * @subpackage controller
 */
class Admin_ProgrammkategorienController extends Zend_Controller_Action
{

    private $realParams = array();
    public $pimple = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Erstellt den Servicecontainer mit den benötigten Models, Tools und Tabellen
     *
     * @return Pimple_Pimple
     */
    protected function servicecontainer()
    {
        $pimple = new Pimple_Pimple();



        // $this->pimple = $pimple;

        return $pimple;
    }

    /**
     * Templat laden
     *
     * + Parent Template
     */
    public function indexAction()
    {

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Programmkategorien_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Darstellen der vorhandenen Programme in einer Tabelle
     */
    public function programmtabelleAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(!array_key_exists('start', $params)){
                $params['start'] = 0;
                $params['limit'] = 20;
            }

            $adminModelProgrammeAnzeigen = new Admin_Model_ProgrammeAnzeigen();

            $adminModelProgrammeAnzeigen
                ->setStart($params['start'])
                ->setLimit($params['limit']);

            if(array_key_exists('sucheCity',$params) and !empty($params['sucheCity']))
                $adminModelProgrammeAnzeigen->setCityName($params['sucheCity']);

            if(array_key_exists('sucheProgramm',$params) and !empty($params['sucheProgramm']))
                $adminModelProgrammeAnzeigen->setProgrammName($params['sucheProgramm']);

            $programme = $adminModelProgrammeAnzeigen
                ->steuerungAnzeigenProgramme()
                ->getProgramme();

            $anzahlProgramme = $adminModelProgrammeAnzeigen->getAnzahlProgramme();

            echo "{success: true, data: " . json_encode($programme) . ", anzahl: " . $anzahlProgramme . "}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Darstellen bereits vorhandener Kategorien
     */
    public function ermittelnKategorienAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $adminModelProgrammkategorien = new Admin_Model_ProgrammKategorien();

            $adminModelProgrammkategorien->steuerungErmittlungProgrammkategorien();
            $programmKategorien = $adminModelProgrammkategorien->getProgrammKategorien();

            $anzahlProgrammKategorien = $adminModelProgrammkategorien->getAnzahlProgrammkategorien();

            echo "{success: true, data: " . json_encode($programmKategorien) . ", anzahl: " .$anzahlProgrammKategorien. "}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die Kategorien eines Programmes
     */
    public function kategorienProgrammAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $adminModelProgrammKategorienZuordnen = new Admin_Model_ProgrammKategorienZuordnen();
            $anzahlKategorien = $adminModelProgrammKategorienZuordnen
                ->setProgrammId($params['programmId'])
                ->steuerungErmittlungProgrammkategorienEinesProgrammes()
                ->getAnzahlProgrammkategorien();

            $kategorienEinesProgrammes = $adminModelProgrammKategorienZuordnen->getKategorienEinesProgrammes();

            echo "{success: true, data: ".json_encode($kategorienEinesProgrammes)."}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichert die Programmkategorien eines Programmes
     */
    public function speichernKategorienAction()
    {
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $kategorien = json_decode($params['kategorien']);

            $werteKategorien = array();
            foreach($kategorien as $key => $value){
                $werteKategorien[] = (array) $value;
            }

            $adminProgrammKategorienSpeichern = new Admin_Model_ProgrammKategorienSpeichern();
            $anzahlGespeicherteProgrammkategorien = $adminProgrammKategorienSpeichern
                ->setProgrammId($params['programmId'])
                ->setKategorienEinesProgrammes($werteKategorien)
                ->steuerungSpeichernProgrammkategorienEinesProgrammes()
                ->getAnzahlGespeicherteProgrammkategorien();

            if($anzahlGespeicherteProgrammkategorien > 0)
                echo "{success: true}";
            else
                echo "{success: false}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}


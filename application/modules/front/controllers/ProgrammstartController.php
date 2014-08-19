<?php
/**
 * Suche nach Programmen in einer Stadt
 *
 * @author Stephan.Krauss
 * @date 11.01.2013
 * @file ProgrammstartController.php
 * @package front
 * @subpackage controller
 */
class Front_ProgrammstartController extends Zend_Controller_Action{
	
    private $_realParams = array();
    private $requestUrl = null;

    protected $condition_programme_pro_seite = 10;
    protected $condition_ziffer_startseite = 1;
    protected $condition_anzahl_sichtbare_seiten = 3;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();
    }

    /**
     * Zeigt die Programme in einer Stadt an
     */
    public function indexAction(){
		$params = $this->realParams;

		try{

            // speichern Parameter in Session
            $params = $this->storeIndexAction($params);

            $raintpl = raintpl_rainhelp::getRainTpl();

            // Breadcrumb
            $this->erstellenBreadcrumb($params, $raintpl);

            // erstellen Paginator
            $raintpl = $this->erstellenPaginatorProgramme($params, $raintpl);

            // Kategorien in der Stadt
            if( (array_key_exists('city', $params)) and (!is_null($params['city'])) )
                $raintpl = $this->ermittelnKategorienDerStadt($params, $raintpl);


            $frontModelProgrammstart = new Front_Model_Programmstart();

            // Programme
            $raintpl = $this->ermittelnProgramme($frontModelProgrammstart, $params, $raintpl);

            // Stadtbeschreibung
            $raintpl = $this->ermittelnStadtbeschreibung($frontModelProgrammstart, $raintpl, $params);

			$this->view->content = $raintpl->draw( "Front_Programmstart_Index", true );
		}
		catch(Exception $e){
			$e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
			$this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
		}
    }

    /**
     * Ermittelt die Kategorien einer Stadt
     *
     * + wenn eine Programmkategorie aktiv ist
     *
     * @param array $params
     * @param $raintpl
     * @return mixed
     */
    protected function ermittelnKategorienDerStadt(array $params, $raintpl)
    {
        $frontModelProgrammeKategorienStadt = new Front_Model_ProgrammeKategorienStadt();
        $frontModelProgrammeKategorienStadt
            ->setCityId($params['city'])
            ->setAnzeigeSpracheId($params['sprache']);

        // wenn eine Programmkategorie aktiv ist
        if( (array_key_exists('programmkategorie', $params)))
            $frontModelProgrammeKategorienStadt->setAktiveProgrammKategorieStadt($params['programmkategorie']);

        // Kategorien einer Stadt
        $kategorien = $frontModelProgrammeKategorienStadt
            ->steuerungErmittlungProgrammkategorienEinerStadt()
            ->getProgrammKategorienStadt();




        $raintpl->assign('kategorien', $kategorien);

        return $raintpl;
    }

    /**
     * ermitteln der Stadtbeschreibung
     *
     * @param $frontModelProgrammstart
     * @param $raintpl
     * @param $params
     * @return mixed
     */
    protected function ermittelnStadtbeschreibung(Front_Model_Programmstart $frontModelProgrammstart, $raintpl, $params)
    {
        // Beschreibung Programm Highlights in der Stadt
        $stadtbeschreibung = $frontModelProgrammstart->getStadtbeschreibung($params['city']);
        $raintpl->assign('stadtbeschreibung', $stadtbeschreibung);

        return $raintpl;
    }

    /**
     * Speichern der Parameter im Session Namespce 'programmsuche'
     *
     * + Unterscheidung zwischen Pflichtwerten und optionale Werte
     *
     * @param $params
     * @return mixed
     */
    protected function storeIndexAction($params)
    {
        // löschen Namespace 'programmsuche'
        if( (!array_key_exists('seite', $params)) and (!array_key_exists('programmkategorie', $params)) )
            Zend_Session::namespaceUnset('programmsuche');

        // Namespace anlegen / aufrufen
        $nameSpaceProgrammsuche = new Zend_Session_Namespace('programmsuche');

        // alle Kategorien
        if( (array_key_exists('programmkategorie',$params)) and ($params['programmkategorie'] == 0) ){
            unset($params['programmkategorie']);

            // entfernen Programmkategorie 'alle'
            if( isset($nameSpaceProgrammsuche->programmkategorie) )
                unset($nameSpaceProgrammsuche->programmkategorie);
        }

        /*** Pflichtwerte ***/

        // City ID
        if(array_key_exists('city', $params))
            $nameSpaceProgrammsuche->cityId = $params['city'];

        $params['city'] = $nameSpaceProgrammsuche->cityId;

        // Anzeigesprache ID
        $spracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $nameSpaceProgrammsuche->spracheId = $spracheId;
        $params['sprache'] = $spracheId;

        // Programme pro Seite
        $programmeProSeite = Zend_Registry::get('static')->items->programItemsPerPage;
        $nameSpaceProgrammsuche->programmeProSeite = $programmeProSeite;
        $params['programmeProSeite'] = $programmeProSeite;

        /*** optionale Werte ***/

        // Startseite
        if(!array_key_exists('seite', $params)){
            $nameSpaceProgrammsuche->seite = $this->condition_ziffer_startseite;
            $params['seite'] = $this->condition_ziffer_startseite;
        }
        else
            $nameSpaceProgrammsuche->seite = $params['seite'];

        // Programmkategorie
        if(array_key_exists('programmkategorie', $params))
            $nameSpaceProgrammsuche->programmkategorie = $params['programmkategorie'];

        if(isset($nameSpaceProgrammsuche->programmkategorie))
            $params['programmkategorie'] = $nameSpaceProgrammsuche->programmkategorie;

        return $params;
    }

    public function zusatzartikelAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();
		
		try{
			$this->_buildStandard($params);
			
			// finden der Zusatzartikel
			$this->_model->setFaId($params['zusatzItems']);
			$this->_model->setPersons($params['persons']);
			
			$additionalItems = $this->_model->getAdditionalItems($params['city']);
			$this->_raintpl->assign('cityEvents', $additionalItems);
			
			$this->_raintpl->assign('Ort', '');
			
			$actualPageNumber = $this->_model->getActualPageNumber();
			$this->_raintpl->assign('actualPageNumber', $actualPageNumber);
			
			$this->view->content = $this->_raintpl->draw( "Front_Programmstart_Index", true );
		}
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Suche nach Programmen mit einem Suchstring
     */
    public function programmSucheAction(){
		$params = $this->realParams;

		try{
            $this->_redirect('/front/programmstart/index/suche/'.$params['suche']);
		}
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Erstellt die Anzeige der Navigation / Breadcrumb
     *
     * @param $params
     * @param $raintpl
     */
    private function erstellenBreadcrumb($params, $raintpl)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep(1, 2)
            ->setParams($params)
            ->getNavigation();

        $raintpl->assign('breadcrumb', $navigation);
    }

    /**
     * Erstellt Navigationsleiste zur Programmsuche
     *
     * + Suchparameter:
     * + sprache ID
     * + City ID
     * + Suchbegriff
     * + Seitennummer
     * + Programmkategorie
     * + Seite vor
     * + Seite zurück
     *
     * @param $model
     * @param $page
     * @param $raintpl
     * @return object
     */
    private function erstellenPaginatorProgramme($params, $raintpl)
    {
        $frontModelProgrammeSeitenSuchen = new Front_Model_ProgrammeSeitenSuchen();

        $frontModelProgrammeSeitenSuchen
            ->setProgrammeProSeite($this->condition_programme_pro_seite)
            ->setCityId($params['city'])
            ->setSpracheId($params['sprache'])
            ->setStartSeite($params['seite'])
            ->setAnzahlSichtbareSeiten($this->condition_anzahl_sichtbare_seiten);

        // Programmkategorie
        if(array_key_exists('programmkategorie',$params))
            $frontModelProgrammeSeitenSuchen->setProgrammkategorieId($params['programmkategorie']);

        $seiten = $frontModelProgrammeSeitenSuchen
            ->steuerungErmittlungAnzahlProgramme()
            ->getSeiten();

        $raintpl->assign('paginator', $seiten);

        return $raintpl;
    }

    /**
     * Ermittelt die Programme entsprechend der Suchparameter
     *
     * @param $params
     * @param $raintpl
     * @return Front_Model_Programmstart
     */
    protected function ermittelnProgramme(Front_Model_Programmstart $frontModelProgrammstart, $params, $raintpl)
    {
        // Sprache
        $frontModelProgrammstart->findLanguage();

        // Stadtname
        if(array_key_exists('city',$params)){
            $frontModelProgrammstart->setCityId($params['city']);
            $ort = $frontModelProgrammstart->getStadtName();
            $raintpl->assign('ort', $ort);
        }

        // City Id
        if(array_key_exists('city',$params))
            $raintpl->assign('cityId', $params['city']);

        // aktuelle Seite
        $frontModelProgrammstart->setActualPages($params['seite']);

        // Programme pro Seite
        $frontModelProgrammstart->setAnzahlProgrammeProSeite($params['programmeProSeite']);

        // Programmkategorie der Stadt
        if(array_key_exists('programmkategorie', $params))
            $frontModelProgrammstart->setProgrammkategorieStadt($params['programmkategorie']);

        // Programme in der Stadt, Suche
        if (array_key_exists('suche', $params))
            $frontModelProgrammstart->setSuchparameterProgramm($params['suche']);

        // ermitteln Subdomain
        $subdomain = Zend_Registry::get('subdomain');

        // ermitteln Filiale ID
        $toolDomainFiliale = new nook_ToolDomainFiliale();
        $gefundeneFilialeId = $toolDomainFiliale
            ->setSubdomain($subdomain)
            ->steuerungErmittlungFilialeId()
            ->getFilialeId();

        // anzeigen Programme der Filiale
        if($gefundeneFilialeId != false)
            $frontModelProgrammstart->setFilialeId($gefundeneFilialeId);

        $programmeCity = $frontModelProgrammstart
            ->steuerungErmittlungProgrammeInEinerStadt()
            ->getCityEvents();

        $raintpl->assign('cityEvents', $programmeCity);

        return $raintpl;
    }
}
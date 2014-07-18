<?php 
 /**
 * Sucht die Programme in einerStadt mittels verschiedener Parameter
 *
 * @author Stephan.Krauss
 * @date 29.11.2013
 * @file ToolProgrammeInEinerStadt.php
 * @package tools
 */
class nook_ToolProgrammeInEinerStadt
{
    // Informationen

    // Tabellen / Views
    /** @var $viewProgrammeEinerStadt Application_Model_DbTable_viewProgrammeEinerStadt */
    protected $viewProgrammeEinerStadt = null;
    protected $viewProgrammeEinerStadtFiliale = null;
    protected $viewProgrammeEinerStadtRolle = null;
    protected $viewProgrammeEinerStadtAustria = null;

    // Tools

    // Konditionen
    protected $condition_rolle_administrator = 10;

    // Zustände
    protected $start = null;
    protected $ende = null;
    protected $cityId = null;
    protected $anzeigeSpracheId = null;
    protected $suchbegriff = null;
    protected $programmkategorieId = null;
    protected $rolleBenutzer = null;
    protected $filialeId = null;
    protected $statusOfflinebucher = false;

    protected $programmeEinerStadt = array();
    protected $anzahlProgramme = 0;

    public function __construct()
    {
        $this->servicecontainer();
    }

    /**
     * Servicecontainer
     *
     * + Application_Model_DbTable_viewProgrammeEinerStadt
     * + Application_Model_DbTable_viewProgrammeEinerStadtRolle
     */
    protected function servicecontainer()
    {
        $this->viewProgrammeEinerStadt = new Application_Model_DbTable_viewProgrammeEinerStadt();
        $this->viewProgrammeEinerStadtRolle = new Application_Model_DbTable_viewProgrammeEinerStadtRolle();
        $this->viewProgrammeEinerStadtAustria = new Application_Model_DbTable_viewProgrammeEinerStadtAustria();
        $this->viewProgrammeEinerStadtFiliale = new Application_Model_DbTable_viewProgrammeEinerStadtFiliale();

        return;
    }

    /**
     * Steuert die Ermittlung der Programme in einer Stadt.
     *
     * + Verwendung eines Suchbegriffes ist optional.
     * + Programme entsprechend der Rolle werden angezeigt
     * + Es werden Programme entsprechend der URL angezeigt
     *
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function steuerungErmittlungProgrammeInEinerStadt()
    {
        if(is_null($this->start))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->ende))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->anzeigeSpracheId))
            throw new nook_Exception('Anzeigesprache ID fehlt');

        if(is_null($this->rolleBenutzer))
            throw new nook_Exception('Benutzerrolle fehlt');

        // Suchbegriff nicht belegt
        if(is_null($this->suchbegriff))
            $this->suchbegriff = false;

        // Programmkategorie ID nicht belegt
        if(is_null($this->programmkategorieId))
            $this->programmkategorieId = false;

        // Programme einer Filiale
        if( (!is_null($this->filialeId) and ($this->filialeId > 1)) )
            $view = $this->viewProgrammeEinerStadtFiliale;
        // normale Benutzer, Herden
        elseif( ($this->rolleBenutzer < $this->condition_rolle_administrator) and ($this->statusOfflinebucher === false) )
            $view = $this->viewProgrammeEinerStadt;
        // Administrator oder Offlinebucher
        elseif( ($this->rolleBenutzer == $this->condition_rolle_administrator) or ($this->statusOfflinebucher === true) )
            $view = $this->viewProgrammeEinerStadtRolle;

        $programmeInEinerStadt = $this->ermittelnProgrammeInEinerStadt($view, $this->cityId, $this->suchbegriff, $this->programmkategorieId, $this->filialeId);
        $this->programmeEinerStadt = $programmeInEinerStadt;

        $this->anzahlProgramme = count($programmeInEinerStadt);

        return $this;
    }

    /**
     * @param $statusOfflinebucher
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setStatusOfflinebucher($statusOfflinebucher)
    {
        $this->statusOfflinebucher = $statusOfflinebucher;

        return $this;
    }

    /**
     * Ermittelt die Programme in einer Stadt
     *
     * + optionale Verwendung eines Suchbegriffes
     * + optionale Verwendung der Suche nach einer Stadt, wenn cityId > 0
     *
     * @param $cityId
     * @param $suchbegriff
     * @return array
     */
    protected function ermittelnProgrammeInEinerStadt(Zend_Db_Table_Abstract $view, $cityId = false, $suchbegriff = false, $programmkategorieId = false, $filialeId = false)
    {
        $select = $view->select();

        // Suche nach Stadt und Stadt / allgemeines Rabattprogramm
        if($cityId > 0){

            // Suche nach Stadt und zusätzlich Rabatt - Programm
            if($view instanceof Application_Model_DbTable_viewProgrammeEinerStadtRolle){
                $staticParams = nook_ToolStatic::getStaticWerte();
                $rabattProgrammId = $staticParams['rabatt']['programmId'];

                $whereCityIdRabattId = new Zend_Db_Expr("ortId = ".$cityId." or id = ".$rabattProgrammId);
                $select->where($whereCityIdRabattId);
            }
            // Suche nach Stadt
            else{
                $whereCityId = "ortId = ".$cityId;
                $select->where($whereCityId);
            }
        }

        // Suche nach Begriff
        if(!empty($this->suchbegriff)){
            // $whereNameOdertext = new Zend_Db_Expr("(progname like '%".$suchbegriff."%' or txt like '%".$suchbegriff."%')");
            $whereName = new Zend_Db_Expr("progname like '%".$suchbegriff."%'");
            $select->where($whereName);
        }

        // Sprache
        $select->where('sprache = '.$this->anzeigeSpracheId);

        // Programmkategorie ID
        if(!empty($programmkategorieId)){
            $whereProgrammkategorieId = "programmkategorieId = ".$programmkategorieId;
            $select->where($whereProgrammkategorieId);
        }
        else{
            $select->group('id');
        }

        // Filiale ID
        if($filialeId > 1){
            $whereFilialeId = "filialeId = '".$filialeId."'";
            $select->where($whereFilialeId);
        }


        $select->limit($this->ende, $this->start);
        $select->order("ortId asc")->order("progname asc");

        $query = $select->__toString();

        $rows = $this->viewProgrammeEinerStadt->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @param $anzeigeSpracheId
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setAnzeigeSpracheId($anzeigeSpracheId)
    {
        $anzeigeSpracheId = (int) $anzeigeSpracheId;
        if($anzeigeSpracheId == 0)
            throw new nook_Exception('falscher Anfangswert');

        $this->anzeigeSpracheId = $anzeigeSpracheId;

        return $this;
    }

    /**
     * @param $filialeId
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setFilialeId($filialeId)
    {
        $this->filialeId = $filialeId;

        return $this;
    }

    /**
     * @param $cityId
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setCityId($cityId)
    {
        $cityId = (int) $cityId;
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @param $ende
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setEnde($ende)
    {
        $ende = (int) $ende;
        if($ende == 0)
            throw new nook_Exception('falscher Anfangswert');

        $this->ende = $ende;

        return $this;
    }

    /**
     * @param $start
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setStart($start)
    {
        if(0 == $start){
            $this->start = $start;

            return $this;
        }

        $start = (int) $start;
        if($start == 0)
            throw new nook_Exception('falscher Anfangswert');

        $this->start = $start;

        return $this;
    }

    /**
     * @param $programmkategorieId
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setProgrammkategorie($programmkategorieId)
    {
        $programmkategorieId = (int) $programmkategorieId;
        $this->programmkategorieId = $programmkategorieId;

        return $this;
    }

    /**
     * @param $rolleBenutzer
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setRolleBenutzer($rolleBenutzer)
    {
        $rolleBenutzer = (int) $rolleBenutzer;
        $this->rolleBenutzer = $rolleBenutzer;

        return $this;
    }

    /**
     * @param $suchbegriff
     * @return nook_ToolProgrammeInEinerStadt
     */
    public function setSuchbegriff($suchbegriff)
    {
        $suchbegriff = trim($suchbegriff);

        $suchbegriff = mb_strtolower($suchbegriff, 'UTF-8');

        if(strlen($suchbegriff) < 3)
            throw new nook_Exception('Suchbegriff zu klein oder fehlt');

        $this->suchbegriff = $suchbegriff;

        return $this;
    }

    /**
     * @return array
     */
    public function getProgrammeEinerStadt()
    {
        return $this->programmeEinerStadt;
    }

    /**
     * @return int
     */
    public function getAnzahlProgramme()
    {
        return $this->anzahlProgramme;
    }
}
 
<?php
/**
 * Dem Benutzer werden die Kategorien der Stadt angezeigt
 *
 * @author Stephan
 * @date 11.03.2014
 * @file ProgrammeKategorienStadt.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_ProgrammeKategorienStadt
{
    protected $cityId = null;
    protected $anzeigeSpracheId = null;
    protected $aktiveProgrammkategorieId = null;

    protected $viewStadtProgrammeKategorien = null;

    protected $programmKategorienStadt = array();

    /**
     * @param Zend_Db_Table_Abstract $viewStadtProgrammeKategorien
     * @return Front_Model_ProgrammeKategorienStadt
     */
    public function setViewStadtProgrammeKategorien(Zend_Db_Table_Abstract $viewStadtProgrammeKategorien)
    {
        $this->viewStadtProgrammeKategorien = $viewStadtProgrammeKategorien;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_viewStadtProgrammeKategorien
     */
    public function getViewStadtProgrammeKategorien()
    {
        if(is_null($this->viewStadtProgrammeKategorien))
            $this->viewStadtProgrammeKategorien = new Application_Model_DbTable_viewStadtProgrammeKategorien();

        return $this->viewStadtProgrammeKategorien;
    }

    /**
     * @param $aktiveProgrammkategorieId
     * @return Front_Model_ProgrammeKategorienStadt
     */
    public function setAktiveProgrammKategorieStadt($aktiveProgrammkategorieId)
    {
        $aktiveProgrammkategorieId = (int) $aktiveProgrammkategorieId;
        $this->aktiveProgrammkategorieId = $aktiveProgrammkategorieId;

        return $this;
    }

    /**
     * @param $anzeigeSpracheId
     * @return Front_Model_ProgrammeKategorienStadt
     */
    public function setAnzeigeSpracheId($anzeigeSpracheId)
    {
        $anzeigeSpracheId = (int) $anzeigeSpracheId;
        $this->anzeigeSpracheId = $anzeigeSpracheId;

        return $this;
    }

    /**
     * @param $cityId
     * @return Front_Model_ProgrammeKategorienStadt
     */
    public function setCityId($cityId)
    {
        $cityId = (int) $cityId;
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Programmkategorien einer Stadt
     *
     * + berücksichtigt Anzeigesprache
     *
     * @return Front_Model_ProgrammeKategorienStadt
     * @throws Exception
     */
    public function steuerungErmittlungProgrammkategorienEinerStadt()
    {
        try{
            if(is_null($this->cityId))
                throw new nook_Exception('City ID fehlt');

            if(is_null($this->anzeigeSpracheId))
                throw new nook_Exception('Anzeigesprache ID fehlt');

            $viewStadtProgrammeKategorien = $this->getViewStadtProgrammeKategorien();

            // ermitteln Kategorien einer Stadt
            $programmKategorienStadt = $this->ermittelnProgrammkategorienStadt($this->cityId, $this->anzeigeSpracheId, $viewStadtProgrammeKategorien);

            // CSS aktiv / passiv
            $programmKategorienStadt = $this->cssProgrammKategorien($programmKategorienStadt, $this->aktiveProgrammkategorieId);

            $this->programmKategorienStadt = $programmKategorienStadt;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Hat die Kategorie Programme
     *
     * @param Front_Model_CheckProgrammeKategorieStadt $modelCheckProgrammeKategorieStadt
     * @param array $programmKategorienStadt
     * @return array
     */
    protected function hatKategorieProgramme(Front_Model_CheckProgrammeKategorieStadt $modelCheckProgrammeKategorieStadt,array $programmKategorienStadt)
    {
        $programmKategorienStadtMitProgramme = array();

        for($i = 0; $i < count($programmKategorienStadt); $i++){
            $kategorie = $programmKategorienStadt[$i];

            $anzahlProgrammeEinerKategorie = $modelCheckProgrammeKategorieStadt
                ->setKategorieId($kategorie['programmKategorieId'])
                ->steuerungErmittlungAnzahlProgrammeEinerKategorie()
                ->getAnzahlProgramme();

            if($anzahlProgrammeEinerKategorie > 0)
                $programmKategorienStadtMitProgramme[] = $kategorie;
        }

        return $programmKategorienStadtMitProgramme;
    }

    /**
     * stattet die Programmkategorien der Stadt mit einer CSS Klasse aus
     *
     * @param array $programmKategorienStadt
     * @param $aktiveProgrammkategorieId
     * @return array
     */
    protected function cssProgrammKategorien(array $programmKategorienStadt, $aktiveProgrammkategorieId)
    {
        for($i = 0; $i < count($programmKategorienStadt); $i++){
            $kategorieStadt = $programmKategorienStadt[$i];

            if( (!is_null($aktiveProgrammkategorieId)) and ($kategorieStadt['programmKategorieId'] == $aktiveProgrammkategorieId) )
                $programmKategorienStadt[$i]['css'] = 'aktiveStep';
            else
                $programmKategorienStadt[$i]['css'] = 'passivStep';
        }

        if(empty($aktiveProgrammkategorieId)){
            $anzahlProgrammKategorien = count($programmKategorienStadt);
            $countKategorieAlle = $anzahlProgrammKategorien - 1;
            $programmKategorienStadt[$countKategorieAlle]['css'] = 'aktiveStep';
        }

        return $programmKategorienStadt;
    }

    /**
     * Ermitteln der Programmkategorien einer Stadt
     *
     * + Anzeigesprache wird berücksichtigt
     *
     * @param $cityId
     * @param $anzeigeSpracheId
     * @param Zend_Db_Table_Abstract $viewStadtProgrammeKategorien
     * @return array
     */
    protected function ermittelnProgrammkategorienStadt($cityId, $anzeigeSpracheId,Zend_Db_Table_Abstract $viewStadtProgrammeKategorien)
    {
        $cols = array(
            'programmKategorieId'
        );

        $whereCityId = "cityId = ".$cityId;

        if($anzeigeSpracheId == 1)
            array_push($cols, new Zend_Db_Expr("de as anzeige"));
        else
            array_push($cols, new Zend_Db_Expr("en as anzeige"));

        $select = $viewStadtProgrammeKategorien->select();
        $select
            ->from($viewStadtProgrammeKategorien, $cols)
            ->where($whereCityId)
            ->order('anzeige');

        $query = $select->__toString();

        $rows = $viewStadtProgrammeKategorien->fetchAll($select)->toArray();

        $alle = array(
            'programmKategorieId' => 0,
            'anzeige' => translate('alle Kategorien')
        );

        // alle Kategorien
        $rows[] = $alle;

        return $rows;
    }

    /**
     * @return array
     */
    public function getProgrammKategorienStadt()
    {
        return $this->programmKategorienStadt;
    }
}

/***********/
//include_once('../../../../autoload_cts.php');
//
//$myClass = new Front_Model_ProgrammeKategorienStadt();
//$kategorien = $myClass
//    ->setCityId(1)
//    ->setAnzeigeSpracheId(1)
//    ->steuerungErmittlungProgrammkategorienEinerStadt()
//    ->getProgrammKategorienStadt();
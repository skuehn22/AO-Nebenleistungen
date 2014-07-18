<?php
/**
 * Es wird kontrolliert das die Kategorie einer Stadt Programme hat
 *
 * @author Stephan
 * @date 13.03.14
 * @file CheckProgrammeKategorieStadt.php
 * @project HOB
 * @package front
 * @subpackage model
 */

class Front_Model_CheckProgrammeKategorieStadt
{
    protected $viewStadtKategorienAnzahlProgramme = null;
    protected $cityId = null;
    protected $kategorieId = null;

    protected $anzahlProgramme = 0;

    /**
     * @param $cityId
     * @return Front_Model_CheckProgrammeKategorieStadt
     */
    public function setCityId($cityId)
    {
        $cityId = (int) $cityId;
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @param $kategorieId
     * @return Front_Model_CheckProgrammeKategorieStadt
     */
    public function setKategorieId($kategorieId)
    {
        $kategorieId = (int) $kategorieId;
        $this->kategorieId = $kategorieId;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $viewStadtKategorienAnzahlProgramme
     * @return Front_Model_CheckProgrammeKategorieStadt
     */
    public function setViewStadtKategorienAnzahlProgramme(Zend_Db_Table_Abstract $viewStadtKategorienAnzahlProgramme)
    {
        $this->viewStadtKategorienAnzahlProgramme = $viewStadtKategorienAnzahlProgramme;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_viewStadtProgrammeKategorien|null
     */
    public function getViewStadtKategorienAnzahlProgramme()
    {
        if(is_null($this->viewStadtKategorienAnzahlProgramme))
            $this->viewStadtKategorienAnzahlProgramme = new Application_Model_DbTable_viewStadtKategorienAnzahlProgramme();

        return $this->viewStadtKategorienAnzahlProgramme;
    }

    /**
     * @return Front_Model_CheckProgrammeKategorieStadt
     * @throws Exception
     */
    public function steuerungErmittlungAnzahlProgrammeEinerKategorie()
    {
        try{
            if(is_null($this->cityId))
                throw new nook_Exception('City ID fehlt');
            if(is_null($this->kategorieId))
                throw new nook_Exception('Kategorie ID fehlt');

            $this->getViewStadtKategorienAnzahlProgramme();

            // Anzahl Programme
            $anzahlProgramme = $this->ermittelnAnzahlProgrammeEinerKategorie($this->viewStadtKategorienAnzahlProgramme,$this->cityId, $this->kategorieId);
            $this->anzahlProgramme = $anzahlProgramme;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }


    }

    /**
     * Berechnen der Anzahl der Programme einer Kategorie in einer Stadt
     *
     * @param Zend_Db_Table_Abstract $viewStadtKategorienAnzahlProgramme
     * @param $cityId
     * @param $kategorieId
     * @return int
     */
    protected function ermittelnAnzahlProgrammeEinerKategorie(Zend_Db_Table_Abstract $viewStadtKategorienAnzahlProgramme,$cityId, $kategorieId)
    {
        $whereCityId = "cityId = ".$cityId;
        $whereProgrammKategorieId = "programmKategorieId = ".$kategorieId;

        $select = $viewStadtKategorienAnzahlProgramme->select();
        $select
            ->where($whereCityId)
            ->where($whereProgrammKategorieId);

        $query = $select->__toString();

        $rows = $viewStadtKategorienAnzahlProgramme->fetchAll($select)->toArray();


        return $rows[0]['anzahlProgrammeKategorie'];
    }

    /**
     * @return int
     */
    public function getAnzahlProgramme()
    {
        return $this->anzahlProgramme;
    }
}

/****/
//include_once('../../../../autoload_cts.php');
//
//$myClass = new Front_Model_CheckProgrammeKategorieStadt();
//$anzahlProgramme = $myClass
//    ->setCityId(1)
//    ->setKategorieId(1)
//    ->steuerungErmittlungAnzahlProgrammeEinerKategorie()
//    ->getAnzahlProgramme();
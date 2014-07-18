<?php
/**
 * Ermittelt und Ordnet Programmkategorien zu einem Programm
 *
 *
 * @author Stephan Krauss
 * @date 06.03.14
 * @file ProgrammKategorienZuordnen.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
class Admin_Model_ProgrammKategorienZuordnen
{
    protected $kategorienEinesProgrammes = null;
    protected $anzahlKategorienEinesProgrammes = 0;
    protected $programmId = null;

    protected $tabelleProgrammdetailsProgrammkategorie = null;

    /**
     * @param $programmId
     * @return Admin_Model_ProgrammKategorienZuordnen
     * @throws nook_Exception
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        if($programmId == 0)
            throw new nook_Exception('Programm ID falsch');

        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie
     * @return Admin_Model_ProgrammKategorienZuordnen
     */
    public function setTabelleProgrammdetailsProgrammkategorie(Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie)
    {
        $this->tabelleProgrammdetailsProgrammkategorie = $tabellleProgrammdetailsProgrammkategorie;

        return $this;
    }

    /**
     * @return Admin_Model_ProgrammKategorienZuordnen
     */
    public function getTabelleProgrammdetailsProgrammkategorie()
    {
        if(is_null($this->tabelleProgrammdetailsProgrammkategorie))
            $this->tabelleProgrammdetailsProgrammkategorie = new Application_Model_DbTable_programmdetailsProgrammkategorie();

        return $this->tabelleProgrammdetailsProgrammkategorie;
    }

    /**
     * Steuert die Ermittlung der bestehenden Programmkategorien
     *
     * @return Admin_Model_ProgrammKategorienZuordnen
     */
    public function steuerungErmittlungProgrammkategorienEinesProgrammes()
    {
        try{
            $this->getTabelleProgrammdetailsProgrammkategorie();

            $kategorienEinesProgrammes = $this->ermittelnProgrammkategorienEinesProgrammes($this->tabelleProgrammdetailsProgrammkategorie);
            $this->kategorienEinesProgrammes = $kategorienEinesProgrammes;
            $this->anzahlKategorienEinesProgrammes = count($kategorienEinesProgrammes);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function getKategorienEinesProgrammes()
    {
        return $this->kategorienEinesProgrammes;
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammkategorien()
    {
        return $this->anzahlKategorienEinesProgrammes;
    }

    /**
     * Ermittelt die vorhandenen Programmkategorien eines Programmes
     *
     * @param Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie
     * @return array
     */
    protected function ermittelnProgrammkategorienEinesProgrammes(Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie)
    {
        $cols = array(
            new Zend_Db_Expr("programmkategorie_id as kategorieId"),
            'status',
            'prioritaet'
        );

        $whereZaehlerProgrammId = new Zend_Db_Expr("programmdetails_id = ".$this->programmId);

        $select = $tabellleProgrammdetailsProgrammkategorie->select();
        $select
            ->from($tabellleProgrammdetailsProgrammkategorie, $cols)
            ->where($whereZaehlerProgrammId)
            ->order('programmkategorie_id');

        $query = $select->__toString();

        $rows = $tabellleProgrammdetailsProgrammkategorie->fetchAll($select)->toArray();

        return $rows;
    }
}

/*****************/

/*
include_once('../../../../autoload_cts.php');

$api = new Admin_Model_ProgrammKategorienZuordnen();
$kategorienEinesProgrammes = $api
    ->setProgrammId(92)
    ->steuerungErmittlungProgrammkategorienEinesProgrammes()
    ->getKategorienEinesProgrammes();

$anzahlKategorienEinesProgrammes = $api->getAnzahlProgrammkategorien();

$test = 123;
*/
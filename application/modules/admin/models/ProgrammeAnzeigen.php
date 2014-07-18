<?php
/**
 * Zeigt die Programme an. Auflisten der Programme
 *
 * + sortieren der Tabelleninhalte
 * + suchen nach Inhalten
 * + blättern in den Inhalten der Tabelle
 *
 * @author Stephan Krauss
 * @date 05.03.14
 * @file ProgrammeAnzeigen.php
 * @project HOB
 * @package admin
 * @subpackage model
 */

class Admin_Model_ProgrammeAnzeigen
{
    // Tabelle / Views
    protected  $viewProgramme = null;

    protected $start = null;
    protected $limit = null;

    protected $cityName = null;
    protected $programmName = null;

    protected $programme = null;
    protected $anzahlProgramme = null;

    /**
     * @param Zend_Db_Table_Abstract $viewProgrammkategorie
     * @return Admin_Model_ProgrammeAnzeigen
     */
    public function setViewProgramme(Zend_Db_Table_Abstract $viewProgrammkategorie)
    {
        $this->viewProgramme = $viewProgrammkategorie;

        return $this;
    }

    /**
     * @param $start
     * @return Admin_Model_ProgrammeAnzeigen
     */
    public function setStart($start)
    {
        $start = (int) $start;
        $this->start = $start;

        return $this;
    }

    /**
     * @param $limit
     * @return Admin_Model_ProgrammeAnzeigen
     */
    public function setLimit($limit)
    {
        $limit = (int) $limit;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $cityName
     * @return Admin_Model_ProgrammeAnzeigen
     */
    public function setCityName($cityName)
    {
        $cityName = trim($cityName);
        $this->cityName = $cityName;

        return $this;
    }

    /**
     * @param $programmName
     * @return Admin_Model_ProgrammeAnzeigen
     */
    public function setProgrammName($programmName)
    {
        $programmName = trim($programmName);
        $this->programmName = $programmName;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_viewProgrammkategorie|null
     */
    public function getViewProgramme()
    {
        if(is_null($this->viewProgramme))
            $this->viewProgramme = new Application_Model_DbTable_viewProgramme();

        return $this->viewProgramme;
    }

    /**
     * @return array
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * @return int
     */
    public function getAnzahlProgramme()
    {
        return $this->anzahlProgramme;
    }

    /**
     * Steuert die Ermittlung der Datensaetze der View Programmkategorie
     *
     * @return Admin_Model_ProgrammeAnzeigen
     * @throws Exception
     */
    public function steuerungAnzeigenProgramme()
    {
        try{
            if(is_null($this->start) or is_null($this->limit))
                throw new nook_Exception('Stsrtwerte fehlen');

            $this->getViewProgramme();
            $programme = $this->ermittlungDatensaetzeProgramme($this->start, $this->limit, $this->cityName, $this->programmName);

            $this->programme = $programme;
            $this->anzahlProgramme = $this->bestimmenAnzahlprogramme();


            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Ermittelt die Programme entsprechend der Suchparamter
     *
     * @param $start
     * @param $limit
     * @param $cityName
     * @param $programmName
     * @return array
     */
    protected function ermittlungDatensaetzeProgramme($start, $limit, $cityName, $programmName)
    {
        /** @var $viewProgramme Zend_Db_Table_Abstract */
        $viewProgramme = $this->viewProgramme;
        $select = $viewProgramme->select();
        $select
            ->limit($limit, $start);

        if(!is_null($cityName)){
            $whereCityName = new Zend_Db_Expr("city like '%".$cityName."%'");
            $select->where($whereCityName);
        }


        if(!is_null($programmName)){
            $whereProgrammName = new Zend_Db_Expr("progname like '%".$programmName."%'");
            $select->where($whereProgrammName);
        }

        $select->order('city')->order('progname');

        $query = $select->__toString();

        $rows = $viewProgramme->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return int
     */
    protected function bestimmenAnzahlprogramme()
    {
        $cols = array(
            new Zend_Db_Expr("count(programmId) as anzahl")
        );

        /** @var $viewProgramme Zend_Db_Table_Abstract */
        $viewProgramme = $this->viewProgramme;
        $select = $viewProgramme->select();
        $select->from($viewProgramme, $cols);

        $query = $select->__toString();

        $rows = $viewProgramme->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }
}

/******************/

//include_once('../../../../autoload_cts.php');
//
//$apiTest = new Admin_Model_ProgrammeAnzeigen();
//
//$params = array(
//    'start' => 0,
//    'limit' => 20,
//    'sucheCity' => 'erli',
//    'sucheProgramm' => 'seen'
//);
//
//
//$apiTest
//    ->setStart($params['start'])
//    ->setLimit($params['limit']);
//
//if(array_key_exists('sucheCity',$params) and !empty($params['sucheCity']))
//    $apiTest->setCityName($params['sucheCity']);
//
//if(array_key_exists('sucheProgramm',$params) and !empty($params['sucheProgramm']))
//    $apiTest->setProgrammName($params['sucheProgramm']);
//
//$programme = $apiTest
//    ->steuerungAnzeigenProgramme()
//    ->getProgramme();
//
//$anzahlProgramme = $apiTest->getAnzahlProgramme();
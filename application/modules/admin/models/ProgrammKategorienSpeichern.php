<?php
/**
 * Speichert die Kategorien eines Programmes
 *
 *
 * @author Stephan Krauss
 * @date 06.03.14
 * @file ProgrammKategorienSpeichern.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
class Admin_Model_ProgrammKategorienSpeichern
{
    protected $programmId = null;
    protected $kategorienProgramm = null;

    protected $tabelleProgrammdetailsProgrammkategorie = null;
    protected $anzahlGespeicherteKategorien = 0;

    /**
     * @param $programmId
     * @return Admin_Model_ProgrammKategorienSpeichern
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
     * @param array $kategorienEinesProgrammes
     * @return Admin_Model_ProgrammKategorienSpeichern
     */
    public function setKategorienEinesProgrammes(array $werteKategorien)
    {
        $this->kategorienProgramm = $werteKategorien;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie
     * @return Admin_Model_ProgrammKategorienSpeichern
     */
    public function setTabelleProgrammdetailsProgrammkategorie(Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie)
    {
        $this->tabelleProgrammdetailsProgrammkategorie = $tabellleProgrammdetailsProgrammkategorie;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_programmdetailsProgrammkategorie
     */
    public function getTabelleProgrammdetailsProgrammkategorie()
    {
        if(is_null($this->tabelleProgrammdetailsProgrammkategorie))
            $this->tabelleProgrammdetailsProgrammkategorie = new Application_Model_DbTable_programmdetailsProgrammkategorie();

        return $this->tabelleProgrammdetailsProgrammkategorie;
    }

    /**
     * Steuert die Speicherung der Programmkategorien
     *
     * @return Admin_Model_ProgrammKategorienSpeichern
     * @throws Exception
     */
    public function steuerungSpeichernProgrammkategorienEinesProgrammes()
    {
        try{
            $this->getTabelleProgrammdetailsProgrammkategorie();

            $this->loeschenProgrammkategorienEinesProgrammes($this->programmId, $this->tabelleProgrammdetailsProgrammkategorie);
            $anzahlGespeicherteKategorien = $this->speichernProgrammkategorienEinesProgrammes($this->programmId, $this->kategorienProgramm);

            $this->anzahlGespeicherteKategorien = $anzahlGespeicherteKategorien;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * @return int
     */
    public function getAnzahlGespeicherteProgrammkategorien()
    {
        return $this->anzahlGespeicherteKategorien;
    }

    /**
     * trägt die Kategorien eines Programmes ein
     *
     * @param $programmId
     * @param $kategorienProgramm
     * @return int
     */
    protected function speichernProgrammkategorienEinesProgrammes($programmId,array $kategorienProgramm)
    {
        $anzahlEingefuegteKategorien = 0;

        /** @var  $tabelleProgrammdetailsProgrammkategorie Zend_Db_table_Abstract */
        $tabelleProgrammdetailsProgrammkategorie = $this->tabelleProgrammdetailsProgrammkategorie;

        for($i=0; $i < count($kategorienProgramm); $i++){
            $insertCols = array(
                'programmdetails_id' => $programmId,
                'programmkategorie_id' => $kategorienProgramm[$i]['zaehler'],
                'prioritaet' => $kategorienProgramm[$i]['prioritaet']
            );

            $tabelleProgrammdetailsProgrammkategorie->insert($insertCols);

            $anzahlEingefuegteKategorien++;
        }

        return $anzahlEingefuegteKategorien;
    }

    /**
     * Löschen der Kategorien eines Programmes
     *
     * @param $programmId
     * @param Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie
     * @return int
     */
    protected function loeschenProgrammkategorienEinesProgrammes($programmId,Zend_Db_Table_Abstract $tabellleProgrammdetailsProgrammkategorie)
    {
        $whereProgrammId = "programmdetails_id = ".$programmId;

        $anzahlGeloeschtKategorienEinesProgrammes = $tabellleProgrammdetailsProgrammkategorie->delete($whereProgrammId);

        return $anzahlGeloeschtKategorienEinesProgrammes;
    }
}

/*****************/

/*
include_once('../../../../autoload_cts.php');

$kategorien = array(10,11,12);

$api = new Admin_Model_ProgrammKategorienSpeichern();
$anzahlKategorien = $api
    ->setProgrammId(100)
    ->setKategorienEinesProgrammes($kategorien)
    ->steuerungSpeichernProgrammkategorienEinesProgrammes()
    ->getAnzahlGespeicherteProgrammkategorien();
*/
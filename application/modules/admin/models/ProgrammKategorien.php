<?php
/**
 * Ermittelt die existierenden Programmkategorien
 *
 *
 * @author Stephan Krauss
 * @date 05.03.14
 * @file ProgrammKategorien.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
class Admin_Model_ProgrammKategorien
{
    protected $programmKategorien = null;
    protected $condition_anzeige_sprache_deutsch = 1;

    protected $tabelleProgrammkategorie = null;

    protected $programmkategorien = null;
    protected $anzahlProgrammkategorien = 0;


    /**
     * @param Zend_Db_Table_Abstract $tabelleProgrammkategorie
     * @return Admin_Model_ProgrammKategorien
     */
    public function setTabelleProgrammkategorie(Zend_Db_Table_Abstract $tabelleProgrammkategorie)
    {
        $this->tabelleProgrammkategorie = $tabelleProgrammkategorie;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_programmkategorie|null
     */
    public function getTabelleProgrammkategorie()
    {
        if(is_null($this->tabelleProgrammkategorie))
            $this->tabelleProgrammkategorie = new Application_Model_DbTable_programmkategorie();

        return $this->tabelleProgrammkategorie;
    }

    /**
     * Steuert die Ermittlung der bestehenden Programmkategorien
     *
     * @return Admin_Model_ProgrammKategorien
     */
    public function steuerungErmittlungProgrammkategorien()
    {
        try{
            $this->getTabelleProgrammkategorie();

            $programmkategorien = $this->ermittelnProgrammkategorien($this->tabelleProgrammkategorie);
            $this->programmkategorien = $programmkategorien;
            $this->anzahlProgrammkategorien = count($programmkategorien);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function getProgrammKategorien()
    {
        return $this->programmkategorien;
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammkategorien()
    {
        return $this->anzahlProgrammkategorien;
    }

    /**
     * Ermittelt die vorhandenen Programmkategorien
     *
     * @param Zend_Db_Table_Abstract $tabelleProgrammkategorie
     * @return array
     */
    protected function ermittelnProgrammkategorien(Zend_Db_Table_Abstract $tabelleProgrammkategorie)
    {
        $cols = array(
            'zaehler',
            'de',
            'en',
            new Zend_Db_Expr("1 as prioritaet")
        );

        $whereZaehlerNotNull = new Zend_Db_Expr("zaehler IS NOT NULL");

        $select = $tabelleProgrammkategorie->select();
        $select
            ->from($tabelleProgrammkategorie, $cols)
            ->where($whereZaehlerNotNull)
            ->order('zaehler');

        $query = $select->__toString();

        $rows = $tabelleProgrammkategorie->fetchAll($select)->toArray();

        return $rows;
    }


}
<?php

//include_once('../../autoload_cts.php');
//
//$myClass = new nook_ToolErmittlungAbweichendeStornofristenKosten();
//$stornoFristen = $myClass
//    ->setProgrammId(127)
//    ->ermittleStornofristenProgramm()
//    ->getStornofristen();
//
//$test = 123;

/**
* Ermittlung abweichender Stornobedingungen eines Programmes. Storno
*
* + Zugriff Tabelle 'tbl_programmdetails_stornokosten'
* + Steuert die Ermittlung der Stornofristen eines Programmes
* + Kontrolle der Stornofristen auf Abweichungen bezüglich des Standard
* + Ermittelt die Stornofristen eines Programmes
*
* @date 18.10.2013
* @file ToolErmittlungAbweichendeStornobedingungen.php
* @package tools
*/
class nook_ToolErmittlungAbweichendeStornofristenKosten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2320;

    // Flags

    // Tabelle / Views
    /** @var $tabelleProgrammdetailsStornokosten Application_Model_DbTable_programmedetailsStornokosten */
    private $tabelleProgrammdetailsStornokosten = null;

    protected $programmId = null;
    protected $stornofristenProgramm = array();

    protected $checkHasStornofristen = false;


    public function __construct()
    {
        $this->tabellen();
    }

    /**
     * Zugriff Tabelle
     */
    private function tabellen()
    {
        $this->tabelleProgrammdetailsStornokosten = new Application_Model_DbTable_programmedetailsStornokosten();

        return;
    }

    /**
     * @param $programmId
     * @return nook_ToolErmittlungAbweichendeStornofristenKosten
     */
    public function setProgrammId($programmId)
    {
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Stornofristen eines Programmes
     *
     * + Vergleich Stornofristen Programm zu allgemeine Stornofristen
     *
     * @return nook_ToolErmittlungAbweichendeStornofristenKosten
     * @throws nook_Exception
     */
    public function ermittleStornofristenProgramm()
    {
        if($this->programmId == null)
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        // Vergleich Stornofristen Programm zu allgemeine Stornofristen
        $stornofristenProgramm = $this->ermittelnStornofristenEinesProgrammes($this->tabelleProgrammdetailsStornokosten, $this->programmId);

        if(count($stornofristenProgramm) > 0)
            $this->checkHasStornofristen = true;

        /* S.Kuehn 01.08.2014
         * auskommetiert da bei NL-Projekt nie Standardstornobedingungen genommen werden
         */

        //$standardStornoFristen = $this->ermittelnStandardStornoFristen();
        //$stornofristenProgramm = $this->kontrolleStandardStornofristen($stornofristenProgramm, $standardStornoFristen);

        $this->stornofristenProgramm = $stornofristenProgramm;

        return $this;
    }

    /**
     * Ermittlung der Standard Stornofristen aus '/application/configs/static.ini'
     *
     * @return array
     */
    protected function ermittelnStandardStornoFristen()
    {
        /** @var $zendRegistryStatic Zend_Registry */
        $zendRegistryStatic = Zend_Registry::get('static');
        $standardStornoFristen = $zendRegistryStatic->stornokosten->toArray();


        return $standardStornoFristen;
    }

    /**
     * Kontrolle der Stornofristen auf Abweichungen bezüglich des Standard in 'application/config/static.ini'
     *
     * + $abweichendeStornofristenCount , Anzahl der abweichenden Stornofristen
     *
     * @param $stornoFristProgramm
     * @return array / false
     */
    private function kontrolleStandardStornofristen($stornoFristProgramm,  $standardStornoFristen)
    {
        $abweichendeStornofristenCount = 0;

        foreach($stornoFristProgramm as $key => $stornoFristProgrammTag){
            if(array_key_exists($stornoFristProgrammTag['tage'], $standardStornoFristen)){
                if($stornoFristProgrammTag['prozente'] != $standardStornoFristen[$stornoFristProgrammTag['tage']])
                    $abweichendeStornofristenCount++;
            }
            else
                $abweichendeStornofristenCount++;
        }

        // Stornofrist ist immer 100%
        if( ($stornoFristProgrammTag['tage'] == 999) and ($stornoFristProgrammTag['prozente'] == 100) ){
            $stornofristPermanent = array();

            $stornofristPermanent[0] = array(
                'tage' => 999,
                'prozente' => 100
            );

            return $stornofristPermanent;
        }
        // wenn Stornofristen abweichen
        elseif($abweichendeStornofristenCount > 0)
            return $stornoFristProgramm;
        // Stornofristen weichen nicht ab
        else
            return false;
    }

    /**
     * Ermittelt die Stornofristen eines Programmes
     *
     * @param $tabelleProgrammdetailsStornokosten
     * @param $programmId
     * @return array
     */
    private function ermittelnStornofristenEinesProgrammes(Application_Model_DbTable_programmedetailsStornokosten $tabelleProgrammdetailsStornokosten, $programmId)
    {
        $whereProgrammId = "programmdetails_id = ".$programmId;

        $cols = array(
            'tage',
            'prozente'
        );

        $select = $tabelleProgrammdetailsStornokosten->select();
        $select
            ->from($tabelleProgrammdetailsStornokosten, $cols)
            ->where($whereProgrammId)
            ->order('tage desc');

        $query = $select->__toString();

        $rows = $tabelleProgrammdetailsStornokosten->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return array
     */
    public function getStornofristen()
    {
        return $this->stornofristenProgramm;
    }

    /**
     * Hat das Programm individuelle Stornofristen
     *
     * @return bool
     */
    public function hasStornofristen()
    {
        return $this->checkHasStornofristen;
    }
}

<?php
/**
 * Der Benutzer kann Zusatzinformationen für den Rezeptionisten schreiben und lesen
 *
 * @author Stephan Krauss
 * @date 11.06.2014
 * @file Admin_Model_Reception.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
class Admin_Model_Reception
{
    protected $pimple = null;

    protected $programmId = null;
    protected $zusatzinformationReception = null;

    public function __construct()
    {
        $this->pimple = $this->buildPimple();
    }

    /**
     * erstellt DIC
     */
    protected function buildPimple()
    {
        $pimple = new Pimple_Pimple();
        $pimple['tabelleReception'] = function(){
            return new Application_Model_DbTable_reception();
        };

        return $pimple;
    }

    /**
     * @param $programmId
     * @return Admin_Model_Reception
     * @throws nook_Exception
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $test = "sjdlksjd";
        if($programmId == 0)
            throw new nook_Exception('Programm ID falsch');

        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @return string
     */
    public function getZusatzinformationReception()
    {
        return $this->zusatzinformationReception;
    }

    /**
     * @param $zusatzinformationReception
     * @return Admin_Model_Reception
     */
    public function setZusatzinformationReception($zusatzinformationReception)
    {
        $this->zusatzinformationReception = $zusatzinformationReception;

        return $this;
    }


    /**
     * @return Admin_Model_Reception
     * @throws Exception
     */
    public function steuerungReception()
    {
        try{
            if(is_null($this->programmId))
                throw new nook_Exception('Programm ID fehlt');

            if(is_null($this->zusatzinformationReception))
                $this->zusatzinformationReception = $this->readZusatzinformation($this->programmId, $this->pimple['tabelleReception']);
            else
                $this->updateZusatzinformation($this->programmId, $this->zusatzinformationReception, $this->pimple['tabelleReception']);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Ermittelt die Zusatzinformation für den rezeptionist eines Programmes
     *
     * @param $programmId
     * @param Zend_Db_Table_Abstract $tabelleReception
     * @return mixed
     * @throws nook_Exception
     */
    protected function readZusatzinformation($programmId, Zend_Db_Table_Abstract $tabelleReception)
    {
        $cols = array(
            'receptionInformation'
        );

        $whereProgrammId = "programmdetails_id = ".$programmId;


        $select = $tabelleReception->select();
        $select
            ->from($tabelleReception, $cols)
            ->where($whereProgrammId);

        $query = $select->__toString();

        $rows = $tabelleReception->fetchAll($select)->toArray();

        if(count($rows) > 1)
            throw new nook_Exception('Anzahl Zusatzinformationen Rezeption zu viele');

        if(count($rows) < 1)
            throw new nook_Exception("Zusatzinformationen Rezeption in 'tbl_reception' fehlt");


        return $rows[0]['receptionInformation'];
    }

    /**
     * Update der Zusatzinformation
     *
     * @param $programmId
     * @param $zusatzinformationReception
     * @param Zend_Db_Table_Abstract $tabelleReception
     */
    protected function updateZusatzinformation($programmId, $zusatzinformationReception, Zend_Db_Table_Abstract $tabelleReception)
    {
        $update = array(
            'receptionInformation' => $zusatzinformationReception
        );

        $whereProgrammdetailsId = "programmdetails_id = ".$programmId;

        $anzahl = $tabelleReception->update($update,$whereProgrammdetailsId);

        return;
    }

}
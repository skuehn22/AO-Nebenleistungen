<?php 
 /**
 * Ermittelt ob ein Programm eine Buchungspauschale hat
 *
 * @author Stephan.Krauss
 * @date 29.11.2013
 * @file ToolBuchungspauschale.php
 * @package tools
 */
class nook_ToolBuchungspauschale
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
    private $tabelleProgrammdetails = null;

    // Tools

    // Konditionen
    private $condition_programm_hat_keine_buchungspauschale = 1;

    // Zustände


    protected $pimple = null;
    protected $programmId = null;
    protected $hasBuchungsPauschale = false;

    public function __construct(Pimple_Pimple $pimple){
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProgrammdetails'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        return;
    }

    /**
     * @param $programmId
     * @return nook_ToolBuchungspauschale
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $kontrolle = $this->tabelleProgrammdetails->kontrolleValue('id', $programmId);
        if( (false === $kontrolle) or ($programmId == 0))
            throw new nook_Exception('Anfangswert falsch');

        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @return nook_ToolBuchungspauschale
     */
    public function steuerungErmittlungObProgrammBuchungspauschaleHat()
    {
        if(is_null($this->programmId))
            throw new nook_Exception('Anfangswert fehlt');

        $hasBuchungsPauschale = $this->hatDasProgrammEineBuchungsPauschale($this->programmId);
        $this->hasBuchungsPauschale = $hasBuchungsPauschale;

        return $this;
    }

    /**
     * Ermittelt ob ein Programm eine Buchungspauschale hat.
     *
     * + $hasBuchungsPauschale = true, Programm hat eine Buchungspauschale
     *
     * @param $programmId
     * @return bool
     */
    private function hatDasProgrammEineBuchungsPauschale($programmId)
    {
        $whereprogrammId = "id = ".$programmId;

        $cols = array(
            'buchungspauschale'
        );

        $select = $this->tabelleProgrammdetails->select();
        $select
            ->from($this->tabelleProgrammdetails, $cols)
            ->where($whereprogrammId);

        $rows = $this->tabelleProgrammdetails->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensätze falsch');

        if($rows[0]['buchungspauschale'] == $this->condition_programm_hat_keine_buchungspauschale)
            $hasBuchungsPauschale = false;
        else
            $hasBuchungsPauschale = true;

        return $hasBuchungsPauschale;
    }

    /**
     * @return bool
     */
    public function hasBuchungsPauschale()
    {
        return $this->hasBuchungsPauschale;
    }

}
 
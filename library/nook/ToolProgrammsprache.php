<?php
/**
* Ermitteln der Programmsprache
*
* + bindet benötigte Dateien ein
* + steuert die Ermittlung der Anzeigesprache
* + Ermittelt die Programmsprache
*
* @date 23.08.13
* @file ToolProgrammsprache.php
* @package tools
*/
class nook_ToolProgrammsprache
{

    // Fehler
    private $error_anfangswerte_fehlen = 1980;
    private $error_anzahl_datensaetze_falsch = 1981;

    // Konditionen
    private $condition_anzeigesprache_deutsch = 1;

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $programmspracheId = null;
    protected $anzeigespracheId = null;
    protected $bezeichnungProgrammsprache = null;

    public function __construct()
    {

    }

    /**
     * @param Pimple_Pimple $pimple
     * @return nook_ToolProgrammsprache
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * bindet benötigte Dateien ein
     */
    private function servicecontainer()
    {
        if (empty($this->pimple)) {
            $this->pimple = new Pimple_Pimple();
        }

        if (!$this->pimple->offsetExists('tabelleProgSprache')) {
            $this->pimple['tabelleProgSprache'] = function () {
                return new Application_Model_DbTable_progSprache();
            };
        }

        return;
    }

    /**
     * @param $programmspracheId
     * @return nook_ToolProgrammsprache
     */
    public function setProgrammsprache($programmspracheId)
    {
        $programmspracheId = (int) $programmspracheId;
        $this->programmspracheId = $programmspracheId;

        return $this;
    }

    /**
     * @param $anzeigespracheId
     * @return nook_ToolProgrammsprache
     */
    public function setAnzeigespracheId($anzeigespracheId)
    {
        $anzeigespracheId = (int) $anzeigespracheId;
        $this->anzeigespracheId = $anzeigespracheId;

        return $this;
    }

    /**
     * steuert die Ermittlung der Anzeigesprache
     *
     * + Ermittlung ob die gespeicherte Programmsprache angezeigt wird.
     * + wenn keine Anzeigesprache gegeben ist, wird deutsch gewählt
     *
     * @return nook_ToolProgrammsprache
     * @throws nook_Exception
     */
    public function steuerungErmittlungProgrammsprache()
    {
        $this->servicecontainer();

        if(empty($this->anzeigespracheId))
            $this->anzeigespracheId = $this->condition_anzeigesprache_deutsch;

        $this->ermittlungProgrammsprache();

        return $this;
    }

    /**
     * Ermittelt die Programmsprache
     *
     * + wählt die Programmsprache nach der Anzeigesprache aus
     *
     * @throws nook_Exception
     */
    private function ermittlungProgrammsprache()
    {
        /** @var  $tabelleProgSprache Application_Model_DbTable_progSprache */
        $tabelleProgSprache = $this->pimple['tabelleProgSprache'];
        $rows = $tabelleProgSprache->find($this->programmspracheId)->toArray();

        if (count($rows) == 1) {

            if ($this->anzeigespracheId == $this->condition_anzeigesprache_deutsch)
                $this->bezeichnungProgrammsprache = $rows[0]['de'];
            else
                $this->bezeichnungProgrammsprache = $rows[0]['eng'];
        }
        elseif(count($rows) == 0)
            $this->bezeichnungProgrammsprache = false;
        else
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return;
    }

    /**
     * @return string
     */
    public function getBezeichnungProgrammsprache()
    {
        return $this->bezeichnungProgrammsprache;
    }
}

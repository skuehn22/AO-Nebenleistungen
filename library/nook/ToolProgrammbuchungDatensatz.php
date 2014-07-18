<?php 
 /**
 * Ermittelt die Buchungsdaten einer Programmbuchung
 * 
 * @author Stephan.Krauss
 * @date 27.06.13
 * @file ToolProgrammbuchung.php
 * @package tools
 */
 
class nook_ToolProgrammbuchungDatensatz {

    // Fehler
    private $error_anfangswerte_fehlen = 1770;
    private $error_anzahl_datensaetze_falsch = 1771;

    // Flags

    // Konditionen

    private static $instance = null;

    protected $pimple = null;
    protected $programmbuchungId = null;
    protected $programmbuchungDatensatz = array();

    /**
     * Singleton
     *
     * @return nook_ToolProgrammbuchung
     */
    public static function instance()
    {
        if(!self::$instance){
            $pimple = self::buildPimple();
            self::$instance = new nook_ToolProgrammbuchungDatensatz($pimple);
        }

        return self::$instance;
    }

    /**
     * Generiert DIC
     *
     * @return Pimple_Pimple
     */
    private static function buildPimple()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammbuchung'] = function(){
            return new Application_Model_DbTable_programmbuchung();
        };

        return $pimple;
    }

    /**
     * Setzt DIC
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @return nook_ToolProgrammbuchungDatensatz
     */
    public function steuerungErmittlungBuchungsdatensatzProgramme()
    {
        if(empty($this->programmbuchungId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittelnBuchungsdatensatzProgramm();

        return $this;
    }

    /**
     * Ermittelt den Buchungsdatensatz eines Programmes
     *
     * @return array
     * @throws nook_Exception
     */
    private function ermittelnBuchungsdatensatzProgramm()
    {
        /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $rows = $tabelleProgrammbuchung->find($this->programmbuchungId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->programmbuchungDatensatz = $rows[0];

        return $rows[0];
    }

    /**
     * @param $programmbuchungId
     * @return nook_ToolProgrammbuchungDatensatz
     */
    public function setProgrammbuchungId($programmbuchungId)
    {
        $programmbuchungId = (int) $programmbuchungId;
        $this->programmbuchungId = $programmbuchungId;

        return $this;
    }

    /**
     * @return array
     */
    public function getProgrammbuchungDatensatz()
    {
        return $this->programmbuchungDatensatz;
    }
}

<?php 
 /**
 * Das System kontrolliert ob in der Bestellung des Kunden an einenProgrammanbieter sich Programme befinden die den Modus Offlinebuchung haben.
 *
 * 
 * @author Stephan.Krauss
 * @date 04.11.2013
 * @file ToolKontrolleOfflinebuchung.php
 * @package tools
 */
class nook_ToolKontrolleOfflinebuchung
{
    // Fehler
    private $error = 2330;

    // Tabellen / Views
    private $tabelleProgrammdetails = null;

    // Konditionen

    // Informationen

    // Flags

    protected $programme = array();
    protected $flagModusOfflinebuchung = false;

    public function __construct(array $programme)
    {
        if(count($programme) > 0)
            $this->programme = $programme;

        $this->serviceContainer();


    }

    /**
     * Initialisieren der Tabellen und Views
     */
    private function serviceContainer()
    {
        $this->tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();

        return;
    }

    /**
     * Ermittelt ob eine Offlinebuchung vorliegt.
     *
     * @return nook_ToolKontrolleOfflinebuchung
     */
    public function kontrolleOfflinebuchung()
    {
        $this->ermittelnStatusOfflinebuchung();

        return $this;
    }

    /**
     * ermittelt ob in den Programmbuchungen eines Programmanbieters sich Offlinebuchungen befinden.
     *
     * @return bool
     */
    private function ermittelnStatusOfflinebuchung()
    {
        for($i=0; $i < count($this->programme); $i++){
            if($this->programme[$i]['buchungsdaten']['offlinebuchung'] == 1){
                $this->flagModusOfflinebuchung = true;
            }
        }

        return $this->flagModusOfflinebuchung;
    }

    /**
     * Gibt den Status der Offlinebuchung für alle gebuchten Programme eines Programmanbieters zurück
     */
    public function getModusOfflinebuchung()
    {
        return $this->flagModusOfflinebuchung;
    }
}

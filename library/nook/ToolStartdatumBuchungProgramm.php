<?php 
 /**
 * Tool zur Kontrolle des frühest möglichen Buchungsdatum eines Programmes
 *
 * @author Stephan.Krauss
 * @date 27.08.13
 * @file ToolStartdatumBuchungProgramm.php
 * @package tools
 */
class nook_ToolStartdatumBuchungProgramm
{
    // Fehler
    private $error_anfangswerte_fehlen = 2010;
    private $error_anzahl_datensaetze_falsch = 2011;

    // Konditionen
    private $condition_programm_hat_keine_oeffnungszeiten = 4;
    private $condition_wochentag_ist_geschaeftstag = 2;

    // Flags

    protected $pimple = false;
    protected $programmId = null;
    protected $zeitSekunden = null;
    protected $typOeffnungszeitenProgramm = null;
    protected $oeffnungszeitenProgramm = array();
    protected $ersterMoeglicherTagProgrammbuchung = "2020-12-31";
    protected $ersterMoeglicherTagProgrammbuchungSekunden = 0;

    public function __construct()
    {

    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleProgrammdetailsOeffnungszeiten')){
            $this->pimple['tabelleProgrammdetailsOeffnungszeiten'] = function(){
                return new Application_Model_DbTable_programmedetailsOeffnungszeiten();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProgrammdetails')){
            $this->pimple['tabelleProgrammdetails'] = function(){
                return new Application_Model_DbTable_programmedetails();
            };
        }

        return;
    }

    /**
     * @param $programmId
     * @return nook_ToolStartdatumBuchungProgramm
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param $zeitSekunden
     * @return nook_ToolStartdatumBuchungProgramm
     */
    public function setZeitSekunden($zeitSekunden)
    {
        $zeitSekunden = (int) $zeitSekunden;
        $this->zeitSekunden = $zeitSekunden;

        return $this;
    }

    /**
     * Wandelt das ankommende Datum in Sekunden um
     *
     * @param $jahr
     * @param $monat
     * @param $tag
     * @return nook_ToolStartdatumBuchungProgramm
     */
    public function setDatum($jahr, $monat, $tag){

        $jahr = (int) $jahr;
        $monat = (int) $monat;
        $tag = (int) $tag;

        $zeitSekunde = strtotime($jahr."-".$monat."-".$tag);
        $this->zeitSekunden = $zeitSekunde;

        return $this;
    }

    /**
     * Steuerung Ermittlung des ersten möglichen aktiven Buchungstag
     *
     * @return nook_ToolStartdatumBuchungProgramm
     * @throws nook_Exception
     */
    public function steuerungErmittlungErsterBuchungstag()
    {
        $this->servicecontainer();

        if(empty($this->programmId) or empty($this->zeitSekunden) )
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittlungTypOeffnungszeitenProgramm();
        $this->bestimmungOeffnungszeitenProgramm();
        $this->bestimmungErsterMoeglicherBuchungstag();

        return $this;
    }

    /**
     * Ermittelt den Typ der Öffnungszeiten eines Programmes
     */
    private function ermittlungTypOeffnungszeitenProgramm()
    {
        /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $rows = $tabelleProgrammdetails->find($this->programmId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->typOeffnungszeitenProgramm = $rows[0]['typOeffnungszeiten'];

        return;
    }

    /**
     * @return int
     */
    public function getTypOeffnungszeitenProgramm()
    {
        return $this->typOeffnungszeitenProgramm;
    }

    /**
     * Bestimmt die Öffnungszeiten eines Programmes
     *
     */
    private function bestimmungOeffnungszeitenProgramm()
    {
        if($this->typOeffnungszeitenProgramm >= $this->condition_programm_hat_keine_oeffnungszeiten)
            return;

        $whereprogrammId = "programmdetails_id = ".$this->programmId;

        /** @var $tabelleProgrammdetailsOeffnungszeiten Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetailsOeffnungszeiten = $this->pimple['tabelleProgrammdetailsOeffnungszeiten'];
        $select = $tabelleProgrammdetailsOeffnungszeiten->select();
        $select->where($whereprogrammId);

        $rows = $tabelleProgrammdetailsOeffnungszeiten->fetchAll($select)->toArray();

        if(count($rows) > 0){
            $this->oeffnungszeitenProgramm = $rows;
        }

        return;
    }

    /**
     * Bestimmt den ersten möglichen Buchungstag des Programmes.
     *
     * + Kontrolliert ob der erste mögliche Tag ein Geschäftstag ist
     *
     * + speichert den tag als 'YYYY-mm-dd'
     *
     */
    private function bestimmungErsterMoeglicherBuchungstag()
    {
        $tagInSekunden = 86400;

        $trefferAbgleich = 0;
        for($i = 0; $i < 7; $i++){
            $zeitSekunden = $this->zeitSekunden + ($i * $tagInSekunden);
            $zifferWochentag = date("w", $zeitSekunden);

            // Korrektur Sonntag
            if($zifferWochentag == 0)
                $zifferWochentag = 7;

            foreach($this->oeffnungszeitenProgramm as $key => $oeffnungszeit){

                if( ($zifferWochentag == $oeffnungszeit['wochentag']) and ($oeffnungszeit['geschaeftstag'] == $this->condition_wochentag_ist_geschaeftstag ) and $trefferAbgleich == 0 ){
                    $this->ersterMoeglicherTagProgrammbuchung = date("Y-m-d", $zeitSekunden);
                    $this->ersterMoeglicherTagProgrammbuchungSekunden = $zeitSekunden;

                    $trefferAbgleich++;
                }
            }
        }

        return;
    }

    /**
     * @return Datum
     */
    public function getErsterMoeglicherTagProgrammbuchung()
    {
        return $this->ersterMoeglicherTagProgrammbuchung;
    }

    /**
     * @return Datum in Sekunden
     */
    public function getErsterMoeglicherTagProgrammbuchungSekunden()
    {
        return $this->ersterMoeglicherTagProgrammbuchungSekunden;
    }



}

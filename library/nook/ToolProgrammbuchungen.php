<?php 
/**
* Ermittelt die Programmbuchungen einer Buchungsnummer und eines zaehlers der Buchungsnummer
*
* + Kontrolle der Tools
* + Steuert die Ermittlung der gebuchten Programme
* + Ermitteln Anzahl gebuchte Programme
* + Ermittelt die gebuchten Programme einer Buchungsnummer und eines Zaehlers
* + Rückgabe gebuchte Programme
*
* @date 14.11.2013
* @file ToolProgrammbuchungen.php
* @package front | admin | tools | plugins | schnittstelle | tabelle
* @subpackage controller | model | interface | shadow | data
*/
class nook_ToolProgrammbuchungen
{

    // Fehler
    private $error_anfangswerte_fehlen = 2400;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;

    // Konditionen

    // Flags

    protected $buchungsNummerId = null;
    protected $zaehler = null;
    protected $anzahlGebuchteProgramme = 0;
    protected $gebuchteProgramme = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);

    }

    /**
     * Kontrolle der Tools
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProgrammbuchung'
        );

        foreach($tools as $key => $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            else
                $this->$value = $pimple[$value];
        }

        return;
    }

    /**
     * @param $buchungsnummer
     * @return $this
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsNummerId = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return $this
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * Steuert die Ermittlung der gebuchten Programme
     *
     * + ermitteln gebuchte Progranmme
     * + Anzahl gebuchte Programme
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuerungErmittlungProgrammbuchungen()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(is_null($this->zaehler))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        // Ermitteln gebuchte Programme
        $gebuchteProgramme = $this->ermittelnProgrammbuchungen($this->buchungsNummerId, $this->zaehler);
        $this->gebuchteProgramme = $gebuchteProgramme;

        // Anzahl gebuchte Programme
        $anzahlGebuchteProgramme = $this->ermittelnAnzahlGebuchteProgramme();
        $this->anzahlGebuchteProgramme = $anzahlGebuchteProgramme;

        return $this;
    }

    /**
     * Ermitteln Anzahl gebuchte Programme
     *
     * @return int
     */
    private function anzahlGebuchteProgramme()
    {
        return count($this->gebuchteProgramme);
    }

    /**
     * Ermittelt die gebuchten Programme einer Buchungsnummer und eines Zaehlers
     *
     * @param $buchungsNummerId
     * @param $zaehler
     * @return int
     */
    private function ermittelnProgrammbuchungen($buchungsNummerId, $zaehler)
    {
        $whereBuchungsNummerid = "buchungsnummer_id = ".$buchungsNummerId;
        $whereZaehler = "zaehler = ".$zaehler;

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->where($whereBuchungsNummerid)
            ->where($whereZaehler);

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return int
     */
    public function getAnzahlGebuchteProgramme()
    {
        return $this->anzahlGebuchteProgramme;
    }

    /**
     * Rückgabe gebuchte Programme
     *
     * + wenn keine Programme gebucht, dann Rückgabe 'false'
     * + wenn Programme gebucht, dann Rückgabe eines Array
     *
     * @return array
     */
    public function getGebuchteProgramme()
    {
        return $this->gebuchteProgramme;
    }
}

<?php 
/**
* Verwaltet den Zaehler in der 'tbl_buchungsnummer'
*
* + Kontrolle Servicecontainer
* + Übernimmt die Buchungsnummer
* + Steuert die Ermittlung des aktuellen zaehlers in 'tbl_buchungsnummer'
* + Ermittelt den aktuellen Zaehler der Buchungsnummer in 'tbl_buchungsnummer'
*
* @date 13.11.2013
* @file ToolZaehler.php
* @package tools
*/
class nook_ToolZaehler
{
    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;

    // Fehler
    private $error_anfangswert_fehlt = 2390;
    private $error_anzahl_datensaetze_falsch = 2391;

    // Informationen

    // Konditionen

    // Flags

    protected $buchungsNummerId = null;
    protected $aktuellerZaehler = null;

    /**
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Kontrolle Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleBuchungsnummer'
        );

        foreach($tools as $key => $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswert_fehlt);
            else
                $this->$value = $pimple[$value];
        }

        return;
    }

    /**
     * Übernimmt die Buchungsnummer
     *
     * @param $buchungsNummerId
     * @return nook_ToolZaehler
     */
    public function setBuchungsnummer($buchungsNummerId)
    {
        $buchungsNummerId = (int) $buchungsNummerId;
        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAktuellerZaehler()
    {
        return $this->aktuellerZaehler;
    }

    /**
     * Steuert die Ermittlung des aktuellen zaehlers in 'tbl_buchungsnummer'
     *
     * @return nook_ToolZaehler
     * @throws nook_Exception
     */
    public function steuerungErmittlungAktuellerZaehler()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception($this->error_anfangswert_fehlt);

        $aktuellerZaehler = $this->ermittelnAktuellerZaehler($this->buchungsNummerId);
        $this->aktuellerZaehler = $aktuellerZaehler;

        return $this;
    }

    /**
     * Ermittelt den aktuellen Zaehler der Buchungsnummer in 'tbl_buchungsnummer'
     *
     * @param $buchungsNummerId
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnAktuellerZaehler($buchungsNummerId)
    {
        $rows = $this->tabelleBuchungsnummer->find($buchungsNummerId)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return $rows[0]['zaehler'];
    }
}

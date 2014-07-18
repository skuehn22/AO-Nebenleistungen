<?php 
 /**
 * Ermittelt die datensätze der Hotelbuchungen des aktuellen Warenkorbes. Hotel Buchungsdatensatz
 *
 * @author Stephan.Krauss
 * @date 04.12.2013
 * @file ToolHotelbuchungenWarenkorb.php
 * @package tools
 */
class nook_ToolHotelbuchungenWarenkorb
{
    // Tabellen / Views
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    protected $tabelleHotelbuchung = null;

    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $zaehler = null;

    protected $hotelbuchungen = array();
    protected $anzahlHotelbuchungen = 0;

    public function __construct(Pimple_Pimple $pimple)
    {
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
            'tabelleHotelbuchung'
        );

        foreach($tools as $tool){

            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $buchungsNummerId
     * @return nook_ToolHotelbuchungenWarenkorb
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $buchungsNummerId = (int) $buchungsNummerId;
        if($buchungsNummerId == 0)
            throw new nook_Exception('falscher Anfangswert');

        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @param $zaehler
     * @return nook_ToolHotelbuchungenWarenkorb
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @return array
     */
    public function getHotelbuchungen()
    {
        return $this->hotelbuchungen;
    }

    /**
     * @return int
     */
    public function getAnzahlHotelbuchungen()
    {
        return $this->anzahlHotelbuchungen;
    }

    /**
     * Steuert die Ermittlung der Datensätze der Hotelbuchungen des aktuellen Warenkorbes
     *
     * @return nook_ToolHotelbuchungenWarenkorb
     * @throws nook_Exception
     */
    public function steuerungErmittlungHotelbuchungen()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->zaehler))
            throw new nook_Exception('Anfangswert fehlt');

        $hotelbuchungen = $this->ermittelnHotelbuchungen($this->buchungsNummerId, $this->zaehler);
        $this->anzahlHotelbuchungen = count($hotelbuchungen);
        $this->hotelbuchungen = $hotelbuchungen;

        return $this;
    }

    /**
     * Ermittelt die Buchungsdatensätze der Hotelbuchungen des aktuellen Warenkorbes.
     *
     * @param $buchungsNummerId
     * @param $zaehler
     * @return array
     */
    private function ermittelnHotelbuchungen($buchungsNummerId, $zaehler)
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsNummerId;
        $whereZaehler = "zaehler = ".$zaehler;

        $select = $this->tabelleHotelbuchung->select();
        $select->where($whereBuchungsnummer)->where($whereZaehler)->order("propertyId asc")->order("startDate asc");

        // $query = $select->__toString();

        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        return  $rows;
    }
}
 
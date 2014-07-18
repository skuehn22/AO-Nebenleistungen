<?php 
 /**
 * Ermittelt die Hotelprodukte einer Buchungsnummer unter Berücksichtigung des Status der Buchung
 *
 * @author Stephan.Krauss
 * @date 26.11.2013
 * @file ToolGebuchteHotelprodukte.php
 * @package tools
 */
class nook_ToolGebuchteHotelprodukte
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    private $tabelleProduktbuchung = null;

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;

    protected $buchungsNummerId = null;
    protected $statusBuchung = null;
    protected $zaehlerBuchungsnummer = null;

    protected $gebuchteHotelProdukte = array();
    protected $anzahlGebuchteHotelprodukte = 0;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProduktbuchung'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Anfangswert fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        return;
    }

    /**
     * @param $buchungsNummerid
     * @return nook_ToolGebuchteHotelprodukte
     */
    public function setBuchungsNummerId($buchungsNummerid)
    {
        $buchungsNummerid = (int) $buchungsNummerid;
        if($buchungsNummerid == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->buchungsNummerId = $buchungsNummerid;

        return $this;
    }

    /**
     * @param $statusBuchung
     * @return nook_ToolGebuchteHotelprodukte
     * @throws nook_Exception
     */
    public function setBuchungsStatus($statusBuchung)
    {
        $statusBuchung = (int) $statusBuchung;
        if($statusBuchung == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->statusBuchung = $statusBuchung;

        return $this;
    }

    /**
     * @param $zaehlerBuchungsnummer
     * @return nook_ToolGebuchteHotelprodukte
     * @throws nook_Exception
     */
    public function setZaehlerBuchungsnummer($zaehlerBuchungsnummer)
    {
        $this->zaehlerBuchungsnummer = $zaehlerBuchungsnummer;

        return $this;
    }

    /**
     * @return array
     */
    public function getGebuchteHotelProdukte()
    {
        return $this->gebuchteHotelProdukte;
    }

    /**
     * @return int
     */
    public function getAnzahlgebuchteHotelprodukte()
    {
        return $this->anzahlGebuchteHotelprodukte;
    }

    /**
     * steuert die Ermittlung der Hotelprodukte einer Buchungsnummer in Abhängigkeit des Status der Buchung
     *
     * @return nook_ToolGebuchteHotelprodukte
     */
    public function steuerungErmittlungGebuchteHotelprodukte()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->statusBuchung))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->zaehlerBuchungsnummer))
            throw new nook_Exception('Anfangswert fehlt');

        $gebuchteHotelProdukte = $this->ermittlungGebuchteHotelprodukte($this->buchungsNummerId, $this->zaehlerBuchungsnummer, $this->statusBuchung);

        $this->anzahlGebuchteHotelprodukte = count($gebuchteHotelProdukte);

        $this->gebuchteHotelProdukte = $gebuchteHotelProdukte;

        return $this;
    }

    /**
     * Ermittelt die gebuchten Hotelprodukte eines Warenkorbes
     *
     * @param $buchungsNummerId
     * @param $statusBuchung
     * @return array
     */
    protected function ermittlungGebuchteHotelprodukte($buchungsNummerId, $zaehlerBuchungsnummer, $statusBuchung)
    {
        $whereBuchungsNummerId = "buchungsnummer_id = ".$buchungsNummerId;
        $whereZaehlerBuchungsnummer = "zaehler = ".$zaehlerBuchungsnummer;
        $whereStatusBuchung = "status = ".$statusBuchung;

        $select = $this->tabelleProduktbuchung->select();
        $select
            ->where($whereBuchungsNummerId)
            ->where($whereZaehlerBuchungsnummer)
            ->where($whereStatusBuchung);

        $rows = $this->tabelleProduktbuchung->fetchAll($select)->toArray();

        return $rows;
    }
}
 
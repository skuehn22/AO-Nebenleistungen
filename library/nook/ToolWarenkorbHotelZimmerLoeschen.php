<?php 
 /**
 * Löscht ein einzelnes Zimmer oder einer Teilrechnung aus dem aktuellen Warenkorb einer Buchung
 *
 * @author Stephan.Krauss
 * @date 11.12.2013
 * @file ToolWarenkorbHotelZimmerLoeschen.php
 * @package tools
 */
class nook_ToolWarenkorbHotelZimmerLoeschen
{
    // Tabellen / Views
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;

    protected $pimple = null;
    protected $hotelbuchungId = null;
    protected $teilrechnungHotelbuchungId = null;
    protected $anzahlGeloeschterDatensaetzeHotelbuchung = 0;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleHotelbuchung'
        );



        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool nicht vorhanden');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $teilrechnungId
     * @return nook_ToolWarenkorbHotelZimmerLoeschen
     */
    public function setTeilrechnungHotelbuchungId($teilrechnungId)
    {
        $teilrechnungId = (int) $teilrechnungId;
        if($teilrechnungId == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->teilrechnungHotelbuchungId = $teilrechnungId;

        return $this;
    }

    /**
     * @param $hotelbuchungId
     * @return nook_ToolWarenkorbHotelZimmerLoeschen
     */
    public function setHotelbuchungId($hotelbuchungId)
    {
        $hotelbuchungId = (int) $hotelbuchungId;
        if($hotelbuchungId == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->hotelbuchungId = $hotelbuchungId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzahlGeloeschterDatensaetzeHotelbuchung()
    {
        return $this->anzahlGeloeschterDatensaetzeHotelbuchung;
    }

    /**
     * Steuert das löschen der Übernchtungen eines Warenkorbes
     *
     * @return nook_ToolWarenkorbHotelZimmerLoeschen
     */
    public function steuerungLoeschenUebernachtungen()
    {
        if(is_null($this->hotelbuchungId) and is_null($this->teilrechnungHotelbuchungId))
            throw new nook_Exception('Anfangswerte fehlen');

        if(!is_null($this->hotelbuchungId) and !is_null($this->teilrechnungHotelbuchungId))
                    throw new nook_Exception('Falsche Anzahl Anfangswerte');

        if($this->hotelbuchungId)
            $anzahlGeloeschterDatensaetzeHotelbuchung = $this->loeschenDatensatzZimmerbuchung($this->hotelbuchungId);
        else
            $anzahlGeloeschterDatensaetzeHotelbuchung = $this->loeschenTeilbuchungHotel($this->teilrechnungHotelbuchungId);

        $this->anzahlGeloeschterDatensaetzeHotelbuchung = $anzahlGeloeschterDatensaetzeHotelbuchung;

        return $this;
    }

    /**
     * löscht den Datensatz einer Zimmerbuchung
     *
     * @param $hotelbuchungId
     * @return int
     */
    protected  function loeschenDatensatzZimmerbuchung($hotelbuchungId)
    {
        $whereDelete = array(
            'id' => $hotelbuchungId
        );

        $anzahlGeloeschterDatensaetzeHotelbuchung = $this->tabelleHotelbuchung->delete($hotelbuchungId);

        return $anzahlGeloeschterDatensaetzeHotelbuchung;
    }

    /**
     * Löscht die Datensätze der Teilrechnung einer Hotelbuchung
     *
     * @param $teilrechnungHotelbuchungId
     * @return int
     */
    protected function loeschenTeilbuchungHotel($teilrechnungHotelbuchungId)
    {
        $whereDelete = array(
            'teilrechnungen_id' => $teilrechnungHotelbuchungId
        );

        $anzahlGeloeschterDatensaetzeHotelbuchung = $this->tabelleHotelbuchung->delete($whereDelete);

        return $anzahlGeloeschterDatensaetzeHotelbuchung;
    }



}
 
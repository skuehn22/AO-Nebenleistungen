<?php 
 /**
 * Löschen einer Hotelbuchung Teilrechnung
 *
 * @author Stephan.Krauss
 * @date 16.12.2013
 * @file ToolHotelbuchungTeilrechnungLoeschen.php
 * @package tools
 */
 
class nook_ToolHotelbuchungTeilrechnungLoeschen
{

    private $pimple = null;

    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var $tabelleTeilrechnungen Application_Model_DbTable_teilrechnungen */
    private $tabelleTeilrechnungen = null;

    protected $teilrechnungId = null;
    protected $anzahlGeloeschteRaten = 0;

    public function __construct(Pimple_Pimple $pimple){
        $this->servicecontainer($pimple);
    }

    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleHotelbuchung',
            'tabelleTeilrechnungen'
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
     * @param $teilrechnungId
     * @return nook_ToolHotelbuchungTeilrechnungLoeschen
     */
    public function setTeilrechnungId($teilrechnungId)
    {
        $teilrechnungId = (int) $teilrechnungId;
        $this->teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzahlGeloeschteRaten()
    {
        return $this->anzahlGeloeschteRaten;
    }

    /**
     * Steuert das loeschen einer Teilrechnung einer Hotelbuchung
     *
     * @return nook_ToolHotelbuchungTeilrechnungLoeschen
     */
    public function steuerungLoeschenTeilrechnung()
    {
        if(is_null($this->teilrechnungId))
            throw new nook_Exception('Teilrechnungs ID unbekannt');

        $anzahlGeloeschteRaten = $this->loeschenHotelbuchungenTeilrechnung($this->teilrechnungId);
        $kontrolleLoeschenTeilrechnung = $this->loeschenTeilrechnung($this->teilrechnungId);

        return $this;
    }

    /**
     * löscht die Teilrechnung einer Hotelbuchung
     *
     * @param $teilrechnungId
     * @return int
     */
    private function loeschenHotelbuchungenTeilrechnung($teilrechnungId)
    {
        $where = array(
            'teilrechnungen_id = '.$teilrechnungId
        );

        $anzahlGeloeschteRaten = $this->tabelleHotelbuchung->delete($where);

        return $anzahlGeloeschteRaten;
    }

    /**
     * loeschen der Zuordnung der Teilrechnung in 'tbl_teilrechnung'
     *
     * @param $teilrechnungId
     * @return int
     */
    private function loeschenTeilrechnung($teilrechnungId)
    {
        $where = array(
            "id = ".$teilrechnungId
        );

        $kontrolleLoeschenTeilrechnung = $this->tabelleTeilrechnungen->delete($where);

        return $kontrolleLoeschenTeilrechnung;
    }
}
 
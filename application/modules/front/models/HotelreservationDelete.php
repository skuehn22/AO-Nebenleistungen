<?php 
 /**
 * Löscht eine Teilrechnung der Hotelbuchungen aus dem Warenkorb. Gibt Anzahl der Löschoperationen Hotelbuchung und Produktbuchung zurück
 *
 * + löschen Hotelbuchungen Teilrechnung eines Warenkorbes
 * + löshen Produktbuchungen einer Teilrechnung Hotelbuchung des Warenkorbes
 *
 * @author Stephan.Krauss
 * @date 18.12.2013
 * @file HotelreservationDelete.php
 * @package front
 * @subpackage model
 */
class Front_Model_HotelreservationDelete extends Front_Model_Grundmodel
{
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    protected $tabelleHotelbuchung = null;
    /** @var $tabelleTeilrechnungen Application_Model_DbTable_teilrechnungen */
    protected $tabelleTeilrechnungen = null;
    /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    protected $tabelleProduktbuchung = null;

    private $steuerungLoeschmodus = 0;

    private $condition_loeschen_hotelbuchungen = 1;
    private $condition_loeschen_produktbuchungen = 2;

    protected $teilrechnungId = null;

    protected $anzahlGeloeschteRaten = 0;
    protected $anzahlGeloeschteHotelprodukte = 0;
    protected $kontrolleLoeschenTeilrechnung = false;

    protected $tools = array(
                'tabelleHotelbuchung',
                'tabelleTeilrechnungen',
                'tabelleProduktbuchung'
            );

    /**
     * @param $teilrechnungId
     * @return Front_Model_HotelreservationDelete
     */
    public function setTeilrechnungId($teilrechnungId)
    {
        $teilrechnungId = (int) $teilrechnungId;
        if($teilrechnungId == 0)
            throw new nook_Exception('Teilrechnungs ID falsch');

        $this->teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * 1 = loeschen Teilrechnung Hotelbuchung und Produktbuchung
     * 2 = loeschen Teilrechnung Produktbuchung
     *
     * @param int $flagLoeschen
     */
    public function setFlagLoeschen($steuerungLoeschmodus)
    {
        $steuerungLoeschmodus = (int) $steuerungLoeschmodus;
        if( ($steuerungLoeschmodus == 0) or ($steuerungLoeschmodus > 2) )
            throw new nook_Exception('falsche Vorgabe Loeschmodus');

        $this->steuerungLoeschmodus = $steuerungLoeschmodus;

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
     * @return int
     */
    public function getAnzahlGeloeschteHotelprodukte()
    {
        return $this->anzahlGeloeschteHotelprodukte;
    }

    /**
     * @return boolean
     */
    public function getKontrolleLoeschenTeilrechnung()
    {
        return $this->kontrolleLoeschenTeilrechnung;
    }

    /**
     * Steuert das löschen der Teilbuchung Hotel eines Warenkorbes
     *
     * + Kontrolle Teilrechnungs ID
     * + Kontrolle Variable Loeschmodus
     * + löschen Hotelbuchung in 'tbl_hotelbuchung'
     * + löschen Produktbuchung 'tbl_produktbuchung'
     * + löschen teilrechnung 'tbl_teilrechnung'
     *
     * @return Front_Model_HotelreservationDelete
     */
    public function steuerungLoeschenTeilrechnungHotelbuchung()
    {
        if(is_null($this->teilrechnungId))
            throw new nook_Exception('Teilrechnungs ID fehlt');

        if($this->steuerungLoeschmodus == 0)
            throw new nook_Exception('Vorgabe Loeschmodus fehlt');

        // löschen Hotelbuchung in 'tbl_hotelbuchung'
        if($this->steuerungLoeschmodus == $this->condition_loeschen_hotelbuchungen ){
            $anzahlGeloeschteRaten = $this->loeschenHotelbuchung($this->teilrechnungId, $this->tabelleHotelbuchung);
            $this->anzahlGeloeschteRaten = $anzahlGeloeschteRaten;
        }

        // löschen Produktbuchung 'tbl_produktbuchung'
        if($this->steuerungLoeschmodus > $this->condition_loeschen_hotelbuchungen){
            $anzahlGeloeschteHotelprodukte = $this->loeschenHotelprodukte($this->teilrechnungId, $this->tabelleProduktbuchung);
            $this->anzahlGeloeschteHotelprodukte = $anzahlGeloeschteHotelprodukte;
        }

        // löschen teilrechnung 'tbl_teilrechnung'
        if($this->steuerungLoeschmodus == $this->condition_loeschen_hotelbuchungen){
            $kontrolleLoeschenTeilrechnung = $this->loeschenTeilrechnung($this->teilrechnungId, $this->tabelleTeilrechnungen);
            $this->kontrolleLoeschenTeilrechnung = $kontrolleLoeschenTeilrechnung;
        }

        return $this;
    }

    /**
     * Löscht die Raten einer Teilrechnung
     *
     * @param $teilrechnungId
     * @param $tabelleHotelbuchung
     * @return int
     */
    protected function loeschenHotelbuchung($teilrechnungId,Application_Model_DbTable_hotelbuchung $tabelleHotelbuchung)
    {
        $anzahlGeloeschteRaten = $tabelleHotelbuchung->delete("teilrechnungen_id = ".$teilrechnungId);

        return $anzahlGeloeschteRaten;
    }

    /**
     * @param $teilrechnungId
     * @param Application_Model_DbTable_produktbuchung $tabelleProduktbuchung
     * @return int
     */
    protected function loeschenHotelprodukte($teilrechnungId,Application_Model_DbTable_produktbuchung $tabelleProduktbuchung)
    {
        $anzahlGeloeschteHotelprodukte = $tabelleProduktbuchung->delete("teilrechnungen_id = ".$teilrechnungId);

        return $anzahlGeloeschteHotelprodukte;
    }

    /**
     * @param $teilrechnungId
     * @param Application_Model_DbTable_teilrechnungen $tabelleTeilrechnungen
     * @return int
     */
    protected function loeschenTeilrechnung($teilrechnungId,Application_Model_DbTable_teilrechnungen $tabelleTeilrechnungen)
    {
        $anzahlLoeschenTeilrechnung = $tabelleTeilrechnungen->delete("id = ".$teilrechnungId);
        if($anzahlLoeschenTeilrechnung == 1)
            return true;
        else
            return false;
    }
}
 
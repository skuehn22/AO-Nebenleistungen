<?php
/**
 * Kontrolliert die Stornofrist eines gebuchten Hotelproduktes
 *
 * @author Stephan.Krauss
 * @date 24.06.13
 * @file ProduktbuchungStornofrist.php
 * @package front
 * @subpackage model
 */
class Front_Model_ProduktbuchungStornofrist implements Front_Model_StornofristenArtikelInterface
{
    // Fehler
    // private $error_artikel_id_unbekannt = 1730;

    // Konditionen

    // Flags
    protected $flagInStornofrist = false;

    protected $gebuchterArtikelId = null;
    protected $pimple = null;

    /**
     * @param $gebuchterArtikelId
     */
    public function setGebuchterArtikelId($gebuchterArtikelId)
    {
        $this->gebuchterArtikelId = $gebuchterArtikelId;
    }

    /**
     * Gibt Information zurück ob Artikel noch in der Stornofrist ist
     *
     * @return bool
     */
    public function isInStornofrist()
    {
        return $this->flagInStornofrist;
    }

    /**
     * @param Pimple_Pimple $pimple
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * steuert die Kontrolle der Stornofrist einer Produktbuchung
     *
     * Fake !!!
     */
    public function steuerungKontrolleStornofrist()
    {
        // Fake
        $this->flagInStornofrist = true;
    }
}

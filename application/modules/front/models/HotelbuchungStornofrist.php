<?php
/**
 * Kontrolliert die Stornofrist einer gebuchten Hotelbuchung
 *
 * @author Stephan.Krauss
 * @date 24.06.13
 * @file HotelbuchungStornofrist.php
 * @package front
 * @subpackage model
 */

class Front_Model_HotelbuchungStornofrist implements Front_Model_StornofristenArtikelInterface
{

    // Fehler
    // private $error_artikel_id_unbekannt = 1720;

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
     * steuert die Kontrolle der Stornofrist einer Hotelbuchung
     *
     * Fake !!!
     */
    public function steuerungKontrolleStornofrist()
    {
        // Fake
        $this->flagInStornofrist = true;
    }
}

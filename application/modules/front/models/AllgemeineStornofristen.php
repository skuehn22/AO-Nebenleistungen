<?php
/**
 * Kontrolliert die Stornofristen vom
 *
 * + Programmbuchungen
 * + Hotelbuchungen
 * + Produktbuchungen
 *
 * @author Stephan.Krauss
 * @date 21.06.13
 * @file AllgemeineStornofristen.php
 * @package front
 * @subpackage model
 */

class Front_Model_AllgemeineStornofristen
{
    // Errors
    private $error_klasse_unbekannt = 1700;

    // Konditionen

    // Flags

    protected $buchungsnummerId = null;
    protected $bereich = null;

    private static $instance = null;

    /**
     * Facctory Static Class für Stornoobjekte des Bereiches
     *
     * @param $bereich
     * @param $buchungsnummer
     * @return Front_Model_AllgemeineStornofristen|null
     */
    public static function getInstance($bereich, $buchungsnummer)
    {
        if (!self::$instance) {
            self::$instance = new Front_Model_AllgemeineStornofristen($bereich, $buchungsnummer);
        }

        return self::$instance;
    }

    /**
     * Speichern des DIC und des Bereiches im Stornoobjekt des Bereiches
     *
     * @param $bereich
     * @param $buchungsnummer
     */
    public function __construct($bereich, $buchungsnummer)
    {
        $this->bereich = $bereich;
        $this->buchungsnummerId = $buchungsnummer;
    }

    /**
     * Gibt Storno Objekt des Bereiches zurück
     *
     * @return Front_Model_HotelbuchungStornofrist|Front_Model_ProduktbuchungStornofrist|Front_Model_ProgrammbuchungStornofrist
     * @throws nook_Exception
     */
    public function generateClass()
    {
        switch ($this->bereich) {
            case 'programmbuchung':
                $stornoObjectBereich = new Front_Model_ProgrammbuchungStornofrist();
                break;
            case 'hotelbuchung':
                $stornoObjectBereich = new Front_Model_HotelbuchungStornofrist();
                break;
            case 'produktbuchung':
                $stornoObjectBereich = new Front_Model_ProduktbuchungStornofrist();
                break;
            default:
                throw new nook_Exception($this->error_klasse_unbekannt);
        }

        return $stornoObjectBereich;
    }
}

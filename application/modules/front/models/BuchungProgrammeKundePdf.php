<?php
/**
* Übernimmt die Grundwerte der Pdf Erstellung. Grundlage für aufbauende Klassen.
*
* + Übernimmt das Raster der Tabellenspalten
* + Übernimmt den Zaehler
* + Übernimmt die Buchungsnummer
*
* @date 02.07.13
* @file BuchungProgrammeKundePdf.php
* @package front
* @subpackage model
*/
 
class Front_Model_BuchungProgrammeKundePdf extends Front_Model_WrapperPdf
{
    protected $tabelleMillimeter = array();
    protected $buchungsnummer = null;
    protected $zaehler = null;

    // Fehler
    private $error = 1810;

    /**
     * Übernimmt das Raster der Tabellenspalten
     *
     * + Angabe in Milimeter
     * + es wird in Punkte umgerechner
     *
     * @param $tabelle
     * @return Front_Model_BuchungProgrammeKundePdf
     */
    public function setTabelleMillimeter(array $tabelleMilimeter)
    {
        $this->tabelleMillimeter = $tabelleMilimeter;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_BuchungProgrammeKundePdf
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_BuchungProgrammeKundePdf
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }
}

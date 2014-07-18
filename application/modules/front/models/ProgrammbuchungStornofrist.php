<?php
/**
 * Kontrolliert die Stornofrist eines gebuchten Programmes
 *
 * @author Stephan.Krauss
 * @date 24.06.13
 * @file ProgrammbuchungStornofrist.php
 * @package front
 * @subpackage model
 */

class Front_Model_ProgrammbuchungStornofrist implements Front_Model_StornofristenArtikelInterface
{
    // Fehler
    private $error_anfangswerte_fehlen = 1710;
    private $error_anzahldatensaetze_stimmen_nicht = 1711;

    // Konditionen

    // Flags
    protected $flagInStornofrist = 1; // Default , nicht in Stornofrist

    protected $pimple = null;
    protected $programmdetailsId = null;
    protected $buchungstabelleId = null;

    protected $datumStartProgramm = null;
    protected $stornofristProgramm = null;

    /**
     * Gibt Information zurück ob Artikel noch in der Stornofrist ist
     *
     * @return int
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
     * @param $programmdetailsId
     * @return Front_Model_ProgrammbuchungStornofrist
     */
    public function setProgrammdetailsId($programmdetailsId)
    {
        $this->programmdetailsId = (int) $programmdetailsId;

        return $this;
    }

    /**
     * @param $buchungstabelleId
     * @return Front_Model_ProgrammbuchungStornofrist
     */
    public function setBuchungstabelleId($buchungstabelleId)
    {
        $this->buchungstabelleId = (int) $buchungstabelleId;

        return $this;
    }

    /**
     * Steuerung Kontrolle, ob die Stornierung eines Programmes möglich ist
     *
     * @return Front_Model_ProgrammbuchungStornofrist
     * @throws nook_Exception
     */
    public function steuerungKontrolleStornofrist()
    {
        $this->flagInStornofrist = 1;

        if (empty($this->buchungstabelleId) or empty($this->programmdetailsId)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $this->kontrolleStornofrist();

        return $this;
    }

    /**
     * Kontrolle Stornofrist eines Programmes
     */
    private function kontrolleStornofrist()
    {
        $this->bestimmungDatumProgrammstart();
        $this->bestimmeStornofristProgramm();
        $this->kontrolleMoeglichkeitDerStornierungProgramm();
    }

    /**
     * Ermittelt das Durchführungsdatum des gebuchten Programmes
     *
     * @return string
     * @throws nook_Exception
     */
    private function bestimmungDatumProgrammstart()
    {
        /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $rows = $tabelleProgrammbuchung->find($this->buchungstabelleId)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahldatensaetze_stimmen_nicht);
        }

        $this->datumStartProgramm = $rows[0]['datum'];

        return $rows[0]['datum'];
    }

    /**
     * Ermittelt die Stornofrist eines gebuchten Programmes
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function bestimmeStornofristProgramm()
    {
        /** @var  $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $rows = $tabelleProgrammdetails->find($this->programmdetailsId)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahldatensaetze_stimmen_nicht);
        }

        $this->stornofristProgramm = $rows[0]['stornofrist'];

        return $rows[0]['stornofrist'];
    }

    /**
     * Vergleicht das Startdatum des Programmes mit dem momentanen Datum unter Berücksichtigung der Stornofrist
     */
    private function kontrolleMoeglichkeitDerStornierungProgramm()
    {
        $startDatumProgramm = new DateTime($this->datumStartProgramm);

        $momentanesDatum = new DateTime();
        $momentanesDatum->add(date_interval_create_from_date_string($this->stornofristProgramm . " days"));

        if ($momentanesDatum <= $startDatumProgramm) {
            $this->flagInStornofrist = 2;
        } else {
            $this->flagInStornofrist = 1;
        }

        return $this->flagInStornofrist;
    }

}

<?php
/**
 * Bearbeitet die Buchungspauschalen der programmbuchungen
 *
 * @author Stephan.Krauss
 * @date 24.07.13
 * @file Buchungspauschale.php
 * @package front
 * @subpackage model
 */

class Front_Model_Buchungspauschale
{
    // Tabellen / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;
    /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
    private $tabelleProgrammdetails = null;
    /** @var $tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
    private $tabellePreiseBeschreibung = null;
    /** @var $viewBuchungspauschalen Application_Model_DbTable_viewBuchungspauschalen */
    private $viewBuchungspauschalen = null;

    // Konditionen
    private $condition_buchungspauschale_verwenden = 2;
    private $condition_aktuelle_buchung_zaehler = 0;
    private $condition_sprache_deutsch = 1;

    // Fehler
    private $error_anfangswerte_fehlen = 1930;
    private $error_anzahl_datensaetze_falsch = 1931;

    // Flags

    /** @var  $pimple Pimple_Pimple */
    protected $pimple;
    protected $programmBuchungspauschaleId = null;
    protected $preisvarianteBuchungspauschaleId = null;
    protected $preisBuchungspauschale = null;
    protected $anzahlBuchungspauschalen = 0;
    protected $buchungsNummer = null;

    public function __construct()
    {
        $static = Zend_Registry::get('static');
        $this->programmBuchungspauschaleId = $static->buchungspauschale->programmId;
        $this->preisvarianteBuchungspauschaleId = $static->buchungspauschale->preisvarianteId;
        $this->preisBuchungspauschale = $static->buchungspauschale->preis;

        return $this;
    }

    /**
     * Übernimmt den Pimple - Container
     * + Kontrolliert ob die Tabellen vorhanden sind
     *
     * @param Pimple_Pimple $pimple
     * @return Front_Model_Buchungspauschale
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        if ($pimple->offsetExists('tabelleProgrammbuchung')) {
            $this->tabelleProgrammbuchung = $pimple['tabelleProgrammbuchung'];
        }

        if ($pimple->offsetExists('tabelleProgrammdetails')) {
            $this->tabelleProgrammdetails = $pimple['tabelleProgrammdetails'];
        }

        if ($pimple->offsetExists('tabellePreiseBeschreibung')) {
            $this->tabellePreiseBeschreibung = $pimple['tabellePreiseBeschreibung'];
        }

        if ($pimple->offsetExists('tabelleProgrammbuchung')) {
            $this->tabelleProgrammbuchung = $pimple['tabelleProgrammbuchung'];
        }

        if($pimple->offsetExists('viewBuchungspauschalen')){
            $this->viewBuchungspauschalen = $pimple['viewBuchungspauschalen'];
        }

        return $this;
    }

    /**
     * Löscht die Programme Typ preisvariante Buchungspauschale
     * + löscht Preisvariante Buchungspauschale
     * + neuaufbau Array Programme
     *
     * @param $programme
     * @return array
     */
    public function aussortierenBuchungspauschale($programme)
    {
        foreach ($programme as $key => $value) {
            if ($value['tbl_programme_preisvarianten_id'] == $this->preisvarianteBuchungspauschaleId) {
                $this->loeschenBuchungspauschale($programme[$key]);
                unset($programme[$key]);
            }

        }

        // neuaufbau Array Programme
        $programme = array_merge($programme);

        return $programme;
    }

    /**
     * Löschen der Preisvariante Buchungspauschale
     *
     * @param $programm
     * @return Front_Model_Buchungspauschale
     */
    public function loeschenBuchungspauschale($programmIdBuchungspauschale)
    {
        $delete = array(
            "id = " . $programmIdBuchungspauschale
        );

        $this->tabelleProgrammbuchung->delete($delete);

        return $this;
    }

    /**
     * Berechnet die Anzahl der Buchungspauschalen
     *
     * + sichtet die in den Warenkorb gelegten Programme
     * + ermittelt welche Programme eine Buchungspauschale haben
     * + Gibt die Anzahl der Buchungspauschalen zurück
     * + $anzahlBuchungspauschalen = 0 => keine Buchungspauschale
     * + $anzahlBuchungspauschalen > 0 , Anzahl der Buchungspauschalen
     *
     * @return int
     */
    public function berechneAnzahlBuchungspauschalen()
    {
        if (empty($this->viewBuchungspauschalen))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->buchungsNummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsNummer;
        $whereZaehler = "zaehler = ".$this->condition_aktuelle_buchung_zaehler;

        $select = $this->viewBuchungspauschalen->select();
        $select
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $rows = $this->viewBuchungspauschalen->fetchAll($select)->toArray();

        $anzahlBuchungspauschalen = count($rows);
        $this->anzahlBuchungspauschalen = $anzahlBuchungspauschalen;

        return $anzahlBuchungspauschalen;
    }

    /**
     * Ermittelt den Gesamtpreis der Buchungspauschalen der Programme
     *
     * + wenn keine Buchungspauschale, dann Preis Buchungspauschale = 0
     *
     * @return int
     * @throws nook_Exception
     */
    public function getPreisBuchungspauschalen()
    {
        if(empty($this->programmBuchungspauschaleId) or empty($this->preisvarianteBuchungspauschaleId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->preisBuchungspauschale))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if($this->anzahlBuchungspauschalen == 0)
            return 0;
        else{
            $gesamtpreisBuchungspauschalen = $this->anzahlBuchungspauschalen * $this->preisBuchungspauschale;

            return $gesamtpreisBuchungspauschalen;
        }
    }

    /**
     * Einzelpreis einer Buchungspauschale
     *
     * @return float
     * @throws nook_Exception
     */
    public function getPreisBuchungspauschale()
    {
        if(empty($this->preisBuchungspauschale))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        return $this->preisBuchungspauschale;
    }

    public function getNamePreisvariante()
    {
        if(empty($this->tabellePreiseBeschreibung) or empty($this->preisvarianteBuchungspauschaleId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $cols = array(
            'preisvariante'
        );

        $whereIdPreisvariante = "preise_id = ".$this->preisvarianteBuchungspauschaleId;
        $sprachenId = nook_ToolSprache::ermittelnKennzifferSprache();
        $whereSprache = "sprachen_id = ".$sprachenId;

        $select = $this->tabellePreiseBeschreibung->select();
        $select->from($this->tabellePreiseBeschreibung, $cols)->where($whereIdPreisvariante)->where($whereSprache);

        $rows = $this->tabellePreiseBeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return $rows[0]['preisvariante'];
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_Buchungspauschale
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsNummer = $buchungsnummer;

        return $this;
    }

    /**
     * Eintragen der Buchungspauschale in 'tbl_programmbuchung'
     *
     * + Kontrolle der Anzahl der Buchungspauschalen
     * + Eintragen der Buchungspauschalen in 'tbl_programmbuchung'
     *
     * @return Front_Model_Buchungspauschale
     * @throws nook_Exception
     */
    public function steuerungEintragenBuchungpauschalenMitBuchungsnummer()
    {
        if(empty($this->buchungsNummer) or empty($this->tabelleProgrammbuchung))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->viewBuchungspauschalen))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $anzahlProgrammeMitBuchungspauschale = $this->anzahlBuchungpauschalenMitBuchungsnummer();

        if($anzahlProgrammeMitBuchungspauschale > 0)
            $this->eintragenBuchungpauschalenMitBuchungsnummer($anzahlProgrammeMitBuchungspauschale);

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Buchungspauschalen eines gebuchten Warenkorbes
     *
     * + ermittelt die Anzahl der Buchungspauschalen mit 'view_anzahl_buchungspauschalen'
     *
     * @return int
     */
    private function anzahlBuchungpauschalenMitBuchungsnummer()
    {
        $anzahlProgrammeMitBuchungspauschale = 0;

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsNummer;
        $whereZaehlerAktuelleBuchung = "zaehler = ".$this->condition_aktuelle_buchung_zaehler;

        $select = $this->viewBuchungspauschalen->select();
        $select
            ->where($whereBuchungsnummer)
            ->where($whereZaehlerAktuelleBuchung);

        $rows = $this->viewBuchungspauschalen->fetchAll($select)->toArray();
        $anzahlProgrammeMitBuchungspauschale = count($rows);

        return $anzahlProgrammeMitBuchungspauschale;
    }

    /**
     * Baut den Datensatz der Buchungspauschale und fügt diesen in die Tabelle 'tbl_programmbuchung'
     *
     * @param $anzahlBuchungspauschalen
     */
    private function eintragenBuchungpauschalenMitBuchungsnummer($anzahlBuchungspauschalen)
    {
        $insert = array(
            'buchungsnummer_id' => $this->buchungsNummer,
            'zaehler' => $this->condition_aktuelle_buchung_zaehler,
            'programmdetails_id' => $this->programmBuchungspauschaleId,
            'tbl_programme_preisvarianten_id' => $this->preisvarianteBuchungspauschaleId,
            'anzahl' => $anzahlBuchungspauschalen,
            'datum' => date("Y-m-d"),
            'zeit' => date("H:i:s"),
            'sprache' => $this->condition_sprache_deutsch
        );

        $this->tabelleProgrammbuchung->insert($insert);

        return;
    }




}

<?php 
 /**
 * Ermittelt die Anzahl der Artikel im aktuellen Warenkorb
 *
 * + Gesamtanzahl Artikel
 * + Artikel Bestandsbuchung
 * + neue Artikel
 * 
 * @author Stephan.Krauss
 * @date 02.10.13
 * @file ToolAnzahlArtikelAktuellerWarenkorb.php
 * @package tools
 */
class nook_ToolAnzahlArtikelAktuellerWarenkorb
{
    // Fehler
    private $error_anfangswerte_fehlen = 2260;

    // Konditionen
    private $condition_zaehler_aktueller_warenkorb = 0;
    private $condition_mindest_anzahl_artikel = 1;

    // Flags

    // Informationen

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;

    protected $anzahlAllerArtikelImWarenkorb = 0;
    protected $anzahlProgrammbuchungen = 0;
    protected $anzahlHotelbuchungen = 0;
    protected $anzahlProduktbuchungen = 0;

    protected $statusArtikelImWarenkorb = 0; // Status Artikel im Warenkorb , 0 = kein Status

    protected $condition_status_storniert = 10;
    protected $condition_status_storniert_nacharbeit = 9;

    /**
     * lädt Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple = null)
    {
        if($pimple != false)
            $this->pimple = $pimple;

        $this->servicecontainer();
    }

    /**
     * Servicecontainer
     */
    public function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleProgrammbuchung')){
            $this->pimple['tabelleProgrammbuchung'] = function(){
                return new Application_Model_DbTable_programmbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleHotelbuchung')){
            $this->pimple['tabelleHotelbuchung'] = function(){
                return new Application_Model_DbTable_hotelbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProduktbuchung')){
            $this->pimple['tabelleProduktbuchung'] = function(){
                return new Application_Model_DbTable_produktbuchung();
            };
        }
    }

    /**
     * @param int $zaehler
     */
    /**
     * @param $zaehler
     * @return nook_ToolAnzahlArtikelAktuellerWarenkorb
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param int $buchungsnummer
     *
     * @param $buchungsnummer
     * @return nook_ToolAnzahlArtikelAktuellerWarenkorb
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzahlAllerArtikelImWarenkorb()
    {
        return $this->anzahlAllerArtikelImWarenkorb;
    }

    /**
     * @return int
     */
    public function getAnzahlHotelbuchungen()
    {
        return $this->anzahlHotelbuchungen;
    }

    /**
     * @return int
     */
    public function getAnzahlProduktbuchungen()
    {
        return $this->anzahlProduktbuchungen;
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammbuchungen()
    {
        return $this->anzahlProgrammbuchungen;
    }

    /**
     *
     *
     * @param $statusWarenkorb
     * @return nook_ToolAnzahlArtikelAktuellerWarenkorb
     */
    public function setStatusArtikelWarenkorb($statusWarenkorb)
    {
        $statusWarenkorb = (int) $statusWarenkorb;
        $this->statusArtikelImWarenkorb = $statusWarenkorb;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Anzahl der datensätze des aktuellen Warenkorbes
     *
     * + kontrolliert ob Buchungsnummer vorhanden
     * + kontrolliert ob Zähler vorhanden
     *
     * @return $this
     */
    public function steuerungErmittelnAnzahlArtikel()
    {
        if(empty($this->buchungsnummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if ( (empty($this->zaehler)) and ($this->zaehler != 0) )
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $anzahlArtikel = $this->anzahlArtikelProgrammbuchung($this->buchungsnummer, $this->zaehler);
        $this->anzahlProgrammbuchungen = $anzahlArtikel;

        $anzahlHotelbuchungen = $this->anzahlArtikelHotelbuchung($this->zaehler);
        $this->anzahlHotelbuchungen = $anzahlHotelbuchungen;

        $anzahlProduktbuchungen = $this->anzahlArtikelProduktbuchung($this->zaehler);
        $this->anzahlProduktbuchungen = $anzahlProduktbuchungen;

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Artikel Programmbuchung des aktuellen Warenkorbes
     *
     * @param $zaehler
     * @return int
     */
    private function anzahlArtikelProgrammbuchung($buchungsnummer, $zaehler)
    {

        $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsnummer;
        $whereZaehler = "zaehler = ".$zaehler;

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahlArtikel")
        );

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $select = $tabelleProgrammbuchung->select();
        $select
            ->from($tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $query = $select->__toString();

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        if(count($rows) > 0)
            $this->anzahlAllerArtikelImWarenkorb += $rows[0]['anzahlArtikel'];

        return $rows[0]['anzahlArtikel'];
    }

    /**
     * Ermittelt die Anzahl der Artikel Hotelbuchung des aktuellen Warenkorbes
     *
     * @param $zaehler
     * @return int
     */
    private function anzahlArtikelHotelbuchung($zaehler)
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummer;
        $whereZaehler = "zaehler = ".$zaehler;
        $whereMindestanzahl = "roomNumbers >= ".$this->condition_mindest_anzahl_artikel;
        $whereStatusArtikel = "status = ".$zaehler;

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahlArtikel")
        );

        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler)
            ->where($whereMindestanzahl);

        // Status Warenkorb
//        if($zaehler > 0)
//            $select->where($whereStatusArtikel);

        $query = $select->__toString();

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($rows) > 0)
            $this->anzahlAllerArtikelImWarenkorb += $rows[0]['anzahlArtikel'];

        return $rows[0]['anzahlArtikel'];
    }

    /**
     * Ermittelt die Anzahl der Artikel Produktbuchungen des aktuellen Warenkorbes
     *
     * @param $zaehler
     * @return int
     */
    private function anzahlArtikelProduktbuchung($zaehler)
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummer;
        $whereZaehler = "zaehler = ".$zaehler;
        $whereMindestanzahl = "anzahl >= ".$this->condition_mindest_anzahl_artikel;
        $whereStatusArtikel = "status = ".$zaehler;

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahlArtikel")
        );

        /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $select = $tabelleProduktbuchung->select();
        $select
            ->from($tabelleProduktbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler)
            ->where($whereMindestanzahl);

        // Status Warenkorb
//        if($zaehler > 0)
//            $select->where($whereStatusArtikel);

        $query = $select->__toString();

        $rows = $tabelleProduktbuchung->fetchAll($select)->toArray();

        if(count($rows) > 0)
            $this->anzahlAllerArtikelImWarenkorb += $rows[0]['anzahlArtikel'];

        return $rows[0]['anzahlArtikel'];
    }
}

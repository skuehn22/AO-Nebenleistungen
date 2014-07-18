<?php 
/**
* Ermittlung der Anzahl der Artikel eines Warenkorbes
*
* + Steuert die Ermittlung der Anzahl der Artikel im Warenkorb
* + Betimmung der Anzahl der Programmbuchungen im Warenkorb
* + Betimmung der Anzahl der Hotelbuchungen im Warenkorb
* + Betimmung der Anzahl der Produktbuchungen im Warenkorb
*
* @date 14.11.2013
* @file ToolAnzahlArtikelWarenkorb.php
* @package tools
*/
class nook_ToolAnzahlArtikelWarenkorb
{
    // Fehler
    private $error_anfangswerte_fehlen = 2420;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var  $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    private $tabelleProduktbuchung = null;

    // Konditionen

    // Zustände


    protected  $buchungsNummer = null;
    protected $zaehler = null;

    protected $anzahlProgrammbuchungen = 0;
    protected $anzahlHotelbuchungen = 0;
    protected $anzahlHotelprodukte = 0;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProgrammbuchung',
            'tabelleHotelbuchung',
            'tabelleProduktbuchung'
        );

        foreach($tools as $key => $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            else
                $this->$value = $pimple[$value];
        }

        return;
    }

    /**
     * @param $buchungsnummer
     * @return nook_ToolAnzahlArtikelWarenkorb
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsNummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return nook_ToolAnzahlArtikelWarenkorb
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Anzahl der Artikel im Warenkorb
     *
     * @return nook_ToolAnzahlArtikelWarenkorb
     */
    public function steuerungErmittlungAnzahlArtikelImWarenkorb()
    {
        if(is_null($this->buchungsNummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(is_null($this->zaehler))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $anzahlProgrammbuchungen = $this->ermittlungAnzahlProgrammbuchungen($this->buchungsNummer, $this->zaehler);
        $this->anzahlProgrammbuchungen = $anzahlProgrammbuchungen;

        $anzahlHotelbuchungen = $this->ermittlungAnzahlHotelbuchungen($this->buchungsNummer, $this->zaehler);
        $this->anzahlHotelbuchungen = $anzahlHotelbuchungen;

        $anzahlProduktbuchungen = $this->ermittlungAnzahlProduktbuchungen($this->buchungsNummer, $this->zaehler);
        $this->anzahlHotelprodukte = $anzahlProduktbuchungen;

        return $this;
    }

    /**
     * Betimmung der Anzahl der Programmbuchungen im Warenkorb
     *
     * @param $buchungsNummer
     * @param $zaehler
     * @return int
     */
    private function ermittlungAnzahlProgrammbuchungen($buchungsNummer, $zaehler)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereBuchungsNummer = "buchungsnummer_id = ".$buchungsNummer;
        $whereZaehler = "zaehler = ".$zaehler;

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->from($this->tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsNummer)
            ->where($whereZaehler);

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Betimmung der Anzahl der Hotelbuchungen im Warenkorb
     *
     * @param $buchungsNummer
     * @param $zaehler
     * @return int
     */
    private function ermittlungAnzahlHotelbuchungen($buchungsNummer, $zaehler)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereBuchungsNummer = "buchungsnummer_id = ".$buchungsNummer;
        $whereZaehler = "zaehler = ".$zaehler;

        $select = $this->tabelleHotelbuchung->select();
        $select
            ->from($this->tabelleHotelbuchung, $cols)
            ->where($whereBuchungsNummer)
            ->where($whereZaehler);

        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Betimmung der Anzahl der Produktbuchungen im Warenkorb
     *
     * @param $buchungsNummer
     * @param $zaehler
     * @return int
     */
    private function ermittlungAnzahlProduktbuchungen($buchungsNummer, $zaehler)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereBuchungsNummer = "buchungsnummer_id = ".$buchungsNummer;
        $whereZaehler = "zaehler = ".$zaehler;

        $select = $this->tabelleProduktbuchung->select();
        $select
            ->from($this->tabelleProduktbuchung, $cols)
            ->where($whereBuchungsNummer)
            ->where($whereZaehler);

        $rows = $this->tabelleProduktbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * @return int
     */
    public function getAnzahlAllerArtikelImWarenkorb()
    {
        $anzahlAllerArtikelImWarenkorb = $this->anzahlProgrammbuchungen + $this->anzahlHotelbuchungen + $this->anzahlHotelprodukte;

        return $anzahlAllerArtikelImWarenkorb;
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
    public function getAnzahlHotelprodukte()
    {
        return $this->anzahlHotelprodukte;
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammbuchungen()
    {
        return $this->anzahlProgrammbuchungen;
    }
}

<?php 
/**
* Tool zur Bestimmung des Status einer Bestellung
*
* + Status Buchung, Änderung und Stornierung.
* + Anzahl der Artikel im Warenkorb
*
* @date 17.10.2013
* @file ToolStatusBestellung.php
* @package tools
*/
class nook_ToolStatusBestellung
{
    // Fehler
    private $error_anfangswerte_fehlen = 2310;

    // Informationen

    // Konditionen

    // Tabellen / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung  */
    private $tabelleProgrammbuchung = null;
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    private $tabelleProduktbuchung = null;

    // Flags
    private $flagStatusWarenkorb = array(
        'buchung' => 1,
        'aenderung' => 2,
        'stornierung' => 3
    );

    protected $pimple = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $statusArtikelImWarenkorb = 0;

    protected $statusWarenkorb = 0;
    protected $summeAllerArtikelImWarenkorb = null;

    public function __construct(Pimple_Pimple $pimple = null)
    {
        if($pimple)
           $this->pimple = $pimple;

        $this->servicecontainer();
    }

    /**
     * bereitet die Tabellen vor
     */
    private function servicecontainer()
    {
        if(!$this->pimple)
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleProgrammbuchung'))
            $this->tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        else
            $this->tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];

        if(!$this->pimple->offsetExists('tabelleHotelbuchung'))
            $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        else
            $this->tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];

        if(!$this->pimple->offsetExists('tabelleProduktbuchung'))
            $this->tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        else
            $this->tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];

        return;
    }

    /**
     * @param $zaehler
     * @return nook_ToolStatusBestellung
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return nook_ToolStatusBestellung
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $statusArtikelImWarenkorb
     * @return nook_ToolStatusBestellung
     */
    public function setStatusArtikelImWarenkorb($statusArtikelImWarenkorb)
    {
        $this->statusArtikelImWarenkorb = $statusArtikelImWarenkorb;

        return $this;
    }

    /**
     * Steuert die Ermittlung des Status des Warenkorbes
     *
     * + Anzahl Artikel Programmbuchungen
     * + Anzahl Artikel Hotelbuchungen
     * + Anzahl Artikel Produktbuchungen
     *
     * + sucht nach Buchungsnummer
     * + sucht nach Zaehler
     * + sucht wenn vorhanden nach Status des Artikel
     *
     * @return nook_ToolStatusBestellung
     */
    public function ermittelnStatusWarenkorb()
    {
        if(!$this->buchungsnummer)
            throw new nook_Exception('Buchungsnummer fehlt: '.$this->buchungsnummer);

        if( ($this->zaehler === null) )
            throw new nook_Exception('Zaehler fehlt');

        $where = array(
            "buchungsnummer_id = ".$this->buchungsnummer,
            "zaehler = ".$this->zaehler
        );

        // wenn der Status der Artikel vorgegeben
        if($this->statusArtikelImWarenkorb > 0)
            $where[] = "status = ".$this->statusArtikelImWarenkorb;

        $this->summeAllerArtikelImWarenkorb = $this->summeArtikelProgrammbuchung($this->tabelleProgrammbuchung, $where);
        $this->summeAllerArtikelImWarenkorb += $this->summeArtikelHotelbuchung($this->tabelleHotelbuchung, $where);
        $this->summeAllerArtikelImWarenkorb += $this->summeArtikelProduktbuchung($this->tabelleProduktbuchung, $where);

        if($this->zaehler == 1)
            $this->statusWarenkorb = $this->flagStatusWarenkorb['buchung'];
        elseif($this->summeAllerArtikelImWarenkorb == 0)
            $this->statusWarenkorb = $this->flagStatusWarenkorb['stornierung'];
        else
            $this->statusWarenkorb = $this->flagStatusWarenkorb['aenderung'];

        return $this;
    }

    /**
     * Ermittelt die Summe der Artikel einer Buchung in der Abteilung 'produktbuchung'
     *
     * @param Application_Model_DbTable_produktbuchung $tabelleProduktbuchung
     * @param $where
     * @return int
     */
    private function summeArtikelProduktbuchung(Application_Model_DbTable_produktbuchung $tabelleProduktbuchung, $where)
    {
        $cols = array(
            new Zend_Db_Expr("sum(anzahl) as anzahl")
        );

        $select = $tabelleProduktbuchung->select();
        $select->from($tabelleProduktbuchung, $cols);

        foreach($where as $singleWhere){
            $select->where($singleWhere);
        }

        $rows = $tabelleProduktbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Summe der Artikel einer Buchung in der Abteilung 'programmbuchung'
     *
     * @param Application_Model_DbTable_programmbuchung $tabelleProgrammbuchung
     * @param $where
     * @return int
     */
    private function summeArtikelProgrammbuchung(Application_Model_DbTable_programmbuchung $tabelleProgrammbuchung, $where)
    {
        $cols = array(
            new Zend_Db_Expr("sum(anzahl) as anzahl")
        );

        $select = $tabelleProgrammbuchung->select();
        $select->from($tabelleProgrammbuchung, $cols);

        foreach($where as $singleWhere){
            $select->where($singleWhere);
        }

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Summe der Artikel einer Buchung in der Abteilung 'hotelbuchung'
     *
     * @param Application_Model_DbTable_hotelbuchung $tabelleHotelbuchung
     * @param $where
     * @return int
     */
    private function summeArtikelHotelbuchung(Application_Model_DbTable_hotelbuchung $tabelleHotelbuchung, $where)
   {
       $cols = array(
           new Zend_Db_Expr("sum(roomNumbers) as anzahl")
       );

       $select = $tabelleHotelbuchung->select();
       $select->from($tabelleHotelbuchung, $cols);

       foreach($where as $singleWhere){
           $select->where($singleWhere);
       }

       $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

       return $rows[0]['anzahl'];
   }

    /**
     * Gibt den Status eines Warenkorbes zurück
     *
     * @return int
     */
    public function getStatusWarenkorb()
    {
        return $this->statusWarenkorb;
    }

    /**
     * Gibt die Summe aller Artikel eines Warenkorbes zurück
     *
     * @return int
     */
    public function getSummeAllerArtikelImWarenkorb()
    {
        return $this->summeAllerArtikelImWarenkorb;
    }






}

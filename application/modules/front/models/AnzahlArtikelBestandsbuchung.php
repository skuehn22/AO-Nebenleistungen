<?php 
 /**
 * Ermittelt die Anzahl der Artikel einer Beszandsbuchung
 *
 * @author Stephan.Krauss
 * @date 30.01.2014
 * @file AnzahlArtikelBestandsbuchung.php
 * @package front
 * @subpackage model
 */
 
class Front_Model_AnzahlArtikelBestandsbuchung
{
    /** @var $tabelleProgrammbuchung Zend_Db_Table_Abstract  */
    protected $tabelleProgrammbuchung = null;
    /** @var $tabelleHotelbuchung Zend_Db_Table_Abstract */
    protected $tabelleHotelbuchung = null;

    protected $anzahlArtikelBestandsbuchung = 0;

    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $anzahl = 0;

    protected $condition_stornierung = 10;
    protected $condition_stornierung_mit_nacharbeit = 9;

    /**
     * @param Zend_Db_Table_Abstract $tabelleHotelbuchung
     * @return Front_Model_AnzahlArtikelBestandsbuchung
     */
    public function setTabelleHotelbuchung(Zend_Db_Table_Abstract $tabelleHotelbuchung)
    {
        $this->tabelleHotelbuchung = $tabelleHotelbuchung;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleProgrammbuchung
     * @return Front_Model_AnzahlArtikelBestandsbuchung
     */
    public function setTabelleProgrammbuchung(Zend_Db_Table_Abstract $tabelleProgrammbuchung)
    {
        $this->tabelleProgrammbuchung = $tabelleProgrammbuchung;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_AnzahlArtikelBestandsbuchung
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_AnzahlArtikelBestandsbuchung
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzahlArtikelBestandsbuchung()
    {
        return $this->anzahlArtikelBestandsbuchung;
    }

    /**
     * Steuert die Ermittlung der Artikel einer Bestandsbuchung
     *
     * @return Front_Model_AnzahlArtikelBestandsbuchung
     * @throws nook_Exception
     */
    public function steuerungErmittlungAnzahlArtikelBestandsbuchung()
    {
        if(is_null($this->buchungsnummer) or is_null($this->zaehler) or is_null($this->tabelleHotelbuchung) or is_null($this->tabelleProgrammbuchung))
            throw new nook_Exception('Anfangswerte fehlen');

        $whereCols = $this->whereColsAnzahlBestandsbuchung($this->buchungsnummer, $this->zaehler);

        // Programmbuchungen
        $anzahlArtikelBestandsbuchungProgramme = $this->anzahlArtikelBestandsbuchungProgramme($whereCols);
        // Hotelbuchungen
        $anzahlArtikelBestandsbuchungHotel = $this->ermittelnAnzahlArtikelBestandsbuchungHotel($whereCols);

        $this->anzahlArtikelBestandsbuchung = $anzahlArtikelBestandsbuchungProgramme + $anzahlArtikelBestandsbuchungHotel;

        return $this;
    }

    /**
     * Grundlegende where Klauseln zur Betimmung Anzahl Artikel Bestandsbuchung
     *
     * @param $buchungsnummer
     * @param $zaehler
     * @return array
     */
    protected function whereColsAnzahlBestandsbuchung($buchungsnummer, $zaehler)
    {
        $whereCols = array(
            "buchungsnummer_id = " . $buchungsnummer,
            "zaehler = " . $zaehler,
            "status <> '" . $this->condition_stornierung . "'",
            "status <> '" . $this->condition_stornierung_mit_nacharbeit . "'"
        );

        return $whereCols;
    }

    /**
     * Ermittelt die Anzahl der Artikel einer Bestandsbuchung Hotel
     *
     * @param $whereCols
     * @return int
     */
    protected function ermittelnAnzahlArtikelBestandsbuchungHotel($whereCols)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereCols[] = "roomNumbers > " . $this->anzahl;

        $select = $this->tabelleHotelbuchung->select();
        $select->from($this->tabelleHotelbuchung, $cols);

        foreach($whereCols as $where){
            $select->where($where);
        }

        $query = $select->__toString();
        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Anzahl der Artikel 'Programme' einer Bestandsbuchung
     *
     * @param $whereBuchungsnummer
     * @param $whereZehler
     * @param $whereStorno
     * @param $whereStornoMitNacharbeit
     * @return array
     */
    protected function anzahlArtikelBestandsbuchungProgramme($whereCols)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereCols[] = "anzahl > " . $this->anzahl;

        $select = $this->tabelleProgrammbuchung->select();
        $select->from($this->tabelleProgrammbuchung, $cols);

        foreach($whereCols as $where){
            $select->where($where);
        }

        $query = $select->__toString();
        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }
}
 
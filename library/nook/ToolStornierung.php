<?php 
/**
* Ermittelt die Anzahl der Artikel einer Buchung.
*
* + Initialisiert den Servicecontainer
* + Servicecontainer , Buchungstabellen verwendet mit Share
* + steuert die Ermittlung der Anzahl der Artikel im Warenkorb
* + Ermittelt die Anzahl der Artikel im Warenkorb, entsprechend der Buchungstabelle
* + Gibt einen Flag zurück, ob Stornierung der Buchungsnummer
*
* @date 08.10.13
* @file ToolStornierung.php
* @package tools
*/
class nook_ToolStornierung
{
    protected $pimple = null;
    protected $anzahlAllerArtikelImWarenkorb = 0;

    protected $buchungsnummer = 0;
    protected $zaehler = 0;

    /**
     * Initialisiert den Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct()
    {
        $this->servicecontainer();
    }

    /**
     * @param $buchungsnummer
     * @return nook_ToolStornierung
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return nook_ToolStornierung
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * Servicecontainer , Buchungstabellen verwendet mit Share
     */
    private function servicecontainer()
    {
        $this->pimple = new Pimple_Pimple();

        $this->pimple['tabelleProgrammbuchung'] = $this->pimple->share(function($c){
            return new Application_Model_DbTable_programmbuchung();
        });

        $this->pimple['tabelleHotelbuchung'] = $this->pimple->share(function($c){
            return new Application_Model_DbTable_hotelbuchung();
        });

        $this->pimple['tabelleProduktbuchung'] = $this->pimple->share(function($c){
            return new Application_Model_DbTable_produktbuchung();
        });

        return;
    }

    /**
     * steuert die Ermittlung der Anzahl der Artikel im Warenkorb
     *
     * + Anzahl aktive Artikel
     * + Anzahl passive Artikel
     *
     * @return nook_ToolStornierung
     * @throws nook_Exception
     */
    public function anzahlArtikelImWarenkorb()
    {
        if(empty($this->buchungsnummer))
            throw new nook_Exception('keine Buchungsnummer vorhanden');

        if(empty($this->zaehler) and $this->zaehler != 0)
            throw new nook_Exception('Zaehler des Warenkorbes ist leer und ungleich 0');

        $this->anzahlAllerArtikelImWarenkorb = 0;

        // Programbuchung
        $this->ermittelnAnzahlArtikel($this->pimple['tabelleProgrammbuchung']);

        // Hotelbuchung
        $this->ermittelnAnzahlArtikel($this->pimple['tabelleHotelbuchung']);

        // Produktbuchung
        $this->ermittelnAnzahlArtikel($this->pimple['tabelleProduktbuchung']);

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Artikel im Warenkorb, entsprechend der Buchungstabelle
     *
     * + Anzahl Programmbuchungen
     * + Anzahl Hotelbuchungen
     * + Anzahl Produktbuchungen
     *
     * @param Zend_Db_Table $tabelle
     * @param $flagPassiveArtikel
     * @return mixed
     */
    private function ermittelnAnzahlArtikel(Zend_Db_Table_Abstract $tabelle)
    {
        if($tabelle instanceof Application_Model_DbTable_hotelbuchung){
            $cols = array(
                new Zend_Db_Expr("sum(roomNumbers) as anzahl")
            );
        }
        else{
            $cols = array(
                new Zend_Db_Expr("sum(anzahl) as anzahl")
            );
        }

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummer;
        $whereZaehler = "zaehler = ".$this->zaehler;

        $select = $tabelle->select();
        $select
            ->from($tabelle, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $rows = $tabelle->fetchAll($select)->toArray();
        $this->anzahlAllerArtikelImWarenkorb += $rows[0]['anzahl'];

        return $rows[0]['anzahl'];
    }

    /**
     * @return int
     */
    public function getAnzahlAllerArtikelImWarenkorb()
    {
        return $this->anzahlAllerArtikelImWarenkorb;
    }

    /**
     * Gibt einen Flag zurück, ob Stornierung der Buchungsnummer
     *
     * @return bool
     */
    public function isStornierung()
    {
        if($this->anzahlAllerArtikelImWarenkorb == 0)
            return true;
        else
            return false;
    }
}

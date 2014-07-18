<?php 
 /**
 * Löscht die Artikel eines Warenkorbes einer Abteilung aus einer Buchungstabelle
 *
 * @author Stephan.Krauss
 * @date 17.01.2014
 * @file WarenkorbLoeschen.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbArtikelAbteilungLoeschen
{
    /** @var $tabelleProgrammbuchung Zend_Db_Table_Abstract */
    protected $tabelle = null;

    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $status = null;

    protected $anzahlGeloeschteDatensaetze = 0;

    /**
     * @param Zend_Db_Table_Abstract $tabelle
     * @return Front_Model_WarenkorbArtikelAbteilungLoeschen
     */
    public function setTabelle(Zend_Db_Table_Abstract $tabelle)
    {
        $this->tabelle = $tabelle;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_WarenkorbArtikelAbteilungLoeschen
     * @throws nook_Exception
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        if($buchungsnummer == 0)
            throw new nook_Exception('Buchungsnummer keine int');

        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_WarenkorbArtikelAbteilungLoeschen
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $status
     * @return Front_Model_WarenkorbArtikelAbteilungLoeschen
     */
    public function setStatus($status)
    {
        $status = (int) $status;
        $this->status = $status;

        return $this;
    }

    /**
     * Steuert das löshen der Artikel einer Abteilung eines Warenkorbes
     *
     * @return Front_Model_WarenkorbArtikelAbteilungLoeschen
     * @throws nook_Exception
     */
    public function steuerungLoeschenWarenkorb()
    {
        try{
            if(is_null($this->buchungsnummer))
                throw new nook_Exception('Buchungsnummer fehlt');

            if(is_null($this->zaehler))
                throw new nook_Exception('Zaehler fehlt');

            if(is_null($this->status))
                throw new nook_Exception('Status fehlt');

            if(! $this->tabelle instanceof Zend_Db_Table_Abstract)
                throw new nook_Exception('keine Tabelle angegeben');

            $where = array(
                'buchungsnummer_id = '.$this->buchungsnummer,
                'zaehler = '.$this->zaehler,
                'status = '.$this->status
            );

            // Artikel löschen
            $this->anzahlGeloeschteDatensaetze = $this->loeschenArtikelWarenkorb($this->tabelle, $where);

            return $this;
        }
        catch(nook_Exception $e){
            throw $e;
        }

    }

    /**
     * löscht Artikel eines Bereiches aus dem Warenkorb
     *
     * @param Zend_Db_Table_Abstract $tabelle
     * @param array $where
     * @return int
     */
    protected function loeschenArtikelWarenkorb(Zend_Db_Table_Abstract $tabelle, array $where)
    {
        $anzahlGeloeschteDatensaetze = $tabelle->delete($where);

        return $anzahlGeloeschteDatensaetze;
    }

    /**
     * @return int
     */
    public function getAnzahlGeloeschteArtikel()
    {
        return $this->anzahlGeloeschteDatensaetze;
    }
}
 
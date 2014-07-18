<?php 
 /**
 * Dupliziert die Artikel eines Warenkorbes
 *
 * + dupliziert Datensätze eines Warenkorbes
 * + setzt neuen Status des Warenkorbes
 *
 * @author Stephan.Krauss
 * @date 16.01.2014
 * @file WarenkorbDuplizieren.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbDuplizieren
{
    /** @var Zend_Db_Table_Abstract  */
    private $tablle = null;

    private $buchungsnummerId = null;
    private $zaehler = null;
    private $alterStatus = null;
    private $neuerStatus = null;

    protected $momentaneBuchungsnummer = null;

    public function __construct()
    {

    }

    /**
     * @param $tabelle
     * @return Front_Model_WarenkorbDuplizieren
     */
    public function setTable(Zend_Db_Table_Abstract $tabelle)
    {
        $this->tablle = $tabelle;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_WarenkorbDuplizieren
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_WarenkorbDuplizieren
     * @throws nook_Exception
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        if($buchungsnummerId == 0)
            throw new nook_Exception('keine int Buchungsnummer');

        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    public function setNeueBuchungsnummer($momentaneBuchungsnummer)
    {
        $momentaneBuchungsnummer = (int) $momentaneBuchungsnummer;
        $this->momentaneBuchungsnummer = $momentaneBuchungsnummer;

        return $this;
    }

    /**
     * @param $alterStatus
     * @return Front_Model_WarenkorbDuplizieren
     * @throws nook_Exception
     */
    public function setAlterStatus($alterStatus)
    {
        $alterStatus = (int) $alterStatus;
        if($alterStatus == 0)
            throw new nook_Exception('Status keine int');

        $this->alterStatus = $alterStatus;

        return $this;
    }

    /**
     * @param $neuerStatus
     * @return Front_Model_WarenkorbDuplizieren
     * @throws nook_Exception
     */
    public function setNeuerStatus($neuerStatus)
    {
        $neuerStatus = (int) $neuerStatus;
        if($neuerStatus == 0)
            throw new nook_Exception('neuer Status kein int');

        $this->neuerStatus = $neuerStatus;

        return $this;
    }

    /**
     * Dupliziert die Artikel eines Warenkorbes
     *
     * @return array
     * @throws nook_Exception
     */
    public function steuerungDuplizierenDatensaetzeBuchungstabelle()
    {
        if(is_null($this->tablle))
            throw new nook_Exception('keine Tabelle angegeben');

        if(is_null($this->buchungsnummerId))
            throw new nook_Exception('keine Buchungsnummer angegeben');

        if(is_null($this->zaehler))
            throw new nook_Exception('kein Zaehler angegeben');

        if(is_null($this->alterStatus))
            throw new nook_Exception('alter Status fehlt');

        if(is_null($this->neuerStatus))
            throw new nook_Exception('neuer Status fehlt');

        $artikelAlterWarenkorb = $this->ermittelnAlteDatensaetzeWarenkorb($this->buchungsnummerId, $this->zaehler, $this->alterStatus);

        if(count($artikelAlterWarenkorb) > 0){

            for($i=0; $i < count($artikelAlterWarenkorb); $i++){
                $neuerArtikel = $artikelAlterWarenkorb[$i];

                unset($neuerArtikel['id']);
                unset($neuerArtikel['hobNummer']);
                $neuerArtikel['status'] = $this->neuerStatus;

                if(!empty($this->momentaneBuchungsnummer))
                    $neuerArtikel['buchungsnummer_id'] = $this->momentaneBuchungsnummer;


                $insertId = $this->duplizierenArtikelWarenkorb($neuerArtikel);
            }

            if(empty($insertId))
                throw new nook_Exception('kein neuer Artikel im Warenkorb eingefügt');
        }

        return $artikelAlterWarenkorb;
    }

    /**
     * Ermittelt die Artikel eines Warenkorbes
     *
     * + sucht nach Buchungsnummer
     * + sucht nach Zaehler
     * + sucht nach Status
     *
     * @param $buchungsnummerId
     * @param $zaehler
     * @param $alterStatus
     * @return array
     */
    protected function ermittelnAlteDatensaetzeWarenkorb($buchungsnummerId, $zaehler, $alterStatus)
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsnummerId;
        $whereZaehler = "zaehler = ".$zaehler;
        $whereStatus = "status = ".$alterStatus;

        $select = $this->tablle->select();
        $select
            ->where($whereBuchungsnummer)
            ->where($whereZaehler)
            ->where($whereStatus);

        $query = $select->__toString();
        $alterWarenkorb = $this->tablle->fetchAll($select)->toArray();

        return $alterWarenkorb;
    }

    /**
     * Fügt die Artikel in die Buchungstabelle mit neuem Status ein.
     *
     * @param array $artikelAlterWarenkorb
     * @param $neuerStatus
     * @return int
     */
    protected function duplizierenArtikelWarenkorb(array $neuerArtikel)
    {
        $insertId = $this->tablle->insert($neuerArtikel);

        return $insertId;
    }
}
 
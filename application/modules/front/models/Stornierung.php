<?php 
 /**
 * Grundlegende Methoden für die Model der Stornierung
 *
 * @author Stephan.Krauss
 * @date 01.10.2013
 * @file Stornierung.php
 * @package front
 * @subpackage model
 */
 
class Front_Model_Stornierung
{
    protected $buchungsnummer = null;
    protected $zaehler = null;

    protected $flagBestandsbuchung = false;

    /** @var $pimple Pimple_Pimple  */
    protected $pimple = null;
    protected $artikelWarenkorb = array();

    /**
     * @param $flagBestandsbuchung
     * @return Front_Model_Stornierung
     */
    public function setFlagBestandsbuchung($flagBestandsbuchung)
    {
        $this->flagBestandsbuchung = $flagBestandsbuchung;

        return $this;
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_Stornierung
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * @param array $artikelWarenkorb
     * @return Front_Model_Stornierung
     */
    public function setArtikelWarenkorb(array $artikelWarenkorb)
    {
        $this->artikelWarenkorb = $artikelWarenkorb;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStatusWork(){
        return $this->flagStatusWork;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_Stornierung
     */
    public function setBuchungsnummer($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        $this->buchungsnummer = $buchungsnummerId;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_Stornierung
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }
}
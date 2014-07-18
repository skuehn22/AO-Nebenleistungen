<?php 
/**
* Erhöht den Zähler des Warenkorbes einer stornierten Bestandsbuchung
*
* + Füllen Servicecontainer
* + Buchungstabellen im Servicecontainer
* + Steuerung, verändern Zaehler in Session und 'tbl_buchungsnummer'
* + neuer Zaehler in der Session, wenn Bestandsbuchung
* + Erhöht den Zähler in der 'tbl_buchungsnummer'
* + Steuerung, veraendern Zaehler in Buchungstabellen
* + Setzt den Zaehler in den Buchungstabellen einer stornierten Bestandsbuchung neu
* + Kontrolliert die benötigten Anfangswerte
*
* @date 08.10.13
* @file ToolErhoehenZaehlerWarenkorb.php
* @package tools
*/
 class nook_ToolVeraendernZaehlerWarenkorb
{
    // Fehler
    private $error_anfangswerte_fehlen = 2280;
    private $error_anzahl_datensaetze_falsch = 2281;

    // Kondition
    private $condition_aktueller_warenkorb = 0;

    // Tabelle / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    private $tabelleProduktbuchung = null;
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;

    protected $buchungsnummer = null;
    protected $momentanerZaehler = null;
    protected $neuerZaehler = null;

    protected $pimple = null;
    protected $sessionId = null;

     /**
      * Füllen Servicecontainer
      *
      * @param Pimple_Pimple $pimple
      */
     public function __construct(Pimple_Pimple $pimple = null)
    {
        if($pimple)
            $this->pimple = $pimple;

        $this->servicecontainer();

        $this->sessionId = Zend_Session::getId();
    }

     /**
      * @param $buchungsnummer
      * @return nook_ToolVeraendernZaehlerWarenkorb
      */
     public function setBuchungsnummer($buchungsnummer)
     {
         $buchungsnummer = (int) $buchungsnummer;
         $this->buchungsnummer = $buchungsnummer;

         return $this;
     }

     /**
      * Übernimmt den momentanen Zaehler des Warenkorbes
      *
      * @param $zaehler
      * @return nook_ToolVeraendernZaehlerWarenkorb
      */
     public function setMomentanerZaehler($momentanerZaehler)
     {
         $momentanerZaehler = (int) $momentanerZaehler;
         $this->momentanerZaehler = $momentanerZaehler;

         return $this;
     }

     /**
      * übernimmt den neuen Zaehler
      *
      * @param $neuerZaehler
      * @return $this
      */
     public function setNeuerZaehler($neuerZaehler)
     {
         $neuerZaehler = (int) $neuerZaehler;
         $this->neuerZaehler = $neuerZaehler;

         return $this;
     }

     /**
      * Buchungstabellen im Servicecontainer
      */
     private function servicecontainer()
    {
        if($this->pimple){
            if($this->pimple->offsetExists('tabelleProgrammbuchung')){
                $this->tabelleProgrammbuchung = clone $this->pimple['tabelleProgrammbuchung'];
            }

            if($this->pimple->offsetExists('tabelleHotelbuchung')){
                $this->tabelleProgrammbuchung = clone $this->pimple['tabelleHotelbuchung'];
            }

            if($this->pimple->offsetExists('tabelleProduktbuchung')){
                $this->tabelleProgrammbuchung = clone $this->pimple['tabelleProduktbuchung'];
            }

            if($this->pimple->offsetExists('tabelleBuchungsnummer')){
                $this->tabelleProgrammbuchung = clone $this->pimple['tabelleBuchungsnummer'];
            }
        }

        if(!$this->tabelleProgrammbuchung)
            $this->tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();

        if(!$this->tabelleHotelbuchung)
            $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();

        if(!$this->tabelleProduktbuchung)
            $this->tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

        if(!$this->tabelleBuchungsnummer)
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return;
    }

     /**
      * Steuerung, verändern Zaehler in Session und 'tbl_buchungsnummer'
      *
      * @return nook_ToolVeraendernZaehlerWarenkorb
      */
     public function neuerZaehlerSessionUndTabelleBuchungsnummer()
     {
        $this->kontrolleAnfangswerte();

        $this->neuerZaehlerInSession($this->neuerZaehler);
        $this->neuerZaehlerInBuchungstabelle($this->neuerZaehler);

        return $this;
     }

     /**
      * neuer Zaehler in der Session, wenn Bestandsbuchung
      *
      * + erhoeht den Zaehler um 1
      */
     private function neuerZaehlerInSession($neuerZaehler)
     {
         $buchung = new Zend_Session_Namespace('buchung');
         $buchung->zaehler = $neuerZaehler;

         return $neuerZaehler;
     }

     /**
      * Erhöht den Zähler in der 'tbl_buchungsnummer'
      *
      * @param $neuerZaehler
      * @return int
      * @throws nook_Exception
      */
     private function neuerZaehlerInBuchungstabelle($neuerZaehler)
     {
         $upadet = array(
             'zaehler' => $neuerZaehler
         );

         $where = array(
             "id = ".$this->buchungsnummer,
             "zaehler = ".$this->momentanerZaehler,
             "session_id = '".$this->sessionId."'"
         );

         $kontrolle = $this->tabelleBuchungsnummer->update($upadet, $where);

         if($kontrolle != 1)
             throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

         return $kontrolle;
     }

     /**
      * Steuerung, veraendern Zaehler in Buchungstabellen
      *
      * @return nook_ToolVeraendernZaehlerWarenkorb
      */
     public function neuerZaehlerBuchungstabellen()
     {
         $this->kontrolleAnfangswerte();

         // Programmbuchung
         $this->zaehlerBuchungstabellen($this->tabelleProgrammbuchung);

         // Hotelbuchung
         $this->zaehlerBuchungstabellen($this->tabelleHotelbuchung);

         // Produktbuchung
         $this->zaehlerBuchungstabellen($this->tabelleProduktbuchung);

         return $this;
     }

     /**
      * Setzt den Zaehler in den Buchungstabellen einer stornierten Bestandsbuchung neu
      *
      * @param $zaehler
      * @return int
      */
     private function zaehlerBuchungstabellen(Zend_Db_Table_Abstract $tabelle)
     {
         $update = array(
            'zaehler' => $this->neuerZaehler
         );

         $where = array(
             "buchungsnummer_id = ".$this->buchungsnummer,
             "zaehler = ".$this->condition_aktueller_warenkorb
         );

         $kontrolle = $tabelle->update($update, $where);

         return $kontrolle;
     }

     /**
      * Kontrolliert die benötigten Anfangswerte
      */
     private function kontrolleAnfangswerte()
     {
         if(is_null($this->buchungsnummer))
             throw new nook_Exception($this->error_anfangswerte_fehlen);

         if(is_null($this->momentanerZaehler))
             throw new nook_Exception($this->error_anfangswerte_fehlen);

         return;
     }
}

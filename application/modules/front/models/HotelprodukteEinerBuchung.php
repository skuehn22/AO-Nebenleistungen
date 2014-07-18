<?php
/**
 * Ermittelt die Produkte , Hotelprodukte einer Buchung
 *
 * + verwendet Buchungsnummer
 * + verwendet Zaehler
 * + sucht die gebuchten Hotelprodukte / Produkte einer Buchung
 *
 * @author Stephan Krauss
 * @date 05.06.2014
 * @file Front_Model_HotelprodukteEinerBuchung.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 
 class Front_Model_HotelprodukteEinerBuchung
 {
     protected $buchungsnummer = null;
     protected $zaehler = null;
     protected $pimple = null;

     protected $produktbuchungen = array();

     /**
      * @param $buchungsnummer
      * @return Front_Model_HotelprodukteEinerBuchung
      * @throws nook_Exception
      */
     public function setBuchungsnummer($buchungsnummer)
     {
         $buchungsnummer = (int) $buchungsnummer;

         if($buchungsnummer == 0)
             throw new nook_Exception('Buchungsnummer ist kein Int');

         $this->buchungsnummer = $buchungsnummer;

         return $this;
     }

     /**
      * @param $zaehler
      * @return Front_Model_HotelprodukteEinerBuchung
      * @throws nook_Exception
      */
     public function setZaehler($zaehler)
     {
         $zaehler = (int) $zaehler;
         if($zaehler == 0)
             throw new nook_Exception('Zaehler ist kein Int');

         $this->zaehler = $zaehler;

         return $this;

     }

     /**
      * Übernimmt den DIC zur Ermittlung der Produkte der Hotels einer Buchung
      *
      * @param Pimple_Pimple $pimple
      * @return Front_Model_HotelprodukteEinerBuchung
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $pimple = $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolliert das vorhandensein der Tabelle 'tabelleProduktbuchung'
      *
      * @param Pimple_Pimple $pimple
      * @return Pimple_Pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         if(!$pimple->offsetExists('tabelleProduktbuchung'))
             throw new nook_Exception("Tabelle 'tabelleProduktbuchung' fehlt");

         return $pimple;
     }


     /**
      * Steuert die Ermittlung der gebuchten Hotelprodukte Produkte einer Buchung
      *
      * @return Front_Model_HotelprodukteEinerBuchung
      * @throws Exception
      */
     public function  steuerungErmittlungProdukteEinerBuchung(){
        try{
            if( (is_null($this->buchungsnummer)) or (is_null($this->zaehler)) )
                throw new nook_Exception('Buchungsnummer oder Zaehler fehlt');

            if( is_null($this->pimple))
                throw new nook_Exception('DIC fehlt');

            $produktbuchungen = $this->ermittlungProdukteEinerBuchung($this->buchungsnummer, $this->zaehler, $this->pimple['tabelleProduktbuchung']);
            $this->produktbuchungen = $produktbuchungen;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
     }

     /**
      * Ermittelt die vorhandenen Produkte einer Hotelbuchung
      *
      * @param $buchungsnummer
      * @param $zaehler
      * @param Zend_Db_Table $tabelleProduktbuchung
      * @return array
      */
     protected function ermittlungProdukteEinerBuchung($buchungsnummer, $zaehler,Application_Model_DbTable_produktbuchung $tabelleProduktbuchung)
     {
         $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsnummer;
         $whereZaehler = "zaehler = ".$zaehler;

         $select = $tabelleProduktbuchung->select();
         $select
             ->where($whereBuchungsnummer)
             ->where($whereZaehler)
             ->order("teilrechnungen_id asc");

         $query = $select->__toString();

         $rows = $tabelleProduktbuchung->fetchAll($select)->toArray();

         return $rows;
     }

     /**
      * @return array
      */
     public function getProdukteEinerBuchung()
     {
         return $this->produktbuchungen;
     }
 }
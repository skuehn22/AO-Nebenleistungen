<?php
/**
 * Ermittelt den Buchungshinweis , Zusatzinformation , den Gruppenname sowie die Personenanzahl einer Buchung
 *
 * + Gruppenname
 * + Zusatzinformation Buchungsinformation
 * + Anzahl Personen männlich maennlich Mädchen Schülerin Schülerin
 * + Anzahl Personen weiblich Jungen weiblich Schüler
 * + Anzahl Begleitpersonen Lehrer Erwachsene
 * + Anzahl Sicherstellung Kraftfahrer Busfahrer driver
 *
 * @author Stephan Krauss
 * @date 05.06.2014
 * @file BuchungsHinweisGruppe.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_BuchungsHinweisGruppe
 {
     protected $buchungsnummer = null;
     protected $zaehler = null;

     protected $pimple = null;

     protected $buchungshinweisGruppe = array();

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_BuchungsHinweisGruppe
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $pimple = $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolliert das vorhandensein von 'tabelleBuchungsnummer'
      *
      * @param Pimple_Pimple $pimple
      * @return Pimple_Pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         if(!$pimple->offsetExists('tabelleBuchungsnummer'))
             throw new nook_Exception('Tabelle tabelleBuchungsnummer fehlt');

         return $pimple;
     }


     /**
      * @param $buchungsnummer
      * @return Front_Model_BuchungsHinweisGruppe
      * @throws nook_Exception
      */
     public function setBuchungsNummer($buchungsnummer){
         $buchungsnummer = (int) $buchungsnummer;
         if($buchungsnummer == 0)
             throw new nook_Exception('Buchungsnummer kein Int');

         $this->buchungsnummer = $buchungsnummer;

         return $this;
     }

     /**
      * @param $zaehler
      * @return Front_Model_BuchungsHinweisGruppe
      * @throws nook_Exception
      */
     public function setZaehler($zaehler)
     {
         $zaehler = (int) $zaehler;
         if($zaehler == 0)
             throw new nook_Exception('Zaehler kein Int');

         $this->zaehler = $zaehler;

         return $this;
     }

     /**
      * Steuert die Ermittlung des Gruppenname und der Zusatzinformation der Gruppe
      *
      * @return Front_Model_BuchungsHinweisGruppe
      * @throws Exception
      */
     public function steuerungErmittlungZusatzinformationBuchung()
     {
        try{
            if( (is_null($this->buchungsnummer)) or (is_null($this->zaehler)) )
                throw new nook_Exception("Buchungsnummer oder Zaehler fehlt");

            if(is_null($this->pimple))
                throw new nook_Exception('DIC fehlt');

            $buchungshinweisGruppe = $this->ermittlungZusatzinformationBuchung($this->buchungsnummer, $this->zaehler, $this->pimple['tabelleBuchungsnummer']);
            $this->buchungshinweisGruppe = $buchungshinweisGruppe;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
     }

     /**
      * Ermittelt die Zusatzinformation einer Gruppe
      *
      * @param $buchungsnummer
      * @param $zaehler
      * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
      * @return array
      * @throws nook_Exception
      */
     protected function ermittlungZusatzinformationBuchung($buchungsnummer, $zaehler,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
     {
         $whereBuchungsnummer = "id = ".$buchungsnummer;
         $whereZaehler = "zaehler = ".$zaehler;

         $cols = array(
             'gruppenname',
             'buchungshinweis',
             'maennlichSchueler',
             'weiblichSchueler',
             'maennlichLehrer',
             'weiblichLehrer',
             'sicherstellung'
         );

         $select = $tabelleBuchungsnummer->select();
         $select
             ->from($tabelleBuchungsnummer, $cols)
             ->where($whereBuchungsnummer)
             ->where($whereZaehler);

         $query = $select->__toString();

         $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

         if(count($rows) <> 1)
             throw new nook_Exception('Anzahl der Buchungsdatensaetze falsch');

         return $rows;
     }

     /**
      * @return array
      */
     public function getZusatzinformationBuchung()
     {
         return $this->buchungshinweisGruppe;
     }
 } 
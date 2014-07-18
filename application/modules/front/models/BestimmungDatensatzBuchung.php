<?php
/**
 * Bestimmung des Datensatzes der 'tbl_buchungsnummer' mit der SessionId. Buchungsnummer HobNummer Zaehler
 *
 * @author Stephan Krauss
 * @date 04.04.2014
 * @file BestimmungDatensatzBuchung.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_BestimmungDatensatzBuchung
 {
     protected $pimple = null;

     protected $hobNummer;
     protected $zaehler;
     protected $buchungsnummer;

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_BestimmungHobNummer
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Überprüft den Inhalt des Container
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         $kontrolle = array(
             0 => array(
                 'name' => 'tabelleBuchungsnummer',
                 'typ' => 'object',
                 'inhalt' => 'Application_Model_DbTable_buchungsnummer'
             ),
             1 => array(
                 'name' => 'sessionId',
                 'typ' => 'string'
             )
         );

         for($i=0; $i < count($kontrolle); $i++){
            if(!$pimple->offsetExists($kontrolle[$i]['name']))
                throw new nook_Exception('Element '.$kontrolle[$i]['name'].' nicht im Container');

            if($kontrolle[$i]['typ'] == 'object')
                if(!$pimple[$kontrolle[$i]['name']] instanceof $kontrolle[$i]['inhalt'])
                    throw new nook_Exception('Object'.$kontrolle[$i]['name'].' nicht im Container');
         }

         return;
     }

     /**
      * Steuert die Ermittlung der HOB Nummer mittels Session ID
      *
      * @return Front_Model_BestimmungHobNummer
      * @throws Exception
      */
     public function steuerungErmittlungWerteBuchung()
     {
         try{
            if(is_null($this->pimple))
                throw new nook_Exception('Container fehlt');


            $werteBuchungTabelleBuchungsnummer = $this->ermittlungWerteBuchungMitSession($this->pimple['sessionId'], $this->pimple['tabelleBuchungsnummer']);
            $this->hobNummer = $werteBuchungTabelleBuchungsnummer['hobNummer'];
            $this->zaehler = $werteBuchungTabelleBuchungsnummer['zaehler'];
            $this->buchungsnummer = $werteBuchungTabelleBuchungsnummer['id'];

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt die Wete einer Buchung aus 'tbl_buchungsnummer' mit der Session ID
      *
      * + gibt Datensatz Tabelle 'tbl_buchungsnummer' zurueck
      *
      * @param $sessionId
      * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
      * @return array
      */
     protected function ermittlungWerteBuchungMitSession($sessionId, Zend_Db_Table_Abstract $tabelleBuchungsnummer)
     {
         $select = $tabelleBuchungsnummer->select();
         $select
             ->where("session_id = '".$sessionId."'");

         $query = $select->__toString();

         $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

         if(count($rows) == 0)
             return 0;
         else
             return $rows[0];
     }

     /**
      * @return int
      */
     public function getHobNummer()
     {
         return $this->hobNummer;
     }

     /**
      * @return int
      */
     public function getZaehler()
     {
         return $this->zaehler;
     }

     /**
      * @return int
      */
     public function getBuchungsnummer()
     {
         return $this->buchungsnummer;
     }
 }

/************/
//include_once('../../../../autoload_cts.php');
//
//$pimple = new Pimple_Pimple();
//
//$pimple['tabelleBuchungsnummer'] = function(){
//    return new Application_Model_DbTable_buchungsnummer();
//};
//
//$pimple['sessionId'] = '62im5d8j01dptt9lqf95spmma1';
//
//$frontModelBestimmungHobNummer = new Front_Model_BestimmungHobNummer();
//$hobNummer = $frontModelBestimmungHobNummer
//    ->setPimple($pimple)
//    ->steuerungErmittlungHobNummer()
//    ->getHobNummer();
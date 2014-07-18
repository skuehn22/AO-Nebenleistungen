<?php
/**
 * Bestimmt die Hauptvariante eines Warenkorbes zur Darstellung des Popup im Logout
 *
 * @author Stephan Krauss
 * @date 03.04.2014
 * @file VarianteLogout.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_VarianteLogout
 {
     protected $pimple = null;

     protected $condition_status_warenkorb = array(
         '0' => 'status_kein_warenkorb_aktiv',
         '1' => 'status_neuer_warenkorb',
         '2' => 'status_warenkorb_vormerkung',
         '4' => 'status_warenkorb_bestandsbuchung'
     );

     protected $condition_status_aktiver_warenkorb = 1;
     protected $condition_status_warenkorb_vormerkung = 2;

     protected $condition_zaehler_aktueller_warenkorb = 0;

     protected $textLogoutPopup = null;
     protected $typWarenkorb = 0;

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_VarianteLogout
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolliert den Inhalt des Pimple Container
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         $kontrolle = array(
             0 => array(
                 'name' => 'tabelleProgrammbuchung',
                 'typ' => 'object',
                 'inhalt' => 'Application_Model_DbTable_programmbuchung'
             ),
             1 => array(
                 'name' => 'tabelleHotelbuchung',
                 'typ' => 'object',
                 'inhalt' => 'Application_Model_DbTable_hotelbuchung'
             ),
             2 => array(
                 'name' => 'tabelleBuchungsnummer',
                 'typ' => 'object',
                 'inhalt' => 'Application_Model_DbTable_buchungsnummer'
             ),
             3 => array(
                 'name' => 'hobNummer'
             ),
             4 => array(
                 'name' => 'buchungsnummer'
             ),
             5 => array(
                 'name' => 'sessionId'
             )
         );

         for($i=0; $i < count($kontrolle); $i++){

             if(!$pimple->offsetExists($kontrolle[$i]['name']))
                 throw new nook_Exception('Pimple: '.$kontrolle[$i]['name']." fehlt");

             if($kontrolle[$i]['typ'] == 'object'){
                 if(!$pimple[$kontrolle[$i]['name']] instanceof $kontrolle[$i]['inhalt'])
                     throw new nook_Exception($kontrolle[$i]['name']." falsches Objekt");
             }
         }

         return;
     }

     /**
      * Steuerung Ermittlung der Buchungsvariante eines Warenkorbes
      *
      * @return Front_Model_VarianteLogout
      * @throws Exception
      */
     public function steuerungErmittlungTypBuchungImWarenkorb()
     {
         try{
             if(is_null($this->pimple))
                 throw new nook_Exception('Pimple fehlt');

             // Vormerkung
             $flagVormerkung = $this->erkennenVormerkung($this->pimple['tabelleBuchungsnummer'], $this->pimple['hobNummer'], $this->pimple['buchungsnummer']);

             if($flagVormerkung === true){
                 $this->typWarenkorb = 2;
             }
             else{
                 // Programmbuchungen
                 $statusProgrammbuchungen = $this->bestimmenMaxStatusBuchungstabelle($this->pimple['tabelleProgrammbuchung'], $this->pimple['hobNummer'], $this->pimple['buchungsnummer']);
                 $this->typWarenkorb = $statusProgrammbuchungen;


                 // Hotelbuchungen
                 $statusHotelbuchungen = $this->bestimmenMaxStatusBuchungstabelle($this->pimple['tabelleHotelbuchung'], $this->pimple['hobNummer'], $this->pimple['buchungsnummer']);
                 if($this->typWarenkorb < $statusHotelbuchungen)
                     $this->typWarenkorb = $statusHotelbuchungen;
             }

             // Text der Meldung Logout
             $this->textLogoutPopup = $this->ermittelnTextLogout($this->typWarenkorb);

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Test ob der Warenkorb eine Vormerkung ist
      *
      * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
      * @param $hobNummer
      * @param $buchungsnummer
      */
     protected function erkennenVormerkung(Zend_Db_Table_Abstract $tabelleBuchungsnummer, $hobNummer, $buchungsnummer)
     {
         $flagVormerkung = false;

         // ist der aktive Warenkorb ein neuer Warenkorb ?
         $row = $tabelleBuchungsnummer->find($buchungsnummer)->toArray();

         if( ($row[0]['zaehler'] != $this->condition_zaehler_aktueller_warenkorb) or ($row[0]['status'] != $this->condition_status_aktiver_warenkorb) )
             return $flagVormerkung;

         // entstand der aktive Warenkorb aus einer Vormerkung ?
         $cols = array(
             "count(id) as anzahl"
         );

         $select = $tabelleBuchungsnummer->select();
         $select
             ->from($tabelleBuchungsnummer, $cols)
             ->where("hobNummer = ".$hobNummer)
             ->where("zaehler = ".$this->condition_zaehler_aktueller_warenkorb)
             ->where("status = ".$this->condition_status_warenkorb_vormerkung);

         $query = $select->__toString();

         $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

         if($rows[0]['anzahl'] == 1){
             $flagVormerkung = true;

             return $flagVormerkung;
         }

         return $flagVormerkung;
     }

     /**
      * Ermittelt den Text des Logout in Abhängigkeit des Status des aktuellen Warenkorbes und der Anzeigesprache
      *
      * + Logout aktueller Warenkorb
      * + Logout Varianten
      * + entsprechend der Anzeigesprache
      *
      * @param $statusWarenkorb
      * @return string
      */
     protected function ermittelnTextLogout($statusWarenkorb)
     {
         $platzhalterTranslate = $this->condition_status_warenkorb[$statusWarenkorb];
         $textLogoutPopup = translate($platzhalterTranslate);

         return $textLogoutPopup;
     }

     /**
      * Bestimmt den maximalen Status eines Artikel im Warenkorb. In den Buchungstabellen
      *
      * @param Zend_Db_Table_Abstract $buchungsTabelle
      * @param array $whereBedingungen
      * @return mixed
      */
     protected function bestimmenMaxStatusBuchungstabelle(Zend_Db_Table_Abstract $tabelleBuchung, $hobNummer, $buchungsNummer)
     {
         $cols = array(
            'buchungsnummer_id',
            'zaehler',
            'status'
         );

         $select = $tabelleBuchung->select();
         $select->from($tabelleBuchung, $cols);

         if($hobNummer > 0){
             $select->where("hobNummer = ".$hobNummer);

             $query = $select->__toString();
             $rows = $tabelleBuchung->fetchAll($select)->toArray();

             $typWarenkorb = $this->auswertenWarenkorbMatrix($rows, $buchungsNummer);
             return $typWarenkorb;
         }
         else{
             $select->where("buchungsnummer_id = ".$buchungsNummer);
             $select->order('status')->order("zaehler");

             $query = $select->__toString();
             $rows = $tabelleBuchung->fetchAll($select)->toArray();

             if(count($rows) > 0)
                 return 1;
             else
                 return 0;
         }
     }

     protected function auswertenWarenkorbMatrix(array $warenkorbMatrix, $buchungsNummer)
     {
         // kein Warenkorb vorhanden
         if(count($warenkorbMatrix) == 0)
             return 0;



         // Vormerkung


         $zaehler = 0;
         $status = 0;
         $checkArtikelImWarenkorb = false;
         foreach($warenkorbMatrix as $zeile){

             if($zeile['zaehler'] > $zaehler)
                 $zaehler = $zeile['zaehler'];

             if($zeile['status'] > $status)
                 $status = $zeile['status'];

             if($zeile['buchungsnummer_id'] == $buchungsNummer)
                 $checkArtikelImWarenkorb = true;
         }

         // keine Artikel im Warenkorb
         if($checkArtikelImWarenkorb === false)
             return 0;
         // Bestandsbuchung
         if($zaehler > 0)
             return 4;
         // neuer Warenkorb
         elseif($zaehler == 0 and $status == 1)
             return 1;
     }

     /**
      * @return string
      */
     public function getTextLogoutPopup()
     {
         return $this->textLogoutPopup;
     }

     /**
      * @return int
      */
     public function getTypWarenkorb()
     {
         return $this->typWarenkorb;
     }


 }

/************/

//include_once('../../../../autoload_cts.php');
//include_once('../../../../library/nook/templateExtensions.php');
//
//$pimple = new Pimple_Pimple();
//
//$pimple['tabelleProgrammbuchung'] = function(){
//    return new Application_Model_DbTable_programmbuchung();
//};
//
//$pimple['tabelleHotelbuchung'] = function(){
//    return new Application_Model_DbTable_hotelbuchung();
//};
//
//$pimple['tabelleBuchungsnummer'] = function(){
//    return new Application_Model_DbTable_buchungsnummer();
//};
//
//$pimple['buchungsnummer'] = 1749;
//$pimple['sessionId'] = '3rh8d851qovi3m8bm2sd85pkf7';
//$pimple['hobNummer'] = 305;
//
//$frontModelVarianteLogout = new Front_Model_VarianteLogout();
//$textpopup = $frontModelVarianteLogout
//    ->setPimple($pimple)
//    ->steuerungErmittlungTypBuchungImWarenkorb()
//    ->getTextLogoutPopup();
//
//$test = 123;
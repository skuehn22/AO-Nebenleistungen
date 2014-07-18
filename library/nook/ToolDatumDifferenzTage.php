<?php
/**
 * Ermittelt die Differenz zwischen 2 Datumsangaben
 *
 * + Datumsangaben in ISO 8601
 * + Kontrolle des Datumsformat ISO 8601
 *
 * @author Stephan Krauss
 * @date 26.05.2014
 * @file ToolDatumDifferenzTage.php
 * @project HOB
 * @package tool
 */
 class nook_ToolDatumDifferenzTage
 {
     protected $startDatum = null;
     protected $endDatum = null;

     protected $tageDifferenz = null;
     protected $differenzTageRelative = true;

     /**
      * @param $startdatum
      * @return nook_ToolDatumDifferenzTage
      */
     public function setStartDatum($startdatum)
     {
         $this->validateDate($startdatum);
         $this->startDatum = $startdatum;

         return $this;
     }

     /**
      * @param $endDatum
      * @return nook_ToolDatumDifferenzTage
      */
     public function setEndDatum($endDatum)
     {
         $this->validateDate($endDatum);
         $this->endDatum = $endDatum;

         return $this;
     }

     /**
      * Steuert die Ermittlung der Datumsdifferenz
      *
      * + alle Termine nach ISO 8601
      *
      * @return nook_ToolDatumDifferenzTage
      * @throws Exception
      */
     public function steuerungErmittlungDifferenzTage()
     {
         try{
             if( (is_null($this->startDatum)) or (is_null($this->endDatum)) )
                 throw new nook_Exception('Start oder Enddatum fehlt');

             $tageDifferenz = $this->differenzTage($this->startDatum, $this->endDatum);
             $this->tageDifferenz = $tageDifferenz;

             return $this;
         }
         catch(Exception $e)
         {
             throw $e;
         }
     }

     /**
      * @return int
      */
     public function getTageDifferenz()
     {
         return $this->tageDifferenz;
     }

     /**
      * Gibt die relative Tagesdifferenz zurück
      *
      * + true == Enddatum größer Startdatum
      * + false == Startdatum > Enddatum
      *
      * @return bool
      */
     public function getDifferenzTageRelative()
     {
         return $this->differenzTageRelative;
     }

     /**
      * Berechnet die Datumsdifferenz in Tage
      *
      * @param $startDatum
      * @param $endDatum
      * @return number
      */
     protected function differenzTage($startDatum, $endDatum)
     {
         $startTime = new DateTime($startDatum);
         $endTime = new DateTime($endDatum);
         $datumsDifferenz = $startTime->diff($endTime);

         $tageDifferenz = $datumsDifferenz->format('%R%d');

         if($tageDifferenz < 0)
             $this->differenzTageRelative = false;

         return abs($tageDifferenz);
     }

     /**
      * Überprüft das Datumsformat
      *
      * + Es muss ISO 8601 sein
      *
      * @param $date
      * @throws nook_Exception
      */
     protected function validateDate($date)
     {
         if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) == true) {
             return;
         }
         else {
             throw new nook_Exception('Datum ist nicht ISO 8601');
         }
     }
 }


/***************/
//include_once('../../autoload_cts.php');
//
//$toolDatumDifferenzTage = new nook_ToolDatumDifferenzTage();
//$tageDifferenz = $toolDatumDifferenzTage
//    ->setStartDatum('2014-05-29')
//    ->setEndDatum('2014-06-01')
//    ->steuerungErmittlungDifferenzTage()
//    ->getTageDifferenz();
//
//$test = 123;
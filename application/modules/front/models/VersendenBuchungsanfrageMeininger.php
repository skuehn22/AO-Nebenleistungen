<?php
/**
 * Versendet die Buchungsanfrage an Meininger
 *
 * @author Stephan Krauss
 * @date 12.06.2014
 * @file Front_Model_VersendenBuchungsanfrageMeininger.php
 * @project HOB
 * @package front | admin | plugin | tabelle | tool
 * @subpackage controller | model | shadow | interface
 */
 
 class Front_Model_VersendenBuchungsanfrageMeininger
 {
     protected $ipMeininger = null;
     protected $portMeininger = null;
     protected $urlErweiterungMeininger = null;

     protected $xmlBuchungsanfrage = null;

     protected $flagUbermittlung = false;

     protected $pimple = null;

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $pimple = $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolle DIC
      *
      * @param Pimple_Pimple $pimple
      * @return Pimple_Pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
        if(!$pimple->offsetExists('tabelleSchnittstelleMeininger'))
            throw new nook_Exception('Tabelle tbl_schnittstelle_meininger fehlt');

         return $pimple;
     }

     /**
      * @param $ipMeininger
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      */
     public function setIp($ipMeininger)
     {
        $this->ipMeininger = $ipMeininger;

        return $this;
     }

     /**
      * @param $portMeininger
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      */
     public function setPort($portMeininger)
     {
         $this->portMeininger = $portMeininger;

         return $this;
     }

     /**
      * @param $urlErweiterungMeininger
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      */
     public function setUrlErweiterungMeininger($urlErweiterungMeininger)
     {
         $this->urlErweiterungMeininger = $urlErweiterungMeininger;

         return $this;
     }

     /**
      * @param $xmlBookingMeininger
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      */
     public function  setXmlBuchungsanfrage($xmlBookingMeininger)
     {
         $this->xmlBuchungsanfrage = $xmlBookingMeininger;

         return $this;
     }

     /**
      * Steuert die Übermittlung der Buchungsanfrage an Meininger
      *
      * @return Front_Model_VersendenBuchungsanfrageMeininger
      * @throws Exception
      */
     public function steuerungBuchungsanfrage()
     {
         try{
             if( (is_null($this->portMeininger)) or (is_null($this->ipMeininger)) or (is_null($this->urlErweiterungMeininger)) )
                 throw new nook_Exception('Informationen Meininger fehlen');

             if(is_null($this->xmlBuchungsanfrage))
                 throw new nook_Exception('XML Buchungsanfrage fehlt');

             // versenden Buchungsanfrage
             $responseXml = $this->versendenBuchungsanfrage($this->ipMeininger, $this->portMeininger, $this->urlErweiterungMeininger, $this->xmlBuchungsanfrage);

             // Auswertung Response
             $this->flagUbermittlung = $this->auswertungResponseMeininger($responseXml);

             // Registrierung Übergabe in 'tbl_schnittstelle_meininger'
             $this->registrierungUebergabeBuchungAnMeininger($responseXml, $this->xmlBuchungsanfrage, $this->flagUbermittlung);

             return $this;
         }
         catch(Exception $e){
            throw $e;
         }
     }

     /**
      * Versendet die Buchungsanfrage an Meininger
      *
      * @param $ipMeininger
      * @param $portMeininger
      * @param $urlErweiterungMeininger
      * @param $xmlBuchungsanfrage
      * @return bool
      */
     protected function versendenBuchungsanfrage($ipMeininger, $portMeininger, $urlErweiterungMeininger, $xmlBuchungsanfrage){

         $url = "http://".$ipMeininger.":".$portMeininger."/".$urlErweiterungMeininger;

         //open connection
         $curlHandler = curl_init($url);
         curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "POST");
         curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($curlHandler,CURLOPT_POSTFIELDS, $xmlBuchungsanfrage);

         //execute XML
         $responseXml = curl_exec($curlHandler);

         //close connection
         curl_close($curlHandler);

         return $responseXml;
     }

     /**
      * Wertet den Response des Server Meininger aus
      *
      * @param $responseXml
      * @return bool
      */
     protected function auswertungResponseMeininger($responseXml)
     {
         $p = xml_parser_create();
         xml_parse_into_struct($p, $responseXml, $vals, $index);
         xml_parser_free($p);

         if( (array_key_exists('SUCCESS', $index)) and ($index['SUCCESS'][0] == 1) )
             $this->flagUbermittlung = true;

         return $this->flagUbermittlung;
     }

     protected function registrierungUebergabeBuchungAnMeininger($responseXml, $xmlBuchungsanfrage, $flagUbermittlung)
     {
         $insertCols = array(
             'response' => $responseXml,
             'buchungsanfrage' => $xmlBuchungsanfrage,
             'uebermittlung' => $flagUbermittlung
         );

         /** @var $tablleSchnittstelleMeininger Zend_Db_Table */
         $tablleSchnittstelleMeininger = $this->pimple['tabelleSchnittstelleMeininger'];
         $idTabelle = $tablleSchnittstelleMeininger->insert($insertCols);

         if($idTabelle == 0)
             throw new nook_Exception('Eintrag in tbl_schnittstelle_meininger fehlgeschlagen');

         return;
     }

     /**
      * @return bool
      */
     public function getFlagUbermittlung()
    {
        return $this->flagUbermittlung;
    }
 }
<?php
/**
 * Fragt die Verfuegbarkeit von Hotels der Hotelkette Meininger ab und trägt diese in die Tabellen ein
 *
 * + fragt nach der verfügbarkeit in einer Stadt
 * + fragt nach der Verfügbarkeit in einem Hotel
 *
 * @author Stephan Krauss
 * @date 30.05.2014
 * @file MeiningerVerfuegbarkeit.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_MeiningerVerfuegbarkeit
 {
     /** @var $pimpleObj Pimple */
     protected $pimpleObj = null;
     protected $flagVerbindungZumServerMeininger = false;

     /**
      * Container
      *
      * @param Pimple $pimpleObj
      * @return Front_Model_MeiningerVerfuegbarkeit
      */
     public function setPimple(Pimple_Pimple $pimpleObj)
     {
         $pimpleObj = $this->checkPimple($pimpleObj);

         $this->pimpleObj = $pimpleObj;

         return $this;
     }

     /**
      * @return Pimple
      */
     public function getPimple()
     {
         return $this->pimpleObj;
     }

     /**
      * War die Verbindung zum Server Meininger erfolgreich ?
      *
      * @return bool
      */
     public function getFlagVerbindungServermeininger()
     {
         return $this->flagVerbindungZumServerMeininger;
     }

     /**
      * Kontrolle der Objekte und Daten
      *
      * @param Pimple $pimple
      * @return Pimple
      * @throws Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         $kontrollePimple = array(
             'convertDataObj' => 'object',
             'serveranfrageObj' => 'object',
             'eintragenVerfuegbarkeitObj' => 'object',
             'urlErweiterung' => 'array',
             'hotelsMeininger' => 'array',
             'skey' => true,
             'meiningerIp' => true,
             'anreiseDatum' => true,
             'abreiseDatum' => true
         );

         foreach($kontrollePimple as $key => $value){
             if(!$pimple->offsetExists($key))
                 throw new Exception("Element Pimple: ".$key." nicht vorhanden");
         }

         return $pimple;
     }

     /**
      * Steuert die Ermittlung der Verfügbarkeiten der Hotels
      *
      * @return Front_Model_MeiningerVerfuegbarkeit
      */
     public function steuerungAvaibilityHotels()
     {
         try{
             // Datumsvergleich
             $dateTimestampAnreise = strtotime($this->pimpleObj['anreiseDatum']);
             $dateTimestampAbreise = strtotime($this->pimpleObj['abreiseDatum']);

             if($dateTimestampAnreise >= $dateTimestampAbreise)
                 throw new Exception('Datumsvergleich fehlgeschlagen');

             // Verfügbarkeiten der Hotels
             $kontrolleVerbindungServerMeininger = $this->abfragenRatenHotels();

             // fehlerhafte Verbindung zum Server Meininger
             if($kontrolleVerbindungServerMeininger === false){
                 $this->flagVerbindungZumServerMeininger = $kontrolleVerbindungServerMeininger;

                 return $this;
             }
             // Verbindung zum Server Meininger erfolgreich
             else
                 $this->flagVerbindungZumServerMeininger = $kontrolleVerbindungServerMeininger;

             // eintragen Verfügbarkeiten Hotel
             $this->eintragenVerfuegbarkeitenHotel($this->pimpleObj['verfuegbarkeitenHotels']);

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * @param $sparrow
      * @param $dataResponseVerfuegbarkeitHotel
      */
     protected function eintragenVerfuegbarkeitenHotel(array $verfuegbarkeitenHotels)
     {
         /** @var $frontModelMeiningerEintragenVerfuegbarkeit Front_Model_MeiningerEintragenVerfuegbarkeit */
         $frontModelMeiningerEintragenVerfuegbarkeit = $this->pimpleObj['eintragenVerfuegbarkeitObj'];

         $frontModelMeiningerEintragenVerfuegbarkeit
             ->setVerfuegbarkeitenHotels($verfuegbarkeitenHotels)
             ->setVereinbarteRaten($this->pimpleObj['vereinbarteRaten'])
             ->setPimple($this->pimpleObj)
             ->steuerungEintragenVerfuegbarkeiten();

         return;
     }

     /**
      * Holt die Verfügbarkeiten des Hotel
      *
      * @param $xmlAnfrageVerfuegbarkeit
      * @param $serverIp
      * @param $serverport
      * @return String
      */
     protected function verfuegbarkeitHotel($xmlAnfrageVerfuegbarkeit, $serverIp, $serverPort)
     {
         /** @var $toolMeiningerServeranfrage nook_ToolMeiningerServeranfrage */
         $toolMeiningerServeranfrage = $this->pimpleObj['serveranfrageObj'];

         $dataResponseVerfuegbarkeitHotel = $toolMeiningerServeranfrage
             ->setServerIp($serverIp)
             ->setServerPort($serverPort)
             ->setConvertDataObj($this->pimpleObj['convertDataObj'])
             ->setRequestXml($xmlAnfrageVerfuegbarkeit)
             ->setUrlErweiterungen($this->pimpleObj['urlErweiterung'])
             ->steuerungHolenResponse()
             ->getResponseData();

         return $dataResponseVerfuegbarkeitHotel;
     }

     /**
      * Kontrolle IP
      *
      * @param $serverIp
      * @throws Exception
      */
     protected function checkIp($serverIp)
     {
         if(!filter_var($serverIp, FILTER_VALIDATE_IP) === true)
             throw new Exception('IP Adresse falsch');

         return;
     }

     /**
      * Kontrolliert das Datum auf ISO 8601
      *
      * @param $date
      * @throws Exception
      */
     protected function convertDate($date)
     {
         if (false === strtotime($date))
             throw new Exception('Datum falsch');

         return;
     }

     /**
      * Erstellt die XML der Anfrage 'availability' für ein Hotel
      *
      * @param $propertyId
      */
     protected function erstellenXmlVerfuegbarkeitEinesHotels($propertyId)
     {
         $xmlAnfrageVerfuegbarkeit = array(
             'fields' => array(
                 'skey' => $this->pimpleObj['skey'],
                 'arrival' => $this->pimpleObj['anreiseDatum'],
                 'departure' => $this->pimpleObj['abreiseDatum']
             )
         );

         $convertDataObj = $this->pimpleObj['convertDataObj'];

         // Array to xml
         $convertDataObj->version = '1.0';

         $xmlVerfuegbarkeitEinesHotels = $convertDataObj->convertArrayToXML($xmlAnfrageVerfuegbarkeit);
         if($convertDataObj->getFlagErrors() === true)
             throw new Exception('erstellen XMl Anfrage Verfuegbarkeit Hotels');

         return $xmlVerfuegbarkeitEinesHotels;
     }

     /**
      * steuert die Ermittlung der Verfügbarkeiten der Hotels
      */
     protected function abfragenRatenHotels()
     {
         $verfuegbarkeitenHotels = array();
         $hotelsMeininger = $this->pimpleObj['hotelsMeininger'];

         // Abfrage mit propertyId
         if ($this->pimpleObj->offsetExists('propertyId')) {

             $propertyId = $this->pimpleObj['propertyId'];

             $xmlAnfrageVerfuegbarkeit = $this->erstellenXmlVerfuegbarkeitEinesHotels($propertyId);

             $propertyItems = $hotelsMeininger[$propertyId];

             $dataResponseVerfuegbarkeitHotel = $this->verfuegbarkeitHotel($xmlAnfrageVerfuegbarkeit, $this->pimpleObj['meiningerIp'], $this->hotelsMeininger[$propertyId]['port']);
             if($dataResponseVerfuegbarkeitHotel === false)
                 return false;

             $dataResponseVerfuegbarkeitHotel['cityId'] = $propertyItems['cityId'];
             $dataResponseVerfuegbarkeitHotel['propertyCode'] = $propertyItems['code'];
             $dataResponseVerfuegbarkeitHotel['propertyId'] = $this->propertyId;

             $verfuegbarkeitenHotels[] = $dataResponseVerfuegbarkeitHotel;
         }
         // Abfrage mit cityId
         else{
             foreach ($hotelsMeininger as $propertyId => $propertyItems) {

                 $cityId = $this->pimpleObj['cityId'];

                 if ($propertyItems['cityId'] == $cityId) {
                     $xmlAnfrageVerfuegbarkeit = $this->erstellenXmlVerfuegbarkeitEinesHotels($propertyId);

                     $dataResponseVerfuegbarkeitHotel = $this->verfuegbarkeitHotel($xmlAnfrageVerfuegbarkeit, $this->pimpleObj['meiningerIp'], $propertyItems['port']);
                     if($dataResponseVerfuegbarkeitHotel === false)
                         return false;

                     $dataResponseVerfuegbarkeitHotel['cityId'] = $propertyItems['cityId'];
                     $dataResponseVerfuegbarkeitHotel['propertyCode'] = $propertyItems['code'];
                     $dataResponseVerfuegbarkeitHotel['propertyId'] = $propertyId;

                     $verfuegbarkeitenHotels[] = $dataResponseVerfuegbarkeitHotel;
                 }
             }
         }

         $this->pimpleObj['verfuegbarkeitenHotels'] = $verfuegbarkeitenHotels;

         return true;
     }
 } 
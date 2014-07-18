<?php
/**
 * Eintragen der Verfügbarkeit Hotels meininger in die Tabellen
 *
 * @author Stephan Krauss
 * @date 30.05.2014
 * @file MeiningerEintragenVerfuegbarkeit.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_MeiningerEintragenVerfuegbarkeit
 {
     protected $pimpleObj = null;

     protected $verfuegbarkeitenHotels = null;
     protected $vereinbarteRaten = null;

     protected $condition_code_einzelzimmer = 'Y.SGL';
     protected $condition_code_doppelzimmer = 'Y.TWN';
     protected $condition_code_mehrbettzimmer = 'Y.MUL';
     protected $condition_rate_ist_verfuegbar = 1;
     protected $condition_rate_ist_aktiv = 3;

     protected $condition_anreise_erlaubt = 1;
     protected $condition_abreise_erlaubt = 1;

     protected $condition_buchbar_von = 0;
     protected $condition_buchbar_bis = 0;

     protected $condition_preise_sind_personenpreise = true;

     protected $verteilerSchluesselZimmer = array(
         'Y.SGL' => 5,
         'Y.TWN' => 5,
         'Y.MUL' => 90
     );

     protected $verteilerSchluesselMehrbettZimmer = array(
         'AM' => 4,
         'OS' => 8,
         'SP' => 6,
         'WP' => 4,
         'BC' => 6,
         'EA' => 6,
         'GA' => 8,
         'LS' => 6,
         'FS' => 6,
         'CG' => 6,
         'RS' => 6,
         'SG' => 8,
         'ES' => 6
     );

     /**
      * @param array $verfuegbarkeitenHotels
      * @return Front_Model_MeiningerEintragenVerfuegbarkeit
      */
     public function setVerfuegbarkeitenHotels(array $verfuegbarkeitenHotels)
     {
         $this->verfuegbarkeitenHotels = $verfuegbarkeitenHotels;

         return $this;
     }

     /**
      * Array der vereinbarten Raten
      *
      * @param array $vereinbarteRaten
      * @return Front_Model_MeiningerEintragenVerfuegbarkeit
      */
     public function setVereinbarteRaten(array $vereinbarteRaten)
     {
         $this->vereinbarteRaten = $vereinbarteRaten;

         return $this;
     }

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_MeiningerEintragenVerfuegbarkeit
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimpleObj = $pimple;

         return $this;
     }

     /**
      * Überprüft DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         $kontrollePimple = array(
             'vereinbarteRaten',
             'tabelleOtaPrices',
             'tabelleOtaRatesAvailability'
         );

         foreach($kontrollePimple as $key => $value){
             if(!$pimple->offsetExists($value))
                 throw new nook_Exception("Im Pimple fehlt: ".$key);
         }

         return;
     }

     /**
      * Steuert das eintragen der verfuegbarkeiten der Hotels Meininger
      *
      * @return Front_Model_MeiningerEintragenVerfuegbarkeit
      * @throws Exception
      */
     public function steuerungEintragenVerfuegbarkeiten()
     {
         try{
             if(is_null($this->pimpleObj))
                 throw new nook_Exception("Pimple DIC fehlt");

             if( (is_null($this->verfuegbarkeitenHotels)) or (count($this->verfuegbarkeitenHotels) == 0) )
                 throw new Exception('Verfügbarkeiten Hotels Meininger fehlen');

             // umwandeln XML-Object in Array
             $this->verfuegbarkeitenHotels = $this->umwandelnXmlToArray($this->verfuegbarkeitenHotels);

             // bereitet ankommende XML - Daten auf
             for($i=0; $i < count($this->verfuegbarkeitenHotels); $i++){
                 $verfuegbarkeitEinesHotel =  $this->verfuegbarkeitenHotels[$i];


                 for($j=0; $j < count($verfuegbarkeitEinesHotel['availability']); $j++){
                     $ratenTagesVerfuehbarkeitenEinesHotel = $verfuegbarkeitEinesHotel['availability'][$j];

                     // linearisieren der Daten
                     $aufbereiteteRatenEinesHotels =  $this->linearisierenHotelInformation($verfuegbarkeitEinesHotel['cityId'], $verfuegbarkeitEinesHotel['propertyCode'], $verfuegbarkeitEinesHotel['propertyId'], $ratenTagesVerfuehbarkeitenEinesHotel);

                     // Kontrolle ob Rate des Hotels im System existiert
                     foreach($aufbereiteteRatenEinesHotels as $key => $einzelRateHotel){

                         $kontrolleRateExistiert = $this->checkHotelRate($einzelRateHotel['propertyCode'], $einzelRateHotel['rateCode']);

                         if($kontrolleRateExistiert === false){
                             break;
                         }
                     }

                     if($kontrolleRateExistiert === true){
                         // Umrechnen der freien Betten auf Zimmertyp
                         $aufbereiteteRatenEinesHotels = $this->umrechnenAufZimmertyp($aufbereiteteRatenEinesHotels);

                         // eintragen der Ratenverfügbarkeit eines Hotels
                         $this->eintragenTagesVerfuegbarkeitenEinesHotel($aufbereiteteRatenEinesHotels);
                     }

                 }
             }

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Wandelt ein 'XML-Objekt' in ein Array um
      *
      * @param $verfuegbarkeitenHotels
      * @return array
      */
     protected function umwandelnXmlToArray($verfuegbarkeitenHotels)
     {
         $verfuegbarkeitenHotels = json_decode(json_encode($verfuegbarkeitenHotels), TRUE);

         return $verfuegbarkeitenHotels;
     }

     /**
      * Umrechnen der freien Betten auf die Zimmertypen
      *
      * + Achtung, Umrechnung erfolgt entsprechend Gespräch mit 'Meininger'
      *
      * @param array $aufbereiteteRatenEinesHotels
      */
     protected function umrechnenAufZimmertyp(array $aufbereiteteRatenEinesHotels)
     {

         for($i=0; $i < count($aufbereiteteRatenEinesHotels); $i++){
             // Einzelzimmer
             if( $aufbereiteteRatenEinesHotels[$i]['rateCode'] == $this->condition_code_einzelzimmer ){
                 $bettenEinzelzimmer = floor(($aufbereiteteRatenEinesHotels[$i]['freesale'] / 100) * $this->verteilerSchluesselZimmer[$aufbereiteteRatenEinesHotels[$i]['rateCode']]);
                 $anzahlEinzelZimmer = $bettenEinzelzimmer;

                 $aufbereiteteRatenEinesHotels[$i]['roomlimit'] = $anzahlEinzelZimmer;
             }
             // Doppelzimmer
             elseif( $aufbereiteteRatenEinesHotels[$i]['rateCode'] == $this->condition_code_doppelzimmer ){
                 $bettenDoppelZimmer = floor( ($aufbereiteteRatenEinesHotels[$i]['freesale'] / 100) * $this->verteilerSchluesselZimmer[$aufbereiteteRatenEinesHotels[$i]['rateCode']]);
                 $anzahlDoppelZimmer = floor($bettenDoppelZimmer / 2);

                 $aufbereiteteRatenEinesHotels[$i]['roomlimit'] = $anzahlDoppelZimmer;
             }
             // Anzahl Mehrbettzimmer in Abhängigkeit der Hotels
             elseif( $aufbereiteteRatenEinesHotels[$i]['rateCode'] == $this->condition_code_mehrbettzimmer ){
                 $bettenMehrbettzimmer = floor(($aufbereiteteRatenEinesHotels[$i]['freesale'] / 100) * $this->verteilerSchluesselZimmer[$aufbereiteteRatenEinesHotels[$i]['rateCode']] );
                 $anzahlMehrbettzimmer = floor($bettenMehrbettzimmer / $this->verteilerSchluesselMehrbettZimmer[$this->verfuegbarkeitenHotels[0]['propertyCode']]);

                 $aufbereiteteRatenEinesHotels[$i]['roomlimit'] = $anzahlMehrbettzimmer;
             }
         }

         return $aufbereiteteRatenEinesHotels;
     }

     /**
      * Zerlegt die Rateninformation eines Hotels an einem Datum
      *
      * @param $cityId
      * @param $propertyCode
      * @param $propertyId
      * @param $ratenTagesVerfuehbarkeitenEinesHotel
      * @return array
      */
     protected function linearisierenHotelInformation($cityId, $propertyCode, $propertyId, $ratenTagesVerfuehbarkeitenEinesHotel)
     {
         $aufbereiteteRatenEinesHotelsAnEinemTag = array();

         $produkte = $ratenTagesVerfuehbarkeitenEinesHotel['products'];

         // herausfiltern der Raten und hinzufügen allgemeine Angaben
         for($i= 0; $i < count($produkte['product']); $i++){

             $produkt = $produkte['product'][$i];

             if(in_array($produkt['id'], $this->vereinbarteRaten )){
                 $aufbereiteteRatenEinesHotelsAnEinemTag[] = array(
                     'regdate' => $ratenTagesVerfuehbarkeitenEinesHotel['regdate'],
                     'allotment' => $ratenTagesVerfuehbarkeitenEinesHotel['allotment'],
                     'freesale' => $ratenTagesVerfuehbarkeitenEinesHotel['freesale'],
                     'cityId' => $cityId,
                     'propertyCode' => $propertyCode,
                     'propertyId' => $propertyId,
                     'price' => $produkt['price'],
                     'rateCode' => $produkt['id'],
                     'categoryCode' => $produkt['id']
                 );


                 // entfernen Rate
                 unset($produkte['product'][$i]);
             }
         }

         // hinzufügen der Attribute zu den Raten
         foreach($aufbereiteteRatenEinesHotelsAnEinemTag as $key => $rate){
             foreach($produkte['product'] as $attribut){
                 $attribut = (array) $attribut;

                 $aufbereiteteRatenEinesHotelsAnEinemTag[$key][$attribut['id']] = $attribut['price'];
             }
         }

         return $aufbereiteteRatenEinesHotelsAnEinemTag;
     }



     /**
      * berechnet die fiktiven Zimmer eines Hotel
      *
      * + Zimmeranzahl ermittelt sich aus dem Hotel
      * + von der Gesamtanzahl sind 10% Einzelzimmer
      *
      * @param $freieBetten
      * @return array
      */
     protected function berechnenZimmeranzahl(array $hotel, array $rate, array $tag)
     {
         $verfuegbareZimmer = array();

         return $verfuegbareZimmer;
     }

     /**
      * Sind die Raten des Hotels verfügbar ?
      *
      * @param $sparrowObj
      * @param $verfuegbarkeitEinesHotel
      */
     protected function eintragenTagesVerfuegbarkeitenEinesHotel($verfuegbarkeitEinesHotel)
     {
         // Verfügbarkeit eines Hotels
         for($i=0; $i < count($verfuegbarkeitEinesHotel); $i++){

             $rateAnEinemTag = $verfuegbarkeitEinesHotel[$i];

             // eintragen Verfügbarkeit Rate eines Hotels an einem Tag
             $ratenId = $this->eintragenRateEinesTages($rateAnEinemTag);

             // eintragen Preis Rate eines Hotels an einem Tag
             $this->eintragenPreiseRateEinesTages($rateAnEinemTag, $ratenId);
         }

         return;
     }

     /**
      * Trägt den Preis der Rate eines Hotels für einen Tag ein
      *
      * + löschen des Preises einer Rate eines Hotels an einem Tag
      *
      * @param Sparrow $sparrowObj
      * @param array $hotel
      * @param array $rate
      * @param array $tag
      * @return int
      */
     protected function eintragenPreiseRateEinesTages(array $rateAnEinemTag, $ratenId)
     {
         /** @var $tabelleOtaPrices Zend_Db_Table */
         $tabelleOtaPrices = $this->pimpleObj['tabelleOtaPrices'];

         // löschen alte Preise
         $whereDelete = array(
             "hotel_code = '".$rateAnEinemTag['propertyCode']."'",
             "rates_config_id = '".$ratenId."'",
             "datum = '".$rateAnEinemTag['regdate']."'"
         );

         $anzahl = $tabelleOtaPrices->delete($whereDelete);

         // eintragen neue Preise
         $insertPreis = array(
             'datum' => $rateAnEinemTag['regdate'],
             'hotel_code' => $rateAnEinemTag['propertyCode'],
             'amount' => $rateAnEinemTag['price'],
             'allowed_arrival' => $this->condition_anreise_erlaubt,
             'allowed_departure' => $this->condition_abreise_erlaubt,
             'release_to' => $this->condition_buchbar_von,
             'release_from' => $this->condition_buchbar_bis,
             'rates_config_id' => $ratenId,
             'pricePerPerson' => $this->condition_preise_sind_personenpreise,
             'category_code' => $rateAnEinemTag['rateCode'],
             'rate_code' => $rateAnEinemTag['rateCode']
         );

         $idPreis = $tabelleOtaPrices->insert($insertPreis);

         return;
     }

     /**
      * Trägt die verfügbaren Zimmer der Rate eines Hotels für einen Tag ein
      *
      * @param Sparrow $sparrowObj
      * @param array $rateAnEinemTag
      * @return int
      */
     protected function eintragenRateEinesTages(array $rateAnEinemTag)
     {
         /** @var $tabelleOtaRatesAvailability Zend_Db_Table */
         $tabelleOtaRatesAvailability = $this->pimpleObj['tabelleOtaRatesAvailability'];

         // löschen bestehender Raten des Tages , Verfügbarkeit
         $whereDelete = array(
             "hotel_code = '".$rateAnEinemTag['propertyCode']."'",
             "rate_code = '".$rateAnEinemTag['rateCode']."'",
             "datum = '".$rateAnEinemTag['regdate']."'"
         );

         $anzahl = $tabelleOtaRatesAvailability->delete($whereDelete);

         // eintragen der Verfügbarkeit der Rate eines Hotels an einem Tag

         $insert = array(
             'datum' => $rateAnEinemTag['regdate'],
             'availibility' => $this->condition_rate_ist_verfuegbar,
             'roomlimit' => $rateAnEinemTag['roomlimit'],
             'hotel_code' => $rateAnEinemTag['propertyCode'],
             'category_code' => $rateAnEinemTag['categoryCode'],
             'rate_code' => $rateAnEinemTag['categoryCode'],
             'property_id' => $rateAnEinemTag['propertyId'],
             'aktiv' => $this->condition_rate_ist_aktiv
         );

         // minimaler Aufenthalt
         if($rateAnEinemTag['MLOS'] != '0.00')
             $insert['min_stay'] = abs($rateAnEinemTag['MLOS']);

         // Anreisetag ?
         if( ($rateAnEinemTag['CTA'] == 1) or ($rateAnEinemTag['CTA'] == 12) )
             $insert['arrival'] = 1;

         // Abreisetag
         if( ($rateAnEinemTag['CTD'] == 2) or ($rateAnEinemTag['CTD'] == 12) )
             $insert['departure'] = 1;

         // Raten ID
         $ratenId = $this->ermittelnRatenId($rateAnEinemTag['propertyId'], $rateAnEinemTag['rateCode']);
         $insert['rates_config_id'] = $ratenId;


         // eintragen Verfügbarkeit Rate
         $anzahl = $tabelleOtaRatesAvailability->insert($insert);

         return $ratenId;
     }

     /**
      * Ermittelt die ID der Rate
      *
      * @param Sparrow $sparrowObj
      * @param $rateCode
      * @return mixed
      */
     protected function ermittelnRatenId($propertyId, $rateCode)
     {
        $cols = array(
            'id'
        );

        /** @var $tabelleOtaRatesConfig Zend_Db_Table */
        $tabelleOtaRatesConfig = $this->pimpleObj['tabelleOtaRatesConfig'];
        $select = $tabelleOtaRatesConfig->select();

        $select
            ->from($tabelleOtaRatesConfig, $cols)
            ->where("rate_code = '".$rateCode."'")
            ->where("properties_id = '".$propertyId."'");

        $query = $select->__toString();

        $rows = $tabelleOtaRatesConfig->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze falsch');

        return $rows[0]['id'];
     }

     /**
      * Kontrolliert ob die Rate in einem Hotel vorhanden ist
      *
      * @param array $hotelCode
      * @param array $rateCode
      * @throws Exception
      */
     protected function checkHotelRate($hotelCode, $rateCode)
     {
         try{
             /** @var $tabelleOtaRatesConfig Zend_Db_Table */
             $tabelleOtaRatesConfig = $this->pimpleObj['tabelleOtaRatesConfig'];
             $select = $tabelleOtaRatesConfig->select();

             $select
                 ->where("hotel_code = '".$hotelCode."'")
                 ->where("rate_code = '".$rateCode."'");

             $query = $select->__toString();

             $rows = $tabelleOtaRatesConfig->fetchAll($select)->toArray();

             if (count($rows) <> 1)
                 throw new Exception("Rate nicht in 'tbl_ota_rates_config'");
             else{
                 return true;
             }
         }
         catch(Exception $e){
             $e->kundenId = nook_ToolKundendaten::findKundenId();
             nook_ExceptionRegistration::buildAndRegisterErrorInfos($e, 2);

             return false;
         }
     }
 }
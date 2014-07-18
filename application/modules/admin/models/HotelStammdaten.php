<?php
/**
 * ermittelt und speichert die Stammdaten eines Hotels
 *
 * @author Stephan Krauss
 * @date 09.04.2014
 * @file HotelStammdaten.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
 class Admin_Model_HotelStammdaten
 {
     protected $pimple = null;
     protected $kontrolleUpdate = null;

     protected $hotelStammdaten = null;

     protected $condition_neu_angelegtes_hotel = 'neu';
     protected $condition_keine_ueberbuchung = 1;
     protected $condition_ueberbuchung = 2;

     protected $db_hotels = null;

     /**
      * @param Pimple_Pimple $pimple
      * @return Admin_Model_HotelStammdaten
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolle der Werte im DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         $kontrolle = array(
             'tabelleProperties' => array(
                 'name' => 'tabelleProperties'
             ),
             'tabellePropertiesDays' => array(
                 'name' => 'tabellePropertiesDays'
             ),
             'params' => array(
                 'name' => 'params',
                 'typ' => 'array'
             )
         );

         foreach($kontrolle as $key => $kontrollwerte){
             if(!$pimple->offsetExists($key))
                 throw new nook_Exception('Element nicht im DIC vorhanden');

             if( ($key == 'tabelleProperties') or ($key == 'tabellePropertiesDays') ){
                 if(!$pimple['tabelleProperties'] instanceof Application_Model_DbTable_properties){
                     throw new nook_Exception('Objekt '.$key.' nicht vorhanden');
                 }
             }

             if($key == 'params'){
                 if(!is_array($pimple['params']))
                     throw new nook_Exception('Parameter fehlen');
             }
         }

         return;
     }

     /**
      * Kontrolle der Verwendung des Hotel Code
      *
      * + Ein Hotel Code darf nur einmal verwendet werden
      * + Wird der Hotelcode schon verwendet, dann ist
      *
      * @param $hotelCode
      * @param bool $id
      * @return bool
      */
     public function findDoubleHotelCode($hotelCode, $id = false){
         $flagVerwendung = true;

         if($this->condition_neu_angelegtes_hotel == $hotelCode)
             return $flagVerwendung;

         $cols = array(
             "count(property_code) as anzahl"
         );

         /** @var $tabelleProperties Application_Model_DbTable_properties */
         $tabelleProperties = $this->pimple['tabelleProperties'];

         $select = $tabelleProperties->select();
         $select
             ->from($tabelleProperties, $cols)
             ->where("property_code = '".$hotelCode."'");

         if($id)
             $select->where("id = ".$id);

         $query = $select->__toString();

         $rows = $tabelleProperties->fetchAll($select)->toArray();

         if($rows[0]['anzahl'] > 1)
             $flagVerwendung = false;

         return $flagVerwendung;
     }

     /**
      * Steuert das Update der Stammdaten eines Hotels
      *
      * @return Admin_Model_HotelStammdaten
      * @throws Exception
      */
     public function steuerungUpdateStammdatenHotel(){
         try{
             if(is_null($this->pimple))
                 throw new nook_Exception('DIC fehlt');

             $this->updateStammdatenHotel();
             $this->updateAnAbreisetageHotel();

             $this->kontrolleUpdate = true;

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     public function steuerungErmittlungStammdatenHotel()
     {
         try{
             $this->db_hotels = Zend_Registry::get('hotels');

             $params = $this->pimple['params'];

             // Stammdaten Hotel
             $hotelStammdaten = $this->ermittelnHotelStammdaten($params['id']);

             // An und Abreisetage
             $hotelAnUndAbreisetageArray = $this->ermittelnAnUndAbreisetage($params['id']);
             $hotelAnUndAbreiseCheckbox = $this->checkboxAnUndAbreise($hotelAnUndAbreisetageArray);

             $this->hotelStammdaten = array_merge($hotelStammdaten, $hotelAnUndAbreiseCheckbox);

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * @return array
      */
     public function getHotelStammdaten()
     {
         return $this->hotelStammdaten;
     }

     /**
      * Update der An und Abreisetage eines Hotels
      *
      * + löschen der An und Abreisetage ines Hotels
      *
      * + 1 = kein An und Abreisetag
      * + 2 = Anreise möglich
      * + 3 = Abreise möglich
      * + 4 = An und Abreise möglich
      */
     protected function updateAnAbreisetageHotel()
     {
         $params = $this->pimple['params'];

         $wochentage = array(
             'montag' => 1,
             'dienstag' => 2,
             'mittwoch' => 3,
             'donnerstag' => 4,
             'freitag' => 5,
             'sonnabend' => 6,
             'sonntag' => 7
         );

         /** @var $tabellePropertiesDays Zend_Db_Table */
         $tabellePropertiesDays = $this->pimple['tabellePropertiesDays'];

         // löschen der An und Abreisetage ines Hotels
         $tabellePropertiesDays->delete("propertyId = ".$params['id']);

         $eintragenTage = 0;
         foreach($wochentage as $tag => $zaehlerTag){

             // keine An und Abreise
             $statusAnUndAbreise = 1;

             // Anreisetag
             if( (array_key_exists($tag.'An', $params)) and (!array_key_exists($tag.'Ab', $params)) )
                 $statusAnUndAbreise = 2;
             // Abreisetag
             elseif( (!array_key_exists($tag.'An', $params)) and (array_key_exists($tag.'Ab', $params)) )
                 $statusAnUndAbreise = 3;
             // An und Abreisetag
             elseif( (array_key_exists($tag.'An', $params)) and (array_key_exists($tag.'Ab', $params)) )
                 $statusAnUndAbreise = 4;

             $insertDays = array(
                 'propertyId' => $params['id'],
                 'wochentag' => $zaehlerTag,
                 'anUndAbreiseTag' => $statusAnUndAbreise
             );

             if($tabellePropertiesDays->insert($insertDays))
                 $eintragenTage++;
         }

         return $eintragenTage;
     }

     /**
      * Ermittelt die Anreise und Abreisetage eines Hotels
      *
      * @param $hotelId
      */
     protected function ermittelnAnUndAbreisetage($hotelId)
     {
         $cols = array(
             'wochentag',
             'anUndAbreiseTag'
         );

         /** @var $tabellePropertiesDays Zend_Db_Table */
         $tabellePropertiesDays = $this->pimple['tabellePropertiesDays'];

         $select = $tabellePropertiesDays->select();
         $select
             ->from($tabellePropertiesDays, $cols)
             ->where("propertyId = ".$hotelId)
             ->order('wochentag');

         $query = $select->__toString();

         $rows = $tabellePropertiesDays->fetchAll($select)->toArray();

         return $rows;
     }

     /**
      * Bereitet die An und Abreisetage eines Hotels auf. Verwendung von Checkboxen;
      *
      * @param array $hotelAnUndAbreisetageArray
      */
     protected function checkboxAnUndAbreise(array $hotelAnUndAbreisetageArray)
     {
         $hotelAnUndAbreiseCheckbox = array();

         $wochentage = array(
             'montag' => 1,
             'dienstag' => 2,
             'mittwoch' => 3,
             'donnerstag' => 4,
             'freitag' => 5,
             'sonnabend' => 6,
             'sonntag' => 7
         );

         for($i=0; $i < count($hotelAnUndAbreisetageArray); $i++){
             $tag = $hotelAnUndAbreisetageArray[$i];

             $nameWochentag = array_search($tag['wochentag'], $wochentage);

             // Anreisetag
             if($tag['anUndAbreiseTag'] == 2){
                 $hotelAnUndAbreiseCheckbox[$nameWochentag.'An'] = 'on';
             }
             // Abreisetag
             elseif($tag['anUndAbreiseTag'] == 3){
                 $hotelAnUndAbreiseCheckbox[$nameWochentag.'Ab'] = 'on';
             }
             // An und Abreisetag
             elseif($tag['anUndAbreiseTag'] == 4){
                 $hotelAnUndAbreiseCheckbox[$nameWochentag.'An'] = 'on';
                 $hotelAnUndAbreiseCheckbox[$nameWochentag.'Ab'] = 'on';
             }
         }

         return $hotelAnUndAbreiseCheckbox;
     }

     /**
      * Ermittelt die Stammdaten eines Hotel
      *
      * @param $id
      * @return mixed
      */
     protected function ermittelnHotelStammdaten($id)
     {
         $sql = "
			SELECT
			    `tbl_properties`.`property_name`
			    , `tbl_properties`.`property_code`
			    , `tbl_properties`.`id`
			    , `tbl_properties`.`aktiv`
			    , `tbl_property_details`.`country`
			    , `tbl_property_details`.`country_code`
			    , `tbl_property_details`.`city`
			    , `tbl_properties`.`overbook`
			    , `tbl_properties`.`numberPeopleTravelGroup`
			    , `tbl_properties`.`gewinnspanne`
			    , `tbl_properties`.`fruehestensBuchbar` as fruehestens
			    , `tbl_properties`.`spaetestensBuchbar` as spaetestens
			    , `tbl_properties`.`minAnzahluebernachtungen` as minimal
			FROM
			    `tbl_properties`
			    INNER JOIN `tbl_property_details`
			        ON (`tbl_properties`.`id` = `tbl_property_details`.`properties_id`)
			WHERE `tbl_properties`.`id` = '".$id."'";

         $hotelStammdaten = $this->db_hotels->fetchRow($sql);

         if($hotelStammdaten['overbook'] == $this->condition_keine_ueberbuchung)
             unset($hotelStammdaten['overbook']);
         else
             $hotelStammdaten['overbook'] = 'on';

         return $hotelStammdaten;
     }

     /**
      * Update der Stammdaten eines Hotels
      *
      * @param $params
      * @return int
      */
     protected function updateStammdatenHotel()
     {
         $params = $this->pimple['params'];

         $id = $params['id'];
         if(!array_key_exists('id',$params))
             throw new nook_Exception('Hotel ID fehlt');

         $colsPropertiesTable = array(
             'property_code' => trim($params['property_code']),
             'property_name' => $params['property_name'],
             'aktiv' => $params['aktiv'],
             'numberPeopleTravelGroup' => $params['numberPeopleTravelGroup'],
             'gewinnspanne' => $params['gewinnspanne'],
             'fruehestensBuchbar' => $params['fruehestens'],
             'spaetestensBuchbar' => $params['spaetestens'],
             'minAnzahluebernachtungen' => $params['minimal']
         );


         if(array_key_exists('overbook', $params))
             $colsPropertiesTable['overbook'] = $this->condition_ueberbuchung;
         else
             $colsPropertiesTable['overbook'] = $this->condition_keine_ueberbuchung;

         /** @var $tabelleProperties Zend_Db_Table */
         $tabelleProperties = $this->pimple['tabelleProperties'];
         $kontrolle = $tabelleProperties->update($colsPropertiesTable, "id = ".$id);

         return;
     }

     /**
      * @return bool
      */
     public function getStatusUpdate()
     {
         return $this->kontrolleUpdate;
     }

 
 } 
<?php
/**
 * Es werden benutzer in der Tabelle 'tbl_adressen' gesucht
 *
 * @author Stephan Krauss
 * @date 05.05.2014
 * @file Offlinebuchung.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 
 class Front_Model_Offlinebuchung
 {
     protected $pimple = null;
     protected $benutzer = array();

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_Offlinebuchung
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolliert den Inhalt des DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         // Tabellen
         if(!$pimple['viewFirmen'] instanceof Application_Model_DbTable_viewFirmen)
            throw new nook_Exception('View Firmen nicht vorhanden');

         if(!$pimple['tabelleBuchungsnummer'] instanceof Application_Model_DbTable_buchungsnummer)
             throw new nook_Exception('Tabelle Buchungsnummer nicht vorhanden');

         // Suchparameter
         if(count($pimple['suchParameter']) < 1)
             throw new nook_Exception('Suchparameter fehlen');

         $suchparameter = $pimple['suchParameter'];

         // Kontrolle Adress ID
         if($suchparameter['id']){
             $suchparameter['id'] = (int) $suchparameter['id'];

             if($suchparameter['id'] === 0)
                 unset($suchparameter['id']);

         }

         // Kontrolle HOB - Nummer ID
         if($suchparameter['hobNummer']){
             $suchparameter['hobNummer'] = (int) $suchparameter['hobNummer'];

             if($suchparameter['hobNummer'] === 0){
                 unset($suchparameter['hobNummer']);
             }
         }

         $pimple['suchParameter'] = $suchparameter;

         return;
     }

     /**
      * Steuert die Ermittlung der Benutzer der Tabelle 'tbl_adressen'
      *
      * @return Front_Model_Offlinebuchung
      * @throws Exception
      */
     public function steuerungErmittlungBenutzer(){
         try{
             if(is_null($this->pimple))
                 throw new nook_Exception('Pimple fehlt');

             // Kontrolle Anzahl Suchparameter
             $suchParameter = $this->pimple['suchParameter'];

             if( (!is_array($suchParameter)) or (count($suchParameter) == 0) )
                 return $this;

             if(array_key_exists('hobNummer', $suchParameter)){
                 $suche = array();
                 $adressId = $this->ermittelnBenutzerIdBuchungstabelle($this->pimple['tabelleBuchungsnummer'], $suchParameter['hobNummer']);
                 $suche['id'] = $adressId;

                 $benutzer = $this->ermittlungBenutzer($this->pimple['viewFirmen'], $suche);
             }
             else{
                 $benutzer = $this->ermittlungBenutzer($this->pimple['viewFirmen'], $suchParameter);
             }


             $this->benutzer = $benutzer;

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt die Benutzer in der Tabelle 'view_firmen'
      *
      * @param $viewFirmen
      * @param $suchParameter
      */
     protected function ermittlungBenutzer(Application_Model_DbTable_viewFirmen $viewFirmen, $suchParameter)
     {
         $select = $viewFirmen->select();

         foreach($suchParameter as $key => $value){
             if($key == 'id')
                 $select->where("id = ".$value);
             else
                $select->where($key." like '%".$value."%'");
         }

         $query = $select->__toString();

         $rows = $viewFirmen->fetchAll($select)->toArray();

         if(count($rows) > 0)
             return $rows;
         else
             return;
     }

     /**
      * Ermittelt die ID des Benutzers in 'tbl_buchungsnummer' mit HOB - Nummer
      *
      * @param Application_Model_DbTable_buchungsnummer $tabelleBuchungsnummer
      * @param array $suchParameter
      * @return int
      */
     protected function ermittelnBenutzerIdBuchungstabelle(Application_Model_DbTable_buchungsnummer $tabelleBuchungsnummer, $hobNummer)
    {
        $cols = array(
            'kunden_id'
        );

        $whereHobNummer = "hobNummer = ".$hobNummer;

        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where($whereHobNummer);

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) > 1)
           $this->registerInformation('Zu viele Buchungsdatensaetze: '.$query);

        return $rows[0]['kunden_id'];
    }


     /**
      * Registriert aufgetretene Fehler
      *
      * @param $information
      */
     protected function registerInformation($information)
     {
         try{
             throw new nook_Exception($information);
         }
         catch(Exception $e){
             $e->kundenId = nook_ToolKundendaten::findKundenId();
             nook_ExceptionRegistration::buildAndRegisterErrorInfos($e, 2);
         }

         return;
     }

     /**
      * @return array
      */
     public function getBenutzer()
     {
         if(count($this->benutzer) > 0)
             return $this->benutzer;
         else
             return;
     }
 }
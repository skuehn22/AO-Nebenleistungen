<?php
/**
 * Der Offlinebucher übernimmt einen benutzer / Kunden und bucht in desen Auftrag
 *
 * @author Stephan Krauss
 * @date 06.05.2014
 * @file UebernahmeBenutzer.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_UebernahmeBenutzer
 {
     protected $pimple = null;
     protected $condition_superuser_aktiv = 2;

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_UebernahmeBenutzer
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Überprüfung Inhalt DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         // Tabellen
         if( (!$pimple->offsetExists('tabelleBuchungsnummer')) or (!$pimple['tabelleBuchungsnummer'] instanceof Application_Model_DbTable_buchungsnummer) )
             throw new nook_Exception('Tabelle Buchungsnummer falsch');

         if( (!$pimple->offsetExists('tabelleAdressenSuperuser')) or (!$pimple['tabelleAdressenSuperuser'] instanceof Application_Model_DbTable_adressenSuperuser) )
             throw new nook_Exception('Tabelle adressenSuperuser falsch');

         if( (!$pimple->offsetExists('tabelleAdressen')) or (!$pimple['tabelleAdressen'] instanceof Application_Model_DbTable_adressen) )
             throw new nook_Exception('Tabelle adressen falsch');

         // Offlinebucher ID
         if( !$pimple->offsetExists('offlineBucherId') )
             throw new nook_Exception('Offline Bucher ID falsch');

         if(!is_int(intval($pimple['offlineBucherId'])))
             throw new nook_Exception('Offline Bucher ID kein Int');

         // Kunden ID
         if( !$pimple->offsetExists('kundenId') )
             throw new nook_Exception('Kunden ID falsch');

         if(!is_int(intval($pimple['kundenId'])))
             throw new nook_Exception('Kunden ID kein Int');

         return;
     }

     public function steuerungUmschreibenBenutzer()
     {
         try{
             if(is_null($this->pimple))
                 throw new nook_Exception('DIC fehlt');

             // Tabelle 'tbl_buchungsnummer'
             $this->eintragenTabelleBuchungsnummer($this->pimple['kundenId'], $this->pimple['offlineBucherId'], $this->pimple['tabelleBuchungsnummer']);

             // eintragen Tabelle 'tbl_adressen_superuser'
             $this->eintragenTabelleAdressenSuperuser ($this->pimple['offlineBucherId'], $this->pimple['kundenId']);

             // Veraenderung Session 'Auth'
             $this->veraenderungSessionAuth($this->pimple['kundenId'], $this->pimple['tabelleAdressen']);
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Veraendert die Variablen des Namespace 'Auth' der Session auf Offlinebuchung / Superuser
      *
      * + Bestimmung Kundendaten
      * + Session Namespace 'Auth'
      *
      * @param $kundenId
      * @param Application_Model_DbTable_adressen $tabelleAdressen
      * @throws nook_Exception
      */
     protected function veraenderungSessionAuth($kundenId, Application_Model_DbTable_adressen $tabelleAdressen)
     {
         // Kundendaten
         $whereKundenId = "id = ".$kundenId;

         $select = $tabelleAdressen->select();
         $select->where($whereKundenId);

         $query = $select->__toString();

         $rows = $tabelleAdressen->fetchAll($select)->toArray();

         if(count($rows) <> 1)
             throw new nook_Exception('Anzahl Datensaetze falsch');

         // Session Namespace 'Auth'
         $auth = new Zend_Session_Namespace('Auth');
         $auth->role_id = $rows[0]['status'];
         $auth->userId = $rows[0]['id'];
         $auth->superuser = $this->condition_superuser_aktiv;
         $auth->anbieter = $rows[0]['anbieter'];
         $auth->company_id = $rows[0]['id'];

         return;
     }

     /**
      * Trägt den Kunden in die 'tbl_adressen_superuser' ein
      *
      * @param $superuserId
      * @param $kundenId
      * @return mixed
      */
     protected function eintragenTabelleAdressenSuperuser ($superuserId, $kundenId)
     {
         $whereAdressenId = "adressen_id = " . $kundenId;
         $whereSuperuserId = "superuser_id = " . $superuserId;

         $insert = array(
             'adressen_id'  => $kundenId,
             'superuser_id' => $superuserId
         );

         $tabelleAdressenSuperuser = new Application_Model_DbTable_adressenSuperuser();

         $select = $tabelleAdressenSuperuser->select();
         $select
             ->where($whereAdressenId)
             ->where($whereSuperuserId);

         $query = $select->__toString();

         $rows = $tabelleAdressenSuperuser->fetchAll($select)->toArray();

         // wenn noch nicht in Tabelle 'tbl_adressenSuperuser'
         if(count($rows) == 0)
             $tabelleAdressenSuperuser->insert($insert);

         return;
     }

     /**
      * anlegen eines Warenkorbes in 'tbl_buchungsnummer'
      *
      * + nur wenn Warenkorb noch nicht existiert
      * + oder update Datensatz 'tbl_buchungsnummer'
      *
      * @param $kundenId
      * @return
      */
     protected function eintragenTabelleBuchungsnummer ($kundenId, $offlineBucherId, Zend_Db_Table_Abstract $tabelleBuchungsnummer)
     {
         $sessionId = Zend_Session::getId();

         $select = $tabelleBuchungsnummer->select();
         $select->where("session_id = '" . $sessionId . "'");

         $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

         // neuer Datensatz
         if(count($rows) == 0){

             $insert = array(
                 'session_id'   => $sessionId,
                 'kunden_id'    => $kundenId,
                 'superuser_id' => $offlineBucherId
             );

             $tabelleBuchungsnummer->insert($insert);
         }

         // update bestehenden Datensatz
         if(count($rows) == 1){

             $update = array(
                 'superuser_id' => $offlineBucherId,
                 'kunden_id'    => $kundenId
             );

             $whereSessionId = "session_id = '".$sessionId."'";

             $tabelleBuchungsnummer->update($update, $whereSessionId);
         }

         return;
     }
 
 }

//include_once('../../../../autoload_cts.php');
//
//$pimple = new Pimple_Pimple();
//$pimple['offlineBucherId'] = 111;
//$pimple['kundenId'] = 222;
//$pimple['tabelleBuchungsnummer'] = function(){
//    return new Application_Model_DbTable_buchungsnummer();
//};
//$pimple['tabelleAdressenSuperuser'] = function(){
//    return new Application_Model_DbTable_adressenSuperuser();
//};
//
//$frontModelOfflinebucherUebernahmeBenutzer = new Front_Model_UebernahmeBenutzer();
//$frontModelOfflinebucherUebernahmeBenutzer
//    ->setPimple($pimple)
//    ->steuerungUmschreibenBenutzer();
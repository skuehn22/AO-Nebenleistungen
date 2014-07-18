<?php
/**
 * Bestimmt mittels Programm Id oder der Company ID die Zugehörigkeit des Benutzers zu einer Kooperation.
 *
 * + Gibt falg = false zurück wenn der angemeldete Kunde nicht der Kooperation angehört.
 * + Verwendung nur im Admin - Bereich
 * + Ermittelt Kunden ID
 * + Ermittelt Rolle ID
 *
 * @author Stephan Krauss
 * @date 11.06.2014
 * @file nook_ToolProgrammeEinerKooperation.php
 * @project HOB
 * @package tool
 */
 class nook_ToolProgrammeEinerKooperation
 {
     protected $pimple = null;

     protected $rolleId = null;
     protected $userId = null;

     protected $programmId = null;
     protected $companyId = null;

     protected $kooperationsIdUser = null;

     protected $flagZugriffErlaubt = false;

     protected $tabelleAdressen = null;
     protected $tabelleProgrammdetailsFiliale = null;

     protected $condition_mindestrolle_admin_bereich = 5;

     /**
      * Bestimmt die Kunden ID und die ID der Kooperation
      */
     public function __construct()
     {
         $this->pimple = $this->buildPimple();
     }

     /**
      * Ermitteln der User ID und der Rolle ID des Benutzers
      *
      * @throws nook_Exception
      */
     protected function bestimmeKundenDatenAusSession()
     {
         $sessionAuth = new Zend_Session_Namespace('Auth');

         // Rolle des Benutzers
         $rolleId = $sessionAuth->role_id;

         $rolleId = (int) $rolleId;
         if(empty($rolleId))
             throw new nook_Exception('Rolle ID leer oder falsch');

         if($rolleId < $this->condition_mindestrolle_admin_bereich)
             throw new nook_Exception('Rolle zu klein für Admin Bereich');

         $this->rolleId = $rolleId;

         // User ID des benutzers
         $userId = $sessionAuth->userId;
         $userId = (int) $userId;

         if(empty($userId))
             throw new nook_Exception('User ID fehlt');

         $this->userId = $userId;

         return;
     }

     /**
      * Baut DIC
      *
      * @return Pimple_Pimple
      */
     protected function buildPimple()
     {
         $pimple = new Pimple_Pimple();

         $pimple['tabelleAdressen'] = function(){
             return new Application_Model_DbTable_adressen();
         };

         $pimple['tabelleProgrammdetailsFiliale'] = function(){
             return new Application_Model_DbTable_programmedetailsFiliale();
         };

         return $pimple;
     }

     /**
      * @param $programmId
      * @return nook_ToolProgrammeEinerKooperation
      */
     public function setprogrammId($programmId)
     {
         $programmId = (int) $programmId;
         if($programmId == 0)
             throw new nook_Exception('Programm ID falsch');

         $this->programmId = $programmId;

         return $this;
     }

     /**
      * @param $companyId
      * @return nook_ToolProgrammeEinerKooperation
      * @throws nook_Exception
      */
     public function setCompanyId($companyId)
     {
         $companyId = (int) $companyId;
         if($companyId == 0)
             throw new nook_Exception('Company ID falsch');

         $this->companyId = $companyId;

         return $this;
     }

     /**
      * @return nook_ToolProgrammeEinerKooperation
      * @throws Exception
      */
     public function steuerungErmittlungZugriffAufDieAction()
     {
         try{
             if( (is_null($this->programmId)) and (is_null($this->companyId)) )
                 throw new nook_Exception('Anfangsangaben fehlen');

             if( (!is_null($this->programmId)) and (!is_null($this->companyId)) )
                 throw new nook_Exception('Zu viele Anfangsangaben');

             // Kunden Daten, UserID und RolleID
             $this->bestimmeKundenDatenAusSession();

             // Kooperations ID des User
             $this->kooperationsIdUser = $this->bestimmeKooperationUser($this->userId, $this->pimple['tabelleAdressen']);

             // Gehört das Programm zur Kooperation
             if(!is_null($this->programmId))
                 $this->flagZugriffErlaubt = $this->gehoertProgrammZurKooperation($this->kooperationsIdUser, $this->programmId, $this->pimple['tabelleProgrammdetailsFiliale']);

             // Gehört Firma zur Kooperation
             if(!is_null($this->companyId))
                 $this->flagZugriffErlaubt = $this->gehoertCompanyZurKooperation($this->kooperationsIdUser, $this->companyId, $this->pimple['tabelleAdressen']);

             return $this;


         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt ob Firma zur Kooperation des Users gehört
      *
      * @param $kooperationsIdUser
      * @param $companyId
      * @param Zend_Db_Table_Abstract $tabelleAdressen
      * @return bool
      */
     protected function gehoertCompanyZurKooperation($kooperationsIdUser, $companyId, Zend_Db_Table_Abstract $tabelleAdressen)
     {
         $cols = array(
             'kooperation'
         );

         $whereCompanyId = "id = ".$companyId;

         $select = $tabelleAdressen->select();
         $select
             ->from($tabelleAdressen, $cols)
             ->where($whereCompanyId);

         $query = $select->__toString();

         $rows = $tabelleAdressen->fetchAll($select)->toArray();

         if($rows[0]['kooperation'] == $kooperationsIdUser)
             $this->flagZugriffErlaubt = true;


         return $this->flagZugriffErlaubt;
     }

     /**
      * Ermittelt ob das Programm zur Kooperation gehört. Verändert $flagZugriffErlaubt
      *
      * @param $kooperationsIdUser
      * @param $programmId
      * @param Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale
      * @return bool
      */
     protected function gehoertProgrammZurKooperation($kooperationsIdUser, $programmId,Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale)
     {
         $cols = array(
             new Zend_Db_Expr("count(id) as anzahl")
         );

         $whereProgrammdetailsId = "programmdetails_id = ".$programmId;
         $whereFilialeId = "filiale_id = ".$kooperationsIdUser;

         $select = $tabelleProgrammdetailsFiliale->select();
         $select
             ->from($tabelleProgrammdetailsFiliale, $cols)
             ->where($whereProgrammdetailsId)
             ->where($whereFilialeId);

         $query = $select->__toString();

         $rows = $tabelleProgrammdetailsFiliale->fetchAll($select)->toArray();

         if($rows[0]['anzahl'] == 1)
             $this->flagZugriffErlaubt = true;

         return $this->flagZugriffErlaubt;
     }

     /**
      * Ermittelt die Koopearions ID des Benutzers
      *
      * @param $userId
      * @param Zend_Db_Table_Abstract $tabelleAdressen
      * @return int
      */
     protected function bestimmeKooperationUser($userId, Zend_Db_Table_Abstract $tabelleAdressen)
     {
         $cols = array(
             'kooperation'
         );

         $whereUserId = "id = ".$userId;

         $select = $tabelleAdressen->select();
         $select
             ->from($tabelleAdressen, $cols)
             ->where($whereUserId);

         $query = $select->__toString();

         $rows = $tabelleAdressen->fetchAll($select)->toArray();

         return $rows[0]['kooperation'];
     }

     public function getFlagZugriffErlaubt()
     {
         return $this->flagZugriffErlaubt;
     }

 
 } 
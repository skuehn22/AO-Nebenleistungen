<?php
/**
 * Veränderung der Zugehörigkeit zu einer Kooperation
 *
 * @author Stephan Krauss
 * @date 11.06.2014
 * @file Admin_Model_KooperationEintragenZugehoerigkeit.php
 * @project HOB
 * @package admin
 * @subpackage model
 */
 class Admin_Model_KooperationEintragenZugehoerigkeit
 {
     protected $programmId = null;
     protected $kooperationId = null;

     protected $pimple = null;

     protected $flagKooperationVorhanden = false;

     public function __construct()
     {
         $this->pimple = $this->buildPimple();

     }

     /**
      * erstellen DIC
      *
      * @return Pimple_Pimple
      */
     protected function buildPimple()
     {
         $pimple = new Pimple_Pimple();

         $pimple['tabelleProgrammdetailsFiliale'] = function(){
             return new Application_Model_DbTable_programmedetailsFiliale();
         };

         return $pimple;
     }

     /**
      * @param $programmId
      * @return Admin_Model_KooperationEintragenZugehoerigkeit
      * @throws nook_Exception
      */
     public function setProgrammId($programmId)
     {
         $programmId = (int) $programmId;
         if($programmId == 0)
             throw new nook_Exception('Programm ID falsch');

         $this->programmId = $programmId;

         return $this;
     }

     /**
      * @param $kooperationId
      * @return Admin_Model_KooperationEintragenZugehoerigkeit
      * @throws nook_Exception
      */
     public function setKooperationId($kooperationId)
     {
         $kooperationId = (int) $kooperationId;
         if($kooperationId == 0)
             throw new nook_Exception('Kooperation ID falsch');

         $this->kooperationId = $kooperationId;

         return $this;
     }

     /**
      * Steuert das setzen einer Kooperation eines Programmes
      *
      * @return Admin_Model_KooperationEintragenZugehoerigkeit
      * @throws Exception
      */
     public function steuerungSetzenKooperationEinesProgrammes(){
         try{
             if( (is_null($this->programmId)) or (is_null($this->kooperationId)) )
                 throw new nook_Exception('Anfangswerte fehlen');

             $flagKooperationVorhanden = $this->ermittelnVorhandenseinKooperation($this->programmId, $this->kooperationId, $this->pimple['tabelleProgrammdetailsFiliale']);

             if($flagKooperationVorhanden === false)
                 $this->kooperationEintragen($this->programmId, $this->kooperationId, $this->pimple['tabelleProgrammdetailsFiliale']);
             else
                 $this->kooperationLoeschen($this->programmId, $this->kooperationId, $this->pimple['tabelleProgrammdetailsFiliale']);


             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt das vorhandensein von Kooperationen eines Programmes
      *
      * @param $programmId
      * @param $kooperationId
      * @param Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale
      * @return bool
      * @throws nook_Exception
      */
     protected function ermittelnVorhandenseinKooperation($programmId, $kooperationId,Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale)
     {
         $cols = array(
             new Zend_Db_Expr("count(id) as anzahl")
         );

         $whereProgrammId = "programmdetails_id = ".$programmId;
         $whereKooperationId = "filiale_id = ".$kooperationId;

         $select = $tabelleProgrammdetailsFiliale->select();
         $select->from($tabelleProgrammdetailsFiliale, $cols)->where($whereProgrammId)->where($whereKooperationId);

         $query = $select->__toString();

         $rows = $tabelleProgrammdetailsFiliale->fetchAll($select)->toArray();

         if($rows[0]['anzahl'] == 0)
            $this->flagKooperationVorhanden = false;
         elseif($rows[0]['anzahl'] == 1)
             $this->flagKooperationVorhanden = true;
         else
             throw new nook_Exception('Anzahl der Kooperationen desProgramm ID: '.$programmId." falsch");

         return $this->flagKooperationVorhanden;
     }

     /**
      * Trägt eine Kooperation eines Programmes ein
      *
      * @param $programmId
      * @param $kooperationId
      * @param Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale
      */
     protected function kooperationEintragen($programmId, $kooperationId,Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale)
     {
         $insert = array(
             'programmdetails_id' => $programmId,
             'filiale_id' => $kooperationId
         );

         $kontrolle = $tabelleProgrammdetailsFiliale->insert($insert);

         return;
     }

     /**
      * Löschen einer Kooperation eines Programmes
      *
      * @param $programmId
      * @param $kooperationId
      * @param Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale
      */
     protected function kooperationLoeschen($programmId, $kooperationId,Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale)
     {
         $where = array(
             "programmdetails_id = ".$programmId,
             "filiale_id = ".$kooperationId
         );

         $kontrolle = $tabelleProgrammdetailsFiliale->delete($where);

         return;
     }
 
 } 
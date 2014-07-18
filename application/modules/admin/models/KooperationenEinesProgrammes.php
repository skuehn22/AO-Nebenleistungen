<?php
/**
 * Durch die übergabe einer Programm ID wird die Beteiligung des Programmes an Kooperation / Filiale ermittelt.
 *
 * @author Stephan Krauss
 * @date 11.06.2014
 * @file Admin_Model_KooperationenEinesprogrammes
 * @project HOB
 * @package admin
 * @subpackage model
 */
 class Admin_Model_KooperationenEinesProgrammes
 {
     protected $programmId = null;
     protected $kooperationen = array();

     protected $pimple = null;


     /**
      * erstellen DIC
      */
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
      * @return Admin_Model_KooperationenEinesProgrammes
      * @throws nook_Exception
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
      * @return array
      */
     public function getKooperationen()
     {
         return $this->kooperationen;
     }

     /**
      * Steuert die Ermittlung der Kooperationen eines Programmes mittels Programm ID
      *
      * @return Admin_Model_KooperationenEinesProgrammes
      * @throws Exception
      */
     public function steuerungErmittlungKooperationen(){
         try{
             if(is_null($this->programmId))
                 throw new nook_Exception('Programm ID fehlt');

             $this->kooperationen = $this->ermittlungKooperationenEinesProgrammes($this->programmId, $this->pimple['tabelleProgrammdetailsFiliale']);


             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt die Zugehörigkeit der Filialen / Kooperation zu einem Programm
      *
      * @param $programmId
      * @param Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale
      * @return array
      */
     protected function ermittlungKooperationenEinesProgrammes($programmId,Zend_Db_Table_Abstract $tabelleProgrammdetailsFiliale)
     {
         $cols = array(
             new Zend_Db_Expr("filiale_id as filiale")
         );

         $whereProgrammId = "programmdetails_id = ".$programmId;

         $select = $tabelleProgrammdetailsFiliale->select();
         $select
             ->from($tabelleProgrammdetailsFiliale, $cols)
             ->where($whereProgrammId);

         $query = $select->__toString();

         $rows = $tabelleProgrammdetailsFiliale->fetchAll($select)->toArray();

         return $rows;
     }

 
 } 
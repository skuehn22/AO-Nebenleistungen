<?php
/**
 * Ermittelt die Premiumprogramme einer Stadt. Die Programme können über die Kategorie 'tbl_programmdetails.premiumprogramm' ausgewählt werden.
 *
 * @author Stephan Krauss
 * @date 20.05.2014
 * @file PremiumProgrammeEinerStadt.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_PremiumProgrammeEinerStadt
 {
     protected $pimple = null;

     protected $cityId = null;
     protected $premiumProgrammKategorieId = null;
     protected $anzeigespracheId = null;

     protected $premiumProgrammeEinerStadt = array();

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_PremiumProgrammeEinerStadt
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Kontrolle des DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
         if( !$pimple['viewProgrammeEinerStadt'] instanceof Application_Model_DbTable_viewProgrammeEinerStadt )
             throw new nook_Exception('View Programme einer Stadt fehlt');

         // Programm Kategorie ID
         if($pimple->offsetExists('premiumProgrammKategorieId')){
             $premiumProgrammKategorieId = (int) $pimple['premiumProgrammKategorieId'];
             if($premiumProgrammKategorieId == 0)
                 throw new nook_Exception('Premium Programmkategorie falsch: '.$pimple['premiumProgrammKategorieId']);

             $this->premiumProgrammKategorieId = $premiumProgrammKategorieId;
         }

         // City ID
         if(!$pimple->offsetExists('cityId'))
             throw new nook_Exception('City ID fehlt: '.$pimple['cityId']);

         $cityId = (int) $pimple['cityId'];
         if($cityId == 0)
             throw new nook_Exception('City ID falsch: '.$pimple['cityId']);

         $this->cityId = $cityId;

         // Anzeigesprache ID
         if(!$pimple->offsetExists('anzeigeSpracheId'))
             throw new nook_Exception('Anzeigesprache ID fehlt');

         $anzeigeSpracheId = (int) $pimple['anzeigeSpracheId'];
         if($anzeigeSpracheId == 0)
             throw new nook_Exception('Anzeigesprache falsch: '.$pimple['anzeigesprache']);

         $this->anzeigespracheId = $anzeigeSpracheId;

         return;
     }

     /**
      * @return Front_Model_PremiumProgrammeEinerStadt
      * @throws Exception
      */
     public function steuerungErmittelnPremiumprogrammeEinerStadt()
     {
         try{
             if(is_null($this->pimple))
                 throw new nook_Exception('DIC fehlt');

             $this->ermittelnPremiumprogrammeEinerStadt($this->cityId, $this->anzeigespracheId);

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Ermittelt die Premium Programme einer Stadt
      *
      * @param $cityId
      * @param $premiumProgrammKategorieId
      * @param $anzeigespracheId
      * @return array
      */
     protected function ermittelnPremiumprogrammeEinerStadt($cityId, $anzeigespracheId)
     {
         $cols = array(
             'txt',
             'progname',
             'id'
         );

         $whereOrtId = "ortId = ".$cityId;
         $whereSpracheId = "spracheId = ".$anzeigespracheId;
         $wherePremiumProgrammKategorieId = "premiumprogramm IS NOT NULL and premiumprogramm > 0";

         /** @var $viewProgrammeEinerStadt Application_Model_DbTable_viewProgrammeEinerStadt */
         $viewProgrammeEinerStadt = $this->pimple['viewProgrammeEinerStadt'];

         $select = $viewProgrammeEinerStadt->select();
         $select
             ->from($viewProgrammeEinerStadt, $cols)
             ->where($whereOrtId)
             ->where($whereSpracheId)
             ->where($wherePremiumProgrammKategorieId)
             ->group('id')
             ->order('id');

         $query = $select->__toString();

         $rows = $viewProgrammeEinerStadt->fetchAll($select)->toArray();
         $this->premiumProgrammeEinerStadt = $rows;

         return $rows;
     }

     /**
      * @return array
      */
     public function getPremiumProgrammeEinerStadt()
     {
         return $this->premiumProgrammeEinerStadt;
     }
 }


/*************/
//include_once('../../../../autoload_cts.php');
//
//$pimple = new Pimple_Pimple();
//$pimple['cityId'] = 1;
//$pimple['premiumProgrammKategorieId'] = 1;
//$pimple['anzeigeSpracheId'] = 1;
//
//$pimple['viewProgrammeEinerStadt'] = function(){
//    return new Application_Model_DbTable_viewProgrammeEinerStadt();
//};
//
//$frontModelPremiumprogrammeEinerStadt = new Front_Model_PremiumProgrammeEinerStadt();
//$premiumProgrammeEinerStadt = $frontModelPremiumprogrammeEinerStadt
//    ->setPimple($pimple)
//    ->steuerungErmittelnPremiumprogrammeEinerStadt()
//    ->getPremiumProgrammeEinerStadt();
//
//$test = 123;

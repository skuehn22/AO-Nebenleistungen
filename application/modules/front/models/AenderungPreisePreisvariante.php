<?php
/**
 * Veränderung des Einkauf und des Verkaufspreises einer Preisvariante
 *
 * @author Stephan Krauss
 * @date 15.05.2014
 * @file AenderungPreisePreisvariante.php
 * @project HOB
 * @package front
 * @subpackage model
 */
 class Front_Model_AenderungPreisePreisvariante
 {

     protected $pimple = null;

     /**
      * @param Pimple_Pimple $pimple
      * @return Front_Model_AenderungPreisePreisvariante
      */
     public function setPimple(Pimple_Pimple $pimple)
     {
         $this->checkPimple($pimple);
         $this->pimple = $pimple;

         return $this;
     }

     /**
      * Überprüfung DIC
      *
      * @param Pimple_Pimple $pimple
      * @throws nook_Exception
      */
     protected function checkPimple(Pimple_Pimple $pimple)
     {
        if(!$pimple->offsetExists('anzahlPreisvarianten'))
            throw new nook_Exception('Anzahl Preisvarianten fehlt');

        if(!$pimple->offsetExists('preisVarianteId'))
            throw new nook_Exception('PreisVarianteId fehlt');

        if( (!$pimple->offsetExists('einkaufspreis')) or (!$pimple->offsetExists('verkaufspreis')) )
            throw new nook_Exception('Einkaufspreis oder Verkaufspreis fehlt');

         if(!$pimple->offsetExists('tabellePreise'))
             throw new nook_Exception('Tabelle Preise nicht vorhanden');

         return;
     }

     /**
      * Steuert die Veränderung des Einkaufspreises und des Verkaufspreises einer Preisvariante eines Programmes
      *
      * @return Front_Model_AenderungPreisePreisvariante
      * @throws Exception
      */
     public function steuerungUpdatePreiseRabattprogramm()
     {
         try{
             if(! $this->pimple instanceof Pimple_Pimple)
                 throw new nook_Exception('DIC fehlt');

             $kontrolleUpdate = $this->aenderungPreisePreisvariante($this->pimple['preisVarianteId'], $this->pimple['einkaufspreis'], $this->pimple['verkaufspreis']);


             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Verändert die Preise einer Preisvariante
      *
      * @param $preisVarianteId
      * @param $einkaufspreis
      * @param $verkaufspreis
      * @return int
      */
     protected function aenderungPreisePreisvariante($preisVarianteId, $einkaufspreis, $verkaufspreis)
     {
         $update = array(
             'einkaufspreis' => $einkaufspreis,
             'verkaufspreis' => $verkaufspreis
         );

         $whereProgrammdetailsId = "id = ".$preisVarianteId;

         /** @var $tabellePreise Application_Model_DbTable_preise */
         $tabellePreise = $this->pimple['tabellePreise'];
         $kontrolleUpdate = $tabellePreise->update($update, $whereProgrammdetailsId);

         return $kontrolleUpdate;
     }
 
 } 
<?php
/**
 * Tool zum suchen des Inhaltes der Spalte einer Tabelle.
 *
 * + übernimmt where Klauseln als Array
 * + mehrere Inhalte einer Spalte sind möglich
 *
 * @author Stephan Krauss
 * @date 12.06.2014
 * @file nook_ToolSucheEintragInSpalte.php
 * @project HOB
 * @package tool
 */
 class nook_ToolSucheEintragInSpalte
 {
     protected $gesuchteSpalte = null;
     protected $whereSpalte = array();
     protected $tabelle = null;

     protected $inhaltSpalte = null;

     /**
      * Übernimmt das Array der where Klauseln
      *
      * @param array $whereSpalte
      * @return nook_ToolSucheEintragInSpalte
      * @throws nook_Exception
      */
     public function setSpalteWhereKlausel(array $whereSpalte)
     {
         if(!is_array($whereSpalte))
             throw new nook_Exception('kein Array $whereSpalte');

         $this->whereSpalte = $whereSpalte;

         return $this;
     }

     /**
      * Spaltenname der gesuchten Spalte
      *
      * @param $gesuchteSpalte
      * @return nook_ToolSucheEintragInSpalte
      */
     public function setGesuchteSpalte($gesuchteSpalte)
     {
         $this->gesuchteSpalte = $gesuchteSpalte;

         return $this;
     }

     /**
      * Übernimmt Zend_Db_Table
      *
      * @param Zend_Db_Table_Abstract $tabelle
      * @return nook_ToolSucheEintragInSpalte
      */
     public function setTabelle(Zend_Db_Table_Abstract $tabelle)
     {
         $this->tabelle = $tabelle;

         return $this;
     }

     /**
      * Steuert das ermitteln des Inhaltes einer Spalte
      *
      * @return nook_ToolSucheEintragInSpalte
      */
     public function steuerungErmittlungInhaltSpalte()
     {
         try{
             if( (is_null($this->gesuchteSpalte)) or (count($this->whereSpalte) == 0) )
                 throw new nook_Exception('notwendige Anfangswerte fehlen');

             if(is_null($this->tabelle))
                 throw new nook_Exception('Zend_Db_Table fehlt');

             $this->inhaltSpalte = $this->ermittelnInhaltSpalte($this->gesuchteSpalte, $this->whereSpalte, $this->tabelle);

             return $this;
         }
         catch(Exception $e){
            throw $e;
         }
     }

     /**
      * Ermittelt den Inhalt einer Spalte
      *
      * + mehrere Inhalte der selben Spalte sind möglich
      *
      * @param $gesuchteSpalte
      * @param array $whereSpalte
      * @param Zend_Db_Table_Abstract $tabelle
      * @return array
      */
     protected function ermittelnInhaltSpalte($gesuchteSpalte,array $whereSpalte,Zend_Db_Table_Abstract $tabelle)
     {
         $cols = array(
             $gesuchteSpalte
         );

         $select = $tabelle->select();
         $select->from($tabelle, $cols);

         foreach($whereSpalte as $whereCondition){
             $select->where($whereCondition);
         }

         $query = $select->__toString();

         $rows = $tabelle->fetchAll($select)->toArray();

         return $rows;
     }

     /**
      * @return array
      */
     public function getInhaltSpalte()
     {
         return $this->inhaltSpalte;
     }
 }
<?php
/**
 * Die notwendigen Angaben zur Unterstützung der Template Austria werden ermittelt
 *
 * + Aufruf als Singleton
 * + Rückgabe:
 * + Anzeigesprache
 * + vorhandene Städte
 *
 * @author Stephan Krauss
 * @date 22.05.2014
 * @file ServiceTemplate.php
 * @project HOB
 * @package front
 * @subpackage model
 */
  class Front_Model_ServiceTemplate
 {
      protected $service = array();
      protected $anzeigesprache = 1;

      public function __construct(){

          $this->service['sprache'] = $this->ermittelnAnzeigesprache();
          $this->service['city'] = $this->ermittelnStaedte();
      }

      /**
       * @return array
       */
      public function getServiceTemplate()
      {

          return $this->service;
      }

      /**
       * Ermittelt die momentan gültige Anzeigesprache
       *
       * @return mixed
       */
      protected function ermittelnAnzeigesprache()
      {
          $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

          return $anzeigesprache;
      }

      /**
       * Ermittelt die vorhandenen Städte
       *
       * + ID der Stadt
       * + Stadtname in der Anzeigesprache
       *
       * @return mixed
       */
      protected function ermittelnStaedte()
      {
          $cols = array(
              'AO_City_ID as cityId',
              'AO_City as city'
          );

          $tabelleAoCity = new Application_Model_DbTable_aoCity();
          $select = $tabelleAoCity->select();
          $select->from($tabelleAoCity, $cols)->order('AO_City');

          $query = $select->__toString();

          $rowsStaedte = $tabelleAoCity->fetchAll($select)->toArray();

          return $rowsStaedte;
      }
 }
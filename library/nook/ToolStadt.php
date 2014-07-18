<?php
/**
 * Tools zur Darstellung des Stadtnamen und
 * der ID's der AO - Citys
 *
 * @author Stephan Krauß
 */

class nook_ToolStadt{

    /**
     * Ermittelt mit der ID des Programmes den Namen
     * der Stadt und die ID der Stadt
     *
     * @param $__programmId
     * @return array
     */
    public static function getStadtNameVonProgramm($__programmId){

        $sprache_deutsch = 1;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        $sql = "
            SELECT
                tbl_programmdetails.AO_City  as stadtId
                , tbl_ao_city.AO_City  as stadtname
            FROM
                tbl_programmdetails
                INNER JOIN tbl_programmbeschreibung
                    ON (tbl_programmdetails.id = tbl_programmbeschreibung.programmdetail_id)
                INNER JOIN tbl_ao_city
                    ON (tbl_programmdetails.AO_City = tbl_ao_city.AO_City_ID)
            WHERE (tbl_programmbeschreibung.sprache = ".$sprache_deutsch."
                AND tbl_programmdetails.id = ".$__programmId.")";

        $stadtInfo = $db->fetchRow($sql);

        return $stadtInfo;
    }

    /**
     * Findet den Stadtnamen mittels
     * der ID der Stadt
     *
     * @param $__stadtId
     * @return mixed
     */
    public static function getStadtNameMitStadtId($__stadtId){
        $tabelleStadt = new Application_Model_DbTable_aoCity();
        $stadtArray = $tabelleStadt->find($__stadtId)->toArray();

        return $stadtArray[0]['AO_City'];
    }

} // end class
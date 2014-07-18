<?php
/**
* Anzahl der Vormerkungen eines Kunden
*
* + Bestimmt die Anzahl der Vormerkungen eines Kunden
*
* @date 16.58.2013
* @file nook_ToolVormerkungen.php
* @package tools
*/

class nook_ToolVormerkungen{

    /**
     * Bestimmt die Anzahl der Vormerkungen eines Kunden
     */
    public static function bestimmeAnzahlVormerkungen($benutzerId)
    {
        $statusWarenkorbVorgemerkt = 2;

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereStatusVormerkung = "status = ".$statusWarenkorbVorgemerkt;
        $whereKundenId = "kunden_id = ".$benutzerId;

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where($whereStatusVormerkung)
            ->where($whereKundenId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

}

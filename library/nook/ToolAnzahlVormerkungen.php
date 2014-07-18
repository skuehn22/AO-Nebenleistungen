<?php 
/**
* Berechnet die Anzahl der Vormerkungen
*
* + Steuert die Ermittlung der Anzahl der Vormerkungen eine Kunden
* + Ermittelt die Anzahl der Vormerkungen eines Kunden
*
* @date 14.11.2013
* @file ToolAnzahlVormerkungen.php
* @package tools
*/
class nook_ToolAnzahlVormerkungen
{
    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;

    // Fehler
    private $error_anfangswerte_fehlen = 2410;

    // Informationen

    // Konditionen
    private $condition_status_vorgemerkt = 2;

    // Zustände

    protected $kundenId = null;
    protected $anzahlVormerkungen = 0;

    public function __construct()
    {
        $this->servicecontainer();
    }

    private function servicecontainer()
    {
        $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return;
    }

    /**
     * @param $kundenId
     * @return nook_ToolAnzahlVormerkungen
     */
    public function setKundenId($kundenId)
    {
        $kundenId = (int) $kundenId;
        $this->kundenId = $kundenId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Anzahl der Vormerkungen eine Kunden
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuerungErmittlungAnzahlVormerkungen()
    {
        if(is_null($this->kundenId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $anzahlVormerkungen = $this->ermittelnAnzahlVormerkungen($this->kundenId);
        $this->anzahlVormerkungen = $anzahlVormerkungen;

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Vormerkungen eines Kunden
     *
     * @param $kundenId
     * @return mixed
     */
    private function ermittelnAnzahlVormerkungen($kundenId)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereKundenId = "kunden_id = ".$kundenId;
        $whereStatus = "status = ".$this->condition_status_vorgemerkt;

        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->from($this->tabelleBuchungsnummer, $cols)
            ->where($whereKundenId)
            ->where($whereStatus);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * @return int
     */
    public function getAnzahlvormerkungen()
    {
        return $this->anzahlVormerkungen;
    }

}

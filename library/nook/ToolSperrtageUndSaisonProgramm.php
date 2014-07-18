<?php 
 /**
 * Überprüft das Datum eines Programmes auf Sperrtage und ob sich das Datum innerhalb einer Saison befindet.
 *
 * @author Stephan.Krauss
 * @date 11.10.2013
 * @file ToolSperrtageUndSaisonProgramm.php
 * @package tools
 */
class nook_ToolSperrtageUndSaisonProgramm
{
    // Fehler
    private $error_anfangswerte_fehlen = 2290;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails  */
    private $tabelleProgrammdetails = null;
    /** @var $tabelleSperrtage Application_Model_DbTable_sperrtage */
    private $tabelleSperrtage = null;

    // Konditionen
    protected $condition_fruehest_moegliches_datum = "2020-12-31";

    // Flags
    private $flagBuchungMoeglich = false;

    protected $datumInSekunden = null;
    protected $programmId = null;

    public function __construct($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        $this->tabellen();
    }

    /**
     * Zugriff auf die Tabellen
     */
    private function tabellen()
    {
        $this->tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        $this->tabelleSperrtage = new Application_Model_DbTable_sperrtage();

        return;
    }

    /**
     * @param $datumInSekunden
     * @return nook_ToolSperrtageUndSaisonProgramm
     */
    public function setDatumInSekunden($datumInSekunden)
    {
        $this->datumInSekunden = $datumInSekunden;

        return $this;
    }

    /**
     * Steuert die Kontrolle. Prüft ob eine Buchung am betreffenden Tag möglich ist
     *
     * + Umrechnung Datum in Sekunden nach ISO 8601
     * + Kontrolle ob Datum in der Saison
     * + Kontrolle ob Datum einem Sperrtag entspricht
     *
     * @return nook_ToolSperrtageUndSaisonProgramm
     * @throws nook_Exception
     */
    public function sucheMoeglichesBuchungsdatum()
    {
        if(!$this->programmId)
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(!$this->datumInSekunden)
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        // Umrechnung Datum in Sekunden nach ISO 8601
        $datum = $this->umrechnenDatum($this->datumInSekunden);

        // Kontrolle ob Datum in der Saison
        $this->flagBuchungMoeglich = $this->sucheBuchungsdatumInSaison($this->programmId, $this->tabelleProgrammdetails, $datum);

        // Kontrolle ob Datum einem Sperrtag entspricht
        if($this->flagBuchungMoeglich)
            $this->flagBuchungMoeglich = $this->sucheSperrtage($this->programmId,$this->tabelleSperrtage, $datum);

        return $this;
    }

    /**
     * ermittelt ob das Datum mit einem Sperrtag zusammenfällt
     *
     * @param $programmId
     * @param Application_Model_DbTable_sperrtage $tabelleSperrtage
     * @param $datum
     * @return bool
     */
    private function sucheSperrtage($programmId, Application_Model_DbTable_sperrtage $tabelleSperrtage, $datum)
    {
        $teileDatum = explode('-', $datum);

        $whereProgrammId = "programmdetails_id = ".$programmId;
        $whereJahr = "jahr = ".$teileDatum[0];
        $whereMonat = "monat = ".$teileDatum[1];
        $whereTag = "tag = ".$teileDatum[2];

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $select = $tabelleSperrtage->select();
        $select
            ->from($tabelleSperrtage, $cols)
            ->where($whereProgrammId)
            ->where($whereJahr)
            ->where($whereMonat)
            ->where($whereTag);

        $rows = $tabelleSperrtage->fetchAll($select)->toArray();

        if($rows[0]['anzahl'] == 1)
            $flagBuchungMoeglich = false;
        else
            $flagBuchungMoeglich = true;

        return $flagBuchungMoeglich;
    }

    /**
     * Ermittelt ob ein Datum innerhalb der Saison liegt
     *
     * @param $programmId
     * @param Application_Model_DbTable_programmedetails $tabelleProgrammdetails
     * @param $datum
     * @return bool
     */
    private function sucheBuchungsdatumInSaison($programmId, Application_Model_DbTable_programmedetails $tabelleProgrammdetails, $datum)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereProgrammId = "id = ".$programmId;
        $whereDatumInStornofrist = new Zend_Db_Expr("valid_from <= '".$datum."' and valid_thru >= '".$datum."'");

        $select = $tabelleProgrammdetails->select();
        $select->from($tabelleProgrammdetails, $cols)->where($whereProgrammId)->where($whereDatumInStornofrist);
        $rows = $tabelleProgrammdetails->fetchAll($select)->toArray();

        if($rows[0]['anzahl'] == 1)
            $flagBuchungMoeglich = true;
        else
            $flagBuchungMoeglich = false;

        return $flagBuchungMoeglich;
    }

    /**
     * rechnet eine Anzahl Sekunden nach ISO 8601 um
     *
     *
     * @param $datumInSekunden
     * @return date
     */
    private function umrechnenDatum($datumInSekunden)
    {
        $datumIso = date("Y-m-d", $datumInSekunden);

        return $datumIso;
    }

    /**
     * @return boolean
     */
    public function getFlagBuchungMoeglich()
    {
        return $this->flagBuchungMoeglich;
    }

}

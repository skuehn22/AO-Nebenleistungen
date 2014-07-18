<?php 
/**
* Model zur Verwaltung durchlaufender Preisposten
*
* + Rückgabe vorhandener durchlaufender Preisposten
* + Steuert die Ermittlung vorhandener durchlaufender Preisposten
* + Ermitteln der vorhandenen durchlaufenden Preisposten in deutsch
*
* @date 23.07.13
* @file PreispostenDuchlaeufer.php
* @package admin
* @subpackage model
*/
 class Admin_Model_PreispostenDurchlaeufer
{
    // Fehler
    private $error_fehlende_anfangswerte = 1920;
    private $error_falsche_anzahl_datensaetze = 1921;

    // Tabellen / Views
    /** @var $tabelleRechnungenDurchlaeufer Application_Model_DbTable_rechnungenDurchlaeufer */
    private $tabelleRechnungenDurchlaeufer = null;

    // Konditionen

    // Flags

    protected $pimple = null;
    protected $durchlaufendePreisposten = null;

    /**
     * @param Pimple_Pimple $pimple
     * @return Admin_Model_PreispostenDurchlaeufer
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->tabelleRechnungenDurchlaeufer = $pimple['tabelleRechnungenDurchlaeufer'];
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Rückgabe vorhandener durchlaufender Preisposten
     *
     * @return array
     */
    public function getDurchlaufendePreisposten()
    {
        return $this->durchlaufendePreisposten;
    }

    /**
     * Steuert die Ermittlung vorhandener durchlaufender Preisposten
     *
     * @return Admin_Model_PreispostenDurchlaeufer
     */
    public function steuerungErmittlungDurchlaufendePreisposten()
    {
        if(empty($this->tabelleRechnungenDurchlaeufer))
            throw new nook_Exception($this->error_fehlende_anfangswerte);

        $this->ermittlungDurchlaufendePreisposten();

        return $this;
    }

    /**
     * Ermitteln der vorhandenen durchlaufenden Preisposten in deutsch
     *
     * @return int
     * @throws nook_Exception
     */
    private function ermittlungDurchlaufendePreisposten()
    {
        $cols = array(
            new Zend_Db_Expr("id as durchlaeuferId"),
            new Zend_Db_Expr("bezeichnung_de as durchlaeufer")
        );

        $select = $this->tabelleRechnungenDurchlaeufer->select();
        $select
            ->from($this->tabelleRechnungenDurchlaeufer, $cols)
            ->order('id asc');

        $rows = $this->tabelleRechnungenDurchlaeufer->fetchAll($select)->toArray();

        if(count($rows) == 0)
            throw new nook_Exception($this->error_falsche_anzahl_datensaetze);

        $this->durchlaufendePreisposten = $rows;

        return count($rows);
    }
}

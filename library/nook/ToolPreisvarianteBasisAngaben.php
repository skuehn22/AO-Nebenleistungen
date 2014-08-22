<?php 
 /**
 * Ermittelt die Basisangaben einer Preisvariante entsprechend der Anzeigesprache
 *
 * @author Stephan.Krauss
 * @date 21.11.2013
 * @file ToolPreisvarianteBasisAngaben.php
 * @package tools
 */
class nook_ToolPreisvarianteBasisAngaben
{
    // Fehler
    private $error_anfangswerte_fehlen = 2450;
    private $error_anfangswert_falsch = 2451;
    private $error_anzahl_datensetze_falsch = 2452;

    // Informationen

    // Tabellen / Views
    /** @var $tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
    private $tabellePreiseBeschreibung = null;

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;
    protected $preisvarianteId = null;
    protected $sprachenId = null;

    protected $basisAngabenPreisvariante = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabellePreiseBeschreibung'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $sprachenId
     * @return nook_ToolPreisvarianteBasisAngaben
     */
    public function setSprachenId($sprachenId)
    {
        $sprachenId = (int) $sprachenId;

        $kontrolle = $this->tabellePreiseBeschreibung->kontrolleValue('sprachen_id', $sprachenId);
        if(false === $kontrolle)
            throw new nook_Exception($this->error_anfangswert_falsch);

        $this->sprachenId = $sprachenId;

        return $this;
    }

    /**
     * @param $preiseId
     * @return nook_ToolPreisvarianteBasisAngaben
     */
    public function setPreisvarianteId($preisvarianteId)
    {
        $preisvarianteId = (int) $preisvarianteId;

        $kontrolle = $this->tabellePreiseBeschreibung->kontrolleValue('id', $preisvarianteId);
        if(false === $kontrolle)
            throw new nook_Exception($this->error_anfangswert_falsch);

        $this->preisvarianteId = $preisvarianteId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der basisngaben einer Preisvariante entsprechend der Anzeigesprache
     *
     * @return nook_ToolPreisvarianteBasisAngaben
     */
    public function steuerungErmittlungBasisangabenPreisvariante()
    {
        if(is_null($this->preisvarianteId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(is_null($this->sprachenId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $basisAngabenPreisvariante = $this->ermittlungBasisangabenPreisvariante($this->preisvarianteId, $this->sprachenId);
        $this->basisAngabenPreisvariante = $basisAngabenPreisvariante;

        return $this;
    }

    /**
     * Ermitteln der basisangaben einer Preisvariante eines Programmes
     *
     * @param $preisvarianteId
     * @param $sprachenId
     * @return array
     * @throws nook_Exception
     */
    private function ermittlungBasisangabenPreisvariante($preisvarianteId, $sprachenId)
    {
        $cols = array(
            'preisvariante',
            'confirm_1'
        );

        $wherePreisvarianteId = "preise_id = ".$preisvarianteId;
        $whereSpracheId = "sprachen_id = ".$sprachenId;

        $select = $this->tabellePreiseBeschreibung->select();
        $select
            ->from($this->tabellePreiseBeschreibung, $cols)
            ->where($wherePreisvarianteId)
            ->where($whereSpracheId);

        $rows = $this->tabellePreiseBeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensetze_falsch);

        return $rows;
    }

    /**
     * @return array
     */
    public function getBasisAngabenPreisvariante()
    {
        return $this->basisAngabenPreisvariante;
    }
}

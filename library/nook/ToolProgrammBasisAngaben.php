<?php
 /**
 * Ermittelt die Basisangaben eines Programmes. Programmname , Programmbeschreibung, Sprache
 *
 * @author Stephan.Krauss
 * @date 20.11.2013
 * @file ToolProgrammBasisAngaben.php
 * @package tools
 */

class nook_ToolProgrammBasisAngaben
{
    // Fehler
    private $error_anfangswert_fehlt = 2440;
    private $error_anfangsangaben_falsch = 2441;
    private $error_anzahl_datensaetze_falsch = 2442;

    // Informationen

    // Konditionen

    // Zustände

    // Tabellen / Views
    /** @var $tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
    private $tabelleProgrammbeschreibung = null;

    // Tools

    protected $pimple = null;
    protected $programmdetailId = null;
    protected $sprache = null;
    protected $basisangabenProgrammbeschreibung = array();


    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);

    }

    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProgrammbeschreibung'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswert_fehlt);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $sprache
     * @return nook_ToolProgrammBasisAngaben
     * @throws nook_Exception
     */
    public function setAnzeigesprache($sprache)
    {
        $kontrolle = $this->tabelleProgrammbeschreibung->kontrolleValue('sprache', $sprache);
        if($kontrolle === false)
            throw new nook_Exception($this->error_anfangsangaben_falsch);

        $this->sprache = $sprache;

        return $this;
    }

    /**
     * @param $programmdetailId
     * @return nook_ToolProgrammBasisAngaben
     * @throws nook_Exception
     */
    public function setProgrammdetailId($programmdetailId)
    {
        $kontrolle = $this->tabelleProgrammbeschreibung->kontrolleValue('programmdetail_id', $programmdetailId);
        if($kontrolle === false)
            throw new nook_Exception($this->error_anfangsangaben_falsch);

        $this->programmdetailId = $programmdetailId;

        return $this;
    }

    /**
     * @return array
     */
    public function getBasisangabenProgrammbeschreibung()
    {
        return $this->basisangabenProgrammbeschreibung;
    }

    /**
     * Ermittelt die Basisangaben der Beschreibung eines Programmes
     *
     * @return $this
     */
    public function steuerungErmittlungBasisangabenProgrammbeschreibung()
    {
        if(is_null($this->programmdetailId))
            throw new nook_Exception($this->error_anfangswert_fehlt);
        if(is_null($this->sprache))
            throw new nook_Exception($this->error_anfangswert_fehlt);

        $basisAngabenProgrammbeschreibung = $this->ermittlungBasisangabenProgrammbeschreibung($this->programmdetailId);
        $this->basisangabenProgrammbeschreibung = $basisAngabenProgrammbeschreibung;

        return $this;
    }

    /**
     * Ermittelt die daten , Basisdaten einer Programmbeschreibung
     *
     * @param $programmdetailId
     * @return array
     * @throws nook_Exception
     */
    private function ermittlungBasisangabenProgrammbeschreibung($programmdetailId)
    {
        $cols = array(
            'txt',
            'progname'
        );

        $whereProgrammdetailId = "programmdetail_id = ".$programmdetailId;
        $whereSpracheId = "sprache = ".$this->sprache;

        $select = $this->tabelleProgrammbeschreibung->select();
        $select
            ->where($whereProgrammdetailId)
            ->where($whereSpracheId);

        $rows = $this->tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return $rows[0];
    }
}

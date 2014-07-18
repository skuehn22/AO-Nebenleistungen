<?php 
/**
* Handelt die Tabelle 'tbl_programmdetails'
*
* + Gibt Werte der Tabelle Programmdetails zurück
* + Steuerung Ermittlung Daten
* + Ermittelt die ID des Programmes
* + Ermittelt die Daten des Programmes
* + Gibt eine Variable des Programmes zurück
*
* @author Stephan.Krauss
* @date 12.08.13
* @file ToolProgrammdetails.php
* @package tools
*/
class nook_ToolProgrammdetails
{
    // Tabellen / Views
    /** @var  $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
    private $tabelleProgrammdetails = null;
    /** @var  $tabellePreise Application_Model_DbTable_preise */
    private $tabellePreise = null;
    /** @var $tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
    private $tabelleProgrammbeschreibung = null;

    // Konditionen

    // Fehler
    private $error_anfangswert_nicht_vorhanden = 1940;
    private $error_anzahl_datensaetze_falsch = 1941;

    // Flags

    protected $programmId = null;
    protected $preisvarianteId = null;
    protected $datenProgramm = array();
    protected $anzeigeSpracheId = null;
    protected $programmName = null;
    protected $programmBeschreibung = null;


    /**
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        if($pimple->offsetExists('tabelleProgrammdetails'))
            $this->tabelleProgrammdetails = $pimple['tabelleProgrammdetails'];
        if($pimple->offsetExists('tabellePreise'))
            $this->tabellePreise = $pimple['tabellePreise'];
        if($pimple->offsetExists('tabelleProgrammbeschreibung'))
            $this->tabelleProgrammbeschreibung = $pimple['tabelleProgrammbeschreibung'];
    }

    /**
     * @param $preisvarianteId
     * @return nook_ToolProgrammdetails
     */
    public function setPreisvarianteId($preisvarianteId)
    {
        $preisvarianteId = (int) $preisvarianteId;
        $this->preisvarianteId = $preisvarianteId;

        return $this;
    }

    /**
     * @param $anzeigeSpracheId
     * @return nook_ToolProgrammdetails
     */
    public function setAnzeigespracheId($anzeigeSpracheId)
    {
        $anzeigeSpracheId = (int) $anzeigeSpracheId;
        $this->anzeigeSpracheId = $anzeigeSpracheId;

        return $this;
    }

    /**
     * Steuerung Ermittlung Daten
     *
     * @return nook_ToolProgrammdetails
     * @throws nook_Exception
     */
    public function steuerungErmittelnDaten()
    {
        if(empty($this->preisvarianteId))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        $this->ermittelnProgrammId();
        $this->ermittlungProgrammdaten();

        return $this;
    }

    /**
     * Ermittelt die ID des Programmes
     *
     * @throws nook_Exception
     */
    private function ermittelnProgrammId()
    {
        $whereId = "id = ".$this->preisvarianteId;

        $cols = array(
            'programmdetails_id'
        );

        $tabellePreise = $this->tabellePreise;
        $select = $tabellePreise->select();
        $select->from($tabellePreise, $cols)->where($whereId);

        $rows = $tabellePreise->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->programmId = $rows[0]['programmdetails_id'];

        return;
    }

    /**
     * Ermittelt die Daten des Programmes
     *
     * @throws nook_Exception
     */
    private function ermittlungProgrammdaten()
    {
        $tabelleProgrammdetails = $this->tabelleProgrammdetails;
        $rows = $tabelleProgrammdetails->find($this->programmId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->datenProgramm = $rows[0];

        return;
    }

    /**
     * Gibt eine Variable des Programmes zurück
     *
     * @param $key
     * @return bool
     */
    public function getProgrammdetail($key)
    {
        if(array_key_exists($key, $this->datenProgramm))
            return $this->datenProgramm[$key];
        else
            return false;
    }

    /**
     * Steuert die Ermittlung des Programmnamen und der Programmbeschreibung
     *
     * @return nook_ToolProgrammdetails
     * @throws nook_Exception
     */
    public function steuerungProgrammbeschreibung()
    {
        if(is_null($this->programmId))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        if(is_null($this->anzeigeSpracheId))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        if(is_null($this->tabelleProgrammbeschreibung))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        $this->bestimmungProgrammBeschreibung();

        return $this;
    }

    /**
     * Ermitteln des Programmbeschreibung mittels Programm ID
     *
     * + berücksichtigt die Anzeigesprache
     * + Programmname
     * + Programmbeschreibung
     */
    private function bestimmungProgrammBeschreibung()
    {
        $whereProgrammDetailsId = "programmdetail_id = ".$this->programmId;
        $whereAnzeigeSprache = "sprache = ".$this->anzeigeSpracheId;

        $select = $this->tabelleProgrammbeschreibung->select();
        $select->where($whereProgrammDetailsId)->where($whereAnzeigeSprache);

        $rows = $this->tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->programmName = $rows[0]['progname'];
        $this->programmBeschreibung = $rows[0]['txt'];

        return;
    }

    /**
     * @return string
     */
    public function getProgrammname()
    {
        return $this->programmName;
    }

    /**
     * @return string
     */
    public function getProgrammBeschreibung()
    {
        return $this->programmBeschreibung;
    }


}

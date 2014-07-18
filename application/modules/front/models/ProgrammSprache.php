<?php
/**
* Ermittelt die vorhandenen Sprachen eines Programmes und bestimmt die gewählte Programmsprache
*
* + Übernimmt Pimple und legt Anzeigesprache fest
* + Setzt eine bereits ausgewaehlte Sprache
* + Steuert die Ermittlung der Programmsprachen eines Programmes
* + Ermittelt Sprachbezeichnung, Flagge und Bezeichnung der Programmsprache eines Programmes
* + Ermittelt die ID der Programmsprachen eines Programmes
* + Gibt die ermittelten Programmsprachen zurück
* + Steuert die Ermittlung einer gebuchten Programmsprache
* + Ermittelt die gebuchte Programmsprache eines Programmes
* + Steuert die Ermittlung des Namen einer gewählten Programmsprache
* + Gibt den Namen der gewählten Programmsprache in der Anzeigesprache zurück
*
* @author Stephan.Krauss
* @date 09.07.13
* @file Front_Model_ProgrammSprache.php
* @package front
* @subpackage model
*/
class Front_Model_ProgrammSprache
{
    // Tabellen / Views
    /** @var  $tabelleProgrammdetailsProgsprachen Application_Model_DbTable_programmedetailsProgsprachen */
    private $tabelleProgrammdetailsProgsprachen = null;
    /** @var $tabelleProgSprache Application_Model_DbTable_progSprache */
    private $tabelleProgSprache = null;
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;

    // Fehler
    private $error_anfangswerte_fehlen = 1860;
    private $error_anzahl_datensaetze_falsch = 1861;

    // Konditionen
    private $condition_sprache_deutsch_id = 1;
    private $condition_zaehler_aktuelle_buchung = 0;
    private $condition_keine_programmsprache_gebucht = 0;

    // Flags

    protected $pimple = null;

    protected $buchungsnummerId = null;
    protected $zaehler = null;

    protected $programmId = null;
    protected $preisvarianteId = null;
    protected $sprachwahlId = null; // bereits gewaehlte Sprache

    protected $anzeigeSprache = null;
    protected $anzahlProgrammsprachen = 0;
    protected $gewaehlteProgrammspracheId = null;
    protected $gewaehlteProgrammsprache = null;
    protected $datum = null;
    protected $zeit = null;

    protected $programmsprachen = array();

    /**
     * Übernimmt Pimple und legt Anzeigesprache fest
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        if(!$pimple->offsetExists('tabelleProgrammdetailsProgsprachen'))
            $this->tabelleProgrammdetailsProgsprachen = new Application_Model_DbTable_programmedetailsProgsprachen();
        else
            $this->tabelleProgrammdetailsProgsprachen = $pimple['tabelleProgrammdetailsProgsprachen'];

        if(!$pimple->offsetExists('tabelleProgSprache'))
            $this->tabelleProgSprache = new Application_Model_DbTable_progSprache();
        else
            $this->tabelleProgSprache = $pimple['tabelleProgSprache'];

        if(!$pimple->offsetExists('tabelleProgrammbuchung'))
            $this->tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        else
            $this->tabelleProgrammbuchung = $pimple['tabelleProgrammbuchung'];

        $this->anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammsprachen()
    {
        return $this->anzahlProgrammsprachen;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_ProgrammSprache
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @param $zeit
     * @return Front_Model_ProgrammSprache
     */
    public function setZeit($zeit)
    {
        $this->zeit = $zeit;

        return $this;
    }

    /**
     * @param $datum
     * @return Front_Model_ProgrammSprache
     */
    public function setDatum($datum)
    {
        $this->datum = $datum;

        return $this;
    }

    /**
     * @param $anzeigeSpracheId
     * @return Front_Model_ProgrammSprache
     */
    public function setAnzeigeSprache($anzeigeSpracheId)
    {
        $anzeigeSpracheId = (int) $anzeigeSpracheId;
        $this->anzeigeSprache = $anzeigeSpracheId;

        return $this;
    }

    /**
     * @param $spracheId
     * @return Front_Model_ProgrammSprache
     */
    public function setGewaehlteProgrammsprache($spracheId)
    {
        $spracheId = (int) $spracheId;
        $this->gewaehlteProgrammspracheId = $spracheId;

        return $this;
    }

    /**
     * @param null $preisvarianteId
     * @return Front_Model_ProgrammSprache
     */
    public function setPreisvarianteId($preisvarianteId)
    {
        $preisvarianteId = (int) $preisvarianteId;
        $this->preisvarianteId = $preisvarianteId;

        return $this;
    }

    /**
     * @param null $programmId
     * @return Front_Model_ProgrammSprache
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * Setzt eine bereits ausgewaehlte Sprache
     *
     * @param null $sprachwahlId
     * @return Front_Model_ProgrammSprache
     */
    public function setSprachwahlId($sprachwahlId)
    {
        $this->sprachwahlId = $sprachwahlId;

        return $this;
    }

    /**
     * @param null $zaehler
     * @return Front_Model_ProgrammSprache
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameGewaehlteProgrammsprache()
    {
        return $this->gewaehlteProgrammsprache;
    }

    /**
     * Steuert die Ermittlung der Programmsprachen eines Programmes
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuernErmittelnProgrammsprachen()
    {
        if (empty($this->programmId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->tabelleProgrammdetailsProgsprachen))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $anzahlProgrammsprachen = $this->ermittelnProgrammsprachen();

        for ($i = 0; $i < $anzahlProgrammsprachen; $i++) {
            $programmSpracheId = $this->programmsprachen[$i]['progsprache_id'];
            $this->ergaenzenProgrammsprache($programmSpracheId, $i);
        }

        return $this;
    }

    /**
     * Ermittelt Sprachbezeichnung, Flagge und Bezeichnung der Programmsprache eines Programmes
     *
     * + Standardsprache = 1 , deutsch
     * + Kontrolliert ob Sprache gewählt ist.
     * + wenn gewaehlt, dann 'gewaehlt' = 2
     *
     * + Korrektur von nicht vorhandenen Programmsprachen
     *
     * @param $spracheId
     * @param $i
     * @return array
     * @throws nook_Exception
     */
    private function ergaenzenProgrammsprache($spracheId, $i)
    {
        if ($this->anzeigeSprache == $this->condition_sprache_deutsch_id) {

            $cols = array(
                'language',
                'flag',
                new Zend_Db_Expr("de as beschreibung")
            );
        } else {

            $cols = array(
                'language',
                'flag',
                new Zend_Db_Expr("eng as beschreibung")
            );
        }

        $whereSpracheId = "id = " . $spracheId;

        $select = $this->tabelleProgSprache->select();
        $select->from($this->tabelleProgSprache, $cols)->where($whereSpracheId);

        $sprache = $this->tabelleProgSprache->fetchAll($select)->toArray();

        // Fehlerbehandlung bei unsinniger Programmsprache
        if (count($sprache) == 0){
            $sprache = array();
            $sprache[0]['language'] = 'deutsch';
            $sprache[0]['flag'] = 'ger';
            $sprache[0]['beschreibung'] = 'deutsch';
        }

        if (!empty($this->sprachwahlId) and ($spracheId == $this->sprachwahlId)) {
            $sprache[0]['gewaehlt'] = 2;
        } else {
            $sprache[0]['gewaehlt'] = 1;
        }

        $sprache[0]['spracheId'] = $spracheId;

        $this->programmsprachen[$i] = $sprache[0];

        return $sprache[0];
    }

    /**
     * Ermittelt die ID der Programmsprachen eines Programmes
     *
     * + gibt Anzahl Programmsprachen zurück
     *
     * @return int
     */
    private function ermittelnProgrammsprachen()
    {
        $whereProgrammId = "programmdetails_id = " . $this->programmId;

        $select = $this->tabelleProgrammdetailsProgsprachen->select();
        $select
            ->where($whereProgrammId)
            ->order("progsprache_id asc");

        $this->programmsprachen = $this->tabelleProgrammdetailsProgsprachen->fetchAll($select)->toArray();

        $this->anzahlProgrammsprachen = count($this->programmsprachen);

        return $this->anzahlProgrammsprachen;
    }

    /**
     * Gibt die ermittelten Programmsprachen zurück
     *
     * @return array
     */
    public function getProgrammsprachen()
    {
        return $this->programmsprachen;
    }

    /**
     * Steuert die Ermittlung einer gebuchten Programmsprache
     *
     * @return int
     * @throws nook_Exception
     */
    public function steuerungErmittelnGebuchteProgrammsprache()
    {
        if(empty($this->tabelleProgrammbuchung))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->buchungsnummerId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->programmId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->preisvarianteId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $idProgrammsprache = $this->ermittelnGebuchteProgrammsprache();

        return $idProgrammsprache;
    }

    /**
     * Ermittelt die gebuchte Programmsprache eines Programmes
     *
     * + wurde keine Programmsprache gebucht, dann Rückgabe 0
     * + wurde eine Programmsprache gebucht, dann Rückgabe ID der gebuchten Sprache
     * + die Zeit ist optional
     * + das Datum ist optional
     *
     * @return int
     * @throws nook_Exception
     */
    private function ermittelnGebuchteProgrammsprache()
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereProgrammdetail = "programmdetails_id = ".$this->programmId;
        $wherePreisvariante = "tbl_programme_preisvarianten_id = ".$this->preisvarianteId;
        $whereZaehler = "zaehler = ".$this->condition_zaehler_aktuelle_buchung;

        $cols = array(
            'sprache'
        );

        $select = $this->tabelleProgrammbuchung->select();
        $select->from($this->tabelleProgrammbuchung, $cols)
            ->where($whereProgrammdetail)
            ->where($whereBuchungsnummer)
            ->where($wherePreisvariante)
            ->where($whereZaehler);

        // Zeit
        if(!empty($this->zeit)){
            $whereZeit = "zeit = '".$this->zeit."'";
            $select->where($whereZeit);
        }

        // Datum
        if(!empty($this->datum))
            $whereDatum = "datum = '".$this->datum."'";
        else
            $whereDatum = "datum = '0000-00-00'";
        $select->where($whereDatum);

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        if(empty($rows[0]['sprache']))
            $idProgrammsprache = 0;
        else
            $idProgrammsprache = $rows[0]['sprache'];

        return $idProgrammsprache;
    }

    /**
     * Steuert die Ermittlung des Namen einer gewählten Programmsprache
     *
     * @return Front_Model_ProgrammSprache
     * @throws nook_Exception
     */
    public function steuerungErmittelnNameGebuchteProgrammsprache()
    {
        if(empty($this->tabelleProgSprache))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->anzeigeSprache))
            $this->anzeigeSprache = $this->condition_sprache_deutsch_id;

        if(empty($this->gewaehlteProgrammsprache))
            $this->gewaehlteProgrammsprache = $this->condition_sprache_deutsch_id;

        $gewaehlteProgrammsprache = $this->ermittelnNameGebuchteProgrammsprache();
        $this->gewaehlteProgrammsprache = $gewaehlteProgrammsprache;

        return $this;
    }

    /**
     * Gibt den Namen der gewählten Programmsprache in der Anzeigesprache zurück
     *
     * + wenn der Name der Anzeigesprache indeutig ist, so wird dieser zurückgegeben
     * + wenn es mehrere Namen gibt, dann wird ein Fehler zurück gegeben
     * + gibt es keine Programmsprache im Buchungsdatensatz des Programmes dann = false
     *
     * @return string / bool
     * @throws nook_Exception
     */
    private function ermittelnNameGebuchteProgrammsprache()
    {
        $rows = $this->tabelleProgSprache->find($this->gewaehlteProgrammspracheId)->toArray();

        if(count($rows) > 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);
        elseif(count($rows) == 1){
            if($this->anzeigeSprache == 1)
                return $rows[0]['de'];
            else
                return $rows[0]['eng'];
        }
        elseif(count($rows) == 0)
            return false;

        return;
    }
}

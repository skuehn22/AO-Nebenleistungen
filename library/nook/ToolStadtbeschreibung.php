<?php
/**
 * Findet den Beschreibungstext einer Stadt mittels
 * Stadt ID. Text wird in der Anzeigesprache zurückgegeben.
 * Städte ohne Beschreibung geben einen
 * leeren String zurück.
 *
 * Es kann ein Kurztext zurück gesandt werden.
 *
 * @date 21.02.13 14:52
 * @author Stephan Krauß
 */



class nook_ToolStadtbeschreibung{

    // Error
    private $_error_nicht_int = 1260;
    private $_error_daten_unvollstaendig = 1261;
    private $_error_anzahl_datensetze_falsch = 1262;

    // Tabellen / Views

    // Konditionen

    // Flags

    protected $trennungWoerter = null;
    protected $_stadtId = null;
    protected $_stadtBeschreibung = '';
    protected $_anzahlBloecke = null;

    public function __construct(){
        /** @var _tabelleStadtbeschreibung Application_Model_DbTable_stadtbeschreibung */
        $this->_tabelleStadtbeschreibung = new Application_Model_DbTable_stadtbeschreibung();
    }

    /**
     * @param $__stadtId
     * @return nook_ToolStadtbeschreibung
     * @throws nook_Exception
     */
    public function setStadtId($__stadtId){
        $stadtId = (int) $__stadtId;
        if(!is_int($stadtId))
            throw new nook_Exception($this->_error_nicht_int);

        $this->_stadtId = $stadtId;

        return $this;
    }

    /**
     * @param $trennungWoerter
     * @return nook_ToolStadtbeschreibung
     * @throws nook_Exception
     */
    public function setTrennungWoerter($trennungWoerter)
    {
        $this->trennungWoerter = $trennungWoerter;

        return $this;
    }

    /**
     * Gibt Stadtbeschreibung zurück
     *
     * @return string
     * @throws nook_Exception
     */
    public function getStadtbeschreibung(){

        if(empty($this->_stadtId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->_getStdtbeschreibung();

        return $this->_stadtBeschreibung;
    }

    /**
     * Ermittelt die tadtbeschreibung.
     * Berücksichtigt die Anzeigesprache.
     * Wenn vorgegeben wird der text gekürzt.
     *
     * @return string
     */
    private function _getStdtbeschreibung(){

        $kennzifferAnzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        $cols = array(
            'stadtbeschreibung'
        );

        $select = $this->_tabelleStadtbeschreibung->select();
        $select
            ->from($this->_tabelleStadtbeschreibung, $cols)
            ->where("sprache_id = ".$kennzifferAnzeigesprache)
            ->where("city_id = ".$this->_stadtId);

        $rows = $this->_tabelleStadtbeschreibung->fetchAll($select)->toArray();
        $anzahl = count($rows);

        // bearbeiten Stadtbeschreibung
        if( (!empty($rows[$anzahl - 1]['stadtbeschreibung'])) and (strlen($rows[$anzahl - 1]['stadtbeschreibung']) > 2) ){
            $stadtbeschreibung = $rows[$anzahl - 1]['stadtbeschreibung'];

            // kürzen Beschreibung
            if($this->trennungWoerter)
                $stadtbeschreibung = $this->_kappeStadtbeschreibung($stadtbeschreibung);
        }
        // keine Stadtbeschreibung vorhanden
        else{
            $stadtbeschreibung = false;
        }

        $this->_stadtBeschreibung = $stadtbeschreibung;

        return $this;
    }

    /**
     * Kürzt eine Stadtbeschreibung nach einer bestimmten Zeichenfolge
     *
     * @param $stadtBeschreibung
     * @return string
     */
    private function _kappeStadtbeschreibung($stadtBeschreibung)
    {
        $stadtBeschreibung = trim($stadtBeschreibung);

        $gekappteStadtbeschreibung = preg_split("#<br><br>#u",$stadtBeschreibung);

        return $gekappteStadtbeschreibung[0];
    }

    /**
     * Übernimmt die Anzahl
     * der anzuzeigenden Blöcke
     * der Stadtbeschreibung.
     *
     * @param $__anzahlBloecke
     * @return nook_ToolStadtbeschreibung
     * @throws nook_Exception
     */
    public function setAnzahlBloecke($__anzahlBloecke){

        $anzahlBloecke = (int) $__anzahlBloecke;
        if(empty($anzahlBloecke))
            throw new nook_Exception($this->_error_nicht_int);

        $this->_anzahlBloecke = $anzahlBloecke;

        return $this;
    }

    /**
     * Kappt den Langtext der Stadtbeschreibung
     * auf ein vorgegebene Anzahl von Blöcken
     *
     * @return nook_ToolStadtbeschreibung
     * @throws nook_Exception
     */
    public function ermittelnBloeckeStadtbeschreibung(){

        if( (empty($this->_stadtId)) or (empty($this->_anzahlBloecke)) or ($this->trennungWoerter > 0) )
            throw new nook_Exception($this->_error_daten_unvollstaendig);


        $this
            ->_getStdtbeschreibung()
            ->_ermittleBloeckeStadtbeschreibung();

        return $this;
    }

    /**
     *
     *
     * @return nook_ToolStadtbeschreibung
     */
    private function _ermittleBloeckeStadtbeschreibung(){

        $stadtbeschreibung = $this->_stadtBeschreibung;

        $position = strpos($stadtbeschreibung, "<b>");

        return $this;
    }




} // end class

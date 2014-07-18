<?php
/**
 * Checkt die Verfügbarkeit der gebuchten Programme
 * 
 * 
 * @author Stephan.Krauss
 * @date 15.05.13
 * @file WarenkorbProgrammeKapazitaet.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbProgrammeKapazitaet {

    // Error
    private $_error_anzahl_datensaetze_falsch = 1480;
    private $testXXX;

    //

    // Tabellen / Views
    private $_tabelleProgrammdetailsKapazitaet = null;
    private $_tabelleProgrammdetails = null;

    protected $_anzeigeSpracheKennziffer = null;
    protected $_stornofristInTage = null;

    public $programmDaten = array();

    /**
     * Übernimmt die Programmdaten, Tabellen einbinden, wandeln Datum
     *
     * @param array $programmDaten
     */
    function __construct ()
    {
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleProgrammdetailsKapazitaet Application_Model_DbTable_programmdetailsKapazitaet */
        $this->_tabelleProgrammdetailsKapazitaet = new Application_Model_DbTable_programmdetailsKapazitaet();

        $this->_anzeigeSpracheKennziffer = nook_ToolSprache::ermittelnKennzifferSprache();

        return;
    }

    /**
     * Übernahme der Daten des gebuchten Programmes
     *
     * @param array $programmDaten
     * @return Front_Model_WarenkorbProgrammekapazitaet
     */
    public function setProgrammDaten(array $programmDaten){

        $this->programmDaten = $programmDaten;

        return $this;
    }

    /**
     * Kontrolliert die kapazitaet des Programmes für den Tag / Uhrzeit und die Firmenkapazität
     *
     * + Tageskapazität / Uhrzeit
     * + Programmkapazität global
     *
     */
    public function checkKapazitaet()
    {
        // Kontrolle Stornofrist
        $kontrolleStornofrist = $this->_kontrolleStornofrist();
        if(empty($kontrolleStornofrist))
            return;

        // check Tageskapazität
        $checkKapazitaet = $this->_checkTageskapazitaet();

        // check Programmkapazität
        if(empty($checkKapazitaet))
            $checkKapazitaet = $this->_checkProgrammkapazitaet();

        // schaltet Status entsprechend der Kapazität
        $this->_schalteNeuenStatus($checkKapazitaet);

        return;
    }

    /**
     * Schaltet den Status in 'tbl_programmbuchung'
     *
     * + $checkKapazitaet = false , schalte auf ausgebucht
     *
     * @param $checkKapazitaet
     * @return mixed
     */
    private function _schalteNeuenStatus($checkKapazitaet){

        return $statusProgrammbuchung;
    }

    /**
     * Kontrolliert die Stornofrist
     *
     * eines Programmes. Wenn Änderungswunsch einer Buchung außerhalb
     * der Stornofrist, dann return true.
     * + Berechnung Datumsdifferenz in Tage
     * + wenn Differenz der Tage >= als Stornofrist in Tage
     *
     * @return bool
     */
    private function _kontrolleStornofrist(){
        $aenderungMoeglich = false;

        $cols = array(
            'stornofrist'
        );

        $whereProgrammdetailsId = "id = ".$this->programmDaten['programmdetails_id'];

        $select = $this->_tabelleProgrammdetails->select();
        $select
            ->from($this->_tabelleProgrammdetails, $cols)
            ->where($whereProgrammdetailsId);

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        // Berechnung Datumsdifferenz in Tage
        $datumJetzt = date("Y-m-d");
        $datumObjectJetzt = new DateTime($datumJetzt);
        $datumObjectProgrammstart = new DateTime($this->programmDaten['datum']);
        $datumsDifferenz = date_diff($datumObjectJetzt, $datumObjectProgrammstart);

        // wenn Differenz der Tage >= als Stornofrist in Tage
        if($datumsDifferenz->invert === 0){
            $differenzTage = $datumsDifferenz->d;
            if($differenzTage >= $rows[0]['stornofrist'])
                $aenderungMoeglich = true;
        }

        return $aenderungMoeglich;
    }

    /**
     * Kontrolliert die Kapazität des Programmes.
     *
     * Wenn die Programmkapazität größer oder gleich der
     * gewünschten Kapazität, dann return = true
     *
     * @return bool
     * @throws nook_Exception
     */
    private function _checkProgrammkapazitaet()
    {
        $checkKapazitaet = false;

        $cols = array(
            'kapazitaet'
        );

        $whereProgrammdetailsId = "id = ".$this->programmDaten['programmdetails_id'];

        $select = $this->_tabelleProgrammdetails->select();
        $select
            ->from($this->_tabelleProgrammdetails, $cols)
            ->where($whereProgrammdetailsId);

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        // Kontrolle Programmkapazität
        if($rows[0]['kapazitaet'] >= $this->programmDaten['anzahl'])
            $checkKapazitaet = true;


        return $checkKapazitaet;
    }

    /**
     * Kontrolle Tageskapazität eines Programmes
     *
     * + Hat das Programm eine Tageskapazität ?
     * + Ist die Tageskapazität ausreichend ?
     * + Hat das Programm eine Tageskapazität gekoppelt mit einer Uhrzeit
     *
     * @return bool
     */
    private function _checkTageskapazitaet(){
        $checkKapazitaet = false;

        $cols = array(
            'kapazitaet'
        );

        $whereProgrammdetailsId = "programmdetails_id = '".$this->programmDaten['programmdetails_id']."'";
        $whereDatum = "datum = '".$this->programmDaten['datum']."'";

        $select = $this->_tabelleProgrammdetailsKapazitaet->select();
        $select
            ->from($this->_tabelleProgrammdetailsKapazitaet, $cols)
            ->where($whereProgrammdetailsId)
            ->where($whereDatum);

        $rows = $this->_tabelleProgrammdetailsKapazitaet->fetchAll($select)->toArray();

        // Kontrolle der Tageskapazität
        if(count($rows) == 1){
            if($rows[0]['kapazitaet'] >= $this->programmDaten['anzahl'])
                $checkKapazitaet = true;
        }

        if(count($rows) > 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);

        return $checkKapazitaet;
    }

    private function _checkTabelleProgrammdetails(){
        $checkKapazitaet = false;

        return $checkKapazitaet;
    }

    private function _setStatusAusgebucht(){

    }

} // end class

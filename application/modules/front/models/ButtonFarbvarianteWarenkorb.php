<?php 
/**
* Ermittlung der Farbvariante der Button des Warenkorbes
*
* + Servicecontainer mit Tabellen
* + Ermittelt die Farbvariante der Button im Warenkorb
* + Ermittelt die Anzahl der Programme, die sich im Warenkorb befinden
* + Ermittelt die Anzahl der Programme, die sich im Warenkorb befinden
*
* @date 17.09.13
* @file ButtonFarbvarianteWarenkorb.php
* @package front
* @subpackage model
*/
class Front_Model_ButtonFarbvarianteWarenkorb
{
    // Fehler
    private $error_anfangsangaben_unvollstaendig = 2130;

    // Konditionen
    private $condition_buttonvariante_bestandsbuchung_wird_bearbeitet = 5;
    private $condition_buttonvariante_programme_gebucht = 1;
    private $condition_buttonvariante_uebernachtungen_gebucht = 3;
    private $condition_buttonvariante_standard = 1;

    private $condition_aktueller_inhalt_warenkorb_zaehler = 0;

    // Meldungen

    // Flags

    protected $pimple = null;
    protected $farbVariante = 1;
    protected $buchungsnummerId = null;
    protected $zaehlerId = null;

    public function __construct()
    {

    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_ButtonFarbvarianteWarenkorb
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * @param $zaehlerId
     * @return Front_Model_ButtonFarbvarianteWarenkorb
     */
    public function setZaehlerId($zaehlerId)
    {
        $this->zaehlerId = $zaehlerId;

        return $this;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_ButtonFarbvarianteWarenkorb
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getFarbVariante()
    {
        return $this->farbVariante;
    }

    /**
     * Servicecontainer mit Tabellen
     */
    private function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleBuchungsnummer')){
            $this->pimple['tabelleBuchungsnummer'] = function(){
                return new Application_Model_DbTable_buchungsnummer();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProgrammbuchung')){
            $this->pimple['tabelleProgrammbuchung'] = function(){
                return new Application_Model_DbTable_programmbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleHotelbuchung')){
            $this->pimple['tabelleHotelbuchung'] = function(){
                return new Application_Model_DbTable_hotelbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProduktbuchung')){
            $this->pimple['tabelleProduktbuchung'] = function(){
                return new Application_Model_DbTable_produktbuchung();
            };
        }

        return;
    }

    /**
     * Ermittelt die Farbvariante der Button im Warenkorb
     *
     * + Kontrolle ob es eine Bestandsbuchung ist
     *
     * @return Front_Model_ButtonFarbvarianteWarenkorb
     */
    public function ermittelnFarbvarianteButtonWarenkorb()
    {
        if( (empty($this->buchungsnummerId)) or ( (empty($this->zaehlerId)) and ($this->zaehlerId != 0) ) )
            throw new nook_Exception($this->error_anfangsangaben_unvollstaendig);

        $this->servicecontainer();

        // wenn es eine Bestandsbuchung ist
        if($this->zaehlerId > 0){
            $this->farbVariante = $this->condition_buttonvariante_bestandsbuchung_wird_bearbeitet;
        }
        // neue Buchung
        else{
            $anzahlProgrammbuchungen = $this->ermittelnAnzahlProgrammbuchungen();
            $anzahlHotelbuchungen = $this->ermittelnAnzahlHotelbuchungen();

            if( ($anzahlProgrammbuchungen > 0) and ($anzahlHotelbuchungen == 0) )
                $this->farbVariante = $this->condition_buttonvariante_programme_gebucht;
            elseif( ($anzahlProgrammbuchungen == 0) and ($anzahlHotelbuchungen > 0) )
                $this->farbVariante = $this->condition_buttonvariante_uebernachtungen_gebucht;
            else
                $this->farbVariante = $this->condition_buttonvariante_standard;
        }

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Programme, die sich im Warenkorb befinden
     *
     * @return int
     */
    private function ermittelnAnzahlProgrammbuchungen()
    {
        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereAktuellerWarenkorbZaehler = "zaehler = ".$this->condition_aktueller_inhalt_warenkorb_zaehler;

        $select = $tabelleProgrammbuchung->select();
        $select
            ->from($tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereAktuellerWarenkorbZaehler);

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Anzahl der Programme, die sich im Warenkorb befinden
     *
     * @return int
     */
    private function ermittelnAnzahlHotelbuchungen()
    {
        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereAktuellerWarenkorbZaehler = "zaehler = ".$this->condition_aktueller_inhalt_warenkorb_zaehler;

        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereAktuellerWarenkorbZaehler);

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }
}

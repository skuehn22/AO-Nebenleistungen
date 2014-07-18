<?php
/**
 * Kontrolliert die Gruppenstaerke einer Hotelbuchung
 *
 * + Ermittelt die Gesamtanzahl Personen einer Buchung / Teilrechnung
 * + Ermittelt die personenanzahl einer gebuchten Rate
 * + Gibt Information ob Gruppenstaerke unterschritten
 * 
 * 
 * @author stephan.krauss
 * @date 27.05.13
 * @file GruppenstaerkeHotelbuchung.php
 * @package front
 * @subpackage model
 */
class Front_Model_GruppenstaerkeHotelbuchung {

    // Error
    private $_error_anfangsdaten_fehlen = 1530;

    // Tabelle / Views

    // Konditionen
    private $_condition_mindest_anzahl_personen_einer_hotelbuchung = 10;

    // Flags
    private $_flagGruppenstaerkeErreicht = false;

    protected $_anzahlPersonenTeilbuchung = null;
    protected $_anzahlPersonenGebuchteRate = null;
    protected $_hotelbuchungId = null;
    protected $_teilrechnungId = null;

    function __construct (){}

    /**
     * @param $hotelbuchungId
     * @return Front_Model_GruppenstaerkeHotelbuchung
     */
    public function setHotelbuchungId ($hotelbuchungId)
    {
        $hotelbuchungId = (int) $hotelbuchungId;
        $this->_hotelbuchungId = $hotelbuchungId;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFlagGruppenstaerkeErreicht ()
    {
        return $this->_flagGruppenstaerkeErreicht;
    }

    /**
     * Ermittelt die Werte der gebuchten Rate
     *
     * Gibt Personenanzahl der gebuchten Rate zurück.
     *
     * @return int
     */
    private function _ermittelnPersonenanzahlHotelbuchung()
    {
        $toolHotelbuchung = new nook_ToolHotelbuchung();
        $datenHotelbuchung = $toolHotelbuchung
            ->setHotelbuchungId($this->_hotelbuchungId)
            ->ermittelnDatenHotelbuchung()
            ->getDatenHotelbuchung();

        $this->_anzahlPersonenGebuchteRate = $datenHotelbuchung['personNumbers'];
        $this->_teilrechnungId = $datenHotelbuchung['teilrechnungen_id'];

        return $datenHotelbuchung['personNumbers'];
    }

    /**
     * Ermittelt die Gesamtpersonenanzahl einer Hotelbuchung / Teilrechnung
     *
     * @return int
     */
    private function _ermittelnGesamtanzahlPersonenTeilrechnung()
    {
        $toolTeilrechnungPersonenanzahl = new nook_ToolTeilrechnungPersonenanzahl();
        $personenAnzahlTeilrechnung = $toolTeilrechnungPersonenanzahl
            ->setTeilrechnungId($this->_teilrechnungId)
            ->ermittelnPersonenanzahlTeilrechnung()
            ->getPersonenanzahl();

        $this->_anzahlPersonenTeilbuchung = $personenAnzahlTeilrechnung;

        return $personenAnzahlTeilrechnung;
    }

    /**
     * Kontrolliert die Mindestgruppenstärke einer Hotelbuchung / Teilrechnung
     *
     * @return Front_Model_GruppenstaerkeHotelbuchung
     * @throws nook_Exception
     */
    public function kontrolleGruppenstaerke(){

        if(empty($this->_hotelbuchungId))
            throw new nook_Exception($this->_error_anfangsdaten_fehlen);

        $this->_kontrolleGruppenstaerke();

        return $this;
    }

    /**
     * Kontrolliert ob die Mindestgruppenstärke unterschritten wurde.
     *
     * + return true , Mindestgruppenstärke erreicht
     * + return false , zu wenig Personen
     *
     * @return Front_Model_GruppenstaerkeHotelbuchung
     */
    private function _kontrolleGruppenstaerke()
    {
        $anzahlPersonenHotelbuchung = $this->_ermittelnPersonenanzahlHotelbuchung();
        $anzahlPersonenTeilrechnung = $this->_ermittelnGesamtanzahlPersonenTeilrechnung();

        if( ($anzahlPersonenTeilrechnung - $anzahlPersonenHotelbuchung) < $this->_condition_mindest_anzahl_personen_einer_hotelbuchung)
            $this->_flagGruppenstaerkeErreicht = false;
        else
            $this->_flagGruppenstaerkeErreicht = true;

        return $this;
    }







} // end class

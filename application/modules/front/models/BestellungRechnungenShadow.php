<?php
/**
 * Teilbereich eintragen der Buchungsdatensaetze in die Tabelle 'tbl_rechnungen'
 *
 * @author Stephan Krauss
 * @date 31.03.2014
 * @file BestellungRechnungenShadow.php
 * @project HOB
 * @package front
 * @subpackage shadow
 */

class  Front_Model_BestellungRechnungenShadow
{
    protected $pimple = null;

    /**
     * BestellungRechnungenShadow Bestellung Rechnungen Shadow
     *
     * @return Front_Model_BestellungRechnungenShadow
     */
    public function BestellungRechnungenStore()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleHotelbuchung'] = function($c){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function($c){
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple['tabelleProgrammbuchung'] = function($c){
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleProducts'] = function($c){
            return new Application_Model_DbTable_products(array('db' => 'hotels'));
        };

        $pimple['tabellePreise'] = function($c){
            return new Application_Model_DbTable_preise();
        };

        $pimple['tabelleRechnungen'] = function($c){
            return new Application_Model_DbTable_rechnungen();
        };

        $pimple['tabelleRechnungenMwst'] = function($c){
            return new Application_Model_DbTable_rechnungenMwst();
        };

        $pimple['tabelleProgrammdetails'] = function($c){
            return new Application_Model_DbTable_programmedetails();
        };

        $this->pimple = $pimple;

        return $this;
    }

    /**
     * @param $aktuelleBuchungsnummer
     * @return Front_Model_BestellungRechnungenShadow
     */
    public function setAktuelleBuchungsnummer($aktuelleBuchungsnummer)
    {
        $this->pimple['aktuelleBuchungsnummer'] = $aktuelleBuchungsnummer;
        $this->pimple['buchungsnummer_id'] = $aktuelleBuchungsnummer;

        return $this;
    }

    /**
     * @param $aktuelleKundenId
     * @return Front_Model_BestellungRechnungenShadow
     */
    public function setAktuelleKundenId($aktuelleKundenId)
    {
        $this->pimple['aktuelleKundenId'] = $aktuelleKundenId;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestellungRechnungenShadow
     */
    public function setZaehler($zaehler)
    {
        $this->pimple['zaehler'] = $zaehler;

        return $this;
    }

    /**
     * Trägt die Daten in die Tabelle Rechnungen ein
     */
    public function eintragenTabelleRechnungen()
    {
        $frontModelRechnungen = new Front_Model_Rechnungen();
        $frontModelRechnungen
            ->setPimple($this->pimple)
            ->steuerungEintragenRechnungen();
    }
}
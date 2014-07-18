<?php
/**
* Eintragen der Zahlungen an die Hotel Anbieter in die Tabelle 'tbl_zahlungen'
*
* + Initialisierung Tabellen
* + Einlesen Model der DatensÃ¤tze
* + Eintragen der daten in
* + Eintragen der Hotelbuchungen
* + Ermittelt vertraglich festgelegte Gewinnspanne
* + Berechnet den Nettopreis der
* + Berechnet den Bruttoverkaufspreis
* + Findet die Adressen Id eines Hotels
* + Eintragen der Zahlungsdaten Hotelbuchung
* + Setzt den Bearbeitungsstatus
*
* @date 02.57.2013
* @file BestellungZahlungenHotelbuchungen.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungZahlungenHotelbuchungen extends nook_ToolModel implements arrayaccess{

    protected $_datenRechnungen = array();
    protected $_datenHotelbuchungen = array();
    protected $_datenProduktbuchungen = array();
    protected $_datenProgrammbuchungen = array();

    protected $aktulleBuchungsnummer = null;
    protected $aktuelleZaehler = null;

    // Errors
    private $_error_datensaetze_der_buchungen_nicht_vorhanden = 1040;

    // Konditionen
    private $_condition_status_eintrag_zahlungstabelle = 5; // in Tabelle 'tbl_zahlungen' registriert
    private $_condition_status_zu_zahlen = 1;

    // Tabellen und Views
    private $_tabelleZahlungen = null;
    private $_tabelleProperties = null;
    private $_tabelleHotelbuchung = null;
    private $_tabelleAdressen = null;

    /**
     * Initialisierung Tabellen
     * und Views
     *
     */
    public function __construct(){

        /** @var _tabelleZahlungen Application_Model_DbTable_zahlungen */
        $this->_tabelleZahlungen = new Application_Model_DbTable_zahlungen();
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();

        return;
    }

    /**
     * @param $aktuelleZaehler
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    public function setAktuellerZaehler($aktuelleZaehler)
    {
        $aktuelleZaehler = (int) $aktuelleZaehler;
        $this->aktuelleZaehler = $aktuelleZaehler;

        return $this;
    }

    /**
     * @param $aktulleBuchungsnummer
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    public function setAktuelleBuchungsnummer($aktulleBuchungsnummer)
    {
        $aktulleBuchungsnummer = (int) $aktulleBuchungsnummer;
        $this->aktulleBuchungsnummer = $aktulleBuchungsnummer;

        return $this;
    }

    /**
     * Einlesen Model der Datensätze
     *
     * @param $__fremdModel
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    public function setModelData(Front_Model_Bestellung $__fremdModel){

        $this->_importModelData($__fremdModel);

        return $this;
    }

    /**
     * Eintragen der daten in
     * die Tabelle Zahlungen
     */
    public function eintragenHotelbuchungenTabelleZahlungen(){

        try{

            if(empty($this->_modelData))
                throw new nook_Exception($this->_error_datensaetze_der_buchungen_nicht_vorhanden);

            // allgemeine Rechnungsdaten
            $this->_datenRechnungen = $this->offsetGet('_datenRechnungen');

            // Hotelbuchungen eintragen
            $this->_verarbeitungHotelbuchungen(); // Hotelbuchungen
        }
        catch(Exception $e){

            // Exception der Model
            if(get_class($e) == 'nook_Exception'){
                switch($e->getMessage()){
                    default:
                        throw $e;
                        break;
                }
            }
            // Exception des Framework
            else{
                throw $e;
            }
        }
    }

    /**
     * Eintragen der Hotelbuchungen
     * in die Tabelle 'tbl_zahlungen'
     *
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    private function _verarbeitungHotelbuchungen(){
        $this->_datenHotelbuchungen = $this->offsetGet('_datenHotelbuchungen');

        for($i=0; $i < count( $this->_datenHotelbuchungen ); $i++){
            $this
                ->_findeIdAdressdatensatz($i)
                ->_berechneGesamtBruttoVerkaufspreisHotelbuchungen($i) // berechnet Gesamt Bruttopreis Hotelbuchung;
                ->_bestimmeGewinnspanne($i) // bestimmen der Gewinnspanne und Bruttozahlung an Hotelbuchung
                ->_bestimmeNettoZahlungHotelbuchungAnbieter($i) // bestimmt Nettozahlung an Anbieter
                ->_eintragenHotelZahlung($i); // eintragen berechnete Daten Hotelbuchung in 'tbl_zahlung'
        }

        return $this;
    }

    /**
     * Ermittelt vertraglich festgelegte Gewinnspanne
     * mit dem Hotel
     *
     * @param $i
     */
    private function _bestimmeGewinnspanne($i){

        $cols = array(
            'gewinnspanne'
        );

        $select = $this->_tabelleProperties->select();
        $select
            ->from($this->_tabelleProperties, $cols)
            ->where("id = ".$this->_datenHotelbuchungen[$i]['propertyId']);

        $row = $this->_tabelleProperties
                    ->fetchRow($select)
                    ->toArray();

        // Berechnung Gewinnspanne und Zahlung an Hotelanbieter
        $gewinnspanne = ($this->_datenHotelbuchungen[$i]['bruttoVerkaufspreis'] / 100) *  $row['gewinnspanne'];
        $this->_datenHotelbuchungen[$i]['bruttoZahlung'] = $this->_datenHotelbuchungen[$i]['bruttoVerkaufspreis'] - $gewinnspanne;

        return $this;
    }

    /**
     * Berechnet den Nettopreis der
     * Zahlung an den Anbieter
     *
     * @param $i
     *
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    private function _bestimmeNettoZahlungHotelbuchungAnbieter($i){

        $this->_datenHotelbuchungen[$i]['nettoZahlung'] = ($this->_datenHotelbuchungen[$i]['bruttoZahlung'] / (100 + $this->_datenHotelbuchungen[$i]['mwst'])) * 100;

        return $this;
    }

    /**
     * Berechnet den Bruttoverkaufspreis
     * einer Übernachtung
     *
     * @param $i
     */
    private function _berechneGesamtBruttoVerkaufspreisHotelbuchungen($i){

        if(empty($this->_datenHotelbuchungen[$i]['personPrice']))
            $bruttoVerkaufspreis = $this->_datenHotelbuchungen[$i]['roomNumbers'] * $this->_datenHotelbuchungen[$i]['nights'] * $this->_datenHotelbuchungen[$i]['roomPrice'];
        else
            $bruttoVerkaufspreis = $this->_datenHotelbuchungen[$i]['personNumbers'] * $this->_datenHotelbuchungen[$i]['nights'] * $this->_datenHotelbuchungen[$i]['personPrice'];

        $this->_datenHotelbuchungen[$i]['bruttoVerkaufspreis'] = $bruttoVerkaufspreis;

        return $this;
    }

    /**
     * Findet die Adressen Id eines Hotels
     *
     * @param $i
     */
    private function _findeIdAdressdatensatz($i){

        $cols = array(
            'id'
        );

        $select = $this->_tabelleAdressen->select();
        $select
            ->from($this->_tabelleAdressen, $cols)
            ->where("properties_id = ".$this->_datenHotelbuchungen[$i]['propertyId']);

        $row = $this->_tabelleAdressen
                    ->fetchRow($select)
                    ->toArray();

        $this->_datenHotelbuchungen[$i]['adressen_id'] = $row['id'];

        return $this;
    }

    /**
     * Eintragen der Zahlungsdaten Hotelbuchung
     * in Tabelle 'tbl_zahlungen'.
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenHotelbuchungen
     */
    private function _eintragenHotelZahlung($i){

        $toolRegistriegungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistriegungsNummer->steuerungErmittelnRegistrierungsnummerMitSession()->getRegistrierungsnummer();

        $insert = array(
            "buchungsnummer_id" => $this->_datenHotelbuchungen[$i]['buchungsnummer_id'],
            "adressen_id" => $this->_datenHotelbuchungen[$i]['adressen_id'],
            "rechnungsnummer" => $this->_datenRechnungen['rechnungsnummer'],
            "rechnungsstatus" => $this->_condition_status_zu_zahlen,
            "mwst" => $this->_datenHotelbuchungen[$i]['mwst'],
            "brutto" => $this->_datenHotelbuchungen[$i]['bruttoZahlung'],
            "netto" => $this->_datenHotelbuchungen[$i]['nettoZahlung'],
            'hobNummer' => $registrierungsnummer
        );

        if( $this->_tabelleZahlungen->insert($insert) )
            $this->_setzeStatusTabelleHotelbuchung($i);


        return $this;
    }

    /**
     * Setzt den Bearbeitungsstatus
     * in der Tabelle 'tbl_hotelbuchung'
     *
     * @param $i
     */
    private function _setzeStatusTabelleHotelbuchung($i){

        $update = array(
            "status" => $this->_condition_status_eintrag_zahlungstabelle
        );

        $this->_tabelleHotelbuchung->update($update,"id = ".$this->_datenHotelbuchungen[$i]['id']);

        return;
    }

} // end class

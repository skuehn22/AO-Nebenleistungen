<?php
/**
* Eintragen der Zahlungen Produkte eines Hotels an die Anbieter in die Tabelle 'tbl_zahlungen'
*
* + Initialisierung Tabellen
* + Einlesen Model der DatensÃ¤tze
* + Eintragen der Daten in
* + Eintragen der Hotelbuchungen
* + Ermittelt die ID des
* + Ermittelt Adressen ID
* + Ermittelt die vereinbarte
* + Berechnet den Netto Zahlungspreis
* + TrÃ¤gt die Zahlungen an den
* + Setzt Status in 'tbl_produktbuchung'
*
* @date 02.00.2013
* @file BestellungZahlungenProduktbuchungen.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungZahlungenProduktbuchungen extends nook_ToolModel implements arrayaccess{

    protected $_datenRechnungen = array();
    protected $_datenProduktbuchungen = array();
    protected $aktulleBuchungsnummer = null;
    protected $aktuelleZaehler = null;

    // Errors
    private $_error_keine_adresse_vorhanden = 1050;
    private $_error_kein_hotel_vorhanden = 1051;
    private $_error_keine_rechnungsdaten_vorhanden = 1052;
    private $_error_tabelle_produktbuchung_fehlgeschlagen = 1053;
    private $_error_kein_produkt_vorhanden = 1054;
    private $_error_eintragen_zahlungen_fehlgeschlagen = 1055;
    private $_error_kein_eindeutiger_adressdatensatz = 1056;

    // Konditionen
    private $_condition_eingetragen_in_zahlungen = 5;

    // Tabellen und Views
    private $_tabelleAdressen = null;
    private $_tabelleProperties = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleProducts = null;
    private $_tabelleZahlungen = null;

    /**
     * Initialisierung Tabellen
     * und Views
     *
     */
    public function __construct(){

        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array("db" => "hotels"));
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array("db" => "hotels"));
        /** @var _tabelleZahlungen Application_Model_DbTable_zahlungen */
        $this->_tabelleZahlungen = new Application_Model_DbTable_zahlungen();

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
     * @return Front_Model_BestellungZahlungen
     */
    public function setModelData(Front_Model_Bestellung $__fremdModel){

        $this->_importModelData($__fremdModel);

        return $this;
    }

    /**
     * Eintragen der Daten in
     * die Tabelle Zahlungen
     */
    public function eintragenProduktbuchungenTabelleZahlungen(){

        try{

            if(empty($this->_modelData))
                throw new nook_Exception($this->_error_datensaetze_der_buchungen_nicht_vorhanden);

            // allgemeine Rechnungsdaten
            $this->_datenRechnungen = $this->offsetGet('_datenRechnungen');

            // Buchungsdaten Produkte
            $this->_datenProduktbuchungen = $this->offsetGet('_datenProduktbuchungen');
            if(empty($this->_datenProduktbuchungen))
                return false;

            // Produktbuchungen eintragen
            $this->_verarbeitungProduktbuchungen(); // Produktbuchung

            return true;
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
     * @return Front_Model_BestellungZahlungen
     */
    private function _verarbeitungProduktbuchungen(){
        $this->_datenProduktbuchungen = $this->offsetGet('_datenProduktbuchungen');

        for($i=0; $i < count( $this->_datenProduktbuchungen ); $i++){
            $this
                ->_ermittelnPropertyId($i)
                ->_ermittelnAdressenIdAnbieter($i)
                ->_ermittelnGewinnspanneHotel($i) // vereinbarte Gewinnspanne
                ->_berechnungNettoUndBruttoZahlung($i) // Netto und Brutto Zahlung an Anbieter
                ->_eintragenTabelleZahlung($i) // eintragen in Tabelle Zahlung
                ->_setzeStatusProduktbuchungen($i); // setzen Status 'tbl_produktbuchung'
        }

        return $this;
    }

    /**
     * Ermittelt die ID des
     * Anbieters des Programmes
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     * @throws nook_Exception
     */
    private function _ermittelnPropertyId($i){

        $produktId = $this->_datenProduktbuchungen[$i]['products_id'];

        /** @var $result Zend_Db_Table_Rows */
        $result = $this->_tabelleProducts->find($produktId);
        if(!is_object($result))
            throw new nook_Exception($this->_error_kein_produkt_vorhanden);
        else{
            $rows = $result->toArray();
        }

        $this->_datenProduktbuchungen[$i]['properties_id'] = $rows[0]['property_id'];

        return $this;
    }


    /**
     * Ermittelt Adressen ID
     * des Anbieter
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     */
    private function _ermittelnAdressenIdAnbieter($i){

        $propertyId = $this->_datenProduktbuchungen[$i]['properties_id'];

        $cols = array(
            "id"
        );

        $select = $this->_tabelleAdressen->select();
        $select
            ->from($this->_tabelleAdressen, $cols)
            ->where("properties_id = ".$propertyId);

        /** @var $rows Zend_Db_Table_Rows */
        $rows = $this->_tabelleAdressen->fetchAll($select);

        if(!is_object($rows[0]) or count($rows) <> 1)
            throw new nook_Exception($this->_error_kein_eindeutiger_adressdatensatz);

        $adressdatensatz = $rows[0]->toArray();
        $this->_datenProduktbuchungen[$i]['adressen_id'] = $adressdatensatz['id'];

        return $this;
    }


    /**
     * Ermittelt die vereinbarte
     * Gewinnspanne eines Hotels
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     * @throws nook_Exception
     */
    private function _ermittelnGewinnspanneHotel($i){

        $propertiesId = $this->_datenProduktbuchungen[$i]['properties_id'];

        $cols = array(
            "gewinnspanne"
        );

        $select = $this->_tabelleProperties->select();
        $select
            ->from($this->_tabelleProperties, $cols)
            ->where("id = ".$propertiesId);

        /** @var $result Zend_Db_Table_Row */
        $result = $this->_tabelleProperties->fetchRow($select);

        if(is_object($result)){
            $row = $result->toArray();

            $this->_datenProduktbuchungen[$i]['gewinnspanne'] = $row['gewinnspanne'];
        }
        else
            throw new nook_Exception($this->_error_kein_hotel_vorhanden);

        return $this;
    }

    /**
     * Berechnet den Netto Zahlungspreis
     * und den
     * Brutto Zahlungspreis
     * an den Anbieter / Hotel
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     */
    private function _berechnungNettoUndBruttoZahlung($i){

        $zahlungProzenteAnAnbiter = 100 - $this->_datenProduktbuchungen[$i]['gewinnspanne'];

        $zahlungBrutto = ($this->_datenProduktbuchungen[$i]['summeProduktPreis'] / 100) * $zahlungProzenteAnAnbiter;
        $this->_datenProduktbuchungen[$i]['brutto'] = $zahlungBrutto;

        $zahlungNetto = $zahlungBrutto / (100 + $this->_datenProduktbuchungen[$i]['mwst']) * 100;
        $zahlungNetto = number_format($zahlungNetto,2); // runden auf 2 Nachkomma

        $this->_datenProduktbuchungen[$i]['netto'] = $zahlungNetto;

        return $this;
    }

    /**
     * Trägt die Zahlungen an den
     * Anbieter in 'tbl_zahlung' ein
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     */
    private function _eintragenTabelleZahlung($i)
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsNummer = $toolRegistrierungsNummer->steuerungErmittelnRegistrierungsnummerMitSession()->getRegistrierungsnummer();

        $insert = array(
            "buchungsnummer_id" => $this->_datenRechnungen['buchungsnummer_id'],
            "adressen_id" => $this->_datenProduktbuchungen[$i]['adressen_id'],
            "rechnungsnummer" => $this->_datenRechnungen['rechnungsnummer'],
            "netto" => $this->_datenProduktbuchungen[$i]['netto'],
            "mwst" => $this->_datenProduktbuchungen[$i]['mwst'],
            "brutto" => $this->_datenProduktbuchungen[$i]['brutto'],
            "hobNummer" => $registrierungsNummer
        );

        $kontrolle = $this->_tabelleZahlungen->insert($insert);
        if(empty($kontrolle))
            throw new nook_Exception($this->_error_eintragen_zahlungen_fehlgeschlagen);

        return $this;
    }

    /**
     * Setzt Status in 'tbl_produktbuchung'
     *
     * @param $i
     * @return Front_Model_BestellungZahlungenProduktbuchungen
     * @throws nook_Exception
     */
    private function _setzeStatusProduktbuchungen($i){

        $idTabelleproduktbuchung = $this->_datenProduktbuchungen[$i]['id'];
        $where = "id = ".$idTabelleproduktbuchung;

        $update = array(
            "status" => $this->_condition_eingetragen_in_zahlungen
        );

        $result = $this->_tabelleProduktbuchung->update($update, $where);

        if(empty($result))
            throw new nook_Exception($this->_error_tabelle_produktbuchung_fehlgeschlagen);

        return $this;
    }

} // end class

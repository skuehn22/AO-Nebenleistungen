<?php
/**
* Eintragen der Zahlungen Programme an die Anbieter in die Tabelle 'tbl_zahlungen'
*
* + Initialisierung Tabellen
* + Setzen Zaehler der Rechnungsnummer
* + Einlesen Model der Datensätze
* + Eintragen der Programmbuchungen in die
* + Eintragen der Programmbuchungen
* + Ermitteln ID des Programmanbieters
* + Ermittelt den Netto Einkaufspreis des Programmes
* + Ermittelt den Nettopreis aus der Tabelle 'tbl_preise'
* + Ermittelt den Mehrwertsteuersatz Verkauf
* + Setzt den Rechnungsstatus auf zu zahlen
* + Eintragen Werte in 'tbl_zahlungen'
*
* @date 02.02.2013
* @file BestellungZahlungenProgrammbuchungen.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungZahlungenProgrammbuchungen extends nook_ToolModel implements arrayaccess
{

    protected $_datenRechnungen = array();
    protected $_datenProgrammbuchungen = array();

    protected $_rechnungsnummer_zaehler = null;
    protected $buchungsnummer = null;

    protected $condition_preisvariante_buchungspauschale = 442;

    private $_mapZahlungen = array(
        "adressen_id" => 'adressen_id',
        "buchungsnummer_id" => 'buchungsnummer_id',
        "rechnungsnummer" => 'rechnungsnummer',
        "rechnungsdatum" => null,
        "rechnungsstatus" => 'status',
        "netto" => 'netto',
        "mwst" => 'mwst',
        "brutto" => 'brutto',
        "zahldatum" => null,
        "betrag" => null
    );

    // Errors
    private $_error_anzahl_datensaetze_falsch = 1960;

    // Konditionen
    private $_condition_eingetragen_in_zahlungen = 5;
    private $_condition_rechnungsstatus_zu_zahlen = 1;

    // Tabellen und Views
    private $_tabelleAdressen = null;
    private $_tabelleProgrammbuchung = null;
    private $_tabelleProgrammdetails = null;
    private $_tabelleZahlungen = null;
    private $_tabellePreise = null;

    /**
     * Initialisierung Tabellen
     * und Views

     */
    public function __construct()
    {

        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
        /** @var _tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleZahlungen Application_Model_DbTable_zahlungen */
        $this->_tabelleZahlungen = new Application_Model_DbTable_zahlungen();
        /** @var _tabellePreise Application_Model_DbTable_preise */
        $this->_tabellePreise = new Application_Model_DbTable_preise();

        return;
    }

    /**
     * Setzen Zaehler der Rechnungsnummer
     *
     * @param $rechnungsnummer_zaehler
     * @return Front_Model_BestellungZahlungenProgrammbuchungen
     */
    public function setAktuellerZaehler($rechnungsnummer_zaehler)
    {
        $rechnungsnummer_zaehler = (int) $rechnungsnummer_zaehler;
        $this->_rechnungsnummer_zaehler = $rechnungsnummer_zaehler;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_BestellungZahlungenProgrammbuchungen
     */
    public function setAktuelleBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * Einlesen Model der Datensätze
     *
     * @param $__fremdModel
     * @return Front_Model_BestellungZahlungenProgrammbuchungen
     */
    public function setModelData($__fremdModel)
    {

        $this->_importModelData($__fremdModel);
        $this->_datenProgrammbuchungen = $this->_modelData['_datenProgrammbuchungen'];

        return $this;
    }

    /**
     * Eintragen der Programmbuchungen in die
     * Tabelle 'tbl_zahlungen'
     *
     * @return Front_Model_BestellungZahlungenProgrammbuchungen
     */
    public function eintragenProgrammbuchungenTabelleZahlungen()
    {
        $this->_verarbeitungProduktbuchungen();

        return $this;
    }

    /**
     * Eintragen der Programmbuchungen
     * in die Tabelle 'tbl_zahlungen'
     */
    private function _verarbeitungProduktbuchungen()
    {

        for ($i = 0; $i < count($this->_datenProgrammbuchungen); $i++) {

            $this->_ermittelnAdressenIdAnbieter($i); // ID Programmanbieter
            $this->_ermittelnBruttoUndMwstProgramm($i); // Netto und Brutto Zahlung an Anbieter
            $this->_berechnenNetto($i); // Berechnung Netto Einkaufspreis
            $this->_setzeRechnungsstatus($i); // setzen Status 'tbl_produktbuchung'
            $this->_setzeRechnungsnummer($i); // setzen der Rechnungsnummer
            $this->_eintragenTabelleZahlung($i); // eintragen in Tabelle Zahlung

        }

        return;
    }

    /**
     * Ermitteln ID des Programmanbieters
     *
     * @param $i
     * @return int
     */
    private function _ermittelnAdressenIdAnbieter($i)
    {

        $cols = array(
            'adressen_id'
        );

        $select = $this->_tabelleProgrammdetails->select();
        $select
            ->from($this->_tabelleProgrammdetails, $cols)
            ->where("id = " . $this->_datenProgrammbuchungen[$i]['programmdetails_id']);

        $rows = $this->_tabelleProgrammdetails->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);
        }

        $this->_datenProgrammbuchungen[$i]['adressen_id'] = $rows[0]['adressen_id'];

        return $rows[0]['adressen_id'];
    }

    /**
     * Ermittelt den Netto Einkaufspreis des Programmes
     * aus Brutto Einkaufspreis  und Mwst
     * des Programmes. Anpassung der Mwst auf einen 'Int' Wert.
     *
     * @param $i
     * @return float
     */
    private function _berechnenNetto($i)
    {

        $bruttoEinkaufspreis = $this->_datenProgrammbuchungen[$i]['brutto'];
        $mwst = $this->_datenProgrammbuchungen[$i]['mwst'];

        $mwst = $mwst * 100;

        $preiseArray = nook_ToolPreise::bestimmeNettopreis($bruttoEinkaufspreis, $mwst);
        $this->_datenProgrammbuchungen[$i]['netto'] = number_format($preiseArray['netto'], 2);

        return $preiseArray['netto'];
    }

    /**
     * Ermittelt den Nettopreis aus der Tabelle 'tbl_preise'
     *
     * + Einkaufspreisen des Programmes
     * + unter Berücksichtigung der Anzahl.
     * + Mehrwertsteuer des Einkauf aus 'tbl_programmdetails'
     * + Bruttopreis des gebuchten Programmes
     *
     * @param $i
     * @return float
     */
    private function _ermittelnBruttoUndMwstProgramm($i)
    {

        $cols = array(
            'einkaufspreis'
        );

        $idPreisvarianteProgramm = $this->_datenProgrammbuchungen[$i]['tbl_programme_preisvarianten_id'];
        $idProgramm = $this->_datenProgrammbuchungen[$i]['programmdetails_id'];

        $whereIdPreisvarianteProgramm = "id = " . $idPreisvarianteProgramm;
        $whereIdProgramm = "programmdetails_id = " . $idProgramm;

        $select = $this->_tabellePreise->select();
        $select
            ->from($this->_tabellePreise, $cols)
            ->where($whereIdPreisvarianteProgramm)
            ->where($whereIdProgramm);

        $rows = $this->_tabellePreise->fetchAll($select)->toArray();

        // Fehler Typ 2
        if(count($rows) < 1){
            nook_ExceptionInformationRegistration::registerError('Das Programm hat keinen Einkaufspreis');

            $this->_datenProgrammbuchungen[$i]['brutto'] = 0;
            $this->_datenProgrammbuchungen[$i]['mwst'] = 0;
        }
        elseif(count($rows)  == 1){
            $this->_datenProgrammbuchungen[$i]['brutto'] = $rows[0]['einkaufspreis'] * $this->_datenProgrammbuchungen[$i]['anzahl'];
            $this->_datenProgrammbuchungen[$i]['mwst'] = $this->ermittelnMehrwertsteuerSatzEinkauf($idProgramm);

            return $this->_datenProgrammbuchungen[$i]['brutto'];
        }
        // Fehler Typ 1
        else{
            throw new nook_Exception('Dieses Programm hat zu viele Einkaufspreise');
        }



    }

    /**
     * Ermittelt den Mehrwertsteuersatz Verkauf
     *
     * + aus 'tbl_programmdetails'
     *
     * @param $programmId
     * @return float
     */
    private function ermittelnMehrwertsteuerSatzEinkauf($programmId)
    {
        $whereId = "id = ".$programmId;
        $cols = array(
            'mwst_ek'
        );

        $tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        $select = $tabelleProgrammdetails->select();
        $select->from($tabelleProgrammdetails, $cols)->where($whereId);

        $rows = $tabelleProgrammdetails->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception($this->_error_anzahl_datensaetze_falsch);
        }

        $mwstSatzEk = $rows[0]['mwst_ek'];

        return $mwstSatzEk;
    }

    /**
     * Setzt den Rechnungsstatus auf zu zahlen
     *
     * @param $i
     * @return int
     */
    private function _setzeRechnungsstatus($i)
    {

        $this->_datenProgrammbuchungen[$i]['status'] = $this->_condition_rechnungsstatus_zu_zahlen;

        return $this->_datenProgrammbuchungen[$i]['status'];
    }

    private function _setzeRechnungsnummer($i)
    {

        $this->_datenProgrammbuchungen[$i]['rechnungsnummer'] = $this->_datenProgrammbuchungen[$i]['buchungsnummer_id'] . "_" . $this->_rechnungsnummer_zaehler;

        return $this->_rechnungsnummer_zaehler;
    }

    /**
     * Eintragen Werte in 'tbl_zahlungen'
     *
     * @param $i
     * @return int
     */
    private function _eintragenTabelleZahlung($i)
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsNummer = $toolRegistrierungsNummer->steuerungErmittelnRegistrierungsnummerMitSession()->getRegistrierungsnummer();

        $programm = $this->_datenProgrammbuchungen[$i];
        $arrayInsert = array(
            'buchungsnummer_id' => $programm['buchungsnummer_id'],
            'adressen_id' => $programm['adressen_id'],
            'rechnungsnummer' => $programm['rechnungsnummer'],
            'rechnungsstatus' => 1,
            'netto' => $programm['netto'],
            'mwst' => $programm['mwst'],
            'brutto' => $programm['brutto'],
            'zahldatum' => 0,
            'betrag' => 0,
            'hobNummer' => $registrierungsNummer
        );

        $kontrolle = $this->_tabelleZahlungen->insert($arrayInsert);

        return $kontrolle;
    }

} // end class

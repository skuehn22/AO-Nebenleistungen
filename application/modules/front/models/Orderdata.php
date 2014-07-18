<?php
/**
* Eintragen der gebuchten Artikel eines Kunden
*
* + Eintragen Gruppenname und Buchungstext
* + Kontrolle und bereinigen
* + Kontrolliert ob der Kunde
* + Wurden die AGB bestätigt ?
* + Setzen Status der Buchungen in den Artikeltabellen. Eintragen Rechnungen in 'tbl_rechnungen'
* + Ermitteln aller Buchungsnummern des Kunden
* + Kontrolliert ob in einem
* + Setzt den Status der Artikel
* + Ermittelt Gesamtpreis der Hotelbuchungen
* + Ermitteln der Mwst aus den gebuchten Hotelbuchungen
* + Ermittelt Datensaetze der Zusatzprodukte einer Session.
* + Ermittelt Mwst eines Hotelproduktes und zurechnen zu Array Mwst
* + Ermittelt die Datensätze der
* + Berechnet die Mwst einer Programmbuchung
* + Tabellen 'tbl_rechnungen' und 'tbl_rechnungen_mwst' befüllen
*/
class Front_Model_Orderdata
{
    // Konditionen
    private $_condition_artikelStatusGebucht = 3;
    protected $condition_status_storniert = 10;
    protected $condition_status_storniert_nacharbeit = 9;

    // Fehler
    private $_error_kunde_nicht_angemeldet = 970;
    private $_error_agb_nicht_bestaetigt = 971;
    private $_error_kunde_gehoert_diese_session_nicht = 972;

    private $_error_preisberechnung_hotelbuchung = 973;
    private $_error_preisberechnung_produktbuchung = 974;
    private $_error_preisberechnung_programmbuchung = 975;

    private $_error_anzahl_datensaetze_stimmt_nicht = 976;

    // Views und Tabellen
    private $_tabelleBuchungsnummer = null;
    private $_tabellePreise = null;
    private $_tabelleRechnungen = null;

    private $_tabelleProduktbuchung = null;
    private $_tabelleProgrammbuchung = null;
    private $_tabelleHotelbuchung = null;
    private $_tabelleXmlBuchung = null;


    protected $buchungsnummer = null;
    protected $zaehler = null;

    protected $_buchungsNummern = array();

    protected $_aktuelleBuchungsnummer = null;
    protected $_aktuelleKundenId = null;

    // Gesamtsumme Brutto der Buchung
    protected $_gesamtsummeRechnung = 0;

    // Mehrwertsteuer der Hotelbuchungen
    protected $mwstSumme = array();

    // Gesamtsumme der Bereiche
    protected $gesamtsummeHotelbuchungen = 0;
    protected $gesamtsummeProduktbuchungen = 0;
    protected $gesamtsummeProgrammbuchungen = 0;

    // Prefix Rechnungsnummer
    private $_prefix_rechnungsnummer = '';

    public function __construct()
    {

        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabellePreise Application_Model_DbTable_preise */
        $this->_tabellePreise = new Application_Model_DbTable_preise();
        /** @var _tabelleRechnungen Application_Model_DbTable_rechnungen */
        $this->_tabelleRechnungen = new Application_Model_DbTable_rechnungen();

        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

        /** @var _tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();

    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_Orderdata
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_Orderdata
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * Eintragen Gruppenname und Buchungstext
     * Nur eintragen wenn
     * Gruppenname nicht leer oder Buchungstext nicht leer
     * Update Tabelle 'tbl_buchungsnummer'.
     *
     * @param $gruppenName
     * @param $buchungstext
     * @return Front_Model_Orderdata
     */
    public function setEintragenZusatzinformationGruppe(array $zusatzinformationGruppe)
    {
        $cols = array();

        $sessionId = Zend_Session::getId();

        if (!empty($zusatzinformationGruppe['gruppenname'])) {
            $cols['gruppenname'] = $zusatzinformationGruppe['gruppenname'];
        }

        if (!empty($zusatzinformationGruppe['buchungshinweis'])) {
            $cols['buchungshinweis'] = $zusatzinformationGruppe['buchungshinweis'];
        }

        if (!empty($zusatzinformationGruppe['maennlichSchueler'])) {
            $cols['maennlichSchueler'] = $zusatzinformationGruppe['maennlichSchueler'];
        }

        if (!empty($zusatzinformationGruppe['weiblichSchueler'])) {
            $cols['weiblichSchueler'] = $zusatzinformationGruppe['weiblichSchueler'];
        }

        if (!empty($zusatzinformationGruppe['maennlichLehrer'])) {
            $cols['maennlichLehrer'] = $zusatzinformationGruppe['maennlichLehrer'];
        }

        if (!empty($zusatzinformationGruppe['weiblichLehrer'])) {
            $cols['weiblichLehrer'] = $zusatzinformationGruppe['weiblichLehrer'];
        }

        if (!empty($zusatzinformationGruppe['sicherstellung'])) {
            $cols['sicherstellung'] = $zusatzinformationGruppe['sicherstellung'];
        }

        if(count($cols) == 0)
            return;

        $where = "session_id = '" . $sessionId . "'";

        $kontrolle = $this->_tabelleBuchungsnummer->update($cols, $where);

        return $this;
    }

    /**
     * Kontrolle und bereinigen
     * übergebener Parameter
     *
     * @param $__params
     * @return mixed
     */
    public function checkParams($__params)
    {

        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);
        unset($__params['order_x']);
        unset($__params['order_y']);

        return $__params;
    }

    /**
     * Kontrolliert ob der Kunde
     * angemeldet / kontrolliert ist.
     *
     * @throws nook_Exception
     * @return bool
     */
    public function checkKundendaten()
    {
        $kundenId = nook_ToolUserId::bestimmeKundenIdMitSession();

        if (empty($kundenId)) {
            throw new nook_Exception($this->_error_kunde_nicht_angemeldet);
        }

        return $kundenId;
    }

    /**
     * Wurden die AGB bestätigt ?
     * Gehört dem Kunden die Session
     *
     * @param $__params
     */
    public function checkEditArtikel($__params)
    {
        // Bestätigung AGB
        if (!array_key_exists('agb', $__params) or $__params['agb'] != 'agb') {
            throw new nook_Exception($this->_error_agb_nicht_bestaetigt);
        }

        // Kunden ID nach Login
        $kundenId = nook_ToolUserId::bestimmeKundenIdMitSession();
        $kundenId = (int) $kundenId;

        if (empty($kundenId) or !is_int($kundenId)) {
            throw new nook_Exception($this->_error_kunde_gehoert_diese_session_nicht);
        }

        // speichern Kunden ID
        $this->_aktuelleKundenId = $kundenId;

        // ermitteln aller Buchungsnummern
        $buchungsnummern = $this->_ermittelnBuchungsnummern($kundenId);
        $this->_checkBuchungsnummern($buchungsnummern);

        return;
    }

    /**
     * Ermitteln aller Buchungsnummern des Kunden
     *
     * @param $__kundenId
     * @throws nook_Exception
     */
    private function _ermittelnBuchungsnummern($__kundenId)
    {
        $cols = array(
            'id',
            'session_id'
        );

        $select = $this->_tabelleBuchungsnummer->select();
        $select
            ->from($this->_tabelleBuchungsnummer, $cols)
            ->where("kunden_id = " . $__kundenId);

        $buchungsnummern = $this
            ->_tabelleBuchungsnummer
            ->fetchAll($select)->toArray();

        if (count($buchungsnummern) == 0) {
            throw new nook_Exception($this->_error_kunde_gehoert_diese_session_nicht);
        }

        return $buchungsnummern;
    }

    /**
     * Kontrolliert ob in einem
     * Array die Buchungsnummer vorhanden ist.
     *
     * @param array $__buchungsnummern
     * @return Front_Model_Orderdata
     */
    private function _checkBuchungsnummern(array $__buchungsnummern)
    {
        $kontrolle = false;
        $sessionId = Zend_Session::getId(); // aktuelle Session

        for ($i = 0; $i < count($__buchungsnummern); $i++) {
            if ($__buchungsnummern[$i]['session_id'] == $sessionId) {
                $this->_buchungsNummern = $__buchungsnummern;
                $this->_aktuelleBuchungsnummer = $__buchungsnummern[$i]['id'];

                $kontrolle = true;

                break;
            }
        }

        if ($kontrolle == false) {
            throw new nook_Exception($this->_error_kunde_gehoert_diese_session_nicht);
        }

        return $this;
    }

    /**
     * Setzt den Status der Artikel
     * in den Tabellen
     * + 'tbl_hotelbuchung'
     * + 'tbl_produktbuchung'
     * + 'tbl_programmbuchung'
     *
     * @return Front_Model_Orderdata
     */
    private function _setStatusBuchung()
    {
        $update = array(
            'status' => $this->_condition_artikelStatusGebucht
        );

        $where = array(
            "buchungsnummer_id = " . $this->_aktuelleBuchungsnummer,
            "status <> '".$this->condition_status_storniert."'",
            "status <> '".$this->condition_status_storniert_nacharbeit."'"
        );

        // Hotelbuchung
        $this->_tabelleHotelbuchung->update($update, $where);
        // Buchung Zusatzprodukte
        $this->_tabelleProduktbuchung->update($update, $where);
        // Programmbuchung
        $this->_tabelleProgrammbuchung->update($update, $where);

        // 'tbl_xml_buchung'
        $this->_tabelleXmlBuchung->update($update, $where);

        return $this;
    }
}
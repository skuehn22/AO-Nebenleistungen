<?php
/**
* Shadow des Controller: 'warenkorb' Action: 'loeschenAlles'
*
* + Model der Buchungsdaten und Kundendaten
* + Model der Buchungsdaten und Kundendaten
* + Model der Buchungsnummern
* + Model zum ermitteln der aktuellen Buchungsnummer und des Zähler
* + Ermittelt den Zaehler der Buchung
* + Ermittelt den aktuellen Zaehler der Buchung
* + Bestimmen der Kunden und Buchungsdaten
* + Sendet Mail der Veränderungsbuchungen an die Programmanbieter
* + Versendet eine Stornierungsmail an den Programmanbieter
* + Anlegen einer neuen Buchungsnummer
* + Kontrolliert die Stornofristen der Programme einer Buchungsnummer
* + Setzt die aktiven Programmbuchungen einer Bestandsbuchung auf 0
* + Löschen der Hotelbuchungen
* + Löschen der Produktbuchungen
* + löschen der Programmbuchungen
* + löschen XML Buchung
* + setzt den Staus auf 10 = storniert in 'tbl_buchungsnummer' der betreffenden Buchungsnummer
*
* @date 17.07.13
* @file WarenkorbLoeschenAllesShadow.php
* @package front
* @subpackage shadow
*/
class Front_Model_WarenkorbLoeschenAllesShadow
{
    // Fehler
    private $error_anfangswerte_fehlen = 1910;

    // Tabellen / Views

    // Konditionen
    private $condition_programm_nicht_in_der_stornofrist = 1;
    private $condition_programm_in_der_stornofrist = 2;
    private $condition_status_storniert = 10;

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    /** @var $modelNeueBuchungsnummer Front_Model_NeueBuchungsnummer */
    protected $modelNeueBuchungsnummer = null;
    /** @var $modelZaehlerBuchungsnummer Front_Model_ZaehlerBuchungsnummer */
    protected $modelZaehlerBuchungsnummer = null;

    /** @var  $modelKundendatenUndBuchungsdaten Front_Model_Bestellung */
    protected $modelKundendatenUndBuchungsdaten = null;
    /** @var  $modelEmailAnbieter Front_Model_BestellungEmailAnbieter */
    protected $modelEmailAnbieter = null;
    /** @var $modelProgrammbuchungen Front_Model_Bestellung */
    protected $modelProgrammbuchungen = null;
    /** @var $modelProgrammbuchungenStornofristen Front_Model_ProgrammbuchungStornofrist */
    protected $modelProgrammbuchungenStornofristen = null;

    protected $zaehler = null;
    protected $buchungsnummer = null;

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Model der Buchungsdaten und Kundendaten
     *
     * @param Front_Model_Bestellung $model
     * @return $this
     */
    public function setModelKundenUndBuchungsdaten(Front_Model_Bestellung $model)
    {
        $this->modelKundendatenUndBuchungsdaten = $model;

        return $this;
    }

    /**
     * Model der Buchungsnummern
     *
     * @param Front_Model_NeueBuchungsnummer $model
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function setModelNeueBuchungsnummer(Front_Model_NeueBuchungsnummer $model)
    {
        $this->modelNeueBuchungsnummer = $model;

        return $this;
    }

    /**
     * Model zum ermitteln der aktuellen Buchungsnummer und des Zähler
     *
     * @param Front_Model_ZaehlerBuchungsnummer $model
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function setModelZaehlerBuchungsnummer(Front_Model_ZaehlerBuchungsnummer $model)
    {
        $this->modelZaehlerBuchungsnummer = $model;

        return $this;
    }

    /**
     * Ermittelt den Zaehler der Buchung
     * + Kontrolle ob Model vorhanden
     *
     * @throws nook_Exception
     * @return int
     */
    public function getZaehler()
    {
        if (empty($this->modelZaehlerBuchungsnummer)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        if ($this->modelZaehlerBuchungsnummer instanceof Front_Model_ZaehlerBuchungsnummer) {
            $zaehler = $this->zaehlerBuchungsnummerErmitteln();
        } else {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        return $zaehler;
    }

    /**
     * Ermittelt den aktuellen Zaehler der Buchung
     *
     * @return int
     */
    private function zaehlerBuchungsnummerErmitteln()
    {
        $zaehler = $this->modelZaehlerBuchungsnummer
            ->findBuchungsnummerUndZaehler()
            ->getZaehler();

        $buchungsnummer = $this->modelZaehlerBuchungsnummer->getBuchungsnummer();

        $this->buchungsnummer = $buchungsnummer;
        $this->zaehler = $zaehler;

        return $zaehler;
    }

    /**
     * Bestimmen der Kunden und Buchungsdaten
     *
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function kundenUndBuchungsdatenErmitteln()
    {
        $this->modelKundendatenUndBuchungsdaten->setAktuelleBuchungsnummer($this->buchungsnummer);
        $this->modelKundendatenUndBuchungsdaten->setAktuellerZaehler($this->zaehler);
        $this->modelKundendatenUndBuchungsdaten->ermittelnBuchungen();

        return $this;
    }

    /**
     * Sendet Mail der Veränderungsbuchungen an die Programmanbieter
     *
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function sendenEmailAnProgrammanbieter()
    {
        /** @var  $modelEmailAnbieter Front_Model_BestellungEmailAnbieter */
        $this->modelEmailAnbieter
            ->setModelDataKundenUndBuchungsdaten($this->modelKundendatenUndBuchungsdaten) // Übernahme Grunddaten
            ->setZaehler($this->zaehler)
            ->ermittelnDaten() // ermittelt Daten der Programme
            ->sendenMails();

        return $this;
    }

    /**
     * Versendet eine Stornierungsmail an den Programmanbieter
     *
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function steuerungVersendeMailAnProgrammanbieter()
    {
        // Veränderung in Tabelle 'tbl_rechnung'
        // Veränderung in Tabelle 'tbl_zahlung'

        // *********
        // setzen Status der Buchung

        return $this;
    }

    /**
     * Anlegen einer neuen Buchungsnummer
     */
    public function neueBuchungsnummer()
    {
        $this->modelNeueBuchungsnummer
            ->aktiveBuchungLoeschen()
            ->neueBuchungsnummerAnlegen();
    }

    /**
     * @param Front_Model_ProgrammbuchungStornofrist $modelProgrammbuchungenStornofristen
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function setmodelProgrammbuchungStornofrist(
        Front_Model_ProgrammbuchungStornofrist $modelProgrammbuchungenStornofristen
    ) {
        /** @var $modelProgrammbuchungenStornofristen Front_Model_ProgrammbuchungStornofrist */
        $this->modelProgrammbuchungenStornofristen = $modelProgrammbuchungenStornofristen;

        return $this;
    }

    /**
     * Kontrolliert die Stornofristen der Programme einer Buchungsnummer
     * + ermitteln Daten der Programmbuchung
     * + Kontrolle der gebuchten Programme auf gültige Stornofrist
     * + ausgliedern der Programme die nicht in der Buchungsfrist sind
     * Gibt die Anzahl der Programmbuchungen zurück die nicht gelöscht werden können
     *
     * @return bool / array
     */
    public function stornierenProgrammbuchungen()
    {
        // ermitteln Daten der Programmbuchung
        /** @var  $modelKundendatenUndBuchungsdaten Front_Model_Bestellung */
        $modelData = $this->modelKundendatenUndBuchungsdaten->_exportModelData();
        $programmdaten = $modelData['_datenProgrammbuchungen'];

        $this->modelProgrammbuchungenStornofristen->setPimple($this->pimple);

        // Kontrolle der gebuchten Programme auf gültige Stornofrist
        $anzahlProgrammeNichtInDerStornofrist = 0;
        for ($i = 0; $i < count($programmdaten); $i++) {
            $this->modelProgrammbuchungenStornofristen->setBuchungstabelleId($programmdaten[$i]['id']);
            $this->modelProgrammbuchungenStornofristen->setProgrammdetailsId($programmdaten[$i]['programmdetails_id']);
            $this->modelProgrammbuchungenStornofristen->steuerungKontrolleStornofrist();
            $kontrolleProgrammInDerStornofrist = $this->modelProgrammbuchungenStornofristen->isInStornofrist();

            if ($kontrolleProgrammInDerStornofrist == $this->condition_programm_nicht_in_der_stornofrist) {
                $anzahlProgrammeNichtInDerStornofrist++;
            }

            // löschen der Programme die nicht in der Buchungsfrist sind
            if ($kontrolleProgrammInDerStornofrist == $this->condition_programm_nicht_in_der_stornofrist) {
                unset($programmdaten[$i]);
            }
        }

        // neuordnen der Programmdaten
        if (count($programmdaten) > 0) {
            $programmdaten = array_merge($programmdaten);
        }

        // stornieren der Programmbuchungen
        if (count($programmdaten) > 0) {
            $this->stornoProgrammbuchungen($programmdaten);
        }

        return $anzahlProgrammeNichtInDerStornofrist;
    }

    /**
     * Setzt die aktiven Programmbuchungen einer Bestandsbuchung auf 0
     *
     * @param array $programmdaten
     */
    private function stornoProgrammbuchungen(array $programmdaten)
    {

        $update = array(
            'anzahl' => 0
        );

        for ($i = 0; $i < count($programmdaten); $i++) {

            $where = array(
                "zaehler = 0",
                "buchungsnummer_id = " . $this->buchungsnummer,
                "programmdetails_id = " . $programmdaten[$i]['programmdetails_id']
            );

            /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
            $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
            $tabelleProgrammbuchung->update($update, $where);
        }

        return;
    }

    /**
     * Löschen der Hotelbuchungen
     */
    public function loeschenHotelbuchungen()
    {
        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = 0"
        );

        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $tabelleHotelbuchung->delete($where);

        return;
    }

    /**
     * Löschen der Produktbuchungen
     */
    public function loeschenProduktbuchungen()
    {
        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = 0"
        );

        /** @var  $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $tabelleProduktbuchung->delete($where);

        return;
    }

    /**
     * löschen der Programmbuchungen

     */
    public function loeschenProgrammbuchungen()
    {
        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = 0"
        );

        /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $tabelleProgrammbuchung->delete($where);

        return;
    }

    /**
     * löschen XML Buchung
     */
    public function loeschenXmlBuchung()
    {
        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = 0"
        );

        /** @var  $tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $tabelleXmlBuchung = $this->pimple['tabelleXmlBuchung'];
        $tabelleXmlBuchung->delete($where);

        return;
    }

    /**
     * setzt den Staus auf 10 = storniert in 'tbl_buchungsnummer' der betreffenden Buchungsnummer
     *
     * @return Front_Model_WarenkorbLoeschenAllesShadow
     */
    public function setzenStatusTabelleBuchungsnummer()
    {
        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];

        $update = array(
            'status' => $this->condition_status_storniert
        );

        $where = array(
            "id = ".$this->buchungsnummer
        );

        $kontrolle = $tabelleBuchungsnummer->update($update, $where);

        return $this;
    }
}

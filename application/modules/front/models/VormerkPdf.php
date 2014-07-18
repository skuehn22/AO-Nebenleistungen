<?php 
/**
* Ermittelt die Daten einer Vormerkung
*
* + Übernahme des Servicecontainer
* + Injection notwendiger Klassen
* + Gibt die Daten des Pdf zurück
* + Gibt detailinformationen zurück
* + Steuert die Erstellung der Daten für das Pdf der Vormerkung
* + stellt das aktuelle datum komplett dar
* + setzen der Grundwerte und Steuerung der Ermittlung der Buchungsdaten
* + Ermittelt die Adressdaten des Kunden
* + Ermitteln Datensatz der Buchung
*
* @date 11.11.2013
* @file VormerkPdf.php
* @package front
* @subpackage model
*/
class Front_Model_VormerkPdf
{
    // Fehler
    protected $error_anfangswerte_fehlen = 2360;
    protected $error_anzahldatensaetze_falsch = 2361;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    protected $tabelleBuchungsnummer = null;
    /** @var $tabelleAdressen Application_Model_DbTable_adressen */
    protected $tabelleAdressen = null;
    /** @var $tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
    protected $tabellePreiseBeschreibung = null;
    /** @var $toolAdressdaten nook_ToolAdressdaten */
    protected $toolAdressdaten = null;


    // Konditionen
    protected $condition_status_vormerkung = 2;

    // Flags


    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;

    protected $buchungsNummerId = null;
    protected $kundenId = null;
    protected $registrierungsNummerId = null;
    protected $daten = array();
    protected $anzeigeSpracheId = null;

    /** @var $frontModelBestellung Front_Model_Bestellung */
    protected $frontModelBestellung = null;
    /** @var $toolMonatsname nook_ToolWochentageNamen */
    protected $toolWochentageName = null;
    /** @var $toolMonatsname nook_ToolMonatsnamen */
    protected $toolMonatsname = null;
    /** @var $toolProgrammdetails nook_ToolProgrammdetails */
    protected $toolProgrammdetails = null;
    /** @var $toolProgrammsprache nook_ToolProgrammsprache */
    protected $toolProgrammsprache = null;
    /** @var $toolPreisvariante nook_ToolPreisvariante  */
    protected $toolPreisvariante = null;
    /** @var $toolErmittlungAbweichendeStornofristenKosten nook_ToolErmittlungAbweichendeStornofristenKosten  */
    protected $toolErmittlungAbweichendeStornofristenKosten = null;

    protected $zaehler = null;
    protected $gesamtpreis = 0;

    /**
     * Übernahme des Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

    }

    /**
     * Injection der notwendigen Klassen
     *
     * + Kontrolle vorhandensein der Klassen
     * + zuordnung der Klassen
     *
     */
    protected function servicecontainer($pimple)
    {
        $kontrolle = array(
            'tabelleBuchungsnummer',
            'tabelleAdressen',
            'toolAdressdaten',
            'frontModelBestellung',
            'toolWochentageName',
            'toolMonatsname',
            'toolProgrammdetails',
            'toolProgrammsprache',
            'toolPreisvariante',
            'toolErmittlungAbweichendeStornofristenKosten',
            'tabellePreiseBeschreibung'
        );

        foreach($kontrolle as $key => $value)
        {
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_VormerkPdf
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsNummerId = $buchungsnummer;

        return $this;
    }

    /**
     * Gibt die Daten des Pdf zurück
     *
     * @return array
     */
    public function getAdresse()
    {
        return $this->daten['adresse'];
    }

    /**
     * Gibt detailinformationen zurück
     *
     * @return array
     */
    public function getDetail()
    {
        return $this->daten['detail'];
    }

    /**
     * Gibt den Gesamtpreis aller Artikel des vorgemerkten Warenkorbes zurück
     */
    public function getGesamtpreisAllerArtikel()
    {
        return $this->gesamtpreis;
    }

    /**
     * Steuert die Erstellung der Daten für das Pdf der Vormerkung
     *
     * @return Front_Model_VormerkPdf
     * @throws nook_Exception
     */
    public function erstellenAdressdatenPdfVormerkung()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittelnKundenId();
        $this->ermittelnAdressdaten($this->kundenId);
        $this->ermittelnAktuellesDatum();
        $this->ermittelnErstellungsDatum($this->buchungsNummerId);

        return $this;
    }

    /**
     * stellt das aktuelle Datum komplett dar
     */
    protected function ermittelnAktuellesDatum()
    {
        $tag = date("d");
        $monat = date("m");
        $jahr = date("Y");

        $toolMonatsnamen = new nook_ToolMonatsnamen();
        $monatsname = $toolMonatsnamen->setMonatsZiffer($monat)->getMonatsnamen();

        $this->daten['detail']['datum'] = $tag.". ".$monatsname." ".$jahr;

        return;
    }

    /**
     * Ermittelt das Anlagedatum des Warenkorbes
     *
     * @param $buchungsNummerId
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittelnErstellungsDatum($buchungsNummerId)
    {
        $cols = array(
            'date'
        );

        /** @var $tabelleBuchungsnummer Zend_Db_Table */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where("id = ".$buchungsNummerId)
            ->where("status = ".$this->condition_status_vormerkung);

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl vorgemerkte Warenkoerbe falsch');

        $this->daten['detail']['erstelltAm'] = $rows[0]['date'];

        return $rows[0]['date'];
    }

    /**
     * setzen der Grundwerte und Steuerung der Ermittlung der Buchungsdaten
     *
     */
    protected function frontModelBestellungGrunddaten()
    {
        $this->frontModelBestellung
            ->setAktuelleBuchungsnummer($this->buchungsNummerId)
            ->setAktuellerZaehler($this->zaehler)
            ->ermittelnBuchungen();

        return;
    }

    /**
     * Ermittelt die Adressdaten des Kunden
     *
     * @param $kundenId
     */
    protected function ermittelnAdressdaten($kundenId)
    {
        $this->daten['adresse'] = $this->toolAdressdaten
            ->setKundenId($kundenId)
            ->steuerungErmittlungKundendaten()
            ->getAdressdatenKunde();

        return;
    }

    /**
     * Ermitteln Datensatz der Buchung
     *
     * + Bestimmung Kunden ID
     * + Bestimmen der Registrierungsnummer
     *
     * @throws nook_Exception
     */
    protected function ermittelnKundenId()
    {
        $rows = $this->tabelleBuchungsnummer->find($this->buchungsNummerId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahldatensaetze_falsch);

        $this->kundenId = $rows[0]['kunden_id'];
        $this->daten['detail']['kundenId'];

        $this->registrierungsNummer = $rows[0]['hobNummer'];
        $this->daten['detail']['registrierungsNummer'] = $rows[0]['hobNummer'];

        $this->zaehler = $rows[0]['zaehler'];

        return;
    }
}

<?php
/**
* Ermittelt die Daten für das Pdf der Programmrechnung
*
* + Übernahme des Pimple Container
* + Übernahme des Pimple Container und der Kennwerte Buchungspauschale
* + Steuert die Übernahme der übergebenen Datensätze
* + Übernimmt Daten eines externen Models
* + Erstellt die Daten der Kopfzeile der Programmzeilen der Erstbuchung
* + Ermittelt die Rechnungszusammenfassung der Programmbuchungen
* + Gibt die Zeilen der Programmbuchung zurück
* + Kontrolliert ob ausgehend von der letzten Buchung Programme gelöscht wurden.
* + Veränderung Vorzeichen Anzahl der gelöschten Programme
* + Darstellen des Datum in der Landessprache
* + Berechnet die Differenz der momentanen Programmbestellung zur vorhergehenden Buchung
* + Berechnet den Nettopreis der Gesamtrechnung
* + Ermittelt die Preise einer Programmvariante
* + Ermittelt die Programmbeschreibung eines Programmes
* + Ermittelt die Beschreibung der Preisvariante
* + Hinweis wenn keine Programme gebucht oder verändert wurden
* + Generiert die Zeilen der Programme einer Programmbuchung
* + Korrigiert den die Anzeige der Buchungspauschale
* + Splittet eine Zeile in mehrere Einzelzeilen auf
* + Ermittelt die Überschrift der Rechnungsdurchläufer
*
* @file BuchungProgrammeErstbuchungPdf.php
* @package front
* @subpackage model
*/
class Front_Model_BuchungProgrammePdf
{
    // Fehler
    private $error_anzahl_datensaetze = 1850;

    // Tabellen / Views
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;
    /** @var $tabellePreise Application_Model_DbTable_preise */
    private $tabellePreise = null;
    /** @var $tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
    private $tabelleProgrammbeschreibung = null;
    /** @var $tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
    private $tabellePreiseBeschreibung = null;
    /** @var $tabellePreiseBeschreibung Application_Model_DbTable_programmedetails */
    private $tabelleProgrammdetails = null;
    /** @var $tabelleRechnungenDurchlaeufer Application_Model_DbTable_rechnungenDurchlaeufer */
    private $tabelleRechnungenDurchlaeufer = null;

    protected $pimple; // Container
    protected $modelData; // Daten

    protected $summeBrutto = 0;
    protected $summeNetto = 0;

    protected $mwstSummeBrutto7 = 0;
    protected $mwstSummeBrutto19 = 0;

    protected $statischeTexte = null;
    protected $datenRechnung = null;
    protected $kundenDaten = null;
    protected $kundenId = null;

    protected $buchungsNummerId = null;
    protected $zaehler = null;

    protected $gebuchteProgramme = null;
    protected $programmBuchungenZeilen = array();

    protected $programmBuchungspauschaleId = null;

    /**
     * Übernahme des Pimple Container und der Kennwerte Buchungspauschale
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        // Container
        $this->tabelleProgrammbuchung = $pimple['tabelleProgrammbuchung'];
        $this->tabellePreise = $pimple['tabellePreise'];
        $this->tabelleProgrammbeschreibung = $pimple['tabelleProgrammbeschreibung'];
        $this->tabellePreiseBeschreibung = $pimple['tabellePreiseBeschreibung'];
        $this->tabelleProgrammdetails = $pimple['tabelleProgrammdetails'];
        $this->tabelleRechnungenDurchlaeufer = $pimple['tabelleRechnungenDurchlaeufer'];

        // Buchungspauschale
        $static = Zend_Registry::get('static');
        $this->programmBuchungspauschaleId = $static->buchungspauschale->programmId;



        $this->pimple = $pimple;
    }

    /**
     * Steuert die Übernahme der übergebenen Datensätze
     *
     * @param $modelKundendatenUndBuchungsdaten
     * @return Front_Model_BuchungProgrammeKundePdf
     */
    public function setProgrammDaten($modelKundendatenUndBuchungsdaten)
    {
        $this->importModelData($modelKundendatenUndBuchungsdaten);

        $this->statischeTexte = $this->modelData['_statischeFirmenTexte'];
        $this->datenRechnung = $this->modelData['_datenRechnungen'];
        $this->kundenDaten = $this->modelData['_kundenDaten'];
        $this->kundenId = $this->modelData['_kundenId'];
        $this->buchungsNummerId = $this->modelData['aktuelleBuchungsnummer'];
        $this->zaehler = $this->modelData['aktuellerZaehler'];
        $this->gebuchteProgramme = $this->modelData['_datenProgrammbuchungen'];

        return $this;
    }

    /**
     * Übernimmt Daten eines externen Models
     *
     * @param $__fremdModel
     * @param bool $__modelName
     * @return
     */
    protected function importModelData($modelKundendatenUndBuchungsdaten, $modelName = false)
    {

        // löscht Transfer - Container
        if (empty($modelName)) {
            $this->modelData = array();
            $this->modelData = $modelKundendatenUndBuchungsdaten->_exportModelData();
        } // neuer Datenbereich eines Model
        else {
            $this->_modelData[$modelName] = $modelKundendatenUndBuchungsdaten->_exportModelData();
        }

        return $this;
    }

    /**
     * Erstellt die Daten der Kopfzeile der Programmzeilen der Erstbuchung
     *
     * @return array
     */
    public function kopfProgrammzeile()
    {
        $ueberschriftProgrammzeile = array();
        $ueberschriftProgrammzeile[0] = translate('Datum');
        $ueberschriftProgrammzeile[1] = translate('no.');
        $ueberschriftProgrammzeile[2] = translate('Programm');
        $ueberschriftProgrammzeile[3] = translate('USt.');
        $ueberschriftProgrammzeile[4] = translate('Einzelpreis');
        $ueberschriftProgrammzeile[5] = translate('gesamt');

        return $ueberschriftProgrammzeile;
    }

    /**
     * Ermittelt die Rechnungszusammenfassung der Programmbuchungen
     *
     * + Brutto Gesamtsumme
     * + Netto Gesamtsumme
     * + Mehrwertsteuer 19%
     * + Mehrwertsteuer 7%
     *
     * @return array
     */
    public function getRechnungszusammenfassung()
    {
        $zeileZusammenfassung = array();
        $j = 0;

        // Rechnungszusammenfassung Überschrift
        if ($this->summeNetto > 0) {
            $zeileZusammenfassung[$j][0] = translate('Zu zahlender Gesamtbetrag');
        } else {
            $zeileZusammenfassung[$j][0] = translate('Differenzbetrag');
        }

        $this->summeBrutto = abs($this->summeBrutto);
        $zeileZusammenfassung[$j][5] = number_format($this->summeBrutto, 2, ',', '') . " €";
        $j++;

        // Abstand zum nächsten Programm
        $zeileZusammenfassung[$j] = true;
        $j++;

        // Mwst 19%
        if( ($this->mwstSummeBrutto19) and ($this->mwstSummeBrutto19 <> 0) ){
            $zeileZusammenfassung[$j][0] = translate('USt. 19% Betrag / Summe netto USt. 19%');

            $netto19 = nook_ToolMehrwertsteuer::getNettobetrag($this->mwstSummeBrutto19, 0.19);
            $netto19 = abs($netto19);
            $mwst19 = nook_ToolMehrwertsteuer::getMehrwertsteuer($netto19, 0.19);

            $netto19 = number_format($netto19, 2, ',', '');
            $mwst19 = number_format($mwst19, 2, ',', '');

            $zeileZusammenfassung[$j][6] = $mwst19." €";
            $zeileZusammenfassung[$j][4] = $netto19." €";

            // $j++;
            // Abstand zum nächsten Programm
            // $zeileZusammenfassung[$j] = true;

            $j++;
        }

        // Mwst 7%
        if( ($this->mwstSummeBrutto7) and ($this->mwstSummeBrutto7 <> 0) ){
            $zeileZusammenfassung[$j][0] = translate('USt. 7% Betrag / Summe netto USt. 7%');

            $netto7 = nook_ToolMehrwertsteuer::getNettobetrag($this->mwstSummeBrutto7, 0.07);
            $netto7 = abs($netto7);
            $mwst7 = nook_ToolMehrwertsteuer::getMehrwertsteuer($netto7, 0.07);

            $netto7 = number_format($netto7, 2, ',', '');
            $mwst7 = number_format($mwst7, 2, ',', '');

            $zeileZusammenfassung[$j][6] = $mwst7." €";
            $zeileZusammenfassung[$j][4] = $netto7." €";

            // $j++;
            // Abstand zum nächsten Programm
            // $zeileZusammenfassung[$j] = true;

            $j++;
        }

        // Mwst 0%
        if( ($this->mwstSummeBrutto0) and ($this->mwstSummeBrutto0 <> 0) ){
            $zeileZusammenfassung[$j][0] = translate('Summe netto USt.-frei');

            $netto0 = abs($this->mwstSummeBrutto0);
            $netto0 = number_format($netto0, 2, ',', '');

            $zeileZusammenfassung[$j][6] = $netto0." €";

            // $j++;
            // Abstand zum nächsten Programm
            // $zeileZusammenfassung[$j] = true;

        }

        return $zeileZusammenfassung;
    }

    /**
     * Gibt die Zeilen der Programmbuchung zurück
     *
     * + wurden seit der letzten Buchung Programme gelöscht ?
     * + Jedes Programm hat einen Zähler
     * + Jeder Teil der Programmzeile hat die Nummer der Spalte
     *
     * @return Front_Model_BuchungProgrammePdf
     */
    public function steuerungProgrammZeilen()
    {
        // Kontrolle ob Programme gelöscht wurden ?
        $this->kontrolleGeloeschteProgramme();

        $programmbuchungen = array();
        $spracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        // ermitteln der Programmzeilen
        $programmBuchungenZeilen = $this->generierenProgrammbuchungen($spracheId, $programmbuchungen);
        $this->programmBuchungenZeilen = $programmBuchungenZeilen;

        return $this;
    }

    /**
     * @return array
     */
    public function getProgrammZeilen()
    {
        return $this->programmBuchungenZeilen;
    }

    /**
     * Kontrolliert ob ausgehend von der letzten Buchung Programme gelöscht wurden.
     *
     * + stellt diese Programme mit einer negativen Anzahl dar
     * + fügt diese Programme mit negativer Anzahl den gebuchten Programmen hinzu.
     *
     * @return int
     */
    private function kontrolleGeloeschteProgramme()
    {
        // Erstbuchung
        if ($this->zaehler == 1) {
            return;
        }

        $wherebuchungsnummer = "buchungsnummer_id = " . $this->buchungsNummerId;
        $zaehlerVorhergehendeBuchung = $this->zaehler - 1;
        $whereZaehlerVorhergehendeBuchung = "zaehler = " . $zaehlerVorhergehendeBuchung;

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->where($wherebuchungsnummer)
            ->where($whereZaehlerVorhergehendeBuchung);

        $rowsVorhergehendeBuchung = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        if (count($rowsVorhergehendeBuchung) == 0) {
            throw new nook_Exception($this->error_anzahl_datensaetze);
        }

        for ($i = 0; $i < count($this->gebuchteProgramme); $i++) {
            $gebuchteProgramm = $this->gebuchteProgramme[$i];

            foreach ($rowsVorhergehendeBuchung as $key => $value) {
                $altesProgramm = $rowsVorhergehendeBuchung[$key];

                if (($gebuchteProgramm['programmdetails_id'] == $altesProgramm['programmdetails_id']) and ($gebuchteProgramm['tbl_programme_preisvarianten_id'] == $altesProgramm['tbl_programme_preisvarianten_id'])) {
                    unset($rowsVorhergehendeBuchung[$key]);

                    $rowsVorhergehendeBuchung = array_values($rowsVorhergehendeBuchung);
                }
            }
        }

        // verändern Vorzeichen Anzahl der gelöschten Programme
        $rowsVorhergehendeBuchung = $this->geloeschteProgrammeAnzahlNegativ($rowsVorhergehendeBuchung);

        // kombinieren gelöschte Programme und veränderte programme
        $this->gebuchteProgramme = array_merge($this->gebuchteProgramme, $rowsVorhergehendeBuchung);

        return;
    }

    /**
     * Veränderung Vorzeichen Anzahl der gelöschten Programme
     *
     * @param $rowsVorhergehendeBuchung
     * @return mixed
     */
    private function geloeschteProgrammeAnzahlNegativ($rowsVorhergehendeBuchung)
    {
        for ($i = 0; $i < count($rowsVorhergehendeBuchung); $i++) {
            $rowsVorhergehendeBuchung[$i]['anzahl'] = $rowsVorhergehendeBuchung[$i]['anzahl'] * -1;
        }

        return $rowsVorhergehendeBuchung;
    }

    /**
     * Darstellen des Datum in der Landessprache
     * + Monatsname in der Kurzform und in der Landessprache
     *
     * @param $datum
     * @return string
     */
    private function datumUndMonatAnpassen($datum)
    {
        $teileDatum = explode('-', $datum);

        $toolMonatsname = new nook_ToolMonatsnamen();
        $monatsname = $toolMonatsname
            ->setMonatsZiffer($teileDatum[1])
            ->getMonatsnameShort();

        $datumMitMonatsname = $teileDatum[2] . " " . $monatsname . " " . $teileDatum[0];

        return $datumMitMonatsname;
    }

    /**
     * Berechnet die Differenz der momentanen Programmbestellung zur vorhergehenden Buchung
     * + Vergleich Buchungsnummer
     * + vergleich über verringerten Zaehler
     * + vergleich über ProgrammId
     * + vergleich über PreisvarianteId
     * + erkennt gelöschte Programme, $anzahl < 0
     * + Rückgabe der Differenz der momentanen Programmbuchung zur vorhergehenden programmbuchung
     *
     * @param $anzahl
     * @param $programmId
     * @param $preisvarianteId
     * @return int
     * @throws nook_Exception
     */
    private function berechnungDifferenzVorhergehendeBuchung($anzahl, $programmId, $preisvarianteId)
    {
        $vorhergehendeZaehler = $this->zaehler - 1;

        $whereBuchungsnummer = "buchungsnummer_id = " . $this->buchungsNummerId;
        $whereZaehler = "zaehler = " . $vorhergehendeZaehler;
        $whereProgramm = "programmdetails_id = " . $programmId;
        $wherePreisvariante = "tbl_programme_preisvarianten_id = " . $preisvarianteId;

        $cols = array(
            'anzahl'
        );

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->from($this->tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler)
            ->where($whereProgramm)
            ->where($wherePreisvariante);

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        // neue Buchung
        if (count($rows) == 0) {
            return $anzahl;
        }
        // Veränderung der Programmbuchung
        elseif (count($rows) == 1) {
            // gelöschtes Programm
            if ($anzahl < 0) {
                $neueAnzahl = $anzahl;
            }
            // Veränderung der Anzahl einer Programmbuchung
            else {
                $neueAnzahl = $anzahl - $rows[0]['anzahl'];
            }

            return $neueAnzahl;
        } else {
            throw new nook_Exception('Anzahl Update Datensaetze falsch');
        }
    }

    /**
     * Berechnet den Nettopreis der Gesamtrechnung
     * + Summe Nettopreis
     *
     * @param $brutto
     * @param $mwstSteuersatz
     */
    private function berchnungNetto($brutto, $mwstSteuersatz)
    {
        // Summe Gesamtpreis Netto
        $netto = nook_ToolPreise::berechneNetto($brutto, $mwstSteuersatz);
        $this->summeNetto += $netto;

        return;
    }

    /**
     * Ermittelt die Preise einer Programmvariante
     *
     * + ermitteln Verkaufspreis
     * + ermitteln Mwst
     *
     * @param $programmdetailsId
     * @return array
     */
    private function ermittelnPreise($preisvarianteId)
    {
        $cols = array(
            'verkaufspreis',
            'programmdetails_id'
        );

        $wherePreisvarianteId = "id = " . $preisvarianteId;

        $select = $this->tabellePreise->select();
        $select
            ->from($this->tabellePreise, $cols)
            ->where($wherePreisvarianteId);

        $rows = $this->tabellePreise->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze);
        }

        $mwst = $this->ermittelnMwstVerkauf($preisvarianteId);
        unset($rows[0]['programmdetails_id']);
        $rows[0]['mwst'] = $mwst;

        return $rows[0];
    }

    /**
     * @param $preisvarianteId
     * @return float
     */
    private function ermittelnMwstVerkauf($preisvarianteId)
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammdetails'] = function()
        {
            return new Application_Model_DbTable_programmedetails();
        };

        $pimple['tabellePreise'] = function()
        {
            return new Application_Model_DbTable_preise();
        };

        $toolProgrammdetails = new nook_ToolProgrammdetails($pimple);
        $mwstSatzVerkauf = $toolProgrammdetails->setPreisvarianteId($preisvarianteId)->steuerungErmittelnDaten()->getProgrammdetail('mwst');

        return $mwstSatzVerkauf;
    }

    /**
     * Ermittelt die Programmbeschreibung eines Programmes
     * + Sprache wird berücksichtigt
     *
     * @param $spracheId
     * @param $programmId
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnProgrammname($spracheId, $programmId)
    {
        $cols = array(
            'progname'
        );

        $whereProgrammdetailId = "programmdetail_id = " . $programmId;
        $whereSprache = "sprache = " . $spracheId;

        /** @var  $tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $select = $this->tabelleProgrammbeschreibung->select();
        $select
            ->from($this->tabelleProgrammbeschreibung, $cols)
            ->where($whereProgrammdetailId)
            ->where($whereSprache);

        $rows = $this->tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze);
        }

        return $rows[0]['progname'];
    }

    /**
     * Ermittelt die Beschreibung der Preisvariante
     * + Sprache wird berücksichtigt
     *
     * @param $spracheId
     * @param $preisvarianteId
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnPreisbeschreibung($spracheId, $preisvarianteId)
    {
        $cols = array(
            'preisvariante'
        );

        $wherePreiseId = "preise_id = " . $preisvarianteId;
        $whereSprache = "sprachen_id = " . $spracheId;

        $select = $this->tabellePreiseBeschreibung->select();
        $select
            ->from($this->tabellePreiseBeschreibung, $cols)
            ->where($wherePreiseId)
            ->where($whereSprache);

        $rows = $this->tabellePreiseBeschreibung->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze);
        }

        return $rows[0]['preisvariante'];
    }

    /**
     * Hinweis wenn keine Programme gebucht oder verändert wurden
     *
     * @return string
     */
    private function keineProgrammeVorhanden()
    {
        $einzelnesProgramm[0][1] = translate("Keine Programmbuchung oder Programmveränderung vorhanden");

        return $einzelnesProgramm;
    }

    /**
     * Generiert die Zeilen der Programme einer Programmbuchung
     *
     * + Besonderheiten der Rechnungslegung / Rechnungsdurchläufer
     * + Datum
     * + Zeit
     * + Programmname
     * + Programmsprache
     * + Anzahl
     *
     * @param $spracheId
     * @param $programmbuchungen
     * @return array
     */
    public function generierenProgrammbuchungen($spracheId, $programmbuchungen)
    {
        $programmbuchungen = array();

        for ($i = 0; $i < count($this->gebuchteProgramme); $i++) {

            $j = 0;
            $einzelnesProgramm = array();
            $gebuchteProgramm = $this->gebuchteProgramme[$i];

            // Anzahl
            $anzahl = $gebuchteProgramm['anzahl'];

            // wenn es eine Bestandsbuchung ist, wird die Differenz zur vorhergehenden Buchung ermittelt
            if ($this->zaehler > 1) {
                $anzahl = $this->berechnungDifferenzVorhergehendeBuchung(
                    $anzahl,
                    $gebuchteProgramm['programmdetails_id'],
                    $gebuchteProgramm['tbl_programme_preisvarianten_id']
                );

                if ($anzahl == 0) {
                    continue;
                }
            }

            /***** erste Zeile ****/
            $ueberschriftRechnungsDurchLaeufer = $this->ermittelnUeberschriftRechnungsDurchLaeufer($gebuchteProgramm['programmdetails_id']);
            if($ueberschriftRechnungsDurchLaeufer){
                $einzelnesProgramm[$j][2] = $ueberschriftRechnungsDurchLaeufer;
                $einzelnesProgramm[$j][5] = ' ';

                $j++;
            }

            /***** zweite Zeile ****/
            // Datum, Kontrolle ob Datum vorhanden
            if( (!empty($gebuchteProgramm['datum'])) and ($gebuchteProgramm['datum'] != '0000-00-00') ){
                $datum = $this->datumUndMonatAnpassen($gebuchteProgramm['datum']);
                $toolWochenTageNamen = new nook_ToolWochentageNamen();
                $nameWochentag = $toolWochenTageNamen
                    ->setAnzeigespracheId($spracheId) // Anzeigesprache
                    ->setAnzeigeNamensTyp(1) // Wochentag in Kurzform
                    ->setDatum($gebuchteProgramm['datum']) // Datum
                    ->steuerungErmittelnWochentag()
                    ->getBezeichnungWochentag(); // Name des Wochentages

                $einzelnesProgramm[$j][0] = $nameWochentag." ".$datum;
            }
            else
                $einzelnesProgramm[$j][0] = " ";

            // Programm Name
            $programmName = $this->ermittelnProgrammname($spracheId, $gebuchteProgramm['programmdetails_id']);
            $arrayProgrammname = $this->splitTextzeile($programmName, 50);

            // mehrzeilige Darstellung des Programmnamen
            for ($k = 0; $k < count($arrayProgrammname); $k++) {
                $einzelnesProgramm[$j][2] = $arrayProgrammname[$k];
                $einzelnesProgramm[$j][4] = ' ';
                $j++;
            }

            /***** dritte Zeile Zeile  *****/
            $j = count($einzelnesProgramm);

            // Preis der Programmvariante
            $preis = $this->ermittelnPreise($gebuchteProgramm['tbl_programme_preisvarianten_id']);

            if ($preis['mwst'] == '0.07') {
                $this->mwstSummeBrutto7 += $anzahl * $preis['verkaufspreis'];
            }

            if ($preis['mwst'] == '0.19') {
                $this->mwstSummeBrutto19 += $anzahl * $preis['verkaufspreis'];
            }

            // Mwst
            $mwst = $preis['mwst'] * 100;
            $mwst .= " %";
            $einzelnesProgramm[$j][3] = $mwst;

            // Einzelpreis
            $einzelpreis = number_format($preis['verkaufspreis'], 2, ',', '');
            $einzelpreis .= " €";
            $einzelnesProgramm[$j][4] = $einzelpreis;

            // Gesamtpreis
            $gesamtpreis = $preis['verkaufspreis'] * $anzahl;
            $this->berchnungNetto($gesamtpreis, $preis['mwst']);

            $this->summeBrutto += $gesamtpreis;

            $gesamtpreis = number_format($gesamtpreis, 2, ',', '');
            $gesamtpreis .= " €";
            $einzelnesProgramm[$j][5] = $gesamtpreis;

            // Uhrzeit wenn vorhanden
            if( (!empty($gebuchteProgramm['zeit'])) and ($gebuchteProgramm['zeit'] != '00:00:00') ){
                $zeit = nook_ToolZeiten::kappenZeit($gebuchteProgramm['zeit'], 2); // Stunden und Minutenanzeige
                $einzelnesProgramm[$j][0] = $zeit . " " . translate('Uhr');
            }

            // darstellen der Anzahl
            if ($this->zaehler > 1) {
                if ($anzahl > 0) {
                    $kompletteAnzahl = "+" . $anzahl;
                } else {
                    $kompletteAnzahl = $anzahl;
                }
            } else {
                $kompletteAnzahl = $anzahl;
            }

            $einzelnesProgramm[$j][1] = $kompletteAnzahl;

            // Preisvariante Beschreibung
            $preisvariante = $this->ermittelnPreisbeschreibung(
                $spracheId,
                $gebuchteProgramm['tbl_programme_preisvarianten_id']
            );

            // mehrzeilige Darstellung einer Preisvariante
            $arrayPreisvariante = $this->splitTextzeile($preisvariante, 50);

            for ($l = 0; $l < count($arrayPreisvariante); $l++) {
                $einzelnesProgramm[$j][2] = $arrayPreisvariante[$l];
                // $einzelnesProgramm[$j][4] = ' ';
                $j++;
            }

            // Abstand zum nächsten Programm
            $j++;
            $einzelnesProgramm[$j] = true;

            // Berücksichtigung Buchungspauschale
            if($gebuchteProgramm['programmdetails_id'] == $this->programmBuchungspauschaleId)
                $einzelnesProgramm = $this->korrekturBuchungspauschale($einzelnesProgramm);

            $programmbuchungen = array_merge($programmbuchungen, $einzelnesProgramm);
        }

        if (empty($programmbuchungen)) {
            $programmbuchungen = $this->keineProgrammeVorhanden();
        }

        return $programmbuchungen;
    }



    /**
     * Korrigiert den die Anzeige der Buchungspauschale
     *
     * + entfernen überflüssiger Daten des Programmes Buchungspauschale
     * + löscht erste Zeile
     * + in der zweiten Zeile wird die Uhrzeit auf 'leer' gesetzt
     *
     * @param $einzelnesProgramm
     */
    private function korrekturBuchungspauschale($einzelnesProgramm)
    {
        unset($einzelnesProgramm[0]);
        $einzelnesProgramm[1][0] = ' ';

        $einzelnesProgramm = array_merge($einzelnesProgramm);

        return $einzelnesProgramm;
    }

    /**
     * Splittet eine Zeile in mehrere Einzelzeilen auf
     * + Standardmäßiger Zeilenumbruch nach 50 Zeichen
     * + Bereitet Spaltenmodell des Pdf auf
     * + Rückgabe eines Array zur Pdf generierung
     *
     * @param $line
     * @param $zeilennummer
     * @param $spaltenNummer
     * @param int $lineLength
     * @return array
     */
    public function splitTextzeile($longLine, $lineLength = 50)
    {
        // Word Wrap
        $longLineWrap = wordwrap($longLine, $lineLength, '#!#');

        // Array der Zeilen
        $zeilen = explode("#!#", $longLineWrap);

        $longLineArray = array();
        if (!is_array($zeilen) or (count($zeilen) == 0)) {
            $longLineArray[0] = $longLine;
        } else {
            $i = 0;
            foreach ($zeilen as $zeile) {
                $longLineArray[$i] = $zeile;
                $i++;
            }
        }

        return $longLineArray;
    }

    /**
     * Ermittelt die Überschrift der Rechnungsdurchläufer
     *
     * + ID des Rechnungsdurchläufer == 0 , keine Überschrift , false
     * + ID des Rechnungsdurchläufer > 0 , eine Überschrift
     * + Überschrift entsprechend der gewählten Sprache
     *
     * @param $programmId
     * @return bool
     * @throws nook_Exception
     */
    private function ermittelnUeberschriftRechnungsDurchLaeufer($programmId)
    {
        // ermitteln ID des Rechnungsdurchläufer
        $whereId = " id = ".$programmId;
        $cols = array(
            'rechnung_durchlaeufer_id'
        );

        $select = $this->tabelleProgrammdetails->select();
        $select->from($this->tabelleProgrammdetails, $cols)->where($whereId);
        $rows = $this->tabelleProgrammdetails->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze);

        if($rows[0]['rechnung_durchlaeufer_id'] == 0)
            return false;
        // Überschrift des Rechnungsdurchläufer
        else{
            $rows = $this->tabelleRechnungenDurchlaeufer->find($rows[0]['rechnung_durchlaeufer_id'])->toArray();

            if(count($rows) <> 1)
                throw new nook_Exception($this->error_anzahl_datensaetze);

            // Sprache
            $kennzifferSprache = nook_ToolSprache::ermittelnKennzifferSprache();

            if($kennzifferSprache == 1)
                return $rows[0]['bezeichnung_de'];
            else
                return $rows[0]['bezeichnung_en'];
        }
    }
}

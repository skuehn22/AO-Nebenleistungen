<?php
/**
 * Controller Bestellung , Action erstellenPdfProgrammrechnung
 *
 * + Erstellt die Tabellen
 * + Erstellt die Spalten der Tabelle
 * + Übernimmt das Pdf Objekt und Grundwerte
 * + Übergabe Grundwerte Pdf und erstellen erste Seite
 * + Erstellt den Inhalt des Pdf
 * + Schreibt die Textblöcke nach der Rechnung
 * + Schreibt die Rechnungszusammenfasung
 * + Schreibt die Kundenadresse
 * + Schreibt die Überschrift der Rechnung
 * + Schreibt Zeile Gruppenname
 * + Schreibt die Überschrift der Programme
 * + Schreibt die Zeilen der Programme im Rechnungsblock
 *
 * @date 03.07.13
 * @file BestellungPdfProgrammrechnungShadow.php
 * @package front
 * @subpackage shadow
 */
class Front_Model_BestellungErstellenPdfProgrammrechnungShadow
{
    // Fehler
    private $error = 1840;

    protected $tabelle = array();
    protected $pimple = null;

    /** @var $modelStandardTexte Front_Model_BuchungProgrammeStandardtextePdf */
    protected $modelStandardTexte = null;
    /** @var $modelProgrammbuchung Front_Model_BuchungProgrammePdf */
    protected $modelProgrammbuchung = null;
    /** @var $pdf Front_Model_BuchungProgrammeKundePdf */
    protected $pdf = null;

    protected $buchungsnummer = null;
    protected $zaehler = null;

    /**
     * @param $buchungsnummer
     * @return Front_Model_BestellungErstellenPdfProgrammrechnungShadow
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestellungErstellenPdfProgrammrechnungShadow
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param object $modelProgrammbuchung
     */
    public function setModelProgrammbuchung($modelProgrammbuchung)
    {
        $this->modelProgrammbuchung = $modelProgrammbuchung;
    }

    /**
     * @param object $modelStandardTexte
     */
    public function setModelStandardTexte($modelStandardTexte)
    {
        $this->modelStandardTexte = $modelStandardTexte;
    }

    /**
     * Erstellt die Tabellen
     *
     * @return object
     */
    public function erstellenPimple(Pimple_Pimple $pimple)
    {
        $pimple['tabelleBuchungsnummer'] = function () {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple['tabelleProgrammbuchung'] = function () {
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleRechnungen'] = function () {
            return new Application_Model_DbTable_rechnungen();
        };

        $pimple['tabelleZahlungen'] = function () {
            return new Application_Model_DbTable_zahlungen();
        };

        $pimple['tabellePreiseBeschreibung'] = function () {
            return new Application_Model_DbTable_preiseBeschreibung();
        };

        $pimple['tabelleProgrammbeschreibung'] = function () {
            return new Application_Model_DbTable_programmbeschreibung();
        };

        $pimple['tabellePreise'] = function () {
            return new Application_Model_DbTable_preise();
        };

        $pimple['tabelleProgrammdetails'] = function(){
            return new Application_Model_DbTable_programmedetails();
        };

        $pimple['tabelleRechnungenDurchlaeufer'] = function(){
            return new Application_Model_DbTable_rechnungenDurchlaeufer();
        };

        $this->pimple = $pimple;

        return $pimple;
    }

    /**
     * Erstellt die Spalten der Tabelle
     *
     * + alle Angaben in Millimeter
     *
     * @return array
     */
    public function erstellenSpaltenTabelle()
    {
        $tabelle = array();

        $tabelle[1] = 40;
        $tabelle[2] = 50;
        $tabelle[3] = 132;
        $tabelle[4] = 142;
        $tabelle[5] = 163;
        $tabelle[6] = 110;

        $this->tabelle = $tabelle;

        return $tabelle;
    }

    /**
     * Übernimmt das Pdf Objekt und Grundwerte
     *
     * @param $pdf
     * @return Front_Model_BestellungErstellenPdfProgrammrechnungShadow
     */
    public function setPdf($pdf, $aktuelleBuchung, $pfad, $fontName)
    {
        $this->pdf = $pdf;
        $this->erstellenGrundstrukturPdf($aktuelleBuchung, $pfad, $fontName);

        return $this;
    }

    /**
     * Übergabe Grundwerte Pdf und erstellen erste Seite
     *
     * @param $aktuelleBuchung
     * @param $pfad
     * @param $fontName
     */
    private function erstellenGrundstrukturPdf($aktuelleBuchung, $pfad, $fontName)
    {
        $marginLeft = $this->pdf->mmToPoints(20);

        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsnummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        $this->pdf
            ->setPfad($pfad) // Ablagepfad Pdf
            ->setBuchungsnummer($aktuelleBuchung['buchungsnummer'])
            ->setZaehler($aktuelleBuchung['zaehler'])
            ->setAutoPageBreak() // automatischer Seitenumbruch
            ->setMargins($marginLeft) // linker Rand
            ->setTabelleMillimeter($this->tabelle) // definieren Spalten der Tabelle
            ->createColors() // festlegen der Farben
            ->createFont($fontName)
            ->createPdfFromOriginal(); // erstellen erste Seite

        return;
    }

    /**
     * Erstellt den Inhalt des Pdf
     *
     * @param $aktuelleBuchung
     * @param $pfad
     * @param $fontName
     * @return string
     */
    public function generierenPdfProgramme($aktuelleBuchung, $pfad, $fontName)
    {
        // Text Kundenblock
        $this->writeKundenblock();

        // Überschrift Rechnung
        $this->writeUeberschrift();

        // Gruppenname
        $this->writeGruppenname();

        // Ueberschrift Programme
        $this->writeUeberschriftProgramme();

        // Programmzeilen
        $this->writeProgrammzeilen();

        // Zusammenfassung der Rechnung
        $this->rechnungsZusammenfassung();

        // Textblöcke
        $this->textBloecke();

        // generieren Pdf
        $pdfDateiname = $this->pdf->output('P');

        return $pdfDateiname;
    }

    /**
     * Schreibt die Textblöcke nach der Rechnung
     *
     * + kein Seitenumbruch in der Grußformel
     * + die letzten 3 Zeilen ohne Seitenumbruch
     */
    private function textBloecke()
    {
        $textBloecke = $this->modelStandardTexte->getTextBloecke($this->zaehler);
        $anzahlTextBloecke = count($textBloecke);

        // Zeilen ohne Seitenumbruch
        $zeilenOhneSeitenumbruch = array(
            0 => $anzahlTextBloecke - 1,
            1 => $anzahlTextBloecke - 2,
            2 => $anzahlTextBloecke - 3
        );

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->pdf->setFont($font, 10, 5, 'black');

        $i = 1;
        foreach ($textBloecke as $key => $textBlock) {

            // Leerzeile
            if(is_bool($textBlock)) {
                // kein Seitenumbruch möglich
                if (in_array($i, $zeilenOhneSeitenumbruch)) {
                    $this->pdf->schreibeTextzeile(false, 0, 0, true, false);
                }
                // Seitenumbruch möglich
                else {
                    $this->pdf->schreibeTextzeile(false);
                }
            }
            // Textzeile
            else{
                // Textblock in Zeilen zerlegen
                if(strlen($textBlock) > 100){
                    $toolZeilenumbruch = new nook_ToolZeilenumbruch();
                    $textBlock = $toolZeilenumbruch
                        ->setText($textBlock)
                        ->setZeilenLaenge(100)
                        ->steuerungZeilenumbruch()
                        ->getZeilen();
                }

                // keine neue Seite
                if (in_array($i, $zeilenOhneSeitenumbruch)) {
                    // mehrere Zeilen
                    if(is_array($textBlock)){
                        foreach($textBlock as $zeile){
                            $this->pdf->schreibeTextzeile($zeile, 0, 0, true, false);
                        }
                    }
                    else
                        $this->pdf->schreibeTextzeile($textBlock, 0, 0, true, false);
                }
                // neue Seite möglich
                else {
                    if(is_array($textBlock)){
                        // mehrere Zeilen
                        foreach($textBlock as $zeile){
                            $this->pdf->schreibeTextzeile($zeile, 0, 0, true);
                        }
                    }
                    else
                        $this->pdf->schreibeTextzeile($textBlock, 0, 0, true);
                }
            }

            $i++;
        }

        return;
    }

    /**
     * Schreibt die Rechnungszusammenfasung
     */
    private function rechnungsZusammenfassung()
    {
        $rechnungZeilen = $this->modelProgrammbuchung->getRechnungszusammenfassung();

        $k = 0;
        foreach ($rechnungZeilen as $zeilennummer => $zeile) {
            foreach ($zeile as $spaltenNummer => $spaltenText) {

                // Anpassung Schrifttyp
                // Fettschrift
                if ($k == 0 or $k == 1) {
                    $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
                    $this->pdf->setFont($font, 10, 5, 'black');
                } // Normalschrift
                else {
                    $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
                    $this->pdf->setFont($font, 10, 5, 'black');
                }
                $k++;

                // Rechnungszeile
                if (is_array($zeile)) {
                    $this->pdf->schreibeTextzeile($spaltenText, 4, $spaltenNummer, false);
                } // Leerzeile
                else {
                    $this->pdf->schreibeTextzeile(false);
                }
            }
            $this->pdf->ln(4);
        }

        $this->pdf->ln(3);
        $this->pdf->drawLines(1);
        $this->pdf->ln(3);

        return;
    }

    /**
     * Schreibt die Kundenadresse
     *
     * @param $modelBuchungProgrammeKundePdf
     */
    private function writeKundenblock()
    {
        $kundenadresse = $this->modelStandardTexte->getKundenblock();

        // Startwert / Tiefwert der Seite
        $this->pdf->setY(67);

        for ($i = 0; $i < count($kundenadresse) - 1; $i++) {
            $this->pdf->schreibeTextzeile($kundenadresse[$i], 4);

            if($i == 2)
                $this->pdf->ln();
        }

        // letzte Zeile
        $this->pdf->schreibeTextzeile($kundenadresse[$i], 20);

        return;
    }

    /**
     * Schreibt die Überschrift der Rechnung
     *
     * @param $modelBuchungProgrammeKundePdf
     * @return mixed
     */
    private function writeUeberschrift()
    {
        $ueberschrift = $this->modelStandardTexte->getUeberschrift();
        $datum = $this->modelStandardTexte->getDatum();

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $this->pdf->setFont($font, 10, 5, 'black');
        $this->pdf->schreibeTextzeile($ueberschrift, 4, 0, false);

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->pdf->setFont($font, 10, 5, 'black');
        $this->pdf->schreibeTextzeile($datum, 4, 4);

        return;
    }

    /**
     * Schreibt Zeile Gruppenname
     *
     * @param $modelBuchungProgrammeKundePdf
     * @return mixed
     */
    private function writeGruppenname()
    {
        $gruppenname = $this->modelStandardTexte->getGruppenname();
        $this->pdf->setY(107);

        if (!empty($gruppenname)) {
            $gruppe = translate('Gruppenname');
            $gruppe .= ": " . $gruppenname;
            $this->pdf->schreibeTextzeile($gruppe, 0, 0);
        }

        $this->pdf->ln(5);

        return;
    }

    /**
     * Schreibt die Überschrift der Programme

     */
    private function writeUeberschriftProgramme()
    {
        /** @var  $modelProgrammbuchung Front_Model_BuchungProgrammeVeraenderungsbuchungPdf */
        $modelProgrammbuchung = $this->modelProgrammbuchung;
        $kopfzeileProgramme = $modelProgrammbuchung->kopfProgrammzeile();

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->pdf->setFont($font, 10, 5, 'black');

        foreach ($kopfzeileProgramme as $key => $value) {
            $this->pdf->schreibeTextzeile($value, 4, $key, false);
        }

        $this->pdf->ln(3);
        $this->pdf->drawLines(1);

        return;
    }

    /**
     * Ermittelt die Daten der Programme und schreibt die Zeilen der Programme im Rechnungsblock
     *
     * + ermitteln Daten der Programmzeilen
     * + schreiben der Programme im Pdf
     */
    private function writeProgrammzeilen()
    {
        // ermitteln Programme
        $programmZeilen = $this->modelProgrammbuchung
            ->steuerungProgrammZeilen()
            ->getProgrammZeilen();

        // schreiben der Programme im Pdf
        foreach ($programmZeilen as $zeilennummer => $zeile) {
            foreach ($zeile as $spaltenNummer => $spaltenText) {

                // Rechnungszeile / Rechnungsdurchläufer
                if(is_array($zeile) and count($zeile) == 1){
                    $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
                    $this->pdf->setFont($font, 10, 5, 'black');

                    $this->pdf->schreibeTextzeile($spaltenText, 4, $spaltenNummer, false);

                    $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
                    $this->pdf->setFont($font, 10, 5, 'black');
                }
                // allgemeine Rechnungszeile
                elseif (is_array($zeile)) {

                    // schreiben der einzelnen Teile der Rechnungszeile
                    $this->pdf->schreibeTextzeile($spaltenText, 4, $spaltenNummer, false);
                }
                // Leerzeile
                else {
                    $this->pdf->schreibeTextzeile(false);
                }
            }

            $this->pdf->ln(4);
        }

        $this->pdf->ln(3);
        $this->pdf->drawLines(1);

        return;
    }

}

<?php
/**
 * Erstellt das Kunden Pdf
 *
 *
 */
class Front_Model_RechnungPdfProgramme extends nook_ToolModel implements arrayaccess{

    protected $_hochWert = 670; // Hochwert Pdf Dokument
    protected $_rechtsWert = 50; // Abstand vom linken Rand
    protected $_zeilenAbstandKlein = 10;
    protected $_zeilenAbstandGross = 30;

    protected $_obererRand = 670;
    protected $_untererRand = 150;

    protected $_seitennummer = 0;

    /** Spalten des Dokument **/
    protected $_linie1 = 50;
    protected $_linie2 = 100;
    protected $_linie3 = 130;
    protected $_linie4 = 350;
    protected $_linie5 = 400;
    protected $_linie6 = 450;
    protected $_linie7 = 500;

    protected $_nettoGesamt = null;
    protected $_bruttoGesamt = null;
    protected $_summeBruttoMitMwst7 = null;
    protected $_summeBruttoMitMwst19 = null;

    protected $_newPdfProgramme = null;
    protected $_font = null;

    protected $_fontTexte = array();
    protected $_metaDaten = array();

    /** Daten aus Fremdmodel **/
    protected $_statischeTexte = null;
    protected $_datenRechnung = null;
    protected $_kundenDaten = null;
    protected $_kundenId = null;
    protected $_buchungsNummerId = null;
    protected $_gebuchteProgramme = null;

    private $_pfad = null;

    // Fehler
    private $_error_kein_array = 1360;
    private $_error_unbekannter_schrift_typ = 1361;
    private $_error_unbekannte_schrift_auspraegung = 1362;

    // Tabellen / Views

    // Konditionen
    private $_condition_deutsche_sprache = 1;
    private $_condition_buchungstyp_offlinebuchung = 1;

    // Flags

    public function __construct(){
        $this->_pfad = realpath(dir(__FILE__)."../pdf/");
    }


    /**
     * Übernimmt Programmdaten,
     * Kundendaten und Anbieterdaten
     *
     * @param $__fremdModel
     * return Front_Model_RechnungPdfProgramme
     */
    public function setDaten($__fremdModel){
        $this->_importModelData($__fremdModel);

        $this->_statischeTexte = $this->_modelData['_statischeTexte'];
        $this->_datenRechnung = $this->_modelData['_datenRechnung'];
        $this->_kundenDaten = $this->_modelData['_kundenDaten'];
        $this->_kundenId = $this->_modelData['_kundenId'];
        $this->_buchungsNummerId = $this->_modelData['_buchungsNummerId'];
        $this->_gebuchteProgramme = $this->_modelData['_gebuchteProgramme'];

        return $this;
    }

    /**
     * Steuerung zur Erstellung der Pdf Datei
     *
     * @return mixed
     */
    public function erstellePdf(){

        $nameNeuePdfDatei = $this
            ->_erstellenGrunddokument()
            ->_blockKundenAdresse()
            ->_blockKopfRechnung() // erstellt Kopf Rechnungsblock
            ->_blockRechnung() // Zeilen der Rechnung
            ->_blockAbschlussRechnung() // Fussteil der Rechnung
            ->_blockBuchungstyp() // Hinweis Buchungstypen
            ->_blockInformation() // Kontonummer
            ->_blockGruss()
            ->_savePdf();

        return $nameNeuePdfDatei;
    }

    /**
     * Süpeichert das Pdf der programmbuchung
     *
     * @return string
     */
    private function _savePdf(){

        /** @var $newPdf Zend_pdf */
        $newPdf = $this->_newPdfProgramme;

        $nameNeuePdfDatei = $this->_pfad."/P_".$this->_buchungsNummerId."_1.pdf";
        $dateiName = $newPdf->save($nameNeuePdfDatei);

        return $nameNeuePdfDatei;
    }

    /**
     * Erstellt den Block der Grussformel
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    private function _blockGruss(){

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Mit freundlichem Gruß";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Stephanie Ulbrich";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');



        return $this;
    }

    /**
     * Erstellt den Block der allgemeinen Information
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    private function _blockInformation(){

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Bitte überweisen Sie den Rechnungsbetrag innerhalb von 2 Wochen nach Rechnungserhalt unter Angabe der Rechnungsnummer";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "auf das Konto der Herden Studienreisen Berlin GmbH,";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Konto 1460799,";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Deutsche Bank, BLZ 100 700 24.";
        $information = translate($information);
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');
        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Überweisungen aus dem Ausland bitte inklusive sämtlicher Bankgebühren und Spesen an die";
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $information = "Deutsche Bank, IBAN: DE70 10070024 0146079900, BIC: DEUT DE DB BER.";
        $page->drawText($information,$this->_linie1, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        return $this;
    }


    private function _blockBuchungstyp(){

        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        $toolPdf = new nook_ToolPdf();
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);
        $toolPdf->setHPadding(0);
        $page = $this->_setPageSchrift($page);

        //$textBuchungstyp = "Die mit * gekennzeichneten Programme sind noch in der Testphase. Die Buchung wird erst rechtsverbindlich, wenn wir Ihnen";
        //$textBuchungstyp = translate($textBuchungstyp);
        //$page->drawText($textBuchungstyp,$this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        //$textBuchungstyp = "innerhalb von max.3 Werktagen eine separate Bestätigung per E-mail schicken.";
        //$textBuchungstyp = translate($textBuchungstyp);
        //$page->drawText($textBuchungstyp,$this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        return $this;
    }


    /**
     * Erstellt die Tabelle der Rechnungsdaten der Programme
     * sowie die Zusammenfasung.
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    private function _blockKopfRechnung(){

        // Tabelle Grundwerte
        $toolPdf = new nook_ToolPdf();
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);
        $toolPdf->setHPadding(0);

        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
        $page = $this->_setPageSchrift($page, 'fett');

        $datum = translate('Datum');
        $page->drawText($datum,$this->_linie1, $this->_hochWert, 'UTF-8');

        $anzahl = translate('no.');
        $page->drawText($anzahl,$this->_linie2, $this->_hochWert, 'UTF-8');

        $programm = translate('Programm');
        $page->drawText($programm,$this->_linie3, $this->_hochWert, 'UTF-8');

        $mwst = translate('USt.');
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie5)->setText($mwst)->berchneRechtswert();
        $page->drawText($mwst,$rechtswert, $this->_hochWert, 'UTF-8');

        $einzelpreis = translate('Einzelpreis');
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie6)->setText($einzelpreis)->berchneRechtswert();
        $page->drawText($einzelpreis,$rechtswert, $this->_hochWert, 'UTF-8');

        $gesamt = translate('gesamt');
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamt)->berchneRechtswert();
        $page->drawText($gesamt,$rechtswert, $this->_hochWert, 'UTF-8');

        $page = $this->_setPageSchrift($page);

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        return $this;
    }

    /**
     * Erstellt die Zeilen der Rechnung
     *
     * + Datum
     * + Anzahl
     * + Mwst
     * + Einzelpreis
     * + Gesamtpreis
     * + Programmbeschreibung
     * + Preisvariante
     *
     * @see nook_ToolBuchungstyp
     * return Front_Model_RechnungPdfProgramme
     */
    private function _blockRechnung(){
        $toolPdf = new nook_ToolPdf();
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);
        $toolPdf->setHPadding(0);

        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
        $page = $this->_setPageSchrift($page);

        // Schleife
        for($i=0; $i < count($this->_gebuchteProgramme); $i++){

            $toolPdf->setHPadding(0);
            $programm = $this->_gebuchteProgramme[$i];

            // Datum
            $datum =   nook_ToolDatum::wandleDatumEnglischInDeutsch($programm['datum']);
            $page->drawText($datum,$this->_linie1, $this->_hochWert, 'UTF-8');
            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

            // Zeit, Formatierung Zeit
            $zeit = nook_ToolZeiten::kappenZeit($programm['zeit'], 2);

            $page->drawText($zeit,$this->_linie1, $this->_hochWert, 'UTF-8');
            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

            // Anzahl
            $page->drawText($programm['anzahl'],$this->_linie2, $this->_hochWert, 'UTF-8');

            // Mehrwertsteuer
            $mehrwertsteuer = $programm['programmvariante']['mwst'] * 100;
            $mehrwertsteuer .= " %";
            $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie5)->setText($mehrwertsteuer)->berchneRechtswert();
            $page->drawText($mehrwertsteuer,$rechtswert, $this->_hochWert, 'UTF-8');

            $einzelpreis = $programm['programmvariante']['verkaufspreis'];
            $anzahl = $programm['anzahl'];

            if($mehrwertsteuer == 7)
                $this->_summeBruttoMitMwst7 += $einzelpreis * $anzahl;
            else
                $this->_summeBruttoMitMwst19 += $einzelpreis * $anzahl;

            // Tabelle
            $toolPdf->setHPadding(0);

            // Einzelpreis
            $einzelpreis = number_format($einzelpreis,2,',','');
            $einzelpreis .= " €";
            $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie6)->setText($einzelpreis)->berchneRechtswert();
            $page->drawText($einzelpreis,$rechtswert, $this->_hochWert, 'UTF-8');

            // Gesamtpreis
            $gesamtpreis = $programm['anzahl'] * $programm['programmvariante']['verkaufspreis'];
            $this->_bruttoGesamt += $gesamtpreis;
            $gesamtpreis = number_format($gesamtpreis,2,',','');
            $gesamtpreis .= " €";
            $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamtpreis)->berchneRechtswert();
            $page->drawText($gesamtpreis,$rechtswert, $this->_hochWert, 'UTF-8');

            // Programmbeschreibung mehrzeilig
            $programmbeschreibung = $programm['programmbeschreibung']['progname'];
            $programmbeschreibung = wordwrap($programmbeschreibung,50,'#');
            $programmbeschreibungTeile = explode('#',$programmbeschreibung);

            for($j = 0; $j < count($programmbeschreibungTeile); $j++){

                if($j == 0){
                    $toolBuchungstyp = new nook_ToolBuchungstyp();
                    $buchungstyp = $toolBuchungstyp
                        ->isValidProgrammId($programm['programmdetails_id'])
                        ->setProgrammId($programm['programmdetails_id'])
                        ->ermittleBuchungstypProgramm();

                    if($buchungstyp == $this->_condition_buchungstyp_offlinebuchung)
                        $programmbeschreibungTeile[$i] .= "  *";
                }


                $page->drawText($programmbeschreibungTeile[$j],$this->_linie3, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
            }

            // Programmvariante mehrzeilig
            $programmvariante = $programm['programmvariante']['preisvariante_de'];
            $programmvariante = wordwrap($programmvariante,50,'#');
            $programmvarianteTeile = explode('#',$programmvariante);
            foreach($programmvarianteTeile as $programmvarianteLine){
                $page->drawText($programmvarianteLine,$this->_linie3, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
            }

            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
        } // Ende Schleife

        return $this;
    }

    /**
     * Erstellt den Abschluss der Rechnung
     *
     * + Gesamtsumme
     * + Nettosumme Mwst 7%
     * + Nettosumme Mwst 19%
     *
     * @return $this
     */
    private function _blockAbschlussRechnung(){

        $toolPdf = new nook_ToolPdf();
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);
        $toolPdf->setHPadding(0);

        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
        $page = $this->_setPageSchrift($page);

        $toolPdf->setHPadding(0);

        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        // Tabellen - Zeile
        $page = $this->_setPageSchrift($page, 'fett');
        $gesamtbetragBezeichnung = translate('Zu zahlender Gesamtbetrag');
        $page->drawText($gesamtbetragBezeichnung,$this->_linie3, $this->_hochWert, 'UTF-8');

        $gesamtbetrag = number_format($this->_bruttoGesamt,2,',','');
        $gesamtbetrag .= " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamtbetrag)->berchneRechtswert();
        $page->drawText($gesamtbetrag,$rechtswert, $this->_hochWert, 'UTF-8');

        $page = $this->_setPageSchrift($page);
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        // Netto
        $nettoMwst19 = $this->_summeBruttoMitMwst19 / 1.19;
        $nettoMwst7 = $this->_summeBruttoMitMwst7 / 1.07;

        // Tabellen - Zeile Netto
        $bezeichnungNetto = translate('Summe netto');
        $summeNetto = $nettoMwst19 + $nettoMwst7;
        $summeNetto = number_format($summeNetto,2,',','');
        $summeNetto .= " €";

        $page->drawText($bezeichnungNetto,$this->_linie3, $this->_hochWert, 'UTF-8');

        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie5)->setText($summeNetto)->berchneRechtswert();
        $page->drawText($summeNetto,$rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        // Tabellen - Zeile 19%
        $mwst19Bezeichnung = translate('enthaltene USt. 19%');
        $page->drawText($mwst19Bezeichnung,$this->_linie3, $this->_hochWert, 'UTF-8');
        $mwst19 = $this->_summeBruttoMitMwst19 / 119 * 19;
        $mwst19 = number_format($mwst19,2,',','');
        $mwst19 .= " €";

        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie5)->setText($mwst19)->berchneRechtswert();
        $page->drawText($mwst19,$rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];

        // Tabellen -Zeile
        $mwst7Bezeichnung = translate('enthaltene USt. 7%');
        $mwst7 = $this->_summeBruttoMitMwst7 / 107 * 7;
        $mwst7 = number_format($mwst7,2,',','');
        $mwst7 .= " €";
        $page->drawText($mwst7Bezeichnung,$this->_linie3, $this->_hochWert, 'UTF-8');

        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie5)->setText($mwst7)->berchneRechtswert();
        $page->drawText($mwst7,$rechtswert, $this->_hochWert, 'UTF-8');

        return $this;
    }

    /**
     * festlegen der Schriften
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    public function setSchriften($__fontTexte = false){

        if(!is_array($__fontTexte))
            throw new nook_Exception($this->_error_kein_array);

        $this->_fontTexte = $__fontTexte;

        return $this;
    }

    /**
     * Erstellt den Block der Kundenadresse
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    private function _blockKundenAdresse(){

        $page = $this->_newPdfProgramme->pages[$this->_seitennummer];
        $page = $this->_setPageSchrift($page);

        $page->drawText($this->_kundenDaten['title']." ".$this->_kundenDaten['firstname']." ".$this->_kundenDaten['lastname'],$this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page->drawText($this->_kundenDaten['street']." ".$this->_kundenDaten['housenumber'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page->drawText($this->_kundenDaten['zip']." ".$this->_kundenDaten['city'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand(true);
        $this->_abstand(true);
        $this->_abstand(true);

        $page = $this->_setPageSchrift($page, 'fett', 'ueberschrift');
        $rechnung = translate('Rechnung Nr.: HOB ');
        $page->drawText($rechnung." P".$this->_buchungsNummerId."-1", $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $page = $this->_setPageSchrift($page);

        $datum = date("d.m.Y", time());
        $datum = "Berlin, den ".$datum;
        $page->drawText($datum, $this->_linie6, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');
        // Gruppenname
        $gruppe = translate('Gruppenname: ');
        $page->drawText($gruppe, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $gruppenname = nook_ToolBuchungsnummer::getGruppenname();
        $page->drawText($gruppenname, $this->_linie3, $this->_hochWert, 'UTF-8');


        $this->_abstand(true);

        return $this;
    }

    /**
     * Erstellt die erste Seite
     * des Pdf der Programmbuchungen
     *
     * @return Front_Model_RechnungPdfProgramme
     */
    private function _erstellenGrunddokument(){

        $this->_newPdfProgramme = Zend_Pdf::load($this->_pfad."/HOB_Briefpapier.pdf");

        return $this;
    }

    private function _getVorlage(){


        $this->_newPdfProgramme = Zend_Pdf::load($this->_pfad."/HOB_Briefpapier.pdf");

        return $this;
    }




    /**
     * Generiert einen Zeilenumbruch.
     *
     * Der Hochwert wird neu ermittelt.
     * Bei Bedarf wird eine neue Seite generiert.
     * Die neue Seite wird mit Standardwerten generiert.
     *
     *
     * @param bool $abstand
     */
    private function _abstand($abstand = false){

        if($abstand === false){
            $this->_hochWert -= $this->_zeilenAbstandKlein;
        }
        elseif($abstand === 'half'){
            $this->_hochWert -= $this->_zeilenAbstandGross / 2;
        }
        elseif($abstand === true){
            $this->_hochWert -= $this->_zeilenAbstandGross;
        }


        // erstellt neue Seite
        if($this->_hochWert <= $this->_untererRand){

            $this->_hochWert = $this->_obererRand;

            // eine neue Seite zum Dokument, Schriftart und Schriftgröße als Standardwerte
            $page = $this->_newPdfProgramme->newPage(Zend_Pdf_Page::SIZE_A4);
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $this->_font = $font;
            $page->setFont($font, $this->_fontTexte['text']['groesse']);

            // einfügen Logo auf der neuen Seite
            $image = Zend_Pdf_Image::imageWithPath($this->_pfad."/vorlagen/aohostel-logo.png");
            $page->drawImage($image, 300, 720, 550, 804);

            // aktueller Seitenzaehler
            $this->_seitennummer++;
            $this->_newPdfProgramme->pages[$this->_seitennummer] = $page;
        }

        return;
    }


    /**
     * Verändert den Schrifttyp in der Seite
     *
     * Zur Auswahl steht 'normal' und 'fett'
     * als Schriftausprägung.
     * Standard ist 'normal'.
     *
     * Die Schriftgröße ist vorgegeben mit
     * 'minitext', 'text' und 'ueberschrift'.
     * Standard ist 'text'.
     *
     * @param $__page
     * @param string $__schriftTyp
     * @param string $__schriftGroesse
     * @return mixed
     */
    private function _setPageSchrift($__page, $__schriftTyp = 'normal', $__schriftGroesse = 'text'){

        // Ausprägung Schrift
        if($__schriftTyp === 'normal'){
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        }
        elseif($__schriftTyp ==='fett'){
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        }
        else{
            throw new nook_Exception($this->_error_unbekannte_schrift_auspraegung);
        }

        // Schriftgroesse / Schrifttyp
        if($__schriftGroesse === 'minitext'){
            $schriftGroesse = $this->_fontTexte['minitext']['groesse'];
        }
        elseif($__schriftGroesse === 'text'){
            $schriftGroesse = $this->_fontTexte['text']['groesse'];
        }
        elseif($__schriftGroesse === 'ueberschrift'){
            $schriftGroesse = $this->_fontTexte['ueberschrift']['groesse'];
        }
        else{
            throw new nook_Exception($this->_error_unbekannter_schrift_typ);
        }

        $this->_font = $font;
        $__page->setFont($font, $schriftGroesse);

        return $__page;
    }

} // end class
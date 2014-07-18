<?php
/**
* Erstellt das Pdf der Hotelrechnung
*
* @date 11.04.13
* @file RechnungPdfUebernachtung.php
* @package front
* @subpackage model
*/
class Front_Model_RechnungPdfUebernachtung extends nook_ToolModel implements arrayaccess
{

    protected $_hochWert = 670; // Hochwert Pdf Dokument
    protected $_rechtsWert = null; // Abstand vom linken Rand
    protected $_zeilenAbstandKlein = 10;
    protected $_zeilenAbstandGross = 30;

    protected $_obererRand = 670;
    protected $_untererRand = 150;

    protected $_seitennummer = 0;

    /** Spalten des Dokument **/
    protected $_linie1 = null;
    protected $_linie2 = null;
    protected $_linie3 = null;
    protected $_linie4 = null;
    protected $_linie5 = null;
    protected $_linie6 = null;
    protected $_linie7 = null;

    protected $_nettoGesamt = null;
    protected $_bruttoGesamt = null;
    protected $_summeBruttoMitMwst7 = null;
    protected $_summeBruttoMitMwst19 = null;

    protected $stornoBedingungen = array(
        'folgende Stornobedingungen sind gültig',
        'für Gruppen ab 10 Personen:',
        'bis 4 Wochen vor Anreise 0%',
        'bis 2 Wochen vor Anreise 50%',
        'bis 8 Tage vor Anreise 75%',
        'ab 7 Tage vor Anreise 90%',
        'des vereinbarten Gesamtpreises',
        ' ',
        'Gruppen ab 20 Personen:',
        'bis 8 Wochen vor Anreise 0%',
        'bis 4 Wochen vor Anreise 50%',
        'bis 8 Tage vor Anreise 75%',
        'ab 7 Tage vor Anreise 90%',
        'des vereinbarten Gesamtpreises'

    );

    protected $zahlungsziele = array(
        '1. Zahlungsziel: bis 8 Wochen vor Anreise 50% des Gesamtpreises',
        '2. Zahlungsziel: Restbetrag bis 4 Wochen vor Anreise'
    );

    protected $_newPdfProgramme = null;
    protected $_font = null;

    protected $_fontTexte = array();
    protected $_metaDaten = array();

    protected $_teilrechnung = 0;

    /** Daten aus Fremdmodel **/
    protected $_statischeTexte = null;
    protected $_datenRechnung = null;
    protected $_kundenDaten = null;
    protected $_kundenId = null;
    protected $_buchungsNummerId = null;
    protected $registrierungsNummer = null;
    protected $_gebuchteUebernachtungen = null;
    protected $_gebuchteProdukte = null;

    private $_pfad = null;

    // Fehler
    private $_error_kein_array = 1360;
    private $_error_unbekannter_schrift_typ = 1361;
    private $_error_unbekannte_schrift_auspraegung = 1362;

    // Tabellen / Views

    // Konditionen

    // Flags

    // Container
    private $_pimple = null;

    public function __construct ($pimple = false)
    {
        if(!empty($pimple)) {
            $this->_pimple = $pimple;
        }

        $this->_pfad = realpath(dir(__FILE__) . "../pdf/");
    }

    /**
     * festlegen der Schriften
     *
     * @param bool $__fontTexte
     * @return $this
     * @throws nook_Exception
     * return Front_Model_RechnungPdfUebernachtung
     */
    public function setSchriften ($__fontTexte = false)
    {

        if(!is_array($__fontTexte)) {
            throw new nook_Exception($this->_error_kein_array);
        }

        $this->_fontTexte = $__fontTexte;

        return $this;
    }

    /**
     * @param $registrierungsNummer
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function setRegistrierungsNummer($registrierungsNummer)
    {
        $registrierungsNummer = (int) $registrierungsNummer;
        $this->registrierungsNummer = $registrierungsNummer;

        return $this;
    }

    /**
     * Erstellen der Spalten im Pdf Dokument
     *
     * Legt die einzelnen Spalten Fest.
     * Spaltenmodell hat 6 Spalten
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function erstellenPdfSpalten ()
    {

        $this->_erstellenRaster(1, 19); // Linie 1
        $this->_erstellenRaster(2, 40); // Linie 2
        $this->_erstellenRaster(3, 100); // Linie 3
        $this->_erstellenRaster(4, 120); // Linie 4
        $this->_erstellenRaster(5, 135); // Linie 5
        $this->_erstellenRaster(6, 160); // Linie 6
        $this->_erstellenRaster(7, 180); // Linie 7

        return $this;
    }

    /**
     * Speichert die Buchungsnummer
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function setBuchungsnummer ()
    {
        /** @var $toolPdf nook_ToolPdf */
        $this->_buchungsNummerId = $this->_pimple[ 'buchungsnummerId' ];

        return $this;
    }

    /**
     * Erstellt Grunddokument Pdf
     *
     * Erstellt Grunddokument und setzt die Kundenadresse
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function erstellenGrunddokument ()
    {
        $this->_erstellenGrunddokument();
        $this->_erstellenFaltmarken(); // Faltmarken Briefpapier
        $this->_blockKundenAdresse(); // Daten Kunde, Buchungsnummer

        return $this;
    }

    /**
     * Erstellt die Faltmarken des Briefpapier
     *
     * + Faltmarke für Brief mit Adressfenster 197 mm von unten
     * + Faltmarke für Lochung 148 mm von unten
     */
    private function _erstellenFaltmarken ()
    {

        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $laengeFaltmarke = $toolPdf->mmToPoints(2);

        // Faltmarke Breif mit Adressfenster
        $hochwert = $toolPdf->mmToPoints(197);
        $page->drawLine(0, $hochwert, $laengeFaltmarke, $hochwert);

        // Faltmarke Lochung
        $hochwert = $toolPdf->mmToPoints(148.5);
        $page->drawLine(0, $hochwert, $laengeFaltmarke, $hochwert);

        return;
    }

    /**
     * Erstellen die Rechnungsblöcke der Hotelübernachtung
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function erstellenRechnungsBloeckeUebernachtung ()
    {
        $teilrechnung = 0;

        // Beginn der Rechnungsblöcke
        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $this->_hochWert = $toolPdf->mmToPoints(185);

        // Rechnungsblöcke Übernachtungen
        for($i = 0; $i < count($this->_gebuchteUebernachtungen); $i++) {
            $this->_erstellenRechnungsBloeckeUebernachtung($this->_gebuchteUebernachtungen[$i]);
        }

        return $this;
    }

    /**
     * Erstellt Datenzeilen der Übernachtung in einem Hotel
     *
     * + Nummer der Teilrechnung
     * + horizontale Linien
     * + Kopfzeile der Rechnung
     * + speichern Teilrechnungsnummer
     *
     * @param $__uebernachtung
     */
    private function _erstellenRechnungsBloeckeUebernachtung($__uebernachtung){

        $teilrechnung = 0;

        // Hotelangaben
        if(($this->_teilrechnung == 0) or ($this->_teilrechnung != $__uebernachtung[ 'teilrechnungen_id' ])) {

            $this->_teilrechnung = $__uebernachtung['teilrechnungen_id']; // speichern Teilrechnungsnummer

            $this->_drawHorizontaleLinie(); // horizontale Linie

            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

            $this->_zusatzangabenUebernachtungBuchung($__uebernachtung); // Hotel , Aufenthalt, Gruppenname
            $this->_blockRechnungKopfzeileUebernachtung(); // Kopfzeile der Rechnung Uebernachtung
            $this->_drawHorizontaleLinie(); // horizontale Linie
        }

        // Übernachtung in einem Hotel
        $this->_erstelleZeileRechnungUebernachtung($__uebernachtung); // Rechnungszeile Übernachtung

        // Zusatzprodukte des Hotels
        if(($teilrechnung == 0) or ($teilrechnung != $__uebernachtung[ 'teilrechnungen_id' ])) {

            $arrayGebuchteProdukteEinesHotels = $this->_kontrolleZuordnungProduktZuHotel(
                $__uebernachtung[ 'teilrechnungen_id' ]
            );

            // eintragen Zusatzprodukte eines Hotels
            if((is_array($arrayGebuchteProdukteEinesHotels)) and (count($arrayGebuchteProdukteEinesHotels) > 0)) {
                $this->_erstelleZeilenRechnungProdukte(
                    $arrayGebuchteProdukteEinesHotels
                );
            } // Rechnungszeile Übernachtung
        }

        // neue Hotelbuchung registrieren
        $teilrechnung = $__uebernachtung[ 'teilrechnungen_id' ];

        // Notiz: einfügen Summenblock Teilrechnung
        // $this->darstellenZwischensumme();
        $this->_drawHorizontaleLinie(); // horizontale Linie

        return;
    }

    /**
     * Erstellt die Zeilen der Rechnung Zusatzprodukte
     *
     * Ergänzt Datum und Preis. Erstellt für jedes Produkt ein Array.
     *
     * @param array $__zusatzprodukteEinesHotels
     */
    private function _erstelleZeilenRechnungProdukte (array $__zusatzprodukteEinesHotels)
    {

        foreach($__zusatzprodukteEinesHotels as $zusatzprodukt) {

            if(($zusatzprodukt[ 'produktTyp' ] == 1) or ($zusatzprodukt[ 'produktTyp' ] == 2) or ($zusatzprodukt[ 'produktTyp' ] == 4)) {
                $zusatzprodukteMitDatumUndPreis = $this->_zusatzproduktTypJeStueck($zusatzprodukt);
            } else {
                $zusatzprodukteMitDatumUndPreis = $this->_zusatzproduktTypJeUebernachtung($zusatzprodukt);
            }

            $this->_erstellenZeileProdukt($zusatzprodukteMitDatumUndPreis);
        }

        return;
    }

    /**
     * Schreibt die Rechnungszeile eines Zusatzproduktes eines Hotels
     *
     * @param array $__zusatzprodukteMitDatumUndPreis
     */
    private function _erstellenZeileProdukt (array $__zusatzprodukteMitDatumUndPreis)
    {
        foreach($__zusatzprodukteMitDatumUndPreis as $zusatzprodukt) {
            $this->_rechnungszeileProdukt($zusatzprodukt);
        }

        return;
    }

    /**
     * Schreibt die Rechnungszeile der Zusatzprodukte
     *
     * @param $__zusatzprodukt
     */
    private function _rechnungszeileProdukt ($__zusatzprodukt)
    {

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // Datum
        $page->drawText($__zusatzprodukt[ 'datum' ], $this->_linie1, $this->_hochWert, 'UTF-8');

        // Bezeichnung
        $page->drawText($__zusatzprodukt[ 'bezeichnung' ], $this->_linie2, $this->_hochWert, 'UTF-8');

        // Menge
        $page->drawText($__zusatzprodukt[ 'menge' ], $this->_linie3, $this->_hochWert, 'UTF-8');

        // Mwst
        $mwst = $__zusatzprodukt[ 'mwst' ];
        $mwst .= "%";
        $page->drawText($mwst, $this->_linie4, $this->_hochWert, 'UTF-8');

        // Preis
        $preisFormatiert = $__zusatzprodukt[ 'preis' ];
        $preisFormatiert = number_format($preisFormatiert, 2, ',', '');
        $preisFormatiert = $preisFormatiert . " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie6)->setText($preisFormatiert)->setHPadding(
            20
        )->berchneRechtswert();
        $page->drawText($preisFormatiert, $rechtswert, $this->_hochWert, 'UTF-8');

        // Gesamtpreis
        $gesamtPreis = $__zusatzprodukt[ 'gesamtpreis' ];
        $gesamtPreisFormatiert = number_format($gesamtPreis, 2, ',', '');
        $gesamtPreisFormatiert = $gesamtPreisFormatiert . " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamtPreisFormatiert)->setHPadding(
            20
        )->berchneRechtswert();
        $page->drawText($gesamtPreisFormatiert, $rechtswert, $this->_hochWert, 'UTF-8');

        return;
    }

    /**
     * Datum und Preis Zusatzprodukt je Stück
     *
     * Preisberechnung entsprechend dem
     * Produkttyp und Datum.
     *
     * @param $__zusatzprodukt
     * @return array
     */
    private function _zusatzproduktTypJeStueck ($__zusatzprodukt)
    {
        $produktMitPreisUndDatum = array();

        // Datum
        $produktMitPreisUndDatum[ 0 ][ 'datum' ] = nook_ToolDatum::berechneEndeZeitraum(
            $__zusatzprodukt[ 'anreisedatum' ],
            0
        );

        // Bezeichnung
        $toolZusatzprodukte = new nook_ToolZusatzprodukte();
        $toolZusatzprodukte
            ->setProduktId($__zusatzprodukt[ 'products_id' ])
            ->ermittleGlobaleInformationenZusatzprodukt();

        $produktMitPreisUndDatum[ 0 ][ 'bezeichnung' ] = $toolZusatzprodukte->getProduktName();

        // Menge
        $produktMitPreisUndDatum[ 0 ][ 'menge' ] = $__zusatzprodukt[ 'anzahl' ];

        // Preis
        $produktMitPreisUndDatum[ 0 ][ 'preis' ] = $__zusatzprodukt[ 'aktuellerProduktPreis' ];

        // Gesamtpreis
        $produktMitPreisUndDatum[ 0 ][ 'gesamtpreis' ] = $__zusatzprodukt[ 'anzahl' ] * $__zusatzprodukt[ 'aktuellerProduktPreis' ];

        // Mwst
        $produktMitPreisUndDatum[ 0 ][ 'mwst' ] = $toolZusatzprodukte->getMwst();

        if($produktMitPreisUndDatum[ 0 ][ 'mwst' ] == 7) {
            $this->_summeBruttoMitMwst7 += $produktMitPreisUndDatum[ 0 ][ 'gesamtpreis' ];
        } elseif($produktMitPreisUndDatum[ 0 ][ 'mwst' ] == 19) {
            $this->_summeBruttoMitMwst19 += $produktMitPreisUndDatum[ 0 ][ 'gesamtpreis' ];
        }

        // Brutto Gesamtpreis
        $this->_bruttoGesamt += $produktMitPreisUndDatum[ 0 ][ 'gesamtpreis' ];

        return $produktMitPreisUndDatum;
    }

    /**
     * Datum und Preis Zusatzprodukt je Übernachtung
     *
     * Preisberechnung entsprechend dem
     * Produkttyp und Datum.
     *
     * @param $__zusatzprodukt
     * @return array
     */
    private function _zusatzproduktTypJeUebernachtung ($__zusatzprodukt)
    {
        $produktMitPreisUndDatum = array();

        for($i = 0; $i < $__zusatzprodukt[ 'uebernachtungen' ]; $i++) {

            // Datum
            $produktMitPreisUndDatum[ $i ][ 'datum' ] = nook_ToolDatum::berechneEndeZeitraum(
                $__zusatzprodukt[ 'anreisedatum' ],
                $i
            );

            // Bezeichnung
            $toolZusatzprodukte = new nook_ToolZusatzprodukte();
            $toolZusatzprodukte
                ->setProduktId($__zusatzprodukt[ 'products_id' ])
                ->ermittleGlobaleInformationenZusatzprodukt();

            $produktMitPreisUndDatum[ $i ][ 'bezeichnung' ] = $toolZusatzprodukte->getProduktName();

            // Menge
            $produktMitPreisUndDatum[ $i ][ 'menge' ] = $__zusatzprodukt[ 'anzahl' ];

            // Preis
            $produktMitPreisUndDatum[ $i ][ 'preis' ] = $__zusatzprodukt[ 'aktuellerProduktPreis' ];

            // Gesamtpreis
            $produktMitPreisUndDatum[ $i ][ 'gesamtpreis' ] = $__zusatzprodukt[ 'anzahl' ] * $__zusatzprodukt[ 'aktuellerProduktPreis' ];

            // Mwst
            $produktMitPreisUndDatum[ $i ][ 'mwst' ] = $toolZusatzprodukte->getMwst();

            if($produktMitPreisUndDatum[ $i ][ 'mwst' ] == 7) {
                $this->_summeBruttoMitMwst7 += $produktMitPreisUndDatum[ $i ][ 'gesamtpreis' ];
            } elseif($produktMitPreisUndDatum[ $i ][ 'mwst' ] == 19) {
                $this->_summeBruttoMitMwst19 += $produktMitPreisUndDatum[ $i ][ 'gesamtpreis' ];
            }

            // Brutto Gesamtpreis
            $this->_bruttoGesamt += $produktMitPreisUndDatum[ $i ][ 'gesamtpreis' ];
        }

        return $produktMitPreisUndDatum;
    }

    /**
     * Kontrolliert Zuordnung Produkt zu Hotel
     *
     * Kontrolle über Teilrechnungsnummer und
     * gibt die Produkte eines Hotels zurück.
     *
     * @param int $__teilrechnungId
     * @return array
     */
    private function _kontrolleZuordnungProduktZuHotel ($__teilrechnungId)
    {
        $arrayGebuchteProdukteEinesHotels = array();

        // sichtet die gebuchten Produkte
        foreach($this->_gebuchteProdukte as $key => $produkt){
            if($produkt[ 'teilrechnungen_id' ] == $__teilrechnungId) {
                $arrayGebuchteProdukteEinesHotels[ ] = $produkt;

                unset($this->_gebuchteProdukte[$key]);
            }
        }

        $this->_gebuchteProdukte = array_values($this->_gebuchteProdukte);

        return $arrayGebuchteProdukteEinesHotels;
    }

    /**
     * Zusammenfasung der Rechnung
     *
     * + Gesamtpreise
     * + Stornobedingungen
     * + Grussformel
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    public function endeRechnung ()
    {
        $this->_blockRechnungZusammenfassung(); // Zusammenfassung der Rechnung

        $texteBettensteuer = $this->textBettensteuer(); // Texte der Bettensteuer
        if(count($texteBettensteuer) > 0)
            $this->pdfBlockTexteBettensteuer($texteBettensteuer);

        $this->schreibeZahlungsziele(); // Zahlungsziele
        $this->_schreibeStornobedingungen(); // Stornobedingungen
        $this->_schreibeInformationTagespreiseUebernachtung(); // Information Tagespreise

        $this->_schreibeGrussformel();

        return $this;
    }

    /**
     * schreibt den Block der Bettensteuer in das Pdf
     *
     * + für jede Stadt kann es einen separaten Block der Bettensteuer geben
     *
     * @param $texteBettensteuer
     */
    protected function pdfBlockTexteBettensteuer($texteBettensteuer)
    {
        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // Block Bettensteuer einer Stadt
        for($i = 0; $i < count($texteBettensteuer); $i++) {

            $textBettensteuerStadt = $texteBettensteuer[$i];

            // Style
            $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['ueberschrift']['groesse']);

            // Überschrift
            $page->drawText($textBettensteuerStadt['title'], $this->_linie1, $this->_hochWert, 'UTF-8');
            $this->_abstand('half');
            $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

            // Style
            $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);

            // wenn Stadt keine Bettensteuer hat


            /** @var $toolZeilenumbruch nook_ToolZeilenumbruch */
            $toolZeilenumbruch = $this->_pimple['toolZeilenumbruch'];
            $zeilenText = $toolZeilenumbruch
                ->setZeilenLaenge(80)
                ->setText($textBettensteuerStadt['text'])
                ->steuerungZeilenumbruch()
                ->getZeilen();

            // Text Bettensteuer
            for($j=0; $j < count($zeilenText); $j++){
                $zeileText = $zeilenText[$j];

                $page->drawText($zeileText, $this->_linie1, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
            }

            // Abstand zum nächsten Block Bettensteuer
            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        }

        return;
    }

    /**
     * Ermitteln der Texte der Bettensteuer der Städte
     *
     * + ermitteln der City ID der Srädte entsprechend der Hotelbuchungen
     * + ermitteln der Texte der Bettensteuer der Städte. Überschrift und Text entsprechend der Anzeigesprache
     *
     */
    /**
     * @return Front_Model_RechnungPdfUebernachtung
     */
    protected function textBettensteuer()
    {
        /** @var $toolSprache nook_ToolSprache */
        $toolSprache = $this->_pimple['toolSprache'];
        $spracheId = $toolSprache->ermittelnKennzifferSprache();

        /** @var $tabelleAoCityBettensteuer Zend_Db_Table_Abstract */
        $tabelleAoCityBettensteuer = $this->_pimple['tabelleAoCityBettensteuer'];

        /** @var $frontModelUniqueRowsArray Front_Model_UniqueRowsArray */
        $frontModelUniqueRowsArray = $this->_pimple['frontModelUniqueRowsArray'];

        /** @var $frontModelBettensteuerStadt Front_Model_BettensteuerStadt */
        $frontModelBettensteuerStadt = $this->_pimple['frontModelBettensteuerStadt'];

        $uniqueCols = array(
            "cityId"
        );

        $texteBettensteuer = array();
        $j = 0;

        // ermitteln der City ID der Srädte entsprechend der Hotelbuchungen
        $reduzierteDatensaetzeArray = $frontModelUniqueRowsArray
            ->setAusgangsArray($this->_gebuchteUebernachtungen)
            ->setUniqueArray($uniqueCols)
            ->setFlagSuchparameter('cityId')
            ->steuerungErmittlungUniqueRows()
            ->getReduzierteArray();

        // ermitteln der Texte der Bettensteuer der Städte.
        // Überschrift und Text entsprechend der Anzeigesprache
        $frontModelBettensteuerStadt
            ->setTabelleAoCityBettensteuer($tabelleAoCityBettensteuer)
            ->setSpracheId($spracheId);

        for($i=0; $i < count($reduzierteDatensaetzeArray); $i++){
            $reduzierteDatensaetz = $reduzierteDatensaetzeArray[$i];

            $frontModelBettensteuerStadt->setCityId($reduzierteDatensaetz['cityId']);
            $frontModelBettensteuerStadt->steuerungErmittlungBettensteuerStadt();
            $texteBettensteuer[$j]['title'] = $frontModelBettensteuerStadt->getTitleBettensteuer();
            $texteBettensteuer[$j]['text'] = $frontModelBettensteuerStadt->getKurztextBettensteuer();

            $j++;
        }

        return $texteBettensteuer;
    }

    /**
     * Schreibt die Zahlungsziele
     *
     * + Listet für alle Hotels die Stornobedingungen auf.
     */
    private function schreibeZahlungsziele()
    {

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // globale Zahlungsziele
        foreach($this->zahlungsziele as $zahlungsziel) {

            $zahlungsziel = translate($zahlungsziel);

            $page->drawText($zahlungsziel, $this->_linie1, $this->_hochWert, 'UTF-8');

            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        }

        return;
    }

    /**
     * Schreibt die Stornobedingungen
     *
     * Listet für alle Hotels
     * die Stornobedingungen auf.
     */
    private function _schreibeStornobedingungen ()
    {

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // globale Stornobedingungen
        foreach($this->stornoBedingungen as $stornoHotel) {

            $stornoHotel = translate($stornoHotel);

            $page->drawText($stornoHotel, $this->_linie1, $this->_hochWert, 'UTF-8');

            $this->_abstand();
            $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        }

        return;
    }

    private function _schreibeInformationTagespreiseUebernachtung(){

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        $infoTagespreise = translate("Die angegebenen Tagespreise entsprechen dem Mittelwert der Tagespreise des Buchungszeitraumes");

        $page->drawText($infoTagespreise, $this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Abschliesende Grußformel
     */
    private function _schreibeGrussformel ()
    {

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        $page->drawText(translate("Mit freundlichen Gruß"), $this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        $page->drawText("Stephanie Ulbrich", $this->_linie1, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Erstellt eine Zeile einer Übernachtungsbuchung
     *
     * für den aktuellen Tag. Jeder Tag und jede
     * Rate eines Hotels bilden eine eigene Rechnungszeile.
     * Umrechnung der Anzahl der Übernachtungen in ein
     * 'aktuelles Datum'.
     *
     * @param $__uebernachtung
     * @return int
     */
    private function _erstelleZeileRechnungUebernachtung ($__uebernachtung)
    {
        for($i = 0; $i < $__uebernachtung[ 'nights' ]; $i++) {

            $aktuellesDatum = nook_ToolDatum::berechneEndeZeitraum($__uebernachtung[ 'startDate' ], $i);
            $this->_rechnungszeileUebernachtung(
                $__uebernachtung,
                $aktuellesDatum
            ); // erstellt Rechnungsblock Übernachtung für den Tag

        }

        return $i;
    }

    /**
     * Ermittelt die Zusatzangaben einer Zimmerbuchung
     *
     * + Gruppenname
     * + Hotelname
     * + Stadt
     *
     * @param $__uebernachtung
     */
    private function _zusatzangabenUebernachtungBuchung ($__uebernachtung)
    {
        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $this->_abstand('half');

        // Stadt
        $stadt = translate('Stadt') . ":";
        $page->drawText($stadt, $this->_linie1, $this->_hochWert, 'UTF-8');
        $stadtName = nook_ToolStadt::getStadtNameMitStadtId($__uebernachtung[ 'cityId' ]);
        $page->drawText($stadtName, $this->_linie2, $this->_hochWert, 'UTF-8');

        // Teilrechnungsnummer
        $teilrechnungsnummer = translate('Teilrechnung');
        $teilrechnungsnummer .= ": ".$this->_teilrechnung;
        $page->drawText($teilrechnungsnummer, $this->_linie3, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Hotel
        $hotel = translate('Hotel');
        $page->drawText($hotel, $this->_linie1, $this->_hochWert, 'UTF-8');
        /** @var $toolHotel nook_ToolHotel */
        $toolHotel = $this->_pimple[ 'toolHotel' ];
        $hotelBezeichnung = $toolHotel->getHotelName($__uebernachtung[ 'propertyId' ]);
        $page->drawText($hotelBezeichnung, $this->_linie2, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Aufenthalt
        $aufenthalt = translate('Aufenthalt') . ":";
        $page->drawText($aufenthalt, $this->_linie1, $this->_hochWert, 'UTF-8');
        /** @var $zeitraum nook_ToolDatum */
        $startdatum = nook_ToolDatum::berechneEndeZeitraum($__uebernachtung[ 'startDate' ], 0);
        $enddatum = nook_ToolDatum::berechneEndeZeitraum($__uebernachtung[ 'startDate' ], $__uebernachtung[ 'nights' ]);
        $page->drawText($startdatum . " - " . $enddatum, $this->_linie2, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Speichern Pdf Hotelbuchung
     *
     * Gibt den Namen der Pdf Datei zuirück
     *
     * @return string
     */
    public function speichernPdf ()
    {
        $nameNeuePdfDatei = $this->_savePdf();

        return $nameNeuePdfDatei;
    }

    /**
     * Erstellt das Spaltenmodell des Pdf
     *
     * Es wird die Liniennummer und
     * der Milimeterwert entgegengenommen.
     * In einer dynamischen Variable wird
     * die Eigenschaft der Linie gesetzt.
     *
     * @param $__linienNummer
     * @param $__milimeter
     * @return Front_Model_RechnungPdfUebernachtung
     */
    private function _erstellenRaster ($__linienNummer, $__milimeter)
    {

        $toolPdf = new nook_ToolPdf();
        $points = $toolPdf->mmToPoints($__milimeter);

        if($__linienNummer == 1) {
            $this->_linie1 = $points;
            $this->_rechtsWert = $points;
        } elseif($__linienNummer == 2) {
            $this->_linie2 = $points;
        } elseif($__linienNummer == 3) {
            $this->_linie3 = $points;
        } elseif($__linienNummer == 4) {
            $this->_linie4 = $points;
        } elseif($__linienNummer == 5) {
            $this->_linie5 = $points;
        } elseif($__linienNummer == 6) {
            $this->_linie6 = $points;
        } elseif($__linienNummer == 7) {
            $this->_linie7 = $points;
        }

        return;
    }

    /**
     * Übernimmt Programmdaten,
     * Kundendaten und Anbieterdaten
     *
     * @param $__fremdModel
     * return Front_Model_RechnungPdfUebernachtung
     */
    public function setDaten ($__fremdModel)
    {
        $this->_importModelData($__fremdModel);

        $this->_kundenDaten = $this->_modelData[ '_kundenDaten' ];
        $this->_kundenId = $this->_modelData[ '_kundenId' ];
        $this->_gebuchteUebernachtungen = $this->_modelData[ '_datenHotelbuchungen' ];
        $this->_gebuchteProdukte = $this->_modelData[ '_datenProduktbuchungen' ];
        $this->_datenRechnung = $this->_modelData[ '_datenRechnungen' ];

        return $this;
    }

    /**
     * Speichert das Pdf der programmbuchung
     *
     * @return string
     */
    private function _savePdf ()
    {

        /** @var $newPdf Zend_pdf */
        $newPdf = $this->_newPdfProgramme;

        $nameNeuePdfDatei = $this->_pfad . "/H_Re_" . $this->registrierungsNummer . "_1.pdf";
        $dateiName = $newPdf->save($nameNeuePdfDatei);

        return $nameNeuePdfDatei;
    }

    /**
     * Berechnet die Bruttopreise Mwt 7% und 19%
     *
     * Summiert Bruttto 7%
     * Summiert Brutto 19%
     * Summiert Brutto Gesamt
     *
     * @param $__brutto
     * @param $__mwst
     */
    private function _summeBruttoMwst ($__brutto, $__mwst)
    {

        if($__mwst == 19) {
            $this->_summeBruttoMitMwst19 += $__brutto;
        } else {
            $this->_summeBruttoMitMwst7 += $__brutto;
        }

        $this->_bruttoGesamt += $__brutto;

        return;
    }

    /**
     * Erstellt die Tabelle der Rechnungsdaten der Programme
     * sowie die Zusammenfasung.
     *
     * @return
     */
    private function _blockRechnungKopfzeileUebernachtung ()
    {

        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $page = $this->_setPageSchrift($page, 'fett');
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // Datum
        $datum = translate('Datum');
        $page->drawText($datum, $this->_linie1, $this->_hochWert, 'UTF-8');

        // Bezeichnung
        $programm = translate('Bezeichnung');
        $page->drawText($programm, $this->_linie2, $this->_hochWert, 'UTF-8');

        // Anzahl
        $anzahl = translate('Anzahl');
        $page->drawText($anzahl, $this->_linie3, $this->_hochWert, 'UTF-8');

        // Mwst
        $mwst = translate('USt.');
        $page->drawText($mwst, $this->_linie4, $this->_hochWert, 'UTF-8');

        // Einzelpreis
        $einzelpreis = translate('Einzelpreis');
        $page->drawText($einzelpreis, $this->_linie5, $this->_hochWert, 'UTF-8');

        // Gesamtpreis
        $gesamt = translate('gesamt');
        $page->drawText($gesamt, $this->_linie6, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Darstellen der Rechnungsdaten
     *
     * Jeder Tag wird als eine Zeile dargetellt.
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    private function _rechnungszeileUebernachtung ($__uebernachtung, $__datum)
    {

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        // Datum
        $page->drawText($__datum, $this->_linie1, $this->_hochWert, 'UTF-8');

        // Bezeichnung
        /** @var $toolRate nook_ToolRate */
        $toolRate = $this->_pimple[ 'toolRate' ];
        $toolRate->setRateId($__uebernachtung[ 'otaRatesConfigId' ]);
        $nameRate = $toolRate->getRateName();
        $page->drawText($nameRate, $this->_linie2, $this->_hochWert, 'UTF-8');

        // Menge
        // Personen
        if($__uebernachtung[ 'personPrice' ] > 0) {
            $personen = $__uebernachtung[ 'personNumbers' ] . " " . translate('Personen');
            $page->drawText($personen, $this->_linie3, $this->_hochWert, 'UTF-8');
        } // Menge Zimmer
        else {
            $zimmer = $__uebernachtung[ 'roomNumbers' ] . " " . translate('Zimmer');
            $page->drawText($zimmer, $this->_linie3, $this->_hochWert, 'UTF-8');

        }

        // Mwst
        $mwst = $__uebernachtung[ 'mwst' ];
        $mwst .= "%";
        $page->drawText($mwst, $this->_linie4, $this->_hochWert, 'UTF-8');

        // Preis
        // Personenpreis
        if($__uebernachtung[ 'personPrice' ] > 0) {
            $preis = $__uebernachtung[ 'personPrice' ];
            $preisFormatiert = number_format($preis, 2, ',', '');
        } // Zimmerpreis
        else {
            $preis = $__uebernachtung[ 'roomPrice' ];
            $preisFormatiert = number_format($preis, 2, ',', '');
        }

        $preisFormatiert = $preisFormatiert . " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie6)->setText($preisFormatiert)->setHPadding(
            20
        )->berchneRechtswert();
        $page->drawText($preisFormatiert, $rechtswert, $this->_hochWert, 'UTF-8');

        // Gesamtpreis für den Tag
        // Personenpreis
        if($__uebernachtung[ 'personPrice' ] > 0) {
            $gesamtPreis = $__uebernachtung[ 'personPrice' ] * $__uebernachtung[ 'personNumbers' ];
            $gesamtPreisFormatiert = number_format($gesamtPreis, 2, ',', '');
        } // Zimmerpreis
        else {
            $gesamtPreis = $__uebernachtung[ 'roomPrice' ] * $__uebernachtung[ 'roomNumbers' ];
            $gesamtPreisFormatiert = number_format($gesamtPreis, 2, ',', '');
        }

        $gesamtPreisFormatiert = $gesamtPreisFormatiert . " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamtPreisFormatiert)->setHPadding(
            20
        )->berchneRechtswert();
        $page->drawText($gesamtPreisFormatiert, $rechtswert, $this->_hochWert, 'UTF-8');

        // Aufsummieren Bruttopreise
        $this->_summeBruttoMwst($gesamtPreis, $__uebernachtung[ 'mwst' ]);

        return;
    }

    /**
     * Erstellt die Zusammenfassung der Rechnung
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    private function  _blockRechnungZusammenfassung ()
    {

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $page = $this->_setPageSchrift($page, 'fett', 'ueberschrift');

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte[ 'text' ][ 'groesse' ]);

        $this->_drawHorizontaleLinie(); // horizontale Linie
        $page = $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Gesamtbetrag / Brutto
        $benennungGesamtbetrag = translate('Zu zahlender Gesamtbetrag');
        $page->drawText($benennungGesamtbetrag, $this->_linie1, $this->_hochWert, 'UTF-8');

        $page = $this->_setPageSchrift($page, 'normal', 'ueberschrift');

        $gesamtbetrag = number_format($this->_bruttoGesamt, 2, ',', '');
        $gesamtbetrag .= " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie7)->setText($gesamtbetrag)->setHPadding(
            20
        )->berchneRechtswert();
        $page->drawText($gesamtbetrag, $rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $page = $this->_setPageSchrift($page);

        // Summe Netto
        $benennungSummeNetto = translate('Summe Netto');
        $page->drawText($benennungSummeNetto, $this->_linie1, $this->_hochWert, 'UTF-8');

        $summeNetto = $this->_berechneSummeNetto();
        $summeNetto = number_format($summeNetto, 2, ',', '');
        $summeNetto .= " €";
        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie3)->setText($summeNetto)->setHPadding(
            0
        )->berchneRechtswert();
        $page->drawText($summeNetto, $rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Mwst 7%
        $benennungMwst7 = translate('enthaltene USt. 7%');
        $page->drawText($benennungMwst7, $this->_linie1, $this->_hochWert, 'UTF-8');

        $mwst7 = $this->_berechneUst7();
        $mwst7 = number_format($mwst7, 2, ',', '');
        $mwst7 .= " €";

        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie3)->setText($mwst7)->setHPadding(0)->berchneRechtswert(
        );
        $page->drawText($mwst7, $rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Mwst 19%
        $benennungMwst19 = translate('enthaltene USt. 19%');
        $page->drawText($benennungMwst19, $this->_linie1, $this->_hochWert, 'UTF-8');

        $mwst19 = $this->_berechneUst19();
        $mwst19 = number_format($mwst19, 2, ',', '');
        $mwst19 .= " €";

        $rechtswert = $toolPdf->setRechteBegrenzung($this->_linie3)->setText($mwst19)->setHPadding(
            0
        )->berchneRechtswert();
        $page->drawText($mwst19, $rechtswert, $this->_hochWert, 'UTF-8');

        // normaler Text
        $page = $this->_setPageSchrift($page);

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Berechnung Netto Gesamtsumme
     *
     * Berechnet aus
     * $this->_summeBruttoMitMwst7 und
     * $this->_summeBruttoMitMwst19 die Gesamtsumme Netto
     *
     * @return float
     */
    private function _berechneSummeNetto ()
    {

        $summeNetto7 = (($this->_summeBruttoMitMwst7 / 107) * 100);
        $summeNetto19 = (($this->_summeBruttoMitMwst19 / 119) * 100);

        return $summeNetto7 + $summeNetto19;
    }

    /**
     * Berechnet die Umsatzsteuer 19%
     *
     * Ausgangswert $this->_summeBruttoMitMwst19
     *
     * @return float
     */
    private function _berechneUst19 ()
    {

        $umsatzsteuer19 = ($this->_summeBruttoMitMwst19 / 119) * 19;

        return $umsatzsteuer19;
    }

    /**
     * Berechnet die Umsatzsteuer 7%
     *
     * Ausgangswert $this->_summeBruttoMitMwst7
     *
     * @return float
     */
    private function _berechneUst7 ()
    {

        $umsatzsteuer7 = ($this->_summeBruttoMitMwst7 / 107) * 7;

        return $umsatzsteuer7;
    }

    /**
     * Erstellt den Block der Kundenadresse
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    private function  _blockKundenAdresse ()
    {
        /** @var $toolPdf nook_ToolPdf */
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $page = $this->_setPageSchrift($page, 'normal', 'ueberschrift');

        /** @var $toolPdf nook_ToolPdf */
        $toolPdf = $this->_pimple[ 'toolPdf' ];
        $beginnAdressblock = $toolPdf->mmToPoints(241);
        $this->_hochWert = $beginnAdressblock;

        // Firma
        $page->drawText(
            $this->_kundenDaten['company'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Name
        $page->drawText(
            $this->_kundenDaten[ 'title' ] . " " . $this->_kundenDaten[ 'firstname' ] . " " . $this->_kundenDaten[ 'lastname' ],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        $page->drawText(
            $this->_kundenDaten[ 'street' ] . " " . $this->_kundenDaten[ 'housenumber' ],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );

        $this->_abstand();
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        $page->drawText(
            $this->_kundenDaten[ 'zip' ] . " " . $this->_kundenDaten[ 'city' ],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );

        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        $beginnRechnungsueberschrift = $toolPdf->mmToPoints(205);
        $this->_hochWert = $beginnRechnungsueberschrift;

        $page = $this->_setPageSchrift($page, 'fett', 'ueberschrift');
        $rechnung = translate('Rechnung Nr.:');
        $page->drawText(
            $rechnung . "HOB H" . $this->registrierungsNummer . "-1",
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $page = $this->_setPageSchrift($page);

        $datum = date("d.m.Y", time());
        $datum = "Berlin, den " . $datum;
        $page->drawText($datum, $this->_linie4, $this->_hochWert, 'UTF-8');
        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        // Gruppenname
        $gruppennameBeteichnung = translate('Gruppenname');
        $page->drawText($gruppennameBeteichnung . ":", $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $gruppenname = nook_ToolBuchungsnummer::getGruppenname();
        $page->drawText($gruppenname, $this->_linie2, $this->_hochWert, 'UTF-8');

        $page = $this->_setPageSchrift($page);
        $this->_abstand(true);
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];

        return;
    }

    /**
     * Erstellt die erste Seite
     * des Pdf der Programmbuchungen
     *
     * @return Front_Model_RechnungPdfUebernachtung
     */
    private function _erstellenGrunddokument ()
    {

        $this->_newPdfProgramme = Zend_Pdf::load($this->_pfad . "/HOB_Briefpapier.pdf");

        return;
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
    private function _abstand ($abstand = false)
    {

        if($abstand === false) {
            $this->_hochWert -= $this->_zeilenAbstandKlein;
        } elseif($abstand === 'half') {
            $this->_hochWert -= $this->_zeilenAbstandGross / 2;
        } elseif($abstand === true) {
            $this->_hochWert -= $this->_zeilenAbstandGross;
        }

        // erstellt neue Seite
        if($this->_hochWert <= $this->_untererRand) {

            $this->_hochWert = $this->_obererRand;

            // Faltmarken Briefpapier
            $this->_erstellenFaltmarken();

            // eine neue Seite zum Dokument, Schriftart und Schriftgröße als Standardwerte
            $page = $this->_newPdfProgramme->newPage(Zend_Pdf_Page::SIZE_A4);
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $this->_font = $font;
            $page->setFont($font, $this->_fontTexte[ 'text' ][ 'groesse' ]);

            // einfügen Logo auf der neuen Seite
            $image = Zend_Pdf_Image::imageWithPath($this->_pfad . "/LogoNameMini.jpg");
            $page->drawImage($image, 300, 720, 550, 804);

            // aktueller Seitenzaehler
            $this->_seitennummer++;
            $this->_newPdfProgramme->pages[ $this->_seitennummer ] = $page;
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
     * @throws nook_Exception
     */
    private function _setPageSchrift ($__page, $__schriftTyp = 'normal', $__schriftGroesse = 'text')
    {

        // Ausprägung Schrift
        if($__schriftTyp === 'normal') {
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        } elseif($__schriftTyp === 'fett') {
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        } else {
            throw new nook_Exception($this->_error_unbekannte_schrift_auspraegung);
        }

        // Schriftgroesse / Schrifttyp
        if($__schriftGroesse === 'minitext') {
            $schriftGroesse = $this->_fontTexte[ 'minitext' ][ 'groesse' ];
        } elseif($__schriftGroesse === 'text') {
            $schriftGroesse = $this->_fontTexte[ 'text' ][ 'groesse' ];
        } elseif($__schriftGroesse === 'ueberschrift') {
            $schriftGroesse = $this->_fontTexte[ 'ueberschrift' ][ 'groesse' ];
        } else {
            throw new nook_Exception($this->_error_unbekannter_schrift_typ);
        }

        $this->_font = $font;
        $__page->setFont($font, $schriftGroesse);

        return $__page;
    }

    /**
     * Zeichnet eine horizontale Linie
     *
     * vom linken Rand (Spalte 1)
     * bis rechteRand (Spalte 6)
     *
     */
    private function _drawHorizontaleLinie ()
    {

        $this->_abstand();
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $page = $this->_setPageSchrift($page);

        /** @var $page Zend_Pdf */
        $page = $this->_newPdfProgramme->pages[ $this->_seitennummer ];
        $toolPdf = $this->_pimple[ 'toolPdf' ];

        $page->drawLine($this->_linie1, $this->_hochWert, $this->_linie7, $this->_hochWert);

        return;
    }
} // end class
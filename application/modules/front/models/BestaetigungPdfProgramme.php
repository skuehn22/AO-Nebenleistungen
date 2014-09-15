<?php
/**
 * Erstellt das Bestätigungs Pdf einer Programmbuchung
 *
 * + legt die Pfade zu den Grafiken des Pdf fest
 * + Übernimmt Programmdaten,
 * + Steuerung zur Erstellung der Pdf Datei
 * + Darstellen des Buchungshinweises und der Grußformel
 * + Erstellt, wenn notwendig den Buchungshinweis
 * + Erstellt den Hinweis auf die AGB
 * + Erstellt die Kundendaten und die erste Seite
 * + Listet die gebuchten Programme auf
 * + Kontrollier ob eine Buchungspauschale vorliegt
 * + Ermittelt die Anzahl der vorhandenen Programmsprachen eines Programmes
 * + Bestimmt den Namen der Stadt
 * + Erstellt die Anrede
 * + Erstellt den Briefkopf.
 * + Süpeichert das Pdf der programmbuchung
 * + Ermitteln der Registrierungsnummer
 * + festlegen der Schriften
 * + Erstellt die erste Seite
 * + Erstellt die Icon des Pdf
 * + Generiert einen Zeilenumbruch.
 * + Verändert den Schrifttyp in der Seite
 * + Erstellt das Datum der Buchung
 * + Gibt das Datum mit dem Monatsnamen als Kurzform zurück
 *
 * @author Stephan.Krauss
 * @date 09.57.2013
 * @file BestaetigungPdfProgramme.php
 * @package front
 * @subpackage model
 */
class Front_Model_BestaetigungPdfProgramme extends nook_ToolModel implements arrayaccess
{

    /** @var intWerte Pdf * */
    protected $_hochWert = 670; // Hochwert Pdf Dokument
    protected $_rechtsWert = 50; // Abstand vom linken Rand
    protected $_zeilenAbstandKlein = 10;
    protected $_zeilenAbstandGross = 30;

    protected $_obererRand = 670;
    protected $_untererRand = 150;

    protected $_seitennummer = 0;

    /** Spalten des Dokument **/
    protected $_spalte1 = 50;
    protected $_spalte2 = 100;
    protected $_spalte3 = 150;
    protected $_spalte4 = 300;
    protected $_spalte5 = 350;
    protected $_spalte6 = 450;
    protected $_spalte7 = 550;

    private $_condition_buchungstyp_offlinebuchung = 1;

    protected $_newPdfBestaetigung = null;
    protected $_font = null;

    protected $_fontTexte = array();
    protected $_metaDaten = array();

    /** Daten aus Fremdmodel **/
    protected $_statischeTexte = null;
    protected $_datenRechnung = null;
    protected $_kundenDaten = null;
    protected $_kundenId = null;
    protected $_buchungsNummerId = null;
    protected $zaehler = null;
    protected $registrierungsnummer = null;
    protected $_gebuchteProgramme = null;

    // Fremdmodel
    protected $frontModelVertragspartner = null;

    // Pfad zu den Pdf Vorlagedateien
    private $_pfad = null;

    // Pfad zu den Icon
    private $iconPfad = null;
    private $iconOfflinebuchung = null;

    // Fehler
    private $_error_kein_array = 1380;

    // Tabellen / Views

    // Konditionen
    private $_condition_deutsche_sprache = 1;
    protected $condition_bereich_programme = 1;

    // Flags

    /**
     * legt die Pfade zu den Grafiken des Pdf fest
     */
    public function __construct()
    {
        $this->_pfad = realpath(dir(__FILE__) . "../pdf/");
        $this->iconPfad = realpath(dir(__FILE__) . "./buttons/");
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestaetigungPdfProgramme
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * Übernimmt Programmdaten,
     * Kundendaten und Anbieterdaten
     *
     * @param $__fremdModel
     * @return Front_Model_BestaetigungPdfProgramme
     */
    public function setDaten($__fremdModel)
    {
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
     * @param Front_Model_Vertragspartner $frontModelVertragspartner
     * @return Front_Model_BestaetigungPdfProgramme
     */
    public function setModelVertragspartner(Front_Model_Vertragspartner $frontModelVertragspartner)
    {
        $this->frontModelVertragspartner = $frontModelVertragspartner;

        return $this;
    }

    /**
     * @return Front_Model_Vertragspartner
     */
    public function getFrontModelVertragspartner()
    {
        if(is_null($this->frontModelVertragspartner))
            $this->frontModelVertragspartner = new Front_Model_Vertragspartner();

        return $this->frontModelVertragspartner;
    }

    /**
     * @param $registrierungsnummer
     * @return Front_Model_BestaetigungPdfProgramme
     */
    public function setRegistrierungsnummer($registrierungsnummer)
    {
        $registrierungsnummer = (int) $registrierungsnummer;
        $this->registrierungsnummer = $registrierungsnummer;

        return $this;
    }

    /**
     * Steuerung zur Erstellung der Pdf Datei
     *
     * @return mixed
     */
    public function erstellePdf($name, $proId)
    {


        $namePdfProgrammBestaetigung = $this
            ->_erstellenGrunddokument()
            ->erstellenIcon() // erstellt das Icon der Offlinebuchung
            ->_erstellenKundendaten()
            ->_erstellenAnrede()
            ->_erstellenProgrammblock($proId)
            ->_hinweisAufAgb()
            ->_erstellenGruss()
            ->_savePdf($name);


        return $namePdfProgrammBestaetigung;
    }

    /**
     * Darstellen des Buchungshinweises und der Grußformel
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenGruss()
    {

        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];
        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }


        $this->_abstand('half');
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        // Buchungshinweis
        $page = $this->erstelleBuchungshinweis($page);
        //$this->_abstand(true);
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        $gruss1 = "Gern stehen wir Ihnen für Rückfragen und Wünsche zur Verfügung.";
        $gruss1 = translate($gruss1);
        $page->drawText($gruss1, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand(true);
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $gruss2 = "Mit freundlichen Grüßen";
        $gruss2 = translate($gruss2);
        $page->drawText($gruss2, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand(true);
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $gruss3 = "Ihre A&O Reservierung";
        $page->drawText($gruss3, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        return $this;
    }

    /**
     * Erstellt, wenn notwendig den Buchungshinweis
     *
     * + wenn kein Buchungshinweis vorhanden , dann Rücksprung
     *
     * @param $page
     * @return mixed
     */
    private function erstelleBuchungshinweis($page)
    {
        // ermitteln Buchungshinweis
        $buchungshinweis = nook_ToolBuchungsnummer::getBuchungshinweisRaw();
        $buchungshinweis = trim($buchungshinweis);

        // kein Buchungshinweis vorhanden
        if (empty($buchungshinweis)) {
            return;
        }

        // Aufspalten Buchungshinweis in einzelne Zeilen
        $toolZeilenumbruch = new nook_ToolZeilenumbruch();
        $buchungshinweisArray = $toolZeilenumbruch
            ->setText($buchungshinweis)
            ->setZeilenLaenge(70)
            ->steuerungZeilenumbruch()
            ->getZeilen();

        if (count($buchungshinweisArray) > 0) {
            $hinweis1 = translate("Folgenden Buchungshinweis haben wir erhalten") . ":";
            $page->drawText($hinweis1, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            $this->_abstand();
            $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

            if ($page == NULL){
                $page = $_SESSION['pageinfo']->pages[0];
            }

            for ($i = 0; $i < count($buchungshinweisArray); $i++) {
                $page->drawText($buchungshinweisArray[$i], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }
            }

            //$this->_abstand('half');
            $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

            if ($page == NULL){
                $page = $_SESSION['pageinfo']->pages[0];
            }

            $hinweis2 = translate("Wir werden uns schnellstmöglich mit ihnen in Verbindung setzen.");
            $page->drawText($hinweis2, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            //$this->_abstand('half');
            $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

            if ($page == NULL){
                $page = $_SESSION['pageinfo']->pages[0];
            }
        }

        return $page;
    }

    /**
     * Erstellt den Hinweis auf die AGB
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _hinweisAufAgb()
    {
        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        //$this->_abstand('half');
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $agb1 = "Es gelten die allgemeinen Geschäftsbedingungen.";
        $agb1 = translate($agb1);
        $page->drawText($agb1, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $agb2 = "Sie können diese hier nochmals einsehen.";
        $agb2 = translate($agb2);
        if($anzeigeSpracheId == 1)
             $agb2 .= " http://www.aohostels.com/de/";
        else
             $agb2 .= " http://www.aohostels.com/de/";

        $page->drawText($agb2, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');

        return $this;
    }

    /**
     * Erstellt die Kundendaten und die erste Seite
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenKundendaten()
    {
        $this->_abstand(true);

        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->_font = $font;
        $page->setFont($font, $this->_fontTexte['text']['groesse']);

        $page->drawText($this->_kundenDaten['company'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $this->_abstand();

        // Anrede übersetzen
        $anrede = translate($this->_kundenDaten['title']);
        $page->drawText(
            $anrede . " " . $this->_kundenDaten['firstname'] . " " . $this->_kundenDaten['lastname'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand();
        $page->drawText(
            $this->_kundenDaten['street'] . " " . $this->_kundenDaten['housenumber'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );

        // Abstand
        $this->_abstand('half');
        $page->drawText(
            $this->_kundenDaten['zip'] . " " . $this->_kundenDaten['city'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );

        $this->_abstand(true);

        // Überschrift
        if($this->zaehler == 1)
            $rechnung = translate('Buchungsbestätigung Nr.: ');
        else
            $rechnung = translate('Buchungsänderung Nr.: ');

        $page = $this->_setPageSchrift($page, 'fett', 'ueberschrift');
        $page->drawText(
            'Programmbestätigung / Voucher',
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $page = $this->_setPageSchrift($page);
        $page = $this->_setPageSchrift($page);

        // Datum mit Monatsnamen
        $datum = $this->erstellenBuchungsdatum();

        $datum = translate("Berlin, den ").$datum;
        $page->drawText($datum, $this->_spalte6, $this->_hochWert, 'UTF-8');
        $this->_abstand('half');

        // Nummer
        $assd = $_SESSION['kundeninfo']['assd_nummer'];
        if (!empty($assd)) {
            $assdnummer = translate('Buchungsnummer');
            $page->drawText($assdnummer . ": " . $assd, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            $this->_abstand('half');
        }
        // Gruppenname
        $gruppe = $_SESSION['kundeninfo']['gruppenname'];
        if (!empty($gruppe)) {
            $gruppenname = translate('Gruppenname');
            $page->drawText($gruppenname . ": " . $gruppe, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            $this->_abstand('half');
        }

        $this->_abstand(true);

        return $this;
    }

    /**
     * Listet die gebuchten Programme auf
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenProgrammblock($proID)
    {
        $anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
        $gebuchteProgramme = $this->_gebuchteProgramme;
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $allgemeineInformation = "vielen Dank für Ihre Programmbuchung, die wir Ihnen hiermit bestätigen.";
        $allgemeineInformation = translate($allgemeineInformation);
        $page->drawText($allgemeineInformation, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand(true);
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        // Programmpunkt
        $programmpunkt = translate("Ihre gebuchten Programmpunkte");
        $page = $this->_setPageSchrift($page, 'fett', 'ueberschrift');
        $page->drawText($programmpunkt, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $page = $this->_setPageSchrift($page, 'normal');

        $this->_abstand(true);
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        /** @var Front_Model_Vertragspartner $frontModelVertragspartner */
        $frontModelVertragspartner = $this->getFrontModelVertragspartner();
        $frontModelVertragspartner->setBereich($this->condition_bereich_programme);

        // Schleife Programme
        for ($i = 0; $i < count($gebuchteProgramme); $i++) {

            $programm = $gebuchteProgramme[$i];

            if ($programm['programmdetails_id'] == $proID){
                // verhindert die Übernahme der Buchungspauschale
                $kontrolleBuchungspauschale = $this->kontrolleBuchungspauschale($programm['programmdetails_id']);
                if (!empty($kontrolleBuchungspauschale)) {
                    continue;
                }

                // Datum
                if ((!empty($programm['datum'])) and ($programm['datum'] != '0000-00-00')) {
                    $toolWochentagName = new nook_ToolWochentageNamen();
                    $nameWochentag = $toolWochentagName
                        ->setAnzeigespracheId($anzeigeSprache)
                        ->setAnzeigeNamensTyp(2) // Langform Wochentag
                        ->setDatum($programm['datum'])
                        ->steuerungErmittelnWochentag()
                        ->getBezeichnungWochentag();

                    $datum = $nameWochentag . " " . $this->erstelleDatumMitMonatsnameKurzform($programm);
                }

                // Programmname
                if($anzeigeSprache == 1)
                    $programmName = $programm['programmbeschreibung']['progname_de'];
                else
                    $programmName = $programm['programmbeschreibung']['progname_en'];

//                // Buchungstyp
//                $toolBuchungstyp = new nook_ToolBuchungstyp();
//
//                $buchungstyp = $toolBuchungstyp
//                    ->isValidProgrammId($programm['programmdetails_id'])
//                    ->setProgrammId($programm['programmdetails_id'])
//                    ->ermittleBuchungstypProgramm();

                // Programmvariante in Abhängigkeit der Anzeigesprache
                if($anzeigeSprache == 1)
                    $programmVariante = $programm['programmvariante']['preisvariante_de'];
                else
                    $programmVariante = $programm['programmvariante']['preisvariante_en'];

                // Programmsprache
                $programmsprache = '';
                if ($programm['sprache'] > 0) {
                    $programmsprache = $this->ermittelnVorhandenseinProgrammsprachenEinesProgrammes(
                        $programm['programmdetails_id'],
                        $programm['sprache']
                    );
                    $programmsprache = lcfirst($programmsprache);
                }

                // Datum
                if ((!empty($programm['datum'])) and ($programm['datum'] != '0000-00-00')) {
                    $page->drawText($datum, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                    $this->_abstand();
                    $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                    if ($page == NULL){
                        $page = $_SESSION['pageinfo']->pages[0];
                    }
                }

                // Zeit
                $zeit = trim($programm['zeit']);
                if (!empty($zeit)) {

                    // Formatierung Zeit
                    $zeit = nook_ToolZeiten::kappenZeit($programm['zeit'], 2);
                    if ($zeit != '00:00') {
                        $programm['zeit'] = $zeit . " " . translate('Uhr');

                        $page->drawText($programm['zeit'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                        $this->_abstand('half');
                        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                        if ($page == NULL){
                            $page = $_SESSION['pageinfo']->pages[0];
                        }
                    }

                }

                // Icon Offlinebuchung ausgerichtet an Spalte 1
                if ($programm['offlinebuchung'] == 1) {
//                    $page->drawImage(
//                        $this->iconOfflinebuchung,
//                        $this->_rechtsWert,
//                        $this->_hochWert,
//                        $this->_rechtsWert + 8,
//                        $this->_hochWert + 8
//                    );
                }

                // Programmname
                $page = $this->_setPageSchrift($page, 'fett');

                if ($programm['offlinebuchung'] == 1) {
                    $page->drawText($programmName, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                } else {
                    $page->drawText($programmName, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                }

                $page = $this->_setPageSchrift($page, 'normal');
                $this->_abstand();
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                // Programmsprache
                if ($programmsprache) {
                    $labelProgrammsprache = translate('gewählte Programmsprache');
                    $page->drawText(
                        $labelProgrammsprache . ": " . $programmsprache,
                        $this->_rechtsWert,
                        $this->_hochWert,
                        'UTF-8'
                    );
                    $this->_abstand();
                    $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                    if ($page == NULL){
                        $page = $_SESSION['pageinfo']->pages[0];
                    }
                }

                // Leistung
                $leistung = $programm['anzahl'] . " x " . $programmVariante;
                $page->drawText($leistung, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

                $this->_abstand();
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                // 'tbl_programmbeschreibung' -> confirm1
                if (!empty($programm['programmbeschreibung']['confirm_1'])) {

                    $confirmArray = nook_ToolText::splitText($programm['programmbeschreibung']['confirm_1'], 90);

                    for ($j = 0; $j < count($confirmArray); $j++) {
                        $page->drawText($confirmArray[$j], $this->_rechtsWert, $this->_hochWert, 'UTF-8');

                        $this->_abstand();
                        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                        if ($page == NULL){
                            $page = $_SESSION['pageinfo']->pages[0];
                        }
                    }
                }

                // 'tbl_programme_beschreibung' -> confirm1
                if($anzeigeSprache == 1)
                    $confirm = $programm['programmbeschreibung']['confirm_de'];
                else
                    $confirm = $programm['programmbeschreibung']['confirm_en'];

                if (!empty($confirm)){

                    $confirmArray = nook_ToolText::splitText($confirm, 90);

                    for ($k = 0; $k < count($confirmArray); $k++) {
                        $page->drawText($confirmArray[$k], $this->_rechtsWert, $this->_hochWert, 'UTF-8');

                        $this->_abstand();
                        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                        if ($page == NULL){
                            $page = $_SESSION['pageinfo']->pages[0];
                        }
                    }
                }

                $this->_abstand('half');
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                // Vertragspartner
                $adresseVertragspartner = $frontModelVertragspartner
                    ->setProgrammId($programm['programmdetails_id'])
                    ->steuerungErmittlungAdresseVertragspartner()
                    ->getAdresse();

                $vertragspartner = translate('Vertragspartner für diese Leistung ist');
                $page->drawText($vertragspartner.": ", $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                $page->drawText($adresseVertragspartner['company'].", ".$adresseVertragspartner['street'].", ".$adresseVertragspartner['zip']." ".$adresseVertragspartner['city'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                $this->_abstand();
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                $page->drawText($adresseVertragspartner['country'], $this->_rechtsWert, $this->_hochWert, 'UTF-8');
                $this->_abstand('half');
                $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                if ($page == NULL){
                    $page = $_SESSION['pageinfo']->pages[0];
                }

                // abweichende Stornobedingungen
                $ToolErmittlungAbweichendeStornofristenKosten = new nook_ToolErmittlungAbweichendeStornofristenKosten();
                $abweichendeStornofristen = $ToolErmittlungAbweichendeStornofristenKosten
                    ->setProgrammId($programm['programmdetails_id'])
                    ->ermittleStornofristenProgramm()
                    ->getStornofristen();

                if (is_array($abweichendeStornofristen)) {

                    // abweichende Stornokosten
                    $page = $this->ermittlungAbweichendeStornokosten($page, $abweichendeStornofristen);
                    $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

                    if ($page == NULL){
                        $page = $_SESSION['pageinfo']->pages[0];
                    }
                }
            }
        } // Ende Programme

        // Offlinebuchung
        $this->_blockBuchungstyp($gebuchteProgramme);

        $this->_abstand('half');
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        // Storno Information des Programmes
        //$stornoInformation1 = "Soweit nicht anders angegeben ist die Stornierung der gebuchten Programme bis zum 3. Tag vor der jeweiligen Programmdurchführung";
       //$stornoInformation1 = translate($stornoInformation1);
       // $page->drawText($stornoInformation1, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        //$stornoInformation2 = "kostenfrei. Danach werden 100% Stornokosten berechnet.";
       //$stornoInformation2 = translate($stornoInformation2);
       // $page->drawText($stornoInformation2, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        return $this;
    }

    /**
     * Kontrollier ob eine Buchungspauschale vorliegt
     *
     * + wenn Buchungspauschale, dann return 'true'
     * + wenn keine buchungspauschale, dann return 'false'
     *
     * @param $programmId
     * @return bool
     */
    private function kontrolleBuchungspauschale($programmId)
    {
        $flagBuchungspauschale = false;

        $static = Zend_Registry::get('static');
        $idBuchungspauschale = $static->buchungspauschale->programmId;

        if ($programmId == $idBuchungspauschale) {
            $flagBuchungspauschale = true;
        }

        return $flagBuchungspauschale;
    }

    /**
     * Ermittelt die Anzahl der vorhandenen Programmsprachen eines Programmes
     *
     * + sind Programmsprachen vorhanden, dann return 'true'
     * + sind keine Programmsprachen vorhanden, dann return 'false'
     *
     * @param $programmId
     * @param $preisvarianteId
     * @return bool
     */
    private function ermittelnVorhandenseinProgrammsprachenEinesProgrammes($programmId, $spracheId)
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammdetailsProgsprachen'] = function () {
            return new Application_Model_DbTable_programmedetailsProgsprachen();
        };

        $pimple['tabelleProgSprache'] = function () {
            return new Application_Model_DbTable_progSprache();
        };

        $frontModelProgrammSprache = new Front_Model_ProgrammSprache($pimple);
        $frontModelProgrammSprache
            ->setBuchungsnummerId($this->buchungsNummerId)
            ->setProgrammId($programmId);

        // Anzahl der Programmsprachen eines
        $anzahlVorhandeneSprachvariantenEinesProgrammes = $frontModelProgrammSprache
            ->steuernErmittelnProgrammsprachen()
            ->getAnzahlProgrammsprachen();

        if ($anzahlVorhandeneSprachvariantenEinesProgrammes == 0) {
            return false;
        }

        // Programmsprache
        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $programmSprache = $frontModelProgrammSprache
            ->setAnzeigeSprache($anzeigeSpracheId)
            ->setGewaehlteProgrammsprache($spracheId)
            ->steuerungErmittelnNameGebuchteProgrammsprache()
            ->getNameGewaehlteProgrammsprache();

        return $programmSprache;
    }

    /**
     * Fügt den Textblock 'Offlinebuchung' an
     *
     * + Block wird nur angefügt, wenn Offlinebuchungen vorhanden sind
     * + ermitteln Anzahl Offlinebuchungen
     *
     * @param array $gebuchteProgramme
     * @return int
     */
    private function _blockBuchungstyp(array $gebuchteProgramme)
    {
        $anzahlOfflinebuchungen = 0;

        for($i=0; $i < count($gebuchteProgramme); $i++){
            if($gebuchteProgramme[$i]['offlinebuchung'] == 1)
                $anzahlOfflinebuchungen++;
        }

        if($anzahlOfflinebuchungen == 0)
            return $anzahlOfflinebuchungen;

        $this->_abstand('half');
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        $toolPdf = new nook_ToolPdf();
        $toolPdf->setFont($this->_font)->setFontSize($this->_fontTexte['text']['groesse']);
        $toolPdf->setHPadding(0);
        $page = $this->_setPageSchrift($page);

        // Ausrichtung an Spalte 1, Text mit Icon
        $rechtswert = $this->_spalte1;

       // $textBuchungstyp = "Die mit ";
      //  $textBuchungstyp = translate($textBuchungstyp);
      //  $page->drawText($textBuchungstyp, $rechtswert, $this->_hochWert, 'UTF-8');
//        $rechtswert += 30;
//
//        $page->drawImage(
//            $this->iconOfflinebuchung,
//            $rechtswert,
//            $this->_hochWert,
//            $rechtswert + 8,
//            $this->_hochWert + 8
//        );
       // $textBuchungstyp = " gekennzeichneten Programme sind noch in der Testphase. Die Buchung wird erst rechtsverbindlich, wenn wir Ihnen";
       // $textBuchungstyp = translate($textBuchungstyp);
        $rechtswert += 10;

       $page->drawText($textBuchungstyp, $rechtswert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        //$textBuchungstyp = "innerhalb von max.3 Werktagen eine separate Bestätigung per E-mail schicken.";
       // $textBuchungstyp = translate($textBuchungstyp);
       // $page->drawText($textBuchungstyp, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        return $anzahlOfflinebuchungen;
    }

    /**
     * Wenn abweichende Stornofristen in 'tbl_programmdetails_stornokosten' dann werte diese aus
     *
     * @param $page
     * @param $abweichendeStornofristen
     * @return mixed
     */
    protected function ermittlungAbweichendeStornokosten($page, $abweichendeStornofristen)
    {
        $stornoUeberschrift = translate('Stornierungsbedingungen:');
        $page->drawText($stornoUeberschrift, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand();
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        // normales Storno
        $einzelneStornofrist = translate('Stornofrist');
        $einzelneStornofristBis = translate('bis');
        $einzelneStornofristTage = translate('Tag(e)');
        $einzelneStornofristStornierungskosten = translate('% Stornierungskosten');

        // kein Storno möglich
        $keinStorno = translate('Keine Stornierung/Umbuchung möglich. 100 % Stornokosten.');

        for ($l = 0; $l < count($abweichendeStornofristen); $l++) {
            $einzelneStornofristTag = $l + 1;

            if ($abweichendeStornofristen[$l]['tage'] == 999) {
                $page->drawText($keinStorno, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            } else {
                $page->drawText(
                    $einzelneStornofristTag . ". " . $einzelneStornofrist . ": " . $einzelneStornofristBis . " " . $abweichendeStornofristen[$l]['tage'] . " " . $einzelneStornofristTage . " " . $abweichendeStornofristen[$l]['prozente'] . " " . $einzelneStornofristStornierungskosten,
                    $this->_rechtsWert,
                    $this->_hochWert,
                    'UTF-8'
                );
            }

            $this->_abstand();
            $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

            if ($page == NULL){
                $page = $_SESSION['pageinfo']->pages[0];
            }
        }

        //$this->_abstand();

        return $page;
    }

    /**
     * Bestimmt den Namen der Stadt
     *
     * @param $__programmId
     * @return array
     */
    private function _bestimmeStadt($__programmId)
    {

        $stadtName = nook_ToolStadt::getStadtNameVonProgramm($__programmId);

        return $stadtName;
    }

    /**
     * Erstellt die Anrede
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenAnrede()
    {

        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        if ($this->_kundenDaten['title'] == 'Herr') {
            $anrede = translate("Sehr geehrter Herr");
        } else {
            $anrede = translate("Sehr geehrte Frau");
        }

        $anrede .= " " . $this->_kundenDaten['lastname'] . ",";

        $page->drawText($anrede, $this->_rechtsWert, $this->_hochWert, 'UTF-8');

        $this->_abstand('half');
        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];

        if ($page == NULL){
            $page = $_SESSION['pageinfo']->pages[0];
        }

        return $this;
    }

    /**
     * Erstellt den Briefkopf.
     * Anlegen der ersten Seite
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenBriefkopf()
    {

        $page = $this->_newPdfBestaetigung->pages[$this->_seitennummer];
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->_font = $font;
        $page->setFont($font, $this->_fontTexte['text']['groesse']);

        $page->drawText(
            $this->_kundenDaten['title'] . " " . $this->_kundenDaten['firstname'] . " " . $this->_kundenDaten['lastname'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand();
        $page->drawText(
            $this->_kundenDaten['street'] . " " . $this->_kundenDaten['housenumber'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand();
        $page->drawText(
            $this->_kundenDaten['zip'] . " " . $this->_kundenDaten['city'],
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand(true);

        $datum = date("d.m.Y", time());
        $datum = "Berlin, den " . $datum;
        $page->drawText($datum, $this->_spalte6, $this->_hochWert, 'UTF-8');
        $this->_abstand(true);

        $rechnung = translate('Buchungsbestätigung');
        $page->drawText(
            $rechnung . " P-" . $this->_buchungsNummerId . "-1",
            $this->_rechtsWert,
            $this->_hochWert,
            'UTF-8'
        );
        $this->_abstand(true);

        return $this;
    }

    /**
     * Süpeichert das Pdf der programmbuchung
     *
     * @return string
     */
    private function _savePdf($docname)
    {

        $registrierungsnummer = $this->ermittelnRegistrierungsnummer();

        /** @var $newPdf Zend_pdf */
        $newPdf = $this->_newPdfBestaetigung;

        $nameNeuePdfDatei = $this->_pfad . "/B_" . $registrierungsnummer . "_" . $this->zaehler . "_".$docname.".pdf";
        $dateiName = $newPdf->save($nameNeuePdfDatei);

        return $nameNeuePdfDatei;
    }

    /**
     * Ermitteln der Registrierungsnummer
     *
     * @return int
     */
    private function ermittelnRegistrierungsnummer()
    {
        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsnummer->steuerungErmittelnRegistrierungsnummerMitSession(
        )->getRegistrierungsnummer();

        return $registrierungsnummer;
    }

    /**
     * festlegen der Schriften
     *
     * @param bool $__fontTexte
     * @return Front_Model_BestaetigungPdfProgramme
     * @throws nook_Exception
     */
    public function setSchriften($__fontTexte = false)
    {

        if (!is_array($__fontTexte)) {
            throw new nook_Exception($this->_error_kein_array);
        }

        $this->_fontTexte = $__fontTexte;

        return $this;
    }

    /**
     * Erstellt die erste Seite
     * des Pdf der Programmbuchungen
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function _erstellenGrunddokument()
    {

        $assd = $this->_kundenDaten['assd_nummer'];
        $assd = explode("-", $assd);

        if ($assd[0] == "AT"){
            if ($assd[1] == "W1"){
                $assd[0] = "AT-W1";
            }

            if ($assd[1] == "W2"){
                $assd[0] = "AT-W2";
            }

            if ($assd[1] == "G1"){
                $assd[0] = "AT-G1";
            }
        }

        try {
            $this->_newPdfBestaetigung = Zend_Pdf::load($this->_pfad . "/vorlagen/".$assd[0].".pdf");
            $_SESSION['pageinfo'] = Zend_Pdf::load($this->_pfad . "/vorlagen/".$assd[0].".pdf");
        }catch (Exception $e) {
            $this->_newPdfBestaetigung = Zend_Pdf::load($this->_pfad . "/vorlagen/default.pdf");
            $_SESSION['pageinfo'] = Zend_Pdf::load($this->_pfad . "/vorlagen/".$assd[0].".pdf");
        }

        return $this;
    }

    /**
     * Erstellt die Icon des Pdf
     *
     * + Icon Offlinebuchung
     *
     * @return Front_Model_BestaetigungPdfProgramme
     */
    private function erstellenIcon()
    {
        $this->iconOfflinebuchung = Zend_Pdf_Image::imageWithPath($this->iconPfad . "/t.png");

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
    private function _abstand($abstand = false)
    {

        if ($abstand === false) {
            $this->_hochWert -= $this->_zeilenAbstandKlein;
        } elseif ($abstand === 'half') {
            $this->_hochWert -= $this->_zeilenAbstandGross / 2;
        } elseif ($abstand === true) {
            $this->_hochWert -= $this->_zeilenAbstandGross;
        }

        // erstellt neue Seite
        if ($this->_hochWert <= $this->_untererRand) {

            $this->_hochWert = $this->_obererRand;

            // eine neue Seite zum Dokument, Schriftart und Schriftgröße als Standardwerte
            $page = $this->_newPdfBestaetigung->newPage(Zend_Pdf_Page::SIZE_A4);
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $this->_font = $font;
            $page->setFont($font, $this->_fontTexte['text']['groesse']);

            // einfügen Logo auf der neuen Seite
            $image = Zend_Pdf_Image::imageWithPath($this->_pfad . "/vorlagen/aohostel-logo.png");
            $page->drawImage($image, 0, 0, 0, 0);

            // aktueller Seitenzaehler
            $this->_seitennummer++;
            $this->_newPdfBestaetigung->pages[$this->_seitennummer] = $page;
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
    private function _setPageSchrift($__page, $__schriftTyp = 'normal', $__schriftGroesse = 'text')
    {

        // Ausprägung Schrift
        if ($__schriftTyp === 'normal') {
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        } elseif ($__schriftTyp === 'fett') {
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        } else {
            throw new nook_Exception($this->_error_unbekannte_schrift_auspraegung);
        }

        // Schriftgroesse / Schrifttyp
        if ($__schriftGroesse === 'minitext') {
            $schriftGroesse = $this->_fontTexte['minitext']['groesse'];
        } elseif ($__schriftGroesse === 'text') {
            $schriftGroesse = $this->_fontTexte['text']['groesse'];
        } elseif ($__schriftGroesse === 'ueberschrift') {
            $schriftGroesse = $this->_fontTexte['ueberschrift']['groesse'];
        } else {
            throw new nook_Exception($this->_error_unbekannter_schrift_typ);
        }

        $this->_font = $font;
        $__page->setFont($font, $schriftGroesse);

        return $__page;
    }

    /**
     * Erstellt das Datum der Buchung
     *
     * + Angabe des vollständigen Monatsnamen
     *
     * @return string
     */
    private function erstellenBuchungsdatum()
    {
        $tag = date("d");
        $monatsZiffer = date("n");
        $toolMonatsname = new nook_ToolMonatsnamen();
        $monatsName = $toolMonatsname->setMonatsZiffer($monatsZiffer)->getMonatsnamen();
        $jahr = date("Y");
        $datum = $tag . ". " . $monatsName . " " . $jahr;

        return $datum;
    }

    /**
     * Gibt das Datum mit dem Monatsnamen als Kurzform zurück
     *
     * + Monatsname in deutsch / englisch
     *
     * @param $programm
     * @return string
     */
    private function erstelleDatumMitMonatsnameKurzform($programm)
    {
        $teileDatum = explode('-', $programm['datum']);
        $toolMonatsname = new nook_ToolMonatsnamen();
        $monatsZiffer = (int) $teileDatum[1];
        $monatsname = $toolMonatsname->setMonatsZiffer($monatsZiffer)->getMonatsnameShort();
        $datumMitMonatsname = $teileDatum[2] . ". " . $monatsname . " " . $teileDatum[0];

        return $datumMitMonatsname;
    }

}
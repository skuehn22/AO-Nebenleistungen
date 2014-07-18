<?php
/**
* Bearbeitet die Bestellung des Kunden und versendet Mails mit Pdf Anhang
*
* @author Stephan.Krauss
* @date 20.09.2013
* @file BestellungController.php
* @package front
* @subpackage controller
*/
class Front_BestellungController extends Zend_Controller_Action implements nook_ToolCrudController
{
    private $realParams = null;
    private $pimple = null;
    private $requestUrl = null;

    // Konditionen
    private $condition_status_bestellung = 1;
    protected $condition_status_hotelbuchung_bestellung = 3;
    private $condition_neue_buchung = 1;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();

        $this->pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

        // Rechte der Rollen an den Aktionen

        // Kontrolle der Hauptvariablen

    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            /** @var $statics Zend_Config_Ini */
            $statics = Zend_Registry::get('static');

            // Schaltung der Mails Online / Offline
            $schaltungMails = $statics->standardmails->toArray();

            // Übersicht der Baustein Navigation
            $navigation = $this->_breadcrumb($params);
            $raintpl->assign('breadcrumb', $navigation);

            $this->buildPimple();

            // verändern Zähler Buchungsnummer
            $this->veraendernBuchungsnummerUndZaehler();

            // ermitteln aktuelle Buchungsnummer und Zaehler
            $aktuelleBuchung = $this->ermittelnBuchungsnummerUndZaehler();

            // ermitteln Kunden und Buchungsdaten
            $model_kundendatenUndBuchungsdaten = $this->_ermittelnDaten($aktuelleBuchung);

            // ermitteln Notschalter des System
            $notschalter = $this->ermittelnNotschalterSystem();
            $raintpl->assign('notschalter', $notschalter);



            // verändert die Anzahl der verfügbaren Zimmer, Status 'tbl_xml_buchung'
            if (($model_kundendatenUndBuchungsdaten->getCountDatenHotelbuchungen() > 0) and empty($notschalter['hotelbuchung'])){
                $this->veraendenDerAnzahlVerfuegbareZimmer($model_kundendatenUndBuchungsdaten);
                $this->veraendernStatusTblXmlBuchung($aktuelleBuchung);
            }

            // verändern der Anzahl der verfügbaren Programme
            if (($model_kundendatenUndBuchungsdaten->getCountDatenProgrammbuchungen() > 0) and empty($notschalter['programmbuchung']))
                $this->veraendenDerAnzahlVerfuegbareProgramme($model_kundendatenUndBuchungsdaten);

            // eintragen in die Tabelle Zahlungen
            $this->eintragenBuchungenInZahlungstabelle($model_kundendatenUndBuchungsdaten,$aktuelleBuchung,$notschalter);

            // eintragen in Tabelle Rechnungen
            $this->eintragenBuchungenInRechnungstabelle($aktuelleBuchung['buchungsnummer'], $aktuelleBuchung['zaehler']);


            // setzen Status in den Buchungstabellen
            $this->setzeStatusDerBuchung();

            /** Grundwerte Pdf **/
            $fontTexte = $this->_textDefinitionPdf();

            /** Bezeichnung der möglichen Pdf's mit kompletter Registrierungsnummer **/
            $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
            $registrierungsnummer = $toolRegistrierungsnummer
                ->setBuchungsnummer($aktuelleBuchung['buchungsnummer'])
                ->steuerungErmittlungRegistrierungsnummer()
                ->getRegistrierungsnummer();

            $zaehler = $toolRegistrierungsnummer->getZaehler();

            // Name der angehängten Pdf an die Mails
            $namePdfProgrammRechnung = '';
            $namePdfProgrammBestaetigung = '';
            $namePdfUebernachtungRechnung = '';

            // Pimple Container
            $pimple = $this->getInvokeArg('bootstrap')->getResource('Container');
            $pimple['toolPdf'] = function ($c) {
                return new nook_ToolPdf();
            };

            // ermitteln Buchungsnummer
            $pimple['buchungsnummerId'] = $aktuelleBuchung['buchungsnummer'];

            /** Pdf der Programme und Mail an Programmanbieter **/
            if (empty($notschalter['programmbuchung'])){

                $anzahlProgrammbuchungen = $model_kundendatenUndBuchungsdaten->getCountDatenProgrammbuchungen();

                if($anzahlProgrammbuchungen > 0){
                    $namePdfProgrammRechnung = 'P_Re' . $registrierungsnummer . "_" . $zaehler . ".pdf";
                    $namePdfProgrammBestaetigung = 'P_' . $registrierungsnummer . "_" . $zaehler . ".pdf";
                }


                $this->_pdfProgrammeUndEmailProgrammanbieter(
                    $model_kundendatenUndBuchungsdaten,
                    $schaltungMails,
                    $namePdfProgrammRechnung,
                    $namePdfProgrammBestaetigung,
                    $aktuelleBuchung,
                    $fontTexte
                );
            }

            /** Pdf Hotelrechnung **/
            if (empty($notschalter['hotelbuchung'])) {

                $anzahlHotelbuchungen = $model_kundendatenUndBuchungsdaten->getCountDatenHotelbuchungen();

                if ($anzahlHotelbuchungen > 0) {
                    $namePdfUebernachtungRechnung = $this->_pdfUebernachtung(
                        $pimple,
                        $model_kundendatenUndBuchungsdaten,
                        $fontTexte
                    );
                }
            }

            // Schnittstelle Meininger
            // Filialen Meininger ist aktiv
            $static = Zend_Registry::get('static');
            if($static->filialen->meininger == 2){
                $this->bookingMeininger($aktuelleBuchung, $model_kundendatenUndBuchungsdaten);
            }


            // vorbereiten versenden E-Mail an Kunde
            $model_emailKunde = $this->anlegenMailAnKunde(
                $aktuelleBuchung,
                $schaltungMails,
                $model_kundendatenUndBuchungsdaten
            );

            $pfad = realpath(dir(__FILE__) . "../pdf/");

            // Pdf Programme - Rechnung
            if (!empty($namePdfProgrammRechnung) and empty($notschalter['programmbuchung']))
            {
                $namePdfProgrammRechnung = $pfad . "/" . $namePdfProgrammRechnung;
                $model_emailKunde->setPdfProgrammRechnung($namePdfProgrammRechnung);
            }

            // Pdf Programme - Bestätigung
            if (!empty($namePdfProgrammBestaetigung) and empty($notschalter['programmbuchung']))
            {
                $model_emailKunde->setPdfProgrammBestaetigung($namePdfProgrammBestaetigung);
            }

            // Pdf Übernachtung Rechnung
            if (!empty($namePdfUebernachtungRechnung)  and empty($notschalter['hotelbuchung']))
            {
                $model_emailKunde->setPdfUebernachtungRechnung($namePdfUebernachtungRechnung);
            }

            // E-Mail an Kunde
            $model_emailKunde->sendEmailAnKunde($notschalter);

            // löschen vorhandener Vormerkungen
            $this->loeschenVormerkung($aktuelleBuchung['buchungsnummer']);

            // löschen vorhandene aktive Warenkörbe


            // Supportangaben
            $supportDaten = $this->supportAngaben();
            $raintpl->assign('support', $supportDaten);

            // darstellen Status der Buchung
            $raintpl = $this->ermittelnStatusBuchung($raintpl);

            // loeschen Namespace der Session
            $this->loeschenNamespace();
            Zend_Session::regenerateId();

            $this->view->content = $raintpl->draw("Front_Bestellung_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    protected function bookingMeininger(array $aktuelleBuchung,Front_Model_Bestellung $model_kundendatenUndBuchungsdaten)
    {
        $datenHotelbuchung = $model_kundendatenUndBuchungsdaten->getHotelbuchungen();

        // Sind überhaupt Hotelbuchungen vorhanden
        if(count($datenHotelbuchung) == 0)
            return;

        // überprüfen ob überhaupt Hotelbuchungen für 'Meininger' vorhanden sind
        $applicationModelConfigMeininger = new Application_Model_Configs_Meininger();
        // Hoteldaten Meininger
        $datenHotelsMeininger = $applicationModelConfigMeininger->getDataHotelsMeininger();
        // vereinbarte Raten Hotels Meininger
        $vereinbarteRatenMeininger = $applicationModelConfigMeininger->getVereinbarteRaten();

        $frontModelBestellungBookingMeiningerShadow = new Front_Model_BestellungBookingMeiningerShadow();
        $existHotelbuchungMeininger = $frontModelBestellungBookingMeiningerShadow
            ->setAktuelleBuchung($aktuelleBuchung)
            ->setHoteldatenMeininger($datenHotelsMeininger)
            ->setDataHotelbuchung($datenHotelbuchung)
            ->setVereinbarteRatenMeininger($vereinbarteRatenMeininger)
            ->checkHotelbuchungenMeiniger();

        if($existHotelbuchungMeininger === true)
            $frontModelBestellungBookingMeiningerShadow->steuerungVersendenBuchungsinformationAnMeininger();

        return;
    }

    /**
     * Verändert den Status der Buchungen in 'tbl_xml_buchung'
     *
     * @param array $aktuelleBuchung
     */
    protected function veraendernStatusTblXmlBuchung(array $aktuelleBuchung)
    {
        // zurückrechnen des Zaehler
        $zaehlerInXmlBuchungstabelle = $aktuelleBuchung['zaehler'] - 1;

        $updateData = array(
            'status' => $this->condition_status_hotelbuchung_bestellung,
            'zaehler' => $aktuelleBuchung['zaehler']
        );

        $where =  new Zend_Db_Expr("buchungsnummer_id = ".$aktuelleBuchung['buchungsnummer']." and zaehler = ".$zaehlerInXmlBuchungstabelle);

        /** @var $tabelleXmlBuchung Zend_Db_Table */
        $tabelleXmlBuchung = $this->pimple['tabelleXmlBuchung'];
        $kontrolle = $tabelleXmlBuchung->update($updateData, $where);

        return;
    }

    /**
     * eintragen in Tabelle 'tbl_rechnungen'.
     *
     * + Shadow - Bereich
     *
     * @param $aktuelleBuchungsnummer
     * @param $aktuellerZaehler
     */
    protected function eintragenBuchungenInRechnungstabelle($aktuelleBuchungsnummer, $aktuellerZaehler)
    {
        $kundenId = nook_ToolKundendaten::findKundenId();

        $frontModelBestellungRechnungenShadow = new Front_Model_BestellungRechnungenShadow();
        $frontModelBestellungRechnungenShadow
            ->BestellungRechnungenStore()
            ->setAktuelleBuchungsnummer($aktuelleBuchungsnummer)
            ->setAktuelleKundenId($kundenId)
            ->setZaehler($aktuellerZaehler)
            ->eintragenTabelleRechnungen();

        return;
    }

    /**
     * Ermittelt den Status der Buchung
     *
     * + ermitteln Buchungsnummer
     * + ermitteln Zaehler
     * + Status 'Bestellung'
     * + Status Änderung
     * + Status Stornierung
     *
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnStatusBuchung($raintpl)
    {
        // Buchungsnummer und Zaehler
        $modelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer();
        $buchungsnummer = $modelZaehlerBuchungsnummer->findBuchungsnummerUndZaehler()->getBuchungsnummer();
        $zaehler = $modelZaehlerBuchungsnummer->getZaehler();

        // Status 'Bestellung'
        if ($zaehler == $this->condition_neue_buchung) {
            $raintpl->assign('statusBuchung', $this->condition_status_bestellung);
        } elseif ($zaehler > $this->condition_neue_buchung) {
            $toolStatusBestellung = new nook_ToolStatusBestellung();
            $statusWarenkorb = $toolStatusBestellung
                ->setBuchungsnummer($buchungsnummer)
                ->setZaehler($zaehler)
                ->ermittelnStatusWarenkorb()
                ->getStatusWarenkorb();

            $raintpl->assign('statusBuchung', $statusWarenkorb);
        }

        return $raintpl;
    }

    /**
     * Ermittelt den Zustand der Notschalter im System
     *
     * @return array
     */
    private function ermittelnNotschalterSystem()
    {
        $adminModelNotschalter = new Admin_Model_Notschalter();
        $notschalter = $adminModelNotschalter
            ->steuerungErmittlungNotschalter()
            ->getNotschalter();

        return $notschalter;
    }

    /**
     * Löscht die Session der aktuellen Buchung
     */
    private function loeschenNamespace()
    {
        $toolSession = new nook_ToolSession();
        $toolSession->loescheNamespace('buchung');
        $toolSession->loescheNamespace('translate');
        $toolSession->loescheNamespace('portalbereich');
        $toolSession->loescheNamespace('programmsuche');
        $toolSession->loescheNamespace('warenkorb');
        $toolSession->loescheNamespace('hotelsuche');

        return;
    }

    /**
     * Verändert die Anzahl der Tageskapazität der gebuchten Programme
     *
     * + verändert die Anzahl der Tageskapazität der gebuchten Programme
     * + Programme ohne Datum werden nicht berücksichtigt
     */
    private function veraendenDerAnzahlVerfuegbareProgramme($model_kundendatenUndBuchungsdaten)
    {
        $programmbuchungen = $model_kundendatenUndBuchungsdaten->getProgrammbuchungen();

        $frontModelProgrammdetailKapazitaetManager = new Front_Model_ProgrammdetailKapazitaetManager();

        // Schleife der gebuchten Programme
        foreach ($programmbuchungen as $programm) {

            // Programme ohne Datum werden nicht berücksichtigt
            if (($programm['datum'] == '0000-00-00') or empty($programm['datum'])) {
                continue;
            }

            // Notiz: Die Anzahl der Programme wird nur reduziert. Eine Stornierung wird nicht berücksichtigt.
            $frontModelProgrammdetailKapazitaetManager
                ->setProgrammId($programm['programmdetails_id'])
                ->setVeraenderungProgrammKapazitaet($programm['anzahl'])
                ->setDatum($programm['datum'])
                ->aendernTageskapatitaetEinesProgrammes();
        }

        return;
    }

    /**
     * Ermittelt die komplette Registrierungsnummer und die Telefonnummer des Telefonsupport
     *
     * + komplette Registrierungsnummer
     * + Telefonnummer des Telefonsupport
     *
     * @return array
     */
    private function supportAngaben()
    {
        $supportDaten = array();

        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $flagHasRegistrierungsnummer = $toolRegistrierungsnummer->steuerungErmittelnRegistrierungsnummerMitSession(
        )->getFlagHasRegistrierungsnummer();

        if ($flagHasRegistrierungsnummer) {
            $supportDaten['kompletteBuchungsnummer'] = $toolRegistrierungsnummer->getKompletteRegistrierungsnummer();
        }

        $toolRegistryDaten = new nook_ToolRegistryDaten('support');
        $configData = $toolRegistryDaten->steuerungErmittelnDaten()->getKonfigDaten();
        $supportDaten['telefon'] = $configData['telefon'];

        return $supportDaten;
    }

    /**
     * Erstellt den DIC
     */
    private function buildPimple()
    {
        $this->pimple['tabelleBuchungsnummer'] = function ($c) {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $this->pimple['tabelleProgrammbuchung'] = function ($c) {
            return new Application_Model_DbTable_programmbuchung();
        };

        $this->pimple['tabelleHotelbuchung'] = function ($c) {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $this->pimple['tabelleProduktbuchung'] = function ($c) {
            return new Application_Model_DbTable_produktbuchung();
        };

        $this->pimple['tabelleXmlBuchung'] = function ($c) {
            return new Application_Model_DbTable_xmlBuchung();
        };

        return;
    }

    /**
     * Verändert / setzt den Zähler der Buchungsnummer
     *
     * + setzt die Buchungsnummer und Zähler in Zend_Session_Namespace 'buchung'
     *
     */
    private function veraendernBuchungsnummerUndZaehler()
    {
        /** @var  $pimple Pimple_Pimple */
        $pimple = $this->pimple;

        $modelSetzenZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer($pimple);
        $modelSetzenZaehlerBuchungsnummer
            ->findBuchungsnummerUndZaehler()
            ->erhoehenZaehler() // verändert den Zähler
            ->veraendernZaehlerInTabellen();

        // speichern Buchungsnummer und Zähler
        $buchungsnummer = $modelSetzenZaehlerBuchungsnummer->getBuchungsnummer();
        $zaehler = $modelSetzenZaehlerBuchungsnummer->getZaehler();

        // setzen Werte in Namespace 'buchung'
        $this->setzenNamespaceBuchung($buchungsnummer, $zaehler);

        return $buchungsnummer;
    }

    /**
     * Übergibt die Buchungsnummer und den Zähler in den Session Namespace 'buchung'
     * + buchungsnummer der aktuellen Buchung
     * + zaehler der aktuellen Buchung unter der gespeichert wird
     *
     * @param $buchungsnummer
     * @param $zaehler
     */
    private function setzenNamespaceBuchung($buchungsnummer, $zaehler)
    {
        $sessionNamespaceBuchung = new Zend_Session_Namespace('buchung');
        $sessionNamespaceBuchung->buchungsnummer = $buchungsnummer;
        $sessionNamespaceBuchung->zaehler = $zaehler;

        return;
    }

    /**
     * Verändert die Anzahl der verfügbaren Zimmer
     * + mappen der gebuchten Hoteldaten
     * + Reduzierung der verfügbaren Zimmer
     *
     * @param $modelHotelBuchung
     * @return int
     */
    private function veraendenDerAnzahlVerfuegbareZimmer($modelHotelBuchung)
    {
        $datenHotelbuchung = $modelHotelBuchung->getHotelbuchungen();

        if (count($datenHotelbuchung) == 0) {
            return;
        }

        // mappen der Hotelbuchungen
        $modelVeraendernAnzahlverfuegbareZimmerMapper = new Front_Model_BestellungVeraendernAnzahlVerfuegbareZimmerMapper($datenHotelbuchung);
        $datenHotelbuchung = $modelVeraendernAnzahlverfuegbareZimmerMapper->start()->getHotelBuchungen();

        // verändern der Zimmeranzahl
        $modelVeraendernAnzahlverfuegbareZimmer = new Front_Model_VeraendernAnzahlVerfuegbareZimmer();
        $modelVeraendernAnzahlverfuegbareZimmer
            ->setDatenHotelbuchung($datenHotelbuchung)
            ->startZimmerreduktion();

        return;
    }

    /**
     * Ermitteln der Kunden und Buchungsdaten
     * + setzen aktuelle Buchungsnummer
     * + setzen aktueller Zaehler
     *
     * @param $aktuelleBuchung
     * @return Front_Model_Bestellung
     */
    private function _ermittelnDaten($aktuelleBuchung)
    {
        /** @var  $model_kundendatenUndBuchungsdaten Front_Model_Bestellung */
        $model_kundendatenUndBuchungsdaten = new Front_Model_Bestellung(); // Kundendaten und Buchungsdatensätze
        $model_kundendatenUndBuchungsdaten->setAktuelleBuchungsnummer($aktuelleBuchung['buchungsnummer']);
        $model_kundendatenUndBuchungsdaten->setAktuellerZaehler($aktuelleBuchung['zaehler']);

        // ermitteln Kundendaten und Buchungen entsprechend Buchungsnummer
        $model_kundendatenUndBuchungsdaten->ermittelnBuchungen();

        return $model_kundendatenUndBuchungsdaten;
    }

    /**
     * Ermittelt Buchungsnummer und Registrierungsnummer
     *
     * + Buchungsnummer und Zaehler
     * + Registrierungsnummer
     * + komplette Registrierungsnummer
     *
     * @return array
     */
    private function ermittelnBuchungsnummerUndZaehler()
    {
        $aktuelleBuchung = array();

        $toolBestandsbuchungKontrolle = new nook_ToolBestandsbuchungKontrolle();
        $toolBestandsbuchungKontrolle->kontrolleBestandsbuchung();
        $aktuelleBuchung['buchungsnummer'] = $toolBestandsbuchungKontrolle->getBuchungsnummer();
        $aktuelleBuchung['zaehler'] = $toolBestandsbuchungKontrolle->getZaehler();

        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $flagHasRegistrierungsnummer = $toolRegistrierungsnummer
            ->setBuchungsnummer($aktuelleBuchung['buchungsnummer'])
            ->steuerungErmittlungRegistrierungsnummer()
            ->getFlagHasRegistrierungsnummer();

        if (true === $flagHasRegistrierungsnummer) {
            $registrierungsnummer = $toolRegistrierungsnummer->getRegistrierungsnummer();
            $aktuelleBuchung['kompletteRegistrierungsnummer'] = $registrierungsnummer . "-" . $aktuelleBuchung['zaehler'];
        }

        return $aktuelleBuchung;
    }

    /**
     * Erstellen Pdf's der Programme und E-Mails an Programmanbieter
     * Nur wenn Programme vorhanden sind.
     * + versenden EMail an Programmanbieter
     * + erstellen Pdf Rechnung Programme
     * + erstellen Pdf Programmbestätigung
     *
     * @param $model_kundendatenUndBuchungsdaten
     * @param $mails
     * @param $namePdfProgrammRechnung
     * @param $namePdfProgrammBestaetigung
     * @param $aktuelleBuchung
     * @param $fontTexte
     */
    private function _pdfProgrammeUndEmailProgrammanbieter(
        $model_kundendatenUndBuchungsdaten,
        $mails,
        &$namePdfProgrammRechnung,
        &$namePdfProgrammBestaetigung,
        $aktuelleBuchung,
        $fontTexte
    ) {
        // wenn Programme vorhanden sind
        $anzahlProgrammbuchungen = $model_kundendatenUndBuchungsdaten->getCountDatenProgrammbuchungen();
        if (!empty($anzahlProgrammbuchungen)) {

            /** versenden E-Mail an Programmanbieter */
            $model_emailAnbieter = $this->versendenEmailAnProgrammanbieter(
                $model_kundendatenUndBuchungsdaten,
                $mails,
                $aktuelleBuchung
            );

            /** Pdf der Programmrechnung  */
            $namePdfProgrammRechnung = $this->erstellenPdfProgrammrechnung(
                $model_kundendatenUndBuchungsdaten,
                $aktuelleBuchung
            );

            /** Pdf der Programmbestätigung */
            $namePdfProgrammBestaetigung = $this->erstellenPdfProgrammbestaetigung(
                $fontTexte,
                $model_emailAnbieter,
                $aktuelleBuchung
            );

        }
    }

    /**
     * Erstellt das Pdf der Programmbestätigung
     *
     * @param $fontTexte
     * @param $model_emailAnbieter
     * @param $aktuelleBuchung
     * @return mixed
     */
    private function erstellenPdfProgrammbestaetigung(
        $fontTexte,
        $model_emailAnbieter,
        $aktuelleBuchung
    ) {
        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsnummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        $model_pdfProgrammbestaetigungKunde = new Front_Model_BestaetigungPdfProgramme(); // Pdf der Bestätigung Programme an den Kunden

        $namePdfProgrammBestaetigung = $model_pdfProgrammbestaetigungKunde
            ->setRegistrierungsnummer($registrierungsnummer)
            ->setDaten($model_emailAnbieter)
            ->setSchriften($fontTexte)
            ->setZaehler($aktuelleBuchung['zaehler'])
            ->erstellePdf();

        return $namePdfProgrammBestaetigung;
    }

    /**
     * Erstellt das Pdf der Programmrechnung an den Kunden
     *
     * @param $modelKundendatenUndBuchungsdaten
     * @param $aktuelleBuchung
     * @return string
     */
    private function erstellenPdfProgrammrechnung($modelKundendatenUndBuchungsdaten, $aktuelleBuchung)
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsNummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        // Grundwerte
        $shadow = new Front_Model_BestellungErstellenPdfProgrammrechnungShadow();
        $shadow->setBuchungsnummer($aktuelleBuchung['buchungsnummer']);
        $shadow->setZaehler($aktuelleBuchung['zaehler']);
        $shadow->erstellenSpaltenTabelle();

        // zuordnen der Datenbank Tabellen
        $pimple = new Pimple_Pimple();
        $pimple = $shadow->erstellenPimple($pimple);

        // Pfad
        $pfad = realpath(dir(__FILE__) . "../pdf/");

        // Standard Text Font
        $fontName = 'Helvetica';

        // Model zur Pdf Generierung
        $modelPdf = new Front_Model_BuchungProgrammeKundePdf();
        $shadow->setPdf($modelPdf, $aktuelleBuchung, $pfad, $fontName);

        // Model Standardtexte
        $modelStandardTexte = new Front_Model_BuchungProgrammeStandardtextePdf($pimple);
        $modelStandardTexte->setRegistrierungsnummer($registrierungsnummer);
        $modelStandardTexte->setProgrammDaten($modelKundendatenUndBuchungsdaten);
        $shadow->setModelStandardTexte($modelStandardTexte);

        // Model Datengenerierung für 'Rechnung Programme'
        $modelProgrammbuchung = new Front_Model_BuchungProgrammePdf($pimple);
        $modelProgrammbuchung->setProgrammDaten($modelKundendatenUndBuchungsdaten);

        $shadow->setModelProgrammbuchung($modelProgrammbuchung);

        // Generierung Pdf
        $namePdf = $shadow->generierenPdfProgramme($aktuelleBuchung, $pfad, $fontName);

        return $namePdf;
    }

    /**
     * Versendet E Mail an den Programmanbieter
     *
     * + Mail an Programmanbieter
     * + Übernahme Grunddaten
     * + ermittelt Daten der Programme
     *
     * @param $model_kundendatenUndBuchungsdaten
     * @param $mails
     * @param $aktuelleBuchung
     * @return Front_Model_BestellungEmailAnbieter
     */
    private function versendenEmailAnProgrammanbieter($model_kundendatenUndBuchungsdaten, $mails, $aktuelleBuchung)
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsNummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

        // Mail an Programmanbieter
        $model_emailAnbieter = new Front_Model_BestellungEmailAnbieter($mails);
        $model_emailAnbieter
            ->setModelDataKundenUndBuchungsdaten($model_kundendatenUndBuchungsdaten) // Übernahme Grunddaten
            ->setZaehler($aktuelleBuchung['zaehler'])
            ->setRegistrierungsNummer($registrierungsnummer)
            ->ermittelnDaten() // ermittelt Daten der Programme
            ->loeschenBuchungspauschaleAusBestelliste() // loescht die Buchungspauschale
            ->sendenMails();

        return $model_emailAnbieter; // versendet Mails an die Programmanbieter
    }

    /**
     * @param $pimple
     * @param $model_kundendatenUndBuchungsdaten
     * @param $fontTexte
     * @return string
     */
    private function _pdfUebernachtung($pimple, $model_kundendatenUndBuchungsdaten, $fontTexte)
    {
        // Registrierungsnummer
        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsNummer = $toolRegistrierungsnummer->steuerungErmittelnRegistrierungsnummerMitSession()->getRegistrierungsnummer();

        // Werkzeug Hotel
        $pimple['toolHotel'] = function ($c) {
            return new nook_ToolHotel();
        };

        // Tabelle Hotelbuchung
        $pimple['tabelleHotelbuchung'] = function ($c) {
            return new Application_Model_DbTable_hotelbuchung();
        };

        // Tabelle Hotel Stornofristen
        $pimple['tabellePropertiesStornofristen'] = function ($c) {
            return new Application_Model_DbTable_properties(array( 'db' => 'hotels' ));
        };

        // Tabelle Bettensteuer
        $pimple['tabelleAoCityBettensteuer'] = function(){
            return new Application_Model_DbTable_aoCityBettensteuer();
        };

        // Werkzeug Rate
        $pimple['toolRate'] = function ($c) {
            return new nook_ToolRate();
        };

        // Werkzeuge Produkt
        $pimple['toolZusatzprodukte'] = function ($c) {
            return new nook_ToolZusatzprodukte();
        };

        // Model Unique Datensatz
        $pimple['frontModelUniqueRowsArray'] = function(){
            return new Front_Model_UniqueRowsArray();
        };

        // Model Texte der Bettensteuer
        $pimple['frontModelBettensteuerStadt'] = function(){
            return new Front_Model_BettensteuerStadt();
        };

        // Tool Sprache bestimmen
        $pimple['toolSprache'] = function(){
            return new nook_ToolSprache();
        };

        // Tool Zeilenumbruch
        $pimple['toolZeilenumbruch'] = function(){
            return new nook_ToolZeilenumbruch();
        };

        /** Pdf der Rechnung Übernachtung  */
        $model_pdfUebernachtungKunde = new Front_Model_RechnungPdfUebernachtung($pimple); // Pdf der Rechnung Übernachtungen
        $namePdfUebernachtungRechnung = $model_pdfUebernachtungKunde
            ->setDaten($model_kundendatenUndBuchungsdaten) // Übernahme Daten
            ->setSchriften($fontTexte) // Vorgabe Schriften
            ->erstellenPdfSpalten() // Spaltenmodell Pdf
            ->setBuchungsnummer() // Ermitteln Buchungsnummer
            ->setRegistrierungsNummer($registrierungsNummer) // Registrierungsnummer
            ->erstellenGrunddokument() // anlegen leeres Dokument
            ->erstellenRechnungsBloeckeUebernachtung() // Erstellen Rechnungsblöcke Übernachtung
            ->endeRechnung() // Zusammenfassung , Storno
            ->speichernPdf();

        return $namePdfUebernachtungRechnung; // abspeichern Pdf
    }

    /**
     * @param $params
     * @return array
     */
    private function _breadcrumb($params)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep(1, 1)
            ->setParams($params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * @return array
     */
    private function _textDefinitionPdf()
    {
        $fontTexte = array(
            "ueberschrift" => array(
                "font" => Zend_Pdf_Font::FONT_HELVETICA,
                "groesse" => 10
            ),
            "text" => array(
                "font" => Zend_Pdf_Font::FONT_HELVETICA,
                "groesse" => 8
            ),
            "minitext" => array(
                "font" => Zend_Pdf_Font::FONT_HELVETICA,
                "groesse" => 6
            )
        );

        return $fontTexte;
    }

    /**
     * Setzt den Status der Buchung in den Tabellen
     *
     * + tbl_buchungsnummer
     * + tbl_hotelbuchung
     * + tbl_programmbuchung
     * + tbl_produktbuchung
     */
    private function setzeStatusDerBuchung()
    {
        $modelBestellungStatus = new Front_Model_BestellungStatus();
        $modelBestellungStatus->steuerungSetzenStatusBuchungen();

        return;
    }

    public function editAction()
    {
    }

    public function deleteAction()
    {
    }

    /**
     * Trägt die Buchung in die Tabelle 'tbl_zahhlungen' ein
     * + Hotelbuchungen
     * + Produktbuchungen
     * + Programmbuchungen
     *
     * @param $model_kundendatenUndBuchungsdaten
     * @param $aktuelleBuchung
     * @param $notschalter
     */
    private function eintragenBuchungenInZahlungstabelle(
        $model_kundendatenUndBuchungsdaten,
        $aktuelleBuchung,
        $notschalter
    ) {
        /** Eintragung in Tabellen ***/
        $model_zahlungenHotelbuchungen = new Front_Model_BestellungZahlungenHotelbuchungen(); // Eintragen der Zahlungen Hotelbuchungen an die Anbieter
        $model_zahlungenProduktbuchungen = new Front_Model_BestellungZahlungenProduktbuchungen(); // eintragen Zahlungen Produktbuchungen in 'tbl_zahlungen'
        $model_zahlungenProgrammbuchungen = new Front_Model_BestellungZahlungenProgrammbuchungen(); // eintragen Zahlungen Programme in 'tbl_zahlungen'

        // eintragen der Hotelbuchungen Datensätze in die Tabelle 'tbl_zahlungen'
        if (empty($notschalter['hotelbuchung'])) {
            $model_zahlungenHotelbuchungen
                ->setModelData($model_kundendatenUndBuchungsdaten)
                ->setAktuelleBuchungsnummer($aktuelleBuchung['buchungsnummer'])
                ->setAktuellerZaehler($aktuelleBuchung['zaehler'])
                ->eintragenHotelbuchungenTabelleZahlungen();
        }

        // eintragen Produktbuchungen Datensätze in 'tbl_zahlungen'
        if (empty($notschalter['programmbuchung'])) {
            $model_zahlungenProduktbuchungen
                ->setModelData($model_kundendatenUndBuchungsdaten)
                ->setAktuelleBuchungsnummer($aktuelleBuchung['buchungsnummer'])
                ->setAktuellerZaehler($aktuelleBuchung['zaehler'])
                ->eintragenProduktbuchungenTabelleZahlungen();
        }

        // eintragen der Programmbuchungen in die 'tbl_zahlungen'
        if (empty($notschalter['programmbuchung'])) {
            $model_zahlungenProgrammbuchungen
                ->setModelData($model_kundendatenUndBuchungsdaten)
                ->setAktuelleBuchungsnummer($aktuelleBuchung['buchungsnummer'])
                ->setAktuellerZaehler($aktuelleBuchung['zaehler'])
                ->eintragenProgrammbuchungenTabelleZahlungen();
        }

        return;
    }

    /**
     * Übergeben Daten an Mail Kunde
     *
     * + vorbereitende Tätigkeit
     */
    private function anlegenMailAnKunde($aktuelleBuchung, $schaltungMails, $model_kundendatenUndBuchungsdaten)
    {
        $toolRegistrierungsNummer = new nook_ToolRegistrierungsnummer();
        $registrierungsNummer = $toolRegistrierungsNummer
            ->steuerungErmittelnRegistrierungsnummerMitSession()
            ->getRegistrierungsnummer();

         // schalten Online / Offline Modus
        $model_emailKunde = new Front_Model_BestellungEmailKunde($schaltungMails);

        $model_emailKunde
            ->setBuchungsnummer($aktuelleBuchung['buchungsnummer'])
            ->setZaehler($aktuelleBuchung['zaehler'])
            ->setRegistrierungsNummer($registrierungsNummer)
            ->setModelDataKundenUndBuchungsdaten($model_kundendatenUndBuchungsdaten);

        return $model_emailKunde;
    }

    /**
     * Löschen der Vormerkung aus 'tbl_buchungsnummer' mit Buchungsnummer
     *
     * + ermitteln HOB Nummer
     * + löschen Vormerkung
     */
    protected function loeschenVormerkung($buchungsNummer)
    {
        $nookToolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $hobNummer = $nookToolRegistrierungsnummer
            ->setBuchungsnummer($buchungsNummer)
            ->steuerungErmittlungRegistrierungsnummer()
            ->getRegistrierungsnummer();

        $frontModelVormerkungLoeschen = new Front_Model_VormerkungLoeschen();
        $frontModelVormerkungLoeschen
            ->setHobNummer($hobNummer)
            ->steuerungVormerkungLoeschen();

        return;
    }
}


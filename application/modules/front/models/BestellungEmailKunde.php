<?php
/**
* Email an den Kunden
*
* + Pdf der Programmrechnung
* + Pdf der Programmbestätigung
* + Setzt den Pfad zum Pdf der Übernachtungen
* + Übernimmt Kundendaten und Buchungsdatensätze
* + sendet EMail an den Kunden
* + Fügt die Kundendaten in das Mail ein
* + Fügt Zahlungsinformationen
* + Fügt Kundennummer hinzu
* + Fügt den Bestätigungstext ein.
* + Generiert die Buchungstexte
* + Fügt eine Pdf als Anhang hinzu
* + Ermitteln Registrierungsnummer
* + Setzt Grunddaten des Mail. Varianten des Empfänger der Kundenmail
* + Setzen der Betreffzeile in Abhängigkeit des Zaehler im Mail
* + Versendet Mail an den Kunden
*
* @author Stephan.Krauss
* @date 16.11.2012
* @file BestellungEmailKunde.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungEmailKunde extends nook_ToolModel implements arrayaccess
{

    /** @var $_modelTexteBuchungsDatenSaetze Front_Model_BestellungTexteBuchungsdatensaetze */
    protected $_modelTexteBuchungsDatenSaetze = null;
    protected $_mail = null;
    protected $_betreffZeile = null;
    protected $_bodyText = '';
    protected $_mailOffline = null;

    protected $_statischeTexte = array(); // statische Texte
    protected $_datenRechnung = array(); // Daten Rechnung
    protected $_kundenDaten = array(); // Anschrift Kunde
    protected $_kundenId = null; // ID des Kunden
    protected $_buchungsnummerId = null; // ID der Buchungsnummer

    protected $buchungsnummer = null;
    protected $zaehler = null; // version der Buchung
    protected $registrierungsNummer = null;

    protected $_pdfprogrammrechnung = null;
    protected $_pdfProgrammBestaetigung = null;
    protected $_pdfUebernachtungRechnung = null;

    // Fehlerbereich
    private $_errror = 1010;

    // Konditionen
    private $_condition_offline_modus_aktiv = 2;

    // Flags
    private $_flag_offline = false;

    // Tabelle / Views

    public function __construct($__mails)
    {
        if ($__mails['modus'] == $this->_condition_offline_modus_aktiv) {
            $this->_flag_offline = true;
            $this->_mailOffline = $__mails['buchung'];
        }
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestellungEmailKunde
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_BestellungEmailKunde
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $registrierungsnummer
     * @return Front_Model_BestellungEmailKunde
     */
    public function setRegistrierungsNummer($registrierungsnummer)
    {
        $registrierungsnummer = (int) $registrierungsnummer;
        $this->registrierungsNummer = $registrierungsnummer;

        return $this;
    }

    /**
     * Pdf der Programmrechnung
     *
     * @param $__pdfProgrammRechnung
     */
    public function setPdfProgrammRechnung($__pdfProgrammRechnung)
    {
        $this->_pdfprogrammrechnung = $__pdfProgrammRechnung;

        return;
    }

    /**
     * Pdf der Programmbestätigung
     *
     * @param $__pdfProgrammBestaetigung
     */
    public function     setPdfProgrammBestaetigung($__pdfProgrammBestaetigung)
    {
        for ($i = 0; $i<count($__pdfProgrammBestaetigung); $i++) {
            $this->_pdfProgrammBestaetigung[$i] = $__pdfProgrammBestaetigung;
        }
        return;
    }

    /**
     * Setzt den Pfad zum Pdf der Übernachtungen
     *
     * @param $__pdfUebernachtungRechnung
     */
    public function setPdfUebernachtungRechnung($__pdfUebernachtungRechnung)
    {
        $this->_pdfUebernachtungRechnung = $__pdfUebernachtungRechnung;

        return;
    }

    /**
     * Übernimmt Kundendaten und Buchungsdatensätze
     *
     * @param $__fremdModelData
     * @return Front_Model_BestellungEmailKunde
     */
    public function setModelDataKundenUndBuchungsdaten($__kundendatenUndBuchungsdaten)
    {
        $this->_importModelData($__kundendatenUndBuchungsdaten);

        $this->_kundenId = $this->_modelData['_kundenId'];
        $this->_kundenDaten = $this->_modelData['_kundenDaten'];
        $this->_datenRechnung = $this->_modelData['_datenRechnungen'];
        $this->_statischeTexte = $this->_modelData['_statischeFirmenTexte'];
        $this->_buchungsnummerId = $this->_modelData['aktuelleBuchungsnummer'];

        return $this;
    }

    /**
     * sendet EMail an den Kunden
     *
     *
     */
    public function sendEmailAnKunde($notschalter)
    {
        $this
            ->_mailGrunddaten()
            // ->_mailKundendaten() // Anschrift des Kunden
            ->_mailBuchungsnummer() // Buchungsnummer
            ->_mailKundennummer() // Kunden ID
            ->_mailBetreffZeile()
            ->_mailAnrede() // Texte der Buchungen
            ->_mailBestaetigungstext() // Standardtext einer Buchungsbestätigung
            ->_mailPdfAnhang() // fügt bereits generiertes Pdf hinzu
            ->_mailSenden($notschalter); // sendet Mail

    }

    /**
     * Fügt die Kundendaten in das Mail ein
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailKundendaten()
    {

        $this->_bodyText .= $this->_kundenDaten['title'] . " " . $this->_kundenDaten['firstname'] . " " . $this->_kundenDaten['lastname'] . "\n";
        $this->_bodyText .= $this->_kundenDaten['street'] . " " . $this->_kundenDaten['housenumber'] . "\n";
        $this->_bodyText .= $this->_kundenDaten['zip'] . " " . $this->_kundenDaten['city'];

        $this->_bodyText .= "\n\n";

        return $this;
    }

    /**
     * Fügt Zahlungsinformationen
     * und Rechnungsnummer hinzu.
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailBuchungsnummer()
    {

        $this->_bodyText .= translate('Buchungsnummer') . ": ".$this->registrierungsNummer."-".$this->zaehler . "\n";
        $this->_bodyText . "\n";

        return $this;
    }

    /**
     * Fügt Kundennummer hinzu
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailKundennummer()
    {

        $this->_bodyText .= translate('Kundennummer') . ": " . $this->_kundenId . "\n";

        return $this;
    }

    /**
     * Fügt den Bestätigungstext ein.
     *
     * + Standardtext der Originalbuchung
     * + Standartext einer Veränderungsbuchung
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function
    _mailBestaetigungstext()
    {

        $this->_bodyText .= "\n\n";

        // Text entsptrechend Anzeigesprache
        if ($this->zaehler == 1) {
            $bestaetigungsText = $this->_statischeTexte['text_mail_bestaetigung_kunde'];
        } else {
            $bestaetigungsText = $this->_statischeTexte['text_mail_buchungsaenderung_kunde'];
        }

        $this->_bodyText .= $bestaetigungsText;
        $this->_bodyText .= "\n\n";

        return $this;
    }

    /**
     * Generiert die Buchungstexte
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailAnrede()
    {

        $text = "\n\n";

        if ($this->_kundenDaten['title'] == 'Herr') {
            $text .= translate("Sehr geehrter Herr");
        } else {
            $text .= translate("Sehr geehrte Frau");
        }

        $text .= " " . $this->_kundenDaten['lastname'] . ",";

        $text .= "\n";

        $this->_bodyText .= $text;

        return $this;
    }

    /**
     * Fügt eine Pdf als Anhang hinzu
     *
     * + Pdf der Programmrechnung
     * + Pdf der Programmbestätigung
     * + Pdf der Hotelbuchung
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailPdfAnhang()
    {
        $registrierungsnummer = $this->ermittelnRegistrierungsnummer();

//        // Programme Rechnung
//        if (!empty($this->_pdfprogrammrechnung)) {
//            $handle = fopen($this->_pdfprogrammrechnung, 'rb');
//            $rechnungPdf = fread($handle, filesize($this->_pdfprogrammrechnung));
//            fclose($handle);
//
//            $rechnung = new Zend_Mime_Part($rechnungPdf);
//            $rechnung->type = 'application/pdf';
//            $rechnung->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
//            $rechnung->encoding = Zend_Mime::ENCODING_BASE64;
//            $rechnung->filename = "P_" . $registrierungsnummer . "_" . $this->zaehler . ".pdf";
//
//            $this->_mail->addAttachment($rechnung);
//        }

        // Programme Bestätigung
        if (!empty($this->_pdfProgrammBestaetigung)) {


            for ($i = 0; $i <= count($this->_pdfProgrammBestaetigung); $i++) {
                $handle = fopen($this->_pdfProgrammBestaetigung[$i], 'rb');
                $bestaetigungPdf = fread($handle, filesize($this->_pdfProgrammBestaetigung[$i]));
                fclose($handle);

                $bestaetigung = new Zend_Mime_Part($bestaetigungPdf);
                $bestaetigung->type = 'application/pdf';
                $bestaetigung->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                $bestaetigung->encoding = Zend_Mime::ENCODING_BASE64;
                $bestaetigung->filename = "B_".$i."-" . $registrierungsnummer . "_" . $this->zaehler . ".pdf";

                $this->_mail->addAttachment($bestaetigung);
            }

        }

        // Übernachtungen Rechnung
        if (!empty($this->_pdfUebernachtungRechnung)) {
            $handle = fopen($this->_pdfUebernachtungRechnung, 'rb');
            $uebernachtungPdf = fread($handle, filesize($this->_pdfUebernachtungRechnung));
            fclose($handle);

            $uebernachtung = new Zend_Mime_Part($uebernachtungPdf);
            $uebernachtung->type = 'application/pdf';
            $uebernachtung->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $uebernachtung->encoding = Zend_Mime::ENCODING_BASE64;
            $uebernachtung->filename = $registrierungsnummer . "_uebernachtungsrechnung.pdf";

            $this->_mail->addAttachment($uebernachtung);
        }

        return $this;
    }

    /**
     * Ermitteln Registrierungsnummer
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
     * Setzt Grunddaten des Mail. Varianten des Empfänger der Kundenmail
     *
     * + lesen 'static.ini'
     * + hat der Superuser gebucht ?
     * + ist der Kunde im Offlinemodus ?
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailGrunddaten()
    {
        // lesen 'static.ini'
        $static = nook_ToolStatic::getStaticWerte();

        /** @var $this->_mail Zend_Mail */
        $this->_mail = new Zend_Mail('UTF-8');
        $this->_mail->setFrom($static['debugModus']['from']);

        // hat der Superuser gebucht ?
        $flagSuperuser = nook_ToolBuchungsnummer::findeOnlineBuchung($this->_buchungsnummerId);

        // Ist der Kunde im Offlinemodus ?
        $toolOfflinekunde = new nook_ToolOfflinekunde();
        $flagOfflineKunde = $toolOfflinekunde
            ->setBuchungsNummerId($this->_buchungsnummerId)
            ->steuerungErmittlungStatusOfflinekunde()
            ->getStatusOfflinekunde();

        // Buchung durch Superuser
//        if (!empty($flagSuperuser))
//            $this->_mail->addTo($static['standardmails']['offline']['buchung']);
//        // versandt E-Mail im Offline Modus
//        elseif ($static['standardmails']['offline']['modus'] == 2)
//            $this->_mail->addTo($static['standardmails']['offline']['buchung']);
//        // Kunde hat Status 'Offlinekunde == 2'
//        elseif($flagOfflineKunde == 2)
//            $this->_mail->addTo($static['standardmails']['offline']['buchung']);
//        else
//            $this->_mail->addTo($this->_kundenDaten['email']);

        $this->_mail->addTo("tickets@aohostels.com");

        return $this;
    }

    /**
     * Setzen der Betreffzeile in Abhängigkeit des Zaehler im Mail
     *
     * + zaehler == 1 , Originalbuchung
     * + zaehler > 1 , veränderungsbuchung
     *
     * @return Front_Model_BestellungEmailKunde
     */
    public function _mailBetreffZeile()
    {
        // Originalbuchung
        if ($this->zaehler == 1) {
            $betreffZeile = translate("Buchung Nebenleistung Buchungs Nr. ");
            $betreffZeile .= $this->registrierungsNummer . "-" . $this->zaehler;
        } // Veränderungsbuchung
        else {
            $betreffZeile = translate("Ihre Buchungsänderung Nr. ");
            $betreffZeile .= $this->registrierungsNummer . "-" . $this->zaehler;
        }

        $this->_betreffZeile = $betreffZeile;

        $this->_mail->setSubject($betreffZeile);

        return $this;
    }

    /**
     * Versendet Mail an den Kunden
     *
     * + Kontrolle der Fallvarianten des senden des Kundenmail
     * + gesendet wird:
     * + wenn Programmbuchung vorliegt und Hotelbuchung abgeschaltet
     * + wenn Hotelbuchung vorliegt und Programmbuchung abgeschaltet
     * + wenn kein Notschalter aktiv
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailSenden($notschalter)
    {

        $senden = false;

        // Fallvarianten abprüfen
        if (!empty($notschalter['programmbuchung']) and strstr($this->_datenRechnung['rechnungsnummer'], 'H')) {
            $senden = true;
        } elseif (!empty($notschalter['hotelbuchung']) and strstr($this->_datenRechnung['rechnungsnummer'], 'P')) {
            $senden = true;
        } elseif (empty($notschalter['hotelbuchung']) and empty($notschalter['programmbuchung'])) {
            $senden = true;
        }

        // versenden Mail
        if ($senden == true) {
            $this->_mail->setBodyText($this->_bodyText);
        }

        if (empty($notschalter['programmbuchung'])) {
            $kontrolleMailversandt = $this->_mail->send();
        }

        return $this;
    }
}
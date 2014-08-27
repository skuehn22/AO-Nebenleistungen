<?php
/**
* Versandt des Buchungs Mail an den Programmanbieter
* + Setzt den Offline Modus und die Mailadresse
* + Setzt den Offline Modus eines Programmanbieters und die Absende Mailadresse
* + Übernimmt die Kundendaten und
* + Ermittelt die Informationen zu den gebuchten Programmen
* + Löscht die Datensätze der Buchungspauschale aus der Bestelliste der programme
* + Findet Programmbuchungen
* + Ermittelt die Programminformationen
* + Ermittelt die Informationen der Preisvariante
* + Ermitteln der Daten des Programmanbieters
* + versendet die Mails an die Programmanbieter
* + Versand des Mail an den Programmanbieter
* + Fügt die Fusszeile an den Text an.
* + Versand eines Mail an Herden Onlinebuchung
* + Setzen Mailadresse für Programm - Mail. Unterscheidung zwischen Online und Offline Buchung des Programmes
* + Setz die Mailadresse von HOB
* + Erstellt den Mailtext einer Veraenderungsbuchung
* + Erstellt den Text der Mail einer Erstbuchung
* + Fügt dem Mailtext die Personendaten hinzu
* + Ermittelt den Mailtext des gebuchten Programmes
* + Versendet Mail an den Programmanbieter
* + Fügt dem Mail den Buchungshinweis hinzu.
*
* @author Stephan.Krauss
* @date 26.03.13
* @file BestellungEmailAnbieter.php
* @package front
* @subpackage model
*/
class Front_Model_BestellungEmailAnbieter extends nook_ToolModel implements arrayaccess
{

    /** @var $_modelTexteBuchungsDatenSaetze Front_Model_BestellungTexteBuchungsdatensaetze */
    protected $_modelTexteBuchungsDatenSaetze = null;
    protected $_mail = null;

    protected $_betreffZeileErstbuchung = "Buchung, A&O Hostel & Hotel";
    protected $_betreffZeileVeraenderungsbuchung = "Buchungsänderung, ";

    protected $_bodyText = '';
    protected $_mailOffline = null;

    protected $_statischeTexte = array(); // statische Texte
    protected $_datenRechnung = array(); // Daten der Rechnung
    protected $_kundenDaten = array(); // Anschrift Kunde
    protected $_kundenId = null; // Kunden ID

    protected $_buchungsNummerId = null; // Buchungsnummer ID
    protected $registrierungsNummer = null;
    protected $zaehler = null;

    protected $_gebuchteProgramme = array();

    // Einstellungen der /application/config/static.ini
    protected $staticMails = array();

    // Fehlerbereich
    private $_errror = 980;
    private $_error_keine_buchungen_vorhanden = 981;
    private $_error_keine_programmbeschreibung_vorhanden = 982;
    private $_error_keine_preisbeschreibung_vorhanden = 983;
    private $_error_keine_programmanbieter_daten_vorhanden = 984;

    // Konditionen
    private $_condition_offline_modus_aktiv = 2;
    private $_condition_offline_modus_passiv = 1;
    private $_condition_sprache_deutsch = 1;
    private $_condition_sprache_englisch = 2;
    private $_condition_wochentage_bezeichnung_kurzform = 1;
    private $_condition_wochentage_bezeichnung_langform = 2;
    protected $condition_neue_buchung = 'neue Buchung';
    protected $condition_veraenderte_buchung = 'Veränderungsbuchung';
    protected $condition_stornierung_buchung = 'Stornierung';

    // Flags
    private $_flag_offline = false;

    // Tabelle / Views
    private $_tabelleProgrammbuchung = null;
    private $_tabelleProgrammdetails = null;
    private $_tabelleProgrammbeschreibung = null;

    private $_viewPreisvariantenDe = null;
    private $_viewPreisvariantenEn = null;

    private $_viewProgrammdetailsAdressen = null;

    /**
     * Setzt den Offline Modus eines Programmanbieters und die Absende Mailadresse
     *
     * + Instanziieren der Tabellen
     * + $this->_flag_offline = false , Mails werden an Programmanbieter versandt
     * + $this->_flag_offline = true , Mails werden nicht an Programmanbieter versandt !!!
     *
     * @param $__mails
     */
    public function __construct($__mails)
    {
        // Einstellungen der static.ini
        $this->staticMails = $__mails;

        /** @var _tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var _tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $this->_tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();
        /** @var _tabelleProgrammbeschreibung Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung();

        /** @var _viewPreisvariantenDe Application_Model_DbTable_viewPreisvariantenDe */
        $this->_viewPreisvariantenDe = new Application_Model_DbTable_viewPreisvariantenDe();
        /** @var  _viewPreisvariantenEn Application_Model_DbTable_viewPreisvariantenEn */
        $this->_viewPreisvariantenEn = new Application_Model_DbTable_viewPreisvariantenEn();

        /** @var _viewProgrammdetailsAdressen Application_Model_DbTable_viewProgrammdetailsAdressen */
        $this->_viewProgrammdetailsAdressen = new Application_Model_DbTable_viewProgrammdetailsAdressen();
    }

    /**
     * Übernimmt die Kundendaten und
     * die Daten der Programmbuchung aus
     * einem Fremdmodel
     *
     * @param $__fremdModelData
     * @return Front_Model_BestellungEmailAnbieter
     */
    public function setModelDataKundenUndBuchungsdaten($__fremdModelData)
    {
        $this->_importModelData($__fremdModelData);

        $this->_kundenDaten = (array) $this->_modelData['_kundenDaten']; // Kundendaten
        $this->_datenRechnung = $this->_modelData['_datenRechnungen']; // Daten der Rechnung
        $this->_statischeTexte = $this->_modelData['_statischeFirmenTexte']; // statische Firmen Texte

        $this->_buchungsNummerId = $this->_modelData['aktuelleBuchungsnummer']; // ID der Buchungsnummer 'tbl_buchungsnummer'
        $this->_kundenId = $this->_modelData['_kundenId']; // Kunden ID

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_BestellungEmailAnbieter
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param $registrierungsnummer
     * @return Front_Model_BestellungEmailAnbieter
     */
    public function setRegistrierungsNummer($registrierungsnummer)
    {
        $registrierungsnummer = (int) $registrierungsnummer;
        $this->registrierungsNummer = $registrierungsnummer;

        return $this;
    }

    /**
     * Ermittelt die Informationen zu den gebuchten Programmen
     *
     * + findet Programmbuchungen des Kunden
     * + findet Programmbeschreibung
     * + findet Preisvariante
     * + findet Basisdaten der Programmanbieter
     * + ermitteln Offline / Onlinebuchung Programm

     */
    public function ermittelnDaten()
    {
        // findet die gebuchten Programme eines Kunden
        $this->_findeProgrammbuchungenDesKunden();

        // keine Programmbuchung vorhanden
        if ($this->_gebuchteProgramme == null) {
            return $this;
        }

        // ermitteln Informationen zu den gebuchten Programmen
        for ($i = 0; $i < count($this->_gebuchteProgramme); $i++) {

            // ermitteln Programminformationen in deutsch
            $this->ermittelnProgrammbeschreibungDe($i);

            // ermitteln Programminformation in englisch
            $this->ermittelnProgrammbeschreibungEn($i);

            // ermitteln Information der Preisvarianten in deutsch
            $this->ermittelnPreisvariantenDe($i);

            // ermitteln Beschreibung der Preisvariante in englisch
            $this->ermittelnPreisvariantenEn($i);

            // ermitteln Informationen zum Programmanbieter
            $this->_ermittelnProgrammanbieter($i);
        }

        return $this;
    }

    /**
     * Ermittelt die Preisvariante in englisch
     *
     * @param $i
     * @return string
     * @throws nook_Exception
     */
    protected function ermittelnPreisvariantenEn($i)
    {
        $cols = array(
            'preisvariante_eng'
        );

        $wherePreisvarianteId = "id = " . $this->_gebuchteProgramme[$i]['tbl_programme_preisvarianten_id'];

        $select = $this->_viewPreisvariantenEn->select();
        $select
            ->from($this->_viewPreisvariantenEn, $cols)
            ->where($wherePreisvarianteId);

        $query = $select->__toString();

        $rows = $this->_viewPreisvariantenDe->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception('keine Beschreibung der Preisvariante in englisch');
        }

        $this->_gebuchteProgramme[$i]['programmvariante']['preisvariante_en'] = $rows[0]['preisvariante_eng'];

        return $rows[0]['preisvariante_eng'];
    }

    /**
     * Löscht die Datensätze der Buchungspauschale aus der Bestelliste der Programme
     *
     * + holt Programm ID und Preisvarianten ID aus 'static.ini'
     * + löscht Datensätze der Buchungspauschale
     * + baut Array der Programmbuchungen neu uf
     *
     * @return Front_Model_BestellungEmailAnbieter
     */
    public function loeschenBuchungspauschaleAusBestelliste()
    {
        if(count($this->_gebuchteProgramme) == 0)
            return;

        $static = nook_ToolStatic::getStaticWerte();

        $buchungspauschaleProgrammId = $static['buchungspauschale']['programmId'];
        $buchungspauschalePreisvarianteId = $static['buchungspauschale']['preisvarianteId'];

        $gebuchteProgramme = array();
        foreach($this->_gebuchteProgramme as $key => $programm){
            if( ($programm['programmdetails_id'] != $buchungspauschaleProgrammId) and ($programm['tbl_programme_preisvarianten_id'] != $buchungspauschalePreisvarianteId) )
                $gebuchteProgramme[] = $programm;
        }

        $this->_gebuchteProgramme = $gebuchteProgramme;

        return $this;
    }

    /**
     * Findet Programmbuchungen
     * des Kunden entsprechend der Buchungsnummer
     *
     * @return Front_Model_BestellungEmailAnbieter
     */
    private function _findeProgrammbuchungenDesKunden()
    {
        $select = $this->_tabelleProgrammbuchung->select();
        $select
            ->where("buchungsnummer_id = " . $this->_buchungsNummerId)
            ->where("zaehler = " . $this->zaehler);

        $query = $select->__toString();

        $rows = $this->_tabelleProgrammbuchung->fetchAll($select)->toArray();
        if (count($rows) == 0) {
            return;
        }

        $this->_gebuchteProgramme = $rows;

        return;
    }

    /**
     * Ermittelt die Programminformationen in deutsch
     * eines Programmes
     *
     * @param $i
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittelnProgrammbeschreibungDe($i)
    {
        $select = $this->_tabelleProgrammbeschreibung->select();
        $select
            ->where("sprache = " . $this->_condition_sprache_deutsch)
            ->where("programmdetail_id = " . $this->_gebuchteProgramme[$i]['programmdetails_id']);

        $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception('Programmbeschreibung deutsch nicht vorhanden');
        }

        // Programminformation
        $rows[0]['confirm_de'] = $rows[0]['confirm_1'];
        // Programmname
        $rows[0]['progname_de'] = $rows[0]['progname'];


        $this->_gebuchteProgramme[$i]['programmbeschreibung'] = $rows[0];

        return $rows[0]['confirm_de'];
    }

    /**
     * Ermittelt die Programminformationen in englisch
     *
     * @param $i
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittelnProgrammbeschreibungEn($i)
    {
        $cols = array(
            new Zend_Db_Expr("confirm_1 as confirm_en"),
            'progname'
        );

        $select = $this->_tabelleProgrammbeschreibung->select();

        $select
            ->from($this->_tabelleProgrammbeschreibung, $cols)
            ->where("sprache = " . $this->_condition_sprache_englisch)
            ->where("programmdetail_id = " . $this->_gebuchteProgramme[$i]['programmdetails_id']);

        $query = $select->__toString();

        $rows = $this->_tabelleProgrammbeschreibung->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception('Programmbeschreibung englisch nicht vorhanden');
        }

        $this->_gebuchteProgramme[$i]['programmbeschreibung']['confirm_en'] = $rows[0]['confirm_en'];
        $this->_gebuchteProgramme[$i]['programmbeschreibung']['progname_en'] = $rows[0]['progname'];

        return $rows[0]['confirm_en'];
    }

    /**
     * Ermittelt die Informationen der Preisvariante in deutsch
     *
     * @param $i
     * @throws nook_Exception
     */
    private function ermittelnPreisvariantenDe($i)
    {
        $select = $this->_viewPreisvariantenDe->select();
        $select->where("id = " . $this->_gebuchteProgramme[$i]['tbl_programme_preisvarianten_id']);

        $rows = $this->_viewPreisvariantenDe->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception($this->_error_keine_preisbeschreibung_vorhanden);
        }

        $this->_gebuchteProgramme[$i]['programmvariante'] = $rows[0];

        return;
    }

    /**
     * Ermitteln der Daten des Programmanbieters
     *
     * @param $i
     * @throws nook_Exception
     */
    private function _ermittelnProgrammanbieter($i)
    {

        $programmdetailId = $this->_gebuchteProgramme[$i]['programmdetails_id'];

        $rows = $this->_viewProgrammdetailsAdressen->find($programmdetailId)->toArray();
        if (count($rows) > 1) {
            throw new nook_Exception($this->_error_keine_programmanbieter_daten_vorhanden);
        }

        $this->_gebuchteProgramme[$i]['programmanbieter'] = $rows[0];

        return;
    }

    /**
     * versendet die Mails an die Programmanbieter
     *
     * + Aufbereitung Datensatz für Mail
     * + zusammenfassen der Programme an einen Programmanbieter
     * + berücksichtigt die Erstbuchung , zaehler == 1
     * + berücksichtigt die Veränderungsbuchungen , zaehler > 1
     *
     * @return Front_Model_BestellungEmailAnbieter
     */
    public function sendenMails()
    {
        // Neuorganisation der Datensätze für den Mailversand
        $frontMailDatenProgrammanbieter = new Front_Model_MailDatenProgrammbestellung($this->_gebuchteProgramme);

        $datenMailProgrammanbieter = $frontMailDatenProgrammanbieter
            ->sortiereNachProgrammanbieter()
            ->getDatenMailProgrammanbieter();

        // Mailadresse Absender
        $static = nook_ToolStatic::getStaticWerte();
        $mailFrom = $static['debugModus']['from'];

        // erstellen Mail
        /** @var  $_mail Zend_Mail */
        $this->_mail = new Zend_Mail('UTF-8');

        // Betreffzeile Erstbuchung
        if ($this->zaehler == 1){
            $subject = $this->_betreffZeileErstbuchung." ".$this->registrierungsNummer."-".$this->zaehler;
            $this->_mail->setSubject($subject);
        }
        // Betreffzeile Veränderungsbuchung
        else{
            $subject = $this->_betreffZeileVeraenderungsbuchung." ".$this->registrierungsNummer."-".$this->zaehler;
            $this->_mail->setSubject($subject);
        }

        // Absender der Mail
        $this->_mail->setFrom($mailFrom);

        // versenden der Mails an die Programmanbieter oder HOB
        for ($i = 0; $i < count($datenMailProgrammanbieter); $i++) {
            $this->mailAnProgrammanbieter($datenMailProgrammanbieter[$i], $subject);
        }

        return $this;
    }

    /**
     * Versand des Mail an den Programmanbieter
     *
     * + Mail Erstbuchung
     * + Mail Veraenderungsbuchung
     * + versandt Mail
     *
     * @return int
     */
    private function mailAnProgrammanbieter(array $datenMailProgrammanbieter, $subject)
    {
        // Setzen Mailadresse für Programm Onlinebuchung / Offlinebuchung
        $this->_mailEmpfaengerProgrammanbieter($datenMailProgrammanbieter, $subject);

        // Mail Erstbuchung, Zähler = 1
        if ($this->zaehler == 1)
            $this->mailTextErstbuchung($datenMailProgrammanbieter);
        // Mail Veraenderungsbuchung
        else
            $this->mailTextVeraenderungsbuchung($datenMailProgrammanbieter);

        $this->mailTextFusszeile();

        // Mail versenden an Programmanbieter
        $this->_mailSendenProgrammanbieter();

        return;
    }

    /**
     * Fügt die Fusszeile an den Text an. Fusszeile in deutsch
     *
     * + Grußformel
     * + Angaben zur Firma
     */
    private function mailTextFusszeile()
    {
        $cols = array(
            'text'
        );

        $whereSprache = "sprache_id = ".$this->_condition_sprache_deutsch;
        $whereTextblock = "blockname = 'text_mail_programmanbieter'";

        $tabelleTextbausteine = new Application_Model_DbTable_textbausteine();
        $select = $tabelleTextbausteine->select();
        $select
            ->from($tabelleTextbausteine, $cols)
            ->where($whereSprache)
            ->where($whereTextblock);

        $rows = $tabelleTextbausteine->fetchAll($select)->toArray();

        $this->_bodyText .= $rows[0]['text'];

        return;
    }

    /**
     * Versand eines Mail an Herden Onlinebuchung
     *
     * + Erstbuchung
     * + Veraenderungsbuchung
     *
     * @param $i
     */
    private function mailAnHob($datenMailProgrammanbieter)
    {
        // Mail Grunddaten
        $this->_mailEmpfaengerHob();

        // Mail erstellt den Mail Text ohne Buchungshinweis
        if($this->zaehler == 1)
            $this->mailTextErstbuchung($datenMailProgrammanbieter);
        // Veraenderungsbuchung
            $this->mailTextVeraenderungsbuchung($datenMailProgrammanbieter);

        // Buchungshinweis in der Mail
        $this->_textBuchungshinweis();

        // Mail versenden an HOB
        $this->_mailEmpfaengerHob();
    }

    /**
    * Setzen Mailadresse für Programm - Mail. Unterscheidung zwischen Online und Offline Buchung des Programmes
    *
    * + $this->_flag_offline == false , Mail an Programmanbieter
    * + $this->_flag_offline == true , Mail an HOB
    *
    * @return Front_Model_BestellungEmailKunde
    */
    private function _mailEmpfaengerProgrammanbieter(array $datenMailProgrammanbieter, $subject)
    {
        // Mailadresse Empfänger löschen
        $this->_mail->clearRecipients();

        // Kontrolle ob in den Programmbestellungen eine Offlinebuchung ist
        $toolKontrolleOfflinebuchung = new nook_ToolKontrolleOfflinebuchung($datenMailProgrammanbieter['programme']);
        $modusOfflinebuchung = $toolKontrolleOfflinebuchung
            ->kontrolleOfflinebuchung()
            ->getModusOfflinebuchung();

        // Offlinebuchung für alle Programmanbieter geschaltet
        if($this->staticMails['offline']['modus'] == 2)
            $this->_mail->addTo($this->staticMails['offline']['programmanbieter']);
        // Mailversand an alle Programmanbieter ?
        elseif($this->staticMails['mailversand']['programmanbieter'] == 1)
            $this->_mail->addTo($this->staticMails['offline']['programmanbieter']);
        // wenn mindestens ein Programm eine Offlinebuchung ist, dann an HOB
        elseif(true == $modusOfflinebuchung)
            $this->_mail->addTo($this->staticMails['offline']['programmanbieter']);
        // Mail an Programmanbieter und Zweitschrift
        else{
            $this->_mail->addTo($datenMailProgrammanbieter['programmanbieter']['email']);
            $this->_mail->addTo($this->staticMails['offline']['zweitschrift']);
        }

        return;
    }



    /**
    * Setz die Mailadresse von HOB
    *
    */
    private function _mailEmpfaengerHob()
    {
        // $this->_mail->addTo($this->_mailOffline);

        return;
    }

    /**
     * Erstellt den Mailtext einer Veraenderungsbuchung
     *
     * @param $i
     */
    public function mailTextVeraenderungsbuchung($datenMailProgrammanbieter)
    {
        $this->_bodyText = null;
        $text = array();

        // Allgemein
        $text[] = "Buchungsänderung! ".$this->registrierungsNummer."-".$this->zaehler;
        $text[] = "";
        $text[] = "Sehr geehrter Programmanbieter,";

        // Programmbuchung
        //$text[] = "ändern Sie die bestehende Buchung Nr. ".$this->registrierungsNummer." bitte wie folgt:";
        //$text[] = "";

        // erstellen Text der Programmbuchungen
        $text = $this->blockProgramm($text, $datenMailProgrammanbieter);

        $text[] = "";
        if (!empty($datenMailProgrammanbieter['programmbeschreibung']['an_prog_1'])) {
            $text[] = $datenMailProgrammanbieter['programmbeschreibung']['an_prog_1'];
        }

        $text[] = "";
        $text[] = "";

        $text = $this->blockPersonendaten($text);

        $gesamttext = "";
        for ($i = 0; $i < count($text); $i++) {
            $gesamttext .= $text[$i] . "\n";
        }

        $this->_bodyText = $gesamttext;

        return;
    }

    /**
     * Erstellt den Text der Mail einer Erstbuchung
     *
     * + Anrede
     * + Handlungsaufforderung
     *
     * @param $i
     */
    private function mailTextErstbuchung($datenMailProgrammanbieter)
    {
        $this->_bodyText = null;
        $text = array();

        // Allgemein
        $text[] = "Sehr geehrter Programmpartner,";

        // Programmbuchung
        //$text[] = "folgende Programmleistungen wurden gebucht:";
        $text[] = "";

        // erstellen Text der Programmbuchungen
        $text = $this->blockProgramm($text, $datenMailProgrammanbieter);

        $text[] = "";
        if (!empty($datenMailProgrammanbieter['programmbeschreibung']['an_prog_1'])) {
            $text[] = $datenMailProgrammanbieter['programmbeschreibung']['an_prog_1'];
        }

        $text[] = "";
        $text[] = "";

        // Personendaten
        $text = $this->blockPersonendaten($text);

        $gesamttext = "";
        for ($i = 0; $i < count($text); $i++) {
            $gesamttext .= $text[$i] . "\n";
        }

        $this->_bodyText = $gesamttext;

        return;
    }

    /**
     * Fügt dem Mailtext die Personendaten hinzu
     *
     * @param array $text
     * @return array
     */
    private function blockPersonendaten(array $text)
    {
        $personendaten = $this->_kundenDaten;

        $text[] = "Kundendetails";
        $text[] = "";
        $text[] = "ASSD-Buchungsnummer: ".$personendaten['assd_nummer'];
        $text[] = "Gruppennname: ".$personendaten['gruppenname'];
        $text[] = "Ansprechpartner:  ".$personendaten['title'] . " " . $personendaten['firstname'] . " " . $personendaten['lastname'];
        $text[] = "Telefon: ".$personendaten['phonenumber'];
        $text[] = "";

        return $text;
    }

    /**
     * Ermittelt den Mailtext des gebuchten Programmes
     *
     * + Programmname
     * + Preisvariante
     * + Name Wochentag
     * + Programmsprache
     * + Datum wenn vorhanden
     * + Zeit wenn vorhanden
     *
     * @param array $text
     * @return array
     */
    private function blockProgramm(array $text, $datenMailProgrammanbieter)
    {
        for($i=0; $i < count($datenMailProgrammanbieter['programme']); $i++){

            if($datenMailProgrammanbieter['programme'][$i]['programmbeschreibung']['programmdetail_id']==$datenMailProgrammanbieter['programme'][$i-1]['programmbeschreibung']['programmdetail_id'] AND $datenMailProgrammanbieter['programme'][$i]['buchungsdaten']['datum']==$datenMailProgrammanbieter['programme'][$i-1]['buchungsdaten']['datum']){

                // einzelnes gebuchtes Programm
                $gebuchtesProgramm = $datenMailProgrammanbieter['programme'][$i];+// Preisvariante
                $text[] = $gebuchtesProgramm['buchungsdaten']['anzahl'] . " * " . $gebuchtesProgramm['programmvariante']['preisvariante_de'];



            }else{// einzelnes gebuchtes Programm
                $gebuchtesProgramm = $datenMailProgrammanbieter['programme'][$i];

                // Ermittlung der Programmsprache
                $toolProgrammsprache = new nook_ToolProgrammsprache();
                $programmsprache = $toolProgrammsprache
                    ->setProgrammsprache($gebuchtesProgramm['buchungsdaten']['sprache'])
                    ->setAnzeigespracheId($this->_condition_sprache_deutsch)
                    ->steuerungErmittlungProgrammsprache()
                    ->getBezeichnungProgrammsprache();

                // Ermittlung Bezeichnung Wochentag
                $toolWochentageNamen = new nook_ToolWochentageNamen();
                $bezeichnungWochentag = $toolWochentageNamen
                    ->setAnzeigespracheId($this->_condition_sprache_deutsch)
                    ->setDatum($gebuchtesProgramm['buchungsdaten']['datum'])
                    ->setAnzeigeNamensTyp($this->_condition_wochentage_bezeichnung_langform)
                    ->steuerungErmittelnWochentag()
                    ->getBezeichnungWochentag();

                // Kennzeichnung Status des Programm
                if($gebuchtesProgramm['buchungsdaten']['zaehler'] == 1)
                    $text[] = $this->condition_neue_buchung;
                elseif( ($gebuchtesProgramm['buchungsdaten']['zaehler'] > 1) and ($gebuchtesProgramm['buchungsdaten']['anzahl'] > 0) )
                    $text[] = $this->condition_veraenderte_buchung;
                elseif(($gebuchtesProgramm['buchungsdaten']['zaehler'] > 1) and ($gebuchtesProgramm['buchungsdaten']['anzahl'] == 0))
                    $text[] = $this->condition_stornierung_buchung;


                // Umwandlung Datum
                if( ($gebuchtesProgramm['buchungsdaten']['datum'] == '0000-00-00') or (empty($gebuchtesProgramm['buchungsdaten']['datum'])) )
                    $datum = false;
                else
                    $datum = nook_ToolDatum::wandleDatumEnglischInDeutsch($gebuchtesProgramm['buchungsdaten']['datum']);

                // Zeit ohne Sekunden
                if( ($gebuchtesProgramm['buchungsdaten']['zeit'] == '00:00:00') or (empty($gebuchtesProgramm['buchungsdaten']['zeit'])) )
                    $zeit = false;
                else
                    $zeit = nook_ToolZeiten::kappenZeit($gebuchtesProgramm['buchungsdaten']['zeit'], 2);

                // Datum mit Wochentag und Zeit ohne Sekunden
                $programmNameMitDatumUndZeit = $gebuchtesProgramm['programmbeschreibung']['progname'];

                if(!empty($datum))
                    $programmNameMitDatumUndZeit .= " am " .$bezeichnungWochentag.", ". $datum;

                if(!empty($zeit))
                    $programmNameMitDatumUndZeit .= " um ".$zeit." Uhr";

                $text[] = $programmNameMitDatumUndZeit;

                // Preisvariante
                $text[] = $gebuchtesProgramm['buchungsdaten']['anzahl'] . " * " . $gebuchtesProgramm['programmvariante']['preisvariante_de'];

                // Programmsprache
                if(!empty($programmsprache))
                    $text[] = 'gewählte Sprache des Programmes: '.$programmsprache;

                // Leerzeile
                $text[] = "";




            }



        }

        return $text;
    }

    /**
     * Versendet Mail an den Programmanbieter
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _mailSendenProgrammanbieter()
    {
        $this->_mail->setBodyText($this->_bodyText);
        $this->_mail->send();

        return $this;
    }

    /**
     * Fügt dem Mail den Buchungshinweis hinzu.
     *
     * @return Front_Model_BestellungEmailKunde
     */
    private function _textBuchungshinweis()
    {

        $buchungshinweis = nook_ToolBuchungsnummer::getBuchungshinweis();

        if (!empty($buchungshinweis)) {
            $this->_bodyText .= "\n\n";
            $this->_bodyText .= translate('Folgende Buchungsinformation wurde an uns übermittelt');
            $this->_bodyText .= "\n\n";

            $buchungshinweis = wordwrap($buchungshinweis, 80, "\n");
            $this->_bodyText .= $buchungshinweis;

            $this->_bodyText .= ": \n\n";
        }

        return $this;
    }
}

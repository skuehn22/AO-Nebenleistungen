<?php
/**
 * Beschreibung der Klasse
 * Nimmt die Bestätigung der Hotelbuchung von 'Autoupdate' entgegen.
 * Setzt den Buchungsstatus und gibt eine 'success' zurück
 *
 * <BookingConfirmations>
 *   <BookingConfirmation HotelCode="AOB1" ResId_Value="55" ConfNr="1092" />
 * </BookingConfirmations>
 *
 * + ResId_Value => Buchungsnummer
 * 
 * @author Stephan.Krauss
 * @since 15.05.12 12:31
 */
 
class confirmBooking{

    // Benutzerkennung und Passwort
    protected $UserName = null;
    protected $Password = null;

    // vorgegebene Benutzerkennung und Passwort
    protected $kontrolleUserName = 'wertxcde';
    protected $kontrollePassword = '12gtzt36';

    protected $flagAuth = false;

    private $_db_connect; // Datenbankverbindung
    private $_xmlReader; // XML Reader
    private $_errors = array();
    private $_errorMessage = false;

    private $_writeDebugFile = false;

    private $_condition_bereich_hotel = 6;

    private $_condition_tbl_xml_buchung_wurde_bestaetigt = 5;
    private $_condition_tbl_hotelbuchung_status = 5;
    private $_condition_tbl_produktbuchung_status = 6;


    /**
     * Verbindung zur Datenbank
     *
     * @param $__xmlConfirm
     */
    public function __construct(){

        $this->_db_connect = mysqli_connect('localhost', 'db1154036-noko', 'huhn9huhn');
		mysqli_select_db($this->_db_connect, 'db1154036-noko') or die( "keine Verbindung zur Datenbank");

        return;
    }

    /**
     * Übernahme XML Daten und
     *
     * @param $__xmlConfirm
     * @return confirmBooking
     */
    public function setXmlData($__xmlConfirm){

        $fp = fopen('confirm.xml','w');
        fputs($fp,$__xmlConfirm);
        fclose($fp);

        return $this;
    }

    /**
     * Start der Verarbeitung
     *
     * @return confirmBooking
     */
    public function start(){

        // lesen XML Block
        $this->_xmlReader = new XMLReader;
        $this->_xmlReader->open('confirm.xml');

        // auflisten der Confirm Meldungen
        $this->_loopConfirmMessages();

        return $this;
    }

    /**
     * Durchläuft die vorhandenen
     * Buchungsnummern und setzt Status in den
     * Tabellen.
     *
     */
    private function _loopConfirmMessages(){

        while($this->_xmlReader->read()){


            // Notiz: Benutzerkennung und Passwort
            // Benutzerkennung und Passwort
            if( $this->_xmlReader->name == 'UserName' or $this->_xmlReader->name == 'Password' ){

                // findet Startelement
                if($this->_xmlReader->nodeType == XMLReader::ELEMENT){
                    $nameAuth = $this->_xmlReader->name;
                    $wertAuth = $this->_xmlReader->readString();

                    $this->checkBenutzerUndPasswort($nameAuth,$wertAuth);
                }
            }

            if($this->_xmlReader->name == 'BookingConfirmation'){
                $buchungsNummer = $this->_xmlReader->getAttribute('ResId_Value');

                // notice: zerlegen der Buchungsnummer, da die übergebene Buchungsnummer
                // aus Buchungsnummer und Teilrechnungsnummer besteht
                // Bsp.: 65-6

                $teileBestaetigung = explode("-",$buchungsNummer);

                $bestaetigungsNummer = $this->_xmlReader->getAttribute('ConfNr');

                $this->_setzeStatus($teileBestaetigung[0], $teileBestaetigung[1],  $bestaetigungsNummer);
            }
        }
    }

    /**
     * Registriert Benutzerkennung und Passwort und Vergleich der Übereinstimmung.
     *
     * + schalten $flagAuth
     * + $flagAuth = true, Kontrolle erfolgreich
     *
     *
     * @param $nameAuth
     * @param $wertAuth
     */
    protected function checkBenutzerUndPasswort($nameAuth,$wertAuth){
        if($nameAuth == 'UserName')
            $this->UserName = $wertAuth;

        if($nameAuth == 'Password')
            $this->Password = $wertAuth;

        if(!empty($this->UserName) and !empty($this->Password)){
            if( ($this->UserName == $this->kontrolleUserName) and ($this->Password == $this->kontrollePassword))
                $this->flagAuth = true;
        }
    }

    /**
     * setzt den Status in den Tabellen
     *
     * + 'tbl_buchungsnummer'
     * + 'tbl_hotelbuchung'
     * + 'tbl_produktbuchung'
     * + 'tbl_xml_buchung'
     *
     * setzt die Bestaetigungsnummer in
     *
     * + 'tbl_buchungsnummer'
     */
    private function _setzeStatus($__buchungsNummer, $__teilrechnungsNummer, $__bestaetigungsNummer){

        // Notiz: Einbau Benutzerkennung und Passwort

        // Veränderung Status 'tbl_xml_buchung' , Übernahme ASSD Conf Nummer, Status = 5
        $sql = "update tbl_xml_buchung set status = ".$this->_condition_tbl_xml_buchung_wurde_bestaetigt.",confNummer = '".$__bestaetigungsNummer."'  where buchungsnummer_id = ".$__buchungsNummer." and bereich = ".$this->_condition_bereich_hotel." and teilrechnungen_id = ".$__teilrechnungsNummer;
        $result = mysqli_query($this->_db_connect, $sql);

        // Veränderung Status 'tbl_hotelbuchung', Status = 5
        $sql = "update tbl_hotelbuchung set status = ".$this->_condition_tbl_hotelbuchung_status." where buchungsnummer_id = ".$__buchungsNummer." and teilrechnungen_id = ".$__teilrechnungsNummer;
        $result = mysqli_query($this->_db_connect, $sql);

        // Veränderung Status 'tbl_produktbuchung', Status = 6
        $sql = "update  tbl_produktbuchung set status = ".$this->_condition_tbl_produktbuchung_status." where buchungsnummer_id = ".$__buchungsNummer." and teilrechnungen_id = ".$__teilrechnungsNummer;
        $result = mysqli_query($this->_db_connect, $sql);

        return $this;
    }

    /**
     *
     *
     * @param $__buchungsNummer
     */
    private function _fehlermeldungen($__buchungsNummer){
        $this->_errors[] = $__buchungsNummer;

        return;
    }

    /**
     * Sendet Bestätigungsmeldung an ASSD
     */
    public function response(){
        if(count($this->_errors) > 0){
            $message = "<Errors>";
            $message .=  "<success>false</success>";

            for($i=0; $i < count($this->_errors); $i++){
                $message .= "<Error ResId_Value='".$this->_errors[$i]."'></Error>";
            }

            $message .= "</Errors>";

            if($this->_writeDebugFile){
                // Ausgabe Fehlermeldung in Datei
                $datum = date("Y_m_d_H_i_s");
                $fp = fopen('error_' . $datum . ".xml", 'w');
                fputs($fp, $message);
                fclose($fp);
            }


            echo $message;

        }
        else{
            $message = "<success></success>";
            echo $message;
        }

        // schreiben der Kontrolldatei
        $this->_writeResponseKontrolldatei($message);
    }

    /**
     * schreibt die Response XML Datei
     */
    private function _writeResponseKontrolldatei($__message){
        if(empty($this->_writeDebugFile))
            return;

        // Ausgabe in Datei
        $datum = date("Y_m_d_H_i_s");

        $fp = fopen('response_' . $datum . ".xml", 'w');
        fputs($fp, $__message);
        fclose($fp);

        return;
    }

    /**
     * Setzt den Flag zum schreiben der
     *
     * Kontroll Response Datei
     *
     * @param bool $__writeDebugFile
     * @return $this
     */
    public function setDebugFile($__writeDebugFile = false){
        $this->_writeDebugFile = $__writeDebugFile;

        return $this;
    }




} // end class

if($_POST['xml']){
    $confirm = new confirmBooking();

    $confirm
        ->setXmlData($_POST['xml']) // Übernahme XML Block
        ->start() // Verarbeitung des XML Block
        ->setDebugFile(true) // schreiben der Response Datei
        ->response(); // Bestätigungsmeldung
}
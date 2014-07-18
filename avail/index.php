<?php
/**
 * Beschreibung der Klasse
 *
 * Einlesen / update der Raten eines Hotels.
 * XML - Datensatz per 30.11.2012.
 *
 *
 *
 *
 * @date 03.12.12 10:56
 * @author Stephan Krauß
 */

class availRate{

    // Benutzerkennung und Passwort
    protected $UserName = null;
    protected $Password = null;

    // vorgegebene Benutzerkennung und Passwort
    protected $kontrolleUserName = 'wertxcde';
    protected $kontrollePassword = '12gtzt36';

    protected $flagAuth = false;

    // Produktionsdatenbanken
    private $_db_database = 'db1154036-hotels';
    private $_db_server = 'localhost';
    private $_db_user = 'db1154036-hotel';
    private $_db_password = 'HuhnHotelsHuhn';
    private $_db_connect = null;

    private $_db_database_groups = 'db1154036-noko';
    private $_db_server_groups = 'localhost';
    private $_db_user_groups = 'db1154036-noko';
    private $_db_password_groups = 'huhn9huhn';
    private $_db_connect_groups = null;
	
	private $mailAdresseStephan = "suppenterrine@gmail.com";
    private $_error_mail_from = 'info@guideberlin.com';

    // Testdatenbank
//    private $_db_database = 'db1154036-schnittstelle';
//    private $_db_server = 'localhost';
//    private $_db_user = 'db1154036-schni';
//    private $_db_password = 'schnittstelle';
//    private $_db_connect = null;
//
//    private $_db_database_groups = 'db1154036-schnittstelle';
//    private $_db_server_groups = 'localhost';
//    private $_db_user_groups = 'db1154036-schni';
//    private $_db_password_groups = 'schnittstelle';
//    private $_db_connect_groups = null;
//
//    private $_error_mail_adresse = 'suppenterrine@gmail.com';
//    private $_error_mail_from = 'info@guideberlin.com';


    private $_debugModus = false;
    private $_xmlReader = null;
    private $_echoToken = null;

    private $_hotelCode = null;
    private $_hotelId = null;
    private $_tagesDatum = null;
    private $_sicherungsdatei = null;

    private $_errors = array();
    private $_xmlErrorMessages = array();

    private $_furtherProcessing = false; // Flag weitere Verarbeitung

    /**
    * Verbindung zur Datenbank
    *
    *
    */
    public function __construct(){
        set_time_limit(0);

        $mysqli = @mysqli_connect($this->_db_server, $this->_db_user, $this->_db_password, $this->_db_database);

        if(!$mysqli)
        die('Fehler: Verbindung zur Datenbank nicht möglich');

        $this->_db_connect = $mysqli;
    }

    /**
     * sperrt die Tabelle der verfügbaren Rates
     *
     * @return
     */
    public function lockTable(){
        $sql = "lock tables tbl_ota_rates_availability";
        mysqli_query($this->_db_connect, $sql);

        return;
    }

    /**
     * entsperren der Tabelle
     * der verfügbaren Raten
     */
    public function unlockTable(){
        $sql = "unlock tables";
        mysqli_query($this->_db_connect, $sql);

        return;
    }

    /**
    * Setzt den Dateinamen der XML Datei
    * Erstellt den XML - Reader
    *
    * @param $__xmlFileName
    * @return void
    */
   public function setAvailDataFile($__xmlFileName){

       // bilden XML - Reader
       $xmlReader = new XMLReader();

       // öffen Datei
       $xmlReader->open($__xmlFileName);

       // lesen XML - Datei
       $this->_xmlReader = $xmlReader;

       return;
   }

    /**
     * Starten der Verarbeitung
     *
     */
    public function start(){
        // durchlaufen der Knoten
        $this->_iterateXmlNodes();

        return;
    }

    /**
     * Abspeichern der Sicherungsdatei 'avail ... '
     */
    private function _anlegenSicherungsdatei()
    {
        if(empty($this->_sicherungsdatei))
            return;

        $hotelCode = $this->_hotelCode;
        $tagesDatum = $this->_tagesDatum;

        // speichert die Sicherungsdatei mit Hotelcode und Datum ab
       $datei = "avail_".$hotelCode."_".$tagesDatum.'.xml';
       copy('HotelAvailNotif.xml',$datei);

        return;
    }

    /**
     * Sendet Response oder speichert Response
     *
     * @return void
     */
    public function getResponse(){
        $response = $this->_responseFileStart();

        if(count($this->_errors) > 0){

            // wandelt das Error Array in XML
            $response .= $this->_errorMessages();
            $response .= $this->_responseFileEnde();

            $datum = date("_Y_m_d_H_i_s");

            // speichern der Fehler XML
            $errorDatei = "error" . $datum . '.xml';
            $fp = fopen($errorDatei, 'w');
            fputs($fp, $response);
            fclose($fp);

            // speichern der Original XML
            $fehlerhafteXmlDatei = "eror_original_".$datum.'.xml';
            copy('HotelAvailNotif.xml', $fehlerhafteXmlDatei);

            // senden einer Fehler Mail
            $mailText = "http://avail.herden.de \n";
            $mailText .= "Ein Fehler ist aufgetreten ! \n";
            $mailText .= "Bitte Fehlerdatei '".$errorDatei."' kontrollieren. \n";
            $mailText .= "Datum: ".date("d.m.Y H:i:s")." \n";



            // sendet Mail an Herden
            mail($this->_error_mail_adresse, 'Fehler http://avail.herden.de',$mailText, "From: ".$this->_error_mail_from);
			
			// senden Mail an Handy Stephan
			mail($this->mailAdresseStephan, 'Fehler Schnittstelle Avail','Fehler Schnittstelle Avail. Datum: '.date("d.m.Y H:i:s"), "From: ".$this->_error_mail_from);
        }
        else{
            $response .= $this->_okMessage();
            $response .= $this->_responseFileEnde();
        }

        echo $response;
    }

    /**
    * Durchläuft die XML Knoten
    * Prüft die Codes der Knoten
    * Differenzierung der Aktionen
    *
    * @return void
    */
    private function _iterateXmlNodes(){

        while($this->_xmlReader->read()){

            // EchoToken
            if($this->_xmlReader->name == 'OTA_HotelAvailNotifRQ' and XMLReader::ELEMENT)
                $this->_echoToken = $this->_xmlReader->getAttribute('EchoToken');

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

            // wenn ein neues Hotel
            if($this->_xmlReader->name == 'AvailStatusMessages' and $this->_xmlReader->nodeType == XMLReader::ELEMENT){
                $this->_hotelCode = null;
                $this->_hotelId = null;

                $this->_furtherProcessing = true; // Flag zur Verarbeitung

                $hotelCode = $this->_xmlReader->getAttribute('HotelCode');

                // existiert das Hotel 'tbl_properties'
                $this->_checkExistHotelCode($hotelCode);

                // registrieren das ein Hotel ein update der Raten durchgeführt hat in 'tbl_schnittstelle'
                $this->_registerUpdateRatenEinesHotels($hotelCode);

                // Wenn Hotel existiert, dann eintragen der Raten eines Hotels
                if($this->_furtherProcessing){
                    $this->_hotelCode = $hotelCode;

                    // Raten des Hotels eintragen oder updaten
                    $this->_hotelRates();
                }
            }

        }

        return;
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
     * Registriert in der Archiv Tabelle, daß
     * ein Hotel die Raten erneuert hat.
     *
     * + Typ = 1, entspricht Ratenaktualisierung
     *
     * @param $__hotelCode
     * @return availRate
     */
    private function _registerUpdateRatenEinesHotels($__hotelCode){

        $mysqli = @mysqli_connect($this->_db_server_groups, $this->_db_user_groups, $this->_db_password_groups, $this->_db_database_groups);

        if(!$mysqli)
        die("Fehler: Verbindung zur Datenbank 'noko' nicht möglich");

        // $this->_db_connect_groups = $mysqli;


        $sql = "insert into tbl_schnittstelle set hotelCode = '".$__hotelCode."', typ = '1'";
        mysqli_query($mysqli, $sql);



        return $this;
    }

    /**
     * Prüft das vorhandensein eines Hotelcode.
     * Im Fehlerfall wird das Array '$this->_errors' ergänzt
     * HotelId wird ermittelt
     * Setzt Flag für weitere Verarbeitung
     *
     * @param $__hotelCode
     * @return
     */
    private function _checkExistHotelCode($__hotelCode){

        $sql = "select id from tbl_properties where property_code = '". $__hotelCode ."'";

        $anfrage = mysqli_query($this->_db_connect, $sql);
        $row = $this->_fetchAnfrageArray($anfrage); // erstellen Array

        if(count($row) != 1){
            $error = array();
            $error['Language'] = 'en-en';
            $error['Type'] = 3;
            $error['Code'] = 392;
            $error['Status'] = 'Unknown';
            $error['Tag'] = "OTA_HotelAvailNotifRQ@AvailStatusMessages";
            $error['ShortText'] = 'Unknown Hotel';
            $error['LongText'] = "Hotel code '".$__hotelCode."' is unknown";

            $this->_errors[] = $error;

            $this->_furtherProcessing = false;
        }
        else
            $this->_hotelId = $row[0]['id'];

        return;
    }

    /**
     * fetcht ein assoziatives Array
     * aus einer mysqli Anfrage
     *
     */
    private function _fetchAnfrageArray($__anfrage){
        $i=0;
        $ergebnis = array();

        while($row = $__anfrage->fetch_assoc()){
            $ergebnis[$i] = $row;

            $i++;
        }

        return $ergebnis;
    }

    /**
     * Eintragen oder Update der
     * Raten eines Hotels
     *
     */
    private function _hotelRates(){

        $rateHotel = array();

        while($this->_xmlReader->read()){

            // </AvailStatusMessages>
            // Abbruch der Schleife wenn Hotelblock beendet
            if($this->_xmlReader->name == 'AvailStatusMessages' and $this->_xmlReader->nodeType == XMLReader::END_ELEMENT){

                break;
            }

            // Verarbeitung der Rate !!!
            // </AvailStatusMessage>
            // updaten einer einzelnen !!! Rate eines Hotels
            if($this->_xmlReader->name == 'AvailStatusMessage' and $this->_xmlReader->nodeType == XMLReader::END_ELEMENT){

                // wenn kein Fehler dann updaten / insert der Werte
                if($this->_furtherProcessing){
                    // berechneZeitraum der Rate
                    $this->_berechneZeitraumDerRate($rateHotel['Start'], $rateHotel['End']);

                    // eintragen der Rate für den Zeitraum
                    $this->_updateRateHotel($rateHotel);

                }

                // Rate des Hotel leeren
                $rateHotel = array();

                // Flag Verarbeitung
                $this->_furtherProcessing = true;
            } // Ende der Verarbeitung


            // <AvailStatusMessage ...
            // lesen der einzelnen Rate
            // Notiz: Überprüfung Benutzer und Passwort einbauen

            if($this->_xmlReader->name == 'AvailStatusMessage' and $this->_xmlReader->nodeType == XMLReader::ELEMENT){
                $this->_furtherProcessing = true;

                $this->_rateId = null; // Raten ID
                $this->_categoryId = null; // Kategorie ID
                $this->_unixStart = null; // Startdatum
                $this->_unixEnd = null; // Enddatum

                // Booking Limit wenn vorhanden !!!
                $bookingLimit = $this->_xmlReader->getAttribute('BookingLimit');
                if( !($bookingLimit === null) and !($bookingLimit === '') )
                    $rateHotel['BookingLimit'] = $bookingLimit;
            }

            // <StatusApplicationControl ...
            // Startdatum / Enddatum / Kategorie / Rate / Wochentage
            if($this->_xmlReader->name == 'StatusApplicationControl' and $this->_xmlReader->nodeType == XMLReader::ELEMENT){

                // Startdatum / Enddatum
                $rateHotel['Start'] = $this->_xmlReader->getAttribute('Start');
                $rateHotel['End'] = $this->_xmlReader->getAttribute('End');

                // Kategorie
                $rateHotel['CategoryCode'] = $this->_xmlReader->getAttribute('InvTypeCode');
                // Kontrolle der Kategorie
                $this->_checkExistCategoryCode($rateHotel['CategoryCode']);

                // Rate
                $rateHotel['RateCode'] = $this->_xmlReader->getAttribute('RatePlanCode');
                // Kontrolle der Rate
                $this->_checkExistRateCode($rateHotel['RateCode']);

                // Wochentage an denen die Rate gültig ist
                if($this->_xmlReader->getAttribute('Mon'))
                   $rateHotel['Mon'] = $this->_xmlReader->getAttribute('Mon');
                if($this->_xmlReader->getAttribute('Tue'))
                   $rateHotel['Tue'] = $this->_xmlReader->getAttribute('Tue');
                if($this->_xmlReader->getAttribute('Weds'))
                   $rateHotel['Wed'] = $this->_xmlReader->getAttribute('Weds');
                if($this->_xmlReader->getAttribute('Thur'))
                   $rateHotel['Thu'] = $this->_xmlReader->getAttribute('Thur');
                if($this->_xmlReader->getAttribute('Fri'))
                   $rateHotel['Fri'] = $this->_xmlReader->getAttribute('Fri');
                if($this->_xmlReader->getAttribute('Sat'))
                   $rateHotel['Sat'] = $this->_xmlReader->getAttribute('Sat');
                if($this->_xmlReader->getAttribute('Sun'))
                   $rateHotel['Sun'] = $this->_xmlReader->getAttribute('Sun');
            }

            // <LengthOfStay ...
            // minimaler und maximaler Aufenthalt
            if($this->_xmlReader->name == 'LengthOfStay' and $this->_xmlReader->nodeType == XMLReader::ELEMENT){
                $typDerLaenge = $this->_xmlReader->getAttribute('MinMaxMessageType');

                if($typDerLaenge == 'SetMinLOS')
                    $rateHotel['min'] = $this->_xmlReader->getAttribute('Time');

                if($typDerLaenge == 'SetMaxLOS')
                    $rateHotel['max'] = $this->_xmlReader->getAttribute('Time');
            }
        }

        return;
    }

    /**
    * Eintragen einer Rate eines Hotels
    * für einen Start und Endzeitpunkt
    *
    *
    *
    *
    * @param array $__rate
    * @return void
    */
   private function _updateRateHotel(Array $__rate){


       for($i = $this->_unixStart; $i <= $this->_unixEnd; $i += 86400){

           $tagesDatum = date('Y-m-d', $i);
           $wochentag = date('D', $i);

           $this->_tagesDatum = $tagesDatum;

           // wenn im Zeitraum der Wochentag == 1
           if(array_key_exists($wochentag, $__rate) and $__rate[$wochentag] == '1'){

               // ist die Rate bereits vorhanden ?????
               $sql = "select count(datum) as anzahl from tbl_ota_rates_availability as anzahl where";
               $sql .= " hotel_code = '".$this->_hotelCode."'";
               $sql .= " and datum = '".$tagesDatum."'";
               $sql .= " and category_code = '".$__rate['CategoryCode']."'";
               $sql .= " and rate_code = '".$__rate['RateCode']."'";

               $ergebnis = mysqli_query($this->_db_connect, $sql);
               $result = $this->_fetchAnfrageArray($ergebnis);
               $anzahl = $result[0]['anzahl'];

               // löschen Datensatz wenn Anzahl > 1, automatische Korrektur !!!
               if($anzahl > 1){
                   $sql = 'delete from tbl_ota_rates_availability where ';
                   $sql .= " hotel_code = '".$this->_hotelCode."'";
                   $sql .= " and datum = '".$tagesDatum."'";
                   $sql .= " and category_code = '".$__rate['CategoryCode']."'";
                   $sql .= " and rate_code = '".$__rate['RateCode']."'";

                   $ergebnis = mysqli_query($this->_db_connect, $sql);
               }

               // neuen Datensatz anlegen
               if( ($anzahl == 0) or ($anzahl > 1)){

                   if(!array_key_exists('min', $__rate))
                       $__rate['min'] = 0;

                   if(!array_key_exists('max', $__rate))
                       $__rate['max'] = 0;

                   $sql = "insert into tbl_ota_rates_availability (datum, hotel_code, roomlimit, min_stay, max_stay, rate_code, category_code, rates_config_id,  property_id) values ";
                   $sql .= "('".$tagesDatum."',";
                   $sql .= "'".$this->_hotelCode."',";
                   $sql .= "'". $__rate['BookingLimit'] ."',";

                   $sql .= "'".$__rate['min']."',";
                   $sql .= "'".$__rate['max']."',";

                   $sql .= "'".$__rate['RateCode']."',";
                   $sql .= "'".$__rate['CategoryCode']."',";
                   $sql .= "'".$this->_rateId."',";
                   $sql .= "'".$this->_hotelId."')";

                    if(!empty($this->_debugModus)){
                         echo "insert: <br>";
                         echo $sql;
                         echo "<hr>";
                    }

                   mysqli_query($this->_db_connect, $sql);

                   // anlegen Sicherungsdatei
                   $this->_anlegenSicherungsdatei();
               }
               // Datensatz updaten
               elseif($anzahl == 1){
                   $kontrolle = 0;

                   $sql = "UPDATE tbl_ota_rates_availability set";

                   // Veränderung der Elemente des Datensatzes
                   if(array_key_exists('BookingLimit', $__rate)){
                       $sql .= " roomlimit = '". $__rate['BookingLimit'] ."',";
                       $kontrolle++;
                   }


                   if(array_key_exists('min', $__rate)){
                       $sql .= " min_stay = '". $__rate['min'] ."',";
                       $kontrolle++;
                   }


                   if(array_key_exists('max', $__rate)){
                       $sql .= " max_stay = '". $__rate['max'] ."',";
                       $kontrolle++;
                   }

                   if($kontrolle == 0){
                       $error = array();
                       $error['Language'] = 'en-en';
                       $error['Type'] = 3;
                       $error['Code'] = 394;
                       $error['Status'] = 'Unknown';
                       $error['Tag'] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
                       $error['ShortText'] = 'no update';
                       $error['LongText'] = 'no elements to update';

                        $this->_errors[] = $error;

                       continue;
                   }


                   $sql = substr($sql,0,-1);

                   $sql .= " where";
                   $sql .= " hotel_code = '".$this->_hotelCode."'";
                   $sql .= " and rate_code = '".$__rate['RateCode']."'";
                   $sql .= " and category_code = '".$__rate['CategoryCode']."'";
                   $sql .= " and datum = '".$tagesDatum."'";

                   if(!empty($this->_debugModus)){
                        echo "update: <br>";
                        echo $sql;
                        echo "<hr>";
                   }

                   mysqli_query($this->_db_connect, $sql);

                   // anlegen Sicherungsdatei
                   $this->_anlegenSicherungsdatei();
               }

               // Fehlermeldung wenn Anzahl > 1
               if($anzahl > 1){

                   $errorMessage  = " Datum = ".$tagesDatum;
                   $errorMessage .= " hotel_code = ".$this->_hotelCode;
                   $errorMessage .= " category_code = ".$__rate['CategoryCode'];
                   $errorMessage .= " rate_code = ".$__rate['RateCode'];

                   $error = array();
                   $error['Language'] = 'en-en';
                   $error['Type'] = 3;
                   $error['Code'] = 394;
                   $error['Status'] = 'Unknown';
                   $error['Tag'] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
                   $error['ShortText'] = 'too many records: '.$errorMessage;
                   $error['LongText'] = 'rates of more than one record exists';

                   $this->_errors[] = $error;
               }
           }
           // Wenn im Zeitraum der Wochentag gesperrt ist
           elseif(array_key_exists($wochentag, $__rate) and $__rate[$wochentag] == '0'){
               $sql = "delete from tbl_ota_rates_availability";
               $sql .= " where hotel_code = '".$this->_hotelCode."'";
               $sql .= " and datum = '".$tagesDatum."'";
               $sql .= " and category_code = '".$__rate['CategoryCode']."'";
               $sql .= " and rate_code = '".$__rate['RateCode']."'";
               mysqli_query($this->_db_connect, $sql);

               // anlegen Sicherungsdatei
               $this->_anlegenSicherungsdatei();
           }

       } // Ende Schleife des Zeitraumes


       return;
   }

    /**
     * Umwandeln des Start und Enddatum in ein Unix Datum
     *
     * @param $__startDatum
     * @param $__endDatum
     * @return
     */
    private function _berechneZeitraumDerRate($__startDatum, $__endDatum){
        $teileStartDatum = explode('-', $__startDatum);
        $this->_unixStart = mktime(0,0,1,$teileStartDatum[1],$teileStartDatum[2],$teileStartDatum[0]);

        $teileEndDatum = explode('-', $__endDatum);
        $this->_unixEnd = mktime(0,0,1,$teileEndDatum[1],$teileEndDatum[2],$teileEndDatum[0]);

        return;
    }

    /**
     * Kontrolliert ob eine Rate in einem Hotel existiert.
     * Bestimmt die Raten ID.
     * Schreibt eine Fehlermeldung wenn Rate nicht vorhanden ist.
     * Setzt Flag für weitere Verarbeitung
     *
     * @param $__rateCode
     * @return void
     */
    private function _checkExistRateCode($__rateCode){
        $sql = "select id from tbl_ota_rates_config where rate_code = '".$__rateCode."' and properties_id = '".$this->_hotelId."'";
        $anfrage = mysqli_query($this->_db_connect, $sql);
        // $row = mysqli_fetch_all($anfrage, MYSQLI_ASSOC);
        $row = $this->_fetchAnfrageArray($anfrage);

        if(count($row) != 1){
            $error = array();
            $error['Language'] = 'en-en';
            $error['Type'] = 3;
            $error['Code'] = 394;
            $error['Status'] = 'Unknown';
            $error['Tag'] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
            $error['ShortText'] = 'Unknown Rate';
            $error['LongText'] = "Rate code'".$__rateCode."' is unknown for hotel '".$this->_hotelCode."'";

            $this->_errors[] = $error;

            $this->_furtherProcessing = false;
        }
        else
            $this->_rateId = $row[0]['id'];

        return;
    }

    /**
     * Kontrolliert ob eine Kategorie in einem Hotel existiert.
     * Bestimmt die Kategorie ID.
     * Schreibt eine Fehlermeldung wenn Kategorie nicht vorhanden ist.
     * Setzt Flag für weitere Verarbeitung
     *
     * @param $__categoryCode
     * @return
     */
    private function _checkExistCategoryCode($__categoryCode){
        $sql = "select id from tbl_categories where categorie_code = '".$__categoryCode."' and properties_id = '".$this->_hotelId."'";
        $anfrage = mysqli_query($this->_db_connect, $sql);
        // $row = mysqli_fetch_all($anfrage, MYSQLI_ASSOC);
        $row = $this->_fetchAnfrageArray($anfrage);

        if(count($row) != 1){
            $error = array();
            $error['Language'] = 'en-en';
            $error['Type'] = 3;
            $error['Code'] = 394;
            $error['Status'] = 'Unknown';
            $error['Tag'] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
            $error['ShortText'] = 'Unknown Category';
            $error['LongText'] = "Category code '".$__categoryCode."' is unknown for hotel '".$this->_hotelCode."'";

            $this->_errors[] = $error;

            $this->_furtherProcessing = false;
        }
        else
            $this->_categoryId = $row[0]['id'];


        return;
    }

    /**
    * setzen des Debug Modus
    *
    * @param $__modus
    * @return
    */
   public function setDebugModus($__modus){
       $this->_debugModus = $__modus;

       return;
   }

    /**
     * Setzt die Sicherungsdatei
     *
     * @param bool $sicherungsdatei
     */
    public function setSicherungsdatei($sicherungsdatei = false)
    {
        $this->_sicherungsdatei = $sicherungsdatei;
    }


    /**
     * Generiert den Start - Teil der Response
     *
     * @return string
     */
    private function _responseFileStart(){
        $time = time();
        $datum = date("Y-m-d",$time);
        $zeit = date("H:i:s",$time);
        $zeitzone = date("O",$time);

        $response = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $response .= "<OTA_HotelAvailNotifRS xmlns='http://www.opentravel.org/OTA/2003/05'";
        $response .= " TimeStamp='".$datum."T".$zeit.$zeitzone."'";
        $response .= " PrimaryLangID='de-DE'";
        $response .= " Version='4.000'";
        $response .= " EchoToken='".$this->_echoToken."'";
        $response .= ">\n";

        return $response;
    }

    /**
     * Wenn Transfer O.K.
     *
     * @return string
     */
    private function _okMessage(){

        return "<success/>\n";
    }

    /**
     * Ende des response File
     *
     * @return string
     */
    private function _responseFileEnde(){
        $response = "</OTA_HotelAvailNotifRS>\n";

        return $response;
    }

    /**
     * Wandelt aus den Error - Array die Fehlermweldungen in XML um
     *
     * @return
     */
    private function _errorMessages(){

        if(!empty($this->_debugModus)){
            for($i=0; $i < count($this->_errors); $i++){
               echo "Fehler !!! <br>";
               echo "Code: ". $this->_errors[$i]['Code']."<br>";
               echo "Status: ". $this->_errors[$i]['Status'] ."<br>";
               echo "Tag: ". $this->_errors[$i]['Tag'] ."<br>";
               echo "ShortText: ". $this->_errors[$i]['ShortText'] ."<br>";
               echo "<hr>";
            }
        }

        $response = "<Errors>\n";

        for($i=0; $i<count($this->_errors); $i++){
            $response .= "<Error";

            $response .= " Language='". $this->_errors[$i]['Language'] ."'";
            $response .= " Type='". $this->_errors[$i]['Type'] ."'";
            $response .= " Code='". $this->_errors[$i]['Code'] ."'";
            $response .= " Status='". $this->_errors[$i]['Status'] ."'";
            $response .= " Tag='". $this->_errors[$i]['Tag'] ."'";
            $response .= " ShortText='". $this->_errors[$i]['ShortText'] ."'";
            $response .= ">\n";

            $response .= $this->_errors[$i]['LongText']."\n";
            $response .= "</Error>\n";
        }

        $response .= "</Errors>\n";

        return $response;
    }


} // end class



/***** Verarbeitung **************/


if(array_key_exists('xml', $_POST)){
    $xmlEinlesen = new availRate();
    $xmlEinlesen->setDebugModus(false);

    // schreiben Übernahme Datei
    $fp = fopen('HotelAvailNotif.xml','w');
    fputs($fp, $_POST['xml']);
    fclose($fp);

    if(file_exists('HotelAvailNotif.xml')){
        $xmlEinlesen->lockTable();
        $xmlEinlesen->setAvailDataFile('HotelAvailNotif.xml');
        $xmlEinlesen->setDebugModus(false);
        $xmlEinlesen->setSicherungsdatei(false);
        $xmlEinlesen->start(); // starten der Verarbeitung
        $xmlEinlesen->unlockTable();
        $xmlEinlesen->getResponse();
    }
    else{
        $response = "<errors>\n";
        $response .= "<error>\n";
        $response ."Server is not ready\n";
        $response .= "</error>\n";
        $response .= "</errors>";

        echo $response;
    }
}
else{
    $response = "<errors>\n";
    $response .= "<error>\n";
    $response ."Missing data\n";
    $response .= "</error>\n";
    $response .= "</errors>";

    echo $response;
}

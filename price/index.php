<?php
/**
 * Einlesen der Preise eines Hotels
 *
 * @author Stephan.Krauss
 * @date 13.05.13
 * @file index.php
 * @package schnittstelle
 */

class price
{
    // Benutzerkennung und Passwort
    protected $UserName = null;
    protected $Password = null;

    // vorgegebene Benutzerkennung und Passwort
    protected $kontrolleUserName = 'wertxcde';
    protected $kontrollePassword = '12gtzt36';

    protected $flagAuth = false;

    // Produktionsdatenbanken
    private $_db_connect = null;
    private $_db_server = 'localhost';
    private $_db_user = 'db1154036-hotel';
    private $_db_password = 'HuhnHotelsHuhn';
    private $_db_database = 'db1154036-hotels';

    private $_db_groups_connect = null;
    private $_db_groups_server = 'localhost';
    private $_db_groups_user = 'db1154036-noko';
    private $_db_groups_password = 'huhn9huhn';
    private $_db_groups_database = 'db1154036-noko';

    // Testdatenbank
//    private $_db_connect = null;
//    private $_db_server = 'localhost';
//    private $_db_user = 'db1154036-schni';
//    private $_db_password = 'schnittstelle';
//    private $_db_database = 'db1154036-schnittstelle';
//
//    private $_db_groups_connect = null;
//    private $_db_groups_server = 'localhost';
//    private $_db_groups_user = 'db1154036-schni';
//    private $_db_groups_password = 'schnittstelle';
//    private $_db_groups_database = 'db1154036-schnittstelle';

    // Mail Adressen
    private $_error_mail_adresse = 'kuehn.sebastian@gamil.com';
    private $_error_mail_from = 'kuehn.sebastian@gamil.com';
    private $mailAdresseStephan = "kuehn.sebastian@gamil.com";

    private $_XMLReader = null;
    private $_furtherProcessing = true;

    private $_echoToken = null;
    private $_errors = array();

    private $_hotelCode = null;
    private $_hotelId = null;

    private $_categoryCode = null;
    private $_categoryId = null;

    private $_rateCode = null;
    private $_rateId = null;

    private $_startDatum = null;
    private $_startDatumUnix = null;
    private $_endDatum = null;
    private $_endDatumUnix = null;

    private $_tagInSekunden = 86400; // Tag in Sekunden

    private $_tageDerRate = array(); // Tage des Zeitraumes der Rate
    private $_rateWochentag = array();

    private $_debugModus = false; // Debug Modus
    private $_sicherungsdatei = false; // Soll eine Sicherungsdatei angelegt werden

    /**
     * Verbindung zur Datenbank und
     * initialisieren des XMLReader !
     *
     */
    public function __construct ()
    {
        set_time_limit(0);
        $mysqli = mysqli_connect($this->_db_server, $this->_db_user, $this->_db_password, $this->_db_database);

        if(!$mysqli) {

            $error = array(
                'Language' => 'en-en',
                'Type' => 3,
                'Code' => 448,
                'Status' => 'Unknown',
                'Tag' => "OTA_HotelRatePlanNotifRQ",
                'ShortText' => 'System Error Database',
                'LongText' => "No connect to database"
            );

            $this->_errors[] = $error;

            $this->getResponse();
            exit();
        }

        $this->_db_connect = $mysqli;

        $mysqli_groups = mysqli_connect($this->_db_groups_server, $this->_db_groups_user, $this->_db_groups_password, $this->_db_groups_database);

        if(!$mysqli_groups) {
            $error = array(
                'Language' => 'en-en',
                'Type' => 3,
                'Code' => 448,
                'Status' => 'Unknown',
                'Tag' => "OTA_HotelRatePlanNotifRQ",
                'ShortText' => 'System Error Database',
                'LongText' => "No connect to database"
            );

            $this->_errors[] = $error;

            $this->getResponse();
            exit();
        }

        $this->_db_groups_connect = $mysqli_groups;

        $this->_XMLReader = new XMLReader();

        return;
    }

    /**
     * Sperren der Tabelle 'ota_prices'
     *
     * @return
     */
    public function lockTable ()
    {
        $sql = "lock tables 'tbl_ota_prices'";
        mysqli_query($this->_db_connect, $sql);

        return;
    }

    /**
     * Entsperren der Tabelle 'ota_prices'
     *
     * @return void
     */
    public function unlockTable ()
    {
        $sql = "unlock tables";
        mysqli_query($this->_db_connect, $sql);
    }

    /**
     * Schaltet Debug Modus
     *
     * @param $__debug
     * @return
     */
    public function setDebugModus ($__debug)
    {
        $this->_debugModus = $__debug;

        return;
    }

    public function einlesenXmlDatei ($__dateiName)
    {

        $this->_XMLReader->open($__dateiName);
        while ($this->_XMLReader->read()) {
            $this->_furtherProcessing = true;

            // EchoToken
            if($this->_XMLReader->name == 'OTA_HotelRatePlanNotifRQ' and XMLReader::ELEMENT) {
                $this->_echoToken = $this->_XMLReader->getAttribute('EchoToken');
            }

            // Notiz: Benutzerkennung und Passwort
            // Benutzerkennung und Passwort
            if( $this->_XMLReader->name == 'UserName' or $this->_XMLReader->name == 'Password' ){

                // findet Startelement
                if($this->_XMLReader->nodeType == XMLReader::ELEMENT){
                    $nameAuth = $this->_XMLReader->name;
                    $wertAuth = $this->_XMLReader->readString();

                    $this->checkBenutzerUndPasswort($nameAuth,$wertAuth);
                }
            }

            // <RateAmountMessages
            // wenn ein neues Hotel
            if( ($this->_XMLReader->name == 'RateAmountMessages') and ($this->_XMLReader->nodeType == XMLReader::ELEMENT) ) {
                $this->_hotelCode = null;
                $this->_hotelId = null;

                $this->_furtherProcessing = true;

                $hotelCode = $this->_XMLReader->getAttribute('HotelCode');

                // Kontrolle:  Registrieren der Preisinformation in Protokolltabelle
                $this->_registrierenDerPreisinformationHotel($hotelCode);

                $this->_checkExistHotelCode($hotelCode);

                // Wenn Hotel existiert, dann eintragen der Raten eines Hotels
                if($this->_furtherProcessing) {
                    $this->_hotelRatesPrices();
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
     * Steuert das anlegen einer Sicherungsdatei
     *
     * @param bool $sicherungsdatei
     */
    public function setSicherungsdateiAnlegen($sicherungsdatei = false)
    {
        $this->_sicherungsdatei = $sicherungsdatei;
    }

    /**
     * Anlegen einer Sicherungsdatei der Preise
     *
     * + für ein Hotel
     * + für ein Datum
     *
     * @param $tagesdatum
     */
    private function _sicherungsDateiAnlegen($tagesdatum)
    {
        if(empty($this->_sicherungsdatei))
            return;

        $hotelCode = $this->_hotelCode;

        $datei = "price_".$hotelCode."_".$tagesdatum.".xml";

        // speichert ankommende Datei mit  Zeitstempel ab
        copy('HotelRatePlanNotif.xml', $datei);

        return;
    }

    /**
     * Registriert in der Tabelle 'tbl_schnittstelle_price'
     * den Hotelcode. Datum und Zeit wird automatisch angelegt.
     *
     * @param $hotelCode
     */
    private function _registrierenDerPreisinformationHotel ($hotelCode)
    {

        $sql = "insert into tbl_schnittstelle_price set hotelCode = '" . $hotelCode . "'";
        mysqli_query($this->_db_groups_connect, $sql);

        return;
    }

    /**
     * Aufbereiten der Preise einer Rate
     *
     * @return void
     */
    private function _hotelRatesPrices ()
    {
        while ($this->_XMLReader->read()) {

            // </RateAmountMessages ...
            // Abbruch der Schleife wenn Raten des Hotel beendet ist
            if($this->_XMLReader->name == 'RateAmountMessages' and $this->_XMLReader->nodeType == XMLReader::END_ELEMENT) {

                break;
            }

            // Notiz: Einbau Kontrolle Benutzerkennung und Passwort

            // <RateAmountMessage ...
            // Auswertung einer Rate eines Hotels, konstante Werte
            if($this->_XMLReader->name == 'RateAmountMessage' and $this->_XMLReader->nodeType == XMLReader::ELEMENT) {
                $this->_furtherProcessing = true;

                $this->_categoryCode = null;
                $this->_categoryId = null;
                $this->_rateCode = null;
                $this->_rateId = null;

            }

            // </RateAmountMessage ...
            // Ende der Rate, alle Werte werden gelöscht
            if($this->_XMLReader->name == 'RateAmountMessage' and $this->_XMLReader->nodeType == XMLReader::END_ELEMENT) {

                // eintragen der Rate wenn O.K. !!!!!!!!!!!!!
                if($this->_furtherProcessing) {
                    $this->_eintragenRate();
                } // eintragen der Rate

                $this->_categoryCode = null;
                $this->_categoryId = null;
                $this->_rateCode = null;
                $this->_rateId = null;

                $this->_rateWochentag = array();
                $this->_tageDerRate = array();

            }

            /*** Bereich der optionalen Werte einer Rate ***/

            // Zeitraum der Rate
            if($this->_XMLReader->name == 'StatusApplicationControl' and $this->_XMLReader->nodeType == XMLReader::ELEMENT and $this->_furtherProcessing) {

                // existiert die Kategorie für das Hotel
                $categoryCode = $this->_XMLReader->getAttribute('InvTypeCode');
                $this->_checkExistCategoryCode($categoryCode);

                // existiert die Rate für die Kategorie des Hotels
                if($this->_furtherProcessing) {
                    $rateCode = $this->_XMLReader->getAttribute('RatePlanCode');
                    $this->_checkExistRateCode($rateCode);
                }

                // ermitteln Start und End-Datum
                $this->_startDatum = null;
                $this->_startDatumUnix = null;
                $this->_endDatum = null;
                $this->_endDatumUnix = null;
                $this->_rateWochentag = array();
                $this->_tageDerRate = array();

                $this->_startDatum = $this->_XMLReader->getAttribute('Start');
                $this->_endDatum = $this->_XMLReader->getAttribute('End');

                // umwandeln in Sekunden
                $this->_startDatumUnix = $this->_umwandelDatumInUnix($this->_startDatum);
                $this->_endDatumUnix = $this->_umwandelDatumInUnix($this->_endDatum);

                // Wochentage
                $this->_restriktionWochentage();

                // Zeitraum des Preises der Rate
                $this->_ermittlungZeitraum();
            }

            // Anreisetage
            if($this->_XMLReader->name == 'ArrivalDaysOfWeek' and $this->_XMLReader->nodeType == XMLReader::ELEMENT  and $this->_furtherProcessing) {
                $this->_anreiseUndAbreiseTage('Arrival');
            }

            // Abreisetage
            if($this->_XMLReader->name == 'DepartureDaysOfWeek' and $this->_XMLReader->nodeType == XMLReader::ELEMENT  and $this->_furtherProcessing) {
                $this->_anreiseUndAbreiseTage('Departure');
            }

            // Boardtype, Achtung bislang nur ein Boardtyp möglich
//            if($this->_XMLReader->name == 'BoardType' and $this->_XMLReader->nodeType == XMLReader::ELEMENT  and $this->_furtherProcessing)
//                $this->_boardTypDerRate();

            // Kontrolle Preis
            if($this->_XMLReader->name == 'Rate' and $this->_furtherProcessing and $this->_XMLReader->nodeType == XMLReader::ELEMENT) {
                $this->_preisDerRate();
            }

            // früheste Freigabe Buchung in Tage
            if($this->_XMLReader->name == 'ReleaseFrom' and $this->_furtherProcessing) {
                $this->_freigebenBuchung('ReleaseFrom');
            }

            // späteste Freigabe der Buchung, Stornofrist
            if($this->_XMLReader->name == 'ReleaseTo' and $this->_furtherProcessing) {
                $this->_freigebenBuchung('ReleaseTo');
            }

        }

        return;
    }

    /**
     * Stellt die Kontrolle des Schleifendurchlaufes
     * am
     *
     * @return
     */
    private function _kontrollMessage ()
    {

        if($this->_debugModus) {
            if($this->_XMLReader->nodeType == XMLReader::ELEMENT) {
                echo $this->_XMLReader->name . "<br>";
                if($this->_furtherProcessing) {
                    echo "Kontrolle: O.K. <br>";
                } else {
                    echo "Alles Mist <br>";
                }

                echo '<hr>';
            }
        }

        return;
    }

    /**
     * Speichert die frühest mögliche und späteste Buchung.
     * 'release_from', Standard ist 0 !
     *
     * @return void
     */
    private function _freigebenBuchung ($__freigabeTyp)
    {
        $time = null;
        $time = $this->_XMLReader->getAttribute('Time');

        for($i = 0; $i < count($this->_tageDerRate); $i++) {
            if($__freigabeTyp == 'ReleaseFrom') {
                $this->_tageDerRate[ $i ][ 'release_from' ] = 0;
            }
            if($__freigabeTyp == 'ReleaseTo') {
                $this->_tageDerRate[ $i ][ 'release_to' ] = $time;
            }
        }

        return;
    }

    /**
     * Trägt die Rate entsprechend
     * Array '$_tageDerRate'
     *
     * @return
     */
    private function _eintragenRate ()
    {
        try{

            for($i = 0; $i < count($this->_tageDerRate); $i++) {

                // aufbereiten Daten
                $data = $this->_tageDerRate[$i];

                $insert = array();
                $update = array();

                // Anreise
                if(array_key_exists('arrival', $data)) {
                    if($data[ 'arrival' ] == true) {
                        $insert[ 'allowed_arrival' ] = 1;
                        $update[ 'allowed_arrival' ] = 1;
                    } else {
                        $insert[ 'allowed_arrival' ] = 0;
                        $update[ 'allowed_arrival' ] = 0;
                    }
                }

                // Abreise
                if(array_key_exists('departure', $data)) {
                    if($data[ 'departure' ] == true) {
                        $insert[ 'allowed_departure' ] = 1;
                        $update[ 'allowed_departure' ] = 1;
                    } else {
                        $insert[ 'allowed_departure' ] = 0;
                        $update[ 'allowed_departure' ] = 0;
                    }

                }

                // Preis
                if(array_key_exists('amount', $data)) {
                    $insert[ 'amount' ] = $data[ 'amount' ];
                    $insert[ 'pricePerPerson' ] = $data[ 'pricePerPerson' ];
                    $update[ 'amount' ] = $data[ 'amount' ];
                    $update[ 'pricePerPerson' ] = $data[ 'pricePerPerson' ];
                }

                // gültig von
                if(array_key_exists('release_from', $data)) {
                    $insert[ 'release_from' ] = $data[ 'release_from' ];
                    $update[ 'release_from' ] = $data[ 'release_from' ];
                }

                // gültig bis
                if(array_key_exists('release_to', $data)) {
                    $insert[ 'release_to' ] = $data[ 'release_to' ];
                    $update[ 'release_to' ] = $data[ 'release_to' ];
                }

                // Rate Code
                $insert[ 'rates_config_id' ] = $this->_rateId;
                $update[ 'rates_config_id' ] = $this->_rateId;

                $insert[ 'hotel_code' ] = $this->_hotelCode;
                $insert[ 'category_code' ] = $this->_categoryCode;
                $insert[ 'rate_code' ] = $this->_rateCode;
                $insert[ 'datum' ] = $data[ 'datum' ];

                $spalten = '';
                $spaltenInhalte = '';
                foreach($insert as $key => $value) {
                    $spalten .= $key . ",";
                    $spaltenInhalte .= "'" . $value . "',";
                }

                $updateSpalten = '';
                foreach($update as $key => $value) {
                    $updateSpalten .= $key . " = '" . $value . "',";
                }



                // SQL löschen Preis
                $sqlDelete = "delete from tbl_ota_prices where";
                $sqlDelete .= " hotel_code = '".$this->_hotelCode."'";
                $sqlDelete .= " and datum = '".$data[ 'datum' ]."'";
                $sqlDelete .= " and rate_code = '".$this->_rateCode."'";

                // löschen in 'tbl_ota_prices'
                if(!mysqli_query($this->_db_connect, $sqlDelete)){
                    $error = array(
                       'Language' => 'en-en',
                       'Type' => 3,
                       'Code' => 448,
                       'Status' => 'Unknown',
                       'Tag' => "OTA_HotelRatePlanNotifRQ",
                       'ShortText' => "System Error Table 'tbl_ota_prices' ",
                       'LongText' => "Error insert / update table 'tbl_ota_prices' "
                   );
                }


                // SQL eintragen Preis
                $sqlInsert = 'insert into tbl_ota_prices(';
                $spalten = substr($spalten, 0, -1);
                $sqlInsert .= $spalten . ") VALUES (";
                $spaltenInhalte = substr($spaltenInhalte, 0, -1);
                $sqlInsert .= $spaltenInhalte . ")";

                 // eintragen in 'tbl_ota_prices'
                if(!mysqli_query($this->_db_connect, $sqlInsert)){
                    $error = array(
                       'Language' => 'en-en',
                       'Type' => 3,
                       'Code' => 448,
                       'Status' => 'Unknown',
                       'Tag' => "OTA_HotelRatePlanNotifRQ",
                       'ShortText' => "System Error Table 'tbl_ota_prices' ",
                       'LongText' => "Error insert / update table 'tbl_ota_prices' "
                   );
                }

                // Debugging Mode
                if(!empty($this->_debugModus)) {
                    echo $sqlInsert."<br>";
                    echo $sqlDelete."<br>";
                    echo "<hr>";
                }

                // anlegen Sicherungsdatei
                $this->_sicherungsDateiAnlegen($data['datum']);
            }
        }
        catch(Exception $e){

            $bodyText = "Ein Fehler ist aufgetreten! \n";
            $bodyText .= "Datum: ".date("d.m.Y H:i:s")."\n";
            $bodyText .= "Query: \n";
            $bodyText .= $sqlDelete." \n";
            $bodyText .= $sqlInsert." \n";
            $bodyText .= "Datei: ".$e->getFile()."\n";
            $bodyText .= "Zeile: ".$e->getLine()."\n";
            $bodyText .= "Code: ".$e->getCode()."\n";
            $bodyText .= "Message: ".$e->getMessage()."\n\n";
            $bodyText .= $e->getTrace();

            mail($this->_error_mail_adresse,'Fehler http://price.herden.de', $bodyText, $this->_error_mail_from);
            mail($this->mailAdresseStephan, 'Fehler Schnittstelle Price','Fehler Schnittstelle Price. Datum: '.date("d.m.Y H:i:s"), "From: ".$this->_error_mail_from);
        }
    } // Ende Function

    /**
     * Ermitteln des Preises einer Rate.
     * Optional der Preis.
     * Optional Personenpreis oder Gruppenpreis
     * Optional Währung
     *
     * Währung noch nicht eingebaut
     *
     * @return
     */
    private function _preisDerRate ()
    {
        $perPerson = null;
        $amount = null;
        // $currencyCode = null;

        $perPerson = $this->_XMLReader->getAttribute('PerPerson');
        $amount = $this->_XMLReader->getAttribute('Amount');
        // ´´$currencyCode = $this->_XMLReader->getAttribute('CurrencyCode');

        for($i = 0; $i < count($this->_tageDerRate); $i++) {

            // Preis
            if($amount) {
                $this->_tageDerRate[ $i ][ 'amount' ] = $amount;
            }

            // wenn keine Angabe
            if(!$perPerson) {
                $this->_tageDerRate[ $i ][ 'pricePerPerson' ] = 'true';
            } // wenn Personenpreis
            elseif($perPerson == 'true') {
                $this->_tageDerRate[ $i ][ 'pricePerPerson' ] = 'true';
            } // wenn Gruppenpreis
            elseif($perPerson == 'false') {
                $this->_tageDerRate[ $i ][ 'pricePerPerson' ] = 'false';
            }

            // Währung nicht implementiert
            // if($currencyCode)

        }

        return;
    }

    /**
     * Wenn vorhanden wird der Boardtyp zur
     * Rate hinzugefügt. Es wird nur ein Boardtyp
     * berücksichtigt.
     *
     * @return
     */
    private function _boardTypDerRate ()
    {
        $boardTyp = $this->_XMLReader->getAttribute('code');

        for($i = 0; $i < count($this->_tageDerRate); $i++) {
            $this->_tageDerRate[ $i ][ 'board_type' ] = $boardTyp;
        }

        return;
    }

    /**
     * Ermittelt ob der betreffende Tag ein
     * Abreise oder Anreisetag ist.
     *
     * @param $__restrictionsTyp
     * @return void
     */
    private function _anreiseUndAbreiseTage ($__restrictionsTyp)
    {
        // Wochentagsbezeichnung der Attribute
        $wochentage = array( 'Mon', 'Tue', 'Weds', 'Thur', 'Fri', 'Sat', 'Sun' );
        $anreise = array();
        $abreise = array();

        foreach($wochentage as $wochentag) {
            $anreiseAbreiseTag = $this->_XMLReader->getAttribute($wochentag);

            $neueWochentagBezeichnung = substr($wochentag, 0, 3);

            if($anreiseAbreiseTag == null or $anreiseAbreiseTag == 1) {
                if($__restrictionsTyp == 'Arrival') {
                    $anreise[ $neueWochentagBezeichnung ] = 1;
                } else {
                    $abreise[ $neueWochentagBezeichnung ] = 1;
                }
            } else {
                if($__restrictionsTyp == 'Arrival') {
                    $anreise[ $neueWochentagBezeichnung ] = 0;
                } else {
                    $abreise[ $neueWochentagBezeichnung ] = 0;
                }
            }

        }

        for($i = 0; $i < count($this->_tageDerRate); $i++) {

            $wochentag = $this->_tageDerRate[ $i ][ 'wochentag' ];

            // Ankunftstage
            if($__restrictionsTyp == 'Arrival') {
                if($anreise[ $wochentag ] == 1) {
                    $this->_tageDerRate[ $i ][ 'arrival' ] = true;
                } else {
                    $this->_tageDerRate[ $i ][ 'arrival' ] = false;
                }
            } // Abfahrtstag
            else {
                if($abreise[ $wochentag ] == 1) {
                    $this->_tageDerRate[ $i ][ 'departure' ] = true;
                } else {
                    $this->_tageDerRate[ $i ][ 'departure' ] = false;
                }
            }
        }

        return;
    }

    /**
     * Bestimmt die Restriktionen der Rate
     * bezüglich des Wochentages
     *
     * @return
     */
    private function _restriktionWochentage ()
    {

        $wochentage = array( 'Mon', 'Tue', 'Weds', 'Thur', 'Fri', 'Sat', 'Sun' );

        foreach($wochentage as $wochentag) {
            $restriktionWochentag = $this->_XMLReader->getAttribute($wochentag);
            $neueWochentagBezeichnung = substr($wochentag, 0, 3);

            if($restriktionWochentag == null or $restriktionWochentag == 1) {
                $this->_rateWochentag[ $neueWochentagBezeichnung ] = 1;
            } else {
                $this->_rateWochentag[ $neueWochentagBezeichnung ] = 0;
            }
        }

        return;
    }

    private function _ermittlungZeitraum ()
    {

        $j = 0;
        for($i = $this->_startDatumUnix; $i <= $this->_endDatumUnix; $i += $this->_tagInSekunden) {

            $wochentag = date("D", $i);
            $this->_tageDerRate[ $j ][ 'wochentag' ] = $wochentag;

            if((array_key_exists($wochentag, $this->_rateWochentag) and $this->_rateWochentag[ $wochentag ] == 1)) {
                $this->_tageDerRate[ $j ][ 'aktiv' ] = true;
            } elseif((array_key_exists(
                $wochentag,
                $this->_rateWochentag
            ) and $this->_rateWochentag[ $wochentag ] == 0)
            ) {
                $this->_tageDerRate[ $j ][ 'aktiv' ] = false;
            } else {
                $this->_tageDerRate[ $j ][ 'aktiv' ] = true;
            }

            $this->_tageDerRate[ $j ][ 'datum' ] = date("Y-m-d", $i);

            $j++;
        }

        return;
    }

    /**
     * Umwandeln des Start und Enddatum in ein Unix Datum
     *
     * @param $__datum
     * @return int
     */
    private function _umwandelDatumInUnix ($__datum)
    {
        $teileDatum = explode('-', $__datum);
        $unixZeit = mktime(0, 0, 1, $teileDatum[ 1 ], $teileDatum[ 2 ], $teileDatum[ 0 ]);

        return $unixZeit;
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
    private function _checkExistHotelCode ($__hotelCode)
    {

        $sql = "select id from tbl_properties where property_code = '" . $__hotelCode . "'";

        $anfrage = mysqli_query($this->_db_connect, $sql);
        // $row = mysqli_fetch_all($anfrage, MYSQLI_ASSOC);
        $row = $this->_fetchAnfrageArray($anfrage);

        if(count($row) != 1) {
            $error = array(
                'Language' => 'en-en',
                'Type' => 3,
                'Code' => 392,
                'Status' => 'Unknown',
                'Tag' => "OTA_HotelRatePlanNotifRQ@AvailStatusMessages",
                'ShortText' => 'Unknown Hotel',
                'LongText' => "Hotel code '" . $__hotelCode . "' is unknown"
            );

            $this->_errors[ ] = $error;

            $this->_furtherProcessing = false;
        } else {
            $this->_hotelId = $row[ 0 ][ 'id' ];
            $this->_hotelCode = $__hotelCode;
        }

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
    private function _checkExistCategoryCode ($__categoryCode)
    {
        $sql = "select id from tbl_categories where categorie_code = '" . $__categoryCode . "' and properties_id = '" . $this->_hotelId . "'";
        $anfrage = mysqli_query($this->_db_connect, $sql);
        $row = $this->_fetchAnfrageArray($anfrage);

        if(count($row) != 1) {
            $error = array();
            $error[ 'Language' ] = 'en-en';
            $error[ 'Type' ] = 3;
            $error[ 'Code' ] = 394;
            $error[ 'Status' ] = 'Unknown';
            $error[ 'Tag' ] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
            $error[ 'ShortText' ] = 'Unknown Category';
            $error[ 'LongText' ] = "Category code '" . $__categoryCode . "' is unknown for hotel '" . $this->_hotelCode . "'";

            $this->_errors[ ] = $error;

            $this->_furtherProcessing = false;
        } else {
            $this->_categoryId = $row[ 0 ][ 'id' ];
            $this->_categoryCode = $__categoryCode;
        }

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
    private function _checkExistRateCode ($__rateCode)
    {
        $sql = "select id from tbl_ota_rates_config where rate_code = '" . $__rateCode . "' and properties_id = '" . $this->_hotelId . "'";
        $anfrage = mysqli_query($this->_db_connect, $sql);
        $row = $this->_fetchAnfrageArray($anfrage);

        if(count($row) != 1) {
            $error = array();
            $error[ 'Language' ] = 'en-en';
            $error[ 'Type' ] = 3;
            $error[ 'Code' ] = 394;
            $error[ 'Status' ] = 'Unknown';
            $error[ 'Tag' ] = "OTA_HotelAvailNotifRQ@AvailStatusMessage";
            $error[ 'ShortText' ] = 'Unknown Rate';
            $error[ 'LongText' ] = "Rate code'" . $__rateCode . "' is unknown for hotel '" . $this->_hotelCode . "'";

            $this->_errors[ ] = $error;

            $this->_furtherProcessing = false;
        } else {
            $this->_rateId = $row[ 0 ][ 'id' ];
            $this->_rateCode = $__rateCode;
        }

        return;
    }

    /**
     * fetcht ein assoziatives Array
     * aus einer mysqli Anfrage
     *
     */
    private function _fetchAnfrageArray ($__anfrage)
    {
        $i = 0;
        $ergebnis = array();

        while ($row = $__anfrage->fetch_assoc()) {
            $ergebnis[ $i ] = $row;

            $i++;
        }

        return $ergebnis;
    }

    /**
     * Sendet Response oder speichert Response
     *
     * @return void
     */
    public function getResponse ()
    {
        $response = $this->_responseFileStart();

        if(count($this->_errors) > 0) // wandelt das Error Array in XML
        {
            $response .= $this->_errorMessages();
            $response .= $this->_responseFileEnde();

            $datum = date("_Y_m_d_H_i_s");
            $errorDatei = "error" . $datum . '.xml';

            // speichern der Fehler XML
            $fp = fopen($errorDatei.".xml", 'w');
            fputs($fp, $response);
            fclose($fp);

            // senden einer Fehler Mail
            $mailText = "http://price.herden.de \n";
            $mailText .= "Ein Fehler ist aufgetreten ! \n";
            $mailText .= "Bitte Fehlerdatei '".$errorDatei."' kontrollieren. \n";
            $mailText .= "Datum: ".date("d.m.Y H:i:s")." \n";

            mail($this->_error_mail_adresse, 'Fehler http://price.herden.de',$mailText, "From: ".$this->_error_mail_from);
            mail($this->mailAdresseStephan, 'Fehler Schnittstelle Price','Fehler Schnittstelle Price. Datum: '.date("d.m.Y H:i:s"), "From: ".$this->_error_mail_from);
        } else {
            $response .= $this->_okMessage();
            $response .= $this->_responseFileEnde();
        }

        echo $response;
    }

    /**
     * Generiert den Start - Teil der Response
     *
     * @return string
     */
    private function _responseFileStart ()
    {
        $time = time();
        $datum = date("Y-m-d", $time);
        $zeit = date("H:i:s", $time);
        $zeitzone = date("O", $time);

        $response = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $response .= "<OTA_HotelRatePlanNotifRS xmlns='http://www.opentravel.org/OTA/2003/05'";
        $response .= " TimeStamp='" . $datum . "T" . $zeit . $zeitzone . "'";
        $response .= " PrimaryLangID='de-DE'";
        $response .= " Version='4.000'";
        $response .= " EchoToken='" . $this->_echoToken . "'";
        $response .= ">\n";

        return $response;
    }

    /**
     * Wandelt aus den Error - Array die Fehlermweldungen in XML um
     *
     * @return
     */
    private function _errorMessages ()
    {
        $response = "<Errors>\n";

        for($i = 0; $i < count($this->_errors); $i++) {
            $response .= "<Error";

            $response .= " Language='" . $this->_errors[ $i ][ 'Language' ] . "'";
            $response .= " Type='" . $this->_errors[ $i ][ 'Type' ] . "'";
            $response .= " Code='" . $this->_errors[ $i ][ 'Code' ] . "'";
            $response .= " Status='" . $this->_errors[ $i ][ 'Status' ] . "'";
            $response .= " Tag='" . $this->_errors[ $i ][ 'Tag' ] . "'";
            $response .= " ShortText='" . $this->_errors[ $i ][ 'ShortText' ] . "'";
            $response .= ">\n";

            $response .= $this->_errors[ $i ][ 'LongText' ] . "\n";
            $response .= "</Error>\n";
        }

        $response .= "</Errors>\n";

        return $response;
    }

    /**
     * Wenn Transfer O.K.
     *
     * @return string
     */
    private function _okMessage ()
    {

        return "<success/>\n";
    }

    /**
     * Ende des response File
     *
     * @return string
     */
    private function _responseFileEnde ()
    {
        $response = "</OTA_HotelRatePlanNotifRS>\n";

        return $response;
    }

}

if(array_key_exists('xml', $_POST)) {

    $xmlEinlesenPrices = new price();

    $fp = fopen('HotelRatePlanNotif.xml', 'w');
    fputs($fp, $_POST[ 'xml' ]);
    fclose($fp);

    if(file_exists('HotelRatePlanNotif.xml')) {

        $xmlEinlesenPrices->setDebugModus(false);
        $xmlEinlesenPrices->lockTable();
        $xmlEinlesenPrices->setSicherungsdateiAnlegen(false);
        $xmlEinlesenPrices->einlesenXmlDatei('HotelRatePlanNotif.xml');
        $xmlEinlesenPrices->unlockTable();
        $xmlEinlesenPrices->getResponse();

    } else {
        $response = "<errors>\n";
        $response .= "<error>\n";
        $response . "Server is not ready\n";
        $response .= "</error>\n";
        $response .= "</errors>";

        echo $response;
    }
} else {
    $response = "<errors>\n";
    $response .= "<error>\n";
    $response . "Missing data\n";
    $response .= "</error>\n";
    $response .= "</errors>";

    echo $response;
}
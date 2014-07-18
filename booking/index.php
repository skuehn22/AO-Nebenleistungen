<?php
/**
 * Übermittlung der gebuchten Raten an ein Hotel
 *
 * http://booking.herden.de
 *
 *
 * @author Stephan.Krauss
 * @date 06.05.13
 * @file index.php
 * @package schnittstelle
 */

class bookingMessage
{
    private $_hotelId = null; // ID des Hotels
    private $hotelCode = null; // Hotel Code

    private $_personenanzahlBuchung = null; // Gesamtanzahl der Personen einer Buchung
    private $_anreiseTag = null; // Anreisetag des Gastes einer Buchung
    private $_abreiseTag = null; // Abreisetag des Gastes einer Buchung
    private $_gesamtPreisEinerBuchung = null; // Gesamtpreis einer Buchung
    private $_buchungsDatum = null;
    private $_kundeId = null; // ID des Kunden

    private $_message = null; // XML Datei

    private $_errors = array();

    // Datenbanken
    private $_db_connect_group = null;
    private $_db_connect_hotel = null;

    // Konditionen
    private $_condition_buchung_ist_geordert = 3;
    private $_condition_buchung_wurde_versandt = 4;
    private $_condition_tabelle_hotelbuchung_uebermittelt = 4;
    private $_condition_tabelle_produktbuchung_uebermittelt = 5;

    private $_condition_bereich_hotelbuchung = 6;
    private $_condition_zimmerbuchung = 1;
    private $_condition_produktbuchung = 2;

    // Flags
    private $flag_tbl_properties_aktiv_zugeschaltet = 3;
    private $flag_tbl_systemparameter_hotelbuchung_zugeschaltet = 1;

    private $_buchungsnummern = array();

    // Blöcke
    private $_ratenXmlBloecke = null;
    private $_produkteXmlBloecke = null;
    private $_personendatenXmlBlock = null;

    // Debug Modus
    private $_debugModus = false;
    private $debugFileFlag = false;

    public function __construct ()
    {
        // Datenbank Programme / allgemein
        $this->_db_connect_group = mysqli_connect('localhost', 'db1154036-noko', 'huhn9huhn');
        mysqli_set_charset($this->_db_connect_group, 'utf8');
        mysqli_select_db($this->_db_connect_group, 'db1154036-noko') or die("keine Verbindung zur Datenbank");

        // Datenbank Hotel
        $this->_db_connect_hotel = mysqli_connect('localhost', 'db1154036-hotel', 'HuhnHotelsHuhn');
        mysqli_set_charset($this->_db_connect_hotel, 'utf8');
        mysqli_select_db($this->_db_connect_hotel, 'db1154036-hotels') or die("keine Verbindung zur Datenbank");

        return $this;
    }

    /**
     * Setzt den Hotel Code und ermittelt die Hotel ID
     *
     * @param $__hotelCode
     * @return bookingMessage
     */
    public function setHotelCode ($__hotelCode)
    {
        $this->hotelCode = $__hotelCode;

        return $this;
    }

    /**
     * setzen des Debug - Modus
     *
     *
     * @param bool $__debug
     * @return bookingMessage
     */
    public function setDebugModus ($__debug = false)
    {
        // Debug Modus
        $this->_debugModus = $__debug;

        return $this;
    }

    /**
     * Flag für das schreiben der Antwort in eine Datei
     *
     * @param bool $debugFileFlag
     * @return $this
     */
    public function setDebugFileFlag($debugFileFlag = false){

        $this->debugFileFlag = $debugFileFlag;

        return $this;
    }

    /**
     * Starten der Verarbeitung
     *
     * @return bookingMessage
     */
    public function start ()
    {

        $this->_message = '<?xml version="1.0" encoding="UTF-8"?><OTA_HotelResRQ>';
        $this->_message .= '<HotelReservations>';

        // Kontrolle ob der Bereich Hotelbuchungen 'aktiv' ist
        $kontrolleAbschaltungBereichUebernachtungen = $this->abschaltungBereichHotelbuchungen();

        // Kontrolle ob das betreffende Hotel 'aktiv' ist
        $kontrolleAbschaltungHotel = $this->abschaltungHotel();

        // wenn Bereich Hotelbuchung zugeschaltet ist und das betreffende Hotel aktiv ist
        if( ($kontrolleAbschaltungBereichUebernachtungen === false) and ($kontrolleAbschaltungHotel === false) ){

            $this->_findHotelId(); // findet Hotel ID
            $this->_findBookings(); // findet die Buchungsnummer zu einem Hotel

            // wenn Buchungsnummern vorliegen
            for($anzahlBuchungen = 0; $anzahlBuchungen < count($this->_buchungsnummern); $anzahlBuchungen++) {

                // Bestimmen der Daten der Buchung
                $this->_bestimmeBuchungsdatenEinerBuchung($anzahlBuchungen);

                // erstellen des XMl File
                $this->_erstellenXmlFile($anzahlBuchungen);

                // Veränderung Status der Teilrechnung
                $this->_setBuchungsStatus($this->_buchungsnummern[ $anzahlBuchungen ]);

            }
        }

        $this->_message .= "</HotelReservations>";
        $this->_message .= "</OTA_HotelResRQ>";

        // Registrierung Buchung
        if(count($this->_buchungsnummern) > 0){
            $this->writeSchnittstelleBuchung($this->hotelCode, count($this->_buchungsnummern));
            $this->writeDebugFile(); // schreibt Kontrolldatei
        }
        // Registrierung Zugriff
        else
            $this->writeSchnittstelleBuchung($this->hotelCode, 0);


        // senden XML - Response
        if(empty($this->_debugModus)) {
            echo $this->_message;
        }

        return $this;
    }

    /**
     * schreibt in die Tabelle 'tbl_schnittstelle_hotel_buchung' wenn mindestens eine Hotelbuchung vorlegt
     *
     * @param $anzahlBuchungen
     * @param $hotelCode
     */
    protected function writeSchnittstelleBuchung($hotelCode, $anzahlBuchungen)
    {
        $sql = "insert into tbl_schnittstelle_hotel_buchung set hotelCode = '".$hotelCode."', anzahlBuchungen = ".$anzahlBuchungen;

        mysqli_query($this->_db_connect_hotel, $sql);

        return;
    }

    /**
     * Kontrolliert ob das Hotel 'aktiv' ist.
     *
     * + true = Hotel ist abgeschaltet
     * + false = Hotel ist nicht abgeschaltet
     *
     * @return bool
     */
    private function abschaltungHotel()
    {
        $statusHotelAktiv = true;

        $sql = "select count(id) as anzahl from tbl_properties where property_code = '".$this->hotelCode."'";
        $sql .= " and aktiv = ".$this->flag_tbl_properties_aktiv_zugeschaltet;

        $result = mysqli_query($this->_db_connect_hotel, $sql);
        $row = mysqli_fetch_assoc($result);

        if($row['anzahl'] == 1)
            $statusHotelAktiv = false;

        return $statusHotelAktiv;
    }

    /**
     * Kontrolliert ob der Bereich Hotelbuchungen im System 'aktiv' ist
     *
     * + true = Bereich Hotelbuchungen abgeschaltet
     * + false = Bereich Hotelbuchungen zugeschaltet
     *
     * @return bool
     */
    private function abschaltungBereichHotelbuchungen()
    {
        $statusBereichHotelbuchungen = true;

        $sql = "select wert from tbl_systemparameter where parametername = 'hotelbuchung' = '".$this->flag_tbl_systemparameter_hotelbuchung_zugeschaltet."'";

        $result = mysqli_query($this->_db_connect_group, $sql);
        $row = mysqli_fetch_assoc($result);

        if($row['wert'] == $this->flag_tbl_systemparameter_hotelbuchung_zugeschaltet)
            $statusBereichHotelbuchungen = false;


        return $statusBereichHotelbuchungen;
    }

    /**
     * Erstellt XML File der Antwort
     *
     * + aktuelles datum
     * + Kunden ID
     * + gebuchte Raten einer Buchungsnummer
     * + Personendaten einer Buchungsnummer
     * + gebuchte Produkte einer Buchungsnummer
     * + globale Information der Buchung
     * + Information durch Kunde
     * + Zeitraum
     *
     * @param $i
     */
    private function _erstellenXmlFile ($i)
    {
        // Start Hotelreservierung
        // nach Venere
        // neues Datum wenn modifiziert und neuer ResStatus nach Venere

        $aktuellesDatum = date("Y-m-d H:i:s", time());
        $this->_message .= '<HotelReservation ResStatus="NU" RoomStayReservation="true" LastModifyDateTime="' . $this->_buchungsDatum . '">';

        // ermitteln Kunden ID
        $this->_ermittelnKundenId($this->_buchungsnummern[ $i ][ 'buchungsnummer' ]);

        // ermitteln XML Blöcke
        $this->_ermittleBloecke(
            $this->_buchungsnummern[ $i ][ 'buchungsnummer' ],
            $this->_buchungsnummern[ $i ][ 'teilrechnung' ]
        );

        // gebuchte Raten einer Buchungsnummer
        $this->_message .= "<RoomStays>";
        $this->_gebuchteRaten();
        $this->_message .= "</RoomStays>";

        // gebuchte Produkte einer Buchungsnummer
        $this->_message .= "<Supplements>";
        $this->_gebuchteProdukte();
        $this->_message .= "</Supplements>";

        // Personendaten einer Buchungsnummer
        $this->_message .= "<ResGuests><ResGuest><Profiles><ProfileInfo><Profile><Customer>";
        $this->_personendaten();
        $this->_message .= "</Customer></Profile></ProfileInfo></Profiles></ResGuest></ResGuests>";

        // globale Information der Buchung
        $this->_message .= "<ResGlobalInfo>";

        // Personenanzahl
        $this->_message .= "<GuestCounts IsPeerRoom='false'>";
        $this->_message .= "<GuestCount Count='" . $this->_personenanzahlBuchung . "' />";
        $this->_message .= "</GuestCounts>";

        // Zeitraum
        $this->_message .= "<TimeSpan Start='" . $this->_anreiseTag . "' End='" . $this->_abreiseTag . "' />";

        // notice: Information durch Kunde
        // Informationen durch den Kunden
        $this->_message .= "<Comments><Comment>";
        $this->_kundenInformation($this->_buchungsnummern[ $i ][ 'buchungsnummer' ]);
        $this->_message .= "</Comment></Comments>";

        // Gesamtsumme
        $this->_message .= "<Total AdditionalFeesExcludedIndicator='false' CurrencyCode='EUR' AmountAfterTax='" . $this->_gesamtPreisEinerBuchung . "' />";

        // Hotel Reservierung ID
        $this->_message .= "<HotelReservationIDs>";
        $this->_message .= "<HotelReservationID ResID_Value='" . $this->_buchungsnummern[ $i ][ 'buchungsnummer' ] . "-" . $this->_buchungsnummern[ $i ][ 'teilrechnung' ] . "' ResID_Date='" . $this->_buchungsDatum . "' />";
        $this->_message .= "</HotelReservationIDs>";

        // Extension Herden
        $this->_message .= "<TPA_Extensions>";
        $this->_message .= "<Herden ReservationType='STD' />";
        $this->_message .= "</TPA_Extensions>";

        $this->_message .= "</ResGlobalInfo>";

        $this->_message .= "</HotelReservation>";

        // Debug
        // Kontrolle Ablauf
        if(!empty($this->_debugModus)) {
            $this->_kontrolleDurchlauf(
                $i,
                $this->_buchungsnummern[ $i ],
                $this->_buchungsnummern[ $i ][ 'teilrechnung' ]
            ); // zeigt Buchungsnummer an
        }
    }

    /**
     * Bestimmt die Buchungsdaten einer Buchung
     *
     * + Start und Enddatum einer Buchung
     * + Gesamtanzahl Personen
     * + Gesamtpreis aller Raten
     *
     * @param $i
     */
    private function _bestimmeBuchungsdatenEinerBuchung ($i)
    {
        $this
            ->_findStartUndEnddatum(
                $this->_buchungsnummern[ $i ][ 'buchungsnummer' ],
                $this->_buchungsnummern[ $i ][ 'teilrechnung' ]
            ) // findet Start und Enddatum der Buchung
            ->_findGesamtanzahlPersonen(
                $this->_buchungsnummern[ $i ][ 'buchungsnummer' ],
                $this->_buchungsnummern[ $i ][ 'teilrechnung' ]
            ) // findet die Gesamtanzahl der Personen
            ->_findGesamtpreisAllerRatenUndProdukte(
                $this->_buchungsnummern[ $i ][ 'buchungsnummer' ],
                $this->_buchungsnummern[ $i ][ 'teilrechnung' ]
            ) // Gesamtpreis aller Raten und Produkte einer Teilrechnung
            ->_findBuchungsdatum($this->_buchungsnummern[ $i ][ 'buchungsnummer' ]); // Buchungsdatum
    }

    /**
     * Ermittelt das Buchungsdatum
     *
     * @param $__buchungsnummer
     * @return bookingMessage
     */
    private function _findBuchungsdatum ($__buchungsnummer)
    {

        $sql = "select date from tbl_buchungsnummer where id = " . $__buchungsnummer;
        $result = mysqli_query($this->_db_connect_group, $sql);
        $row = mysqli_fetch_assoc($result);
        $this->_buchungsDatum = $row[ 'date' ];

        return $this;
    }

    /**
     * Setzt den Buchungsstatus in
     * 'tbl_buchungsnummer'
     * 'tbl_xml_buchung'
     *
     * @param $__buchungsnummer
     * @return bookingMessage
     */
    private function _setBuchungsStatus ($__buchungsnummer)
    {

        if($this->_debugModus === true)
            return $this;

        // 'tbl_xml_buchung' , Status = 4
        $sql = "update tbl_xml_buchung set status = '" . $this->_condition_buchung_wurde_versandt . "' where (buchungsnummer_id = " . $__buchungsnummer[ 'buchungsnummer' ] . ") and (teilrechnungen_id = " . $__buchungsnummer[ 'teilrechnung' ] . ")";
        $result = mysqli_query($this->_db_connect_group, $sql);

        // 'tbl_hotelbuchung' , Status = 4
        $sql = "update tbl_hotelbuchung set status = ".$this->_condition_tabelle_hotelbuchung_uebermittelt." where buchungsnummer_id = ".$__buchungsnummer[ 'buchungsnummer' ]." and teilrechnungen_id = " . $__buchungsnummer[ 'teilrechnung' ];
        $result = mysqli_query($this->_db_connect_group, $sql);

        // 'tbl_produktbuchung' , Status = 5
        $sql = "update tbl_produktbuchung set status = ".$this->_condition_tabelle_produktbuchung_uebermittelt." where buchungsnummer_id = ".$__buchungsnummer[ 'buchungsnummer' ]." and teilrechnungen_id = " . $__buchungsnummer[ 'teilrechnung' ];

        return $this;
    }

    /**
     * Ermittelt die Gesamtanzahl der Personen
     *
     * @param $__buchungsnummer
     * @return bookingMessage
     */
    private function _findGesamtanzahlPersonen ($__buchungsnummer, $__teilrechnung)
    {

        $sql = "
        SELECT
            SUM(`personNumbers`) AS `personenanzahl`
        FROM
            `tbl_hotelbuchung`
        WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " and teilrechnungen_id = " . $__teilrechnung . ")";

        /** @var $db_group Zend_Db_Adapter_Mysqli */
        $result = mysqli_query($this->_db_connect_group, $sql);
        $row = mysqli_fetch_assoc($result);

        $this->_personenanzahlBuchung = $row[ 'personenanzahl' ];

        return $this;
    }

    /**
     * Ermittelt die ID
     * des Kunden
     *
     * @param $i
     * @return bookingMessage
     */
    private function _ermittelnKundenId ($__buchungsnummer)
    {

        $sql = "
            SELECT
                `kunden_id`
            FROM
                `tbl_buchungsnummer`
            WHERE (`id` = " . $__buchungsnummer . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        $row = mysqli_fetch_assoc($result);
        $this->_kundeId = $row[ 'kunden_id' ];

        return $this;
    }

    /**
     * Findet den Gesamtpreis einer Hotelbuchung
     *
     * + aller Überchachtungen
     * + aller Produkte
     *
     * @param $__buchungsnummer
     * @return bookingMessage
     */
    private function _findGesamtpreisAllerRatenUndProdukte ($__buchungsnummer, $__teilrechnung)
    {
        $gesamtpreis = 0;

        // Übernachtungen einer Buchung
        $sql = "
            SELECT
                `nights`
                , `roomNumbers`
                , `personNumbers`
                , `roomPrice`
                , `personPrice`
            FROM
                `tbl_hotelbuchung`
            WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " and teilrechnungen_id = " . $__teilrechnung . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        while ($hotelbuchung = mysqli_fetch_assoc($result)) {

            // Zimmerpreis
            if(!empty($hotelbuchung[ 'roomPrice' ])) {
                $gesamtpreis += $hotelbuchung[ 'nights' ] * $hotelbuchung[ 'roomNumbers' ] * $hotelbuchung[ 'roomPrice' ];
            }

            // Personenpreis
            if(!empty($hotelbuchung[ 'personPrice' ])) {
                $gesamtpreis += $hotelbuchung[ 'nights' ] * $hotelbuchung[ 'personNumbers' ] * $hotelbuchung[ 'personPrice' ];
            }
        }

        // Produkte einer Buchung
        $sql = "
            SELECT
                SUM(`summeProduktPreis`) as summe
            FROM
                `tbl_produktbuchung`
            WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " and teilrechnungen_id = " . $__teilrechnung . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        $summeProduktbuchung = mysqli_fetch_assoc($result);
        $gesamtpreis += $summeProduktbuchung[ 'summe' ];

        $this->_gesamtPreisEinerBuchung = number_format($gesamtpreis, 2, '.', '');

        return $this;
    }

    /**
     * Ermittelt die XML - Blöcke einer Buchung
     *
     * @param $i
     * @return bookingMessage
     */
    private function _ermittleBloecke ($__buchungsnummer, $__teilrechnung)
    {

        // Erststart
        $this->_ratenXmlBloecke = null;
        $this->_produkteXmlBloecke = null;

        // Raten
        $sql = "
            SELECT
                `block`
            FROM
                `tbl_xml_buchung`
            WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " AND teilrechnungen_id = " . $__teilrechnung . "
                AND `buchungstyp` = " . $this->_condition_zimmerbuchung . " and `bereich` = " . $this->_condition_bereich_hotelbuchung . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $this->_ratenXmlBloecke[ ] = $row[ 'block' ];
        }

        // Produkte
        $sql = "
            SELECT
                `block`
            FROM
                `tbl_xml_buchung`
            WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " AND teilrechnungen_id = " . $__teilrechnung . "
                AND `buchungstyp` = " . $this->_condition_produktbuchung . " and `bereich` = " . $this->_condition_bereich_hotelbuchung . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $this->_produkteXmlBloecke[ ] = $row[ 'block' ];
        }

        // Personendaten
        $sql = "
            SELECT
                `block`
            FROM
                `tbl_xml_kundendaten`
            WHERE (`kunde_id` = " . $this->_kundeId . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);
        $row = mysqli_fetch_assoc($result);
        $this->_personendatenXmlBlock = $row[ 'block' ];

        return $this;
    }

    /**
     * Schreibt die Blöcke der gebuchten
     * Raten einer Hotelbuchung
     *
     *
     * @return bookingMessage
     */
    private function _gebuchteRaten ()
    {

        // schreiben der Raten Blöcke
        for($i = 0; $i < count($this->_ratenXmlBloecke); $i++) {
            $this->_message .= $this->_ratenXmlBloecke[ $i ];
        }

        return $this;
    }

    /**
     * Schreibt die gebuchten Produkte
     * als XML Blöcke
     *
     * @return bookingMessage
     */
    private function _gebuchteProdukte ()
    {

        // schreiben XML Blöcke
        for($i = 0; $i < count($this->_produkteXmlBloecke); $i++) {
            $this->_message .= $this->_produkteXmlBloecke[ $i ];
        }

        return $this;
    }

    /**
     * Schreibt den XML - Block der
     * Personendaten des Bestellers / Kunde
     *
     * @return bookingMessage
     */
    private function  _personendaten ()
    {

        // schreibt die Personendaten des Bestellers
        $this->_message .= $this->_personendatenXmlBlock;

        return $this;
    }

    /**
     * Findet die Hotel ID
     * mit den Hotel Code
     *
     * @return bookingMessage
     */
    private function _findHotelId ()
    {

        $sql = "select id from tbl_properties where property_code = '" . $this->hotelCode . "'";

        $result = mysqli_query($this->_db_connect_hotel, $sql);
        $row = mysqli_fetch_assoc($result);

        $this->_hotelId = $row[ 'id' ];

        return $this;
    }

    /**
     * schreibt die Kundeninformation in den XML File
     *
     * @param $__buchungsnummern
     * @param $__buchungsnummern
     * @return $this
     */
    private function _kundenInformation ($__buchungsnummern)
    {

//        $sql = "select buchungshinweis from tbl_buchungsnummer where id = '".$__buchungsnummern."'";
//        $result = mysqli_query($this->_db_connect_group, $sql);
//        $row = mysqli_fetch_assoc($result);
//        $this->_message .= "<Text>".$row['buchungshinweis']."</Text>";

        $this->_message .= "<Text>"."</Text>";

        return $this;
    }

    /**
     * Findet die Hotelbuchungen die
     * geordert sind.
     * Status = 2
     *
     * @return
     */
    private function _findBookings ()
    {
        $bookings = array();

        // ermitteln aller Zimmerbuchungen eines Hotels
        $sql = "
            SELECT
                tbl_xml_buchung.buchungsnummer_id,
                tbl_xml_buchung.teilrechnungen_id
            FROM
                tbl_xml_buchung
                INNER JOIN tbl_hotelbuchung
                    ON (tbl_xml_buchung.buchungstabelle_id = tbl_hotelbuchung.id)
            WHERE (tbl_xml_buchung.buchungstyp = " . $this->_condition_zimmerbuchung . "
                AND (tbl_xml_buchung.bereich = ".$this->_condition_bereich_hotelbuchung.")
                AND ( (tbl_xml_buchung.status = " . $this->_condition_buchung_ist_geordert . ")
                    OR (tbl_xml_buchung.status = " . $this->_condition_buchung_wurde_versandt. "))
                AND tbl_hotelbuchung.propertyId = " . $this->_hotelId . ")
                order by tbl_xml_buchung.buchungsnummer_id, tbl_xml_buchung.teilrechnungen_id";

        $result = mysqli_query($this->_db_connect_group, $sql);

        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[ $i ][ 'buchungsnummer' ] = $row[ 'buchungsnummer_id' ];
            $bookings[ $i ][ 'teilrechnung' ] = $row[ 'teilrechnungen_id' ];

            $i++;
        }

        // Kontrolle ob Buchung vollstaendig ist.
        $bookings = $this->_kontrolleVollstaendigeBuchung($bookings);

        // vereinzeln der Buchungen
        $bookings = $this->_vereinzelnBuchungen($bookings);

        $this->_buchungsnummern = $bookings;

        return $this;
    }

    /**
     * Kontrolliert, das nur sauber abgeschlossene Buchungen übermittelt werden.
     *
     * Buchungen mit einer Session die nicht sauber beendet wurden, also
     * keinen 'tbl_buchungsnummer'.'status' = 4, werden ausgefiltert und nicht übermittelt
     *
     * @param array $bookings
     * @return array
     */
    private function _kontrolleVollstaendigeBuchung(array $bookings)
    {
        $vollstaendigeBuchung = array();

        for($i=0; $i < count($bookings); $i++){
            $sql = "select status from tbl_buchungsnummer where id = ".$bookings[$i]['buchungsnummer'];
            $result = mysqli_query($this->_db_connect_group, $sql);

            $row = mysqli_fetch_array($result,MYSQL_ASSOC);

            if($row['status'] == $this->_condition_buchung_wurde_versandt){
                $vollstaendigeBuchung[] = $bookings[$i];
            }
        }

        return $vollstaendigeBuchung;
    }

    /**
     * Vereinzeln der Zimmerbuchungen.
     * Filtern nach Buchungsnummer und Teilrechnung
     *
     * @param $__bookings
     * @return array
     */
    private function _vereinzelnBuchungen ($__bookings)
    {

        // vereinzeln der Zimmerbuchungen
        $j = 0;
        $memoryBuchungsnummer = null;
        $memoryTeilbuchung = null;
        $vereinzelteBuchung = array();
        for($i = 0; $i < count($__bookings); $i++) {

            // Erststart
            if($memoryBuchungsnummer === null) {
                $vereinzelteBuchung[ $j ][ 'buchungsnummer' ] = $__bookings[ $i ][ 'buchungsnummer' ];
                $vereinzelteBuchung[ $j ][ 'teilrechnung' ] = $__bookings[ $i ][ 'teilrechnung' ];

                $j++;
            } elseif(($memoryBuchungsnummer != $__bookings[ $i ][ 'buchungsnummer' ]) or ($memoryTeilbuchung != $__bookings[ $i ][ 'teilrechnung' ])) {
                $vereinzelteBuchung[ $j ][ 'buchungsnummer' ] = $__bookings[ $i ][ 'buchungsnummer' ];
                $vereinzelteBuchung[ $j ][ 'teilrechnung' ] = $__bookings[ $i ][ 'teilrechnung' ];

                $j++;
            }

            $memoryBuchungsnummer = $__bookings[ $i ][ 'buchungsnummer' ];
            $memoryTeilbuchung = $__bookings[ $i ][ 'teilrechnung' ];

        }

        return $vereinzelteBuchung;
    }

    /**
     * Findet das Start und Enddatum einer Buchung
     *
     * @param $__buchungsnummer
     * @return bookingMessage
     */
    private function _findStartUndEnddatum ($__buchungsnummer, $__teilrechnung)
    {

        $sql = "
            SELECT
                `startDate`
                , `nights`
            FROM
                `tbl_hotelbuchung`
            WHERE (`buchungsnummer_id` = " . $__buchungsnummer . " and teilrechnungen_id = " . $__teilrechnung . ")";

        $result = mysqli_query($this->_db_connect_group, $sql);

        $anreiseDatumUndNaechte = array();
        $i = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $anreiseDatumUndNaechte[ $i ][ 'startdatum' ] = $row[ 'startDate' ];
            $anreiseDatumUndNaechte[ $i ][ 'naechte' ] = $row[ 'nights' ];

            $i++;
        }

        $this->_anreiseTag = $anreiseDatumUndNaechte[ 0 ][ 'startdatum' ];
        $datumsTeileAnreiseTag = explode("-", $anreiseDatumUndNaechte[ 0 ][ 'startdatum' ]);
        $anreiseTagInSekunden = mktime(
            0,
            0,
            1,
            $datumsTeileAnreiseTag[ 1 ],
            $datumsTeileAnreiseTag[ 2 ],
            $datumsTeileAnreiseTag[ 0 ]
        );
        $tagInSekunden = 86400;
        $abreiseTagInSekunden = $anreiseTagInSekunden + ($anreiseDatumUndNaechte[ 0 ][ 'naechte' ] * $tagInSekunden);
        $this->_abreiseTag = date("Y-m-d", $abreiseTagInSekunden);

        return $this;
    }

    /**
     * Ausgabe in XML - Datei
     *
     */
    private function writeDebugFile ()
    {
        // Ausgabe in Datei
        $datum = date("Y_m_d_H_i_s");

        $fp = fopen($this->hotelCode.'_'. $datum . ".xml", 'w');
        fputs($fp, $this->_message);
        fclose($fp);

        return;
    }

    /**
     * zeigt den Durchlauf der Buchungsnummern an
     *
     * @param $i
     * @param bool $__buchungsnummer
     * @param bool $__teilrechnung
     * @return $this
     */
    private function _kontrolleDurchlauf ($i, $__buchungsnummer = false, $__teilrechnung = false)
    {

        if(empty($this->_debugModus))
            return;

        if(empty($__buchungsnummer)) {
            $buchungsnummer = 'nicht vorhanden';
        } else {
            $buchungsnummer = $__buchungsnummer[ 'buchungsnummer' ];
        }

        if(empty($__teilrechnung)) {
            $teilrechnung = 'Teilrechnungsnummer nicht vorhanden';
        } else {
            $teilrechnung = $__buchungsnummer[ 'teilrechnung' ];
        }

        echo "Buchungsnummer: " . $buchungsnummer . "<br>";
        echo "Teilrechnungsnummer: " . $teilrechnung . "<br>";
        echo "Durchlauf: " . $i . "<hr>";

        var_dump($this->_buchungsnummern[$i]);
        echo "<hr>";

        return $this;
    }

    /**
     * speichert die Fehlermeldung
     *
     * @param $__errorMessage
     * @param bool $__errorCode
     * @return bookingMessage
     */
    public function setError ($__errorMessage, $__errorCode = false)
    {

        $error = array();
        $error[ 'message' ] = $__errorMessage;
        $error[ 'code' ] = $__errorCode;

        $this->_errors[ ] = $error;

        return $this;
    }

    public function sendErrors ()
    {

        $fileTyp = "OTA_HotelResRQ";

        $errors = "<Errors>";

        for($i = 0; $i < count($this->_errors); $i++) {
            $errors .= "<Error";
            $errors .= " Language='en-en'";
            $errors .= " Type='3'";

            if(!empty($this->_errors[ $i ][ 'code' ])) {
                $errors .= " Code='" . $this->_errors[ $i ][ 'code' ] . "'";
            }

            $errors .= " Status='Unknown'";
            $errors .= " Tag='" . $fileTyp . "'";
            $errors .= " ShortText='" . $this->_errors[ $i ][ 'message' ] . "'";
            $errors .= " >";
            $errors .= $this->_errors[ $i ][ 'message' ];
            $errors .= "</Error>";
        }

        $errors .= "</Errors>";

        if(empty($this->_debugModus)) {
            echo $errors;
        }
    }

}

$newBooking = new bookingMessage();

if($_GET[ 'propertycode' ]) {

    $newBooking
        ->setDebugModus(false) // Debugging Anzeige
        ->setDebugFileFlag(true) // schreibt Buchungs - XML in Datei
        ->setHotelCode($_GET[ 'propertycode' ])
        ->start();
} else {

    $newBooking->setError('Missing propertyCode', 394);
    $newBooking->sendErrors();
}
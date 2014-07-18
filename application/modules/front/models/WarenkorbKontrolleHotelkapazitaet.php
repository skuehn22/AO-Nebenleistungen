<?php
/**
 * Beschreibung der Klasse
 *
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 * Ausführliche Beschreibung der Klasse
 *
 *
 * @author stephan.krauss
 * @date 22.05.13
 * @file Front_Model_WarenkorbKontrolleHotelkapazitaet.php
 * @package front | admin | tabelle | data | tools | plugins
 * @subpackage model | controller | filter | validator
 */
class Front_Model_WarenkorbKontrolleHotelkapazitaet extends nook_ToolModel
{

    // Views / Tabellen
    private $_tabelleHotelbuchung = null;
    private $_tabelleOtaRatesAvailability = null;
    private $_tabelleOtaPrices = null;
    private $_tabelleProperties = null;

    // Errors
    private $_error_anzahl_datensaetze_stimmt_nicht = 1500;
    private $_error_ausgangswert_nicht_vorhanden = 1501;

    // Flags

    // Konditionen
    private $_condition_rate_ist_ausgebucht = 6;

    protected $_gebuchteUebernachtungen = array();
    protected $_buchungsnummer = null;
    protected $_pimple = null;

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleOtaRatesAvailability Application_Model_DbTable_otaRatesAvailability */
        $this->_tabelleOtaRatesAvailability = new Application_Model_DbTable_otaRatesAvailability(array( 'db' => 'hotels' ));
        /** @var _tabelleOtaPrices Application_Model_DbTable_otaPrices */
        $this->_tabelleOtaPrices = new Application_Model_DbTable_otaPrices(array( 'db' => 'hotels' ));
        /** @var  _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array( 'db' => 'hotels'));
    }

    /**
     * Übernimmt den Pimple Container
     *
     * @param $pimple
     * @return $this
     */
    public function setPimple($pimple){
        $this->_pimple = $pimple;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_WarenkorbKontrolleHotelkapazitaet
     */
    public function setBuchungsnummer ($buchungsnummer)
    {
        $this->_buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @return int
     */
    public function getBuchungsnummer ()
    {
        return $this->_buchungsnummer;
    }

    /**
     * Ermittelt alle Hotelbuchungen einer Buchungaus 'tbl_hotelbuchung'
     *
     * + sucht Hotelbuchungen
     * + startet Kontrolle der Hotelbuchungen eines Warenkorbes
     *
     * @throws nook_Exception
     * @return Front_Model_WarenkorbKontrolleHotelkapazitaet
     */
    public function ermittelnHotelbuchungen ()
    {

        if(empty($this->_buchungsnummer)) {
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);
        }

        // suchen Hotelbuchungen
        $this->_findeUebernachtungen();

        // Kontrolle der Hotelbuchungen
        $this->_ermittelnHotelbuchungen();

        return $this;
    }

    /**
     * Ermittelt die Hotelbuchungen einer Buchungsnummer
     *
     * + Kontrolliert Kapazität und Preis der Rate eines Hotels in einem Zeitraum
     * + ermitteln Hotel Code
     * + ermitteln Category Code
     * + Kontrolle ob Hotel Überbuchung zulässt
     * + Kontrolle Kapazität der Rate im Zeitraum
     * + Kontrolle der Preise
     *
     */
    private function _ermittelnHotelbuchungen ()
    {
        for($i = 0; $i < count($this->_gebuchteUebernachtungen); $i++) {
            $zimmerBuchung = $this->_gebuchteUebernachtungen[ $i ];

            $zeitraum = $this->_bestimmenDatumDesZeitraum($zimmerBuchung[ 'startDate' ], $zimmerBuchung[ 'nights' ]);

            // ermitteln Hotel Code
            $hotelCode = $this->_ermittelnHotelCode($zimmerBuchung[ 'propertyId' ]);
            $zimmerBuchung[ 'hotel_code' ] = $hotelCode;

            // ermitteln Rate Code
            $rateCode = $this->_ermittelnRatenCode($zimmerBuchung[ 'otaRatesConfigId' ]);
            $zimmerBuchung[ 'rate_code' ] = $rateCode;

            // Kontrolle Überbuchungs Modus Hotel
            $ueberbuchungMoeglich = $this->_kontrolleUeberbuchungsModusHotel($zimmerBuchung);

            // Kontrolle Kapazitaet des Hotels, wenn kein Überbuchung im Hotel geschaltet
            if(empty($ueberbuchungMoeglich))
                $this->_kontrolleKapazitaetDesHotels($zimmerBuchung, $zeitraum);

            // Kotrolle Preise der Uebernachtung
            $this->_kontrollePreiseUebernachtung($zimmerBuchung, $zeitraum);
        }

        return;
    }

    /**
     * Überprüft ob in einem Hotel eine Überbuchung möglich ist.
     *
     * + return 'true' = Überbuchung möglich
     * + return 'false' = Überbuchung nicht möglich
     *
     * @param $zimmerBuchung
     * @return bool
     */
    private function _kontrolleUeberbuchungsModusHotel($zimmerBuchung)
    {
        $ueberbuchungMoeglich = false;

        $toolHotel = new nook_ToolHotel();
        $grundDatenHotel = $toolHotel
            ->setHotelId($zimmerBuchung['propertyId'])
            ->getGrunddatenHotel();

        if($grundDatenHotel['overbook'] == 2)
            $ueberbuchungMoeglich = true;

        return $ueberbuchungMoeglich;
    }



    /**
     * Findet die bereits getätigten Hotelbuchungen
     *
     * eines Warenkorbes.
     */
    private function _findeUebernachtungen ()
    {
        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->_pimple['tabelleHotelbuchung'];

        $select = $tabelleHotelbuchung->select();
        $select->where("buchungsnummer_id = " . $this->_buchungsnummer);

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($rows) > 0) {
            $this->_gebuchteUebernachtungen = $rows;
        }

        return;
    }

    /**
     * Kontrolliert die Kapazität der Rate eines Hotels
     *
     * im angegebenen Zeitraum.
     * + Wenn die Kapazität nicht ausreichend ist, dann wird der Status ausgebucht gesetzt.
     *
     * @param $zimmerBuchung
     */
    private function _kontrolleKapazitaetDesHotels ($zimmerBuchung, $zeitraum)
    {

        for($i = 0; $i < count($zeitraum); $i++) {
            $checkKapazitaetRate = $this->_kontrolleRatenKapazitaet($zeitraum[ $i ], $zimmerBuchung);

            // wenn nötig verändern Status der Buchung
            if(empty($checkKapazitaetRate)) {
                $this->_setzeRateStatusAusgebucht($zimmerBuchung);

                break;
            }
        }

        return;
    }

    /**
     * Ermittelt das Datum von Anreisedatum bis zum Abreisedatum.
     *
     * Das Abreisedatum wird nicht übermittelt.
     *
     * @param $anreisedatum
     * @param $naechte
     * @return array
     */
    private function _bestimmenDatumDesZeitraum ($anreisedatum, $naechte)
    {
        $zeitraum = array();
        $zeitraum[ ] = $anreisedatum;

        $dateObject = new DateTime($anreisedatum);

        for($i = 0; $i < ($naechte - 1); $i++) {
            $periode = "P1D";
            $dateObjectAdd = date_add($dateObject, new DateInterval($periode));
            $zeitraum[ ] = date_format($dateObjectAdd, "Y-m-d");
        }

        return $zeitraum;
    }

    /**
     * Ermittelt den aktuellen Preis der Rate und verändert diesen bei Bedarf
     *
     * in 'tbl_hotelbuchung'
     * + ermittelt den aktuellen Tagespreis
     * + ermittelt die Summe der Preise über den gesamten Zeitraum
     * + Kontrolliert ob sich der Verrechnungsmodus geändert hat
     * + wenn geänderter Verrechnungsmodus, dann setze Status ausgebucht
     * + Berechnung des gemittelten preis über den Zeitraum
     *
     * @param $zimmerBuchung
     */
    private function _kontrollePreiseUebernachtung ($zimmerBuchung, $zeitraum)
    {
        $summeTagespreise = 0;

        for($i = 0; $i < count($zeitraum); $i++) {

            // Ermitteln Preis und Preistyp
            $aktuellerTag = $this->_ermittleAktuellenInformationDerRate($zimmerBuchung, $zeitraum[ $i ]);
            $summeTagespreise += $aktuellerTag[ 'amount' ];

            // wenn von Personenpreis auf Zimmerpreis umgestellt wurde
            if(($aktuellerTag[ 'pricePerPerson' ] === false) and ($zimmerBuchung[ 'personPrice' ] > 0)) {
                $this->_setzeRateStatusAusgebucht($zimmerBuchung);
            }

            // wenn von Zimmerpreis auf Personenpreis umgestellt wurde
            if(($aktuellerTag[ 'pricePerPerson' ] === true) and ($zimmerBuchung[ 'roomPrice' ] > 0)) {
                $this->_setzeRateStatusAusgebucht($zimmerBuchung);
            }

        }

        // Berechnung gemittelter preis über den Zeitraum
        $aktuellerPreis = $summeTagespreise / $i;

        // ändern Vergleich
        if($aktuellerPreis != $zimmerBuchung[ 'roomPrice' ]) {
            $this->_korrekturPreisInHotelbuchung(
                $zimmerBuchung,
                $aktuellerPreis,
                $aktuellerTag[ 'pricePerPerson' ]
            );
        }

        return;
    }

    /**
     * Ermittelt den aktuellen Preis der
     *
     * Rate aus 'tbl_ota_price'
     *
     * @param $zimmerbuchung
     * @param $datum
     * @return mixed
     */
    private function _ermittleAktuellenInformationDerRate ($zimmerbuchung, $datum)
    {

        $whereDatum = "datum = '" . $datum . "'";
        $whereHotelCode = "hotel_code = '" . $zimmerbuchung[ 'hotel_code' ] . "'";
        $whereRatesConfigId = "rates_config_id = '" . $zimmerbuchung[ 'otaRatesConfigId' ] . "'";

        $cols = array(
            'amount',
            'pricePerPerson'
        );

        $select = $this->_tabelleOtaPrices->select();
        $select
            ->from($this->_tabelleOtaPrices, $cols)
            ->where($whereDatum)
            ->where($whereHotelCode)
            ->where($whereRatesConfigId);

        $rows = $this->_tabelleOtaPrices->fetchAll($select)->toArray();

        // Berechnung aktueller Preis
        if(count($rows) <> 1) {
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);
        }

        return $rows[ 0 ];
    }

    /**
     * Korrigiert den Preis der Rate im Buchungsdatensatz
     *
     * in 'tbl_hotelbuchung'.'roomprice'
     *
     * @param $zimmerBuchung
     * @param $aktuellerPreis
     */
    private function _korrekturPreisInHotelbuchung ($zimmerBuchung, $aktuellerPreis, $personenPreis)
    {
        // Update Zimmerpreis
        if($personenPreis == 'false') {
            $update = array(
                "roomPrice" => $aktuellerPreis
            );
        } // Update Personenpreis
        else {
            $update = array(
                "personPrice" => $aktuellerPreis
            );
        }

        $where = array(
            "buchungsnummer_id = " . $zimmerBuchung[ 'buchungsnummer_id' ],
            "propertyId = " . $zimmerBuchung[ 'propertyId' ],
            "startDate = '" . $zimmerBuchung[ 'startDate' ] . "'",
            "nights = " . $zimmerBuchung[ 'nights' ],
            "otaRatesConfigId = " . $zimmerBuchung[ 'otaRatesConfigId' ]
        );

        $this->_tabelleHotelbuchung->update($update, $where);

        return;
    }

    /**
     * Ermittelt den Code der Rate eines Hotels
     *
     * @param $rateId
     * @return mixed
     */
    private function _ermittelnRatenCode ($rateId)
    {

        $toolRate = new nook_ToolRate();
        $rateData = $toolRate->setRateId($rateId)->getRateData();

        return $rateData[ 'rate_code' ];
    }

    /**
     * Ermittelt den Hotel Code
     *
     * @param $hotelId
     * @return mixed
     */
    private function _ermittelnHotelCode ($hotelId)
    {

        $toolHotel = new nook_ToolHotel();
        $hotelCode = $toolHotel->setHotelId($hotelId)->getHotelCode();

        return $hotelCode;
    }

    /**
     * Kontrolliert die Ratenbuchung eines Hotels zu einem Datum
     *
     * Wenn die Kapazität entsprechend des Datum vorhanden ist dann $checkKapazitaetRate = true
     *
     * @param $datum
     * @param $zimmerBuchung
     * @return bool
     */
    private function _kontrolleRatenKapazitaet ($datum, $zimmerBuchung)
    {
        $checkKapazitaetRate = false;

        $whereHotelCode = "hotel_code = '" . $zimmerBuchung[ 'hotel_code' ] . "'";
        $whereRateCode = "rate_code = '" . $zimmerBuchung[ 'rate_code' ] . "'";
        $whereDatum = "datum = '" . $datum . "'";

        $cols = array(
            "roomlimit"
        );

        $select = $this->_tabelleOtaRatesAvailability->select();
        $select
            ->from($this->_tabelleOtaRatesAvailability, $cols)
            ->where($whereHotelCode)
            ->where($whereRateCode)
            ->where($whereDatum);

        $query = $select->__toString();
        $rows = $this->_tabelleOtaRatesAvailability->fetchAll($select)->toArray();

        if(count($rows) < 1)
            throw new nook_Exception("Keine Kapazitaet vorhanden: ".$query);

        if(count($rows) > 1)
            throw new nook_Exception("Zu viele Datensaetze: ".$query);

        if($rows[ 0 ][ 'roomlimit' ] >= $zimmerBuchung[ 'roomNumbers' ]) {
            $checkKapazitaetRate = true;
        }

        return $checkKapazitaetRate;
    }

    /**
     * Setzt den status einer Zimmerbuchung auf ausgebucht.
     *
     * Es wird die Rate auf den Status ausgebucht gesetzt.
     *
     * @param $zimmerbuchung
     */
    private function _setzeRateStatusAusgebucht ($zimmerbuchung)
    {

        $update = array(
            'status' => $this->_condition_rate_ist_ausgebucht
        );

        $where = array(
            "buchungsnummer_id = " . $this->_buchungsnummer,
            "propertyId = " . $zimmerbuchung[ 'propertyId' ],
            "startDate = '" . $zimmerbuchung[ 'startDate' ] . "'",
            "nights = " . $zimmerbuchung[ 'nights' ],
            "otaRatesConfigId = " . $zimmerbuchung[ 'otaRatesConfigId' ]
        );

        $this->_tabelleHotelbuchung->update($update, $where);

        return;
    }

} // end class

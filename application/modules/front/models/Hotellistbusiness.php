<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 01.09.11
 * Time: 10:17
 * To change this template use File | Settings | File Templates.
 */

class Front_Model_Hotellistbusiness
{

    // Tabellen / Views / Datenbanken
    private $_db_hotel;
    private $_db_front;
    private $_viewHotelkapazitaet = null;

    // Fehler


    // Konditionen
    private $_condition_hotel_hat_keine_kapazitaet = 0;
    private $_condition_keine_ueberbuchung_moeglich = 1;
    private $_condition_ueberbuchung_moeglich = 2;
    private $_condition_beliebige_anzahl_betten = 1000;
    private $_condition_hotel_ist_aktiv = 3;
    private $_condition_rate_ist_aktiv = 3;

    protected $_datenSuchanfrage = array();

    public function __construct()
    {
        $this->_db_hotel = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');

        /** @var _viewHotelkapazitaet Application_Model_DbTable_viewHotelkapazitaet */
        $this->_viewHotelkapazitaet = new Application_Model_DbTable_viewHotelkapazitaet(array('db' => 'hotels'));
    }

    public function getHotelsInEinerStadt()
    {
        $hotelsInEinerStadt = $this->_suchenDerHotelsInEinerStadt();

        return $hotelsInEinerStadt;
    }

    /**
     * Ermittelt die Bettenkapazität in einem Hotel
     * Hotels welche keine oder ungenügende Kapazität haben
     * werden aus der Liste entfernt
     *
     * @param $__hotelsInEinerStadt
     * @return array
     */
    public function getHotelsMitBeschreibung($__hotelsInEinerStadt)
    {

        // ermittelt die verfügbaren Betten des Hotel
        $verfuegbareBettenDerHotelsEinerStadt = $this->_ermittelnVerfuegbarkeitenDerHotelsInEinerStadt($__hotelsInEinerStadt);

        // wenn mehr Personen als Bettenkapazität
        $hotels = $this->_mehrPersonenAlsVerfuegbareBetten($verfuegbareBettenDerHotelsEinerStadt);

        // wenn Hotel keine Kapazität hat
        $hotels = $this->_entfernenHotelsOhneKapazitaet($hotels);

        // Hotelbeschreibung
        $hotelsMitBeschreibung = $this->_ermittleBeschreibungDerHotels($hotels);

        return $hotelsMitBeschreibung;
    }

    public function setDatenDerSuchanfrage($suchparameter)
    {

        $datumsTeile = explode('.', $suchparameter['from']);
        $suchDatum = $datumsTeile[2] . '-' . $datumsTeile[1] . '-' . $datumsTeile[0];

        $this->_datenSuchanfrage = $suchparameter;
        $this->_datenSuchanfrage['suchdatum'] = nook_Tool::erstelleSuchdatumAusFormularDatum($suchparameter['from']);

        return $this->_datenSuchanfrage;
    }

    /**
     * Ermittelt die Beschreibung der Hotels
     *
     * @param $__hotels
     * @return array
     */
    private function _ermittleBeschreibungDerHotels($__hotels)
    {
        $translate = new Zend_Session_Namespace('translate');
        $sprache = $translate->language;
        $hotelsMitBeschreibung = array();

        if($sprache == 'eng')
            $sprache = "en";

        for ($i = 0; $i < count($__hotels); $i++) {

            $sql = "
                SELECT
                    `tbl_properties`.`property_name` AS `ueberschrift`
                    , `tbl_property_details`.`description_" . $sprache . "` as description
                FROM
                    `tbl_properties`
                    INNER JOIN `tbl_property_details`
                        ON (`tbl_properties`.`id` = `tbl_property_details`.`properties_id`)
                WHERE (`tbl_properties`.`id` = " . $__hotels[$i]['id'] . ")";

            $hotelbeschreibung = $this->_db_hotel->fetchRow($sql);

            if ($hotelbeschreibung) {
                $hotelbeschreibung['description'] = nook_Tool::trimLongTextStandard($hotelbeschreibung['description']);
                $hotelsMitBeschreibung[$i] = array_merge($__hotels[$i], $hotelbeschreibung);
            } else
                $hotelsMitBeschreibung[$i] = $__hotels[$i];
        }

        return $hotelsMitBeschreibung;
    }

    /**
     * Entfernt die Hotels wo die Personenanzahl
     * die buchen will größer ist als die Kapazität
     *
     * @param $__hotels
     * @return array
     */
    private function _mehrPersonenAlsVerfuegbareBetten($__hotels)
    {

        foreach ($__hotels as $key => $items) {
            if ($items['bettenVerfuegbar'] < $this->_datenSuchanfrage['adult']) {
                unset($__hotels[$key]);
            }
        }

        // Array der Hotels mit Kapazität
        $hotelsMitKapazitaet = array_merge($__hotels);

        return $hotelsMitKapazitaet;
    }

    /**
     * Entfernt Hotels ohne Kapazität.
     *
     * @param $__hotels
     * @return array
     */
    private function _entfernenHotelsOhneKapazitaet($__hotels)
    {

        foreach ($__hotels as $key => $items) {
            if ($items['bettenVerfuegbar'] == 0) {
                unset($__hotels[$key]);
            }
        }

        // Array der Hotels mit Kapazität
        $hotelsMitKapazitaet = array_merge($__hotels);

        return $hotelsMitKapazitaet;
    }

    /**
     * Berechnet verfügbare Ratenkapazität.
     *
     * Verwendet die View 'view_kapazitaet'
     *
     * @param $__hotels
     * @return array
     */
    private function _ermittelnVerfuegbarkeitenDerHotelsInEinerStadt($__hotels)
    {

        // verfügbare Betten des Hotels über den gesamten Zeitraum
        $select = $verfuegbareBettenHotel = $this->_viewHotelkapazitaet->select();

        $cols = array(
            'roomlimit' => new Zend_Db_Expr("MIN(roomlimit)"),
            'property_id',
            'rates_config_id',
            'standard_persons'
        );

        $whereCity = "city_id = " . $this->_datenSuchanfrage['city'];
        $whereTagVorAnreise = new Zend_Db_Expr("DATE_SUB('" . $this->_datenSuchanfrage['suchdatum'] . "',Interval 1 Day)");
        $whereAbreisedatum = new Zend_Db_Expr("DATE_ADD('" . $this->_datenSuchanfrage['suchdatum'] . "',Interval " . $this->_datenSuchanfrage['days'] . " day )");

        $select
            ->from($this->_viewHotelkapazitaet, $cols)
            ->where($whereCity)
            ->where("datum > " . $whereTagVorAnreise)
            ->where("datum < " . $whereAbreisedatum)
            ->group('property_id')
            ->group('rates_config_id');

        $query = $select->__toString();

        $kapazitaetDerRatenDerHotelsEinerStadt = $this->_viewHotelkapazitaet->fetchAll($select)->toArray();
        $__hotels = $this->_berechneKapazitaetHotel($__hotels, $kapazitaetDerRatenDerHotelsEinerStadt);

        return $__hotels;
    }

    /**
     * 3 Zustände Kapazität eines Hotels
     *
     * Ermittelt die 3 möglichen
     * Zustände der Hotels einer Stadt
     *
     * @param $__hotels
     * @param $__kapazitaetDerRatenDerHotelsEinerStadt
     * @return array
     */
    private function _berechneKapazitaetHotel($__hotels, $__kapazitaetDerRatenDerHotelsEinerStadt)
    {
        // summierte Kapazität der Hotels einer Stadt
        $kapazitaetHotel = array();

        foreach ($__kapazitaetDerRatenDerHotelsEinerStadt as $zaehler => $hotelRate) {
            $kapazitaetHotel[$hotelRate['property_id']] += $hotelRate['roomlimit'] * $hotelRate['standard_persons'];
        }

        // 3 varianten der Kapazität
        for ($i = 0; $i < count($__hotels); $i++) {

            // keine Betten vorhanden und keine Überbuchung
            if (!array_key_exists($__hotels[$i]['id'], $kapazitaetHotel) and $__hotels[$i]['overbook'] == $this->_condition_keine_ueberbuchung_moeglich)
                $__hotels[$i]['bettenVerfuegbar'] = $this->_condition_hotel_hat_keine_kapazitaet;
            // Überbuchung immer möglich
            elseif ($__hotels[$i]['overbook'] == $this->_condition_ueberbuchung_moeglich)
                $__hotels[$i]['bettenVerfuegbar'] = $this->_condition_beliebige_anzahl_betten; // Betten vorhanden
            elseif (array_key_exists($__hotels[$i]['id'], $kapazitaetHotel))
                $__hotels[$i]['bettenVerfuegbar'] = $kapazitaetHotel[$__hotels[$i]['id']];

        }

        return $__hotels;
    }

    private function _suchenDerHotelsInEinerStadt()
    {
        $sql = "select id, overbook from tbl_properties where city_id = " . $this->_datenSuchanfrage['city'] . " and aktiv = " . $this->_condition_hotel_ist_aktiv;
        $hotels = $this->_db_hotel->fetchAll($sql);

        return $hotels;
    }
}
 

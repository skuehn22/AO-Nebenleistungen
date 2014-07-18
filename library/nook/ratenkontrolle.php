<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 22.03.12
 * Time: 11:26
 *
 * Klasse zur Kontrolle der Ratenlogik eines Hotels
 */

class nook_ratenkontrolle
{

    private $_hotelList = array(); // Liste der ermittelten Hotels
    private $_checkedHotels = array(); // Liste der kontrollierten Hotels
    private $_ratesHotel = array(); // Raten eines Hotels
    private $_db_hotels; // des Übernachtungszeitraumes

    private $_condition_rate_is_activ = 3;
    private $_startDate;

    /**
     * speichert den Datenbank Adapter für die Hotel Datenbank
     *
     */
    public function __construct()
    {
        $this->_db_hotels = Zend_Registry::get('hotels');

        return;
    }

    /**
     * Übernimmt das Startdatum der Übernachtung entsprechend der Anzeigesprache
     *
     * + automatisches ermitteln des Datumsformates
     *
     * @param $__startDate
     * @return
     */
    public function setStartDate($__startDate)
    {
        $language = Zend_Registry::get('language');

        if(strstr($__startDate, '.')){
            $teile = explode('.', $__startDate);
            $this->_startDate = $teile[2] . "-" . $teile[1] . "-" . $teile[0];
        }
        elseif ($language == 'de') {
            $teile = explode('.', $__startDate);
            $this->_startDate = $teile[2] . "-" . $teile[1] . "-" . $teile[0];
        }
        else {
            $teile = explode('/', $__startDate);
            $this->_startDate = $teile[2] . "-" . $teile[0] . "-" . $teile[1];
        }

        return;
    }

    /**
     * Übernimmt die Hotelliste und speichert diese
     *
     * @param $__hotelList
     * @return
     */
    public function setHotelList(array $__hotelList)
    {
        $this->_hotelList = $__hotelList;

        return;
    }

    /**
     * Gibt ein Array zurück.
     * Dieses Array beinhaltet die kontrollierten Hotels der Stadt
     * Es existiert mindestens eine definierte Rate
     * Diese Rate hat für das Startdatum einen gültigen Preis
     *
     * @return array
     */
    public function getKontrollierteListeDerHotels()
    {
        $this->_controllingCorrectHotelRates();

        return $this->_checkedHotels;
    }


    /**
     * Kontrolliert ob das Hotel aktive Raten hat.
     *
     * @return
     */
    private function _controllingCorrectHotelRates()
    {
        $checkedHotels = array();

        foreach ($this->_hotelList as $key => $hotel) {

            $sql = "
                SELECT
                    `tbl_ota_rates_config`.`properties_id`
                    , `tbl_ota_rates_config`.`aktiv`
                    , `tbl_ota_rates_config`.`rate_code`
                    , `tbl_properties`.`property_code`
                FROM
                    `tbl_ota_rates_config`
                    INNER JOIN `tbl_properties`
                        ON (`tbl_ota_rates_config`.`properties_id` = `tbl_properties`.`id`)
                WHERE (`tbl_ota_rates_config`.`properties_id` = " . $hotel['id'] . "
                    AND `tbl_ota_rates_config`.`aktiv` = " . $this->_condition_rate_is_activ . ")";

            $raten = $this->_db_hotels->fetchAll($sql);
            if (count($raten) > 0) {
                $this->_ratesHotel = $raten;

                $this->_controllingRatesHasPrice($hotel);
            }
        }

        return;
    }

    /**
     * Kontrolliert ob die Raten bezüglich des Startdatums einen Preis haben.
     *
     * @param $__hotel
     * @return
     */
    private function _controllingRatesHasPrice($__hotel)
    {
        $anzahl = 0;

        foreach ($this->_ratesHotel as $key => $rate) {
            $sql = "
                SELECT
                    count(`datum`) as anzahl
                FROM
                    `tbl_ota_prices`
                WHERE (`datum` = '" . $this->_startDate . "'
                    AND `rate_code` = '" . $rate['rate_code'] . "'
                    AND `hotel_code` = '" . $rate['property_code'] . "')";

            $anzahl += $this->_db_hotels->fetchOne($sql);
        }

        if ($anzahl > 0)
            $this->_checkedHotels[] = $__hotel;

        return;
    }

}

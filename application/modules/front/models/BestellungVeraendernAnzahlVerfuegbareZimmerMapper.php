<?php
/**
 * Mapper der Front_Model_veraendenDerAnzahlVerfuegbareZimmerAction
 *
 * + Kontrolle der Hotelbuchungen
 *
 * @author stephan.krauss
 * @date 10.06.13
 * @file BestellungVeraendernAnzahlVerfuegbareZimmerMapper.php
 * @package front
 * @subpackage controller | model | mapper
 */
class Front_Model_BestellungVeraendernAnzahlVerfuegbareZimmerMapper implements Front_Model_BestellungVeraendernAnzahlVerfuegbareZimmerMapperInterface{

    // Fehler / Informationen
    private $error_anfangswerte_fehlen = 1610;
    private $error_typ_des_anfangswertes_falsch = 1611;

    protected $hotelBuchungen = null;

    function __construct ($hotelBuchungen)
    {
        $this->hotelBuchungen = $hotelBuchungen;
    }

    /**
     * Überprüft die Hotelbuchungsdaten
     *
     * @return Front_Model_BestellungVeraendernAnzahlVerfuegbareZimmerMapper
     */
    public function start(){
        for($i=0; $i < count($this->hotelBuchungen); $i++){
            $this->checkDaten($this->hotelBuchungen[$i]);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getHotelBuchungen ()
    {
        return $this->hotelBuchungen;
    }

    /**
     * Prüft die ankommenden Daten der Hotelbuchungen
     *
     * @param $buchung
     * @return mixed
     * @throws nook_Exception
     */
    private function checkDaten($hotelBuchung)
    {
        $hotelBuchung['roomNumbers'] = (int) $hotelBuchung['roomNumbers'];
        $hotelBuchung['nights'] = (int) $hotelBuchung['nights'];

        if(!isset($hotelBuchung['startDate']) or !isset($hotelBuchung['otaRatesConfigId']))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(!isset($hotelBuchung['roomNumbers']) or !isset($hotelBuchung['nights']))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($hotelBuchung['roomNumbers']) or empty($hotelBuchung['nights']))
            throw new nook_Exception($this->error_typ_des_anfangswertes_falsch);

        if(!is_int($hotelBuchung['roomNumbers']) or !is_int($hotelBuchung['nights']))
            throw new nook_Exception($this->error_typ_des_anfangswertes_falsch);

        return $hotelBuchung;
    }

} // end class

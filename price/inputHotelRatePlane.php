<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 17.08.11
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

class inputHotelRatePlane{
    protected $_xmlInhalt;
    private $_db_connect;
    private $_db_ergebnis;
    private $_sqlDump;

    private $_startDatum;
    private $_endDatum;

    private $_zeitraum = array();

    private $_insert = array();
    private $_verpflegung = array();

    public $debug = false;


    public function __construct($debug = false){
        $this->debug = $debug;

        set_time_limit(0);
        $this->_db_connect = mysqli_connect('localhost', 'db1154036-hotel', 'HuhnHotelsHuhn');
		mysqli_select_db($this->_db_connect, 'db1154036-hotels') or die( "keine Verbindung zur Datenbank");
    }

    public function einlesenXmlDatei($eingabeDateiName){
        $this->_xmlInhalt = simplexml_load_file($eingabeDateiName);

        return;
    }

    public function startFuellenTabellePrices(){

        // Hotel
        for($i=0; $i<count($this->_xmlInhalt->AvailStatusMessages); $i++){
            $this->_insert['hotel_code'] = (string) $this->_xmlInhalt->AvailStatusMessages[$i]['HotelCode'];

            // Rate des Hotel
            foreach($this->_xmlInhalt->AvailStatusMessages[$i] as $zeitraumEinerRate){
                $this->_insert['rate_code'] = (string) $zeitraumEinerRate['RatePlanCode'];
                $this->_insert['category_code'] = (string) $zeitraumEinerRate['InvTypeCode'];
                $this->_insert['pricePerPerson'] = (string) $zeitraumEinerRate->Rates->Rate['PerPerson'];
                if(empty($this->_insert['pricePerPerson']))
                    $this->_insert['pricePerPerson'] = 'false';

                $this->_insert['amount'] = (string) $zeitraumEinerRate->Rates->Rate['Amount'];
                $this->_insert['release_from'] = (string) $zeitraumEinerRate->ReleaseFrom;
                $this->_insert['release_to'] = (string) $zeitraumEinerRate->ReleaseTo;

                // minimaler und maximaler Aufenthalt
                $this->_berechneMinMaxAufenthalt($zeitraumEinerRate);

                // Berechnung Zeitraum und Wochentag
                $this->_startDatum = (string) $zeitraumEinerRate->StatusApplicationControl['Start'];
                $this->_endDatum = (string) $zeitraumEinerRate->StatusApplicationControl['End'];
                $this->_berechneDatumUndWochentag($zeitraumEinerRate);

                // welche Tage werden geupdatet
                $this->_statusControlRate($zeitraumEinerRate->StatusApplicationControl);

                // eintragen Grundwerte der Rate
                // $this->_eintragenGrunddatenEinerRate();

                // Tage mit Restriktionen
                $this->_tageMitRestriktionen($zeitraumEinerRate->DOW_Restrictions);

                // TODO: Verpflegung noch nicht implementiert
                // $this->_uebernahmeVariantenMoeglicheVerpflegungsarten($tagesinformationEinerRate->BoardTypes);

                /**** eintragen der Werte in ota_prices ****/
                $this->_loeschenDerRatenEinesHotels();
                $this->_eintragenPreiseEinerRate();

                /*** ergaenzen der Tabellen ***/
                // Tabelle 'ota_rates_config'
                // $this->_ergaenzeTabelleOtaRatesConfig();
                
                // Tabelle 'ota_prices'
                $this->_ergaenzeTabelleOtaPrices();

                
            }

            unset($this->_insert);
            $this->_insert = array();
        }
    }

    private function _ergaenzeTabelleOtaPrices(){
        // $sql = "update ota_rates_config set `ota_rates_config`.`properties_id` = (select `properties`.`id` from `properties` where `ota_rates_config`.`hotel_code` = `properties`.`property_code`)";
        $sql = "update `ota_prices` set `ota_prices`.`rates_config_id` = (select `ota_rates_config`.`id` from `ota_rates_config` where `ota_rates_config`.`rate_code` = `ota_prices`.`rate_code` and `ota_rates_config`.`hotel_code` = `ota_prices`.`hotel_code`)";
        $this->_sqlDump = $sql;
        $this->_mysqliQuery();
    }

    private function _ergaenzeTabelleOtaRatesConfig(){
        $sql = "update ota_rates_config set `ota_rates_config`.`properties_id` = (select `properties`.`id` from `properties` where `ota_rates_config`.`hotel_code` = `properties`.`property_code`)";
        $this->_sqlDump = $sql;
        $this->_mysqliQuery();
    }

    private function _eintragenGrunddatenEinerRate(){
        $sql = "select count(id) as anzahl from ota_rates_config where hotel_code = '".$this->_insert['hotel_code']."' and rate_code = '".$this->_insert['rate_code']."'";
        $this->_sqlDump = $sql;
        $this->_mysqliQuery('eintragenGrunddatenEinerRate');
        $ergebnis = $this->_getErgebnis();
        if(empty($ergebnis[0]['anzahl'])){
            $sql = "insert into ota_rates_config set hotel_code = '".$this->_insert['hotel_code']."', rate_code = '".$this->_insert['rate_code']."'";
            $this->_sqlDump = $sql;
            $this->_mysqliQuery('eintragenGrunddatenEinerRate');
        }


        return;
    }

    private function _getErgebnis(){
        $i = 0;
        $ergebnis = array();
        while($row = mysqli_fetch_array($this->_db_ergebnis, MYSQLI_ASSOC)){
            $ergebnis[$i] = $row;
            $i++;
        }

        return $ergebnis;
    }

    private function _loeschenDerRatenEinesHotels(){

        foreach($this->_zeitraum as $datum => $datumsZusatzInformationen){
            // NOTICE: Achtung möglicher Fehler
            // $sql = "delete from ota_prices where hotel_code = '".$this->_insert['hotel_code']."' and category_code = '".$this->_insert['category_code']."' and datum = '".$datum."'";
            $sql = "delete from ota_prices where hotel_code = '".$this->_insert['hotel_code']."' and rate_code = '".$this->_insert['rate_code']."' and datum = '".$datum."'";
            $this->_sqlDump = $sql;


            $this->_mysqliQuery('loeschenDerRatenEinesHotels');
        }


        return;
    }

    private function _eintragenPreiseEinerRate(){
        $wert = $this->_insert;
        $zeitraum = $this->_zeitraum;

        $sql = "insert into ota_prices (hotel_code, category_code, datum, amount, min_stay, max_stay, allowed_arrival, allowed_departure, release_from, release_to, rate_code, pricePerPerson ) values ";
        foreach($this->_zeitraum as $datum => $tagesZusatzInformationen){
            $sql .= "('".$this->_insert['hotel_code']."', '".$this->_insert['category_code']."', '".$datum."', '".$this->_insert['amount']."', '".$this->_insert['min_stay']."', '".$this->_insert['max_stay']."', '".$tagesZusatzInformationen['allowed_arrival']."', '".$tagesZusatzInformationen['allowed_departure']."', '".$this->_insert['release_from']."', '".$this->_insert['release_to']."', '".$this->_insert['rate_code']."', '".$this->_insert['pricePerPerson']."'),";
        }

        $sql = substr($sql, 0, -1);
        $this->_sqlDump = $sql;
        $this->_mysqliQuery('eintragenPreiseEinerRate');

        return;
    }



    private function _mysqliQuery($__sqlOrt = 'unbekannt'){

        if($this->debug){
            echo "inputHotelRatePlane -> ".$__sqlOrt."<br>";
            echo $this->_sqlDump."<br>";
            echo "<hr>";
        }

        if(!$this->_db_ergebnis = mysqli_query($this->_db_connect, $this->_sqlDump))
            $this->_mysqlError($__sqlOrt);

        return;
    }

    private function _mysqlError($__sqlOrt){
        if(!empty($this->debug))
            echo "Fehler: ".$__sqlOrt." , ".$this->_insert['hotel_code']." , ".$this->_insert['category_code']."<br>";

        return;
    }

    private function _uebernahmeVariantenMoeglicheVerpflegungsarten($__verpflegungstypen){

        // NOTICE: Boardtypes für alle Tage ???

        unset($this->_verpflegung);
        $this->_verpflegung = array();

        foreach($this->_zeitraum as $key => $value){
            for($i=0; $i<count($__verpflegungstypen->BoardType); $i++){
                $this->_verpflegung[$key][$i]['code'] = (string) $__verpflegungstypen->BoardType[$i]['code'];
                $this->_verpflegung[$key][$i]['available'] = (string) $__verpflegungstypen->BoardType[$i]['available'];
                $this->_verpflegung[$key][$i]['included'] = (string) $__verpflegungstypen->BoardType[$i]['included'];
            }
        }

        return;
    }

    private function _statusControlRate($__statusApplicationControl){
        foreach($this->_zeitraum as $key => $value){
            $this->_zeitraum[$key]['use_rate_avaibility'] = (string) $__statusApplicationControl[$this->_zeitraum[$key]['Wochentag']];
            if((string) $__statusApplicationControl[$this->_zeitraum[$key]['Wochentag']] != '1')
                unset($this->_zeitraum[$key]);
        }

        return;
    }

    private function _tageMitRestriktionen($__restriktionenDerRate){

        foreach($this->_zeitraum as $key => $value){
            $this->_zeitraum[$key]['allowed_arrival'] = (string) $__restriktionenDerRate->ArrivalDaysOfWeek[$this->_zeitraum[$key]['Wochentag']];
            $this->_zeitraum[$key]['allowed_departure'] = (string) $__restriktionenDerRate->DepartureDaysOfWeek[$this->_zeitraum[$key]['Wochentag']];
        }

        return;
    }

    /**
     * Berechnet die minimale und maximale Aufenthaltsdauer der Rate
     *
     * @param $__zeitraumEinerRate
     * @return
     */
    private function _berechneMinMaxAufenthalt($__zeitraumEinerRate){
        for($i=0; $i<count($__zeitraumEinerRate->LengthsOfStay->LengthOfStay); $i++){
            if($__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['MinMaxMessageType'] == 'SetMinLOS'){
                $this->_insert['min_stay'] = (string) $__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['Time'];
                $min = (string) $__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['Time'];
            }
            if($__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['MinMaxMessageType'] == 'SetMaxLOS'){
                $this->_insert['max_stay'] = (string) $__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['Time'];
                $max = (string) $__zeitraumEinerRate->LengthsOfStay->LengthOfStay[$i]['Time'];
            }
        }

        return;
    }

    private function _berechneDatumUndWochentag($__tagesinformationEinerRate){

        unset($this->_zeitraum);
        $this->_zeitraum = array();

        $teileStartDatum = explode('-', $this->_startDatum);
        $unixStartDatum = mktime(0,0,1,$teileStartDatum[1],$teileStartDatum[2],$teileStartDatum[0]);
        $wochentagStartdatum = date('D', $unixStartDatum);
        $wochentagStartdatum = $this->_korrekturWochentag($wochentagStartdatum);
        $this->_zeitraum[$this->_startDatum]['Wochentag'] = $wochentagStartdatum;

        if($this->_startDatum != $this->_endDatum){
            $teileEndDatum = explode('-', $this->_endDatum);
            $unixEndDatum = mktime(0,0,1,$teileEndDatum[1],$teileEndDatum[2],$teileEndDatum[0]);

            $i = $unixStartDatum + 86400;
            for($i ; $i <= $unixEndDatum; $i += 86400){
                $aktuellesDatum = date("Y-m-d", $i);
                $wochentagAktuellesDatum = date('D', $i);
                $wochentagAktuellesDatum = $this->_korrekturWochentag($wochentagAktuellesDatum);

                $this->_zeitraum[$aktuellesDatum]['Wochentag'] = $wochentagAktuellesDatum;
            }
        }

        return;
    }

    private function _korrekturWochentag($__wochentag){
        if($__wochentag == 'Wed')
            $__wochentag = 'Weds';
        if($__wochentag == 'Thu')
            $__wochentag = 'Thur';

        return $__wochentag;
    }
}

//$hotelRateplane = new inputHotelRatePlane();
//$hotelRateplane->einlesenXmlDatei('HotelRatePlanNotif_17_08_2011.xml');
//$hotelRateplane->startFuellenTabellePrices();
 

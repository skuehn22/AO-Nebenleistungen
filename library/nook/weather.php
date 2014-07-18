<?php

class nook_weather{

    public function website_wetter($sprache="de", $ort=""){

        $station = $ort;
        $api = simplexml_load_string(utf8_encode(file_get_contents("http://www.google.com/ig/api?weather=".$station."&hl=".$sprache)));

        $wetter = array();

        // Allgemeine Informationen
        $wetter['stadt'] = $api->weather->forecast_information->city->attributes()->data;
        $wetter['datum'] = $api->weather->forecast_information->forecast_date->attributes()->data;
        $wetter['zeit'] = $api->weather->forecast_information->current_date_time->attributes()->data;

        // Aktuelles Wetter
        $wetter[0]['zustand'] = "".$api->weather->current_conditions->condition->attributes()->data;
        $wetter[0]['temperatur'] = "".$api->weather->current_conditions->temp_c->attributes()->data;
        $wetter[0]['luftfeuchtigkeit'] = "".$api->weather->current_conditions->humidity->attributes()->data;
        $wetter[0]['wind'] = "".$api->weather->current_conditions->wind_condition->attributes()->data;
        $wetter[0]['icon'] = "www.google.de".$api->weather->current_conditions->icon->attributes()->data;

        // Wettervorhersage heute, morgen, in zwei und in drei Tagen ($wetter[1] bis $wetter[4])
//        $i = 1;
//        foreach($api->weather->forecast_conditions as $weather)
//        {
//            $wetter[$i]['wochentag'] = $weather->day_of_week->attributes()->data;
//            $wetter[$i]['zustand'] = $weather->condition->attributes()->data;
//            $wetter[$i]['tiefsttemperatur'] = $weather->low->attributes()->data;
//            $wetter[$i]['hoechsttemperatur'] = $weather->high->attributes()->data;
//            $wetter[$i]['icon'] = str_replace($icons_google, $icons_src, $weather->icon->attributes()->data);
//
//            $i++;
//        }

        return $wetter;
    }
    
}

?>
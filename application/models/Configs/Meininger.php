<?php
/**
 * Konfigurationsparameter Schnittstelle Meininger
 *
 * @author Stephan Krauss
 * @date 30.05.2014
 * @file Meininger.php
 * @project HOB
 * @package config
 */
 class Application_Model_Configs_Meininger
 {
     /**
      * @return string
      */
     public function getKey()
     {
         return 'RES876HSR';
     }

     /**
      * @return string
      */
     public function getUrlErweiterung()
     {
         return 'rq_getavailability';
     }

     /**
      * Konfigurationsdaten Hotels Meininger
      *
      * @return array
      */
     public function getDataHotelsMeininger()
     {
         $hotelsMeininger = array(
    //    1000 => array(
    //        'name' => 'Test System',
    //        'code' => 'OO',
    //        'port' => '8080',
    //        'cityId' => 1
    //    ),
    //    1001 => array(
    //        'name' => 'Amsterdam City West',
    //        'code' => 'OP',
    //        'port' => '8083',
    //        'cityId' => 1
    //    ),
             42 => array(
                 'name' => 'Hotel Berlin Airport',
                 'code' => 'AM',
                 'port' => '8080',
                 'cityId' => 1
             ),
//        28 => array(
//            'name' => 'Berlin Mitte Humboldthaus',
//            'code' => 'OS',
//            'port' => '8085',
//            'cityId' => 1
//        ),
    //    43 => array(
    //        'name' => 'Berlin Prenzlauer Berg',
    //        'code' => 'SP',
    //        'port' => '8086',
    //        'cityId' => 1
    //    ),
    //    25 => array(
    //        'name' => 'Berlin Central Station',
    //        'code' => 'WP',
    //        'port' => '8088',
    //        'cityId' => 1
    //    ),
    //    1006 => array(
    //        'name' => 'Brussels City Center',
    //        'code' => 'QH',
    //        'port' => '8099',
    //        'cityId' => 1
    //    ),
    //    1007 => array(
    //        'name' => 'Cologne City Center',
    //        'code' => 'ES',
    //        'port' => '8089',
    //        'cityId' => 1
    //    ),
    //    1008 => array(
    //        'name' => 'Frankfurt Airport',
    //        'code' => 'BC',
    //        'port' => '8090',
    //        'cityId' => 1
    //    ),
    //    1009 => array(
    //        'name' => 'Frankfurt Convention Center',
    //        'code' => 'EA',
    //        'port' => '8091',
    //        'cityId' => 1
    //    ),
    //    37 => array(
    //        'name' => 'Hamburg City Center',
    //        'code' => 'GA',
    //        'port' => '8092',
    //        'cityId' => 4
    //    ),
    //    1011 => array(
    //        'name' => 'London Hyde Park',
    //        'code' => 'QG',
    //        'port' => '8093',
    //        'cityId' => 1
    //    ),
    //    34 => array(
    //        'name' => 'Munich City Center',
    //        'code' => 'LS',
    //        'port' => '8094',
    //        'cityId' => 6
    //    ),
    //    1012 => array(
    //        'name' => 'Salzburg City Center',
    //        'code' => 'FS',
    //        'port' => '8095',
    //        'cityId' => 1
    //    ),
    //    1012 => array(
    //        'name' => 'Vienna City Center',
    //        'code' => 'CG',
    //        'port' => '8096',
    //        'cityId' => 1
    //    ),
    //    69 => array(
    //        'name' => 'Vienna Downtown Franz',
    //        'code' => 'RS',
    //        'port' => '8097',
    //        'cityId' => 1
    //    ),
    //    40 => array(
    //        'name' => 'Vienna Downtown Sissi',
    //        'code' => 'SG',
    //        'port' => '8098',
    //        'cityId' => 8
    //    )
         );

         return $hotelsMeininger;
    }

     /**
      * IP des Server Meininger
      *
      * @return string
      */
     public function getMeiningerIp()
    {
        return '178.23.123.23';
    }

     /**
      * Vereinbarte Raten Hotelgruppe Meininger
      *
      * @return array
      */
     public function getVereinbarteRaten()
    {
        $vereinbarteRaten = array(
            'Y.SGL',
            'Y.TWN',
            'Y.MUL'
        );

        return $vereinbarteRaten;
    }


 
 } 
<?php
class nook_Tool
{

    private static $error_no_control_update_table = 140;
    private static $error_not_correct_role_id = 141;

    // generiert ein zufälliges Passwort
    public static function passwordGenerator($length = 8)
    {
        $password = null;

        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ_-!?";
        $maxlength = strlen($possible);

        if ($length > $maxlength) {
            $length = $maxlength;
        }

        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }

        return $password;
    }

    // Kontrolle der Eingabeparameter auf Vorhandensein / Kontrolle 2
    public static function kontrolleEingabeparameter($__eingabeParameter)
    {
        $kontrolle = true;

        foreach ($__eingabeParameter as $key => $value) {
            if ($value === false) {
                $kontrolle = $value;
                break;
            }
        }

        return $kontrolle;
    }

    // Umrechnung Netto in Brutto
    public static function addVat($__details)
    {
        $vat = 1;

        if ($__details['Mehrwertsteuer'] == 'A')
            $vat = 1.19;
        elseif ($__details['Mehrwertsteuer'] == 'B')
            $vat = 1.07; elseif ($__details['Mehrwertsteuer'] == 'C')
            $vat = 1.0; else
            $vat = 1.0;

        $__details['Verkaufspreis'] *= $vat;
        $__details['Verkaufspreis'] = number_format($__details['Verkaufspreis'], 2);

        return $__details;
    }

    // Umrechnung Netto in Brutto
    public static function calculateNettoBrutto($__mwstTyp, $__nettoPrice)
    {
        $vat = 1;

        if ($__mwstTyp == 'A')
            $vat = 1.19;
        elseif ($__mwstTyp == 'B')
            $vat = 1.07;

        $brutto = $__nettoPrice * $vat;
        $brutto = number_format($brutto, 2);

        return $brutto;
    }

    // Berechnung der Mehrwertsteuer entsprechen Buchstabenvorgabe
    public static function calculateVat($__mwstTyp, $__bruttoPrice)
    {
        $vat = 1;
        if ($__mwstTyp == 'A')
            $vat = 1.19;
        elseif ($__mwstTyp == 'B')
            $vat = 1.07;

        $nettoPrice = $__bruttoPrice / $vat;
        $diffBruttoNetto = $__bruttoPrice - $nettoPrice;
        $diffBruttoNetto = number_format($diffBruttoNetto, 2);

        return $diffBruttoNetto;
    }

    // erstellt Unix Datum
    public static function buildTime($__date)
    {
        $__date = trim($__date);
        $myItemsOfDate = explode(".", $__date);

        $unixTime = mktime(0, 0, 0, $myItemsOfDate[1], $myItemsOfDate[0], $myItemsOfDate[2]);

        return $unixTime;
    }

    // erstellt das deutsche Datum
    public static function buildGermanDateNow()
    {
        $aktuellesDeutschesDatum = date("d.m.Y", time());

        return $aktuellesDeutschesDatum;
    }

    public static function buildTimeFromCompleteDate($__date)
    {
        $__date = trim($__date);
        $itemsOfDate = explode(" ", $__date);
        $date = explode('-', $itemsOfDate[0]);

        $unixTime = self::buildTime($date[2] . "." . $date[1] . "." . $date[0]);

        return $unixTime;
    }

    // finden einer Sprache-ID mittels einer Kurzbeschreibung
    public static function findLanguage()
    {
        $db = Zend_Registry::get('front');
        $sql = "select Sprache_id from tbl_sprache where short_language = '" . Zend_Registry::get('language') . "'";
        $language_id = $db->fetchOne($sql);

        return $language_id;
    }

    // findet die Flagge entsprechend der Sprache
    public static function findFlagForProgLanguage($__languageId)
    {
        $db = Zend_Registry::get('front');
        $sql = "select flag from tbl_prog_sprache where id = '" . $__languageId . "'";
        $flagLanguage = $db->fetchOne($sql);

        return $flagLanguage;
    }

    /**
     * kürzt einen langen Text einer
     * Programmbeschreibung
     *
     * @param $__events
     * @return mixed
     */
    public static function trimLongText($__events)
    {
        $borderTrimLongText = Zend_Registry::get('static')->items->trimLongText;
        $borderTrimLongText = intval($borderTrimLongText);

        for ($i = 0; $i < count($__events); $i++) {
            if (!empty($__events[$i]['noko_kurz']))
                $__events[$i]['txt'] = $__events[$i]['noko_kurz'];

            if (!empty($__events[$i]['txt']) and (strlen($__events[$i]['txt']) > $borderTrimLongText))
                $__events[$i]['txt'] = substr($__events[$i]['txt'], 0, $borderTrimLongText);

            $__events[$i]['txt'] .= " ...";
            unset($__events[$i]['noko_kurz']);

           // $__events[$i]['txt'] = preg_replace("(<[a-zA-Z\/]+>)", '', $__events[$i]['txt']);
        }

        return $__events;
    }

    // schneidet einen langen Text zurecht
    public static function trimLongTextStandard($__text, $__laenge = false)
    {

        if (empty($__laenge)) {
            $kappungsGrenzeWoerter = Zend_Registry::get('static')->items->trimLongText;
            $kappungsGrenzeWoerter = intval($kappungsGrenzeWoerter);
        } else {
            $kappungsGrenzeWoerter = $__laenge;
        }

        $words = preg_split('#\s#', $__text);
        if ($kappungsGrenzeWoerter > count($words))
            return $__text;

        $gekuerzterText = implode(array_slice($words, 0, $kappungsGrenzeWoerter), ' ');

        return $gekuerzterText . " ...";
    }

    // aufsplitten eines deutschen Datums
    public static function splitGermanedate($__date)
    {
        $items = explode(".", $__date);

        return $items;
    }

    // wandelt ein deutsche Datum in MySQL Date
    public static function buildEnglishDateFromGermanDate($__germanDate)
    {
        $teile = explode('.', $__germanDate);

        $englishDate = $teile[2] . '-' . $teile[1] . '-' . $teile[0];

        return $englishDate;
    }

    // baut aus einem deutschen Datum ein englisches Datum
    public static function splitGermanDateToEnglishDate($__date)
    {
        $items = explode(".", $__date);

        return $items[2] . "-" . $items[1] . "-" . $items[0];
    }

    /**
     * Verändert die Darstellung des Preises entsprechend der Anzeigesprache
     *
     * + 2 Nachkommastellen
     * + Darstellung Trennzeichen entsprechend der Anzeigesprache
     * + Besonderheit: Wenn Anzeigesprache deutsch und Preis hat bereits Komma, dann keine Veränderung
     *
     * @param $price
     * @return string
     */
    public static function commaCorrection($price)
    {
        $spracheId = self::findLanguage();

        // deutsche Preisvariante
        if ($spracheId != 2){

            if(!strstr($price, ','))
                $priceFormat = number_format($price, 2, ',', '');
            else
                $priceFormat = $price;
        }
        // englische Preisvariante
        else
            $priceFormat = number_format($price, 2, '.', '');

        return $priceFormat;
    }

    // baut ein komplettes deutsches Datum
    public static function buildCompleteGermanDate($__date)
    {
        $__date = trim($__date);
        $translate = new Zend_Session_Namespace('translate');
        $language = $translate->language;
        if ($language != 'de')
            return $__date;

        $mainParts = explode(' ', $__date);
        $dayMonthYear = explode("-", trim($mainParts[0]));

        $germanDate = $dayMonthYear[2] . "." . $dayMonthYear[1] . "." . $dayMonthYear[0] . " " . $mainParts[1];
        return $germanDate;
    }

    // baut ein Datum entsprechend der Sprache
    public static function buildDateForLanguage($__date, $__language)
    {
        $dateParts = explode("-", $__date);
        if ($__language == "de")
            $correctLanguageDate = $dateParts[2] . "." . $dateParts[1] . "." . $dateParts[0];
        else
            $correctLanguageDate = $dateParts[2] . "/" . $dateParts[1] . "/" . $dateParts[0];

        return $correctLanguageDate;
    }

    // baut ein Kurzdatum entsprechend der Sprache
    public static function buildShortDateForLanguage($__date, $__language)
    {
        $__date = trim($__date);
        $teile = explode(" ", $__date);
        $dateParts = explode("-", $teile[0]);

        if ($__language == "de")
            $correctLanguageDate = $dateParts[2] . "." . $dateParts[1] . "." . $dateParts[0];
        else
            $correctLanguageDate = $dateParts[2] . "/" . $dateParts[1] . "/" . $dateParts[0];

        return $correctLanguageDate;
    }

    public static function buildLongDateForLanguage($__date, $__language)
    {
        $__date = trim($__date);
        $teile = explode(" ", $__date);
        $dateParts = explode("-", $teile[0]);

        if ($__language == "de")
            $correctLanguageDate = $dateParts[2] . "." . $dateParts[1] . "." . $dateParts[0] . " " . $teile[1];
        else
            $correctLanguageDate = $dateParts[2] . "/" . $dateParts[1] . "/" . $dateParts[0] . " " . $teile[1];

        return $correctLanguageDate;
    }

    /**
     * wandelt deutsche und englische Datum in MySQL Date
     *
     * + umwandeln deutsches Datum in ISO8601
     * + keine Umwandlung, da ISO8601
     * + Umwandlung englisches Datum in ISO8601
     *
     * @param $datum
     * @return bool|string
     */
    public static function erstelleSuchdatumAusFormularDatum($datum)
    {
        $suchDatum = false;

        // umwandeln deutsches Datum in ISO8601
        if(strstr($datum, '.')){
            $datumsTeile = explode('.', $datum);
            $suchDatum = $datumsTeile[2] . '-' . $datumsTeile[1] . '-' . $datumsTeile[0];
        }

        // keine Umwandlung, da ISO8601
        if(strstr($datum, '-')){
            $suchDatum = $datum;
        }

        // Umwandlung englisches Datum in ISO8601
        if(strstr($datum, '/')){
            $datumsTeile = explode('/', $datum);
            $suchDatum = $datumsTeile[2] . '-' . $datumsTeile[0] . '-' . $datumsTeile[1];
        }

        return $suchDatum;
    }

    public static function buildUnixfromDate($__date)
    {
        $dateParts = explode("-", $__date);

        foreach ($dateParts as $key => $value) {
            $dateParts[$key] = (int)$value;
        }

        $unixTime = mktime(0, 0, 0, $dateParts[1], $dateParts[2], $dateParts[0]);

        return $unixTime;
    }

    /**
     * Zerlegt ein Datumsformat in Tag, Monat und Jahr. Errechnet Unix Zeitstempel.
     *
     * + wenn ein deutsches Datumsformat, dann automatische Zerlegung
     * + Zerlegung entsprechend der Anzeigesprache
     *
     * @param $date
     * @return array
     */
    public static function buildUnixFromDateByLanguage($date)
    {
        $datum = array();

        // automatisches erkennen des Datum
        if(strstr($date,'.')){
            $datumTeile = explode('.', $date);
            $datum['unixDatum'] = mktime(0, 0, 0, $datumTeile[0], $datumTeile[1], $datumTeile[2]);
            $datum['tag'] = $datumTeile[0];
            $datum['monat'] = $datumTeile[1];
            $datum['jahr'] = $datumTeile[2];

            return $datum;
        }


        $translate = new Zend_Session_Namespace('translate');
        $language = $translate->language;

        if ($language == 'de') {
            $datumTeile = explode('.', $date);
            $datum['unixDatum'] = mktime(0, 0, 0, $datumTeile[0], $datumTeile[1], $datumTeile[2]);
            $datum['tag'] = $datumTeile[0];
            $datum['monat'] = $datumTeile[1];
            $datum['jahr'] = $datumTeile[2];
        }
        else {
            $datumTeile = explode('/', $date);
            $datum['unixDatum'] = mktime(0, 0, 0, $datumTeile[0], $datumTeile[1], $datumTeile[2]);
            $datum['tag'] = $datumTeile[0];
            $datum['monat'] = $datumTeile[1];
            $datum['jahr'] = $datumTeile[2];
        }

        $test = 123;

        return $datum;
    }

    // ????
    public static function buildServiceArea()
    {
        $serviceArea = Zend_Registry::get('service');

        if (!is_array($serviceArea))
            return '';

        $serviceNavigation = "<ul id='service'>";
        $serviceNavigation .= "<li><a href='#'>Service</a><ul>";
        foreach ($serviceArea as $label => $path) {
            $serviceNavigation .= "<li><a href='" . $path . "'>" . $label . "</a></li>";
        }
        $serviceNavigation .= "</ul></li></ul>";

        return $serviceNavigation;
    }

    public static function buildAdminArea()
    {
        $adminArea = Zend_Registry::get('admin');

        if (!is_array($adminArea))
            return '';

        $adminNavigation = "<ul class='navigation'>";
        for ($i = 0; $i < count($adminArea); $i++) {
            $adminNavigation .= "<li><a href='" . $adminArea[$i]['path'] . "'>" . $adminArea[$i]['label'] . "</a></li>";
        }
        $adminNavigation .= "</ul>";

        return $adminNavigation;
    }

    public static function generateTranslateFile()
    {
        $db = Zend_Registry::get('front');
        $sql = "select de from tbl_translate";
        $translate = $db->fetchAll($sql);
        for ($i = 0; $i < count($translate); $i++) {
            echo '$wert["' . $i . '"] = "' . $translate[$i]['de'] . '";<br>';
        }
    }

    public static function calculateStornoDate($__unixDate)
    {
        $stornoFlag = 1;
        $unixNow = mktime(0, 0, 0);
        $stornoDate = $__unixDate - (Zend_Registry::get('static')->storno->stornotage * 86400);

        if ($stornoDate >= $unixNow)
            $stornoFlag = 2;

        return $stornoFlag;
    }

    public static function setMessageToLog($__message, $__shoppingCart, $__errorCode)
    {
        $db = Zend_Registry::get('front');
        $warenkorb = new Zend_Session_Namespace('warenkorb');

        for ($i = 0; $i < count($__shoppingCart); $i++) {

            $insert = array(
                "log_detail" => $__message,
                "kunden_id" => $warenkorb->kundenId,
                "buchungsnummer_id" => $__shoppingCart[$i]['buchungsnummer'],
                "buchungsnummer_program" => $__shoppingCart[$i]['id']
            );

            $control = $db->insert('tbl_buchung_log', $insert);
            if ($control != 1)
                throw new nook_Exception($__errorCode);

        }

        return;
    }

    // findet den Namen des Landes
    public static function findCountryName($__countryId)
    {
        $db = Zend_Registry::get('front');
        $sql = "select Name from tbl_countries where id = '" . $__countryId . "'";
        $country = $db->fetchOne($sql);

        return $country;
    }

    // findet den Namen der Sprache
    public static function findLanguageName($__languageId)
    {
        $db = Zend_Registry::get('front');

        $language = Zend_Registry::get('language');
        $sql = "select " . $language . " from tbl_prog_sprache where id = '" . $__languageId . "'";
        $languageName = $db->fetchOne($sql);

        return $languageName;
    }

//	public static function calculateTotalPrice($__shoppingCart){
//		$priceTotal = 0;
//
//		for($i=0; $i<count($__shoppingCart); $i++){
//			$priceTotal += $__shoppingCart[$i]['offerPrice'];
//		}
//
//		$priceTotal = number_format($priceTotal, 2);
//
//		return $priceTotal;
//	}

    // protokolliert die veränderung im Datensatz
    public static function controlEntryTable($__db, $__kundenId, $__tableName)
    {
        $lastInsertId = $__db->lastInsertId();

        $changeDate = time();

        $update = array(
            "changed_by" => $__kundenId,
            "date_change" => $changeDate
        );

        $control = $__db->update("$__tableName", $update, "id = '" . $lastInsertId . "'");
        if ($control != 1)
            throw new nook_Exception(self::$error_no_control_update_table);

        $lastInsertId;
    }

    public static function test()
    {
        return "Das ist ein Test";
    }

    // findet die ID der Stadt nach dem Namen
    public static function findCityIdByName($__cityEvents)
    {
        $db = Zend_Registry::get('front');
        $sql = "select `tbl_AO_City_ID`, `AO_City` from `tbl_ao_city` order by `AO_City_ID`";
        $aoCity = $db->fetchAll($sql);

        for ($i = 0; $i < count($__cityEvents); $i++) {
            $cityInEvent = strtolower($__cityEvents[$i]['Ort']);
            for ($j = 0; $j < count($aoCity); $j++) {
                $cityInAoTable = strtolower($aoCity[$j]['AO_City']);
                if ($cityInEvent == $cityInAoTable) {
                    $__cityEvents[$i]['cityId'] = $aoCity[$j]['AO_City_ID'];
                    break;
                }
            }
        }

        return $__cityEvents;
    }

    /**
     * findet ein Item in der
     * Session Namespace 'hotelsuche'
     * default ist berlin = 1
     *
     * @static
     * @param $__find
     * @return int
     */
    public static function findItemHotelsuche($__find)
    {
        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $items = $hotelsuche->getIterator();

        if (array_key_exists($__find, $items))
            return $items[$__find];
        else
            return 1;
    }

    // findet den Namen der Stadt nach der ID
    public static function findCityNameById($__cityId)
    {
        $db = Zend_Registry::get('front');
        $sql = "select AO_City from tbl_ao_city where AO_City_ID = '" . $__cityId . "'";
        $cityName = $db->fetchOne($sql);

        return $cityName;
    }


    public static function filterPlz($__string)
    {
        $__string = trim($__string);
        $teile = explode(' ', $__string);

        if (!preg_match("#^([0-9]){5,5}$#", $teile[0]))
            return false;

        return $teile[0];
    }

//    public static function getTotalPriceBookingCart($__typ, array $__buchung){
//        $preisTotal = 0;
//
//        for($i=0; $i<count($__buchung); $i++){
//            if($__typ == 'Programme'){
//                $__buchung[$i]['offerPrice'] = str_replace(',','.',$__buchung[$i]['offerPrice']);
//                $preisTotal += $__buchung[$i]['offerPrice'];
//            }
//            elseif($__typ == 'Uebernachtung'){
//                $__buchung[$i]['gesamtpreis'] = str_replace(',','.',$__buchung[$i]['gesamtpreis']);
//                $preisTotal += $__buchung[$i]['gesamtpreis'];
//            }
//            elseif($__typ == 'Zusatzprodukte'){
//                $__buchung[$i]['summeProduktPreis'] = str_replace(',','.',$__buchung[$i]['summeProduktPreis']);
//                $preisTotal += $__buchung[$i]['summeProduktPreis'];
//            }
//        }
//
//        return $preisTotal;
//    }
}
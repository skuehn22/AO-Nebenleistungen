<?php
/**
* Tool zum umrechnen von Datumsformaten
*
* + Berechnet das Enddatum eines Zeitraumes
* + Umwandlung deutsches Datum
* + Umwandlung englisches Datum
* + Wandelt ein englisches Datum 'dd/mm/yyyy' in das deutsche Datumsformat 'dd.mm.YYYY'
* + Wandelt ein Datum entsprechend der Anzeigesprache
* + Erstellt aus einem deutschen Datum
* + Konvertiert ein Datum in der Sprachabhängigen
* + Verkürzt ein Datum, die Jahreszahl auf 2 Ziffern
* + Wandelt ein Datum entsprechend der Anzeigesprache
*
* @date 31.01.13
* @file ToolDatum.php
* @package tools
*/
class nook_ToolDatum{

    /**
     * Berechnet das Enddatum eines Zeitraumes
     *
     * Übergeben wird das Startdatum und
     * die Anzahl der Übernachtungen.
     * Entsprechend der gewählten Sprache
     * wird das Datum zurückgegeben.
     * Datumsanzeige entsprechend gewählter Sprache.
     *
     * @param $__startDatum
     * @param $__uebernachtung
     * @return date
     */
    public static function berechneEndeZeitraum($__startDatum, $__uebernachtung){

        $startdatum = date_create($__startDatum);
        $endDatum = date_add($startdatum, date_interval_create_from_date_string($__uebernachtung." days"));

        $sprache = Zend_Registry::get('language');
        if($sprache == 'de')
            return date_format($endDatum, "d.m.Y");
        else
            return date_format($endDatum, "Y-m-d");
    }


    /**
     * Umwandlung deutsches Datum
     * in englisches datum
     *
     * @param $__deutschesDatum
     * @return string
     */
    public static function wandleDatumDeutschInEnglisch($__deutschesDatum){

        $teileDatum = explode('.',$__deutschesDatum);
        $englischesDatum = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];

        return $englischesDatum;
    }

    /**
     * Umwandlung englisches Datum
     * in deutsches Datum
     *
     * @param $__englischesDatum
     * @return string
     */
    public static function wandleDatumEnglischInDeutsch($__englischesDatum){
        $teileDatum = explode('-',$__englischesDatum);

        $deutschesDatum = $teileDatum[2].".".$teileDatum[1].".".$teileDatum[0];

        return $deutschesDatum;
    }

    /**
     * Wandelt ein englisches Datum 'dd/mm/yyyy' in das deutsche Datumsformat 'dd.mm.YYYY'
     *
     * @param $datum
     * @return string
     */
    public static function wandleEnglischesDatumInsDeutscheDatum($datum)
    {
        $teileDatum = explode("/", $datum);
        $datum = $teileDatum[0].".".$teileDatum[1].".".$teileDatum[2];

        return $datum;
    }

    /**
     * Wandelt ein Datum entsprechend der Anzeigesprache
     *
     * @param $__datum
     * @return string
     */
    public static function wandleDatumEntsprechendSprache($__datum){

        $spracheItems = new Zend_Session_Namespace('translate');
        $sprache = (array) $spracheItems->getIterator();

        if($sprache['language'] == 'de'){
            $teile = explode('.',$__datum);
            $datum = $teile[2].".".$teile[1].".".$teile[0];
        }
        else
            $datum = $__datum;

        return $datum;
    }

    /**
     * Erstellt aus einem deutschen Datum
     * und der Uhrzeit ein DateTime für MySQL DB
     *
     * @param $__deutschesDatum 28.02.2013
     * @param $__zeit 08:00
     * @return string
     */
    public static function erstelleDateTime($__deutschesDatum, $__zeit){

        $teileDatum = explode(".", $__deutschesDatum);

        $dateTime = $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0]." ".$__zeit;

        return $dateTime;
    }

    /**
     * Konvertiert ein Datum in der Sprachabhängigen
     *
     * Anzeige in ein Datum vom Typ 'Date'.
     * Berücksichtigt die Anzeigesprache.
     *
     * @param $datum
     * @param $anzeigesprache, 1 = deutsch, 2 = englisch
     * @return string
     */
    static public function konvertDatumInDate($anzeigesprache, $datum){

        if($anzeigesprache == 1){
            $teileDatum = explode('.',$datum);

            return $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
        }

        if($anzeigesprache == 2){
            $teileDatum = explode('/',$datum);

            return $teileDatum[2]."-".$teileDatum[1]."-".$teileDatum[0];
        }

    }

    /**
     * Verkürzt ein Datum, die Jahreszahl auf 2 Ziffern
     *
     * + berücksichtigt die Anzeigesprache
     *
     * @param string $datum
     * @return string
     */
    public static function verkuerzeDatumJahreszahl($datum){
        $anzeigeSprache = nook_ToolSprache::getAnzeigesprache();

        if($anzeigeSprache == 'de'){
            $seperator = '.';
            $teileDatum = explode('.',$datum);
        }
        else{
            $seperator = '/';
            $teileDatum = explode('/',$datum);
        }

        $jahr = substr($teileDatum[2], 2);
        $datum = $teileDatum[0].$seperator.$teileDatum[1].$seperator.$jahr;

        return $datum;
    }

    /**
     * Wandelt ein Datum entsprechend der Anzeigesprache
     *
     * + $datum = 2013-08-13
     * + $spracheId = 1, => deutsch
     * + $spracheId = 2, => englisch
     *
     * @param $spracheId
     * @param $datum
     * @return mixed
     */
    public static function umwandelnDatumNachAnzeigesprache($spracheId, $datum)
    {
        $teileDatum = explode("-",$datum);

        if($spracheId == 1)
            $korrigiertesDatum = $teileDatum[2].".".$teileDatum[1].".".$teileDatum[0];
        else
            $korrigiertesDatum = $teileDatum[2]."/".$teileDatum[1]."/".$teileDatum[0];

        return $korrigiertesDatum;
    }

    /**
     * Ermittelt die Datumsangaben einer gebuchten Hoteluebernachtung, ISO8601
     *
     * @param $start ISO8601
     * @param $anzahlUebernachtungen int
     * @return array
     */
    public static function berechnungUebernachtungen($start, $anzahlUebernachtungen)
    {
        $uebernachtungen = array();

        for($i = 0; $i < $anzahlUebernachtungen; $i++){
             $startDatum = new DateTime($start);
             $startDatum->add(new DateInterval("P".$i."D"));
             $uebernachtungen[$i] = $startDatum->format('Y-m-d');
        }

        return $uebernachtungen;
    }

} // end class
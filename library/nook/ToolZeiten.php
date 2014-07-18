<?php
/**
 * Tools zum bearbeiten von Zeiten
 *
 * + gibt das Datum entsprechend der Anzeigesprache zurück
 * + Erstellt aus einem, MySQl - Date Field ein
 * + Verändert die Darstellung der Zeit.
 * + Kappt nicht benötigte Teile einer Zeitangabe
 * + Gibt die Teile einer Zeitangabe zurück.
 *
 * @date 23.41.2013
 * @file ToolZeiten.php
 * @package tools
 */
class nook_ToolZeiten
{

    /**
     * gibt das Datum entsprechend der Anzeigesprache zurück
     * Eingabedatum ist komplettes date aus der MySQL
     *
     * @static
     * @param $__date
     * @param $__anzeigeSprache
     * @return string
     */
    static public function generiereDatumNachAnzeigesprache($__date = false, $__anzeigeSprache = false)
    {

        $teile = explode(' ', trim($__date));
        $datumTeile = explode('-', $teile[0]);

        if (count($teile) > 1) {
            $teile[1] = (string) $teile[1];
            $zeit = substr($teile[1], 0, -3);
        } else {
            $zeit = '';
        }

        // deutsches Datum
        if ($__anzeigeSprache == 1) {
            $datum = $datumTeile[2] . "." . $datumTeile[1] . "." . $datumTeile[0];
        } // englisches Datum
        else {
            $datum = $datumTeile[1] . "/" . $datumTeile[2] . "/" . $datumTeile[0];
        }

        $datum .= " " . $zeit;

        return $datum;
    }

    /**
     * Erstellt aus einem, MySQl - Date Field ein
     * Unix Datum.
     * Rückgabe der Sekunden
     *
     * @static
     * @param $__date
     * @return
     */
    static public function erstelleUnixAusDate($__date)
    {
        $__date = trim($__date);
        $dateItems = explode("-", $__date);

        $unixTime = mktime(0, 0, 0, $dateItems[1], $dateItems[2], $dateItems[0]);

        return $unixTime;
    }

    /**
     * Verändert die Darstellung der Zeit.
     * Umrechnung einer Zahl / Float in
     * 2 Nachkommastellen und Doppelpunkt
     *
     * @param $__bookingDetails
     * @return mixed
     */
    static public function aendereDarstellungZeit($zeit)
    {
        $zeitMitDoppelpunkt = number_format($zeit, 2, ':', '.');

        return $zeitMitDoppelpunkt;
    }

    /**
     * Kappt nicht benötigte Teile einer Zeitangabe
     *
     * + 1 = Stunden
     * + 2 = Stunden und Minuten
     *
     * @param $zeit
     * @param int $groesse
     * @return string
     */
    static public function kappenZeit($zeit, $groesse = 1)
    {

        $teileZeit = explode(':', $zeit);

        If ($groesse == 1) {
            $zeit = $teileZeit[0];
        } elseif ($groesse == 2) {
            $zeit = $teileZeit[0] . ":" . $teileZeit[1];
        }

        return $zeit;
    }

    /**
     * Gibt die Teile einer Zeitangabe zurück.
     *
     * + Stunde
     * + Minute
     * + Sekunde
     *
     * @param $zeit
     * @return array
     */
    public static function teileZeit($zeit)
    {
        $zeitangabe = array();
        $teileZeit = explode(':', $zeit);

        $zeitangabe['stunde'] = $teileZeit[0];
        $zeitangabe['minute'] = $teileZeit[1];
        $zeitangabe['sekunde'] = $teileZeit[2];

        return $zeitangabe;
    }

}
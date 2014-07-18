<?php
/**
 * Verändert die Anzahl der verfügbaren Zimmer
 *
 * + Kontrolliert ankommende Werte
 * + Berechnet Zeitraum der Buchung
 *
 * @author stephan.krauss
 * @date 10.06.13
 * @file VeraendernAnzahlVerfuegbareZimmer.php
 * @package front
 * @subpackage model
 */
class Front_Model_VeraendernAnzahlVerfuegbareZimmer implements Front_Model_VeraendernAnzahlVerfuegbareZimmerInterface{

    // Fehler
    private $error = 1620;

    // Tabellen / Views
    private $tabelleOtaRatesAvailability = null;

    protected $datenHotelbuchung = array();

    function __construct ()
    {
        $this->tabelleOtaRatesAvailability = new Application_Model_DbTable_otaRatesAvailability(array('db' => 'hotels'));

    }

    /**
     * Setzt die Daten der Hotelbuchung
     *
     * @param array $datenHotelbuchung
     * @return Front_Model_VeraendernAnzahlVerfuegbareZimmer
     */
    public function setDatenHotelbuchung(array $datenHotelbuchung)
    {
        $this->datenHotelbuchung = $datenHotelbuchung;

        return $this;
    }

    /**
     * Kontrolliert die Daten und trägt das neue Zimmerlimit ein
     *
     * @return Front_Model_VeraendernAnzahlVerfuegbareZimmer
     */
    public function startZimmerreduktion(){

        for($i=0; $i < count($this->datenHotelbuchung); $i++){
            $this->reduktionVerfuegbareRaten($i);
        }

        return $this;
    }

    /**
     * Verringert die Anzahl der verfuegbaren Zimmer einer Rate
     *
     * + entsprechend Anreisedatum und Anzahl der Naechte
     * + entsprechend gebuchter Zimmeranzahl
     * + entsprechend ID der Rate
     *
     * @return mixed
     */
    private function reduktionVerfuegbareRaten($i)
    {
        for($j=0; $j < $this->datenHotelbuchung[$i]['nights']; $j++){
            $dateObj = date_create($this->datenHotelbuchung[$i]['startDate']);
            $datumsZugabe = $j." days";
            $berechnetesDatum = date_add($dateObj,date_interval_create_from_date_string($datumsZugabe));

            $datumUebernachtung = date_format($berechnetesDatum,"Y-m-d");

            $this->reduktionEinzelneRate($this->datenHotelbuchung[$i]['otaRatesConfigId'],$this->datenHotelbuchung[$i]['roomNumbers'], $datumUebernachtung);
        }

        return $i;
    }

    /**
     * Reduziert die Zimmeranzahl einer einzelnen Rate
     *
     * @param $rateId
     * @param $roomLimit
     * @param $datum
     * @return mixed
     */
    private function reduktionEinzelneRate($rateId, $roomLimit, $datum)
    {
        $update = array(
            'roomlimit' => new Zend_Db_Expr("roomlimit - ".$roomLimit)
        );

        $where = array(
            "rates_config_id = ".$rateId,
            "datum = '".$datum."'"
        );

        $anzahlVeraenderteRaten = $this
            ->tabelleOtaRatesAvailability
            ->update($update, $where);

        return $anzahlVeraenderteRaten;
    }



} // end class

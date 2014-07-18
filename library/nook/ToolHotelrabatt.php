<?php 
 /**
 * Ermittelt den Rabatt eines Hotels bezüglich einer Gruppenbuchung
 *
 * @author Stephan.Krauss
 * @date 22.11.2013
 * @file ToolHotelrabatt.php
 * @package tools
 */
class nook_ToolHotelrabatt
{
    // Tabellen / Views
    /** @var $tabelleProperties Application_Model_DbTable_properties  */
    protected $tabelleProperties = null;
    /** @var $tabelleOtaprices Application_Model_DbTable_otaPrices */
    protected $tabelleOtaPrices = null;
    /** @var $tabelleCategories Application_Model_DbTable_categories */
    protected $tabelleCategories = null;
    /** @var $tabelleCategoriesRates Application_Model_DbTable_categoriesRates */
    protected $tabelleCategoriesRates = null;
    /** @var $tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig  */
    protected $tabelleOtaRatesConfig = null;
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    protected $tabelleHotelbuchung = null;

    // Zustände
    protected $isRabatt = false;

    protected $pimple = null;

    protected $gebuchteRaten = array();
    protected $propertyId = null;
    protected $startDatum = null;
    protected $anzahlNaechte = null;

    protected $anzahlPersonenreisegruppe = null;
    protected $anzahlFreiplaetze = 0;

    protected $hotelRabattInformation = array();
    protected $personenAnzahlFuerHotelRabatt = null;


    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProperties',
            'tabelleOtaRatesConfig',
            'tabelleCategories',
            'tabelleOtaPrices'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception('Anfangswert fehlt');
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param array $raten
     * @return nook_ToolHotelrabatt
     */
    public function setRaten(array $raten)
    {
        foreach($raten as $valueRaten){

            $valueRaten['anzahl'] = (int) $valueRaten['anzahl'];
            $valueRaten['ratenId'] = (int) $valueRaten['ratenId'];

            if(!empty($valueRaten['anzahl']))
                $this->gebuchteRaten[] = $valueRaten;
        }

        return $this;
    }

    /**
     * @param $propertyId
     * @return nook_ToolHotelrabatt
     */
    public function setPropertyId($propertyId)
    {
        $propertyId = (int) $propertyId;
        $kontrolle = $this->tabelleProperties->kontrolleValue('id', $propertyId);
        if(false === $propertyId)
            throw new nook_Exception('Anfangswert falsch');

        $this->propertyId = $propertyId;

        return $this;
    }

    /**
     * @param $suchdatum
     * @return nook_ToolHotelrabatt
     */
    public function setStartDatum($startDatum)
    {
        $this->startDatum = $startDatum;

        return $this;
    }

    /**
     * @param $anzahlNaechte
     * @return nook_ToolHotelrabatt
     * @throws nook_Exception
     */
    public function setAnzahlUebernachtungen($anzahlNaechte)
    {
        $anzahlNaechte = (int) $anzahlNaechte;
        if($anzahlNaechte == 0)
            throw new nook_Exception('Anzahl Naechte falsch');

        $this->anzahlNaechte = $anzahlNaechte;

        return $this;
    }

    /**
     * @return array
     */
    public function getHotelRabattInformation()
    {
        return $this->hotelRabattInformation;
    }

    /**
     * @return int
     */
    public function getAnzahlFreiplaetze()
    {
        return $this->anzahlFreiplaetze;
    }

    /**
     * Steuert die Ermittlung des Gruppenrabatt eines Hotels
     *
     * + Anzahl Personen
     * + vergibt das Hotel einen Rabatt ?
     * + abbruch wenn kein Hotel Rabatt
     *
     * @return nook_ToolHotelrabatt
     * @throws nook_Exception
     */
    public function steuerungBerechnungRabatt()
    {
        if(count($this->gebuchteRaten) == 0)
            return $this;

        if(is_null($this->propertyId))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->startDatum))
            throw new nook_Exception('Startdatum fehlt');

        if(is_null($this->anzahlNaechte))
            throw new nook_Exception('Anzahl Naechte fehlt');

        // Anzahl Personen
        $anzahlPersonenreisegruppe = $this->ermittelnAnzahlpersonenReisegruppe($this->gebuchteRaten);
        $this->anzahlPersonenreisegruppe = $anzahlPersonenreisegruppe;

        // vergibt das Hotel einen Rabatt ?
        $isRabatt = $this->vergibtDasHotelRabatt($anzahlPersonenreisegruppe);
        $this->isRabatt = $isRabatt;

        // abbrechen wenn kein Hotelrabatt
        if(false === $isRabatt)
            return $this;

        // ermitteln Standard Zimmerbelegung
        $gebuchteRaten = $this->ermittelnKategorie($this->gebuchteRaten);

        // Berechnung der Personenpreise einer Rate
        $gebuchteRaten = $this->personenPreisEinerRate($gebuchteRaten, $this->startDatum);

        // sortieren der gebuchten Übernachtungen nach dem Personenpreis im Zeitraum
        $sortierteGebuchteRaten = $this->sortiereNachPersonenPreis($gebuchteRaten);

        // Berechnung Hotelrabatt
        $anzahlFreipletzeImHotel = $this->anzahlFreiplaetze;
        $hotelRabattInformation = $this->berechnungHotelrabattInformation($sortierteGebuchteRaten, $anzahlFreipletzeImHotel);
        $this->hotelRabattInformation = $hotelRabattInformation;

        return $this;
    }

    /**
     * Verrechnet beginnend in der preislich höchsten gebuchten Rate die Freiplätze
     *
     * + wenn in der 'preislich höchsten' gebuchten Rate die Anzahl der Freiplätze nicht ausreicht
     * + wird in der nachfolgenden 'preislichen' Rate der Rest der Freiplätze verrechnet.
     *
     * @param array $sortierteGebuchteRaten
     * @param $anzahlFreipletzeImHotel
     * @return array
     */
    protected function berechnungHotelrabattInformation(array $sortierteGebuchteRaten, $anzahlFreipletzeImHotel)
    {
        $rabattInformation = array();
        $rabattInformation['hotelRabattPreis'] = 0;

        $i = 0;
        foreach($sortierteGebuchteRaten as $sortierteGebuchteRate){

            $test = 123;

            if( ($sortierteGebuchteRate['anzahl'] - $anzahlFreipletzeImHotel) >= 0){

                $rabattInformation['hotelRabattPreis'] += $anzahlFreipletzeImHotel * $sortierteGebuchteRate['personenPreisRateImZeitraum'];
                $rabattInformation['freiplatzRate'][$i]['anzahl'] = $anzahlFreipletzeImHotel;
                $rabattInformation['freiplatzRate'][$i]['ratenName'] = $sortierteGebuchteRate['ratenName'];

                break;
            }
            else{
                $rabattInformation['hotelRabattPreis'] += ($anzahlFreipletzeImHotel - $sortierteGebuchteRate['anzahl']) * $sortierteGebuchteRate['personenPreisRateImZeitraum'];

                $rabattInformation['freiplatzRate'][$i]['anzahl'] = $sortierteGebuchteRate['anzahl'];
                $rabattInformation['freiplatzRate'][$i]['ratenName'] = $sortierteGebuchteRate['ratenName'];

                $anzahlFreipletzeImHotel = $anzahlFreipletzeImHotel - $sortierteGebuchteRate['anzahl'];
            }

            $i++;
        }

        return $rabattInformation;
    }

    /**
     * Sortiert die gebuchten Ratn eines Hotels nach dem grössten Personenpreis im Zeitraum
     *
     * @param array $gebuchteRaten
     * @return array
     */
    protected function sortiereNachPersonenPreis(array $gebuchteRaten)
    {
        $sortierteGebuchteRaten = array();

        // erstellen Array das sortiert werden soll
        foreach($gebuchteRaten as $key =>$gebuchteRate){
            $sortierteGebuchteRaten[$key] = (float) $gebuchteRate['personenPreisRateImZeitraum'];
        }

        // rekursives sortieren des Array
        arsort($sortierteGebuchteRaten);

        // übergeben der Daten der gebuchten Raten
        foreach($sortierteGebuchteRaten as $key => $value){
            $sortierteGebuchteRaten[$key] = $gebuchteRaten[$key];
        }

        return $sortierteGebuchteRaten;
    }

    /**
     * Ermittelt die Belegungswerte einer Rate
     *
     * + Standardbelegung einer Rate
     * + Kategorie ID einer Rate
     *
     * @param $gebuchteRaten
     * @return array
     */
    protected function ermittelnKategorie(array $gebuchteRaten)
    {
        for($i=0; $i < count($gebuchteRaten); $i++){
            $rate = $gebuchteRaten[$i];

            $datenRate = $this->tabelleOtaRatesConfig->find($rate['ratenId'])->toArray();
            $gebuchteRaten[$i]['category_id'] = $datenRate[0]['category_id'];
            $gebuchteRaten[$i]['ratenName'] = $datenRate[0]['name'];

            $row = $this->tabelleCategories->find($datenRate[0]['category_id'])->toArray();
            $gebuchteRaten[$i]['standard_persons'] = $row[0]['standard_persons'];
        }


        return $gebuchteRaten;
    }

    /**
     * Ermittlung des maximalen Peronenpreises einer Rate im gebuchten Zeitraum
     *
     * + maximaler Preis einer Rate / Zimmer im Zeitraum
     * + Ermittlung des Personenpreises der maximalen Rate im Zeitraum
     *
     * @param array $gebuchteRaten
     * @param $startDatum
     * @return array
     */
    protected function personenPreisEinerRate(array $gebuchteRaten, $startDatum)
    {
        $weitereVolleTage = $this->anzahlNaechte - 1;

        for($i=0; $i < count($gebuchteRaten); $i++){
            $rate = $gebuchteRaten[$i];

            // maximaler Preis einer Rate / Zimmer im Zeitraum
            $cols = array(
                new Zend_Db_Expr("SUM(amount) AS personenPreisUebernachtungen")
            );

            $whereRatenId = "rates_config_id = ".$rate['ratenId'];
            $whereDatum = "datum BETWEEN '".$startDatum."' and DATE_ADD('".$startDatum."', INTERVAL ".$weitereVolleTage." DAY)";

            $select = $this->tabelleOtaPrices->select();
            $select
                ->from($this->tabelleOtaPrices, $cols)
                ->where($whereRatenId)
                ->where($whereDatum);

            // $query = $select->__toString();

            $row = $this->tabelleOtaPrices->fetchAll($select)->toArray();

            // Ermittlung des Personenpreises der maximalen Rate im Zeitraum
            $personenPreisEinerRateImZeitraum = $row[0]['personenPreisUebernachtungen'] / $rate['standard_persons'];

            // Preis für eine Person für eine Rate im Zeitraum
            $gebuchteRaten[$i]['personenPreisRateImZeitraum'] = $personenPreisEinerRateImZeitraum;
        }

       return $gebuchteRaten;
    }


    /**
     * Ermittelt die Anzahl der Personen für einen Rabatt. Bestimmt die Anzahl der Freiplätze.
     *
     * @param anzahlPersonenreisegruppe $
     * @return bool
     */
    protected function vergibtDasHotelRabatt($anzahlPersonenreisegruppe)
    {
        $rows = $this->tabelleProperties->find($this->propertyId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze falsch');

        $isRabatt = false;

        // Hat das Hotel einen Gruppenrabatt ?
        if($rows[0]['gruppenrabatt'] > 0){

            $anzahlFreiplaetze = floor($anzahlPersonenreisegruppe / $rows[0]['gruppenrabatt']);

            if($anzahlFreiplaetze > 0){
                $this->personenAnzahlFuerHotelRabatt = $rows[0]['gruppenrabatt'];
                $this->anzahlFreiplaetze = $anzahlFreiplaetze;
                $isRabatt = true;
            }
        }

        return $isRabatt;
    }

    /**
     * Ermittlung Anzahl Personen Reisegruppe
     *
     * @param $gebuchteRaten
     * @return int
     */
    protected function ermittelnAnzahlpersonenReisegruppe($gebuchteRaten)
    {
        $anzahlPersonenreisegruppe = 0;

        foreach($gebuchteRaten as $rate){
            $anzahlPersonenreisegruppe += $rate['anzahl'];
        }

        return $anzahlPersonenreisegruppe;
    }
}
 
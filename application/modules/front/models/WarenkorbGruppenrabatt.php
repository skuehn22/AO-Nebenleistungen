<?php 
 /**
 * Ermittelt den Gruppenrabatt der gebuchten Hotels des aktuellen Warenkorbes. Gruppen Rabatt Hotel
 *
 * @author Stephan.Krauss
 * @date 04.12.2013
 * @file WarenkorbGruppenrabatt.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbGruppenrabatt
{

    /** @var $toolZaehler nook_ToolZaehler */
    protected $toolZaehler = null;
    /** @var $toolHotelbuchungenWarenkorb nook_ToolHotelbuchungenWarenkorb */
    protected $toolHotelbuchungenWarenkorb = null;
    /** @var $tabelleProperties Application_Model_DbTable_properties */
    protected $tabelleProperties = null;
    /** @var $tabelleCategories Application_Model_DbTable_categories */
    protected $tabelleCategories = null;
    /** @var $tabelleOtaRatesConfig Application_Model_DbTable_otaRatesConfig */
    protected $tabelleOtaRatesConfig = null;
    /** @var $tabelleOtaPrices Application_Model_DbTable_otaPrices */
    protected $tabelleOtaPrices = null;

    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $zaehler = null;
    protected $anzeigeSpracheId = 0;

    protected $hotelbuchungen = array();
    protected $anzahlPersonenGruppenrabattHotel = array();
    protected $gruppenRabatt = array();
    protected $rabattWarenkorb = 0;

    /**
     * Servicecontainer und Anzeigesprache
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);

        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'toolZaehler',
            'toolHotelbuchungenWarenkorb',
            'tabelleProperties',
            'tabelleCategories',
            'tabelleOtaRatesConfig',
            'tabelleOtaPrices'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @return int
     */
    public function getGruppenRabatt()
    {
        return $this->gruppenRabatt;
    }

    /**
     * @return float
     */
    public function getGesamtRabattWarenkorb()
    {
        return $this->rabattWarenkorb;
    }

    /**
     * @param $buchungsNummerId
     * @return Front_Model_WarenkorbGruppenrabatt
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_WarenkorbGruppenrabatt
     */
    public function setZaehler($zaehler)
    {
        $this->zaehler = $zaehler;

        return $this;
    }



    /**
     * Steuert die Ermittlung der Gruppenrabatte des aktuellen Warenkorbes
     *
     * @return Front_Model_WarenkorbGruppenrabatt
     * @throws nook_Exception
     */
    public function steuerungErmittlungGruppenRabatt()
    {
        if(is_null($this->pimple))
            throw new nook_Exception('Anfangswert fehlt');

        if( is_null($this->buchungsNummerId) and !is_null($this->zaehler))
            throw new nook_Exception('Ein Anfangswert fehlt');

        if( !is_null($this->buchungsNummerId) and is_null($this->zaehler))
            throw new nook_Exception('Ein Anfangswert fehlt');

        // Buchungsnummer und Zaehler
        if(is_null($this->buchungsNummerId) and is_null($this->zaehler))
            $buchungsnummer = $this->ermittelnBuchungsnummerZaehler();

        // ermitteln Hotelbuchungen
        $hotelbuchungen = $this->ermittelnHotelbuchungen($this->buchungsNummerId, $this->zaehler);

        // Abbruch wenn keine Hotelbuchungen vorhanden
        if(count($hotelbuchungen) == 0)
            return $this;

        // Mindestgruppenstärke für den Hotelrabatt in einem Hotel, löschen Hotels ohne Gruppenrabatt
        $hotelbuchungen = $this->gruppenrabattHotelcode($hotelbuchungen);

        // Standard Personenanzahl in einer Kategorie, Kategoriename
        $hotelbuchungen = $this->ermittelnStandardBelegungZimmer($hotelbuchungen);

        // aufsplitten der Hotelbuchungen nach einzelnes Hotel
        $hotelbuchungen = $this->aufsplittenBuchungenNachHotel($hotelbuchungen);

        // löschen von Buchungsdatensaetzen der Hotels an denen die Mindesgruppenstärke nicht erreicht wurde. Berechnung der Freiplätze
        $hotelbuchungen = $this->kontrolleMindesGruppenstaerkeBuchungstage($hotelbuchungen);

        // Berechnung des Rabatt einer Gruppe in einem Hotel
        $sortierteHotelbuchungen = $this->berechnungGruppenRabatt($hotelbuchungen);

        // Berechnung Rabatt für ein Hotel und einer Gruppe an einem Tag. Freiplätze in den gebuchten Raten
        $gruppenRabatt = $this->berechnungGesamtRabattGruppe($sortierteHotelbuchungen);

        // Berechnung Gesamtrabatt Warenkorb
        $gesamtRabattWarenkorb = $this->gesamtRabattWarenkorb($gruppenRabatt);
        $this->rabattWarenkorb = $gesamtRabattWarenkorb;

        $this->gruppenRabatt = $gruppenRabatt;


        return $this;
    }

    /**
     * Berechnung Gesamtrabatt der Übernachtungen eines Warenkorbes
     *
     * @param $gruppenRabatt
     * @return float
     */
    protected function gesamtRabattWarenkorb($gruppenRabatt)
    {
        $gesamtRabatt = 0;

        for($i=0; $i < count($gruppenRabatt); $i++){
            $gesamtRabatt += $gruppenRabatt[$i]['rabatt'];
        }

        return $gesamtRabatt;
    }

    /**
     * Berechnet den Rabatt Preis der Gruppenbuchung in einem Hotel an einem Tag
     *
     * + Gibt den Rabattpreis einer Gruppe für eine Teilrechnung zurück
     * + Gibt die Anzahl der Freiplätze der Raten zurück
     * + Gibt Ratenbezeichnung zurück
     *
     * @param $sortierteHotelbuchungen
     * @return array
     */
    protected function berechnungGesamtRabattGruppe($sortierteHotelbuchungen)
    {
        $tagesRabattInEinemHotel = array();

        $j = 0;
        foreach($sortierteHotelbuchungen as $hotelId => $buchungen){
            foreach($buchungen as $tag => $raten){
                $freiPlaetze = $raten['freiplaetze'];
                unset($raten['freiplaetze']);

               foreach($raten as $key => $rate){
                   if($rate['personNumbers'] >= $freiPlaetze){

                       $tagesRabattInEinemHotel[$j]['rabatt'] = $freiPlaetze * ($rate['tagesPreis'] / $rate['standardBelegungZimmer']);
                       $tagesRabattInEinemHotel[$j]['freiplaetze'] = $freiPlaetze;
                       $tagesRabattInEinemHotel[$j]['categorieName'] = $rate['categorieName'];
                       $tagesRabattInEinemHotel[$j]['datum'] = $tag;
                       $tagesRabattInEinemHotel[$j]['hotelName'] = $rate['hotelName'];

                       $freiPlaetze = 0;
                       $j++;
                   }
                   elseif($freiPlaetze > 0){

                       $tagesRabattInEinemHotel[$j]['rabatt'] = $rate['personNumbers'] * ($rate['tagesPreis'] / $rate['standardBelegungZimmer']);
                       $tagesRabattInEinemHotel[$j]['freiplaetze'] = $rate['personNumbers'];
                       $tagesRabattInEinemHotel[$j]['categorieName'] = $rate['categorieName'];
                       $tagesRabattInEinemHotel[$j]['datum'] = $tag;
                       $tagesRabattInEinemHotel[$j]['hotelName'] = $rate['hotelName'];

                       $freiPlaetze = $freiPlaetze - $rate['personNumbers'];
                       $j++;
                   }

                   if($freiPlaetze <= 0)
                       break;
               }
            }
        }

        return $tagesRabattInEinemHotel;
    }

    /**
     * Sortiert nach der 'fettesten' Rate
     *
     * @param array $hotelbuchungen
     * @return array
     */
    protected function berechnungGruppenRabatt(array $hotelbuchungen)
    {
        $sortierteHotelbuchungen = array();
        $tmpRatenPreisAnEinemTag = array();

        foreach($hotelbuchungen as $hotelId => $buchungsDatenAnEinemTag){
            foreach($buchungsDatenAnEinemTag as $tag => $raten){
                foreach($raten as $key => $rate){

                    // verhindern das Anzahl Freiplätze verwandt wird
                    foreach($raten as $key => $rate){
                        if(is_array($rate))
                            $tmpRatenPreisAnEinemTag[$key] = $rate['tagesPreis'] / $rate['standardBelegungZimmer'];
                    }
                }

                // sortieren nach fettesten Rate
                arsort($tmpRatenPreisAnEinemTag);

                // schaffen Knoten sortierte Hotelbuchung
                $sortierteHotelbuchungen[$hotelId][$tag] = $tmpRatenPreisAnEinemTag;

                // Aufbau sortierte Hotelbuchungen
                foreach($sortierteHotelbuchungen[$hotelId][$tag] as $key => $tagespreis){
                    $sortierteHotelbuchungen[$hotelId][$tag][$key] = $raten[$key];
                }

                $sortierteHotelbuchungen[$hotelId][$tag]['freiplaetze'] = $hotelbuchungen[$hotelId][$tag]['freiplaetze'];
            }
        }

        return $sortierteHotelbuchungen;
    }

    /**
     * Löschen von Buchungen in einem Hotel an denen die Mindestgruppenstärke nicht erreicht wurde.
     *
     * + Ermittelt die Anzahl der Personen / Gruppenstärke in einem Hotel an einem Tag
     * + löscht Datensätze eines Hotels an einem Tag an denen die Mindestgruppenstärke nicht erreicht wurde
     * + errechnet Freiplätze
     *
     * @param array $hotelbuchungen
     * @return array
     */
    protected function kontrolleMindesGruppenstaerkeBuchungstage(array $hotelbuchungen)
    {
        $tmpHotel = array();

        // ermitteln Personenanzahl an einem Tag in einem Hotel
        foreach($hotelbuchungen as $hotelId => $tageBuchungImHotel){
            foreach($tageBuchungImHotel as $tag => $buchungsDaten){
                for($i=0; $i < count($buchungsDaten); $i++){
                    $tmpHotel[$hotelId][$tag]['personenAnzahl'] += $buchungsDaten[$i]['personNumbers'];
                    $tmpHotel[$hotelId][$tag]['personenAnzahlGruppenrabatt'] = $buchungsDaten[$i]['personenAnzahlGruppenrabatt'];
                }
            }
        }

        // Löschen und berehnen der Freiplätze an einem Tag
        foreach($tmpHotel as $hotelId => $tagesAngaben){
            foreach($tagesAngaben as $datum => $angaben){
                $freiplaetzeImHotelAnEinemTag = $angaben['personenAnzahl'] / $angaben['personenAnzahlGruppenrabatt'];

                // wenn keine Freiplätze möglich
                if($freiplaetzeImHotelAnEinemTag < 0)
                    unset($hotelbuchungen[$hotelId][$datum]);
                // Berechnung Freiplätze
                else{
                    $freiplaetzeImHotelAnEinemTag = floor($freiplaetzeImHotelAnEinemTag);
                    $hotelbuchungen[$hotelId][$datum]['freiplaetze'] = $freiplaetzeImHotelAnEinemTag;
                }
            }
        }

        return $hotelbuchungen;
    }

    /**
     * Splittet die Buchungsdatensätze nach Hotels auf. Berechnet die Tage der Buchung
     *
     * @param array $hotelbuchungen
     * @return array
     */
    protected function aufsplittenBuchungenNachHotel(array $hotelbuchungen)
    {
        $tmpHotel = array();

        // filtert die Hotels, bildet Knoten
        foreach($hotelbuchungen as $hotelbuchung){
            if(!array_key_exists($hotelbuchung['propertyId'], $tmpHotel))
                $tmpHotel[$hotelbuchung['propertyId']] = array();
        }

        // berechnet die Tage des Aufenthaltes im Hotel
        foreach($hotelbuchungen as $hotelbuchung){

            for($i=0; $i < $hotelbuchung['nights']; $i++){
                $anreiseDatum = date_create($hotelbuchung['startDate']);
                date_add($anreiseDatum, date_interval_create_from_date_string($i.' days'));
                $naechsterTag = date_format($anreiseDatum, 'Y-m-d');

                // Berechnung Tagespreis einer Rate
                $hotelbuchung = $this->berechnungTagespreisrateRatecode($hotelbuchung, $naechsterTag);

                $tmpHotel[$hotelbuchung['propertyId']][$naechsterTag][] = $hotelbuchung;
            }
        }

        return $tmpHotel;
    }

    /**
     * Ermittelt den Tagespreis einer Rate
     *
     * @param array $hotelbuchung
     * @param $hotelId
     * @param $naechsterTag
     * @param $rateId
     * @return array
     */
    protected function berechnungTagespreisrateRatecode(array $hotelbuchung, $naechsterTag)
    {
        $rows = $this->tabelleOtaRatesConfig->find($hotelbuchung['otaRatesConfigId'])->toArray();

        $wherePropertyCode = "hotel_code = '".$hotelbuchung['propertyCode']."'";
        $whereDatum = "datum = '".$naechsterTag."'";
        $whereRateCode = "rate_code = '".$rows[0]['rate_code']."'";

        $select = $this->tabelleOtaPrices->select();
        $select
            ->where($wherePropertyCode)
            ->where($whereDatum)
            ->where($whereRateCode);

        $query = $select->__toString();

        $rows = $this->tabelleOtaPrices->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl datensaetze falsch');

        $hotelbuchung['tagesPreis'] = $rows[0]['amount'];

        return $hotelbuchung;
    }

    /**
     * Ermitteln der Standardbelegung eines Zimmers, Kategorie ID
     *
     * + Standardbelegung Zimmer
     * + Name der Kategorie
     *
     * @param array $hotelbuchungen
     * @return array
     */
    protected function ermittelnStandardBelegungZimmer(array $hotelbuchungen)
    {
        for($i=0; $i < count($hotelbuchungen); $i++){
            $datenDerRate = $this->tabelleOtaRatesConfig->find($hotelbuchungen[$i]['otaRatesConfigId'])->toArray();
            $datenDerKategorie = $this->tabelleCategories->find($datenDerRate[0]['category_id'])->toArray();

            $hotelbuchungen[$i]['standardBelegungZimmer'] = $datenDerKategorie[0]['standard_persons'];

            if($this->anzeigeSpracheId == 1)
                $hotelbuchungen[$i]['categorieName'] = $datenDerKategorie[0]['categorie_name'];
            else
                $hotelbuchungen[$i]['categorieName'] = $datenDerKategorie[0]['categorie_name_en'];
        }

        return $hotelbuchungen;
    }

    /**
     * Ermittelt Gruppenrabatt, Hotel Code
     *
     * + Ermittelt die Mindesgruppenstärke für den Hotelrabatt in einem Hotel.
     * + Ermittelt den Hotel Code
     * + Löscht Datensätze für Hotels ohne Gruppenrabatt
     *
     * @param array $hotelbuchungen
     * @return array
     */
    protected function gruppenrabattHotelcode(array $hotelbuchungen)
    {
        for($i=0; $i < count($hotelbuchungen); $i++){

            // Daten Hotel
            $datenHotel = $this->tabelleProperties->find($hotelbuchungen[$i]['propertyId'])->toArray();
            $hotelbuchungen[$i]['propertyCode'] = $datenHotel[0]['property_code'];
            $hotelbuchungen[$i]['hotelName'] = $datenHotel[0]['property_name'];
            $hotelbuchungen[$i]['personenAnzahlGruppenrabatt'] = $datenHotel[0]['gruppenrabatt'];
        }

        foreach($hotelbuchungen as $key => $hotelbuchung){
            if($hotelbuchung['personenAnzahlGruppenrabatt'] == 0)
                unset($hotelbuchungen[$key]);
        }

        $hotelbuchungen = array_merge($hotelbuchungen);

        return $hotelbuchungen;
    }

    /**
     * Ermitteln Buchungsnummer und Zaehler
     *
     * @return int
     */
    protected function ermittelnBuchungsnummerZaehler()
    {
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $zaehler = $this->toolZaehler->setBuchungsnummer($buchungsnummer)->steuerungErmittlungAktuellerZaehler()->getAktuellerZaehler();

        $this->buchungsNummerId = $buchungsnummer;
        $this->zaehler = $zaehler;

        return $buchungsnummer;
    }

    /**
     * Ermittelt die Hotelbuchungen des aktuellen Warenkorbes
     *
     * @param $buchungsNummerId
     * @param $zaehler
     * @return array
     */
    protected function ermittelnHotelbuchungen($buchungsNummerId, $zaehler)
    {
        $hotelbuchungen = $this->toolHotelbuchungenWarenkorb
            ->setBuchungsNummerId($this->buchungsNummerId)
            ->setZaehler($this->zaehler)
            ->steuerungErmittlungHotelbuchungen()
            ->getHotelbuchungen();

        return $hotelbuchungen;
    }
}
 
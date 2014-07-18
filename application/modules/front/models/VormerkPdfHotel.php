<?php 
/**
* Ermittelt die Hotelbuchungen einer Vormerkung
*
* + Steuerung Ermittlung der Hotelbuchungen einer Vormerkung
* + Erstellt komplettes Datum mit verkürzten Monatsnamen entsprechend der Anzeigesprache
* + Ermittlung der Basisdaten einer Kategorie
* + Ermittelt den Hotelnamen
* + Ermitteln der Hotelbuchungen einer Vormerkung
*
* @date 12.11.2013
* @file VormerkPdfHotel.php
* @package front
* @subpackage model
*/
class Front_Model_VormerkPdfHotel
{
    // Fehler
    private $error_anfangswert_nicht_vorhanden = 2460;
    private $error_anfangswert_falsch = 2461;

    // Informationen

    // Konditionen
    private $condition_status_vormerkung = 2;
    private $condition_zaehler_aktiver_warenkorb = 0;

    // Zustände

    // Tabellen / Views
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
    private $tabelleProduktbuchung = null;
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;
    /** @var $tabelleProperties Application_Model_DbTable_properties */
    private $tabelleProperties = null;

    // Tools
    /** @var $toolBasisdatenHotel nook_ToolBasisdatenHotel */
    private $toolBasisdatenHotel = null;
    /** @var $toolRate nook_ToolBasisdatenKategorie */
    private $toolBasisdatenKategorie = null;
    /** @var $toolRate nook_ToolRate  */
    private $toolRate = null;
    /** @var $toolMonatsnamen nook_ToolMonatsnamen */
    private $toolMonatsnamen = null;


    protected $buchungsnummerId = null;
    protected $anzahlHotelbuchungen = 0;
    protected $pimple = null;
    protected $anzeigeSprache = null;
    protected $datenHotelbuchungen = array();


    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleHotelbuchung',
            'tabelleProduktbuchung',
            'tabelleBuchungsnummer',
            'tabelleProperties',
            'toolBasisdatenHotel',
            'toolBasisdatenKategorie',
            'toolRate',
            'toolMonatsnamen'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_VormerkPdfHotel
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        $kontrolle = $this->tabelleBuchungsnummer->kontrolleValue('id', $buchungsnummerId);
        if(false === $kontrolle)
            throw new nook_Exception($this->error_anfangswert_falsch);

        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @return array
     */
    public function getDatenHotelbuchungen()
    {
        return $this->datenHotelbuchungen;
    }

    /**
     * Steuerung Ermittlung der Hotelbuchungen einer Vormerkung
     *
     * @return Front_Model_VormerkPdfHotel
     */
    public function steuerungErmittlungHotelbuchungenVormerkung()
    {
        if(is_null($this->buchungsnummerId))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        $hotelbuchungenVormerkung = $this->ermittelnHotelbuchungen();
        if(count($hotelbuchungenVormerkung) > 0){

            $this->anzahlHotelbuchungen = count($hotelbuchungenVormerkung);
            $this->anzeigeSprache = nook_ToolSprache::getAnzeigesprache();

            for($i=0; $i < count($hotelbuchungenVormerkung); $i++){
                $hotelbuchung = $hotelbuchungenVormerkung[$i];

                // Basisdaten Hotelname
                $basisdatenHotel = $this->ermittelnBasisdatenHotel($hotelbuchung);
                $this->datenHotelbuchungen[$i]['hotelName'] = $basisdatenHotel['property_name'];

                // Stadtname
                $stadtName = nook_ToolStadt::getStadtNameMitStadtId($basisdatenHotel['city_id']);
                $this->datenHotelbuchungen[$i]['stadt'] = $stadtName;

                // ID der Stadt
                $this->datenHotelbuchungen[$i]['cityId'] = $basisdatenHotel['city_id'];

                // ermitteln Kategorie Id
                $ratenDaten = $this->toolRate->setRateId($hotelbuchung['otaRatesConfigId'])->getRateData();

                // Daten Kategorie
                $basisDatenKategorie = $this->ermittelnBasisdatenKategorie($ratenDaten['category_id']);

                if($this->anzeigeSprache == 'de')
                    $this->datenHotelbuchungen[$i]['kategorieName'] = $basisDatenKategorie['categorie_name'];
                else
                    $this->datenHotelbuchungen[$i]['kategorieName'] = $basisDatenKategorie['categorie_name_en'];

                // Übernachtungen
                $this->datenHotelbuchungen[$i]['uebernachtungen'] = $hotelbuchung['nights'];

                // Kategorie Preis
                $this->datenHotelbuchungen[$i]['roomPrice'] = $hotelbuchung['roomPrice'];

                // Anzahl der Zimmer
                $this->datenHotelbuchungen[$i]['anzahl'] = $hotelbuchung['roomNumbers'];

                // Anreisedatum
                $anreiseDatum = $this->anreisedatum($hotelbuchung['startDate']);
                $this->datenHotelbuchungen[$i]['komplettesAnreiseDatum'] = $anreiseDatum;

                // Gesamtpreis der Zimmer
                $this->datenHotelbuchungen[$i]['summeZimmerPreis'] = $hotelbuchung['roomNumbers'] * $hotelbuchung['nights'] * $hotelbuchung['roomPrice'];
            }
        }

        return $this;
    }

    /**
     * Erstellt komplettes Datum mit verkürzten Monatsnamen entsprechend der Anzeigesprache
     *
     * @param $startDate
     * @return string
     */
    private function anreisedatum($startDate)
    {
        $teileDatum = explode('-', $startDate);

        $monatsName = $this->toolMonatsnamen->setMonatsZiffer($teileDatum[1])->getMonatsnameShort();

        $komplettesDatum = $teileDatum[2].". ".$monatsName.". ".$teileDatum[0];

        return $komplettesDatum;
    }

    /**
     * Ermittlung der Basisdaten einer Kategorie
     *
     * @param $kategorieId
     * @param $propertyId
     * @return array
     */
    private function ermittelnBasisdatenKategorie($kategorieId)
    {
        $basisDatenKategorie = $this->toolBasisdatenKategorie
            ->setCategorieId($kategorieId)
            ->steuerungErmittlungDatenCategorie()
            ->getDatenCategorie();

        return $basisDatenKategorie;
    }

    /**
     * Ermittelt den Hotelnamen
     *
     * @param array $hotelbuchung
     * @return array
     */
    private function ermittelnBasisdatenHotel(array $hotelbuchung)
    {
        $basisdatenHotel = $this->toolBasisdatenHotel
            ->setPropertyId($hotelbuchung['propertyId'])
            ->steuerungErmittlungBasisdatenHotel()
            ->getBasisdatenHotel();

        return $basisdatenHotel;
    }

    /**
     * Ermitteln der Hotelbuchungen einer Vormerkung
     *
     * @return array
     */
    private function ermittelnHotelbuchungen()
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereStatusVormerkung = "status = ".$this->condition_status_vormerkung;
        $whereZaehlerAktiv = "zaehler = ".$this->condition_zaehler_aktiver_warenkorb;

        $select = $this->tabelleHotelbuchung->select();
        $select
            ->where($whereBuchungsnummer)
            ->where($whereZaehlerAktiv)
            ->where($whereStatusVormerkung);

        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        return $rows;
    }
}

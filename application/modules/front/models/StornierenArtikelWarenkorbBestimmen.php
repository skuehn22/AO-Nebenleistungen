<?php 
/**
* Ermittelt die Artikel des Warenkorbes die gelöscht oder storniert werden sollen
*
* + Id eines Artikels im Warenkorb der gelöscht / storniert werden soll
* + Steuerung der Ermittlung der Artikel eines Warenkorbes
* + Bestimmt die Anzahl der Artikel im Warenkorb die einer Bestandsbuchung entstammen
* + Bestimmt die Stornofrist / Stornowert der Artikel im Warenkorb
* + Ermittelt den Stornowert eines Artikel vom Typ Programmbuchung
* + Bestimmt die Artikel eines Warenkorbes oder ein einzelner Artikel vom Typ Programmbuchung
* + Bestimmt die Artikel eines Warenkorbes oder ein einzelnen Artikel vom Typ Hotelbuchung
* + Bestimmt die Artikel eines Warenkorbes oder ein einzelnen Artikel vom Typ Hotelprodukte
*
* @date 01.10.2013
* @file StornierenObserver.php
* @package front
* @subpackage model
*/ class Front_Model_StornierenArtikelWarenkorbBestimmen
{
    // Konditionen
    private $condition_zaehler_buchungsnummer = 0;
    private $condition_bereich_programme = 1;
    private $condition_artikel_status_bestandsbuchung = 3;

    // Flags
    private $flagBestandsbuchung = false; // allgemeiner Handlungsaufruf

    protected $flagBereicheSystem = array(
       'programm' => 1,
       'sachleistung' => 2,
       'bus' => 3,
       'citytransfer' => 4,
       'uebernachtung' => 6,
       'produkte' => 7
   );

    // Informationen

    // Fehler
    private $error_anzahl_datensaetze_falsch = 2160;
    private $error_unzulaessiger_wert = 2161;
    private $error_wert_fehlt = 2161;

    protected $buchungsnummerId = null;
    protected $zaehler = null;
    protected $pimple = null;
    protected $obserers = array();
    protected $artikelId = null;
    protected $artikelWarenkorb = array();
    protected $bereichId = null;

    protected $anzahlArtikelBestandsbuchung = 0;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_StornierenArtikelWarenkorbBestimmen
     */
    public function setBuchungsnummer($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_StornierenArtikelWarenkorbBestimmen
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        $this->zaehler = $zaehler;

        return $this;
    }

    /**
    * Id eines Artikels im Warenkorb der gelöscht / storniert werden soll
    *
    * @param array $artikelWarenkorb
    * @return Front_Model_StornierenArtikelWarenkorbBestimmen
    */
   public function setArtikelId($artikelId)
   {
       $artikelId = (int) $artikelId;
       $this->artikelId = $artikelId;

       return $this;
   }

    /**
     * @return Front_Model_StornierenArtikelWarenkorbBestimmen
     * @param $bereichId
     * @throws nook_Exception
     */
    public function setFlagBereich($bereichId)
   {
       $bereichId = (int) $bereichId;

       if(!in_array($bereichId, $this->flagBereicheSystem))
           throw new nook_Exception($this->error_unzulaessiger_wert);

       $this->bereichId = $bereichId;

       return $this;
   }

    /**
     * @return array
     */
    public function getArtikelStornierungWarenkorb()
    {
        return $this->artikelWarenkorb;
    }

    /**
     * Steuerung der Ermittlung der Artikel eines Warenkorbes
     *
     * + Artikel Programmbuchung
     * + Artikel Hotelbuchung
     * + Artikel Produktbuchung
     * + In Stornofrist ?
     *
     * @return Front_Model_StornierenArtikelWarenkorbBestimmen
     */
    public function bestimmeArtikelWarenkorb()
    {
        $warenkorbProgramme = $this->bestimmeArtikelWarenkorbProgrammbuchung();
        $warenkorbHotelbuchungen = $this->bestimmeArtikelWarenkorbHotelbuchungen();
        $warenkorbHotelprodukte = $this->bestimmeArtikelWarenkorbHotelprodukte();

        // Stornofrist Programme
        $warenkorbProgramme = $this->bestimmeStornofristProgramme($warenkorbProgramme);

        // Stornofrist Hotelbuchungen
        $warenkorbHotelbuchungen = $this->bestimmeStornofristHotelbuchungen($warenkorbHotelbuchungen);

        // Storno Hotelprodukte


        $artikelWarenkorb = array_merge($warenkorbProgramme, $warenkorbHotelbuchungen, $warenkorbHotelprodukte);

        $this->anzahlArtikelBestandsbuchung($artikelWarenkorb);

        $this->artikelWarenkorb = $artikelWarenkorb;

        return $this;
    }

    /**
     * Bestimmt die Anzahl der Artikel im Warenkorb die einer Bestandsbuchung entstammen
     *
     * @return int
     */
    private function anzahlArtikelBestandsbuchung($artikelWarenkorb)
    {
        $anzahlArtikelBestandsbuchung = 0;

        foreach($artikelWarenkorb as $artikel)
        {
            if($artikel['status'] > $this->condition_artikel_status_bestandsbuchung)
                $anzahlArtikelBestandsbuchung++;
        }

        $this->anzahlArtikelBestandsbuchung = $anzahlArtikelBestandsbuchung;

        return $anzahlArtikelBestandsbuchung;
    }

    /**
     * @return int
     */
    public function getAnzahlArtikelBestandsbuchung()
    {
        return $this->anzahlArtikelBestandsbuchung;
    }

    /**
     * Bestimmt die Stornofrist / Stornowert der Programme im Warenkorb
     *
     * + eine Stornierung eines Artikels nach dem Durchführungsdatum = 100% Stornowert
     * + Stornowerte werden nur für den Bereich 'Programme' berechnet
     *
     * @param $artikelWarenkorb
     * @return mixed
     */
    private function bestimmeStornofristProgramme(array $artikelWarenkorb)
    {
        $momentanesDatum = date("Y-m-d");
        $momentanesDatumUnix = strtotime($momentanesDatum);

        for($i=0; $i < count($artikelWarenkorb); $i++){

            $stornoWert = 0;
            $datumArtikelUnix = strtotime($artikelWarenkorb[$i]['datum']);
            $datumsDifferenzUnix = $datumArtikelUnix - $momentanesDatumUnix;

            if($datumsDifferenzUnix < 0)
                $stornoWert = 100;

            // Bereich Programme
            if($artikelWarenkorb[$i]['bereich'] == $this->condition_bereich_programme){
                $tageDifferenz = $datumsDifferenzUnix / 86400;
                $stornoWert = $this->berechnungStornowert($tageDifferenz, $artikelWarenkorb[$i]);
            }

            $artikelWarenkorb[$i]['stornowert'] = $stornoWert;
        }

        return $artikelWarenkorb;
    }

    /**
     * Bestimmt die Stornofrist / Stornowert der Hotelbuchungen im Warenkorb
     *
     * + muss noch eingebaut werden
     *
     * @param array $warenkorbHotelbuchungen
     * @return array
     */
    private function bestimmeStornofristHotelbuchungen(array $warenkorbHotelbuchungen)
    {


        return $warenkorbHotelbuchungen;
    }

    /**
     * Ermittelt den Stornowert eines Artikel vom Typ Programmbuchung
     *
     * + Stornowert wird in Abhängigkeit der Tage Differenz ermittelt
     *
     * @param $momentanesDatum
     * @param $datumsDifferenz
     * @param $artikel
     * @return int
     */
    private function berechnungStornowert($tageDifferenz, $artikel)
    {
        $stornoWert = 0;

        /** @var $tabelleProgrammdetailsStornokosten Application_Model_DbTable_stornofristen */
        $tabelleProgrammdetailsStornokosten = $this->pimple['tabelleProgrammdetailsStornokosten'];

        $whereArtikelId = "programmdetails_id = ".$artikel['artikel_id'];

        $cols = array(
            "prozente",
            "tage"
        );

        $select = $tabelleProgrammdetailsStornokosten->select();
        $select->from($tabelleProgrammdetailsStornokosten, $cols)->where($whereArtikelId)->order("tage DESC");
        $rows = $tabelleProgrammdetailsStornokosten->fetchAll($select)->toArray();

        if(count($rows) > 0){
            foreach($rows as $row){
                $stornoTage = (int) $row['tage'];
                if($stornoTage >= $tageDifferenz)
                    $stornoWert = $row['prozente'];
            }
        }

        return $stornoWert;
    }

    /**
     * Bestimmt die Artikel eines Warenkorbes oder ein einzelner Artikel  vom Typ Programmbuchung
     *
     * + bestmmt die Programme eines Warenkorbes oder single Programm
     * + setzt den Bereich
     *
     * @return array / bool
     */
    private function bestimmeArtikelWarenkorbProgrammbuchung()
    {
        $cols = array(
            'id',
            new Zend_Db_Expr("programmdetails_id as artikel_id"),
            'teilrechnungen_id',
            'anzahl',
            'datum',
            'status',
            'zeit'
        );

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereBuchungsnummerZaehler = "zaehler = ".$this->condition_zaehler_buchungsnummer;

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $select = $tabelleProgrammbuchung->select();
        $select
            ->from($tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereBuchungsnummerZaehler);

        // einzelner Artikel
        if($this->artikelId){
            $whereProgrammbuchungId = "id = ".$this->artikelId;
            $select->where($whereProgrammbuchungId);
        }

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        // setzen Flag Bereich der Artikel
        for($i=0; $i < count($rows); $i++){
            $rows[$i]['bereich'] = $this->flagBereicheSystem['programm'];
        }

        return $rows;
    }

    /**
     * Bestimmt die Artikel eines Warenkorbes oder ein einzelnen Artikel vom Typ Hotelbuchung
     *
     * + bestimmt die Hotelbuchungen
     * + bestimmt eine einzelne Hotelbuchung
     * + fügt Kennung Bereich Hotelbuchung hinzu
     *
     * @return array
     */
    private function bestimmeArtikelWarenkorbHotelbuchungen()
    {
        $cols = array(
            'id',
            new Zend_Db_Expr("otaRatesConfigId as artikel_id"),
            'teilrechnungen_id',
            'status',
            new Zend_Db_Expr("roomNumbers as anzahl"),
            new Zend_Db_Expr("startDate as datum")
        );

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereBuchungsnummerZaehler = "zaehler = ".$this->condition_zaehler_buchungsnummer;

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereBuchungsnummerZaehler);

        // einzelne Hotelbuchung
        if($this->artikelId){
            $whereHotelbuchungId = "id = ".$this->artikelId;
            $select->where($whereHotelbuchungId);
        }

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

        // setzen Flag Bereich der Artikel
       for($i=0; $i < count($rows); $i++){
           $rows[$i]['bereich'] = $this->flagBereicheSystem['uebernachtung'];
       }

       return $rows;
    }

    /**
     * Bestimmt die Artikel eines Warenkorbes oder ein einzelnen Artikel vom Typ Hotelprodukte
     *
     * + bestimmt die Hotelprodukte
     * + bestimmt eine einzelnes Hotelprodukt
     * + fügt Kennung Bereich Hotelprodukt hinzu
     *
     * @return array
     */
    private function bestimmeArtikelWarenkorbHotelprodukte()
    {
        $cols = array(
            'id',
            new Zend_Db_Expr("products_id as artikel_id"),
            'teilrechnungen_id',
            'anzahl',
            'status',
            new Zend_Db_Expr("anreisedatum as datum")
        );

        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummerId;
        $whereBuchungsnummerZaehler = "zaehler = ".$this->condition_zaehler_buchungsnummer;

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $select = $tabelleProduktbuchung->select();
        $select
            ->from($tabelleProduktbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereBuchungsnummerZaehler);

        // einzelne Hotelbuchung
        if($this->artikelId){
            $whereProduktbuchungId = "id = ".$this->artikelId;
            $select->where($whereProduktbuchungId);
        }

        $rows = $tabelleProduktbuchung->fetchAll($select)->toArray();

        // setzen Flag Bereich der Artikel
        // setzen Flag Bereich der Artikel
        for($i=0; $i < count($rows); $i++){
            $rows[$i]['bereich'] = $this->flagBereicheSystem['produkte'];
        }

        return $rows;
    }


}

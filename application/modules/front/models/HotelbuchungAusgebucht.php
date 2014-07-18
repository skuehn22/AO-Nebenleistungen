<?php
/**
 * Behandelt ausgebuchte Hotelbuchungen
 *
 * + nimmt die Buchungsnummern entgegen
 * + löscht ausgebuchte Hotelbuchungen
 *
 * @author stephan.krauss
 * @date 06.06.13
 * @file HotelbuchungAusgebucht.php
 * @package front
 * @subpackage model
 */
class Front_Model_HotelbuchungAusgebucht {

    // Tabellen / Views
    private $tabelleHotelbuchung = null;
    private $tabelleXmlBuchung = null;

    // Konditionen
    private $condition_status_ueberbucht = 6;
    private $condition_bereich_hotel = 6;
    private $condition_buchungstyp_zimmer = 1;

    // Flags

    // Fehler
    private $error_anfangswerte_fehlen = 1590;

    protected  $buchungsNummer = null;
    protected $ausgebuchteZimmerObjects = null;
    protected $hotelbuchungGeloeschtAnzahl = null;


    function __construct ()
    {
        /** @var  tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var  tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();
    }

    /**
     * @param array $buchungsNummern
     *
     * @param array $buchungsNummern
     * @return Front_Model_HotelbuchungAusgebucht
     */
    public function setBuchungsNummer ($buchungsNummer)
    {
        $buchungsNummer = (int) $buchungsNummer;
        $this->buchungsNummer = $buchungsNummer;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getHotelbuchungGeloeschtAnzahl()
    {
        return $this->hotelbuchungGeloeschtAnzahl;
    }

    /**
     * Loeschen von Hotelbuchungen die ausgebucht sind
     *
     * @throws nook_Exception
     */
    public function loeschenHotelbuchungen()
    {
        if( empty($this->buchungsNummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $buchungsNummer = $this->buchungsNummer;
        $this->kontrolleUebernachtungenMitUeberbuchung($buchungsNummer);
        $this->loeschenAusgebuchteZimmer($buchungsNummer);

        return $this;
    }

    /**
     * Ermittelt die Zeilenobjekte bei denen der Status auf 'ueberbucht / ausgebucht' = 6 steht
     *
     * + sucht von einer Buchungsnummer
     * + die ausgebuchten Zimmer
     *
     * @param $buchungsNummer
     * @return int
     */
    private function kontrolleUebernachtungenMitUeberbuchung($buchungsNummer){

        $whereBuchungsNummer = "buchungsnummer_id = ".$buchungsNummer;
        $whereStatus = "status = '".$this->condition_status_ueberbucht."'";

        $select = $this->tabelleHotelbuchung->select();
        $select
            ->where($whereBuchungsNummer)
            ->where($whereStatus);

        $this->ausgebuchteZimmerObjects = $this->tabelleHotelbuchung->fetchAll($select);

        return count($this->ausgebuchteZimmerObjects);
    }

    /**
     * Löschen der Zimmer die ausgebucht sind
     *
     * + 'tbl_hotelbuchung'
     * + 'tbl_xml_buchung'
     *
     * @param $buchungsNummer
     * @return int
     */
    private function loeschenAusgebuchteZimmer($buchungsNummer){
        $anzahlGeloeschteZimmer = 0;

        for($i=0; $i < count($this->ausgebuchteZimmerObjects); $i++){
            $whereHotelBuchung = array();
            $whereXmlBuchung = array();

            $ausgebuchtesZimmer = $this->ausgebuchteZimmerObjects[$i];
            $id = $ausgebuchtesZimmer->id;

            // 'tbl_hotelbuchung'
            $whereHotelBuchung[] = "id = ".$id;
            $whereHotelBuchung[] = "status = '".$this->condition_status_ueberbucht."'";
            $whereHotelBuchung[] = "buchungsnummer_id = ".$buchungsNummer;
            $this->tabelleHotelbuchung->delete($whereHotelBuchung);

            // 'tbl_xml_buchung'
            $whereXmlBuchung[] = "buchungstabelle_id = ".$id;
            $whereXmlBuchung[] = "buchungsnummer_id = ".$buchungsNummer;
            $whereXmlBuchung[] = "buchungstyp = '".$this->condition_buchungstyp_zimmer."'";
            $whereXmlBuchung[] = "bereich = ".$this->condition_bereich_hotel;
            $anzahlGeloeschteZimmer += $this->tabelleXmlBuchung->delete($whereXmlBuchung);
        }

        $this->hotelbuchungGeloeschtAnzahl = $anzahlGeloeschteZimmer;

        return $anzahlGeloeschteZimmer;
    }








} // end class

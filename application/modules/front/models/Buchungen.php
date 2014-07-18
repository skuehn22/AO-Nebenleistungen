<?php
/**
* Darstellen der bereits getätigten Buchungen des Kunden
*
* + Ermitteln der bereits gebuchter Warenkörbe des Kunden.
* + Ermittelt die Buchungshistorie eines vorhandenen Warenkorbes
* + Setzt Flag ob eine Programmbuchung vorhanden
* + Setzt Flag ob eine Hotelbuchung vorhanden
* + Ermittelt den Namen des virtuellen Server aus der 'static.ini'
* + Ermittelt ob Hotelbuchungen für diese Buchung vorliegt
* + Ermittelt die Programmbuchungen des Kunden
* + Ermitteln der Buchungsnummern des Kunden
*
* @date 02.05.2013
* @file Buchungen.php
* @package front
* @subpackage model
*/
class Front_Model_Buchungen extends nook_ToolModel implements Front_Model_BuchungenInterface
{
    // Tabellen / Views
    private $tabelleHotelbuchung = null;
    private $tabelleProduktbuchung = null;
    private $tabelleProgrammbuchung = null;
    private $tabelleBuchungsnummern = null;

    // Konditionen
    private $condition_hotelbuchung_nicht_vorhanden = 1;
    private $condition_hotelbuchung_vorhanden = 2;

    private $condition_programmbuchung_nicht_vorhanden = 1;
    private $condition_programmbuchung_vorhanden = 2;

    private $condition_bestandsbuchung = 4;
    private $condition_warenkorb_storniert = 3;

    // Flags

    // Fehler
    private $error_kunden_id_unbekannt = 1450;

    protected $kundenId = null;
    protected $buchungen = array();
    protected $serverName = null;
    protected $buchungshistorie = array();

    public function __construct()
    {
        /** @var tabelleBuchungsnummern Application_Model_DbTable_buchungsnummer */
        $this->tabelleBuchungsnummern = new Application_Model_DbTable_buchungsnummer();
        /** @var tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        /** @var tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

        // ermitteln Servername
        $this->ermittelnServername();
    }

    /**
     * @param $kundenId
     * @return Front_Model_Buchungen
     */
    public function setKundenId($kundenId)
    {
        $this->kundenId = $kundenId;

        return $this;
    }

    /**
     * @return int
     */
    public function getKundenId()
    {
        return $this->kundenId;
    }

    /**
     * Ermitteln der bereits gebuchter Warenkörbe des Kunden.
     *
     * + ermitteln der zuletzt gebuchten Warenkörbe
     * + ermitteln der Buchungshistorie
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuernErmittelnBuchungen()
    {
        if (empty($this->kundenId))
            throw new nook_Exception($this->error_kunden_id_unbekannt);

        // bestimmen der Buchungen des Kunden
        $rows = $this->ermittelnBuchungen();

        // bestimmen Status der Buchungen
        $rows = $this->bestimmenStatusDerBuchungen($rows);

        // Übernahme der Buchungen
        $this->buchungen = $rows;

        $this->ermittelnBuchungsHistorie($this->buchungen);

        for ($i = 0; $i < count($this->buchungshistorie); $i++) {
            $kontrolleHotelbuchung = $this->ermittelnHotelbuchungen($i);
            $this->setzenFlagHotelbuchung($kontrolleHotelbuchung, $i);

            $kontrolleProgrammbuchung = $this->ermittelnProgrammbuchungen($i);
            $this->setzenFlagProgrammbuchung($kontrolleProgrammbuchung, $i);

            $this->buchungshistorie[$i]['server'] = $this->serverName;
        }

        return $this;
    }

    /**
     * Ermittelt die Buchungshistorie eines vorhandenen Warenkorbes
     *
     * @param $buchungen
     * @return array
     */
    private function ermittelnBuchungsHistorie($buchungen)
    {
        $buchungshistorie = array();

        foreach ($buchungen as $buchung) {

            $maxZaehlerBuchung = $buchung['zaehler'];

            for ($i = $buchung[ 'zaehler' ]; $i > 0; $i--) {
                $buchung[ 'zaehler' ] = $i;
                $buchung[ 'buchungsnummer' ] = $buchung[ 'id' ] . '-' . $i;
                $buchung['maxZaehler'] = $maxZaehlerBuchung;
                $buchungshistorie[ ] = $buchung;
            }
        }

        $this->buchungshistorie = $buchungshistorie;

        return $buchungshistorie;
    }

    /**
     * Setzt Flag ob eine Programmbuchung vorhanden
     *
     * + 1 = keine Programmbuchung vorhanden
     * + 2 = Programmbuchung vorhanden
     *
     * @param $kontrolleProgrammbuchung
     * @param $key
     */
    private function setzenFlagProgrammbuchung($kontrolleProgrammbuchung, $key)
    {
        if ($kontrolleProgrammbuchung) {
            $flag = $this->condition_programmbuchung_vorhanden;
        } else {
            $flag = $this->condition_programmbuchung_nicht_vorhanden;
        }

        $this->buchungshistorie[ $key ][ 'programmbuchung' ] = $flag;

        return $flag;
    }

    /**
     * Setzt Flag ob eine Hotelbuchung vorhanden
     *
     * + 1 = keine Hotelbuchung vorhanden
     * + 2 = Hotelbuchung vorhanden
     *
     * @param $kontrolleHotelbuchung
     * @param $key
     */
    private function setzenFlagHotelbuchung($kontrolleHotelbuchung, $key)
    {
        if ($kontrolleHotelbuchung) {
            $flag = $this->condition_hotelbuchung_vorhanden;
        } else {
            $flag = $this->condition_hotelbuchung_nicht_vorhanden;
        }

        $this->buchungshistorie[ $key ][ 'hotelbuchung' ] = $flag;

        return $flag;
    }

    /**
     * Ermittelt den Namen des virtuellen Server aus der 'static.ini'
     */
    private function ermittelnServername()
    {

        $toolServername = new nook_ToolServername();
        $serverName = $toolServername
            ->findServername()
            ->getServerName();

        $this->serverName = $serverName;

        return;
    }

    /**
     * Ermittelt ob Hotelbuchungen für diese Buchung vorliegt
     *
     * @param $buchung
     * @return bool
     */
    private function ermittelnHotelbuchungen($i)
    {
        $buchung = $this->buchungshistorie[$i];

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl"),
            new Zend_Db_Expr("max(buchungsdatum) as buchungsdatum")
        );

        $whereBuchungsnummer = "buchungsnummer_id = " . $buchung['id'];
        $whereZaehler = "zaehler = " . $buchung[ 'zaehler' ];

        $select = $this->tabelleHotelbuchung->select();
        $select
            ->from($this->tabelleHotelbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $query = $select->__toString();

        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        if ($rows[0][ 'anzahl' ] > 0) {
            $this->buchungshistorie[$i]['date'] = $rows[ 0 ]['buchungsdatum'];
            return true;
        }

        return false;
    }

    /**
     * Ermittelt die Programmbuchungen des Kunden
     *
     * + true wenn Programmbuchungen vorhanden sind
     * + false wenn keine Programmbuchungen vorhanden sind
     * + ermittelt Buchungszeit der Programmbuchung
     *
     * @param $buchung
     * @return bool
     */
    private function ermittelnProgrammbuchungen($i)
    {
        $buchung = $this->buchungshistorie[$i];

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl"),
            new Zend_Db_Expr("max(date) as date")
        );

        $whereBuchungsnummer = "buchungsnummer_id = " . $buchung[ 'id' ];
        $whereZaehler = "zaehler = " . $buchung['zaehler'];

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->from($this->tabelleProgrammbuchung, $cols)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $query = $select->__toString();

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        if ($rows[ 0 ][ 'anzahl' ] > 0) {

            $rows[0]['date'] = trim($rows[0]['date']);
            $teileDatum = explode(' ',$rows[0]['date']);
            $zeit = nook_ToolZeiten::kappenZeit($teileDatum[1], 2);
            $rows[0]['date'] = $teileDatum[0]." ".$zeit;

            $this->buchungshistorie[$i]['date'] = $rows[0]['date'];
            return true;
        }

        return false;

    }

    /**
     * Ermitteln der Buchungsnummern des Kunden
     * die bereits abgeschlossen sind
     *
     * @return array
     */
    private function ermittelnBuchungen()
    {

        $cols = array(
            'id',
            'date',
            'zaehler',
            'hobNummer'
        );

        $whereKundenId = "kunden_id = " . $this->kundenId;
        $whereStatus = "status >= " . $this->condition_bestandsbuchung;

        $select = $this->tabelleBuchungsnummern->select();
        $select
            ->from($this->tabelleBuchungsnummern, $cols)
            ->where($whereKundenId)
            ->where($whereStatus)
            ->order("id desc");

        $rows = $this->tabelleBuchungsnummern->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * Bestimmt den Status der Buchungen
     *
     * + gibt an ob der Warenkorb storniert wurde
     * + 1 = keine Stornierung
     * + 2 = Stornierung
     *
     * @param $rows
     * @return array
     */
    private function bestimmenStatusDerBuchungen($rows)
    {
        for($i=0; $i < count($rows); $i++){
            $buchung = $rows[$i];

            $toolStatusBestellung = new nook_ToolStatusBestellung();
            $statusWarenkorb = $toolStatusBestellung
                ->setBuchungsnummer($buchung['id'])
                ->setZaehler($buchung['zaehler'])
                ->ermittelnStatusWarenkorb()
                ->getStatusWarenkorb();

            if($statusWarenkorb == $this->condition_warenkorb_storniert)
                $rows[$i]['stornierung'] = 2;
            else
                $rows[$i]['stornierung'] = 1;
        }

        return $rows;
    }

    /**
     * @param $kundenId
     * @return bool
     */
    public function validateKundenId($kundenId)
    {

        $kundenId = trim($kundenId);
        $kundenId = (int) $kundenId;
        if (is_int($kundenId) and $kundenId > 0) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getBuchungsHistorie()
    {
        return $this->buchungshistorie;
    }
}

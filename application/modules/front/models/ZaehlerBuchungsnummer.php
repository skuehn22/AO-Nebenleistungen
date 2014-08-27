<?php
/**
* Verändert den Zähler der Buchungsnummer
*
* + Erhöht den Zaehler der Buchungsnummer
* + Steuert das finden der Buchungsnummer und des Zählers der aktuellen Buchung
* + Verändern des Zählers in den Tabellen
* + Verändert den Zähler der 'aktiven' Datensätze der gebuchten Artikel
* + Update des Zähler in der Buchungstabelle
* + Ermittelt die Buchungsnummer und den Zaehler der aktuellen Buchung
* + Ermittelt die aktuelle Session ID
* + Ermittelt die Buchungsnummer und den Zähler einer Session
*
* @author Stephan.Krauss
* @date 19.06.13
* @file ZaehlerBuchungsnummer.php
* @package front
* @subpackage model
*/
class Front_Model_ZaehlerBuchungsnummer implements Front_Model_ZaehlerBuchungsnummerInterface
{
    // Tabellen / Views
    private $tabelleProgrammbuchung = null;
    private $tabelleBuchungsnummer = null;
    private $tabelleHotelbuchung = null;
    private $tabelleProduktbuchung = null;
    private $tabelleXmlBuchung = null;

    // Fehler
    private     $error_anzahl_datensaetze_stimmt_nicht = 1670;

    // Konditionen
    private $condition_datensaetze_in_bearbeitung = 0;

    // Flags

    protected $pimple = null;
    protected $sessionId = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;

    public function __construct($pimple = false)
    {

         if(!empty($pimple))
             $this->pimple = $pimple;

         $this->servicecontainer();

        /** @var  tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->tabelleBuchungsnummer = $this->pimple[ 'tabelleBuchungsnummer' ];
        /** @var  tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->tabelleProgrammbuchung = $this->pimple[ 'tabelleProgrammbuchung' ];
        /** @var  tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->tabelleHotelbuchung = $this->pimple[ 'tabelleHotelbuchung' ];
        /** @var  tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->tabelleProduktbuchung = $this->pimple[ 'tabelleProduktbuchung' ];
        /** @var  tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->tabelleXmlBuchung = $this->pimple[ 'tabelleXmlBuchung' ];

        return;
    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleBuchungsnummer')){
            $this->pimple['tabelleBuchungsnummer'] = function(){
                return new Application_Model_DbTable_buchungsnummer();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProgrammbuchung')){
            $this->pimple['tabelleProgrammbuchung'] = function(){
                return new Application_Model_DbTable_programmbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleHotelbuchung')){
            $this->pimple['tabelleHotelbuchung'] = function(){
                return new Application_Model_DbTable_hotelbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProduktbuchung')){
            $this->pimple['tabelleProduktbuchung'] = function(){
                return new Application_Model_DbTable_produktbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleXmlBuchung')){
            $this->pimple['tabelleXmlBuchung'] = function(){
                return new Application_Model_DbTable_xmlBuchung();
            };
        }

        return;
    }

    /**
     * Erhöht den Zaehler der Buchungsnummer
     *
     * @return $this
     */
    public function erhoehenZaehler()
    {
        $this->zaehler++;

        return $this;
    }

    /**
     * @return int
     */
    public function getBuchungsnummer()
    {
        return $this->buchungsnummer;
    }

    /**
     * @return int
     */
    public function getZaehler()
    {
        return $this->zaehler;
    }

    /**
     * Steuert das finden der Buchungsnummer und des Zählers der aktuellen Buchung
     *
     * + ermittelt buchungsnummer
     * + ermittelt zaehler
     * + erhoeht zaehler !!!
     *
     * @return Front_Model_ZaehlerBuchungsnummer
     */
    public function findBuchungsnummerUndZaehler()
    {
        $this->buchungsnummerUndZaehler();

        return $this;
    }

    /**
     * Verändern des Zählers in den Tabellen
     *
     * + neuer Zähler 'tbl_buchungsnummer'
     * + und alle anderen Tabellen
     *
     * @return $this
     */
    public function veraendernZaehlerInTabellen()
    {
        // neuer Zaehler
        $this->neuerZaehlerInTabelleBuchungsnummer();

        $this->neuerZaehlerInTabellen('buchungsnummer');
        $this->neuerZaehlerInTabellen('programmbuchung');
        $this->neuerZaehlerInTabellen('hotelbuchung');
        $this->neuerZaehlerInTabellen('produktbuchung');
        $this->neuerZaehlerInTabellen('xmlBuchung');

        return $this;
    }

    /**
     * Verändert den Zähler der 'aktiven' Datensätze der gebuchten Artikel
     *
     * + 'tbl_hotelbuchung'
     * + 'tbl_programmbuchung'
     * + 'tbl_produktbuchung'
     *
     * @param $tabellenName
     * @return int
     */
    private function neuerZaehlerInTabellen($tabellenName)
    {
        $update = array(
            'zaehler' => $this->zaehler
        );

        $whereBuchungstabellen = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = " . $this->condition_datensaetze_in_bearbeitung
        );

        $whereBuchungsnummer = array(
            'id = ' . $this->buchungsnummer,
            'zaehler = ' . $this->condition_datensaetze_in_bearbeitung
        );

        switch ($tabellenName) {
            case 'buchungsnummer':
                $kontrolle = $this->tabelleBuchungsnummer->update($update, $whereBuchungsnummer);
                break;
            case 'programmbuchung':
                $kontrolle = $this->tabelleProgrammbuchung->update($update, $whereBuchungstabellen);
                break;
            case 'hotelbuchung':
                $kontrolle = $this->tabelleHotelbuchung->update($update, $whereBuchungstabellen);
                break;
            case 'produktbuchung':
                $kontrolle = $this->tabelleProduktbuchung->update($update, $whereBuchungstabellen);
                break;
            case 'xmlBuchung':
                $kontrolle = $this->tabelleProduktbuchung->update($update, $whereBuchungstabellen);
                break;
        }

        return $kontrolle;
    }

    /**
     * Update des Zähler in der Buchungstabelle
     *
     * + Eingrenzung über Buchungsnummer
     *
     * @return int
     */
    private function neuerZaehlerInTabelleBuchungsnummer()
    {
        $update = array(
            'zaehler' => $this->zaehler
        );

        $whereBuchungsnummer = "id = " . $this->buchungsnummer;

        $kontrolle = $this->tabelleBuchungsnummer->update($update, $whereBuchungsnummer);

        return $kontrolle;
    }

    /**
     * Ermittelt die Buchungsnummer und den Zaehler der aktuellen Buchung
     *
     * + Variante Erstbuchung , Zähler = 0
     * + Variante Bestandsbuchung , Zähler > 0
     */
    private function buchungsnummerUndZaehler()
    {
        // wenn Bestandsbuchung
        if(Zend_Session::namespaceIsset('buchung')){
            $nameSpaceBuchung = new Zend_Session_Namespace('buchung');
            $arrayBuchung = (array) $nameSpaceBuchung->getIterator();

            $this->buchungsnummer = $arrayBuchung[ 'buchungsnummer' ];
            $this->zaehler = $arrayBuchung[ 'zaehler' ];

            if(empty($this->buchungsnummer) or empty($this->zaehler)){
                //$this->findSessionId();
                //$this->findAktuelleBuchungsnummerUndZaehlerMitSession();
            }
        }
        // wenn neue Buchung
        else{
            //$this->findSessionId();
            //$this->findAktuelleBuchungsnummerUndZaehlerMitSession();
        }

        return;
    }

    /**
     * Ermittelt die aktuelle Session ID
     *
     * @return string
     */
    private function findSessionId()
    {
        $this->sessionId = nook_ToolSession::getSessionId();

        return $this->sessionId;
    }

    /**
     * Ermittelt die Buchungsnummer und den Zähler einer Session
     *
     * + Buchungsnummer
     * + Session
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function findAktuelleBuchungsnummerUndZaehlerMitSession()
    {
        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->tabelleBuchungsnummer;
        $select = $tabelleBuchungsnummer->select();
        $select->where("session_id = '" . $this->sessionId . "'");

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception('Anzahl der Datensaetze in tbl_buchungsnummer stimmt nicht');
        }

        $this->buchungsnummer = $rows[ 0 ][ 'id' ];
        $this->zaehler = $rows[0]['zaehler'];

        return $rows[0]['zaehler'];
    }
} // end class

<?php
/**
* setzt einer neuen Buchungsnummer für einen Kunden
*
* + Singleton zum erstellen Front_Model_NeueBuchungsnummer
* + Lädt Klassen in den DIC
* + Steuerungeine neue Buchungsnummer für den Kunden
* + Wenn es eine Bestandsbuchung war, wird eine neue Buchungsnummer angelegt
* + Löschen Namespace der Buchung
* + Ändert den Datensatz der alten Buchungsnummer
* + Wenn keine Bestandsbuchung, dann wird Buchungsnummer und Zaehler der aktuellen Buchung bestimmt
* + Bestimmung Datensatz alte Buchungsnummer
* + Löscht die aktuellen Datensätze einer Buchungsnummer in den buchungstabellen
* + Bestimmen ob eine Bestandsbuchung verwandt wird
* + Bestimmen der Session ID
*
* @author Stephan.Krauss
* @date 20.06.13
* @file ToolNeueBuchungsnummer.php
* @package tools
*/
class Front_Model_NeueBuchungsnummer
{

    // Error
    private $error_anzahl_datensaetze_stimmt_nicht = 1680;

    // Konditionen
    private $condition_neue_Zaehler = 0;

    // Flags
    private $flag_verwendung_bestandsbuchung = null;

    protected $alteBuchungsnummer = null;
    protected $neueBuchungsnummer = null;
    protected $alteZaehler = null;
    protected $sessionId = null;

    protected $pimple = null;
    protected static $instance = null;

    /**
     * Singleton zum erstellen Front_Model_NeueBuchungsnummer
     *
     * + generiert wenn nötig den Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    public static function getInstance(Pimple_Pimple $pimple = null)
    {
        if (!self::$instance) {
            if(!$pimple)
                $pimple = new Pimple_Pimple();

            $pimple = self::buildPimple($pimple);
            self::$instance = new Front_Model_NeueBuchungsnummer($pimple);
        }

        return self::$instance;
    }

    /**
     * Lädt Klassen in den DIC
     *
     * @param $pimple
     * @return mixed
     */
    private static function buildPimple($pimple)
    {
        $pimple[ 'tabelleBuchungsnummer' ] = function ($c) {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple[ 'tabelleHotelbuchung' ] = function ($c) {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple[ 'tabelleProduktbuchung' ] = function ($c) {
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple[ 'tabelleProgrammbuchung' ] = function ($c) {
            return new Application_Model_DbTable_programmbuchung();
        };

        return $pimple;
    }

    function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @return int
     */
    public function getBuchungsnummer()
    {
        if ($this->neueBuchungsnummer) {
            return $this->neueBuchungsnummer;
        } else {
            return $this->alteBuchungsnummer;
        }
    }

    /**
     * @return int
     */
    public function getConditionNeueZaehler()
    {
        return $this->condition_neue_Zaehler;
    }

    /**
     * @return boolean
     */
    public function getFlagVerwendungBestandsbuchung()
    {
        return $this->flag_verwendung_bestandsbuchung;
    }

    /**
     * Steuerung eine neue Buchungsnummer für den Kunden
     *
     * + Bestimmung ob eine Bestandsbuchung verwandt wird
     * + wenn keine Bestandsbuchung bestimmen Werte aktuelle Buchung
     * + löschen Buchungstabellen
     *
     * @return Front_Model_NeueBuchungsnummer
     */
    public function aktiveBuchungLoeschen()
    {
        $this->bestimmeSessionId();
        $this->bestimmenVerwendungBestandsbuchung();

        // wenn keine Bestandsbuchung
        if (empty($this->flag_verwendung_bestandsbuchung)) {
            $this->bestimmenAktuelleBuchung();
        }

        // löschen Buchungstabellen
        $this->loescheDatensaetzeBuchungstabellen('hotelbuchung');
        $this->loescheDatensaetzeBuchungstabellen('produktbuchung');
        $this->loescheDatensaetzeBuchungstabellen('programmbuchung');

        return $this;
    }

    /**
     * Wenn es eine Bestandsbuchung war, wird eine neue Buchungsnummer angelegt
     *
     * @return Front_Model_NeueBuchungsnummer
     */
    public function neueBuchungsnummerAnlegen()
    {
        if ($this->flag_verwendung_bestandsbuchung) {
            $this->anlegenNeueBuchungsnummer();
            $this->aendernAlteBuchungsnummer();
            $this->loeschenNamespaceBuchung();
        }

        return $this;
    }

    /**
     * Löschen Namespace der Buchung
     */
    private function loeschenNamespaceBuchung()
    {
        $namespaceBuchung = new Zend_Session_Namespace('buchung');
        $arrayNamespaceBuchung = (array) $namespaceBuchung->getIterator();

        if (isset($arrayNamespaceBuchung[ 'buchungsnummer' ]) and isset($arrayNamespaceBuchung[ 'zaehler' ])) {
            $namespaceBuchung->buchungsnummer = null;
            $namespaceBuchung->zaehler = null;
        }
    }

    /**
     * Ändert den Datensatz der alten Buchungsnummer
     *
     * @return int
     */
    private function aendernAlteBuchungsnummer()
    {
        $update = array(
            'session_id' => ''
        );

        $where = array(
            "id = " . $this->alteBuchungsnummer,
            "zaehler = " . $this->alteZaehler
        );

        /** @var  $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple[ 'tabelleBuchungsnummer' ];
        $kontrolle = $tabelleBuchungsnummer->update($update, $where);

        return $kontrolle;
    }

    /**
     * Wenn keine Bestandsbuchung, dann wird Buchungsnummer und Zaehler der aktuellen Buchung bestimmt
     *
     * + buchungsnummer
     * + zaehler
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function bestimmenAktuelleBuchung()
    {
        $cols = array(
            'id',
            'zaehler'
        );

        $whereSessionId = "session_id = '" . $this->sessionId . "'";

        /** @var  $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple[ 'tabelleBuchungsnummer' ];
        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where($whereSessionId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);
        }

        $this->alteBuchungsnummer = $rows[ 0 ][ 'id' ];
        $this->alteZaehler = $rows[ 0 ][ 'zaehler' ];

        return $rows[ 0 ][ 'id' ];
    }

    /**
     * Bestimmung Datensatz alte Buchungsnummer
     *
     * + wenn es sich um die Bearbeitung einer Bestandsbuchung handelte
     * + setzt session_id der alten Buchungsnummer auf null
     */
    private function anlegenNeueBuchungsnummer()
    {
        $whereAlteBuchungsnummer = "id = " . $this->alteBuchungsnummer;
        $whereAlteZaehler = "zaehler = " . $this->alteZaehler;

        /** @var  $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple[ 'tabelleBuchungsnummer' ];
        $select = $tabelleBuchungsnummer->select();
        $select
            ->where($whereAlteBuchungsnummer)
            ->where($whereAlteZaehler);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);
        }

        // anpassen Datensatz
        $neueBuchungsnummer = array(
            'session_id'   => $rows[ 0 ][ 'session_id' ],
            'sess_data'    => $rows[ 0 ][ 'sess_data' ],
            'kunden_id'    => $rows[ 0 ][ 'kunden_id' ],
            'superuser_id' => $rows[ 0 ][ 'superuser_id' ]
        );

        $this->neueBuchungsnummer = $tabelleBuchungsnummer->insert($neueBuchungsnummer);

        return $this->neueBuchungsnummer;
    }

    /**
     * Löscht die aktuellen Datensätze einer Buchungsnummer in den buchungstabellen
     *
     * + Tabelle hotelbuchung
     * + Tabelle produktbuchung
     * + Tabelle programmbuchung
     *
     * @param $tabellenName
     * @return int
     */
    private function loescheDatensaetzeBuchungstabellen($tabellenName)
    {
        $delete = array(
            "buchungsnummer_id = " . $this->alteBuchungsnummer,
            "zaehler = " . $this->condition_neue_Zaehler
        );

        switch ($tabellenName) {
            case 'hotelbuchung':
                /** @var  $tabelle Application_Model_DbTable_hotelbuchung */
                $tabelleHotelbuchung = $this->pimple[ 'tabelleHotelbuchung' ];
                $kontrolle = $tabelleHotelbuchung->delete($delete);
                break;
            case 'produktbuchung':
                /** @var  $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
                $tabelleProduktbuchung = $this->pimple[ 'tabelleProduktbuchung' ];
                $kontrolle = $tabelleProduktbuchung->delete($delete);
                break;
            case 'programmbuchung':
                /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
                $tabelleProgrammbuchung = $this->pimple[ 'tabelleProgrammbuchung' ];
                $kontrolle = $tabelleProgrammbuchung->delete($delete);
                break;
        }

        return $kontrolle;
    }

    /**
     * Bestimmen ob eine Bestandsbuchung verwandt wird
     *
     * @return bool
     */
    private function bestimmenVerwendungBestandsbuchung()
    {
        $sessionBestandsbuchung = new Zend_Session_Namespace('buchung');
        $arraySessionBestandsbuchung = (array) $sessionBestandsbuchung->getIterator();

        if ((!isset($arraySessionBestandsbuchung[ 'buchungsnummer' ])) and (!isset($arraySessionBestandsbuchung[ 'zaehler' ]))) {
            $this->flag_verwendung_bestandsbuchung = false;
        } else {
            $this->alteBuchungsnummer = $arraySessionBestandsbuchung[ 'buchungsnummer' ];
            $this->alteZaehler = $arraySessionBestandsbuchung[ 'zaehler' ];
            $this->flag_verwendung_bestandsbuchung = true;
        }

        return $this->flag_verwendung_bestandsbuchung;
    }

    /**
     * Bestimmen der Session ID
     *
     * @return string
     */
    private function bestimmeSessionId()
    {
        $this->sessionId = nook_ToolSession::getSessionId();

        return $this->sessionId;
    }
}

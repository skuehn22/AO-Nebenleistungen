<?php
/**
 * Gibt die aktuelle Buchungsnummer und die Benutzer ID zurück
 *
 * + Ermitteln Benutzer ID
 * + Ermitteln Buchungsnummer
 * + Status Bestandsbuchung / neue Buchung
 * + Zaehler
 *
 * @author Stephan.Krauss
 * @date 21.06.13
 * @file InformationBenutzerBuchung.php
 * @package front
 * @subpackage mapper
 */

class Front_Model_InformationBenutzerBuchung implements Front_Model_InformationBenutzerBuchungInterface
{
    // Error
    private $error_anzahl_datensaetze_stimmt_nicht = 1690;

    // Konditionen
    private $condition_kunden_id_unbekannt = ' ';
    private $condition_zaehler_neue_buchung = 0;

    // Flags
    private $flag_buchung_ist_bestandsbuchung = null;

    protected static $instance = null;
    protected $pimple = null;
    protected $kundenId = null;
    protected $buchungsnummerId = null;
    protected $sessionId = null;
    protected $zaehler = null;
    protected $buchungsnummerIdZaehler = null;

    protected $buchungsDatensatz = array();

    /**
     * Erstellt Singleton
     *
     * @param $pimple
     * @return Front_Model_InformationBenutzerBuchung|null
     */
    public static function getInstance($pimple)
    {
        if (!self::$instance) {
            $pimple = self::buildPimple($pimple);
            self::$instance = new Front_Model_InformationBenutzerBuchung($pimple);
        }

        return self::$instance;
    }

    /**
     * Baut den DIC
     *
     * @param Pimple_Pimple $pimple
     * @return Pimple_Pimple
     */
    private static function buildPimple(Pimple_Pimple $pimple)
    {
        $pimple['tabelleBuchungsnummer'] = function ($c) {
            return new Application_Model_DbTable_buchungsnummer();
        };

        return $pimple;
    }

    /**
     * Übernimmt DIC
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * Steuert Ermittlung Datensatz 'tbl_buchungsnummer'
     *
     * + zuordnen der Daten, wenn genau ein Datensatz in 'tbl_buchungsnummer' vorhanden ist
     *
     * @return Front_Model_InformationBenutzerBuchung
     */
    public function generateBuchungsnummerKundenId()
    {
        $this->findSessionId();
        $rows = $this->findBuchungsnummerKundenId();

        if(count($rows) > 1)
            throw new nook_Exception("Anzahl Datensaetze in 'tbl_buchungsnummer' zu gross");

        if(count($rows) == 1)
            $this->zuordnenDaten($rows[0]);

        return $this;
    }

    /**
     * Bereitet die Daten des Buchungsdatensatzes auf
     *
     * + Kunden ID wenn vorhanden
     * + Buchungsnummer
     * + komplette Buchungsnummer
     * + Zaehler
     *
     * @param array $buchungsDatensatz
     * @return string
     */
    private function zuordnenDaten(array $buchungsDatensatz)
    {
        $aufbereiteteBuchungsDatensatz = array();

        // wenn Kunden ID vorhanden
        if (!empty($buchungsDatensatz['kunden_id'])) {
            $this->kundenId = $buchungsDatensatz['kunden_id'];
            $aufbereiteteBuchungsDatensatz['kundenId'] = $buchungsDatensatz['kunden_id'];
        } else {
            $this->kundenId = $this->condition_kunden_id_unbekannt;
            $aufbereiteteBuchungsDatensatz['kundenId'] = $this->condition_kunden_id_unbekannt;
        }

        // Kontrolle ob Bestandsbuchung
        if ($buchungsDatensatz['zaehler'] > $this->condition_zaehler_neue_buchung) {
            $this->flag_buchung_ist_bestandsbuchung = true;
            $aufbereiteteBuchungsDatensatz['bestandsbuchung'] = true;
        } else {
            $this->flag_buchung_ist_bestandsbuchung = false;
            $aufbereiteteBuchungsDatensatz['bestandsbuchung'] = false;
        }

        $this->buchungsnummerId = $buchungsDatensatz['id'];
        $aufbereiteteBuchungsDatensatz['buchungsnummerId'] = $buchungsDatensatz['id'];

        $this->zaehler = $buchungsDatensatz['zaehler'];
        $aufbereiteteBuchungsDatensatz['zaehler'] = $buchungsDatensatz['zaehler'];

        $this->buchungsnummerIdZaehler = $buchungsDatensatz['id'] . "-" . $buchungsDatensatz['zaehler'];

        if($buchungsDatensatz['registrierungsnummer'] > 0)
            $aufbereiteteBuchungsDatensatz['kompletteBuchungsnummer'] = $buchungsDatensatz['registrierungsnummer'] . "-" . $buchungsDatensatz['zaehler'];
        else
            $aufbereiteteBuchungsDatensatz['kompletteBuchungsnummer'] = ' ';

        $this->buchungsDatensatz = $aufbereiteteBuchungsDatensatz;

        return $this->buchungsnummerIdZaehler;
    }

    /**
     * Ermittelt Datensatz aus 'tbl_buchungsnummer'
     *
     * + gibt zurück 'id' , 'kunden_id' , 'zaehler'
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function findBuchungsnummerKundenId()
    {
        $cols = array(
            'id',
            new Zend_Db_Expr("hobNummer as registrierungsnummer"),
            'zaehler',
            'kunden_id'
        );

        $whereSessionId = "session_id = '" . $this->sessionId . "'";

        /** @var  $tablleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tablleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $select = $tablleBuchungsnummer->select();
        $select
            ->from($tablleBuchungsnummer, $cols)
            ->where($whereSessionId);

        $rows = $tablleBuchungsnummer->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * Ermitteln der Session ID
     *
     * @return string
     */
    private function findSessionId()
    {
        $this->sessionId = nook_ToolSession::getSessionId();

        return $this->sessionId;
    }

    /**
     * @return int
     */
    public function getBuchungsnummerId()
    {
        return $this->buchungsnummerId;
    }

    /**
     * @return int
     */
    public function getKundenId()
    {
        return $this->kundenId;
    }

    /**
     * @return string
     */
    public function getBuchungsnummerIdZaehler()
    {
        return $this->buchungsnummerIdZaehler;
    }

    /**
     * Ist die buchung eine Bestandsbuchung ?
     *
     * @return bool
     */
    public function getFlagBuchungIstBestandsbuchung()
    {
        return $this->flag_buchung_ist_bestandsbuchung;
    }

    /**
     * Buchungsdatensatz Array für Template
     *
     * @return array
     */
    public function getBuchungsdaten()
    {
        return $this->buchungsDatensatz;
    }
} // end class

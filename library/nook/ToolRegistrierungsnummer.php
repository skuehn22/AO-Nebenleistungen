<?php
/**
* Verwaltung der Registrierungsnummer / HOB NUmmer, hobnummer HOBNummer einer Buchung
*
* + Servicecontainer
* + Steuert die Ermittlung der Registrierungsnummer
* + Ermittelt die Registrierungsnummer eines Buchungsdatensatzes
* + steuert die Ermittlung der Buchungsnummer mit der Session ID
* + Ermitteln der Buchungsnummer mittels Session Id
*
* @date 06.11.2013
* @file ToolGegistrierungsnummer.php
* @package tools
*/
class nook_ToolRegistrierungsnummer
{
    // Fehler
    private $error_anfangswerte_fehlen = 2350;
    private $error_anzahl_datensaetze_falsch = 2351;

    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;

    // Konditionen

    // Flags
    private $flagHasRegistrierungsnummer = false;

    protected $registrierungsnummer = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;

    public function __construct()
    {
        $this->servicecontainer();

    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return;
    }

    /**
     * @param $buchungsnummer
     * @return nook_ToolRegistrierungsnummer
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Registrierungsnummer
     *
     * @return nook_ToolRegistrierungsnummer
     */
    public function steuerungErmittlungRegistrierungsnummer()
    {
        if (empty($this->buchungsnummer)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $this->ermittelnRegistrierungsnummer();

        return $this;
    }

    /**
     * Ermittelt die Registrierungsnummer eines Buchungsdatensatzes
     *
     * + wurde eine Registrierungsnummer vergeben
     * + ermitteln Registrierungsnummer > 0
     *
     */
    private function ermittelnRegistrierungsnummer()
    {
        $cols = array(
            'hobNummer',
            'zaehler'
        );

        $whereBuchungsnummer = "id = " . $this->buchungsnummer;

        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->from($this->tabelleBuchungsnummer, $cols)
            ->where($whereBuchungsnummer);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);
        }

        if ($rows[0]['hobNummer'] > 0) {
            $this->flagHasRegistrierungsnummer = true;
            $this->registrierungsnummer = $rows[0]['hobNummer'];
            $this->zaehler = $rows[0]['zaehler'];
        }

        return;
    }

    /**
     * steuert die Ermittlung der Buchungsnummer mit der Session ID
     *
     * @return nook_ToolRegistrierungsnummer
     */
    public function steuerungErmittelnRegistrierungsnummerMitSession()
    {
        $this->ermittelnBuchungsnummerMitSession();
        $this->steuerungErmittlungRegistrierungsnummer();

        return $this;
    }

    /**
     * Ermitteln der Buchungsnummer mittels Session Id
     *
     * @throws nook_Exception
     */
    private function ermittelnBuchungsnummerMitSession()
    {
        $sessionId = Zend_Session::getId();
        $whereSessionId = "session_id = '" . $sessionId . "'";

        $select = $this->tabelleBuchungsnummer->select();
        $select->where($whereSessionId);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

//        if (count($rows) == 0)
//            throw new nook_Exception("kein Datensatz in 'tbl_buchungsnummer vorhanden'");

        if(count($rows) > 1)
            throw new nook_Exception("zu viele Datensaetze in 'tbl_buchungsnummer'");

        $this->buchungsnummer = $rows[0]['id'];

        return;
    }

    /**
     * @return bool
     */
    public function getFlagHasRegistrierungsnummer()
    {
        return $this->flagHasRegistrierungsnummer;
    }

    /**
     * @return int / null
     */
    public function getRegistrierungsnummer()
    {
        return $this->registrierungsnummer;
    }

    /**
     * @return int
     */
    public function getBuchungsnummer()
    {
        return $this->buchungsnummer;
    }

    public function getKompletteRegistrierungsnummer()
    {
        return $this->registrierungsnummer . "-" . $this->zaehler;
    }

    /**
     * @return int
     */
    public function getZaehler()
    {
        return $this->zaehler;
    }
}

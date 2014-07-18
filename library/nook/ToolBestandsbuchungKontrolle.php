<?php
/**
 * Kontrolliert ob eine Bestandsbuchung vorliegt
 *
 * + ermittelt ob eine Bestandsbuchung vorliegt
 * + gibt Buchungsnummer der Bestandsbuchung zurück
 * + gibt Zähler der Bestandsbuchung zurück
 *
 * @author Stephan.Krauss
 * @date 24.06.13
 * @file ToolBestandsbuchungKontrolle.php
 * @package tools
 */

class nook_ToolBestandsbuchungKontrolle
{
    // Fehler
    private $error_anzahl_datensaetze_stimmt_nicht = 1790;

    protected $bestandsbuchung = false;
    protected $buchungsnummer = null;
    protected $zaehler = null;

    /**
     * Kontrolliert ob eine Bestandsbuchung vorliegt
     *
     * wurde in Session Namespace die
     * + Buchungsnummer registriert
     * + ist die Buchungsnummer nicht leer
     * + ist der Zaehler der bestandsbuchung groesser als 0
     *
     * @return nook_ToolBestandsbuchungKontrolle
     */
    public function kontrolleBestandsbuchung()
    {
        $this->ermittelnBuchungsnummerUndZaehler();


        return $this;
    }

    /**
     * Ermitteln Buchungsnummer und Session mit Session Namespace 'buchung'
     */
    private function ermittelnBuchungsnummerUndZaehler()
    {
        $sessionNamespaceBuchung = new Zend_Session_Namespace('buchung');
        $arraySessionNamespaceBuchung = (array) $sessionNamespaceBuchung->getIterator();

        if (isset($arraySessionNamespaceBuchung['buchungsnummer'])) {
            if (!empty($arraySessionNamespaceBuchung['buchungsnummer'])) {
                if ($arraySessionNamespaceBuchung['zaehler'] > 0) {
                    $this->bestandsbuchung = true;
                    $this->buchungsnummer = $arraySessionNamespaceBuchung['buchungsnummer'];
                    $this->zaehler = $arraySessionNamespaceBuchung['zaehler'];
                }
            }
        }

        return;
    }

    /**
     * @return bool
     */
    public function getKontrolleBestandsbuchung()
    {
        return $this->bestandsbuchung;
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
     * @return string
     */
    public function getKompletteBuchungsnummer()
    {
        return $this->buchungsnummer . "-" . $this->zaehler;
    }

    /**
     * Gibt die Daten des Session Namespace 'buchung' zurück
     *
     * @return array
     */
    public function alleDaten()
    {
        $alleDaten = array();
        $alleDaten['buchungsnummer'] = $this->buchungsnummer;
        $alleDaten['zaehler'] = $this->zaehler;

        return $alleDaten;
    }

    public function buchungsNummerZaehlerTabelleBuchungsnummer()
    {
        $sessionId = Zend_Session::getId();
        $whereSessionId = "session_id = '" . $sessionId . "'";

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select->where($whereSessionId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);
        }

        $this->buchungsnummer = $rows[0]['id'];
        $this->zaehler = $rows[0]['zaehler'];

        return;
    }
}

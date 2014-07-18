<?php 
 /**
 * Überprüft das versenden der Mail einer Programmbestellung an den Kunden.
 *
 * @author Stephan.Krauss
 * @date 05.11.2013
 * @file ToolMailSuperuser.php
 * @package tools
 */
 
class nook_ToolMailSuperuser
{
    // Fehler
    private $error_anfangswerte_fehlen = 2340;
    private $error_anzahl_datensaetze_falsch = 2341;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;

    // Konditionen

    // Flags
    private $flagMailAnKunde = true;

    protected $buchungsnummer = null;

    public function __construct()
    {
        $this->servicecontainer();
    }

    private function servicecontainer()
    {
        $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return;
    }

    /**
     * @return bool
     */
    public function getFlagMailAnKunde()
    {
        return $this->flagMailAnKunde;
    }

    /**
     * @param $buchungsnummer
     * @return nook_ToolMailSuperuser
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * Steuert die Ermittlung ob eine Mail an den Kunden oder den Superuser gesandt wird
     *
     * @return nook_ToolMailSuperuser
     */
    public function steuerungErmittlungVersandMail()
    {
        if(is_null($this->buchungsnummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $row = $this->ermittelnDatenBuchungsnummer();
        $flagMailAnKunde = $this->auswertenDatensatzBuchungsnummer($row);

        return $this;
    }

    /**
     * setzt den Flag $flagMailAnKunde
     *
     * + true = Mail wird an Kunde versandt
     * + false = Mail wird an Superuser versandt
     *
     * @param $row
     * @return bool
     */
    private function auswertenDatensatzBuchungsnummer($row)
    {
        $flagMailAnKunde = true;

        if(!is_null($row['superuser_id']))
            $this->flagMailAnKunde = $flagMailAnKunde;

        return $flagMailAnKunde;
    }

    /**
     * Ermittelt den Datensatz einer Buchungsnummer
     *
     * @return array
     * @throws nook_Exception
     */
    private function ermittelnDatenBuchungsnummer()
    {
        $cols = array(
            'superuser_id'
        );

        $whereBuchungsnummer = "id = ".$this->buchungsnummer;

        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->from($this->tabelleBuchungsnummer, $cols)
            ->where($whereBuchungsnummer);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return $rows[0];
    }

}

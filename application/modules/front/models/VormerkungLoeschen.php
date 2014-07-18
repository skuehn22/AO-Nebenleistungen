<?php 
 /**
 * Loeschen einer Vormerkung
 *
 * @author Stephan.Krauss
 * @date 18.02.2014
 * @file VormerkungLoeschen.php
 * @package front
 * @subpackage model
 */
class Front_Model_VormerkungLoeschen
{
    protected $buchungsNummer = null;
    protected $hobNummer = null;
    protected $sessionId = null;

    protected $tabelleBuchungsnummer = null;

    protected $condition_status_warenkorb_vorgemerkt = 2;

    /**
     * @param $sessionId
     * @return Front_Model_VormerkungLoeschen
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param $buchungsNummer
     * @return Front_Model_VormerkungLoeschen
     */
    public function setBuchungsNummer($buchungsNummer)
    {
        $buchungsNummer = (int) $buchungsNummer;
        $this->buchungsNummer = $buchungsNummer;

        return $this;
    }

    /**
     * @param $hobNummer
     * @return Front_Model_VormerkungLoeschen
     */
    public function setHobNummer($hobNummer)
    {
        $hobNummer = (int) $hobNummer;
        $this->hobNummer = $hobNummer;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return Front_Model_VormerkungLoeschen
     */
    public function setTabelleBuchungsnummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $this->tabelleBuchungsnummer = $tabelleBuchungsnummer;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_buchungsnummer
     */
    public function getTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return $this->tabelleBuchungsnummer;
    }

    /**
     * Steuert das löschen einer Vormerkung
     *
     * @return Front_Model_VormerkungLoeschen
     * @throws Exception
     */
    public function steuerungVormerkungLoeschen()
    {
        try{
            $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();

            if(is_null($this->buchungsNummer) and is_null($this->hobNummer) and is_null($this->sessionId))
                throw new nook_Exception('Ein Parameter muss vorhanden sein');

            if($this->buchungsNummer)
               $where = $this->whereBuchungsnummer($this->buchungsNummer, $this->condition_status_warenkorb_vorgemerkt);
            elseif($this->hobNummer)
                $where = $this->whereHobnummer($this->hobNummer, $this->condition_status_warenkorb_vorgemerkt);
            elseif($this->sessionId)
                $where = $this->whereSessionId($this->sessionId, $this->condition_status_warenkorb_vorgemerkt);

            $anzahlGeloeschteDatensaetze = $this->loeschenVormerkung($tabelleBuchungsnummer, $where, $this->condition_status_warenkorb_vorgemerkt);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Erstellt where - Klausel für löschen mit Session ID
     *
     * @param $sessionId
     * @param $condition_status_warenkorb_vorgemerkt
     * @return array
     */
    protected function whereSessionId($sessionId, $condition_status_warenkorb_vorgemerkt)
    {
        $where = array(
            "session_id = '".$sessionId."'",
            "status = ".$condition_status_warenkorb_vorgemerkt
        );

        return $where;
    }

    /**
     * Erstellt where - Klausel für löschen mit Buchungsnummer
     *
     * @param $buchungsNummer
     * @param $statusVorgemerkt
     * @return array
     */
    protected function whereBuchungsnummer($buchungsNummer, $statusVorgemerkt)
    {
        $where = array(
            "id = ".$buchungsNummer,
            "status = ".$statusVorgemerkt
        );

        return $where;
    }

    /**
     * Erstellt where - Klausel für löschen mit HOB Nummer
     *
     * @param $hobNummer
     * @param $statusVorgemerkt
     * @return array
     */
    protected function whereHobnummer($hobNummer,  $statusVorgemerkt)
    {
        $where = array(
            "hobNummer = ".$hobNummer,
            "status = ".$statusVorgemerkt
        );

        return $where;
    }

    /**
     * löschen der Vormerkung
     *
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @param array $where
     * @return int
     */
    protected function loeschenVormerkung(Zend_Db_Table_Abstract $tabelleBuchungsnummer,array $where)
    {
        $anzahlGeloeschteDatensaetze = $tabelleBuchungsnummer->delete($where);

        return $anzahlGeloeschteDatensaetze;
    }
}
 
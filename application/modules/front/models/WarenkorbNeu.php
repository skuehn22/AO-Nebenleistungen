<?php 
 /**
 * Legt einen neuen Warenkorb / Buchungsnummer in 'tbl_buchungsnummer' an.
 *
 * @author Stephan.Krauss
 * @date 22.01.2014
 * @file WarenkorbNeu.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbNeu
{

    protected $pimple = null;
    protected $sessionId = null;
    protected $alteBuchungsNummerId = null;
    protected $neueBuchungsnummerId = null;

    /** @var  $tabelleBuchungsnummer Zend_Db_Table_Abstract */
    protected $tabelleBuchungsnummer = null;

    public function __construct()
    {

    }

    /**
     * Übernahme Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @return Front_Model_WarenkorbNeu
     */
    public function setTabelleBuchungsnummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $this->tabelleBuchungsnummer = $tabelleBuchungsnummer;

        return $this;
    }

    /**
     * @param $buchungsNummerId
     * @return Front_Model_WarenkorbNeu
     */
    public function setAlteBuchungsnummerId($alteBuchungsNummerId)
    {
        $this->alteBuchungsNummerId = $alteBuchungsNummerId;

        return $this;
    }

    /**
     * @param $sesionId
     * @return Front_Model_WarenkorbNeu
     */
    public function setSession($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getBuchungsnummerNeu()
    {
        return $this->neueBuchungsnummerId;
    }

    /**
     * steuert das anlegen einer neuen Buchungsnummer in 'tbl_buchungsnummer'.
     *
     * + dupliziert Datensatz aus 'tbl_buchungsnummer'
     * + neue Buchungsnummer
     * + zaehler = 0
     * + status = 0
     *
     * @return Front_Model_WarenkorbNeu
     */
    public function         steuerungAnlegenNeuerWarenkorbTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            throw new nook_Exception('Tabelle Buchungsnummer fehlt');

        if(is_null($this->sessionId))
            throw new nook_Exception('Session ID fehlt');

        $alteBuchungsnummer = $this->datenAlteBuchungsnummer($this->sessionId);

        unset($alteBuchungsnummer['id']);
        $alteBuchungsnummer['zaehler'] = 0;
        unset($alteBuchungsnummer['status']);
        unset($alteBuchungsnummer['hobNummer']);
        unset($alteBuchungsnummer['gruppenname']);
        unset($alteBuchungsnummer['buchungshinweis']);

        $neueBuchungsnummerId = $this->erstellenNeueBuchungsnummerMitVorhandenerSessionId($alteBuchungsnummer);
        $this->neueBuchungsnummerId = $neueBuchungsnummerId;

        $this->loeschenSessionAlteBuchungsnummerId($this->alteBuchungsNummerId);

        return $this;
    }

    /**
     * Ermittelt die Daten des alten Buchungsdatensatz aus Tabelle 'tbl_buchungsnummer'
     *
     * @param $sessionId
     * @return int
     */
    protected function datenAlteBuchungsnummer($sessionId)
    {
        $whereSessionId = "session_id = '".$sessionId."'";

        $select = $this->tabelleBuchungsnummer->select();
        $select->where($whereSessionId);

        $alteBuchungsnummer = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $alteBuchungsnummer[0];
    }

    /**
     * Trägt die neue Buchungsnummer in 'tbl_buchungsnummer' ein
     *
     * @param $neueBuchungsnummerDaten
     * @return int
     */
    protected function erstellenNeueBuchungsnummerMitVorhandenerSessionId($neueBuchungsnummerDaten)
    {
        $neueBuchungsnummer =$this->tabelleBuchungsnummer->insert($neueBuchungsnummerDaten);

        return $neueBuchungsnummer;
    }

    /**
     * löscht die Session der alten Buchungsnummer
     *
     * @param $sessionId
     * @return int
     */
    protected function loeschenSessionAlteBuchungsnummerId($alteBuchungsnummerId)
    {
        $update = array(
            'session_id' => ''
        );

        $whereBuchungsNummerId = "id = ".$alteBuchungsnummerId;

        $anzahlUpdateDatensaetze = $this->tabelleBuchungsnummer->update($update, $whereBuchungsNummerId);

        return $anzahlUpdateDatensaetze;
    }
}
 
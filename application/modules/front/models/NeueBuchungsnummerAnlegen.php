<?php 
/**
* Anlegen einer neuen Buchungsnummer in 'tbl_buchungsnummer'
*
* @author Stephan.Krauss
* @date 18.33.2014
* @file NeueBuchungsnummerAnlegen.php
* @package front
* @subpackage model
 */
class Front_Model_NeueBuchungsnummerAnlegen
{
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    protected $tabelleBuchungsnummer = null;

    protected $buchungsnummer = null;
    protected $sesionId = null;
    protected $flagNeueBuchungsnummer = false;

    /**
     * @return bool
     */
    public function getFlagNeueBuchungsnummer()
    {
        return $this->flagNeueBuchungsnummer;
    }

    /**
     * @return Application_Model_DbTable_buchungsnummer|null
     */
    public function getTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return $this->tabelleBuchungsnummer;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return nook_ToolNeueBuchungsnummer
     */
    public function setTabelleBuchungsnummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $this->tabelleBuchungsnummer = $tabelleBuchungsnummer;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return Zend_Session::getId();
    }

    /**
     * @return int
     */
    public function getBuchungsnummer()
    {
        return $this->buchungsnummer;
    }

    /**
     * Steuerung des anlegen einer neuen Buchungsnummer
     *
     * @return nook_ToolNeueBuchungsnummer
     */
    public function steuerungErstellenNeueBuchungsnummer()
    {
        try{
            $this->getTabelleBuchungsnummer();

            $sessionId = $this->getSessionId();

            // Kontrolle ob SessionID in 'tbl_buchungsnummer'
            $flagNeueBuchungsnummer = $this->kontrolleBuchungstabelleSessionId($sessionId);
            $this->flagNeueBuchungsnummer = $flagNeueBuchungsnummer;

            // anlegen neue Buchungsnummer
            if(!empty($flagNeueBuchungsnummer)){
                $neueBuchungsnummer = $this->anlegenNeueBuchungsNummer($sessionId);
                $this->buchungsnummer = $neueBuchungsnummer;
            }
        }
        catch(Exception $e){
            throw $e;
        }

        return $this;
    }

    /**
     * Kontrolliert ob die SessionId schon in der 'tbl_buchungsnummer' ist
     *
     * @param $sessionId
     */
    protected function kontrolleBuchungstabelleSessionId($sessionId)
    {
        $whereSessionId = "session_id = '".$sessionId."'";

        $select = $this->tabelleBuchungsnummer->select();
        $select->where($whereSessionId);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) == 1){
            $this->buchungsnummer = $rows[0]['id'];
            $this->flagNeueBuchungsnummer = false;
        }
        elseif(count($rows) == 0)
            $this->flagNeueBuchungsnummer = true;
        else
            throw new nook_Exception("Anzahl Datensaetze 'tbl_buchungsnummer' falsch");

        return $this->flagNeueBuchungsnummer;
    }

    /**
     * Anlegen einer neuen Buchungsnummer in 'tbl_buchungsnummer'
     *
     * @param $sessionId
     * @return int
     */
    protected function anlegenNeueBuchungsNummer($sessionId)
    {
        $insert = array(
            "session_id" => $sessionId
        );

        $neueBuchungsNummer = $this->tabelleBuchungsnummer->insert($insert);

        return $neueBuchungsNummer;
    }
}
 
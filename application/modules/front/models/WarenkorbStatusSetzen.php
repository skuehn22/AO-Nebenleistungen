<?php 
 /**
 * Setzt den Bearbeitungsstatus eines Warenkorbes. Status Warenkorb
 *
 * @author Stephan.Krauss
 * @date 20.01.2014
 * @file WarenkorbStatusSetzen.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbStatusSetzen
{
    /** @var $tabelle Application_Model_DbTable_buchungsnummer */
    protected $tabelle = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $status = null;

    /**
     * @param $buchungsnummer
     * @return Front_Model_WarenkorbStatusSetzen
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $status
     * @return Front_Model_WarenkorbStatusSetzen
     */
    public function setStatus($status)
    {
        $status = (int) $status;
        $this->status = $status;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelle
     * @return Front_Model_WarenkorbStatusSetzen
     */
    public function setTabelle(Zend_Db_Table_Abstract $tabelle)
    {
        $this->tabelle = $tabelle;

        return $this;
    }

    /**
     * Steuert das setzen des Status in der Tabelle 'tbl_buchungsnummer'
     *
     * @return Front_Model_WarenkorbStatusSetzen
     * @throws Exception
     * @throws nook_Exception
     */
    public function steuerungSetzenStatusWarenkorb()
    {
        try{
            if(is_null($this->buchungsnummer))
                throw new nook_Exception('Buchungsnummer fehlt');

            if(is_null($this->status))
                throw new nook_Exception('Status fehlt');

            if(!$this->tabelle instanceof Application_Model_DbTable_buchungsnummer)
                throw new nook_Exception('Tabelle Buchungsnummer fehlt');

            // setzt den Status einer Buchungsnummer
            $anzahlGeaenderterBuchungen = $this->setzenStatus($this->tabelle, $this->buchungsnummer, $this->status);

            return $this;
        }
        catch(nook_Exception $e){
            throw $e;
        }
    }

    /**
     * Setzen neuer Status eines Warenkorbes in der Tabelle 'tbl_buchungsnummer'
     *
     * @param $buchungsnummer
     * @param $status
     * @return int|void
     * @throws nook_Exception
     */
    protected function setzenStatus(Zend_Db_Table_Abstract $tabelleWarenkorb, $buchungsnummer, $status)
    {
        $update = array(
            'status' => $status
        );

        $where = array(
            "id = ".$buchungsnummer
        );

        $anzahlGeaenderteDatensetze = $tabelleWarenkorb->update($update, $where);
        if($anzahlGeaenderteDatensetze > 1)
            throw new nook_Exception("Anzahl der geaenderten Datensaetze in 'tbl_buchungsnummer' zu gross ");

        return $anzahlGeaenderteDatensetze;
    }
}
 
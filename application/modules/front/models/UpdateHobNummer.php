<?php 
 /**
 * Update der HOB Nummer einer Buchungsnummer
 *
 * @author Stephan.Krauss
 * @date 18.02.2014
 * @file UpdateHobNummer.php
 * @package front
 * @subpackage model
 */
 
class Front_Model_UpdateHobNummer
{
    protected $buchungsNummer = null;
    protected $hobNummer = null;

    protected $tabelleBuchungsnummer = null;

    /**
     * @param $buchungsNummer
     * @return Front_Model_UpdateHobNummer
     */
    public function setBuchungsNummer($buchungsNummer)
    {
        $buchungsNummer = (int) $buchungsNummer;
        $this->buchungsNummer = $buchungsNummer;

        return $this;
    }

    /**
     * @param $hobNummer
     * @return Front_Model_UpdateHobNummer
     */
    public function setHobNummer($hobNummer)
    {
        $hobNummer = (int) $hobNummer;
        $this->hobNummer = $hobNummer;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return Front_Model_UpdateHobNummer
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
     * Steuert das Update der HOB Nummer einer Buchungsnummer
     *
     * @return Front_Model_UpdateHobNummer
     * @throws Exception
     */
    public function steuerungUpdateHobNummer()
    {
        try
        {
            if(is_null($this->buchungsNummer))
                throw new nook_Exception('Buchungsnummer fehlt');

            if(is_null($this->hobNummer))
                throw new nook_Exception('HOB Nummer fehlt');

            $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();

            $anzahlUpdate = $this->updateTabelleBuchungsnummer($this->buchungsNummer, $this->hobNummer, $tabelleBuchungsnummer);

            return $this;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Update der HOB Nummer einer Buchungsnummer
     *
     * @param $buchungsNummer
     * @param $hobNummer
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     * @throws nook_Exception
     */
    protected function updateTabelleBuchungsnummer($buchungsNummer, $hobNummer,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $update = array(
            'hobNummer' => $hobNummer
        );

        $whereId = "id = ".$buchungsNummer;

        $anzahlUpdate = $tabelleBuchungsnummer->update($update, $whereId);

        if($anzahlUpdate <> 1)
            throw new nook_Exception('Anzahl Update Datensaetze stimmt nicht');

        return $anzahlUpdate;
    }
}
 
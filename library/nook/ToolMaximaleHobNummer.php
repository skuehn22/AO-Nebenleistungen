<?php 
 /**
 * Ermittelt die größte HOB Nummer
 *
 * @author Stephan.Krauss
 * @date 18.02.2014
 * @file nook_ToolMaximaleHobNummer.php
 * @package tools
 */
class nook_ToolMaximaleHobNummer
{
    protected $maxHobNummer = 0;

    protected $tabelleBuchungsnummer = null;

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return nook_ToolMaximaleHobNummer
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
     * @return int
     */
    public function getMaxHobNummer()
    {
        return $this->maxHobNummer;
    }

    /**
     * Steuerung der Ermittlung der HOB Nummer
     *
     * @return nook_ToolMaximaleHobNummer
     * @throws Exception
     */
    public function steuerungErmittlungMaxHobNummer()
    {
        try{
            $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();

            $maxHobNummer = $this->ermittlungMaxHobNummer($tabelleBuchungsnummer);
            $this->maxHobNummer = $maxHobNummer;

            return $this;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Ermittlung der maximalen HOB Nummer
     *
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     */
    protected function ermittlungMaxHobNummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $cols = array(
            new Zend_Db_Expr('max(hobNummer) as maxHobNummer')
        );


        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['maxHobNummer'];
    }
}
 
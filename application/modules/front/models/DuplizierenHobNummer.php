<?php 
 /**
 * Duplizieren der HOB Nummer. neue HOB Nummer HOBNummer alte HOB Nummer
 *
 * + wenn eine Vormerkung zum aktiven Warenkorb umgeschrieben wird, dann wird die alte HOB Nummer vererbt
  *
 * @author Stephan.Krauss
 * @date 18.02.2014
 * @file DuplizierenHobNummer.php
 * @package front
 * @subpackage model
 */
class Front_Model_DuplizierenHobNummer
{
    protected $alteBuchungsnummer = null;
    protected $neueBuchungsnummer = null;
    protected $hobNummer = null;
    protected $statusBuchung = 0;

    protected $tabelleBuchungsnummer = null;

    /**
     * @param $statusBuchung
     * @return Front_Model_DuplizierenHobNummer
     */
    public function setStatusBuchung($statusBuchung)
    {
        $statusBuchung = (int) $statusBuchung;
        if($statusBuchung < 1)
            throw new nook_Exception('Status der Buchung wurde auf 1 gesetzt');

        $this->statusBuchung = $statusBuchung;

        return $this;
    }

    /**
     * @param $alteBuchungsnummer
     * @return Front_Model_DuplizierenHobNummer
     */
    public function setAlteBuchungsnummer($alteBuchungsnummer)
    {
        $alteBuchungsnummer = (int) $alteBuchungsnummer;
        if($alteBuchungsnummer == 0)
            throw new nook_Exception('alte Buchungsnummer nicht vorhanden');

        $this->alteBuchungsnummer = $alteBuchungsnummer;

        return $this;
    }

    /**
     * @param $neueBuchungsnummer
     * @return Front_Model_DuplizierenHobNummer
     */
    public function setNeueBuchungsnummer($neueBuchungsnummer)
    {
        $neueBuchungsnummer = (int) $neueBuchungsnummer;
        if($neueBuchungsnummer == 0)
            throw new nook_Exception('neue Buchungsnummer nicht vorhanden');

        $this->neueBuchungsnummer = $neueBuchungsnummer;

        return $this;
    }

    /**
     * @param $tabelleBuchungsnummer
     * @return Front_Model_DuplizierenHobNummer
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
     * Steuert die Vreerbung einer bereits vorhandenen HOB Nummer
     *
     * + Ermittlung HOB Nummer einer bereits vorhandenen Buchung
     * + Update einer zweiten Buchung mit der HOB Nummer
     *
     * @return $this
     * @throws Exception
     */
    public function steuerungDuplizierenHobNummer()
    {
        try{
            if(is_null($this->alteBuchungsnummer))
                throw new nook_Exception('alte Buchungsnummer fehlt');

            if(is_null($this->neueBuchungsnummer))
                throw new nook_Exception('neue Buchungsnummer fehlt');

            $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();

            // bestimmen HOB Nummer alte Buchung
            $hobNummer = $this->bestimmenHOBNummer($this->alteBuchungsnummer, $tabelleBuchungsnummer);

            // vererben HOB Nummer
            $anzahlUpdate = $this->vererbenHobNummer($hobNummer, $this->neueBuchungsnummer, $tabelleBuchungsnummer);
            if($anzahlUpdate > 1)
                throw new nook_Exception('Vererbung HOB Nummer fehlgeschlagen');

        }
        catch(Exception $e){
            throw $e;
        }

        return $this;
    }

    /**
     * Ermittelt die HOB Nummer einer bereits vorhandenen Buchungsnummer
     *
     * @param $alteBuchungsnummer
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     * @throws nook_Exception
     */
    protected function bestimmenHOBNummer($alteBuchungsnummer,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $cols = array(
            'hobNummer'
        );

        $whereAlteBuchungsnummer = "id = ".$alteBuchungsnummer;

        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where($whereAlteBuchungsnummer);

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) < 1)
            throw new nook_Exception('keine Buchungsnummer vorhanden');

        if(count($rows) > 1)
            throw new nook_Exception('zu viele Buchungsnummern vorhanden');

        return $rows[0]['hobNummer'];
    }

    /**
     * Vererbt eine bereits vorhandene HOB Nummer zu einer Buchungsnummer
     *
     * + wenn ein Status vorgegeben ist, wird dieser übernommen
     *
     * @param $hobNummer
     * @param $neueBuchungsnummer
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     */
    protected function vererbenHobNummer($hobNummer, $neueBuchungsnummer,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $update = array(
            'hobNummer' => $hobNummer
        );

        if($this->statusBuchung > 0){
            $update['status'] = $this->statusBuchung;
        }

        $whereBuchungsnummer = "id = ".$neueBuchungsnummer;

        $anzahlUpdate = $tabelleBuchungsnummer->update($update, $whereBuchungsnummer);

        return $anzahlUpdate;
    }
}
 
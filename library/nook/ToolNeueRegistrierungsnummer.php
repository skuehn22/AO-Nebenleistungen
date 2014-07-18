<?php

/**
 * Ermitteld die neue Registrierungsnummer ,  Hob Nummer , hobnummer für einen Warenkorb. Aktualisiert die 'tbl_buchungsnummer'
 *
 * + mit der Session ID wird die HOB Nummer / Registrierungsnummer in die 'tbl_buchungsnummer' eingetragen
 * + mit der Buchungsnummer wird die HOB Nummer / hobnummer / Registrierungsnummer in die 'tbl_buchungsnummer' eingetragen
 *
 * @author Stephan Krauss
 * @date 20.06.2014
 * @file ToolNeueRegistrierungsnummer.php
 * @project HOB
 * @package tool
 */
class nook_ToolNeueRegistrierungsnummer
{
    protected $buchungsnummerId = null;
    protected $sessionId = null;
    protected $neueHobNummer = null;

    protected $pimple = null;

    protected $flagHobNummerVorhanden = false;

    public function __construct()
    {
        $this->servicecontainer();
    }

    /**
     * Servicecontainer DIC
     */
    protected function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleBuchungsnummer'] = function () {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $buchungsnummerId
     * @return nook_ToolNeueRegistrierungsnummer
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $buchungsnummerId = (int)$buchungsnummerId;

        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @param $sessionId
     * @return nook_ToolNeueRegistrierungsnummer
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * setzt die neue HOB Nummer in 'tbl_buchungsnummer'
     *
     * @throws Exception
     */
    public function steuerungSetzenHobnummerInTblBuchungsnummer()
    {
        try {
            if ((is_null($this->buchungsnummerId)) and (is_null($this->sessionId)))
                throw new nook_Exception('Buchungsnummer und Session ID fehlen');

            if ((!is_null($this->buchungsnummerId)) and (!is_null($this->sessionId)))
                throw new nook_Exception('Zu viele Parameter');

            // ermitteln grösste HOB Nummer
            $groessteHobNummer = $this->ermittelnGroessteHobNummer($this->pimple['tabelleBuchungsnummer']);

            // neue HOB Nummer
            $groessteHobNummer++;

            $this->neueHobNummer = $groessteHobNummer;

            if (!is_null($this->buchungsnummerId))
                $where = "id = " . $this->buchungsnummerId;

            if (!is_null($this->sessionId))
                $where = "session_id = '" . $this->sessionId . "'";

            // Ist HOB Nummer vorhanden ?
            $this->flagHobNummerVorhanden = $this->hobNummerVorhanden($this->pimple['tabelleBuchungsnummer'], $where);

            if($this->flagHobNummerVorhanden == false)
                $this->ergaenzenTblBuchungsnummerUmNeueHobNummer($this->pimple['tabelleBuchungsnummer'], $this->neueHobNummer, $where);

            return $this;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Überprüft ob in 'tbl_buchungsnummer' die HOB Nummer vorhanden ist
     *
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @param $where
     * @return int
     */
    protected function hobNummerVorhanden(Zend_Db_Table_Abstract $tabelleBuchungsnummer, $where)
    {
        $cols = array(
            'hobNummer'
        );

        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols)->where($where);

        $query = $select->__toString();

        $row = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if($row[0]['hobNummer'] > 0)
            $this->flagHobNummerVorhanden = true;

        return $row[0]['hobNummer'];
    }

    /**
     * Update des Datensatzes in 'tbl_buchungsnummer' um die neue HOB Nummer
     *
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @param $neueHobNummer
     * @param $where
     */
    protected function ergaenzenTblBuchungsnummerUmNeueHobNummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer, $neueHobNummer, $where)
    {
        $update = array(
            'hobNummer' => $neueHobNummer
        );

        $kontrolle = $tabelleBuchungsnummer->update($update, $where);

        return;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     */
    protected function ermittelnGroessteHobNummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $cols = array(
            new Zend_Db_Expr("max(hobNummer) as groessteHobNummer")
        );

        $select = $tabelleBuchungsnummer->select();
        $query = $select
            ->from($tabelleBuchungsnummer, $cols)
            ->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['groessteHobNummer'];
    }

    /**
     * @return int
     */
    public function getNeueHobNummer()
    {
        return $this->neueHobNummer;
    }
}
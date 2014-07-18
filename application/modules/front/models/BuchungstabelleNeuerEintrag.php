<?php
/**
 * Kontrolliert ob der Benutzer schon in der 'tbl_buchungsnummer' eingetragen ist. neuer Eintrag buchungsnummer
 *
 * + Kontrolle ob der Benutzer schon mit einer aktuellen Session eingetragen ist
 * + Kontrolle ob die Benutzer ID schon eingetragen ist
 * + Eintragen in 'tbl_buchungsnummer'
 * + gibt Erfolg des Eintrag zurück
 * + gibt Buchungsnummer zurück
 *
 * @package front
 * @subpackage model
 */

class Front_Model_BuchungstabelleNeuerEintrag
{
    protected $sessionId = null;
    protected $benutzerId = null;

    /** @var $tabelleBuchungsnummer Zend_Db_Table_Abstract  */
    protected $tabelleBuchungsnummer = null;

    protected $flagEintragBuchungstabelle = false;
    protected $buchungsId = null;

    protected $condition_status_warenkorb_vormerkung = 2;

    /**
     * @param $benutzerId
     * @return Front_Model_BuchungstabelleNeuerEintrag
     */
    public function setBenutzerId($benutzerId)
    {
        $this->benutzerId = $benutzerId;

        return $this;
    }

    /**
     * @param $sessionId
     * @return Front_Model_BuchungstabelleNeuerEintrag
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return Front_Model_BuchungstabelleNeuerEintrag
     */
    public function setTabelleBuchungsnummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $this->tabelleBuchungsnummer = $tabelleBuchungsnummer;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getFlagEintragBuchungstabelle()
    {
        return $this->flagEintragBuchungstabelle;
    }

    /**
     * @return int
     */
    public function getBuchungsId()
    {
        return $this->buchungsId;
    }

    /**
     * @return Zend_Db_Table_Abstract
     */
    public function getTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return $this->tabelleBuchungsnummer;
    }

    /**
     * steuert das eintragen eines Datensatzes in 'tbl_buchungsnummer'
     *
     * + Steuert die Ermittlung der Anzahl der Einträge in 'tbl_buchungsnummer'
     * + wenn kein Eintrag in der Tabelle 'tbl_buchungsnummer', dann wird eine neue Buchungsnummer eingetragen
     *
     * @return Front_Model_BuchungstabelleNeuerEintrag
     * @throws Exception
     */
    public function steuerungEintragenBuchungstabelle()
    {
        try{
            if(is_null($this->sessionId) and is_null($this->benutzerId))
                throw new nook_Exception('Anfangsangaben fehlen');

            $this->getTabelleBuchungsnummer();

            $where = $this->whereKlausel($this->sessionId);

            $anzahlDatensaetzeBuchungstabelle = $this->ermittelnDatensatzBuchungstabelle($where);

            if($anzahlDatensaetzeBuchungstabelle > 0)
                $this->flagEintragBuchungstabelle = true;

            if($anzahlDatensaetzeBuchungstabelle == 0)
                $this->buchungsId = $this->buchungsDatensatzEintragen($this->benutzerId, $this->sessionId);
            elseif($anzahlDatensaetzeBuchungstabelle == 1 and !is_null($this->benutzerId)){
                $this->setzeBenutzerId($where, $this->benutzerId);
                $this->buchungsId = $this->ermittelnBuchungsnummer($where);
            }

            return $this;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Ermittelt Buchungsnummer ID aus 'tbl_buchungsnummer' mit Session ID
     *
     * @param $where
     * @return int
     */
    protected function ermittelnBuchungsnummer($where)
    {
        $cols = array(
            "id"
        );

        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->from($this->tabelleBuchungsnummer, $cols)
            ->where($where[0]);

        $query = $select->__toString();

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['id'];

    }

    /**
     * Update Datensatz 'tbl_buchungsnummer'
     *
     * @param $where
     * @param $benutzerId
     * @return int
     * @throws nook_Exception
     */
    protected function setzeBenutzerId($where, $benutzerId)
    {
        $update = array(
            "kunden_id" => $benutzerId,
            "status" => $this->condition_status_warenkorb_vormerkung
        );

        $anzahlUpdateDatensaetze = $this->tabelleBuchungsnummer->update($update, $where);

        if($anzahlUpdateDatensaetze <> 1)
            throw new nook_Exception('Anzahl Update falsch');

        return $anzahlUpdateDatensaetze;
    }

    /**
     * Trägt Datensatz in Tabelle Buchungsnummer ein
     *
     * @param $benutzerId
     * @param $sessionId
     * @return int
     */
    protected function buchungsDatensatzEintragen($benutzerId, $sessionId)
    {
        $insert = array();

        if(!is_null($benutzerId))
            $insert['kunden_id'] = $benutzerId;

        if(!is_null($sessionId))
            $insert['session_id'] = $sessionId;

        $buchungsId = $this->tabelleBuchungsnummer->insert($insert);

        return $buchungsId;
    }

    /**
     * Ermittelt die Anzahl der Buchungsdatensaetze
     *
     * @param array $where
     * @return int
     */
    protected function ermittelnDatensatzBuchungstabelle(array $where)
    {
        $cols = array(
            new Zend_Db_Expr('count(id) as anzahl')
        );

        $select = $this->tabelleBuchungsnummer->select();
        $select->from($this->tabelleBuchungsnummer, $cols);

        for($i=0; $i < count($where); $i++){
            $select->where($where[$i]);
        }

        $query = $select->__toString();

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Erstellen der Where Klausel der Abfrage
     *
     * @param $sessionId
     * @param $benutzerId
     * @return array
     */
    protected function whereKlausel($sessionId)
    {
        $where = array();

        if(!is_null($sessionId))
            $where[] = "session_id = '".$sessionId."'";

        return $where;
    }
}
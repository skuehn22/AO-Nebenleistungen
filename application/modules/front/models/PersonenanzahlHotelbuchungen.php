<?php

/**
 * Ermittelt die Personenanzahl der Hotelbuchungen eines Warenkorbes
 *
 * @author Stephan Krauss
 * @date 26.02.14
 * @package front
 * @subpackage model
 */


class Front_Model_PersonenanzahlHotelbuchungen
{
    protected $buchungsnummerId = null;
    protected $teilrechnungsId = null;

    // optionale Werte
    protected $status = null;
    protected $zaehler = null;

    /** @var $tabelleHotelbuchung Zend_Db_Table_Abstract  */
    protected $tabelleHotelbuchung = null;

    protected $personenAnzahlHotelbuchungen = 0;

    /**
     * @param $teilrechnungId
     * @return Front_Model_PersonenanzahlHotelbuchungen
     * @throws nook_Exception
     */
    public function setTeilrechnungsId($teilrechnungId)
    {
        $teilrechnungId = (int) $teilrechnungId;
        if($teilrechnungId == 0)
            throw new nook_Exception('Teilrechnung ID falsch');

        $this->teilrechnungsId = $teilrechnungId;

        return $this;
    }

    /**
     * @param $buchungsnummerId
     * @return Front_Model_PersonenanzahlHotelbuchungen
     * @throws nook_Exception
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $buchungsnummerId = (int) $buchungsnummerId;
        if($buchungsnummerId == 0)
            throw new nook_Exception('Buchungsnummer ID falsch');

        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * @param $status
     * @return Front_Model_PersonenanzahlHotelbuchungen
     * @throws nook_Exception
     */
    public function setStatus($status)
    {
        $status = (int) $status;
        if($status == 0)
            throw new nook_Exception('Status falsch');

            $this->status = $status;

        return $this;
    }

    /**
     * @param $zaehler
     * @return Front_Model_PersonenanzahlHotelbuchungen
     * @throws nook_Exception
     */
    public function setZaehler($zaehler)
    {
        $zaehler = (int) $zaehler;
        if($zaehler == 0)
            throw new nook_Exception('Zaehler falsch');

            $this->zaehler = $zaehler;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleHotelbuchung
     * @return Front_Model_PersonenanzahlHotelbuchungen
     */
    public function setTabelleHotelbuchung(Zend_Db_Table_Abstract $tabelleHotelbuchung)
    {
        $this->tabelleHotelbuchung = $tabelleHotelbuchung;

        return $this;
    }

    /**
     *
     *
     * @return Front_Model_PersonenanzahlHotelbuchungen
     * @throws Exception
     */
    public function steuerungErmittlungPersonenanzahlHotelbuchung()
    {
        try{
            if( (is_null($this->buchungsnummerId)) and (is_null($this->teilrechnungsId)) )
                throw new nook_Exception('Buchungsnummer ID oder Teilrechnungs ID fehlt');

            $tabelleHotelbuchung = $this->getTabelleHotelbuchung();

            $where = $this->whereKlausel($this->buchungsnummerId, $this->teilrechnungsId);

            $anzahlPersonenHotelbuchung = $this->ermittelnPersonenanzahlHotelbuchung($tabelleHotelbuchung, $where);
            $this->personenAnzahlHotelbuchungen = $anzahlPersonenHotelbuchung;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Ermittelt die Personenanzahl einer Hotelbuchung mittels Buchungsnummer ID
     *
     * + 'zaehler' optional
     * + 'status' optional
     *
     * @param Zend_Db_Table_Abstract $tabelleHotelbuchung
     * @param $where
     * @return int
     */
    protected function ermittelnPersonenanzahlHotelbuchung(Zend_Db_Table_Abstract $tabelleHotelbuchung, $where)
    {
        $anzahlPersonenHotelbuchung = 0;

        $cols = array(
            new Zend_Db_Expr("sum(personNumbers) as anzahl")
        );

        $select = $tabelleHotelbuchung->select();
        $select->from($tabelleHotelbuchung, $cols);

        for($i=0; $i < count($where); $i++){
            $select->where($where[$i]);
        }

        $query = $select->__toString();

        $rows = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($rows) > 0)
            $anzahlPersonenHotelbuchung = $rows[0]['anzahl'];

        return $anzahlPersonenHotelbuchung;
    }

    /**
     * Baut die where - Klausel zur Ermittlung der personenanzahl der Hotelbuchungen einer Buchung
     *
     * @param $buchungsnummerId
     * @param $teilrechnungId
     * @return array
     */
    protected function whereKlausel($buchungsnummerId, $teilrechnungId)
    {
        $where = array();

        if(is_int($buchungsnummerId))
            $where[] = "buchungsnummer_id = ".$buchungsnummerId;

        if(is_int($teilrechnungId))
            $where[] = "teilrechnungen_id = ".$teilrechnungId;

        if(is_int($this->status))
            $where[] = "status = ".$this->status;

        if(is_int($this->zaehler))
            $where[] = "zaehler = ".$this->zaehler;

        return $where;
    }

    /**
     * @return Application_Model_DbTable_hotelbuchung
     */
    public function getTabelleHotelbuchung()
    {
        if(is_null($this->tabelleHotelbuchung))
            $this->tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();

        return $this->tabelleHotelbuchung;
    }

    /**
     * @return int
     */
    public  function getPersonenAnzahlHotelbuchungen()
    {
        return $this->personenAnzahlHotelbuchungen;
    }






}
<?php

/**
 * Speichert die Suchwerte Hotel aus der Session Namespace 'hotelsuche' in die Tabelle 'tbl_buchungsnummer'
 *
 * @author Stephan Krauss
 * @date 02.07.2014
 * @file nook_ToolSpeichernWerteessionVormerkungHotel.php
 * @project HOB
 * @package tool
 */
class nook_ToolSpeichernWerteSessionVormerkungHotel
{
    protected $tabelleTeilrechnungen;
    protected $suchparameterHotelsuche = array();
    protected $teilrechnungenId = null;

    /**
     * Zugriff auf 'tbl_buchungsnummer'
     */
    public function __construct()
    {
        $this->tabelleTeilrechnungen = new Application_Model_DbTable_teilrechnungen();
    }

    /**
     * schreibt die Suchwerte der Hotelsuche in die 'tbl_teilrechnungen'
     *
     * @return nook_ToolSpeichernWerteSessionVormerkungHotel
     * @throws Exception
     */
    public function steuerungSpeichernWerteHotelsuche()
    {
        try {
            if(is_null($this->teilrechnungenId))
                throw new nook_Exception('Teilrechnung ID Hotelbuchung fehlt');

            $suchparameterHotelsuche = $this->ermittelnSuchparameterHotelsuche();
            $kontrolleUpdate = $this->speichernSuchparameterHotelsucheInTabelleTeilrechnungen($this->teilrechnungenId, $this->tabelleTeilrechnungen);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * holt die Werte der Hotelsuche aus der Tabelle 'tbl_teilrechnungen'
     *
     * @return nook_ToolSpeichernWerteSessionVormerkungHotel
     * @throws Exception
     */
    public function steuerungErmittelnWerteHotelsuche(){
        try{
            if(is_null($this->teilrechnungenId))
                throw new nook_Exception('Teilrechnung ID Hotelbuchung fehlt');

            $suchparameterHotelsuche = $this->ermitteltSuchparameterHotelsucheWarenkorb($this->teilrechnungenId, $this->tabelleTeilrechnungen);
            $suchparameterHotelsuche = $this->schreibenSuchparameterHotelsucheInSession($suchparameterHotelsuche);

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * setzen der buchungsNummerId für das auslesen der Suchparameter Hotelsuche aus 'tbl_buchungsnummer'
     *
     * @param $teilrechnungenId
     * @return nook_ToolSpeichernWerteSessionVormerkungHotel
     * @throws nook_Exception
     */
    public function setTeilrechnungenId($teilrechnungenId)
    {
        $teilrechnungenId = (int)$teilrechnungenId;
        if ($teilrechnungenId == 0)
            throw new nook_Exception('Teilrechnung ID fehlt');

        $this->teilrechnungenId = $teilrechnungenId;

        return $this;
    }

    /**
     * auslesen der Suchparameter der Namespace 'hotelsuche' aus der Session
     *
     * @return array
     */
    protected function ermittelnSuchparameterHotelsuche()
    {
        $hotelsuche = new Zend_Session_Namespace('hotelsuche');

        foreach ($hotelsuche as $key => $value) {
            $this->suchparameterHotelsuche[$key] = $value;
        }

        return $this->suchparameterHotelsuche;
    }

    /**
     * speichern der Suchparameter Hotelsuche in 'tbl_buchungsnummer'
     *
     * @param $teilrechnungenId
     * @param Zend_Db_Table_Abstract $tabelleTeilrechnungen
     * @throws nook_Exception
     */
    protected function speichernSuchparameterHotelsucheInTabelleTeilrechnungen($teilrechnungenId, Zend_Db_Table_Abstract $tabelleTeilrechnungen)
    {
        $updateCol = array(
            'parameterHotelsuche' => json_encode($this->suchparameterHotelsuche)
        );

        $where = "id = " . $teilrechnungenId;

        $kontrolleUpdate = $tabelleTeilrechnungen->update($updateCol, $where);
        if ($kontrolleUpdate > 1)
            throw new nook_Exception('Anzahl der Teilrechnungen stimmt nicht');

        return;
    }

    /**
     * liest aus der 'tbl_buchungsnummer' die Suchparameter der Hotelsuche
     *
     * @param $teilrechnungenId
     * @param Zend_Db_Table_Abstract $tabelleTeilrechnungen
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermitteltSuchparameterHotelsucheWarenkorb($teilrechnungenId, Zend_Db_Table_Abstract $tabelleTeilrechnungen)
    {
        $cols = array(
            'parameterHotelsuche'
        );

        $whereTeilrechnungenId = "id = " . $teilrechnungenId;

        $select = $tabelleTeilrechnungen->select();
        $select->from($tabelleTeilrechnungen, $cols)->where($whereTeilrechnungenId);

        $query = $select->__toString();

        $rows = $tabelleTeilrechnungen->fetchAll($select)->toArray();

        if (count($rows) <> 1)
            throw new nook_Exception('Anzahl Teilrechnungen falsch');

        $suchParameterHotelsuche = (array) json_decode($rows[0]['parameterHotelsuche']);

        return $suchParameterHotelsuche;
    }

    /**
     * schreibt die Suchparameter in den Namespace 'hotelsuche' der Session
     *
     * @param $suchparameter
     * @return Zend_Session_Namespace
     */
    protected function schreibenSuchparameterHotelsucheInSession($suchparameter)
    {
        $hotelsuche = new Zend_Session_Namespace('hotelsuche');

        foreach ($suchparameter as $key => $value) {
            $hotelsuche->$key = $value;
        }

        return $hotelsuche;
    }
}
<?php
/**
 * Ermittelt die Hotelbuchungen einer Buchungsnummer
 *
 * @author stephan.krauss
 * @date 13.06.13
 * @file Hotelbuchungen.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_Hotelbuchungen {

    // Konditionen

    // Fehler
    private $error_ausgangswerte_unvollstaendig = 1650;
    private $error_anzahl_datensaetze_stimmt_nicht = 1651;

    // Flags

    protected $pimple = null;
    protected $buchungsnummerId = null;
    protected $hotelbuchungen = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @param $buchungsnummerId
     * @return Admin_Model_Hotelbuchungen
     */
    public function setBuchungsnummerId($buchungsnummerId)
    {
        $this->buchungsnummerId = $buchungsnummerId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Hotelbuchungen einer Buchungsnummer
     *
     * @return $this
     * @throws nook_Exception
     */
    public function ermittelnHotelbuchungen()
    {
        if(empty($this->buchungsnummerId))
            throw new nook_Exception($this->error_ausgangswerte_unvollstaendig);

        $this->findeHotelbuchungen($this->buchungsnummerId);

        for($i=0; $i < count($this->hotelbuchungen); $i++){
            $this->hotelbuchungen[$i]['buchungsnummer'] = $this->hotelbuchungen[$i]['buchungsnummer_id'];
            $this->hotelbuchungen[$i]['hotel'] = $this->findeHotelname($this->hotelbuchungen[$i]['propertyId']);
            $this->hotelbuchungen[$i]['stadt'] = $this->findeStadtname($this->hotelbuchungen[$i]['cityId']);
            $this->hotelbuchungen[$i]['rate'] = $this->findeRatenname($this->hotelbuchungen[$i]['cityId']);
        }

        return $this;
    }

    private function findeRatenname($ratenId)
    {

        return;
    }

    /**
     * Ermittelt den Namen der Stadt
     *
     * @param $stadtId
     * @return mixed
     * @throws nook_Exception
     */
    private function findeStadtname($stadtId)
    {
        /** @var  $tabelleAoCity Application_Model_DbTable_aoCity */
        $tabelleAoCity = $this->pimple['tabelleAoCity'];
        $rows = $tabelleAoCity->find($stadtId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);

        return $rows[0]['AO_City'];
    }

    /**
     * Findet die Hotelbuchungen einer Buchungsnummer
     *
     * @param $buchungsnummerId
     * @return array
     */
    private function findeHotelbuchungen($buchungsnummerId)
    {
        $whereBuchungsnummerId = "buchungsnummer_id = ".$buchungsnummerId;

        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $select = $tabelleHotelbuchung->select();
        $select->where($whereBuchungsnummerId);

        $this->hotelbuchungen = $tabelleHotelbuchung->fetchAll($select)->toArray();

        return count($this->hotelbuchungen);
    }

    /**
     * Ermitteln des Hotelnamen
     *
     * @param $hotelId
     * @return array
     * @throws nook_Exception
     */
    private function findeHotelname($hotelId)
    {
        /** @var  $tabelleHotel Application_Model_DbTable_properties */
        $tabelleHotel = $this->pimple['tabelleProperties'];
        $rows = $tabelleHotel->find($hotelId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);

        return $rows[0]['property_name'];
    }

    /**
     * @return array
     */
    public function getHotelbuchungen()
    {
        return $this->hotelbuchungen;
    }
} // end class

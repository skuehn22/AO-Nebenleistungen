<?php

/**
 * Ermittelt die Grunddaten der Hotelbuchungen eines Warenkorbes
 *
 * @author Stephan Krauss
 * @date 07.07.2014
 * @file Front_Model_HotelbuchungGrunddaten.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_HotelbuchungGrunddaten
{
    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $anzeigeSpracheId = null;

    protected $grunddatenHotelbuchung = array();

    protected $condition_aktiver_warenkorb = 1;

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_HotelbuchungGrunddaten
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * @param $buchungsNummerId
     * @return Front_Model_HotelbuchungGrunddaten
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @param $anzeigeSpracheId
     * @return Front_Model_HotelbuchungGrunddaten
     */
    public function setAnzeigeSpracheId($anzeigeSpracheId)
    {
        $this->anzeigeSpracheId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Hoteldaten eines Warenkorbes  für den Reisekorb
     *
     * @return Front_Model_HotelbuchungGrunddaten
     * @throws Exception
     */
    public function steuerungErmittlungGrunddatenHotelbuchung()
    {
        try {
            if(is_null($this->buchungsNummerId))
                throw new nook_Exception('Buchungsnummer ID fehlt');

            if(is_null($this->anzeigeSpracheId))
                throw new nook_Exception('Anzeigesprache ID fehlt');

            if(is_null($this->pimple))
                throw new nook_Exception('Pimple fehlt');

            $datensaetzeHotelbuchungenWarenkorb = $this->ermittelnHotelbuchungen($this->buchungsNummerId, $this->condition_aktiver_warenkorb, $this->pimple['tabelleHotelbuchung']);
            $datensaetzeHotelbuchungenWarenkorb = $this->ermittelnHotelname($this->anzeigeSpracheId, $datensaetzeHotelbuchungenWarenkorb);
            $datensaetzeHotelbuchungenWarenkorb = $this->ermittelnRatenname($this->anzeigeSpracheId, $datensaetzeHotelbuchungenWarenkorb);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }


    protected function ermittelnHotelbuchungen($buchungsNummerId, $conditionAktiverWarenkorb, Zend_Db_Table_Abstract $tabelleHotelbuchung){
        $cols = array(
            'teilrechnungen_id',
            'propertyId',
            'cityId',
            'otaRatesConfigId',
            'roomNumbers',
            'personNumbers',
            'nights',
            'startDate'
        );

        $whereBuchungsNummerId = "buchungsnummer_id = ".$buchungsNummerId;
        $whereAktiverWarenkorb = "status = ".$conditionAktiverWarenkorb;

        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where($whereBuchungsNummerId)
            ->where($whereAktiverWarenkorb)
            ->order("teilrechnungen_id asc");

        $query = $select->__toString();




        return $datensaetzeHotelbuchungenWarenkorb;
    }

    /**
     * @return array
     */
    public function getGrunddatenHotelbuchung()
    {
        return $this->grunddatenHotelbuchung;
    }

}
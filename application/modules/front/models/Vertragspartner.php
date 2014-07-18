<?php

//include_once('../../../../autoload_cts.php');
//
//$myClass = new Front_Model_Vertragspartner();
//$adresse = $myClass
//    ->setBereich(1)
//    ->setProgrammId(951)
//    ->steuerungErmittlungAdresseVertragspartner()
//    ->getAdresse();

/**
 * Dem Kunden werden die Adressdaten des Vertrgspartner angezeigt
 *
 * @author Stephan
 * @date 14.03.14
 * @file Vertragspartner.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_Vertragspartner{

    protected $bereich = null;
    protected $hotelId = null;
    protected $programmId = null;

    protected $tabelleAdressen = null;
    protected $tabelleProgrammdetails = null;
    protected $toolLand = null;

    protected $adresse = array();

    protected $condition_bereich_programme = 1;
    protected $condition_bereich_hotel = 6;

    /**
     * @param $bereich
     * @return Front_Model_Vertragspartner
     */
    public function setBereich($bereich)
    {
        $bereich = (int) $bereich;
        $this->bereich = $bereich;

        return $this;
    }

    /**
     * @param $hotelId
     * @return Front_Model_Vertragspartner
     */
    public function setHotelId($hotelId)
    {
        $hotelId = (int) $hotelId;
        $this->hotelId = $hotelId;

        return $this;
    }

    /**
     * @param $programmId
     * @return Front_Model_Vertragspartner
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleAdressen
     * @return Front_Model_Vertragspartner
     */
    public function setTabelleAdressen(Zend_Db_Table_Abstract $tabelleAdressen)
    {
        $this->tabelleAdressen = $tabelleAdressen;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_adressen
     */
    public function getTabelleAdresse()
    {
        if(is_null($this->tabelleAdressen))
            $this->tabelleAdressen = new Application_Model_DbTable_adressen();

        return $this->tabelleAdressen;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleProgrammdetails
     * @return Front_Model_Vertragspartner
     */
    public function setTabellerogrammdetails(Zend_Db_Table_Abstract $tabelleProgrammdetails)
    {
        $this->tabelleProgrammdetails = $tabelleProgrammdetails;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_programmedetails
     */
    public function getTabelleProgrammdetails()
    {
        if(is_null($this->tabelleProgrammdetails))
            $this->tabelleProgrammdetails = new Application_Model_DbTable_programmedetails();

        return $this->tabelleProgrammdetails;
    }

    /**
     * @param nook_ToolLand $toolLand
     * @return Front_Model_Vertragspartner
     */
    public function setToolLand(nook_ToolLand $toolLand)
    {
        $this->toolLand = $toolLand;

        return $this;
    }

    /**
     * @return nook_ToolLand
     */
    public function getToolLand()
    {
        if(is_null($this->toolLand))
            $this->toolLand = new nook_ToolLand();

        return $this->toolLand;
    }

    /**
     * @return array
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * teuert die Ermittlung der Adresse des Vertragspartners
     *
     * + Unterscheidet nach Bereich Programme / Hotel
     * +
     *
     * @return Front_Model_Vertragspartner
     * @throws Exception
     */
    public function steuerungErmittlungAdresseVertragspartner()
    {
        try{
            if(is_null($this->bereich))
                throw new nook_Exception('ID Bereich fehlt');

            if(is_null($this->programmId) and is_null($this->hotelId) )
                throw new nook_Exception('Programm ID oder Hotel ID fehlt');

            $this->getTabelleAdresse();

            // Programme
            if($this->bereich == $this->condition_bereich_programme){
                if(is_null($this->programmId))
                    throw new nook_Exception('Programm ID fehlt');

                $this->getTabelleProgrammdetails();

                $adressIdProgrammAnbieter = $this->ermittlungAdressIdProgrammanbieter($this->tabelleProgrammdetails, $this->programmId);
                $adresse = $this->ermittelnAdresse($this->tabelleAdressen, $this->condition_bereich_programme, $adressIdProgrammAnbieter);
            }
            // Hotel
            elseif($this->bereich == $this->condition_bereich_hotel){
                if(is_null($this->hotelId))
                    throw new nook_Exception('Hotel ID fehlt');

                $adresse = $this->ermittelnAdresse($this->tabelleAdressen, $this->condition_bereich_hotel, $this->hotelId);
            }
            // Fehler
            else{
                throw new nook_Exception('Bereich unbekannt');
            }

            // Ländernamen
            $adresse = $this->ermittelnNameLand($adresse);

            $this->adresse = $adresse;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Wandelt die ID eines Landes in den Ländernamen um
     *
     * @param array $adresse
     * @return array
     */
    protected function ermittelnNameLand(array $adresse)
    {
        // Test ob ID des Landes vorhanden ist
        $testIdLand = (int) $adresse['country'];

        if($testIdLand > 0){
            $toolLand = new nook_ToolLand();
            $adresse['country'] = $toolLand->convertLaenderIdNachLandName($testIdLand);


        }

        return $adresse;
    }

    /**
     * Ermittelt die Adress ID des Programmanbieters
     *
     * @param Zend_Db_Table_Abstract $tabelleProgrammdetails
     * @param $programmId
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittlungAdressIdProgrammanbieter(Zend_Db_Table_Abstract $tabelleProgrammdetails, $programmId)
    {
        $rows = $tabelleProgrammdetails->find($programmId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Programmdatensaetze falsch');

        return $rows[0]['adressen_id'];
    }

    /**
     * Ermittelt die Adresse des Programmanbiters / Hotel
     *
     * @param Zend_Db_Table_Abstract $tabelleAdressen
     * @param $bereich
     * @param $idHotelOderAdressId
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittelnAdresse(Zend_Db_Table_Abstract $tabelleAdressen, $bereich, $idHotelOderAdressId)
    {
        $cols = array(
            'company',
            'city',
            'street',
            'zip',
            'country'
        );

        $select = $tabelleAdressen->select();
        $select->from($tabelleAdressen, $cols);

        // Adress ID Programmanbiter
        if($bereich == $this->condition_bereich_programme){
            $whereId = "id = ".$idHotelOderAdressId;
            $select->where($whereId);
        }
        // Hotel ID
        elseif($bereich == $this->condition_bereich_hotel){
            $wherePropertyId = "properties_id = ".$idHotelOderAdressId;
            $select->where($wherePropertyId);
        }

        $query = $select->__toString();

        $rows = $tabelleAdressen->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Adressen falsch');

        return $rows[0];
    }
}
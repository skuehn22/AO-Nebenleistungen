<?php
/**
 * Erstellt eine Liste der Hotels auf die der User zugreifen kann.
 * Dabei wird nach der Benutzerrolle und den zugeordneten Hotels geschaut.
 *
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 05.12.11
 * Time: 10:28
 * To change this template use File | Settings | File Templates.
 */
 
class nook_ZugriffAufHotels {

    private $listeDerHotels = array();
    private $_stringDerHotels;
    private $_idDesHotels;
    private $idIstInDerListeDerHotels = false;
    private $sql;

    private $kundenDaten = array();

    private $condition_kunde_ist_anbieter = 5;
    private $condition_kunde_ist_konzernverantwortlicher = 6;
    private $_condition_hotel_ist_aktiv = 3;
    private $condition_bereich_uebernachtung = 6;

    private $error_kunde_ist_nicht_bereich_uebernachtung = 470;
    private $_error_Hotel_liste_nicht_vorhanden = 471;

    private $db_hotels;

    // dürfen alle Hotels bearbeitet werden ?
    public $alleHotels = false;

    public function __construct(){
        // Datenbank
        $this->db_hotels = Zend_Registry::get('hotels');
    }

    /**
     * Gibt die Variablen des Objektes zurück
     */
    public function getObjectVars(){
        // Variablen des Objectes
        $objectVars = get_object_vars($this);

        return $objectVars;
    }

    public function getStringHotels(){
        $this->buildListeDerHotels();

        return $this->_stringDerHotels;
   }

   public function setKundenDaten(){

       $auth = new Zend_Session_Namespace('Auth');
       $authDesKunden = (array) $auth->getIterator();

       if($authDesKunden['role_id'] > $this->condition_kunde_ist_konzernverantwortlicher)
           $this->alleHotels = true;

       $this->kundenDaten = $authDesKunden;

       return $this;
   }

   public function getListeDerHotels(){

       return $this->listeDerHotels;
   }

   public function checkIstZugriffAufHotelErlaubt($__hotelId = 0){
       $this->setKundenDaten();
       $this->findListeDerHotels();
       $this->findHotelIdInList($__hotelId);

       return $this->idIstInDerListeDerHotels;
   }

   private function findHotelIdInList($__hotelId){
       $control = false;

       $this->_idDesHotels = $__hotelId;
       $control = array_map(array($this, '_checkIdDesHotels'), $this->listeDerHotels);

       return $this;
   }

   private function _checkIdDesHotels($__hotel){
       $control = false;

       if($__hotel['id'] == $this->_idDesHotels)
           $this->idIstInDerListeDerHotels = true;

       return $this;
   }


    private function buildListeDerHotels()
    {
        $boolKundeIstUebernachtungsanbieter = $this->checkKundeIstBereichUbernachtung($this->kundenDaten['role_id']);

        if(!$boolKundeIstUebernachtungsanbieter)
            throw new nook_Exception($this->error_kunde_ist_nicht_bereich_uebernachtung);

        $listeDerHotels =  $this->findListeDerHotels();
        $this->checkListeDerHotels();
        $this->buildStringDerHotels();

        return $this;
    }

    public function getSql(){

        return $this->sql;
    }

    /**
     * Kontrolle der Rolle des Kunden
     *
     * + Ist der eingeloggte Kunde ein Übernachtungsanbieter ?
     * + Ist der eingeloggte Kunde ein Administrator
     *
     * @return bool
     * @throws nook_Exception
     */
    private function checkKundeIstBereichUbernachtung($rolleId)
    {
        if($this->kundenDaten['role_id'] > $this->condition_kunde_ist_konzernverantwortlicher)
            return true;

        if( $this->kundenDaten['anbieter'] != $this->condition_bereich_uebernachtung )
            return false;

        return true;
    }

    /**
     * Ermittelt die zu verwaltenden Hotels eines Kunden in Abhängigkeit seiner Rolle
     *
     * @return int
     */
    private function findListeDerHotels(){

        // Einzelanbieter
        if($this->kundenDaten['role_id'] == $this->condition_kunde_ist_anbieter){
            $sql = "select id from tbl_properties where id = ".$this->kundenDaten['company_id']." and aktiv = ".$this->_condition_hotel_ist_aktiv;
        }
        // Konzernverantwortliche
        elseif($this->kundenDaten['role_id'] == $this->condition_kunde_ist_konzernverantwortlicher){
            $sql = "
                SELECT
                    `tbl_properties`.`id`
                FROM
                    `tbl_kunde_properties`
                    INNER JOIN `tbl_properties`
                        ON (`tbl_kunde_properties`.`properties_id` = `tbl_properties`.`id`)
                WHERE (`tbl_kunde_properties`.`kunden_id` = " .$this->kundenDaten['userId']. "
                    AND `tbl_properties`.`aktiv` = " .$this->_condition_hotel_ist_aktiv. ")";

        }
        // Redakteur und Administrator
        elseif($this->kundenDaten['role_id'] > $this->condition_kunde_ist_konzernverantwortlicher){
            $sql = "select id from tbl_properties";
        }

        $this->sql = $sql;
        $this->listeDerHotels = $this->db_hotels->fetchAll($sql);

        return count($this->listeDerHotels);
    }

    private function checkListeDerHotels(){
        if(!is_array($this->listeDerHotels) and !(count($this->listeDerHotels) > 0))
            throw new nook_Exception($this->_error_Hotel_liste_nicht_vorhanden);

        return $this;
    }

    private function buildStringDerHotels(){
        $stringHotelListe = '';

        for($i=0; $i<count($this->listeDerHotels); $i++){
            $stringHotelListe .= $this->listeDerHotels[$i]['id'].", ";
        }

        $stringHotelListe = substr($stringHotelListe, 0, -2);
        $this->_stringDerHotels = $stringHotelListe;

        return $this;
    }
}

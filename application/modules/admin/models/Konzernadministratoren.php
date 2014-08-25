<?php
class Admin_Model_Konzernadministratoren extends nook_Model_model{

    // Datenbank
	private $_db_groups;
    private $_db_hotels;
	
	// private $_error = 480;

    private $_condition_bereich_hotel = 6;
    private $_condition_kunde_ist_konzernadministrator = 6;
    private $_condition_kunde_ist_aktiv = 3;
    private $_condition_firma_ist_aktiv = 3;
    private $_condition_hotel_ist_aktiv = 3;

    private $_start;
    private $_limit;
    private $_zuBearbeitenderKonzernVerantwortlicher;

    private $dependency = array();
    private $__konzernAdministratoren = array();
    private $_hotels = array();

    private $_anzahlKonzernAdministratoren;
    private $_anzahlHotels;
    private $_datenNeuerKonzernverantwortlicher = array();
	
	public function __construct(){
		$this->_db_groups = Zend_Registry::get('front');
		$this->_db_hotels = Zend_Registry::get('hotels');

		return;
	}

    public function setDependency($name = false){

        if(!empty($name)){
            $wert = '123';
        }

        return $this;
    }

    public function setDatenKonzernVerantwortlicher($__datenKonzernVerantwortlicher){
        $__datenKonzernVerantwortlicher['status'] = $this->_condition_kunde_ist_konzernadministrator;
        $__datenKonzernVerantwortlicher['anbieter'] = $this->_condition_bereich_hotel;
        $__datenKonzernVerantwortlicher['password'] = $__datenKonzernVerantwortlicher['password1'];

        unset($__datenKonzernVerantwortlicher['password1']);
        unset($__datenKonzernVerantwortlicher['password2']);

        $this->_datenNeuerKonzernverantwortlicher = $__datenKonzernVerantwortlicher;

        return $this;
    }

    public function getDatenKonzernverantwortlicher($__suchParameterKonzernverantwortlicher){
        $sql = "select * from tbl_adressen where id = ".$__suchParameterKonzernverantwortlicher['konzernVerantwortlicherId'];
        $personenDaten = $this->_db_groups->fetchRow($sql);

        return $personenDaten;
    }

    public function checkDoubleMailAdress($__datenKonzernVerantwortlicher){
        $sql = "select count(id) as anzahl from tbl_adressen where email = '".$__datenKonzernVerantwortlicher['email']."'";
        $anzahl = $this->_db_groups->fetchOne($sql);

        //25.08.2014 S.Kuehn
        //Doppelte Mails sind nun erlaubt
        $anzahl = 0;
        if($anzahl > 0){

            $errors[0]['id'] = 'email';
			$errors[0]['msg'] = 'Mailadresse wird bereits verwandt';

            return $errors;
        }

        return;
    }

    public function getKontrolleSpeichernDatenKonzernverantwortlicher(){
         $kontrolle = $this->_db_groups->insert('tbl_adressen', $this->_datenNeuerKonzernverantwortlicher);

        return $kontrolle;
    }

    public function updateDatenKonzernverantwortlicher($__idKonzernVerantwortlicher, $__datenKonzernVerantwortlicher){

        $__datenKonzernVerantwortlicher['password'] = $__datenKonzernVerantwortlicher['password1'];
        unset($__datenKonzernVerantwortlicher['password1']);
        unset($__datenKonzernVerantwortlicher['password2']);

        // Verschlüsselung Passwort
        $__datenKonzernVerantwortlicher['password'] = nook_ToolVerschluesselungPasswort::salzePasswort($__datenKonzernVerantwortlicher['password']);

        $kontrolle = $this->_db_groups->update('tbl_adressen', $__datenKonzernVerantwortlicher, "id = ".$__idKonzernVerantwortlicher);

        return $kontrolle;
    }

    public function setStartdaten($__start, $__limit){
        $this->_start = $__start;
        $this->_limit = $__limit;

        return $this;
    }

    public function getKonzernAdministratoren(){
        $this
            ->_findeAnzahlDerKonzernadministratoren()
            ->_findDatensaetzeKonzernAdministratoren();

        return $this->__konzernAdministratoren;
    }

    public function getAnzahlAdministratoren(){

        return $this->_anzahlKonzernAdministratoren;
    }

    public function getAnzahlHotels(){

        return $this->_anzahlHotels;
    }

    public function getHotels($__konzernVerantwortlicherId){
        $this
            ->_setKonzernverantwortlichen($__konzernVerantwortlicherId)
            ->_findAnzahlHotels()
            ->_findHotels()
            ->_findKonzernberechtigung();

        return $this->_hotels;
    }

    private function _setKonzernverantwortlichen($__konzernVerantwortlichen){
        $this->_zuBearbeitenderKonzernVerantwortlicher = $__konzernVerantwortlichen;

        return $this;
    }

    private function _findKonzernberechtigung(){
        $sql = "
            SELECT
                `properties_id` AS `property`
            FROM
                `tbl_kunde_properties`
            WHERE (`kunden_id` = ".$this->_zuBearbeitenderKonzernVerantwortlicher.")";

        $zugeordneteHotelsDesKonzernverantwortlichen = $this->_db_hotels->fetchCol($sql);

        for($i=0; $i < count($this->_hotels); $i++){
            if(in_array($this->_hotels[$i]['id'], $zugeordneteHotelsDesKonzernverantwortlichen))
                $this->_hotels[$i]['check'] = true;
            else
                $this->_hotels[$i]['check'] = false;
        }

        return $this;
    }

    private function _findAnzahlHotels(){

        $sql = "
            SELECT
                count(`id`) as anzahl
            FROM
                `tbl_properties`
            WHERE (`aktiv` = ".$this->_condition_hotel_ist_aktiv.")";

        $this->_anzahlHotels = $this->_db_hotels->fetchOne($sql);

        return $this;
    }

    private function _findHotels(){
        $sql = "
            SELECT
                `id`
                , `property_name` AS `hotel`
                , `city_id`
            FROM
                `tbl_properties`
            WHERE (`aktiv` = ".$this->_condition_hotel_ist_aktiv.") order by hotel asc limit ".$this->_start.",".$this->_limit;

        $this->_hotels = $this->_db_hotels->fetchAll($sql);

        return $this;
    }

    private function  _findeAnzahlDerKonzernadministratoren(){

         $sql = "
            SELECT
                count(`tbl_adressen`.`id`) as anzahl
            FROM
                `tbl_adressen`
            WHERE (`tbl_adressen`.`aktiv` = ".$this->_condition_kunde_ist_aktiv."
                AND `tbl_adressen`.`status` = ".$this->_condition_kunde_ist_konzernadministrator.")";

        $this->_anzahlKonzernAdministratoren = $this->_db_groups->fetchOne($sql);

        return $this;
    }

    private function _findDatensaetzeKonzernAdministratoren(){

         $sql = "
            SELECT
                `tbl_adressen`.`id`
                , `tbl_adressen`.`firstname`
                , `tbl_adressen`.`lastname`
            FROM
                `tbl_adressen`
            WHERE (`tbl_adressen`.`aktiv` = ".$this->_condition_kunde_ist_aktiv."
                AND `tbl_adressen`.`status` = ".$this->_condition_kunde_ist_konzernadministrator.")
            ORDER BY `tbl_adressen`.`lastname` ASC";

        $this->__konzernAdministratoren = $this->_db_groups->fetchAll($sql);

        return $this;
    }

    public function setDataGewaehlteHotels($__parameterGewaehlteHotels){
        $this
            ->_setKonzernverantwortlichen($__parameterGewaehlteHotels['konzernverantwortlicher'])
            ->_speichernGewaehlteHotels($__parameterGewaehlteHotels['hotels']);


    }

    public function setDatenZumLoeschenHotels($__loeschParameter){
        $this->_zuBearbeitenderKonzernVerantwortlicher = $__loeschParameter['konzernverantwortlicher'];

        return $this;
    }

    public function getKontrolleLoeschenZugeordneteHotels(){
        $loeschKontrolle = $this
            ->_loschenHotels();

        return $loeschKontrolle;
    }

    private function _loschenHotels(){
        $sql = "delete from tbl_kunde_properties where kunden_id = ".$this->_zuBearbeitenderKonzernVerantwortlicher;
        $kontrolle = $this->_db_hotels->query($sql);

        return $kontrolle;
    }

    private function _speichernGewaehlteHotels($__gewaehlteHotels){
        for($i = 0; $i < count($__gewaehlteHotels); $i++){
            $sql = "select count(id) as anzahl from tbl_kunde_properties where kunden_id = ".$this->_zuBearbeitenderKonzernVerantwortlicher." and properties_id = ".$__gewaehlteHotels[$i];
            $anzahl = $this->_db_hotels->fetchOne($sql);
            
            if($anzahl > 0)
                continue;

            $sql = "insert into tbl_kunde_properties set kunden_id = '".$this->_zuBearbeitenderKonzernVerantwortlicher."', properties_id = '".$__gewaehlteHotels[$i]."'";
            $this->_db_hotels->query($sql);
        }

        return $this;
    }
}
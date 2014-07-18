<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 07.05.12
 * Time: 15:35
 * To change this template use File | Settings | File Templates.
 */
 
class Front_Model_BuchungsuebersichtRatenverfuegbarkeit{

    private $_db_hotel;
    private $_db_groups;

    private $_buchungstabelleId = array();

    public $transferdata = array();

    private $_error_daten_unvollstaendig = 690;

    private $_condition_hotel_erlaubt_ueberbuchung = 2;

    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');
    }

    /**
     * Übernimmt die Transferdaten
     *
     * @param Front_Model_WarenkorbHotelbuchung $model
     * @return void
     */
    public function setTransferData(Front_Model_WarenkorbHotelbuchung $model){
        if(!property_exists($model,'transferdata'))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $this->_buchungstabelleId = $model->transferdata['idBuchungstabelle'];

        return $this;
    }

    /**
     * Übernahme der BuchungsID als Array
     *
     * @param array $__buchungsId
     * @return Front_Model_WarenkorbHotelbuchungRatenverfuegbarkeit
     */
    public function setArrayBuchungsId(array $__buchungsId){
        $this->_buchungstabelleId = $__buchungsId;

        return $this;
    }

    /**
     * Verringert oder erhöht die Verfügbarkeit der Raten in der Tabelle
     * 'ota_rates_availability'
     * 'true' = verringern der Raten
     * 'false' = Originalzustand
     *
     * @param bool $__veraenderung
     * @return Front_Model_WarenkorbHotelbuchungRatenverfuegbarkeit
     */
    public function setVeraenderungVerfuegbarkeitRaten($__veraenderungsTyp){

        if(!$this->_buchungstabelleId or !is_array($this->_buchungstabelleId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $buchungsdaten = $this->_ermittlungBuchungsdaten();

        // Ratenverfügbarkeit verändern
        $this->_neuberechnungAnzahlVerfuegbareRaten($__veraenderungsTyp, $buchungsdaten);

        return $this;
    }

    /**
     * Verändert die Anzahl der verfügbaren Raten in der
     * Tabelle 'ota_rates_availability'
     * Gesteuret wird die Veränderung mittels
     *
     * @return void
     */
    private function _neuberechnungAnzahlVerfuegbareRaten($__veraenderungsTyp, $__buchungsdaten){

        for($i=0; $i<count($__buchungsdaten); $i++){
            // Übernachtungen
            $uebernachtungen = array();

            // Start Datum
            $uebernachtungen[0] = $__buchungsdaten[$i]['startDate'];
            $startDatum = new DateTime($__buchungsdaten[$i]['startDate']);

            // weitere Tage des Zeitraumes entsprechend Anzahl Nächte
            for($j=1;$j < $__buchungsdaten[$i]['nights']; $j++){
                $periode = new DateInterval('P1D');
                $neuesDatum = $startDatum->add($periode);
                $uebernachtungen[$j] = $neuesDatum->format('Y-m-d');
            }

            foreach($uebernachtungen as $key => $datum){
                // verringern der Verfügbarkeit
                if($__veraenderungsTyp == 'true'){
                    $sql = "update tbl_ota_rates_availability set roomlimit = roomlimit - ".$__buchungsdaten[$i]['roomNumbers'];
                    $sql .= " where datum = '".$datum."'";
                    $sql .= " and hotel_code = '".$__buchungsdaten[$i]['property_code']."'";
                    $sql .= " and rate_code = '".$__buchungsdaten[$i]['rate_code']."'";
                }
                // erhöhen der Verfügbarkeit
                else{
                    $sql = "update tbl_ota_rates_availability set roomlimit = roomlimit + ".$__buchungsdaten[$i]['roomNumbers'];
                    $sql .= " where datum = '".$datum."'";
                    $sql .= " and hotel_code = '".$__buchungsdaten[$i]['property_code']."'";
                    $sql .= " and rate_code = '".$__buchungsdaten[$i]['rate_code']."'";
                }

                /** @var $db Zend_Db_Adapter_Mysqli */
                $db = $this->_db_hotel;
                $db->query($sql);
            }
        }

        return;
    }

    /**
     * Ermittelt die Buchungsdaten der
     * Hotelbuchungen.
     * Hotelbuchungen mit 'Überbuchung' werden
     * ignoriert.
     *
     * @return
     */
    private function _ermittlungBuchungsdaten(){
        /** @var $db_group Zend_Db_Adapter_Mysqli */
        $db_group = $this->_db_groups;
        /** @var $db_hotel Zend_Db_Adapter_Mysqli */
        $db_hotel = $this->_db_hotel;

        $buchungsdaten = array();

        for($i=0; $i < count($this->_buchungstabelleId); $i++){
            $sql = "select propertyId, startDate, otaRatesConfigId, roomNumbers, nights from tbl_hotelbuchung where id = ".$this->_buchungstabelleId[$i];
            $einzelbuchung = $db_group->fetchRow($sql);

            $sql = "
                SELECT
                    `tbl_properties`.`property_code`
                    , `tbl_properties`.`overbook`
                    , `tbl_ota_rates_config`.`rate_code`
                FROM
                    `tbl_properties`
                    INNER JOIN `tbl_ota_rates_config`
                        ON (`tbl_properties`.`id` = `tbl_ota_rates_config`.`properties_id`)
                WHERE (`tbl_properties`.`id` = ".$einzelbuchung['propertyId']."
                    AND `tbl_ota_rates_config`.`id` = ".$einzelbuchung['otaRatesConfigId'].")";

            $hotelDaten = $db_hotel->fetchRow($sql);

            if($hotelDaten['overbook'] != $this->_condition_hotel_erlaubt_ueberbuchung){
                $buchungsdaten[] = array_merge($einzelbuchung, $hotelDaten);
            }
            
        }

        return $buchungsdaten;
    }


}

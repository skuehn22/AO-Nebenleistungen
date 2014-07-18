<?php

/**
 * Simulieren der Demonstration
 * der Buchung von Übernachtungen
 * während einer Messe
 *
 * Wird gesteuert über /application/config/static.ini
 * [messe]
 *   dummy = 2
 *
 */

class nook_ToolDummyMesse{

    // Datenbanken / Tabellen / Views
    private $_tabelleProperties = null;
    private $_tabellePropertyDetails = null;

    // Errors
    private $_error_kein_int = 1300;
    private $_error_daten_unvollstaendig = 1301;
    private $_error_anzahl_datensetze_falsch = 1302;

    // Konditionen
    private $_condition_hotel_ist_aktiv = 3;
    private $_condition_hotel_ueberbuchung_moeglich = 2;
    private $_condition_ab_hierher = 20; // Grenze A und O
    private $_condition_bis_hierher = 580; // Grenze A und O
    private $_condition_property_id_dummy = 3;

    // Flags


    protected $_dummyModus = 1; // abgeschaltet
    protected $_stadtId = null;

    public function __construct(){

        $dummyModus = Zend_Registry::get('static')->messe->dummy;
        $this->_dummyModus = $dummyModus;

        /** @var _tabelleProperties Application_Model_DbTable_properties */
        $this->_tabelleProperties = new Application_Model_DbTable_properties(array('db' => 'hotels'));
        /** @var _tabellePropertyDetails Application_Model_DbTable_propertyDetails */
        $this->_tabellePropertyDetails = new Application_Model_DbTable_propertyDetails(array('db' => 'hotels'));
    }

    /**
     * Verändert die Hotel ID
     * zu Messe Zwecken
     *
     * @param array $__params
     * @return array
     */
    public function mapHotelreservationParamsPropertyId(array $__params){

        if($this->_dummyModus == 1)
                    return $__params;

        if( ($__params['propertyId'] > $this->_condition_ab_hierher) and ($__params['propertyId'] < $this->_condition_bis_hierher) )
            $__params['propertyId'] = $this->_condition_property_id_dummy;

        return $__params;
    }

    /**
     * Setzt die Hotel ID
     * für den Messe Modus
     *
     * @param $__hotelId
     * @return mixed
     */
    public function mapHotelId($__hotelId){
        if($this->_dummyModus == 1)
                    return $__hotelId;

        if( ($__hotelId > $this->_condition_ab_hierher) and ($__hotelId < $this->_condition_bis_hierher) )
            $__hotelId = $this->_condition_property_id_dummy;

        return $__hotelId;
    }

    /**
     * Gibt Hotelbeschreibung von
     * 'Dummy' - Hotel zurück.
     *
     * @param $__hotelbeschreibung
     * @param $__dummyPropertyId
     * @return mixed
     */
    public function veraendereHotelbeschreibung($__hotelbeschreibung, $__dummyPropertyId){

        if($this->_dummyModus == 1)
            return $__hotelbeschreibung;

        $__hotelbeschreibung = $this->_findeHotelbeschreibung($__hotelbeschreibung, $__dummyPropertyId);

        return $__hotelbeschreibung;
    }

    /**
     * Findet die Hotelbeschreibung
     * eines Dummy Hotel im
     * Messe - Modus
     *
     * + Hotelname
     * + Hotelbeschreibung
     *
     * @param $__hotelbeschreibung
     * @param $__dummyPropertyId
     * @return mixed
     */
    public function _findeHotelbeschreibung($__hotelbeschreibung, $__dummyPropertyId){

        $zifferAnzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();

        // Name des Hotels
        $cols = array(
            'property_name'
        );

        $wherePropertyid = "id = ".$__dummyPropertyId;

        $select = $this->_tabelleProperties->select();
        $select
            ->from($this->_tabelleProperties, $cols)
            ->where($wherePropertyid);

        $rows = $this->_tabelleProperties->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensetze_falsch);

        $__hotelbeschreibung['ueberschrift'] = $rows[0]['property_name'];

        // Beschreibung des Hotels
        $cols = array(
            'description_de',
            'description_en'
        );

        $wherePropertyIdbeschreibung = "properties_id = ".$__dummyPropertyId;

        $select = $this->_tabellePropertyDetails->select();
        $select->from($this->_tabellePropertyDetails, $cols)->where($wherePropertyIdbeschreibung);

        $rows = $this->_tabellePropertyDetails->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensetze_falsch);

        if($zifferAnzeigeSprache == 1)
            $__hotelbeschreibung['hotelbeschreibung'] = $rows[0]['description_de'];
        else
            $__hotelbeschreibung['hotelbeschreibung'] = $rows[0]['description_en'];

        return $__hotelbeschreibung;
    }

    /**
     * Schaltet den Messe Modus
     *
     * + 1 = nicht aktiv
     * + 2 aktiv
     *
     * @param $__dummyModus
     * @return nook_ToolDummyMesse
     * @throws nook_Exception
     */
    public function setDummyModus($__dummyModus){

        $dummyModus = (int) $__dummyModus;
        if(empty($dummyModus))
            throw new nook_Exception($this->_error_kein_int);

            $this->_dummyModus = $dummyModus;


        return $this;
    }

    /**
     * @param $__stadtId
     * @return nook_ToolDummyMesse
     * @throws nook_Exception
     */
    public function setStadtId($__stadtId){

        $stdtId = (int) $__stadtId;
        if(empty($stdtId))
            throw new nook_Exception($this->_error_kein_int);

        $this->_stadtId = $stdtId;

        return $this;
    }

    /**
     * Gibt die vorhandenen
     * Hotels einer Stadt zurück
     *
     * @param array $__vorhandeneHotels
     * @return array
     * @throws nook_Exception
     */
    public function getHotelsEinerStadt(array $__vorhandeneHotels){

        if($this->_dummyModus == 1)
            return $__vorhandeneHotels;

        if(empty($this->_stadtId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $alleHotelsDerStadt = $this->_ermittelnHotelsDerStadt();
        $komplettierteListeDerHotels = $this->_hinzufuegenHotels($alleHotelsDerStadt, $__vorhandeneHotels);

        return $komplettierteListeDerHotels;
    }

    /**
     * Ergänzt die Liste
     * der vorhandenen Hotels
     *
     * @param $__alleHotelsDerStadt
     * @param $__vorhandeneHotels
     * @return array
     */
    private function _hinzufuegenHotels($__alleHotelsDerStadt, $__vorhandeneHotels){

        $alleHotelsDerStadt = array();

        $k = 0;
        for($i=0; $i < count($__alleHotelsDerStadt); $i++){

            $flagHinzufuegen = true;
            for($j=0; $j < count($__vorhandeneHotels); $j++){
                if($__alleHotelsDerStadt[$i]['id'] == $__vorhandeneHotels[$j]['id']){
                    $flagHinzufuegen = $j;
                }
            }

            // Afbau Array
            if(!empty($flagHinzufuegen)){
                $alleHotelsDerStadt[$k]['id'] = $__alleHotelsDerStadt[$i]['id'];
                $alleHotelsDerStadt[$k]['overbook'] = $this->_condition_hotel_ueberbuchung_moeglich;
            }
            else{
                $alleHotelsDerStadt[$k]['id'] =  $__vorhandeneHotels[$flagHinzufuegen]['id'];
                $alleHotelsDerStadt[$k]['overbook'] =  $__vorhandeneHotels[$flagHinzufuegen]['overbook'];
            }

            $k++;
            $flagHinzufuegen = false;
        }

        return $alleHotelsDerStadt;
    }

    /**
     * Ermittelt alle Hotels
     * einer Stadt die aktiv sind.
     *
     * @param array $__vorhandeneHotels
     */
    private function _ermittelnHotelsDerStadt(){

        $cols = array(
            'id'
        );

        $whereStadt = "city_id = ".$this->_stadtId;
        $whereHotelAktiv = "aktiv = ".$this->_condition_hotel_ist_aktiv;

        $select = $this->_tabelleProperties->select();
        $select
            ->from($this->_tabelleProperties, $cols)
            ->where($whereStadt)
            ->where($whereHotelAktiv);

        $rows = $this->_tabelleProperties->fetchAll($select)->toArray();

        return $rows;
    }
}
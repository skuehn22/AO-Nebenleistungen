<?php
/**
 * Fehlerbereich: 820
 * Bearbeiten der Stornofristen eines Hotels
 *
 * @author Stephan Krauß
 */

class Admin_Model_HotelsStornofristen extends nook_Model_model{


	private $_error_hotelId_nicht_int = 820;
    private $_error_stornofrist_eintragen = 821;

    private $_tabelleStornofristen = null;
    private $_HotelId;

    public function __construct(){
        $this->_tabelleStornofristen = new Application_Model_DbTable_hotelStornofristen(array('db' => 'hotels'));
    }

    /********************* Kontrollen *********************/

    /**
     * Kontrolle der Programm ID
     *
     * @throws nook_Exception
     * @param $__hotelId
     * @return int
     */
    public  function kontrolleHotelId($__hotelId){
        $__hotelId = (int) $__hotelId;
        if(!is_int($__hotelId))
            throw new nook_Exception($this->_error_hotelId_nicht_int);

        $this->_HotelId = $__hotelId;

        return $__hotelId;
    }

    /**
     * Kontrolliert die Stornofristen
     *
     * @param $__params
     * @return array
     */
    public function kontrolleStornofristen($__params){
        $stornofristen = array();

        $i = 0;

        // 1. Stornofrist
        if(array_key_exists('tage1', $__params)){
            $__params['tage1'] = (int) $__params['tage1'];
            $__params['prozente1'] = (int) trim($__params['prozente1']);

            if(is_int($__params['tage1']) and is_int($__params['prozente1'])){
                $stornofristen[$i]['tage'] = $__params['tage1'];
                $stornofristen[$i]['prozente'] = $__params['prozente1'];

                $i++;
            }
        }

        // 2. Stornofrist
        if(array_key_exists('tage1', $__params)){
            $__params['tage2'] = (int) $__params['tage2'];
            $__params['prozente2'] = (int) trim($__params['prozente2']);

            if(is_int($__params['tage2']) and is_int($__params['prozente2'])){
                $stornofristen[$i]['tage'] = $__params['tage2'];
                $stornofristen[$i]['prozente'] = $__params['prozente2'];

                $i++;
            }
        }

        // 3. Stornofrist
        if(array_key_exists('tage3', $__params)){
            $__params['tage3'] = (int) $__params['tage3'];
            $__params['prozente3'] = (int) trim($__params['prozente3']);

            if(is_int($__params['tage3']) and is_int($__params['prozente3'])){
                $stornofristen[$i]['tage'] = $__params['tage3'];
                $stornofristen[$i]['prozente'] = $__params['prozente3'];

                $i++;
            }
        }

         // 4. Stornofrist
        if(array_key_exists('tage4', $__params)){
            $__params['tage4'] = (int) $__params['tage4'];
            $__params['prozente4'] = (int) trim($__params['prozente4']);

            if(is_int($__params['tage4']) and is_int($__params['prozente4'])){
                $stornofristen[$i]['tage'] = $__params['tage4'];
                $stornofristen[$i]['prozente'] = $__params['prozente4'];

                $i++;
            }
        }

        return $stornofristen;
    }

    /************ Formular **********************/

    /**
     * Holt alle Stornofristen eines Programmes
     *
     * @param $__hotelId
     * @return array
     */
    public function stornofristenEinesHotels($__hotelId){
        $select = $this->_tabelleStornofristen->select();
        $select->where('tbl_properties_id = '.$__hotelId)->order('id');

        $result = $this->_tabelleStornofristen->fetchAll($select);
        $zahlungsziele = $result->toArray();

        if(count($zahlungsziele) > 0){
            $formularZahlungsziele = $this->_umwandlungStornofristenInFormular($zahlungsziele);
            return $formularZahlungsziele;
        }

        return;
    }

    /**
     * Wandelt die vorhandenen Stornofristen eines Programmes
     * in die Formularansicht um.
     *
     * @param $__stornofristen
     * @return array
     */
    private function _umwandlungStornofristenInFormular($__stornofristen){
        $formularStornofristen = array();

        $j = 1;
        for($i=0; $i < count($__stornofristen); $i++){
            $formularStornofristen['tage'.$j] = $__stornofristen[$i]['tage'];
            $formularStornofristen['prozente'.$j] = $__stornofristen[$i]['prozente'];

            $j++;
        }

        return $formularStornofristen;
    }

    public function speichernStornofristen($__stornofristen){

        // löschen alte Stornofristen
        $kontrolle = $this->_tabelleStornofristen->delete('tbl_properties_id = '.$this->_HotelId);

        // eintragen neue Stornofristen

        for($i=0; $i < count($__stornofristen); $i++){
            $__stornofristen[$i]['tbl_properties_id'] = $this->_HotelId;
            $kontrolle = $this->_tabelleStornofristen->insert($__stornofristen[$i]);
            if($kontrolle == 0)
                throw new nook_Exception($this->_error_zahlungsziel_eintragen);
        }

        return true;

    }

}
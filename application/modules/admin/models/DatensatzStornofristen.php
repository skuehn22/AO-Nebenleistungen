<?php
/**
 * Fehlerbereich: 780
 * Bearbeiten der Stornofristen eines Programmes
 *
 * @author Stephan Krauß
 */

class Admin_Model_DatensatzStornofristen extends nook_Model_model{

    // Fehler
	private $_error_programmId_nicht_int = 800;
    private $_error_zahlungsziel_eintragen = 801;
    private $_error_eintragen_fehlgeschlagen = 802;

    // Tabellen / Views
    private $_tabelleZahlungsziele = null;
    private $_programmId = null;

    // Konditionen
    private $_condition_anzahl_default_stornofristen = 2;

    // Flags
    // private $_flag;

    private $_stornofristen = array();

    protected $_defaultStornofristen = array(
        '4' => 0,
        '3' => 100
    );

    public function __construct(){
        /** @var _tabelleZahlungsziele Application_Model_DbTable_stornofristen */
        $this->_tabelleZahlungsziele = new Application_Model_DbTable_stornofristen(array('db' => 'front'));
    }

    /**
     * setzen Programm ID
     *
     * @param $__programmId
     * @return Admin_Model_DatensatzStornofristen
     */
    public function setProgrammId($__programmId){
        $this->_programmId = $__programmId;

        return $this;
    }

    /********************* Kontrollen *********************/

    /**
     * Kontrolle der Programm ID
     *
     * @param $__programmId
     * @return Admin_Model_DatensatzStornofristen
     * @throws nook_Exception
     */
    public  function kontrolleProgrammId($__programmId){
        $__programmId = (int) $__programmId;
        if(!is_int($__programmId))
            throw new nook_Exception($this->_error_programmId_nicht_int);

        $this->_programmId = $__programmId;

        return $this;
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

        // 5. Stornofrist
        if(array_key_exists('tage5', $__params)){
            $__params['tage5'] = (int) $__params['tage5'];
            $__params['prozente5'] = (int) trim($__params['prozente5']);

            if(is_int($__params['tage5']) and is_int($__params['prozente5'])){
                $stornofristen[$i]['tage'] = $__params['tage5'];
                $stornofristen[$i]['prozente'] = $__params['prozente5'];

                $i++;
            }
        }

        // 6. Stornofrist
        if(array_key_exists('tage6', $__params)){
            $__params['tage6'] = (int) $__params['tage6'];
            $__params['prozente6'] = (int) trim($__params['prozente6']);

            if(is_int($__params['tage6']) and is_int($__params['prozente6'])){
                $stornofristen[$i]['tage'] = $__params['tage6'];
                $stornofristen[$i]['prozente'] = $__params['prozente6'];

                $i++;
            }
        }

        // 7. Stornofrist
        if(array_key_exists('tage7', $__params)){
            $__params['tage7'] = (int) $__params['tage7'];
            $__params['prozente7'] = (int) trim($__params['prozente7']);

            if(is_int($__params['tage7']) and is_int($__params['prozente7'])){
                $stornofristen[$i]['tage'] = $__params['tage7'];
                $stornofristen[$i]['prozente'] = $__params['prozente7'];

                $i++;
            }
        }

        $this->_stornofristen = $stornofristen;

        return $this;
    }

    /************ Formular **********************/

    /**
     * Holt alle Stornofristen eines Programmes
     *
     * @param $__faId
     * @return array
     */
    public function stornofristenEinesProgrammes(){

        $select = $this->_tabelleZahlungsziele->select();
        $select->where('programmdetails_id = '.$this->_programmId)->order('id');

        $stornofristen = $this->_tabelleZahlungsziele->fetchAll($select)->toArray();

        // Korrektur wenn keine Stornofristen eingetragen
        if( count($stornofristen) < $this->_condition_anzahl_default_stornofristen )
            $this->_korrekturStornofristen();

        $formularZahlungsziele = $this->_umwandlungStornofristenInFormularWerte($stornofristen);
        return $formularZahlungsziele;

        return false;
    }

    /**
     * Trägt die Standard Stornofristen der
     * Programme ein.
     *
     */
    private function _korrekturStornofristen(){

        foreach($this->_defaultStornofristen as $tage => $stornoProzente){
            $insert = array(
                'programmdetails_id' => $this->_programmId,
                'tage' => $tage,
                'prozente' => $stornoProzente
            );

            $this->_tabelleZahlungsziele->insert($insert);
        }


        $this->stornofristenEinesProgrammes();
    }

    /**
     * Wandelt die vorhandenen Stornofristen eines Programmes
     * in die Formularansicht um.
     *
     * @param $__stornofristen
     * @return array
     */
    private function _umwandlungStornofristenInFormularWerte($__stornofristen){
        $formularStornofristen = array();

        $j = 1;
        for($i=0; $i < count($__stornofristen); $i++){
            $formularStornofristen['tage'.$j] = $__stornofristen[$i]['tage'];
            $formularStornofristen['prozente'.$j] = $__stornofristen[$i]['prozente'];

            $j++;
        }

        return $formularStornofristen;
    }

    public function speichernStornofristen(){

        // löschen alte Stornofristen
        $kontrolle = $this->_tabelleZahlungsziele->delete('programmdetails_id = '.$this->_programmId);

        // eintragen neue Stornofristen

        for($i=0; $i < count($this->_stornofristen); $i++){

            // wenn keine Tage angegeben
            if($this->_stornofristen[$i]['tage'] == 0)
                continue;

            $this->_stornofristen[$i]['programmdetails_id'] = $this->_programmId;
            $kontrolle = $this->_tabelleZahlungsziele->insert($this->_stornofristen[$i]);
            if($kontrolle == 0)
                throw new nook_Exception($this->_error_zahlungsziel_eintragen);
        }

        return;
    }

}
<?php
/**
 * Fehlerbereich: 780
 * Bearbeiten der Preisvarianten eines Programmes
 *
 * @author Stephan Krauß
 */

class Admin_Model_DatensatzZahlungsziele extends nook_Model_model{


	private $_error_programmId_nicht_int = 790;
    private $_error_zahlungsziel_eintragen = 792;

    private $_tabelleZahlungsziele = null;
    private $_programmId;

    public function __construct(){
        $this->_tabelleZahlungsziele = new Application_Model_DbTable_zahlungsziele(array('db' => 'front'));
    }

    /********************* Kontrollen *********************/

    /**
     * Kontrolle der Programm ID
     *
     * @throws nook_Exception
     * @param $__programmId
     * @return int
     */
    public  function kontrolleProgrammId($__programmId){
        $__programmId = (int) $__programmId;
        if(!is_int($__programmId))
            throw new nook_Exception($this->_error_programmId_nicht_int);

        $this->_programmId = $__programmId;

        return $__programmId;
    }

    /**
     * Kontrolle der Zahlungsziele
     *
     * @param $__params
     * @return array
     */
    public function kontrolleZahlungsziele($__params){
        $zahlungsziele = array();

        $i = 0;

        // 1. Zahlungsziel
        if(array_key_exists('tage1', $__params)){
            $__params['tage1'] = (int) $__params['tage1'];
            $__params['prozente1'] = (int) trim($__params['prozente1']);

            if(is_int($__params['tage1']) and is_int($__params['prozente1'])){
                $zahlungsziele[$i]['tage'] = $__params['tage1'];
                $zahlungsziele[$i]['prozente'] = $__params['prozente1'];

                $i++;
            }
        }

        // 2. Zahlungsziel
        if(array_key_exists('tage1', $__params)){
            $__params['tage2'] = (int) $__params['tage2'];
            $__params['prozente2'] = (int) trim($__params['prozente2']);

            if(is_int($__params['tage2']) and is_int($__params['prozente2'])){
                $zahlungsziele[$i]['tage'] = $__params['tage2'];
                $zahlungsziele[$i]['prozente'] = $__params['prozente2'];

                $i++;
            }
        }

        // 2. Zahlungsziel
        if(array_key_exists('tage3', $__params)){
            $__params['tage3'] = (int) $__params['tage3'];
            $__params['prozente3'] = (int) trim($__params['prozente3']);

            if(is_int($__params['tage3']) and is_int($__params['prozente3'])){
                $zahlungsziele[$i]['tage'] = $__params['tage3'];
                $zahlungsziele[$i]['prozente'] = $__params['prozente3'];

                $i++;
            }
        }

        return $zahlungsziele;
    }

    /************ Formular **********************/

    /**
     * Holt alle Zahlungsziele eines Programmes
     *
     * @param $__faId
     * @return array
     */
    public function zahlungszieleEinesProgrammes($__faId){
        $select = $this->_tabelleZahlungsziele->select();
        $select->where('tbl_bjl_FaId = '.$__faId)->order('id');

        $result = $this->_tabelleZahlungsziele->fetchAll($select);
        $zahlungsziele = $result->toArray();

        if(count($zahlungsziele) > 0){
            $formularZahlungsziele = $this->_umwandlungZahlungszieleInFormular($zahlungsziele);
            return $formularZahlungsziele;
        }

        return;
    }

    /**
     * Wandelt die vorhandenen Zahlungsziele eines Programmes
     * in die Formularansicht um.
     *
     * @param $__zahlungsziele
     * @return array
     */
    private function _umwandlungZahlungszieleInFormular($__zahlungsziele){
        $formularZahlungsziele = array();

        $j = 1;
        for($i=0; $i < count($__zahlungsziele); $i++){
            $formularZahlungsziele['tage'.$j] = $__zahlungsziele[$i]['tage'];
            $formularZahlungsziele['prozente'.$j] = $__zahlungsziele[$i]['prozente'];

            $j++;
        }

        return $formularZahlungsziele;
    }

    /**
     * speichern der Zahlungsziele eines programmes
     *
     * @throws nook_Exception
     * @param $__zahlungsziele
     * @return bool
     */
    public function speichernZahlungsziele($__zahlungsziele){

        // löschen alte Zahlungsziele
        $kontrolle = $this->_tabelleZahlungsziele->delete('tbl_bjl_FaId = '.$this->_programmId);

        // eintragen neue Zahlungsziele

        for($i=0; $i < count($__zahlungsziele); $i++){
            $__zahlungsziele[$i]['tbl_bjl_FaId'] = $this->_programmId;
            $kontrolle = $this->_tabelleZahlungsziele->insert($__zahlungsziele[$i]);
            if($kontrolle == 0)
                throw new nook_Exception($this->_error_zahlungsziel_eintragen);
        }

        return true;

    }

}
<?php
/**
 * Fehlerbereich: 780
 * Bearbeiten der Preisvarianten eines Programmes
 *
 * @author Stephan Krauß
 */

class Admin_Model_DatensatzPreisvarianten extends nook_Model_model{


	private $_error_programm_id_nicht_int = 780;
    private $_error_daten_preisvariante_nicht_korrekt = 781;
    private $_error_id_preisvariante_nicht_int = 782;
    private $_error_keine_daten = 783;

    private $_condition_sprache_deutsch = 1;
    private $_condition_sprache_englisch = 2;

    private $_programmId = null;

    private $_tabellePreisvarianten = null;
    private $_tabellePreiseBeschreibung = null;
    private $_viewPreisvarianten = null;
    private $_viewAlleSprachvarianten = null;

    public function __construct(){

        /** @var _tabellePreisvarianten Application_Model_DbTable_preise */
        $this->_tabellePreisvarianten = new Application_Model_DbTable_preise(array('db' => 'front'));
        /** @var _tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
        $this->_tabellePreiseBeschreibung = new Application_Model_DbTable_preiseBeschreibung();
        /** @var _viewPreisvarianten Application_Model_DbTable_viewPreisvarianten */
        $this->_viewPreisvarianten = new Application_Model_DbTable_viewPreisvarianten();
        /** @var _viewAlleSprachvarianten Application_Model_DbTable_viewSprachvariantenAlleSprachen */
        $this->_viewAlleSprachvarianten = new Application_Model_DbTable_viewSprachvariantenAlleSprachen();
    }

    /********************* Kontrollen *********************/

    /**
     * Kontrolle ID auf Int
     *
     * @throws nook_Exception
     * @param $__programmId
     * @return
     */
    public function checkProgrammId($__programmId){

        $programmId = (int) $__programmId;

        if(!is_int($programmId))
            throw new nook_Exception($this->_error_programm_id_nicht_int);

        $this->_programmId = $programmId;

        return;
    }

    /**
     * Kontrolle der ID der Preisvariante
     *
     * @throws nook_Exception
     * @param $__idPreisvariante
     * @return
     */
    public function checkDatenPreisvarianteId($__idPreisvariante){

        $__idPreisvariante = (int) $__idPreisvariante;
        if(!is_int($__idPreisvariante))
            throw new nook_Exception($this->_error_id_preisvariante_nicht_int);

        return;
    }

    /************ Tabelle **********************/

    /**
     * 
     *
     * @param $__start
     * @param $__limit
     * @return array
     */
    public function vorhandenePreisvariantenEinesProgrammes($__start, $__limit){
        $start = (int) $__start;
        $limit = (int) $__limit;

        $select = $this->_tabellePreisvarianten->select();
        $select->where('programmdetails_id = '.$this->_programmId)->limit($__limit, $__start);
        $preisvariantenEinesProgrammes = $this->_tabellePreisvarianten->fetchAll($select)->toArray();

        for($i = 0; $i < count($preisvariantenEinesProgrammes); $i++){

            $cols = array(
                'preisvariante',
                'sprachen_id'
            );

            $selectBeschreibung = $this->_tabellePreiseBeschreibung->select();
            $selectBeschreibung->from($this->_tabellePreiseBeschreibung, $cols);
            $selectBeschreibung->where("preise_id = ".$preisvariantenEinesProgrammes[$i]['id']);

            $preisvarianteBeschreibungen = $this->_tabellePreiseBeschreibung->fetchAll($selectBeschreibung)->toArray();

            for($j=0; $j < 2; $j++){
                if($preisvarianteBeschreibungen[$j]['sprachen_id'] == 1){
                    if(empty($preisvarianteBeschreibungen[$j]['preisvariante']))
                        $preisvarianteBeschreibungen[$j]['preisvariante'] = ' ';

                    $preisvariantenEinesProgrammes[$i]['preisvariante_de'] = $preisvarianteBeschreibungen[$j]['preisvariante'];
                }


                if($preisvarianteBeschreibungen[$j]['sprachen_id'] == 2){
                    if(empty($preisvarianteBeschreibungen[$j]['preisvariante']))
                        $preisvarianteBeschreibungen[$j]['preisvariante'] = ' ';

                    $preisvariantenEinesProgrammes[$i]['preisvariante_en'] = $preisvarianteBeschreibungen[$j]['preisvariante'];
                }

            }


        }

        $preisvarianten['anzahl'] = $this->_countPreisvariantenEinesProgrammes();
        $preisvarianten['data'] = $preisvariantenEinesProgrammes;

        return $preisvarianten;
    }

    /**
     * Ermittelt die Anzahl der Preisvarianten eines Programmes
     *
     * @return mixed
     */
    private function _countPreisvariantenEinesProgrammes(){
        $cols = array(
            "anzahl" => new Zend_Db_Expr('count(id)')
        );

        $select = $this->_tabellePreisvarianten->select();
        $select->from($this->_tabellePreisvarianten,$cols)->where('programmdetails_id = '.$this->_programmId);

        $ergebnis = $this->_tabellePreisvarianten->fetchRow($select);

        if($ergebnis != null)
            $anzahl = $ergebnis->toArray();
        else
            throw new nook_Exception($this->_error_keine_daten);


        return $anzahl['anzahl'];
    }

    public function loeschePreisvariante($__idPreisvariante){

        $this->_tabellePreisvarianten->delete('id = '.$__idPreisvariante);
        $this->_tabellePreiseBeschreibung->delete("preise_id = ".$__idPreisvariante);

        return true;
    }

    /*************** Formular *****************/

    /**
     * Kontrolliert die ankommenden Daten der Preisvariante
     *
     * + wenn keine ID durchläufer Preisposten vorhanen dann ist der durchlaeufer = 1
     *
     * @throws nook_Exception
     * @param $__params
     * @return array
     */
    public function checkDatenPreisvariante($__params){
        $kontrolle = 0;

        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        if(empty($__params['id']))
            unset($__params['id']);

        $__params['bjl_Fa_ID'] = $__params['FaId'];
        unset($__params['FaId']);

        if(array_key_exists('id', $__params)){
            $__params['id'] = (int) $__params['id'];
            if(!is_int($__params['id']))
                $kontrolle++;
        }

        $__params['bjl_Fa_ID'] = (int) $__params['bjl_Fa_ID'];
        if(!is_int($__params['bjl_Fa_ID']))
            $kontrolle++;

        $__params['einkaufspreis'] = str_replace(',','.',$__params['einkaufspreis']);
        $__params['einkaufspreis'] = (float) trim($__params['einkaufspreis']);
        if(!is_float($__params['einkaufspreis']))
            $kontrolle++;

        $__params['verkaufspreis'] = str_replace(',','.',$__params['verkaufspreis']);
        $__params['verkaufspreis'] = (float) trim($__params['verkaufspreis']);
        if(!is_float($__params['verkaufspreis']))
            $kontrolle++;

        $__params['preisvariante_de'] = trim($__params['preisvariante_de']);
        if(strlen($__params['preisvariante_de']) < 5)
            $kontrolle++;

        if($kontrolle > 0)
            throw new nook_Exception($this->_error_daten_preisvariante_nicht_korrekt);

        return $__params;
    }

    /**
     * Überarbeitet eine vorhandene Preisvariante
     * 
     * @param $__params
     * @return
     */
    public function bearbeitenVorhandenePreisvariante($__params){

        // Update Preise
        $whereUpdatePreise = "id = ".$__params['id'];
        $updatePreise = array(
            'einkaufspreis' => $__params['einkaufspreis'],
            'verkaufspreis' => $__params['verkaufspreis']
        );

        $this->_tabellePreisvarianten->update($updatePreise, $whereUpdatePreise);

        // Preisbezeichnung deutsch
        $whereUpdateDe = array(
            "preise_id = ".$__params['id'],
            "sprachen_id = ".$this->_condition_sprache_deutsch
        );

        $updatePreiseBeschreibungDe = array(
            'preisvariante' => $__params['preisvariante_de']
        );

        $this->_tabellePreiseBeschreibung->update($updatePreiseBeschreibungDe,$whereUpdateDe);

        // Preisbezeichnung englisch
        $whereUpdateEn = array(
            "preise_id = ".$__params['id'],
            "sprachen_id = ".$this->_condition_sprache_englisch
        );

        $updatePreiseBeschreibungEn = array(
            'preisvariante' => $__params['preisvariante_en']
        );

       $this->_tabellePreiseBeschreibung->update($updatePreiseBeschreibungEn, $whereUpdateEn);

        return true;
    }

    /**
     * Speichern einer neuen Preisvariante
     * + Preise
     * + deutsche Beschreibung
     * + englische Beschreibung
     *
     * @param $__params
     * @return bool
     */
    public function speichernNeuePreisvariante($__params){

        // eintragen Preise
        $insertPreise = array(
            "programmdetails_id" => $__params['bjl_Fa_ID'],
            "einkaufspreis" => $__params['einkaufspreis'],
            "verkaufspreis" => $__params['verkaufspreis']
        );

        $preise_id = $this->_tabellePreisvarianten->insert($insertPreise);

        // deutsche Beschreibung Preisvariante
        $insertPreiseBeschreibungDe = array(
            "sprachen_id" => $this->_condition_sprache_deutsch,
            "preisvariante" => $__params['preisvariante_de'],
            "preise_id" => $preise_id
        );

        $kontrolle = $this->_tabellePreiseBeschreibung->insert($insertPreiseBeschreibungDe);

        // englische Beschreibung Preisvariante
        $insertPreiseBeschreibungEn = array(
            "sprachen_id" => $this->_condition_sprache_englisch,
            "preisvariante" => $__params['preisvariante_en'],
            "preise_id" => $preise_id
        );

        $kontrolle = $this->_tabellePreiseBeschreibung->insert($insertPreiseBeschreibungEn);

         if($kontrolle > 0)
            return true;
        else
            return false;
    }
}
<?php
/**
 * Fehlerbereich: 780
 * Bearbeiten der Stornofristen eines Programmes
 *
 * @author Stephan Krauß
 */

class Admin_Model_DatensatzWerbepreis extends nook_Model_model{


	private $_error_programmId_nicht_int = 840;
    private $_error_werbepreis_nicht_float = 841;
    private $_error_variablentyp_falsch = 842;

    private $_tabelleProgrammeDetails = null;

    public function __construct(){

        /** @var $_tabelleProgramme Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammeDetails = new Application_Model_DbTable_programmedetails();
    }

    /********************* Kontrollen *********************/

    /**
     * Kontrolliert die ankommenden Parameter
     * der Besonderheiten eines Programmes
     *
     * @param array $params
     * @return array
     * @throws nook_Exception
     */
    public function checkProgrammWerbepreis(array $params){

        if(!filter_var($params['programmId'], FILTER_VALIDATE_INT))
            throw new nook_Exception($this->_error_programmId_nicht_int);

        $params['mwst'] = (float) $params['mwst'];
        if(!is_float($params['mwst']))
        	throw new nook_Exception($this->_error_variablentyp_falsch);

        $params['mwst_ek'] = (float) $params['mwst_ek'];
        if(!is_float($params['mwst_ek']))
            throw new nook_Exception($this->_error_variablentyp_falsch);

        if(isset($params['durchlaeuferId'])){
        	$params['rechnung_durchlaeufer_id'] = (int) $params['durchlaeuferId'];
        	if(!is_int($params['rechnung_durchlaeufer_id']))
                throw new nook_Exception($this->_error_variablentyp_falsch);
        }
        else{
        	$params['rechnung_durchlaeufer_id'] = 1;
        }

        unset($params['durchlaeuferId']);

        return $params;
    }


    /**
     * Holt die Besonderheiten eines Programmes
     *
     * + Werbepreis
     * + Buchungspauschale
     *
     * @param $__idProgramm
     * @return array
     */
    public function getBesonderheiten($__idProgramm)
    {
        $cols = array(
            'werbepreis',
            'buchungspauschale',
            'mwst',
            'mwst_ek',
            'rechnung_durchlaeufer_id'
        );

        $select = $this->_tabelleProgrammeDetails->select();
        $select->from($this->_tabelleProgrammeDetails, $cols);
        $select->where('id = '.$__idProgramm);

        $rows = $this->_tabelleProgrammeDetails->fetchAll($select)->toArray();



        if($rows[0]['werbepreis'] == null)
            $rows[0]['werbepreis'] = '';

        $data = array();
        $data['werbepreis'] = str_replace('.',',',$rows[0]['werbepreis']);

        if($rows[0]['buchungspauschale'] == 2)
            $data['buchungspauschale'] = true;

        $data['mwst'] = $rows[0]['mwst'];
        $data['mwst_ek'] = $rows[0]['mwst_ek'];
        $data['durchlaeuferId'] = $rows[0]['rechnung_durchlaeufer_id'];

        return $data;
    }

    /**
     * Updatet der Besonderheiten eines Programmes
     *
     * + Werbepreis
     * + Buchungspauschale 1 = keine Buchungspauschale
     *
     * @param $__idProgramm
     * @return
     */
    public function setBesonderheitenPreisvariante($params){

        $werbepreis = str_replace(',','.',$params['werbepreis']);
        $werbepreis = (float) $werbepreis;
        $werbepreis = number_format($werbepreis,2);

        $update = array(
            'werbepreis' => $werbepreis,
            'mwst' => $params['mwst'],
            'mwst_ek' => $params['mwst_ek'],
            'rechnung_durchlaeufer_id' => $params['rechnung_durchlaeufer_id']
        );

        if(isset($params['buchungspauschale'])){
            $update['buchungspauschale'] = 2;
        }
        else{
            $update['buchungspauschale'] = 1;
        }

        $this->_tabelleProgrammeDetails->update($update,"id = ".$params['programmId']);

        return;
       }


}
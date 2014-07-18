<?php
class Admin_Model_ProgrammvariantenForm extends nook_Model_model{

	private $_formData = array();

    private $_error_keine_korrekten_Eingabewerte = 500;
    private $_error_eintragen_preisvariante_ist_fehlgeschlagen = 501;
    private $_error_keine_daten_vorhanden = 502;

    // Datenbank
	private $_db_groups;
    private $_db_hotels;

    private $_idProgrammvariante;
    
    public function __construct(){
		$this->_db_groups = Zend_Registry::get('front');
		$this->_db_hotels = Zend_Registry::get('hotels');

		return;
	}

    public function checkData($__params){
        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        $this->_checkData($__params);

        $__params['bezeichnung'] = trim($__params['bezeichnung']);
        $this->_formData = $__params;

        return $this;
    }

    private function  _checkData($__params){
        $kontrollArray = array(
            'preistyp' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'ansatz' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'anzahl' => array(
                'filter' => FILTER_VALIDATE_INT
            )
        );

        $kontrolle = filter_var_array($__params, $kontrollArray);
        if(!is_array($kontrollArray) or count($kontrollArray) != 3)
            throw new nook_Exception($this->_error_keine_korrekten_Eingabewerte);

        return $this;
    }

    public function setId($__id){
        $this->_idProgrammvariante = $__id;

        return $this;
    }

    public function getData(){
        $sql = "select * from tbl_programmvarianten where id = ".$this->_idProgrammvariante;
        $datenPreisvariante = $this->_db_groups->fetchRow($sql);
        if(!is_array($datenPreisvariante))
            throw new nook_Exception($this->_error_keine_daten_vorhanden);

        return $datenPreisvariante;
    }

    public function savePreisvariante(){
        $this->_findeVariantengruppe();


        $kontrolle = $this->_db_groups->insert('tbl_programmvarianten', $this->_formData);
        if(!$kontrolle)
            throw new nook_Exception($this->_error_eintragen_preisvariante_ist_fehlgeschlagen);

        return $this;
    }

    private function _findeVariantengruppe(){
        $sql = "select count(id) from tbl_programmvarianten where bezeichnung = '".$this->_formData['bezeichnung']."'";
        $anzahl = $this->_db_groups->fetchOne($sql);

        if($anzahl == 0){
            $sql = "select max(variantengruppe) from tbl_programmvarianten";
            $variantengruppe = $this->_db_groups->fetchOne($sql);

            if(is_null($variantengruppe))
                $variantengruppe = 0;

            $variantengruppe++;
        }
        else{
            $sql = "select variantengruppe from tbl_programmvarianten where bezeichnung = '".$this->_formData['bezeichnung']."'";
            $variantengruppe = $this->_db_groups->fetchOne($sql);
        }

        $this->_formData['variantengruppe'] = $variantengruppe;

        return $this;
    }

    public function updatePreisvariante(){
        $kontrolle = $this->_db_groups->update('tbl_programmvarianten', $this->_formData, "id = ".$this->_idProgrammvariante);

        return $this;
    }





}
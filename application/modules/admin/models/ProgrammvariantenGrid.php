<?php
class Admin_Model_ProgrammvariantenGrid extends nook_Model_model{

	private $_formData = array();

	private $_error_keine_korrekten_Eingabewerte = 510;

    private $_start;
    private $_limit;

    private $_idPreisvariante;
    private $_idProgramm;

     public function __construct(){
		$this->_db_groups = Zend_Registry::get('front');
		$this->_db_hotels = Zend_Registry::get('hotels');

		return;
	}

    public function setStartLimit($__start, $__limit){
        $this->_start = $__start;
        $this->_limit = $__limit;

        return $this;
    }

    public function getPreisvarianten(){
        $sql = "select * from tbl_programmvarianten order by variantengruppe, id limit ".$this->_start.",".$this->_limit;

        $preisvarianten = $this->_db_groups->fetchAll($sql);

        if(!empty($this->_idProgramm)){
            $zuschlaege = $this->_getZuschlaege();
            $preisvarianten = $this->_ergaenzeZuschlaege($preisvarianten, $zuschlaege);
        }

        return $preisvarianten;
    }

    private function _ergaenzeZuschlaege($__preisvarianten, $__zuschlaege){
        for($i=0; $i<count($__preisvarianten); $i++){
            for($j=0; $j<count($__zuschlaege); $j++){
                if($__preisvarianten[$i]['id'] == $__zuschlaege[$j]['programmVarianteId'])
                    $__preisvarianten[$i]['zuschlag'] = $__zuschlaege[$j]['value'];
            }

            if(!array_key_exists('zuschlag', $__preisvarianten[$i]))
                $__preisvarianten[$i]['zuschlag'] = '0';
        }

        return $__preisvarianten;
    }

    public function deleteZuschlaege(){
        $sql = "delete from tbl_details_programmvarianten where Fa_Id = ".$this->_idProgramm;
        $this->_db_groups->query($sql);

        return $this;
    }

    private function _getZuschlaege(){
        $sql = "
            SELECT
                `tbl_details_programmvarianten`.`value`
                , `tbl_details_programmvarianten`.`programmvariante_id` as programmVarianteId
            FROM
                `tbl_programmvarianten`
                INNER JOIN `tbl_details_programmvarianten`
                    ON (`tbl_programmvarianten`.`id` = `tbl_details_programmvarianten`.`programmvariante_id`)
            WHERE (`tbl_details_programmvarianten`.`Fa_Id` = ".$this->_idProgramm.")";

        $zuschlaege = $this->_db_groups->fetchAll($sql);

        return $zuschlaege;
    }

    public function setZuschlaege($__preisVarianten){

        for($i=0; $i<count($__preisVarianten); $i++){
            $teile = explode(':', $__preisVarianten[$i]);
            $sql = "delete from tbl_details_programmvarianten where Fa_Id = ".$this->_idProgramm." and programmvariante_id = ".$teile[0];
            $this->_db_groups->query($sql);

            $insert = array();
            $insert['Fa_Id'] = $this->_idProgramm;
            $insert['programmvariante_id'] = $teile[0];
            $insert['value'] = $teile[1];

            $this->_db_groups->insert('tbl_details_programmvarianten', $insert);
        }

        return $this;
    }

    public function getAnzahlDatensaetze(){
        $sql = "select count(id) as anzahl from tbl_programmvarianten";
        $anzahl = $this->_db_groups->fetchOne($sql);

        return $anzahl;
    }

    public function setId($__idPreisVariante){
        $this->_idPreisvariante = $__idPreisVariante;

        return $this;
    }

    public function setProgrammId($__programmId){
        $this->_idProgramm = $__programmId;

        return $this;
    }

    public function deletePreisvariante(){
        $this->_db_groups->delete('tbl_programmvarianten', 'id = '.$this->_idPreisvariante);

        return $this;
    }

}
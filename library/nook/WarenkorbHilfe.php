<?php
class nook_WarenkorbHilfe{

    public $_db_groups;
    public $_eingabedaten = array();

    private $_error_programm_konnte_nicht_geloescht_werden = 670;
    private $_error_status_nicht_geupdatet = 671;

    private $_condition_item_ist_geordert_aber_nicht_bestaetigt = 3;
    private $_condition_stornofrist_abgelaufen = 6;
    private $_condition_bereiche = array(
        '1' => 'tbl_programmbuchung',
        '6' => 'tbl_hotelbuchung',
        '7' => 'tbl_produktbuchung'
    );

    public function __construct(){
         $this->_db_groups = Zend_Registry::get('front');

         return;
    }

    public function setAktiveStep($__step){
		$int = new Zend_Validate_Int();
		if(!$int->isValid($__step))
			throw new nook_Exception($this->error_no_integer_step);

		$aktiveStep = array();
		for($i=1; $i< 5; $i++){
			$aktiveStep['aktiveStep'.$i] = '';

			if($i == $__step)
				$aktiveStep['aktiveStep'.$i] = 'aktiveStep';
		}

		return $aktiveStep;
	}

    public function test(){
        echo 'Test';

        return;
    }

    /**
     * Kontrolliert ob dieses Programm gelöscht werden darf.
     * Kontrolliert ob die User ID oder die Session ID das Programm
     * löschen darf.
     *
     * @throws nook_Exception
     * @param $__bereichId
     * @param $__idBuchungstabelle
     *
     * @internal param $__buchungsId
     * @return
     */
    public function kontrolleLoeschenBuchung($__bereichId, $__idBuchungstabelle){
        $auth = new Zend_Session_Namespace('Auth');
        $authItems = $auth->getIterator();

        $sql = "
            SELECT
                `tbl_buchungsnummer`.`kunden_id` as kundenId
              , `tbl_programmbuchung`.`status`
              , `tbl_buchungsnummer`.`session_id` as sessionId
            FROM
                `tbl_buchungsnummer`
                INNER JOIN `".$this->_condition_bereiche[$__bereichId]."`
                    ON (`tbl_buchungsnummer`.`id` = `".$this->_condition_bereiche[$__bereichId]."`.`buchungsnummer_id`)
            WHERE (`".$this->_condition_bereiche[$__bereichId]."`.`id` = ". $__idBuchungstabelle .")";

        $kontrolleLoeschen = $this->_db_groups->fetchRow($sql);

        if(!empty($authItems['userId'])){
            if($authItems['userId'] != $kontrolleLoeschen['kundenId'])
                throw new nook_Exception($this->_error_keine_berechtigung_zum_loeschen);
        }
        else{
            $session = Zend_Session::getId();
            if($session != $kontrolleLoeschen['sessionId'])
                throw new nook_Exception($this->_error_keine_berechtigung_zum_loeschen);
        }

        return $kontrolleLoeschen['status'];
    }

}
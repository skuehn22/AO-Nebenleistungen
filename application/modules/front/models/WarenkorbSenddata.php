<?php
/**
 * 09.07.12 14:13
 * Allgemeine Beschreibung der Klasse
 *
 * <code>
 *   Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 */

class Front_Model_WarenkorbSenddata{

    private $_db_front = null;
    private $_data = array();

    private $_buchungsnummer = null;
    private $_user = null;
    private $_superUser = null;

    private $_error_daten_nicht_korrekt = 1110;
    private $_error_mehr_als_eine_adminkennung = 1111;

    private $_condition_status_administrator = 10;
    private $_condition_status_angefragt = 2;

    public function __construct(){
        $this->_db_front = Zend_Registry::get('front');
    }

    /**
     * Übernahme der Daten
     *
     * @param $__data
     */
    public function setData($__data){
       $this->_data = $__data;

       $this->_checkData();
       $this->_findBuchungsnummer();

        // Kontrolliert auf Superuser und ordnet Buchungen einem Superuser zu
        if(array_key_exists('superuser', $this->_data) and !empty($this->_data['superuser']))
            $this->_kontrolleSuperuser();

        // verändern Status und Superuser zuordnen
        $this->_setStatus();


       return $this->_user;
    }

    /**
     * Findet die Buchungsnummer
     * und KundenID
     */
    private function _findBuchungsnummer(){
        $session = Zend_Session::getId();

        $sql = "select kunden_id, id from tbl_buchungsnummer where session_id = '".$session."'";
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $row = $db->fetchRow($sql);
        $this->_buchungsnummer = $row['id'];
        $this->_user = $row['kunden_id'];

        return;
    }

    /**
     * Kontrolliert die AGB und verzweigt bei Bedarf auf die Superuser
     *
     * @return Front_Model_WarenkorbSenddata
     * @throws nook_Exception
     */
    public function _checkData(){

        if(!array_key_exists('agb', $this->_data) or empty($this->_data['agb']))
            throw new nook_Exception($this->_error_daten_nicht_korrekt);

        return;
    }

    /**
     * Kontrolliert ob die zusätzliche Mailadresse einem Superuser gehört
     *
     */
    private function _kontrolleSuperuser(){
        $sql = "select id, status from tbl_adressen where email = '".$this->_data['superuser']."' and status = ".$this->_condition_status_administrator;
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $result = $db->fetchAll($sql);

        if(count($result) == 0)
            return;
        elseif(count($result) > 1)
            throw new nook_Exception($this->_error_mehr_als_eine_adminkennung);
        elseif(count($result) == 1){
            $this->_superUser = $result[0]['id'];
        }

        return;

    }

    /**
     * Wenn vorhanden wird die Buchung einem Superuser zugeordnet.
     * Der Status der Buchung wird auf 'angefragt' = 2
     * verändert
     *
     */
    private function _setStatus(){

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;

        if(!empty($this->_superUser)){
            $insert = array();
            $insert['buchungsnummer'] = $this->_buchungsnummer;
            $insert['user_id'] = $this->_superUser;

            $db->insert('tbl_fremdbuchung', $insert);
        }

        $update = array();
        $update['status'] = $this->_condition_status_angefragt;
        $where = "buchungsnummer_id = ".$this->_buchungsnummer;

        // Programmbuchung
        $db->update('tbl_programmbuchung', $update, $where);

        // Hotelbuchung
        $db->update('tbl_hotelbuchung', $update, $where);

        // XML Programmbuchung
        $db->update('tbl_xml_buchung', $update, $where);

        return;
    }
}

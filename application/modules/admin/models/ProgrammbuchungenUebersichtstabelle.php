<?php
/**
 * 17.07.12 12:40
 * Veränderung des Status einer
 * Programmbuchung
 *
 * @author Stephan Krauss
 */

class Admin_Model_ProgrammbuchungenUebersichtstabelle{

    private $_error_programm_id_keine_zahl = 770;
    private $_error_keine_daten = 771;

    private $_anzahl = null; // Anzahl der Programme
    private $_gebuchteProgramme = array(); // gefundene gebuchte Programme
    private $_suchparameter = array(); // Suchparameter
    private $_start = 0;
    private $_limit = 10;


    public function __construct($__params){
        if(array_key_exists('start', $__params)){
            $this->_start = $__params['start'];
            $this->_limit = $__params['limit'];
        }
    }

    /**
     * Holt die verfügbaren Programmbuchungen
     *
     */
    public function getAllProgrammbuchungen(){

        $this
            ->_findeAlleGebuchteProgramme()
            ->_anzahlProgramme();

        return $this->_gebuchteProgramme;
    }

    /**
     * Setzt die Suchparameter zur Anzeige
     * spezieller Datensetze
     *
     * @return void
     */
    public function setzenSuchparameter(array $__suchparameter){

        // Suchparameter Name
        if(array_key_exists('name', $__suchparameter)){
            $name = trim($__suchparameter['name']);
            if(!empty($name))
                $this->_suchparameter['lastname'] = $name;
        }

        // Suchparameter Buchungsnummer
        if(array_key_exists('buchungsnummer', $__suchparameter)){
            $buchungsnummer = trim($__suchparameter['buchungsnummer']);
            if(!empty($buchungsnummer))
                $this->_suchparameter['buchungsnummer_id'] = $buchungsnummer;
        }

        // Suchparameter Superuser
        if(array_key_exists('superuser', $__suchparameter)){
            if(!empty($__suchparameter['superuser']) and ($__suchparameter['superuser'] == 'true')){
                $this->_suchparameter['superuser'] = $__suchparameter['superuser'];
            }
        }


        return $this;
    }

    /**
     * findet die gebuchten Programme
     * Wenn vorhanden werden Suchparameter genutzt
     *
     * @return Admin_Model_Programmbuchungen
     */
    private function _findeAlleGebuchteProgramme(){

        $viewProgrammbuchungenVorhandeneProgramme = new Application_Model_DbTable_viewProgrammbuchungenVorhandeneProgramme(array('db' => 'front'));
        $select = $viewProgrammbuchungenVorhandeneProgramme->select();

        // Buchungsnummer
        if(array_key_exists('buchungsnummer_id', $this->_suchparameter))
            $select->where("buchungsnummer_id = ".$this->_suchparameter['buchungsnummer_id']);

        // Familienname
        if(array_key_exists('lastname', $this->_suchparameter))
            $select->where("lastname like '%".$this->_suchparameter['lastname']."%'");

        // Superuser
        if(array_key_exists('superuser', $this->_suchparameter))
            $select->where("superuser_id is not null");

        // Limit
        $select->limit($this->_limit, $this->_start);

        $ergebnis = $viewProgrammbuchungenVorhandeneProgramme->fetchAll($select);
        $this->_gebuchteProgramme = $ergebnis->toArray();

        return $this;
    }

    /**
     * Ermittelt die Anzahl der Programmbuchungen
     *
     * @return Admin_Model_Programmbuchungen
     */
    private function _anzahlProgramme(){
        $viewGebuchteProgramme = new Application_Model_DbTable_viewProgrammbuchungenVorhandeneProgramme(array('db' => 'front'));
        $select = $viewGebuchteProgramme->select();
        $select->from($viewGebuchteProgramme,'COUNT(id) as anzahl');
        $ergebnis = $viewGebuchteProgramme->fetchRow($select);

        if($ergebnis != null)
            $anzahl = $ergebnis->toArray();
        else
            throw new nook_Exception($this->_error_keine_daten);

        $this->_anzahl = $anzahl['anzahl'];

        return $this;
    }

    /**
     * Gibt die Anzahl der verfügbaren Programme zurück
     *
     */
    public function getAnzahlProgramme(){
        if(empty($this->_anzahl))
            return 0;
        else
            return $this->_anzahl;
    }

    /**
     * setzen der Suchparameter
     *
     */
    public function setSearchParams(array $__params){
        $this->_searchPrams = $__params;

        return $this;
    }
}

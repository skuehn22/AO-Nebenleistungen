<?php
/**
 * 17.07.12 12:40
 * Darstellung der gebuchten Programme in Tabellenform
 *
 * <code>
 *   Codebeispiel
 * </code>
 *
 * @author Stephan Krauss
 */

class Admin_Model_Programmbuchungen{

    private $_error_programm_id_keine_zahl = 740;
    private $_error_buchungsnummer_keine_zahl = 741;
    private $_error_daten_unvollstaendig = 742;
    private $_error_kein_int_wert = 743;

    //-------------- Übersichtstabelle Programmbuchungen --------

    /**
     * Übergibt Parameter an Sub Model 'ProgrammbuchungenUebersichtstabelle'
     *
     * @param $__params
     * @return array
     */
    public function setParameterTabelleProgrammbuchungen($__params){
        $programme = array();

        /** @var $tabelleProgrammbuchungen  */
        $tabelleProgrammbuchungen = new Admin_Model_ProgrammbuchungenUebersichtstabelle($__params);

        // Suchparameter
        if(array_key_exists('name', $__params) or array_key_exists('buchungsnummer', $__params)){
            if(array_key_exists('buchungsnummer', $__params))
                $this->_checkbuchungsnummer($__params['buchungsnummer']);

            $tabelleProgrammbuchungen->setzenSuchparameter($__params);
        }

        
        $programme['gebuchteProgramme'] = $tabelleProgrammbuchungen->getAllProgrammbuchungen();
        $programme['anzahl'] = $tabelleProgrammbuchungen->getAnzahlProgramme();

        return $programme;
    }

    /**
     * Kontrolle der Buchungsnummer
     *
     * @throws nook_Exception
     * @param $__buchungsNummer
     * @return
     */
    private function _checkbuchungsnummer($__buchungsNummer){

        $buchungsnummer = trim($__buchungsNummer);
        $buchungsnummer = (int) $buchungsnummer;
        if(!is_int($buchungsnummer))
            throw new nook_Exception($this->_error_buchungsnummer_keine_zahl);

        return;
    }

    //------- Status -------------------------

    /**
     * Kontrolliert die ID der Programmbuchung
     */
    public function checkProgrammId($__programmId){
        $programmId = (int) $__programmId;
        if(!is_int($programmId))
            throw new nook_Exception($this->_error_programm_id_keine_zahl);

        return $programmId;
    }

    /**
     * Holt die Programm Buchungs Grunddaten
     * Kennung ist Programmbuchungs ID
     *
     * @return void
     */
    public function getProgrammGrundDaten($__programmId){
        $statusProgrammbuchungen = new Admin_Model_ProgrammbuchungenStatus();
        $programmBuchungGrundDaten = $statusProgrammbuchungen->getProgrammGrundDaten($__programmId);

        return $programmBuchungGrundDaten;
    }

    /**
     * Verändert den Status der Programmbuchung
     *
     *
     */
    public function setParameterNeuerStatus($__params){
        if(!array_key_exists('grid_status', $__params) or !array_key_exists('id', $__params))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $__params['grid_status'] = $this->_kontrolleIntWerte($__params['grid_status']);
        $__params['id'] = $this->_kontrolleIntWerte($__params['id']);

        $statusModel = new Admin_Model_ProgrammbuchungenStatus();
        $statusModel->aenderungStatusProgrammbuchung($__params['id'], $__params['grid_status']);

        return;
    }

    private function _kontrolleIntWerte($__ziffer){

        $ziffer = trim($__ziffer);
        $ziffer = (int) $ziffer;
        if(!is_int($ziffer))
            throw new nook_Exception($this->_error_kein_int_wert);

        return $ziffer;
    }

}

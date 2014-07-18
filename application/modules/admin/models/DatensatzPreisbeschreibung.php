<?php
/**
 * 09.10.12 13:34
 * Fehlerbereich:
 * Beschreibung der Klasse
 *
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 * @subpackage Bausteinname
 */

class Admin_Model_DatensatzPreisbeschreibung
{

    // Fehler
    private $_error_anzahl_datensaetze = 930;
    private $_error_keine_daten = 931;

    private $_programmId = null;
    private $_preisvarianteId = null;
    private $_condition_sprache_deutsch = 1;
    private $_condition_sprache_englisch = 2;
    private $_condition_erforderliche_anzahl_datensaetze = 2;

    private $_tabellePreiseBeschreibung = null;

    public function __construct(){
        /** @var _tabellePreiseBeschreibung Application_Model_DbTable_preiseBeschreibung */
        $this->_tabellePreiseBeschreibung = new Application_Model_DbTable_preiseBeschreibung();
    }

    /**
     * setzt ProgrammId
     *
     * @param $__programmId
     * @return Admin_Model_DatensatzPreisbeschreibung
     */
    public function setProgrammId($__programmId){
        $this->_programmId = $__programmId;

        return $this;
    }

    /**
     * setzt preisvariante
     *
     * @param $__preisvarianteId
     * @return Admin_Model_DatensatzPreisbeschreibung
     */
    public function setPreisvarianteId($__preisvarianteId){
        $this->_preisvarianteId = $__preisvarianteId;

        return $this;
    }

    /**
     * Ermittelt die Beschreibungstexte einer Preisvariante
     *
     * @return array|bool
     */
    public function getPreisvarianteBestaetigungstexte(){
        $confirmTexte = $this->_getPreisvarianteBestaetigungstexte();

        return $confirmTexte;
    }

    /**
     * Ermittelt die Bestätigungstexte einer
     * Priesvariante eines Programmes
     *
     * @return array|bool
     */
    private function _getPreisvarianteBestaetigungstexte(){
        $cols = array(
            "confirm_1"
        );

        $select = $this->_tabellePreiseBeschreibung->select();
        $select->from($this->_tabellePreiseBeschreibung, $cols)->where('preise_id = '.$this->_preisvarianteId)->order('sprachen_id');
        $rawConfirmTexte = $this->_tabellePreiseBeschreibung->fetchAll($select)->toArray();

        if(count($rawConfirmTexte) == 2){

            $confirmTexte = array();
            $confirmTexte['confirm_1_de'] = $rawConfirmTexte[0]['confirm_1'];
            $confirmTexte['confirm_1_en'] = $rawConfirmTexte[1]['confirm_1'];

            return $confirmTexte;
        }

        return false;
    }

    /**
     * speichert die Bestätigungstexte
     *
     * @param $__params
     * @throws nook_Exception
     */
    public function setConfirmTexte($__params){

        $update_de = array(
            "confirm_1" => $__params['confirm_1_de']
        );

        $where_de = array(
            "preise_id = ".$__params['preisvarianteId'],
            "sprachen_id = ".$this->_condition_sprache_deutsch
        );

        $this->_tabellePreiseBeschreibung->update($update_de, $where_de);

        $update_en = array(
            "confirm_1" => $__params['confirm_1_en']
        );

        $where_en = array(
            "preise_id = ".$__params['preisvarianteId'],
            "sprachen_id = ".$this->_condition_sprache_englisch
        );

        $this->_tabellePreiseBeschreibung->update($update_en, $where_en);

        return;
    }

    /**
     * $this->_preisvarianteId ist die ID
     * der Tabelle 'tbl_preise_beschreibung'
     *
     */
    private function _findePreiseId(){
        $cols = array(
            "preise_id"
        );

        $select = $this->_tabellePreiseBeschreibung->select();
        $select->from($this->_tabellePreiseBeschreibung, $cols)->where("id = ".$this->_preisvarianteId);

        $ergebnis = $this->_tabellePreiseBeschreibung->fetchRow($select);

        if($ergebnis != null)
            $result = $ergebnis->toArray();
        else
            throw new nook_Exception($this->_error_keine_daten);


        return $result['preise_id'];
    }

} // end class

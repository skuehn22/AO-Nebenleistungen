<?php
/**
 * Fehlerbereich: 1050
 * Bearbeitung der Bilddatei
 * eines Programmes
 *
 * @author Stephan Krauß
 */

class Admin_Model_DatensatzProgrammbild extends nook_Model_model{

    protected $_programmId = null;

	private $_error = 1050;

    public function __construct(){

    }

    /*** Kontrollen ***/
    private $_condition_bilddatei_vorhanden = 1;
    private $_condition_bilddatei_nicht_vorhanden = 2;


    /*** Verarbeitung ***/

    /**
     * Setzen der ProgrammId
     *
     * @param $__programmId
     * @return Admin_Model_DatensatzWerbepreis
     */
    public function setProgrammId($__programmId){
        $this->_programmId = $__programmId;

        return $this;
    }

    /**
     * Löscht die Bilddatei, wenn vorhanden
     *
     * @return int
     */
    public function loeschenProgrammbild(){

        // Pfad aus 'index.php'
        $dateiName = $this->_programmId.'.jpg';
        $bildPfad = ABSOLUTE_PATH."/images/program/midi/".$dateiName;

        // löschen Bilddatei wenn vorhanden
        if (file_exists($bildPfad)) {
            if (unlink($bildPfad))
                return $this->_condition_bilddatei_vorhanden;
        }
        else
            return $this->_condition_bilddatei_nicht_vorhanden;
    }

    /**
     * Kontrolliert ob ein Bild existiert.
     *
     * @param $__bildTyp
     * @param $__bildNummer
     * @return bool
     */
    public function checkExistProgrammBild($__programmId, $__bildTyp){
        $bildUrl = nook_ToolProgrammbilder::findImageFromProgram($__programmId, $__bildTyp);

        return $bildUrl;

    }


}
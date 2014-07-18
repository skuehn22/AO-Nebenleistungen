<?php
/**
 * 17.07.12 12:40
 * Veränderung des Status einer
 * Programmbuchung
 *
 * @author Stephan Krauss
 */

class Admin_Model_ProgrammbuchungenStatus{

    // private $_error_ = 760;

    private $_condition_bereich_programmbuchung = 1;

    public function __construct(){

    }

    /**
     * holt die Grunddaten eines Programmes
     *
     * @param $__programmId
     * @return array
     */
    public function getProgrammGrundDaten($__programmId){

        $viewProgrammbuchungen = new Application_Model_DbTable_viewProgrammbuchungenVorhandeneProgramme(array('db' => 'front'));
        $select = $viewProgrammbuchungen->select();
        $select->where('id = '.$__programmId);
        $row = $viewProgrammbuchungen->fetchAll($select);
        $programmBuchungGrunddaten = $row->toArray();

        return $programmBuchungGrunddaten[0];
    }

    /**
     * Verändert den Status eines gebuchten Programmes
     *
     * @param $id
     * @param $status
     * @return void
     */
    public function aenderungStatusProgrammbuchung($__id, $__status){
        $tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array('db' => 'front'));
        $tabelleXmlProgrammbuchung = new Application_Model_DbTable_xmlBuchung();

        $update = array(
            'status' => $__status
        );

        $where_programmbuchung = "id = ".$__id;
        $where_xmlBuchung = "buchungstabelle_id = ".$__id." and bereich = ".$this->_condition_bereich_programmbuchung;

        $tabelleProgrammbuchung->update($update, $where_programmbuchung);
        $tabelleXmlProgrammbuchung->update($update, $where_xmlBuchung);

        return;
    }

}

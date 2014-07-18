<?php
/**
 * CRUD des Zusatzkommentares
 *
 * + Bereich: Datensatz / Programme
 * + Chield - View: Kommentare
 *
 * @author Stephan Krauss
 * @date 25.02.2014
 * @package admin
 * @subpackage model
 */
class Admin_Model_DatensatzExternerRedakteur{

    private $programmIdEinesProgrammes = null;
    private $_maxIdProgramm = null;
    private $_userId = null;

    // Konditionen
    private $_condition_sprache_deutsch = 1;

    // Errors
    private $_error_programm_id_nicht_vorhanden = 960;
    private $_error_fehler_verbindung_zur_datenbank = 961;
    private $_error_keine_int_zahl = 962;


    // Tabellen / Views
    private $_tabelleProgrammdetailsExterneKommentare = null;
    private $_tabelleAdressen = null;

    public function __construct(){

        // Tabellen / Views
        /** @var _tabelleProgrammdetailsExterneKommentare Application_Model_DbTable_programmdetailsExterneKommentare */
        $this->_tabelleProgrammdetailsExterneKommentare = new Application_Model_DbTable_programmdetailsExterneKommentare();
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();

        return;
    }

    /**
     * Ermittelt die User ID
     *
     * @return Admin_Model_DatensatzExternerRedakteur
     */
    private function _getUserId(){
        $this->_userId = nook_ToolUserId::kundenIdAusSession();

        return $this;
    }

    /**
     * setzen der Programm ID
     *
     * @param $__programmId
     * @return Admin_Model_DatensatzExternerRedakteur
     */
    public function setProgrammId($__programmId){
        $this->programmIdEinesProgrammes = $__programmId;

        return $this;
    }

    /**
     * Ermittelt den letzten Kommentar
     * eines externen Redakteurs zu einem Programm
     *
     * @throws nook_Exception
     */
    public function getZusatzinformationEinesProgrammes(){

        $zusatzinformationDaten = $this
            ->_getUserId()
            ->_getMaxIdKommentar()
            ->_getZusatzinformationEinesProgrammes();

        return $zusatzinformationDaten;
    }

    /**
     * Ermitteln letzten Kommentar zum Programm
     *
     * @throws nook_Exception
     */
    private function _getZusatzinformationEinesProgrammes(){
        if(empty($this->programmIdEinesProgrammes))
            throw new nook_Exception($this->_error_programm_id_nicht_vorhanden);

        if(empty($this->_tabelleProgrammdetailsExterneKommentare))
            throw new nook_Exception($this->_error_fehler_verbindung_zur_datenbank);

        $datenKommentarEinesProgrammes = array(
            'kommentar' => ' ',
            'status' => 1,
            'datum' => ' ',
            'nameRedakteur' => ' '
        );

        // wenn Kommentar vorhanden
        if(!empty($this->_maxIdProgramm)){
            // Daten Kommentar
            $datenKommentarEinesProgrammes = $this->_tabelleProgrammdetailsExterneKommentare->find($this->_maxIdProgramm)->toArray();

            // formatieren Datum
            $datenKommentarEinesProgrammes[0]['datum'] = nook_ToolZeiten::generiereDatumNachAnzeigesprache($datenKommentarEinesProgrammes[0]['datum'], $this->_condition_sprache_deutsch);

            // Name des Redakteur
            $nameRedakteur = $this->_tabelleAdressen->find($datenKommentarEinesProgrammes[0]['adressen_id'])->toArray();

            $datenKommentarEinesProgrammes[0]['nameRedakteur'] = $nameRedakteur[0]['title']." ".$nameRedakteur[0]['firstname']." ".$nameRedakteur[0]['lastname'];

            return $datenKommentarEinesProgrammes[0];
        }

        return $datenKommentarEinesProgrammes;
    }

    /**
     * ermitteln der max ID der Kommentare eines
     * Programmes
     *
     * @return Admin_Model_DatensatzExternerRedakteur
     */
    private function _getMaxIdKommentar(){
        $select = $this->_tabelleProgrammdetailsExterneKommentare->select();
        $select
            ->from($this->_tabelleProgrammdetailsExterneKommentare, array(new Zend_Db_Expr('max(id) as maxId')))
            ->where("programmdetails_id = ".$this->programmIdEinesProgrammes);

        $query = $select->__toString();

        $ergebnis = $this->_tabelleProgrammdetailsExterneKommentare->fetchRow($select);

        if($ergebnis != null){
            $row = $ergebnis->toArray();
            $this->_maxIdProgramm = $row['maxId'];
        }


        return $this;
    }

    /**
     * Kontrolliert die übermittelten Formularwerte
     *
     * @param $__params
     * @return mixed
     * @throws nook_Exception
     */
    public function checkSubmit($__params){
        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        $__params['status'] = (int) $__params['status'];

        if(!filter_var($__params['status'], FILTER_VALIDATE_INT))
            throw new nook_Exception($this->_error_keine_int_zahl);

        return $__params;
    }

    /**
     * speichern der Zusatzinformation
     *
     * @param $__params
     */
    public function insertZusatzinformation($__params){
        $this
            ->_getUserId()
            ->_insertZusatzinformation($__params);

        return;
    }

    /**
     * Trägt die Zusatzinformation
     * eines Programmes ein
     *
     * @return Admin_Model_DatensatzExternerRedakteur
     */
    private function _insertZusatzinformation($__params){
        $__params['adressen_id'] = $this->_userId;

        $this->_tabelleProgrammdetailsExterneKommentare->insert($__params);

        return $this;
    }

    /**
     * update der Zusatzinformation
     *
     * @param $__params
     */
    public function updateZusatzinformation($__params){
        $this
            ->_getMaxIdKommentar()
            ->_getUserId()
            ->_updateZusatzinformation($__params);


    }

    /**
     * Update Bearbeitungsstand
     * des Kommentares zu einem Programm
     *
     */
    private function _updateZusatzinformation($__params){

        $update = array(
            'adressen_id' => $this->_userId,
            'kommentar' => $__params['kommentar'],
            'status' => $__params['status']
        );

        $where = "id = ".$this->_maxIdProgramm;

        $this->_tabelleProgrammdetailsExterneKommentare->update($update, $where);

        return;

    }


}
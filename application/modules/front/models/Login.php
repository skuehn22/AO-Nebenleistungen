<?php
class Front_Model_Login extends nook_Model_model{

    // Error
	public $error_no_database_connect = 170;

    // Tabellen / View
    private $_tabelleAoCity = null;


    // Konditionen
    private $_condition_trennung_staedte_teaser = '<br><br>';
    private $_condition_typ_stadtbild = 10;


    private $_zusatzInformationStadtBilder = array();


    public function __construct(){
        /** @var _tabelleAoCity Application_Model_DbTable_aoCity */
        $this->_tabelleAoCity = new Application_Model_DbTable_aoCity();
    }

    /**
     * Gibt im Fehlerfall die Fehlermeldung zurück
     *
     * @param $__params
     * @return mixed
     */
    public function getErrorMessage($__params){

        /** @var $tabelleErrorLog Application_Model_DbTable_logSystem */
        $tabelleErrorLog = new Application_Model_DbTable_logSystem(array('db' => 'front'));
        $select = $tabelleErrorLog->select()->from($tabelleErrorLog, array(new Zend_Db_Expr('max(id) as id')));
        $row = $tabelleErrorLog->fetchRow($select)->toArray();

		return $row['id'];
	}

    /**
     * Gibt die Zusatzinformation der
     * Städtebilder zurück.
     *
     * @return array
     */
    public function getZusatzinformationStaedte(){

        $this->_ermittleStaedteTexteUbdBildzusatzinformationen();

        return $this->_zusatzInformationStadtBilder;
    }

    /**
     * Ermittelt die Stadtbeschreibung und
     * die Zusatzinformationen der Bilder.
     * Bildet den Knoten für Zusatzinformationen
     *der Bilder.
     *
     */
    private function _ermittleStaedteTexteUbdBildzusatzinformationen(){

        $cols = array(
            'AO_City_ID'
        );

        $select = $this->_tabelleAoCity->select();
        $select->from($this->_tabelleAoCity, $cols);
        $rows = $this->_tabelleAoCity->fetchAll($select)->toArray();

        for($i=0; $i < count($rows); $i++){

            $cityId = $rows[$i]['AO_City_ID'];

            $this->_zusatzInformationStadtBilder[$cityId]['bildinformation'] = array(); // leerer Knoten der Bildinformation

            $this
                ->_getStadtText($cityId)
                ->_getZusatzinformationBild($cityId);
        }
    }

    /**
     * Ermittelt die Stadtbeschreibung einer Stadt.
     *
     * @param $__cityId
     * @return Front_Model_Login
     */
    private function _getStadtText($__cityId){
        $toolStadtbeschreibung = new nook_ToolStadtbeschreibung();
        $stadtbeschreibung = $toolStadtbeschreibung
            ->setStadtId($__cityId)
            ->setTrennungWoerter($this->_condition_trennung_staedte_teaser)
            ->getStadtbeschreibung();

        $this->_zusatzInformationStadtBilder[$__cityId]['stadttext'] = $stadtbeschreibung;

        return $this;
    }

    /**
     * Ermittelt die Zusatzinformationen eines Stadtbildes
     *
     * @param $__cityId
     * @return Front_Model_Login
     */
    private function _getZusatzinformationBild($__cityId){

        $toolBilder = nook_ToolBild::factory();
        $bildinformation = $toolBilder
            ->setBildId($__cityId)
            ->setBildTyp($this->_condition_typ_stadtbild)
            ->getZusatzinformationBild();

        $this->_zusatzInformationStadtBilder[$__cityId]['bildinformation'] = $bildinformation;

        return $this;
    }



}
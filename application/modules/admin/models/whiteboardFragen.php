<?php
class Admin_Model_whiteboardFragen{


    private $_start = 0;
    private $_limit = 10;

    // Fehler
    private $_error_keine_daten = 1090;

    // Konditionen
    private $_condition_sprache_deutsch = 1;
    private $_condition_kommentar_nicht_erledigt = 1;
    private $_condition_kommentar_erledigt = 2;

    // Datenbank
    private $_db = null;

    // Views / Tabellen
    private $_viewKommentareProgrammdetails = null;
    private $_tabelleProgrammdetailsExterneKommentare = null;

	public function __construct(){
		$this->_db = Zend_Registry::get('front');

        /** @var _viewKommentareProgrammdetails Application_Model_DbTable_viewKommentareProgrammdetails */
        $this->_viewKommentareProgrammdetails = new Application_Model_DbTable_viewKommentareProgrammdetails();
        /** @var _tabelleProgrammdetailsExterneKommentare Application_Model_DbTable_programmdetailsExterneKommentare */
        $this->_tabelleProgrammdetailsExterneKommentare = new Application_Model_DbTable_programmdetailsExterneKommentare();

		return;
	}

    /**
     * Gibt die offenen Fragen / Kommentare
     * und die Anzahl der noch
     * offenen Fragen / Kommentare zurück
     *
     * @return array
     */
    public function getOffeneKommentare($__params){

        $offeneFragen = array();
        $offeneFragen['data'] = $this->_offeneKommentare($__params);
        $offeneFragen['anzahl'] = $this->_anzahlOffeneKommentare();

        return $offeneFragen;
    }

    /**
    * Ermittelt die offenen Fragen / Kommentare
    * für die Tabelle. Seitenweise darstellung der
    * Fragen
    *
    * @param $__params
    * @return array
    */
    private function _offeneKommentare($__params){

        if(array_key_exists('limit', $__params)){
            $this->_start = $__params['start'];
            $this->_limit = $__params['limit'];
        }

        $select = $this->_viewKommentareProgrammdetails->select();
        $select
            ->where("status = ".$this->_condition_kommentar_nicht_erledigt)
            ->limit($this->_limit, $this->_start);

        $offeneKommentare = $this->_viewKommentareProgrammdetails->fetchAll($select)->toArray();

        return $offeneKommentare;
    }

    /**
     * Ermittelt die Anzahl der offenen Kommentare
     * aller Programme
     *
     * @return mixed
     */
    private function _anzahlOffeneKommentare(){

        $cols = array(
            'id' => new Zend_Db_Expr("count(id)")
        );

        $select = $this->_viewKommentareProgrammdetails->select();
        $select->from($this->_viewKommentareProgrammdetails, $cols)->where("status = ".$this->_condition_kommentar_nicht_erledigt);

        $ergebnis = $this->_viewKommentareProgrammdetails->fetchRow($select);

        if($ergebnis != null)
            $anzahlOffeneKommentare = $ergebnis->toArray();
        else
            throw new nook_Exception($this->_error_keine_daten);

        return $anzahlOffeneKommentare['id'];
    }

    /**
     * Ändert den Status eines offenen Kommentares
     *
     * @param $__idKommentar
     */
    public function offeneKommentarStatusaenderung($__idKommentar){

        $update = array(
            'status' => $this->_condition_kommentar_erledigt
        );

        $this->_tabelleProgrammdetailsExterneKommentare->update($update, "id = ".$__idKommentar);

        return;
    }


}
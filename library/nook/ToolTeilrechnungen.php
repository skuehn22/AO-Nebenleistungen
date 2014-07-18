<?php
/**
 * Tool zur Ermittlung der ID
 * einer Teilrechnung des Warenkorbes
 *
 * Überprüft ob für einen Teil der Artikel
 * im Warenkorb eine Teilrechnung / 'PartId'
 * vorhanden ist.
 *
 * Ist diese 'PartId' vorhanden,
 * so wird diese ID zurückgegeben.
 * Wenn nicht vorhanden, so wird diese
 * ID generiert und zurück gegeben.
 *
 * @author Stephan.Krauss
 * @date 20.12.12
 * @file ToolTeilrechnungen.php
 */
class nook_ToolTeilrechnungen {

    protected $_teilrechnungsId = null;

    protected $_vorhandeneBuchungsBereiche = array(
        '1' => 'programmbuchung',
        '6' => 'hotelbuchung'
    );

    protected $_insertTeilrechnung = array(
        "ort_id" => null,
        "startDatum" => null,
        "personenanzahl" => null,
        "zeitraum" => null,
        "buchungsBereich" => null,
        "buchungsnummer_id" => null
    );

    // Fehler
    private $_error_keine_int_zahl = 1080;
    private $_error_buchungsbereich_nicht_vorhanden = 1081;
    private $_error_ausgangswert_nicht_vorhanden = 1082;
    private $_error_falsche_anzahl_datensaetze = 1083;

    // Optionen

    // Tabellen und View
    private $_tabelleTeilrechnungen = null;

    public function __construct(){
        /** @var _tabelleTeilrechnungen Application_Model_DbTable_teilrechnungen */
        $this->_tabelleTeilrechnungen = new Application_Model_DbTable_teilrechnungen();

        return;
    }

    /**
     * @param $__buchungsnummerId
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    public function setBuchungsnummer($__buchungsnummerId){

        $__buchungsnummerId = (int) $__buchungsnummerId;

        if(!is_int($__buchungsnummerId))
            throw new nook_Exception($this->_error_keine_int_zahl);

        $this->_insertTeilrechnung['buchungsnummer_id'] = $__buchungsnummerId;

        return $this;
    }

    /**
     * @param $__ortId
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    public function setOrtId($__ortId){
        $__ortId = (int) $__ortId;

        if(!is_int($__ortId))
            throw new nook_Exception($this->_error_keine_int_zahl);

        $this->_insertTeilrechnung['ort_id'] = $__ortId;

        return $this;
    }

    /**
     * @param $__beginnDerMassnahme
     * @return nook_ToolTeilrechnungen
     */
    public function setStartDatum($__beginnDerMassnahme){

        $this->_insertTeilrechnung['startDatum'] = $__beginnDerMassnahme;

        return $this;
    }

    /**
     * @param $__anzahlDerPersonenAnEinerMassnahme
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    public function setPersonenAnzahl($__anzahlDerPersonenAnEinerMassnahme){

        $__anzahlDerPersonenAnEinerMassnahme = (int) $__anzahlDerPersonenAnEinerMassnahme;

        if(!is_int($__anzahlDerPersonenAnEinerMassnahme))
            throw new nook_Exception($this->_error_keine_int_zahl);

        $this->_insertTeilrechnung['personenanzahl'] = $__anzahlDerPersonenAnEinerMassnahme;

        return $this;
    }

    /**
     * @param $__zeitraumDerMassnahmeInTage
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    public function setZeitraum($__zeitraumDerMassnahmeInTage){
        $__zeitraumDerMassnahmeInTage = (int) $__zeitraumDerMassnahmeInTage;

        if(!is_int($__zeitraumDerMassnahmeInTage))
            throw new nook_Exception($this->_error_keine_int_zahl);

        $this->_insertTeilrechnung['zeitraum'] = $__zeitraumDerMassnahmeInTage;

        return $this;
    }

    /**
     * @param $teilrechnungsId
     * @return nook_ToolTeilrechnungen
     */
    public function setTeilrechnungsId ($teilrechnungsId)
    {
        $this->_teilrechnungsId = $teilrechnungsId;

        return $this;
    }

    /**
     * @param $__idDesBuchungsBereich
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    public function setBuchungsBereich($__idDesBuchungsBereich){

        $__idDesBuchungsBereich = (int) $__idDesBuchungsBereich;
        if(!is_int($__idDesBuchungsBereich))
            throw new nook_Exception($this->_error_keine_int_zahl);

        if(!array_key_exists($__idDesBuchungsBereich, $this->_vorhandeneBuchungsBereiche))
            throw new nook_Exception($this->_error_buchungsbereich_nicht_vorhanden);

        $this->_insertTeilrechnung['buchungsBereich'] = $__idDesBuchungsBereich;

        return $this;
    }

    /**
     * Kontrolliert Ausgangswerte.
     * Ermittelt Teilrechnungs ID.
     * Wenn notwendig wird eine neue Teilrechnung angelegt.
     *
     * @return
     */
    public function getTeilrechnungsId(){

        $this
            ->_checkAusgangsDaten()
            ->_findenIdDerTeilrechnung();

        return $this->_teilrechnungsId;
    }

    /**
     * @return nook_ToolTeilrechnungen
     * @throws nook_Exception
     */
    private function _checkAusgangsDaten(){

        if(empty($this->_insertTeilrechnung['buchungsnummer_id']))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(empty($this->_insertTeilrechnung['ort_id']))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(empty($this->_insertTeilrechnung['startDatum']))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(empty($this->_insertTeilrechnung['personenanzahl']))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(empty($this->_insertTeilrechnung['zeitraum']))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(empty($this->_insertTeilrechnung['buchungsBereich']))
                    throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        return $this;
    }

    /**
     * Findet die Teilrechnungsnummer einer Buchung
     *
     * an Hand der bereits gespeicherten Datensaetze
     * in der tabelle 'tbl_teilrechnungen'.
     *
     * @return nook_ToolTeilrechnungen
     */
    private function _findenIdDerTeilrechnung(){

        $cols = array(
            'id'
        );

        $select = $this->_tabelleTeilrechnungen->select();
        // from
        $select->from($this->_tabelleTeilrechnungen, $cols);

        // where Klausel
        foreach($this->_insertTeilrechnung as $key => $value){

            if($key == 'artikel_id')
                continue;

            if($key == 'personenanzahl')
                continue;

            $select->where($key." = '".$value."'");
        }

        $query = $select->__toString();

        $rowObj = $this->_tabelleTeilrechnungen->fetchRow($select);

        if($rowObj === null)
             $this->_neueTeilrechnungsId();
        else{
            $row = $rowObj->toArray();
            $this->_teilrechnungsId = $row['id'];
        }

        return $this;
    }

    /**
     * @return nook_ToolTeilrechnungen
     */
    private function _neueTeilrechnungsId(){
        $this->_teilrechnungsId = $this->_tabelleTeilrechnungen->insert($this->_insertTeilrechnung);

        return $this;
    }

    /**
     * Nimmt die Werte zur Bestimmung der
     * Teilrechnungs - ID einer Zimmerbuchung entgegen
     *
     * @param int $__buchungsnummerId
     * @param int $__bereichId
     * @param int $__ortId
     * @throws nook_Exception
     * return int
     */
    public function getIdTeilrechnungZimmerbuchung($__buchungsnummerId, $__bereichId, $__ortId, $datum = false, $__personenAnzahl = false, $__naechte = false){

        $buchungsnummerId = (int) $__buchungsnummerId;
        if(empty($buchungsnummerId))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        $bereichId = (int) $__bereichId;
        if(empty($bereichId))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        $ortId = (int) $__ortId;
        if(empty($ortId))
            throw new nook_Exception($this->_error_ausgangswert_nicht_vorhanden);

        if(!empty($datum)){
            $anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
            $datum = nook_ToolDatum::konvertDatumInDate($anzeigesprache, $datum);
        }


        $teilrechnungsId = $this->_findeIdTeilrechnungZimmerbuchung($buchungsnummerId, $bereichId, $ortId, $datum, $__personenAnzahl, $__naechte);

        return $teilrechnungsId;
    }

    /**
     * Ermittelt die Teilrechnungs ID einer Zimmerbuchung
     *
     * @param $buchungsnummerId
     * @param $bereichId
     * @param $ortId
     */
    private function _findeIdTeilrechnungZimmerbuchung($buchungsnummerId, $bereichId, $ortId, $datum, $personenAnzahl, $naechte){

        $cols = array('id');

        $select = $this->_tabelleTeilrechnungen->select();

        // Bedingungen zur Abfrage der Teilrechnungs ID
        $select = $this->_erstelleBedingungenZurAbfrageTeilrechnungsId(
            $buchungsnummerId,
            $bereichId,
            $ortId,
            $datum,
            $personenAnzahl,
            $naechte,
            $select,
            $cols
        );


        $rows = $this->_tabelleTeilrechnungen->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_falsche_anzahl_datensaetze);

        return $rows[0]['id'];
    }

    /**
     * Nimmt die Eingrenzung der Abfrage der Teilrechnungs ID
     *
     * aus 'tbl_teilrechnungen' vor.
     * + optional mit Startdatum
     * + optional mit Personenanzahl
     * + optional mit Übernachtungsanzahl
     *
     * @param $buchungsnummerId
     * @param $bereichId
     * @param $ortId
     * @param $datum
     * @param $personenAnzahl
     * @param $naechte
     * @param $select
     * @param $cols
     *
     * return
     */
    private function _erstelleBedingungenZurAbfrageTeilrechnungsId (
        $buchungsnummerId,
        $bereichId,
        $ortId,
        $datum,
        $personenAnzahl,
        $naechte,
        $select,
        $cols
    ) {
        $select
            ->from($this->_tabelleTeilrechnungen, $cols)
            ->where("buchungsnummer_id = " . $buchungsnummerId)
            ->where("buchungsBereich = " . $bereichId)
            ->where("ort_id = " . $ortId);

        // wenn Startdatum vorhanden
        if(!empty($datum))
            $select->where("startDatum = '" . $datum . "'");

        // wenn Personenanzahl vorhanden
        if(!empty($personenAnzahl))
            $select->where("personenanzahl = " . $personenAnzahl);

        // wenn Anzahl Nächte bekannt
        if(!empty($naechte))
            $select->where("zeitraum = " . $naechte);

        return $select;
    }

} // end class

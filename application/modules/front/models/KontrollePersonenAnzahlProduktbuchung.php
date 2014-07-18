<?php

/**
 * Kontrolliert die Personenanzahl der Buchungsdatensaetze einer Hotelbuchung
 *
 * + Ermittelt die Personenanzahl einer Teilrechnung
 * + Ermittelt die Zimmeranzahl
 * + Ermittelt die Anzahl der Naechte
 * + Korrigiert die Personenanzahl der Buchungsdatensaetze einer Hotelbuchung in 'tbl_produktbuchung'
 *
 * @author stephan.krauss
 * @date 28.05.13
 * @file KontrollePersonenAnzahlProduktbuchung.php
 * @package front
 * @subpackage model
 */
class Front_Model_KontrollePersonenAnzahlProduktbuchung
{

    // Tabellen / Views
    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;

    // Error
    private $_error_anfangswerte_fehlen = 1540;
    private $_error_buchungsdatensaetze_nicht_vorhanden = 1541;

    // Konditionen

    // Flags

    protected $_teilrechnungId = null;
    protected $_personenanzahlTeilrechnung = null;
    protected $_zimmeranzahlTeilrechnung = null;
    protected $_uebernachtungenTeilrechnung = null;

    protected $_rowsetsHotelEinerTeilrechnung = array();
    protected $_rowsetProdukteEinerTeilrechnung = array();

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
    }

    /**
     * Gibt die Datensaetze einer Hotelbuchung zurueck
     *
     * @return array|bool
     */
    public function getDatensaetzeHotelbuchungTeilrechnung ()
    {
        if(empty($this->_rowsetsHotelEinerTeilrechnung)) {
            return false;
        }

        /** @var $datensaetzeHotelbuchung Zend_Db_Table_Rowset */
        $datensaetzeHotelbuchung = $this->_rowsetsHotelEinerTeilrechnung;
        return $datensaetzeHotelbuchung->toArray();
    }

    /**
     * @return int
     */
    public function getPersonenanzahlTeilrechnung ()
    {
        return $this->_personenanzahlTeilrechnung;
    }

    /**
     * @return int
     */
    public function getUebernachtungenTeilrechnung ()
    {
        return $this->_uebernachtungenTeilrechnung;
    }

    /**
     * @return int
     */
    public function getZimmeranzahlTeilrechnung ()
    {
        return $this->_zimmeranzahlTeilrechnung;
    }

    /**
     * @param $teilrechnungId
     * @return Front_Model_KontrollePersonenAnzahlHotelbuchung
     */
    public function setTeilrechnungId ($teilrechnungId)
    {
        $this->_teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * Bestimmen der Daten der Hotelbuchung
     *
     * + Datensaetze der Hotelbuchung
     * + Personenanzahl der Teilrechnung
     * + Anzahl Nächte der Teilrechnung
     * + Anzahl der Zimmer
     *
     * @return Front_Model_KontrollePersonenAnzahlProduktbuchung
     * @throws nook_Exception
     */
    public function bestimmenDatenHotelbuchung ()
    {
        if(empty($this->_teilrechnungId)) {
            throw new nook_Exception($this->_error_anfangswerte_fehlen);
        }

        $this->_bestimmeDatensaetzeHotelbuchung();
        $this->_bestimmePersonenanzahlTeilrechnung();
        $this->_bestimmeAnzahlNaechteTeilrechnung();
        $this->_bestimmeAnzahlZimmerTeilrechnung();

        return $this;
    }

    /**
     * Bestimmen der Datensaetz Objekte der Hotelbuchung
     *
     * @return int
     * @throws nook_Exception
     */
    private function _bestimmeDatensaetzeHotelbuchung ()
    {
        $select = $this->_tabelleHotelbuchung->select();
        $select->where("teilrechnungen_id = " . $this->_teilrechnungId);

        $rowset = $this->_tabelleHotelbuchung->fetchAll($select);

        if(count($rowset) < 1) {
            throw new nook_Exception($this->_error_buchungsdatensaetze_nicht_vorhanden);
        }

        $this->_rowsetsHotelEinerTeilrechnung = $rowset;

        return count($rowset);
    }

    /**
     * Bestimmt die Personenanzahl der Teilrechnung
     *
     * @return int
     */
    private function _bestimmePersonenanzahlTeilrechnung ()
    {
        $this->_personenanzahlTeilrechnung = 0;

        foreach($this->_rowsetsHotelEinerTeilrechnung as $row) {
            $this->_personenanzahlTeilrechnung += $row->personNumbers;
        }

        return $this->_personenanzahlTeilrechnung;
    }

    /**
     * Bestimmt die Anzahl der Nächte einer Teilrechnung
     *
     * @return int
     */
    private function _bestimmeAnzahlNaechteTeilrechnung ()
    {
        $this->_uebernachtungenTeilrechnung = 0;

        foreach($this->_rowsetsHotelEinerTeilrechnung as $row) {
            $this->_uebernachtungenTeilrechnung = $row->nights;
        }

        return $this->_uebernachtungenTeilrechnung;
    }

    /**
     * Bestimmt die Anzahl der Zimmer einer Teilrechnung
     *
     * @return int
     */
    private function _bestimmeAnzahlZimmerTeilrechnung ()
    {
        $this->_zimmeranzahlTeilrechnung = 0;

        foreach($this->_rowsetsHotelEinerTeilrechnung as $row) {
            $this->_zimmeranzahlTeilrechnung += $row->roomNumbers;
        }

        return $this->_zimmeranzahlTeilrechnung;
    }

    /**
     * Korrigiert die Personenanzahl einer Hotelbuchung / Teilrechnung
     *
     * in den Buchungsdatensaetzen der Tabelle 'tbl_produktbuchung'
     *
     * + Kontrlle der Ausgangswerte
     * + bestimmen Datensaetze der produktbuchung einer Teilrechnung
     *
     * @return Front_Model_KontrollePersonenAnzahlProduktbuchung
     * @throws nook_Exception
     */
    public function korrekturPersonenanzahlHotelbuchungenEinerTeilrechnung ()
    {

        if((empty($this->_personenanzahlTeilrechnung)) or (empty($this->_zimmeranzahlTeilrechnung)) or (empty($this->_uebernachtungenTeilrechnung)) or (empty($this->_teilrechnungId))) {
            throw new nook_Exception($this->_error_anfangswerte_fehlen);
        }

        $this->_ermittelnDerProduktbuchungEinerTeilrechnung();
        $this->_korrekturPersonenanzahlroduktbuchungenEinerTeilrechnung();

        return $this;
    }

    /**
     * Gibt die Produkte einer Teilrechnung zurück.
     *
     * + Die Anzahl der möglichen Produkte der Teilrechnung wurde korrigiert
     *
     * @return bool / array
     */
    public function getKorrigierteProdukteHotelbuchungEinerTeilrechnung ()
    {
        if(empty($this->_rowsetProdukteEinerTeilrechnung)) {
            return false;
        }

        return $this->_rowsetProdukteEinerTeilrechnung->toArray();
    }

    /**
     * Ermittelt das Rowset der gebuchten Zusatzprodukte einer Hotelbuchung
     *
     * @return int
     */
    private function _ermittelnDerProduktbuchungEinerTeilrechnung ()
    {
        $select = $this->_tabelleProduktbuchung->select();
        $select->where("teilrechnungen_id = " . $this->_teilrechnungId);

        $this->_rowsetProdukteEinerTeilrechnung = $this->_tabelleProduktbuchung->fetchAll($select);

        $anzahl = count($this->_rowsetProdukteEinerTeilrechnung);

        return $anzahl;
    }

    /**
     * Korrigiert die Personenanzahl der gebuchten Zusatzprodukte einer Teilbuchung Hotelbuchung
     *
     * + Kontrolliert die mögliche max. Anzahl der Buchung von Zusatzprodukten einer Teilrechnung
     * + Korrigiert bei Bedarf die Anzahl der produkte einer Teilrechnung
     * + Speichert die Korrektur ab
     *
     * @return int
     */
    private function _korrekturPersonenanzahlroduktbuchungenEinerTeilrechnung ()
    {
        foreach($this->_rowsetProdukteEinerTeilrechnung as $row) {
            $produktTyp = $row->produktTyp;
            $anzahl = $row->anzahl;
            $anzahlVeraenderterDatensaetze = 0;

            switch ($produktTyp) {
                case "1": // je Person
                    if($anzahl > $this->_personenanzahlTeilrechnung) {
                        $row->anzahl = $this->_personenanzahlTeilrechnung;
                    }
                    break;
                case "2": // je Zimmer
                    if($anzahl > $this->_zimmeranzahlTeilrechnung) {
                        $row->anzahl = $this->_zimmeranzahlTeilrechnung;
                    }
                    break;
                case "3": // Personen * Nächte
                    if($anzahl > $this->_personenanzahlTeilrechnung) {
                        $row->anzahl = $this->_personenanzahlTeilrechnung;
                    }
                    break;
                case "5": // Stück * Nächte
                    if($anzahl > $this->_uebernachtungenTeilrechnung) {
                        $row->anzahl = $this->_uebernachtungenTeilrechnung;
                    }
                    break;
            }

            $row->save();
            $anzahlVeraenderterDatensaetze++;
        }

        return $anzahlVeraenderterDatensaetze;
    }
} // end class

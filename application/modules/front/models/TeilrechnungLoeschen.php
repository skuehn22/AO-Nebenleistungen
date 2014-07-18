<?php
/**
 * Löschen Hotelbuchung einer Teilrechnung
 *
 * + löschen Hotelbuchungen einer Teilrechnung
 * + löschen Zusatzprodukte einer Teilrechnung
 * + löschen XML - Dateien einer Hotelbuchung der Teilrechnung
 *
 *
 * @author stephan.krauss
 * @date 29.05.13
 * @file TeilrechnungLoeschen.php
 * @package front
 * @subpackage model
 */
class Front_Model_TeilrechnungLoeschen
{

    // Tabellen / Views
    private $_tabelleHotelbuchung = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleXmlBuchung = null;

    // Konditionen
    private $_condition_artikel_vorgemerkt = 2;
    private $_condition_bereich_hotelbuchung = 6;

    // Fehler
    private $_error_anfangswerte_fehlen = 1550;

    // Flags

    protected $_teilrechnungId = null;
    protected $_buchungsnummerId;
    protected $_where = array();

    function __construct ()
    {
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();
    }

    /**
     * @param $teilrechnungId
     * @return Front_Model_TeilrechnungLoeschen
     */
    public function setTeilrechnungId ($teilrechnungId)
    {
        $this->_teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * Löschen der Hotelbuchung und der Zusatzprodukte einer Teilrechnung
     *
     * + erstellen Löschkriterien
     * + bestimmen Buchungsnummer
     * + löschen Hotelbuchung
     * + löschen Zusatzprodukte
     * + löschen XML Dateien
     *
     * @throws nook_Exception
     */
    public function loeschenHotelbuchungEinerTeilrechnung ()
    {
        if(empty($this->_teilrechnungId)) {
            throw new nook_Exception($this->_error_anfangswerte_fehlen);
        }

        $this->_bestimmenBuchungsnummer();
        $this->_erstellenLoeschKriterien();
        $this->_loeschenHotelbuchungEinerTeilrechnung();
        $this->_loeschenProduktbuchungEinerTeilrechnung();
        $this->_loeschenXmlDatei();

    }

    /**
     * Erstellt die 'where' bedingungen der Löschabfragen
     */
    private function _erstellenLoeschKriterien ()
    {

        $this->_where = array(
            "buchungsnummer_id = " . $this->_buchungsnummerId,
            "teilrechnungen_id = " . $this->_teilrechnungId,
            "status <= ".$this->_condition_artikel_vorgemerkt
        );

        return;
    }

    /**
     * Löscht die Einträge in 'tbl_hotelbuchung'
     *
     * @return int
     */
    private function _loeschenHotelbuchungEinerTeilrechnung ()
    {
        $anzahlGeloescht = $this->_tabelleHotelbuchung->delete($this->_where);

        return $anzahlGeloescht;
    }

    /**
     * Löscht die Zusatzprodukte einer Teilbuchung
     *
     * @return int
     */
    private function _loeschenProduktbuchungEinerTeilrechnung ()
    {
        $anzahlGeloescht = $this->_tabelleProduktbuchung->delete($this->_where);

        return $anzahlGeloescht;
    }

    /**
     * Löscht die Datensätze XML aus 'tbl_xml_buchung'
     */
    private function _loeschenXmlDatei ()
    {
        $where = $this->_where;
        $where[] = "bereich = ".$this->_condition_bereich_hotelbuchung;

        $anzahlGeloescht = $this->_tabelleXmlBuchung->delete($where);

        return;
    }

    /**
     * Findet die Buchungsnummer der aktuellen Buchung
     */
    private function _bestimmenBuchungsnummer ()
    {
        $this->_buchungsnummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        return;
    }

} // end class

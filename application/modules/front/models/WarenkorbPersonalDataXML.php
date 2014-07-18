<?php
/**
 * Generiert die Personendaten im XML Format
 *
 * + setzt den Debug Modus
 * + Übernimmt die Kunden ID und die Kundendaten
 * + Speichert die Kundendaten als XML - Block
 * + Gibt XML - Block
 * + Schreibt den XML - Block des Kunden
 * + Speichert den XML - Block des Kunden
 * + prüft ob der XML - Block des Kunden
 *
 * @date 10.24.2013
 * @file WarenkorbPersonalDataXML.php
 * @package front
 * @subpackage model
 */
class Front_Model_WarenkorbPersonalDataXML extends Pimple_Pimple
{

    private $_error_kundendaten_mehrfach_vorhanden = 700;

    private $_kundenId = null;
    private $_kundenDaten = array();

    private $_db_groups = null;

    private $_XMLWriter = null;
    private $_XMLblock = null;

    private $_debugModus = false;

    public function __construct()
    {
        $this->_db_groups = Zend_Registry::get('front');
        $this->_XMLWriter = new XMLWriter();

        return;
    }

    /**
     * setzt den Debug Modus
     *
     * @param bool $__debugModus
     * @return bool
     */
    public function setDebugModus($__debugModus = false)
    {

        return $this->_debugModus = $__debugModus;
    }

    /**
     * Übernimmt die Kunden ID und die Kundendaten
     *
     * @param $__kundenId
     * @param $__kundenDaten
     * @return Front_Model_WarenkorbPersonalDataXML
     */
    public function setKundenDaten($__kundenId, $__kundenDaten)
    {

        $this->_kundenId = $__kundenId;
        $this->_kundenDaten = $__kundenDaten;

        return $this;
    }

    /**
     * Speichert die Kundendaten als XML - Block
     * in die Tabelle 'xml_kundendaten'
     *
     * @return void
     */
    public function saveKundenDatenXML()
    {
        $this->_writeXmlBlock();
        $this->_saveXmlBlock();

        // $this->_control();

        return;
    }

    /**
     * Gibt XML - Block
     * in eine Datei aus.
     * Achtung !!!
     * Nur zu Kontrollzwecken
     *
     */
    private function _control()
    {
        $fp = fopen('kundenXml.xml', 'w');
        fputs($fp, $this->_XMLblock);
        fclose($fp);

        return;
    }

    /**
     * Schreibt den XML - Block des Kunden
     *
     * @return Front_Model_WarenkorbPersonalDataXML
     */
    private function _writeXmlBlock()
    {
        $this->_XMLWriter->openMemory();
        $this->_XMLWriter->setIndent(4);

        $this->_XMLWriter->startElement('PersonName');
        $this->_XMLWriter->startElement('GivenName');
        $this->_XMLWriter->text($this->_kundenDaten['firstname']);
        $this->_XMLWriter->endElement();

        $this->_XMLWriter->startElement('SurName');
        $this->_XMLWriter->text($this->_kundenDaten['lastname']);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->endElement();

        $this->_XMLWriter->startElement('Telephone');
        $this->_XMLWriter->writeAttribute('PhoneNumber', $this->_kundenDaten['phonenumber']);
        $this->_XMLWriter->endElement();

        $this->_XMLWriter->startElement('Email');
        $this->_XMLWriter->text($this->_kundenDaten['email']);
        $this->_XMLWriter->endElement();

        $this->_XMLWriter->startElement('Address');
        $this->_XMLWriter->startElement('StreetNumbr');
        $this->_XMLWriter->text($this->_kundenDaten['street']);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->startElement('CityName');
        $this->_XMLWriter->text($this->_kundenDaten['city']);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->startElement('PostalCode');
        $this->_XMLWriter->text($this->_kundenDaten['zip']);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->startElement('CountryName');
        $countryName = nook_Tool::findCountryName($this->_kundenDaten['country']);
        $this->_XMLWriter->text($countryName);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->startElement('CompanyName');
        $this->_XMLWriter->text($this->_kundenDaten['company']);
        $this->_XMLWriter->endElement();
        $this->_XMLWriter->endElement();

        $this->_XMLblock = $this->_XMLWriter->outputMemory(true);

        return $this;
    }

    /**
     * Speichert den XML - Block des Kunden
     * Löscht alten XML - Block des Kunden.
     *
     * @return Front_Model_WarenkorbPersonalDataXML
     */
    private function _saveXmlBlock()
    {
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;

        $sql = "delete from tbl_xml_kundendaten where kunde_id = " . $this->_kundenId;
        $db->query($sql);

        $insert = array();
        $insert['kunde_id'] = $this->_kundenId;
        $insert['block'] = $this->_XMLblock;
        $db->insert('tbl_xml_kundendaten', $insert);

        return $this;
    }

    /**
     * prüft ob der XML - Block des Kunden
     * bereits vorhanden ist
     *
     * false = nicht vorhanden
     * true = vorhanden
     *
     * @param $__kundeId
     */
    public function checkExistPersonalDataXml($__kundeId)
    {
        $blockVorhanden = false;

        $sql = "SELECT COUNT(id) AS anzahl FROM tbl_xml_kundendaten WHERE tbl_xml_kundendaten.kunde_id = " . $__kundeId;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_groups;
        $row = $db->fetchRow($sql);

        if ($row['anzahl'] == 1) {
            $blockVorhanden = true;
        } elseif ($row['anzahl'] > 1) {
            throw new nook_Exception($this->_error_kundendaten_mehrfach_vorhanden);
        }

        return $blockVorhanden;
    }

}

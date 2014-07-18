<?php
/**
* Schreibt den XML Block einer Programmbuchung
*
* + erstellt den Datenbankzugriff
* + setzt die Rechnungsposition
* + Übernimmt die ID der Programmbuchungstabelle
* + Legt die Anzeigesprache fest
* + Setzt die Buchungsnummer ID
* + Startet die Verarbeitung des XML Blockes
* + Wenn ein Update auftritt wird der
* + Bestimmt die Preise der Preisvariante
* + Speichert XML in eine Datei
* + Holt die Beschreibung des Programmes entsprechend der Anzeigesprache
* + Gibt den gespeicherten Programmdatensatz aus
* + Bestimmt den Treffpunkt an hand der
* + Bestimmt die Sprache in deutsch
* + Baut den XML - Block
* + Bestimmt die Basisdaten des Programmes
* + Findet den Stadtnamen entsprchend der Stadt ID
* + Bestimmt die Kunden-ID und die
* + Überprüft das vorhandensein von Manager-
* + Gibt eine Position einer Buchung zurück.
* + Gibt eine Einzelposition einer Buchung zurück.
*
* @date 30.59.2013
* @file ProgrammdetailXml.php
* @package front
* @subpackage model
*/
class Front_Model_ProgrammdetailXml {

    // Daten sind unvollständig
    private $_error_daten_unvollstaendig = 660;
    private $_error_anzahl_datensaetze_stimmt_nicht = 662;

    // Staus / Zustände
    private $_condition_programm_liegt_im_warenkorb = 1;
    // Buchungsbereich Programmanbieter
    private $_condition_programmanbieter = 1;
    // keine Rechnungsnummer
    private $_condition_keine_rechnungsnummer = 0;

    private $_db_front = null;

    private $_programmbuchungId = null;
    private $_buchungsnummerId = null;

    private $_XmlWriter = null;
    private $_XmlBlock = null;

    private $_data = array();

    private $_anzeigeSprache;

    private $_rechnungsPosition = null; // einzelne Position einer Rechnung / Warenkorb

    private $_programmVarianten = array(); // gebuchte Programmvarianten eines Programmes

    private $_tabelleKunde = null;
    private $_tabelleBuchungsnummer = null;
    private $_tabelleProgrammbuchung = null;
    private $_tabelleProgrammbeschreibung = null;
    private $_tabelleCity = null;
    private $_tabelleXmlBuchung = null;
    private $_tabelleProgrammePreisvarianten = null;
    private $_viewProgrammdetailBasisdaten = null;

    /**
     * erstellt den Datenbankzugriff
     *
     */
    public function __construct(){

        // Datenbank
        $this->_db_front = Zend_Registry::get('front');

        // XML Reader
        $this->_XmlWriter = new XMLWriter();

        /** @var $_tabelleKunde Application_Model_DbTable_adressen */
        $this->_tabelleKunde = new Application_Model_DbTable_adressen(array('db' => 'front'));

        /** @var $_tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array('db' => 'front'));

        /** @var $_tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $this->_tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array('db' => 'front'));

        /** @var $_tabelleBjl Application_Model_DbTable_programmbeschreibung */
        $this->_tabelleProgrammbeschreibung = new Application_Model_DbTable_programmbeschreibung(array('db' => 'front'));

        /** @var $_viewProgrammdetailBasisdaten Application_Model_DbTable_viewProgrammdetailBasisdatenProgramm */
        $this->_viewProgrammdetailBasisdaten = new Application_Model_DbTable_viewProgrammdetailBasisdatenProgramm(array('db' => 'front'));

        /** @var $_tabelleCity Application_Model_DbTable_aoCity */
        $this->_tabelleCity = new Application_Model_DbTable_aoCity(array('db' => 'front'));

        /** @var $_tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung(array('db' => 'front'));

        /** @var $_tabelleProgrammePreisvarianten Application_Model_DbTable_preise */
        $this->_tabelleProgrammePreisvarianten = new Application_Model_DbTable_preise(array('db' => 'front'));

        return;
    }

    /**
     * setzt die Rechnungsposition
     * entsprechend der ID in der Tabelle 'xml_buchung'.
     * Wird genutzt für update der Einzelbuchung.
     *
     * @param $__rechnungsPosition
     * @return
     */
    public function setRechnungsPosition($__rechnungsPosition){
        $this->_rechnungsPosition = $__rechnungsPosition;

        return $this;
    }

    /**
     * Übernimmt die ID der Programmbuchungstabelle
     *
     * @param $__programmId
     * @return
     */
    public function setProgrammbuchungId($__programmbuchungId){
        $this->_programmbuchungId = $__programmbuchungId;

        return $this;
    }

    /**
     * Legt die Anzeigesprache fest
     *
     * + Standardsprache ist 'de', deutsch
     *
     * @param $__sprache
     * @return
     */
    public function setAnzeigesprache($__sprache = 'de'){
        if($__sprache == 'de')
            $this->_anzeigeSprache = 1;
        else
            $this->_anzeigeSprache = 2;

        return $this;
    }

    /**
     * Setzt die Buchungsnummer ID
     *
     * @param $__buchungsnummerId
     * @return
     */
    public function setBuchungsnummerId($__buchungsnummerId){
        $this->_buchungsnummerId = $__buchungsnummerId;

        return $this;
    }

    /**
     * Startet die Verarbeitung des XML Blockes
     *
     * @return void
     */
    public function startSaveXmlBuchungsdatenProgramm(){

        $this->_bestimmeKunde();
        $this->_getProgrammbuchungDatensatz();
        $this->_getBeschreibungProgramm();
        $this->_bestimmeBasisDatenProgramm();
        $this->_bestimmeDatenDerProgrammvariante();

        for($i = 0; $i < count($this->_programmVarianten); $i++){
            $this->_buildXml($this->_programmVarianten[$i]);
            $this->_loescheAltenXmlDatensatz($this->_programmVarianten[$i]);
            $this->_saveData($this->_programmVarianten[$i]);
        }

        return;
    }

    /**
     * Wenn ein Update auftritt wird der
     * 'alte' XML Block gelöscht
     * und der neue Block eingetragen.
     * Es werden alle Blöcke gelöscht
     *
     * @param $__programmVariante
     * @return void
     */
    private function _loescheAltenXmlDatensatz($__programmVariante){

        $where = "buchungstabelle_id = ".$__programmVariante['BuchungstabelleId']." and buchungsnummer_id = ".$__programmVariante['BuchungsnummerId']." and bereich = ".$this->_condition_programmanbieter;
        $this->_tabelleXmlBuchung->delete($where);

        return;
    }

    /**
     * Bestimmt die Preise der Preisvariante
     *
     * @return
     */
    private function _bestimmeDatenDerProgrammvariante(){
        $tabelleProgrammePreisvarianten = $this->_tabelleProgrammePreisvarianten;

        for($i = 0; $i < count($this->_programmVarianten); $i++){
            $result = $tabelleProgrammePreisvarianten->find($this->_programmVarianten[$i]['PreisvariantenId']);
            $rowPreisvariante = $result->toArray();

            $this->_programmVarianten[$i]['preisvariante'] = $rowPreisvariante[0]['preisvariante_de'];
            $this->_programmVarianten[$i]['einkaufspreis'] = $rowPreisvariante[0]['einkaufspreis'];
            $this->_programmVarianten[$i]['verkaufspreis'] = $rowPreisvariante[0]['verkaufspreis'];
            $this->_programmVarianten[$i]['mwst'] = $rowPreisvariante[0]['mwst'];
        }

        return;
    }

    /**
     * Speichert XML in eine Datei
     *
     * @return void
     */
    private function _saveXmlToFile(){

        $fp = fopen('programmXml', 'w');
        fputs($fp, $this->_XmlBlock);
        fclose($fp);

    }

    /**
     * Holt die Beschreibung des Programmes entsprechend der Anzeigesprache
     *
     *
     * @return
     */
    private function _getBeschreibungProgramm()
    {
        if( empty($this->_programmVarianten[0]['BuchungstabelleId']) )
            throw new nook_Exception('ID der Preisvariante in Buchungstabelle leer');
        if(empty($this->_anzeigeSprache))
            throw new nook_Exception('ID Anzeigesprache unbekannt');

        $tabelleProgrammbeschreibung = $this->_tabelleProgrammbeschreibung;
        
        $columns = array(
            "txt as Programmbeschreibung",
            "progname as Programmname"
        );

        /** @var $select Zend_Db_Table_Select */
        $buchungsTabelleId = $this->_programmVarianten[0]['ProgrammId'];

        $select = $tabelleProgrammbeschreibung->select();
        $select
            ->from($tabelleProgrammbeschreibung, $columns)
            ->where("programmdetail_id = ".$buchungsTabelleId)
            ->where("sprache = ".$this->_anzeigeSprache);

        $rows = $tabelleProgrammbeschreibung->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_anzahl_datensaetze_stimmt_nicht);

        $this->_data['Programmbeschreibung'] = $rows[0]['Programmbeschreibung'];
        $this->_data['Programmname'] = $rows[0]['Programmname'];

        // Programmbild
        $this->_findProgrammBild();
        
        return;
    }


    private function _findProgrammBild(){
        if(file_exists(ABSOLUTE_PATH."/images/program/midi/".$this->_programmbuchungId.".jpg"))
            $this->_data['Programmbild'] = ABSOLUTE_PATH."/images/program/midi/".$this->_programmbuchungId.".jpg";
        else
            $this->_data['Programmbild'] = ABSOLUTE_PATH."/images/program/midi/standard.jpg";

        return;
    }

   

    /**
     * Gibt den gespeicherten Programmdatensatz aus
     * der Tabelle 'programmbuchung' zurück.
     *
     * @return array
     */
    private function _getProgrammbuchungDatensatz(){

        if(empty($this->_buchungsnummerId))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $tabelleProgrammbuchung = $this->_tabelleProgrammbuchung;

        $columns = array(
            "id AS BuchungstabelleId",
            "buchungsnummer_id as BuchungsnummerId",
            "programmdetails_id AS ProgrammId",
            "tbl_programme_preisvarianten_id AS PreisvariantenId",
            "anzahl",
            "datum"
        );

        $select = $tabelleProgrammbuchung->select();
        $select->from($tabelleProgrammbuchung, $columns);
        $select->where("buchungsnummer_id = ".$this->_buchungsnummerId);

        $gebuchteProgrammvarianten = $tabelleProgrammbuchung->fetchAll($select)->toArray();
        $this->_programmVarianten = $gebuchteProgrammvarianten;

        return;
    }

    /**
     * Bestimmt den Treffpunkt an hand der
     * TreffpunktId
     *
     * @return void
     */
    private function _bestimmeTreffpunkt(){
        $sql = "select treffpunkt from tbl_treffpunkt where id = ".$this->_data['TreffpunktId'];
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $this->_data['TreffpunktText'] = $db->fetchOne($sql);

        return;
    }

    /**
     * Bestimmt die Sprache in deutsch
     * entsprechend der Sprache ID
     *
     * @return
     */
    private function _bestimmeSprache(){
        if(!$this->_anzeigeSprache)
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $sql = "select id, de from tbl_prog_sprache where id = ".$this->_anzeigeSprache;
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $sprache = $db->fetchRow($sql);
        $this->_data['Sprache'] = $sprache['de'];
        $this->_data['SpracheId'] = $sprache['id'];

        return;
    }

    /**
     * Baut den XML - Block
     *
     * @return void
     */
    private function _buildXml(array $__programmVariante){
        $version = '1.0';
        $encoding = 'UTF-8';

        $this->_XmlWriter->openMemory();
        $this->_XmlWriter->setIndent(TRUE);
        $this->_XmlWriter->startDocument($version, $encoding);

        $this->_XmlWriter->startElement('Programmbuchung'); // Beginn Programmbuchung
        $this->_XmlWriter->writeAttribute('BuchungstabelleId', $__programmVariante['BuchungstabelleId']);
        $this->_XmlWriter->writeAttribute('Angelegt', time("Y-m-d H:i:s"));
        
        $this->_XmlWriter->writeAttribute('Buchungstyp', $this->_condition_programmanbieter);
        $this->_XmlWriter->writeAttribute('Buchungsnummer', $this->_buchungsnummerId);

            // Buchungsbereich
            $this->_XmlWriter->startElement('Buchungsbereich');
            $this->_XmlWriter->text($this->_condition_programmanbieter);
            $this->_XmlWriter->endElement();

            // Buchungsnummer
            $this->_XmlWriter->startElement('Buchungsnummer');
            $this->_XmlWriter->text($this->_buchungsnummerId);
            $this->_XmlWriter->endElement();

            // Rechnungsnummer
            $this->_XmlWriter->startElement('Rechnungsnummer');
            $this->_XmlWriter->text(0);
            $this->_XmlWriter->endElement();

            // Session ID
            $this->_XmlWriter->startElement('SessionId');
            $this->_XmlWriter->text($this->_data['SessionId']);
            $this->_XmlWriter->endElement();

            // Kunden Id
            $this->_XmlWriter->startElement('KundeId');
            if(empty($this->_data['KundeId']))
                $this->_XmlWriter->text(0);
            else
                $this->_XmlWriter->text($this->_data['KundeId']);
            $this->_XmlWriter->endElement();

            // Einkaufspreis
            $this->_XmlWriter->startElement('Einkaufspreis');
            $this->_XmlWriter->text($__programmVariante['einkaufspreis']);
            $this->_XmlWriter->endElement();

            // Verkaufspreis
            $this->_XmlWriter->startElement('Verkaufspreis');
            $this->_XmlWriter->text($__programmVariante['verkaufspreis']);
            $this->_XmlWriter->endElement();

            // Mehrwertsteuer
            $this->_XmlWriter->startElement('Mehrwertsteuer');
            $this->_XmlWriter->text($__programmVariante['mwst']);
            $this->_XmlWriter->endElement();

            // Anzahl der Preisvarianten
            $this->_XmlWriter->startElement('Anzahl');
            $this->_XmlWriter->text($__programmVariante['anzahl']);
            $this->_XmlWriter->endElement();

            $gerechneterVerkaufspreis = $__programmVariante['verkaufspreis'] * $__programmVariante['anzahl'];
            $gerechneterVerkaufspreis = number_format($gerechneterVerkaufspreis, 2, ',', '');

            // gerechnete Verkaufspreis
            $this->_XmlWriter->startElement('GerechneteVerkaufspreis');
            $this->_XmlWriter->text($gerechneterVerkaufspreis);
            $this->_XmlWriter->endElement();

            // Buchungsfrist
            $this->_XmlWriter->startElement('Buchungsfrist');
            $this->_XmlWriter->text($this->_data['Buchungsfrist']);
            $this->_XmlWriter->endElement();

            // Stornofrist
            $this->_XmlWriter->startElement('Stornofrist');
            $this->_XmlWriter->text($this->_data['Stornofrist']);
            $this->_XmlWriter->endElement();

            // Programm ID
            $this->_XmlWriter->startElement('ProgrammId');
            $this->_XmlWriter->text($__programmVariante['ProgrammId']);
            $this->_XmlWriter->endElement();

            // Programmbeschreibung entsprechend der Anzeigesprache
            $this->_XmlWriter->startElement('Beschreibung');

                // Programmname
                $this->_XmlWriter->startElement('Programmname');
                $this->_XmlWriter->text($this->_data['Programmname']);
                $this->_XmlWriter->endElement();

                // Preisvariante
                $this->_XmlWriter->startElement('Preisvariante');
                $this->_XmlWriter->text($__programmVariante['preisvariante']);
                $this->_XmlWriter->endElement();

                // Beschreibungstext
                $this->_XmlWriter->startElement('Programmbeschreibung');
                $this->_XmlWriter->text($this->_data['Programmbeschreibung']);
                $this->_XmlWriter->endElement();

                // Bild
                $this->_XmlWriter->startElement('Programmbild');
                $this->_XmlWriter->text($this->_data['Programmbild']);
                $this->_XmlWriter->endElement();


            $this->_XmlWriter->endElement(); // Ende der Beschreibung

            // Firma
            $this->_XmlWriter->startElement('Firma');

                // Firmenname
                $this->_XmlWriter->startElement('Firmenname');
                $this->_XmlWriter->writeAttribute('FirmenId', $this->_data['FirmenId']);
                $this->_XmlWriter->text($this->_data['Firmenname']);
                $this->_XmlWriter->endElement();

                // Anrede
                $this->_XmlWriter->startElement('AnsprechpartnerAnrede');
                $this->_XmlWriter->text($this->_data['AnsprechpartnerAnrede']);
                $this->_XmlWriter->endElement();

                // Name
                $this->_XmlWriter->startElement('AnsprechpartnerName');
                $this->_XmlWriter->text($this->_data['AnsprechpartnerName']);
                $this->_XmlWriter->endElement();

                // Stadt
                $this->_XmlWriter->startElement('FirmaStadt');
                $this->_XmlWriter->text($this->_data['FirmaStadt']);
                $this->_XmlWriter->endElement();

                // Strasse
                $this->_XmlWriter->startElement('FirmaStrasse');
                $this->_XmlWriter->text($this->_data['FirmaStrasse']);
                $this->_XmlWriter->endElement();

                // Hausnummer
                $this->_XmlWriter->startElement('FirmaHausnummer');
                $this->_XmlWriter->text($this->_data['FirmaHausnummer']);
                $this->_XmlWriter->endElement();

                // Mailadresse
                $this->_XmlWriter->startElement('FirmaMail');
                $this->_XmlWriter->text($this->_data['FirmaMail']);
                $this->_XmlWriter->endElement();

            $this->_XmlWriter->endElement(); // Ende der Firma


            // Daten zur Durchfuehrung
            $this->_XmlWriter->startElement('Durchfuehrung');

                // Datum
                $this->_XmlWriter->startElement('Datum');
                $this->_XmlWriter->text($__programmVariante['datum']);
                $this->_XmlWriter->endElement();

                // Stadt
                $this->_XmlWriter->startElement('Stadt');
                $this->_XmlWriter->writeAttribute('StadtId', $this->_data['StadtId']);
                $this->_XmlWriter->text($this->_data['Stadt']);
                $this->_XmlWriter->endElement();

                // Zeit
//                $this->_XmlWriter->startElement('Zeit');
//                $this->_XmlWriter->text($this->_data['Zeit']);
//                $this->_XmlWriter->endElement();

                // Optional
//                if( (!array_key_exists('TreffpunktText', $this->_data)) or (empty($this->_data['TreffpunktText'])) )
//                    $this->_data['TreffpunktText'] = '';

                // Treffpunkt Text
//                $this->_XmlWriter->startElement('TreffpunktText');
//                $this->_XmlWriter->text($this->_data['TreffpunktText']);
//                $this->_XmlWriter->endElement();

                // Personenanzahl
//                $this->_XmlWriter->startElement('Personenanzahl');
//                $this->_XmlWriter->text($this->_data['Personenanzahl']);
//                $this->_XmlWriter->endElement();

                // Programmsprache
//                $this->_XmlWriter->startElement('Sprache');
//                $this->_XmlWriter->writeAttribute('SpracheId', $this->_data['SpracheId']);
//                $this->_XmlWriter->text($this->_data['Sprache']);
//                $this->_XmlWriter->endElement();

            $this->_XmlWriter->endElement(); // Ende der Durchführung

            // Optionale Vereinbarungen / Manager
//            $this->_XmlWriter->startElement('Optionen');
//
//                // Optional
//                if( (!array_key_exists('Information', $this->_data)) or (empty($this->_data['Information'])) )
//                    $this->_data['Information'] = '';
//
//                // Zusatzinformation
//                $this->_XmlWriter->startElement('Information');
//                $this->_XmlWriter->text($this->_data['Information']);
//                $this->_XmlWriter->endElement();
//
//            $this->_XmlWriter->endElement(); // Ende Optionen

            // Informationen zur Bearbeitung / Global
//            $this->_XmlWriter->startElement('Global');
//
//                // Bearbeitungsstand
//                $this->_XmlWriter->startElement('Status');
//                $this->_XmlWriter->text($this->_data['Status']);
//                $this->_XmlWriter->writeAttribute('StatusId', $this->_data['StatusId']);
//                $this->_XmlWriter->endElement();
//
//
//            $this->_XmlWriter->endElement(); // Ende globale Informationen

        $this->_XmlWriter->endElement(); // Ende Programmbuchung

        $this->_XmlWriter->endDocument();

        // speichern
        $this->_XmlBlock = $this->_XmlWriter->outputMemory(true);
    }

    /**
     * Bestimmt die Basisdaten des Programmes
     *
     * @return void
     */
    private function _bestimmeBasisDatenProgramm(){
        
        if(empty( $this->_programmbuchungId ) and empty($this->_programmVarianten[0]['ProgrammId']))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        if(empty($this->_programmbuchungId))
            $programmBuchungId = $this->_programmVarianten[0]['ProgrammId'];
        else
            $programmBuchungId = $this->_programmbuchungId;

        /** @var $select Zend_Db_Table_Select */
        $select = $this->_viewProgrammdetailBasisdaten->select();
        $select->where('id = '.$programmBuchungId);

        $basisdaten = $this->_viewProgrammdetailBasisdaten->fetchRow($select)->toArray();
        $this->_data = array_merge($this->_data, $basisdaten);
        
        // Stadtname
        $this->_findStadtname();

        // Status
        $this->_data['StatusId'] = $this->_condition_programm_liegt_im_warenkorb;
        
        return;
    }

    /**
     * Findet den Stadtnamen entsprchend der Stadt ID
     *
     * @return void
     */
    private function _findStadtname(){

        $tabelleCity = $this->_tabelleCity;
        $result = $tabelleCity->find($this->_data['StadtId']);
        $city = $result->toArray();
        $this->_data['Stadt'] = $city[0]['AO_City'];

        return;
    }

    /**
     * Bestimmt die Kunden-ID und die
     * Session-ID
     * 
     * @return void
     */
    private function _bestimmeKunde(){

        $tabelleBuchungsnummer = $this->_tabelleBuchungsnummer;
        $kundenUndSessionId = $tabelleBuchungsnummer
                                ->find($this->_buchungsnummerId)
                                ->toArray();

        $this->_data['SessionId'] = $kundenUndSessionId[0]['session_id'];
        $this->_data['KundeId'] = $kundenUndSessionId[0]['kunden_id'];

        return;
    }

    /**
     * Überprüft das vorhandensein von Manager-
     * variablen im $programmDatensatz und erstellt
     * die Teile des XMl Buchungsblock
     *
     * @return void
     */
    private function _saveData($__preisvariante){

        $insert = array(
            'block' => $this->_XmlBlock,
            'buchungsnummer_id' => $this->_buchungsnummerId,
            'buchungstabelle_id' => $__preisvariante['BuchungstabelleId'],
            'status' => $this->_condition_programm_liegt_im_warenkorb,
            'bereich' => $this->_condition_programmanbieter
        );

        $this->_tabelleXmlBuchung->insert($insert);

        return;
    }

    /**
     * Gibt eine Position einer Buchung zurück.
     * Buchung wird über die ID der Tabelle 'xml_buchung'
     * ausgewählt.
     *
     * @return array|mixed
     */
    public function getBuchungsArray(){
        // Dummy
        // $this->_rechnungsPosition = 9;

        $xmlArray = array();

        if(empty($this->_rechnungsPosition))
            throw new nook_Exception($this->_error_daten_unvollstaendig);


        $sql = "select block from tbl_xml_buchung where id = ".$this->_rechnungsPosition;
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $buchungsXml = $db->fetchOne($sql);

        // Umwandlung XMl in Array
        $xml = simplexml_load_string($buchungsXml);
        $json = json_encode($xml);
        $xmlArray = json_decode($json,TRUE);

        return $xmlArray;
    }

    /**
     * Gibt eine Einzelposition einer Buchung zurück.
     * Auswahl der Position erfolgt über die ID
     * der Tabelle 'xml_buchung'.
     *
     * @return string
     */
    public function getBuchungsXml(){
        // Dummy
        // $this->_rechnungsPosition = 9;

        if(empty($this->_rechnungsPosition))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $sql = "select block from tbl_xml_buchung where id = ".$this->_rechnungsPosition;
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $buchungsXml = $db->fetchOne($sql);

        return $buchungsXml;
    }


    public function getBuchungsVariable($__variablenName){
        // Dummy
        // $this->_rechnungsPosition = 9;

        if(empty($this->_rechnungsPosition))
            throw new nook_Exception($this->_error_daten_unvollstaendig);

        $sql = "select block from tbl_xml_buchung where id = ".$this->_rechnungsPosition;
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $buchungsXml = $db->fetchOne($sql);


        $sxml = simplexml_load_string($buchungsXml);
        $valueXmlVaraiable = $sxml->xpath("/".$__variablenName);

        if(!empty($valueXmlVaraiable))
            return $valueXmlVaraiable;
        else
            return false;
    }



}

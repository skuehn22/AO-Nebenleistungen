<?php
/**
 * 19.09.2012
 * Fehlerbereich: 680
 * Erstellt den XML - Block der
 * Buchung der Raten einer Buchung
 *
 */

class Front_Model_BuchungsuebersichtHotelbuchungXML{

    // private $_error_ ... = 680;

    private $_condition_bereich_hotel = 6;
    private $_condition_buchung_liegt_im_warenkorb = 1;
    private $_condition_buchungstyp_zimmerbuchung = 1;

    private $_XMLWriter = null;
    private $_debugModus = false;

    private $_zusammengefassteHotelbuchungen = null;

    private $_buchungsNummerId = null;

    private $_tabelleHotelbuchung = null;
    private $_tabelleBuchungsnummer = null;
    private $_tabelleXmlHotelBuchung = null;
    private $_viewGebuchteRaten = null;
    private $_viewCategoryRateProperty = null;

    public function __construct(){
        /** @var _tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung(array('db' => 'front'));
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer(array('db' => 'front'));
        /** @var _tabelleXmlHotelBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlHotelBuchung = new Application_Model_DbTable_xmlBuchung(array('db' => 'front'));
        /** @var _viewGebuchteRaten Application_Model_DbTable_viewGebuchteRaten */
        $this->_viewGebuchteRaten = new Application_Model_DbTable_viewGebuchteRaten(array('db' => 'front'));
        /** @var _viewCategoryRateProperty Application_Model_DbTable_viewCategoryRateProperty */
        $this->_viewCategoryRateProperty = new Application_Model_DbTable_viewCategoryRateProperty(array('db' => 'hotels'));

        // XML Writer
        $this->_XMLWriter = new XMLWriter();
    }

    /**
     * Speichern der Hotelbuchung als XML in
     * Tabelle 'xml_buchung'
     *
     * @return void
     */
    public function saveXML()
    {
        $this
            ->_findBuchungsNummerId() // findet Buchungsnummer
            ->_loescheXmlBloeckeEinerBuchungsnummer() // loescht die XML - Bloecke der Buchungsnummer
            ->_findHotelbuchungen() // suche nach den Hotelbuchungen
            ->_findCategoryRateProperty() // findet Category und Rate und Property
            ->_berechneTagespreis() // berchne Tagespreis der Rate
            ->_buildXmlFuerEinzelneRate(); // erzeugen XML - Block und speichern

        return;
    }

    /**
     * setzen des Debug Modus
     *
     * @param bool $__debug
     * @return Front_Model_BuchungsuebersichtHotelbuchungXML
     */
    public function setDebugModus($__debug = false){
        $this->_debugModus = $__debug;

        return $this;
    }

    /**
     * Löscht die XML - Blöcke in der Tabelle
     * 'tbl_xml_buchung'
     *
     * @return Front_Model_BuchungsuebersichtHotelbuchungXML
     */
    private function _loescheXmlBloeckeEinerBuchungsnummer(){

        $where = array(
            "buchungsnummer_id = ".$this->_buchungsNummerId,
            "bereich = ".$this->_condition_bereich_hotel,
            "buchungstyp = ".$this->_condition_buchungstyp_zimmerbuchung
        );

        $this->_tabelleXmlHotelBuchung->delete($where);

        return $this;
    }

    /**
     * Findet die Hotelbuchungen einer Buchungsnummer
     *
     * @return Front_Model_BuchungsuebersichtHotelbuchungXML
     */
    private function _findHotelbuchungen(){

        $select = $this->_viewGebuchteRaten->select();
        $select->where("buchungsnummer_id = ".$this->_buchungsNummerId);
        $this->_zusammengefassteHotelbuchungen = $this->_viewGebuchteRaten->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Bestimmt die aktuelle Buchungsnummer
     * mittels der Session ID
     *
     * @return Front_Model_BuchungsuebersichtHotelbuchungXML
     */
    private function _findBuchungsNummerId(){
        $sessionId = Zend_Session::getId();
        $select = $this->_tabelleBuchungsnummer->select();
        $select->where("session_id = '".$sessionId."'");
        $row = $this->_tabelleBuchungsnummer->fetchRow($select)->toArray();
        $this->_buchungsNummerId = $row['id'];

        return $this;
    }

    /**
     * Berechnet den Tagespreis 'tagespreis' in Abhängigkeit.
     * Ist der Preis ein Personenpreis
     * oder
     * ein Zimmerpreis.
     * Es wird der mittlere Preis
     * für den Zeitraum angenommen.
     *
     * @return Front_Model_WarenkorbHotelbuchungXML
     */
    private function _berechneTagespreis(){
        for($i=0; $i < count($this->_zusammengefassteHotelbuchungen); $i++){
            // Übernachtungen
            $uebernachtungen = array();

            // Start Datum
            $startDatum = new DateTime($this->_zusammengefassteHotelbuchungen[$i]['startDate']);

            // Anzahl Nächte
            $anzahlNaechte = $this->_zusammengefassteHotelbuchungen[$i]['nights'];

            // Gesamtsumme
            $total = 0;

            // abarbeiten gebuchter Zeitraum
            for($j=0; $j < $anzahlNaechte; $j++){

                /*** Datumsbereich der Buchung ***/
                // weiterer Tag
                if($j > 0){
                    $periode = new DateInterval('P1D');
                    $neuesDatum = $startDatum->add($periode);
                    $uebernachtungen[$j]['datum'] = $neuesDatum->format('Y-m-d');
                }
                // Anreisetag
                else
                    $uebernachtungen[$j]['datum'] = $startDatum->format('Y-m-d');


                /*** Tagespreise der Rate entsprechend Kalendertag ***/
                $datum = $uebernachtungen[$j]['datum'];
                $hotelCode = $this->_zusammengefassteHotelbuchungen[$i]['hotelCode'];
                $rateCode = $this->_zusammengefassteHotelbuchungen[$i]['rateCode'];

                // ermitteln Tagespreis
                $toolHotel = new nook_ToolHotel();
                $tagespreis = $toolHotel
                    ->setDatum($datum)
                    ->setHotelCode($hotelCode)
                    ->setRateCode($rateCode)
                    ->ermittleTagespreisEinerRate()
                    ->getTagespreisRate();


                // Personenpreis
                if(!empty($this->_zusammengefassteHotelbuchungen[$i]['personPrice']))
                    $uebernachtungen[$j]['tagespreis'] = $tagespreis * $this->_zusammengefassteHotelbuchungen[$i]['personNumbers'];
                // Zimmerpreis
                else
                    $uebernachtungen[$j]['tagespreis'] = $tagespreis * $this->_zusammengefassteHotelbuchungen[$i]['roomNumbers'];

                $uebernachtungen[$j]['tagespreis'] = number_format($uebernachtungen[$j]['tagespreis'],2,'.','');

                // Gesamtpreis der Rate
                $total += $uebernachtungen[$j]['tagespreis'];


            }

            // Rate der einzelnen Tage
            $this->_zusammengefassteHotelbuchungen[$i]['tage'] = $uebernachtungen;

            // Totalpreis der Rate
            $total = number_format($total, 2,'.','');
            $this->_zusammengefassteHotelbuchungen[$i]['total'] = $total;

            // Abreisetag
            $startDatum->add($periode);
            $this->_zusammengefassteHotelbuchungen[$i]['endDate'] = $startDatum->format('Y-m-d');
        } // Ende Rate

        return $this;
    }

    /**
     * Findet die Zusatzinformationen der Rate
     *
     * @return void
     */
    private function _findCategoryRateProperty(){

        for($i=0; $i<count($this->_zusammengefassteHotelbuchungen); $i++){
            $select = $this->_viewCategoryRateProperty->select();
            $select->where("id = ".$this->_zusammengefassteHotelbuchungen[$i]['otaRatesConfigId']);
            $categoryRateProperty = $this->_viewCategoryRateProperty->fetchRow($select)->toArray();

            unset($categoryRateProperty['id']);

            $this->_zusammengefassteHotelbuchungen[$i] = array_merge($this->_zusammengefassteHotelbuchungen[$i], $categoryRateProperty);
        }

        return $this;
    }

    /**
     * Erstellt den XML Block für die
     * gebuchten Raten
     *
     */
    private function _buildXmlFuerEinzelneRate(){

        $ausgabeXml = '';

        for($i=0; $i < count($this->_zusammengefassteHotelbuchungen); $i++){
            $this->_XMLWriter->openMemory();
            $this->_XMLWriter->setIndent(4);
            $this->_XMLWriter->startElement('RoomStay');
                $this->_XMLWriter->startElement('RoomType');
                    $this->_XMLWriter->writeAttribute('IsRoom','true');
                    $this->_XMLWriter->writeAttribute('NumberOfUnits', $this->_zusammengefassteHotelbuchungen[$i]['roomNumbers']);
                    $this->_XMLWriter->writeAttribute('RoomTypeCode', $this->_zusammengefassteHotelbuchungen[$i]['categoryCode']);

                    $this->_XMLWriter->startElement('RoomDescription');
                        $this->_XMLWriter->writeAttribute('Name', $this->_zusammengefassteHotelbuchungen[$i]['RoomDescription']);
                    $this->_XMLWriter->endElement(); // RoomDescription

                $this->_XMLWriter->endElement(); // RoomType

                $this->_XMLWriter->startElement('RoomRates');
                    $this->_XMLWriter->startElement('RoomRate');
                        $this->_XMLWriter->writeAttribute('NumberOfUnits', $this->_zusammengefassteHotelbuchungen[$i]['roomNumbers']);
                        $this->_XMLWriter->writeAttribute('RoomTypeCode', $this->_zusammengefassteHotelbuchungen[$i]['categoryCode']);
                        $this->_XMLWriter->writeAttribute('RatePlanCode', $this->_zusammengefassteHotelbuchungen[$i]['rateCode']);
                            $this->_XMLWriter->startElement('Rates');

                                for($j=0; $j < count($this->_zusammengefassteHotelbuchungen[$i]['tage']); $j++){
                                    $this->_XMLWriter->startElement('Rate');
                                        $this->_XMLWriter->writeAttribute('EffectiveDate', $this->_zusammengefassteHotelbuchungen[$i]['tage'][$j]['datum']);
                                        $this->_XMLWriter->startElement('Base');
                                            $this->_XMLWriter->writeAttribute('CurrencyCode', 'EUR');
                                            $this->_XMLWriter->writeAttribute('AmountAfterTax', $this->_zusammengefassteHotelbuchungen[$i]['tage'][$j]['tagespreis']);
                                        $this->_XMLWriter->endElement();
                                    $this->_XMLWriter->endElement(); // Rate
                                }

                            $this->_XMLWriter->endElement(); // Rates

                            $this->_XMLWriter->startElement('Total');
                                $this->_XMLWriter->writeAttribute('AdditionalFeesExcludeIndicator', 'false');
                                $this->_XMLWriter->writeAttribute('CurrencyCode', 'EUR');
                                $this->_XMLWriter->writeAttribute('AmountAfterTax', $this->_zusammengefassteHotelbuchungen[$i]['total']);
                            $this->_XMLWriter->endElement(); // Total

                    $this->_XMLWriter->endElement(); // RoomRate
                $this->_XMLWriter->endElement(); // RoomRates

                $this->_XMLWriter->startElement('GuestCounts');
                    $this->_XMLWriter->writeAttribute('IsPerRoom', 'false');
                    $this->_XMLWriter->startElement('GuestCount');
                        $this->_XMLWriter->writeAttribute('AgeQualifyingCode', '10');
                        $this->_XMLWriter->writeAttribute('Count', $this->_zusammengefassteHotelbuchungen[$i]['personNumbers']);
                    $this->_XMLWriter->endElement(); // GuestCount
                $this->_XMLWriter->endElement(); // GuestCounts

                $this->_XMLWriter->startElement('TimeSpan');
                    $this->_XMLWriter->writeAttribute('End', $this->_zusammengefassteHotelbuchungen[$i]['endDate']);
                    $this->_XMLWriter->writeAttribute('Start', $this->_zusammengefassteHotelbuchungen[$i]['startDate']);
                $this->_XMLWriter->endElement(); // TimeSpan

            $this->_XMLWriter->endElement(); // RoomStay

            $xmlData = $this->_XMLWriter->outputMemory(true);

            // speichern Hotelbuchung in Tabelle 'xml_buchung'
            // + Buchungsnummer
            // + XML Block
            // + ID der Teilrechnung
            $this->_saveHotelbuchungXmlBlock($this->_zusammengefassteHotelbuchungen[$i]['id'], $xmlData, $this->_zusammengefassteHotelbuchungen[$i]['teilrechnung']);

            if($this->_debugModus){
                $ausgabeXml .= $xmlData;
            }
        }

        if($this->_debugModus){
            $fp = fopen('test.xml','w');
            fputs($fp, $ausgabeXml);
            fclose($fp);
        }

        return $this;
    }

    /**
     * Speichert die Hotelbuchung in Tabelle
     * 'xml_buchung'
     *
     * @param $__xmlData
     * @return
     */
    private function _saveHotelbuchungXmlBlock($__buchungstabelleId, $__xmlData, $__teilrechnung){
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');

        $insert = array();
        $insert['block'] = $__xmlData;
        $insert['buchungstabelle_id'] = $__buchungstabelleId;
        $insert['buchungsnummer_id'] = $this->_buchungsNummerId;
        $insert['status'] = $this->_condition_buchung_liegt_im_warenkorb;
        $insert['bereich'] = $this->_condition_bereich_hotel;
        $insert['teilrechnungen_id'] = $__teilrechnung;

        $db->insert('tbl_xml_buchung', $insert);

        return;
    }
} // end class
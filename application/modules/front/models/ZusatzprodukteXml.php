<?php
/**
 * Beschreibung der Klasse
 *
 * Speichert die Zusatzprodukte der Hotelbuchung
 * eines Kunden als XML - Block in die Tabelle
 * 'tbl_xml_buchung'
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan.Krauss
 * @date 14.12.12
 * @file ZusatzprodukteXml.php
 */

class Front_Model_ZusatzprodukteXml extends nook_ToolModel implements ArrayAccess{

    protected $_gebuchteZusatzprodukte = array();
    protected $_buchungsNummerId = null;
    protected $_buchungstabelleId = null;
    protected $_produktId = null;

    protected $_hotelId = null;
    protected $_sessionId = null;
    protected $_anzahlUebernachtungen = null;
    protected $_typDatumsZuordnung = null;
    protected $_datumsZuordnung = null;
    protected $_suppName = null;
    protected $_typVerpflegung = 1;

    // XML - Writer
    private $_XmlWriter = null;
    private $_XmlBlock = null;
    protected $_suppTypeCode = null;


    // Konditionen
    private $_condition_zusatzartikel_liegt_im_warenkorb = 1;
    private $_condition_xml_block_status_produktbuchung = 2;
    private $_condition_bereich_hotelbuchung = 6;
    private $_condition_buchungstyp_produktbuchung = 2;
    private $_condition_produkt_typ_je_person_und_uebernachtung = 3; // je Person und Übernachtung
    private $_condition_letztmalig_am_abreisetag = 3;
    private $_condition_erstmalig_am_anreisetag = 2;
    private $_condition_produkt_ist_verpflegung = 2;

    // Fehler
    private $_error_kein_product_code_vorhanden = 1060;
    private $_error_mehr_als_ein_produktdatensatz = 1061;

    // Tabellen / Views
    private $_tabelleXmlBuchung = null;
    private $_tabelleBuchungsnummer = null;
    private $_tabelleProduktbuchung = null;
    private $_tabelleProducts = null;

    public function __construct(){
        /** @var _tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $this->_tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung();
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));
    }

    /**
     * Speichert die Zusatzprodukte
     * in 'tbl_xml_buchung'.
     * Jedes Zusatzprodukt bildet einen XML - Block
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    public function saveXmlZusatzprodukte(){

        try{
            $anzahl = $this
                ->_ermittelnBuchungsnummer() // ermitteln Buchungsnummer
                ->_ermittleZusatzprodukteDerBuchung(); // ermittelt die Zusatzprodukte der Buchung

            // keine Produktbuchung vorhanden
            if($anzahl == 0)
                return;

            $this
                ->_loescheXmlBloecke() // löscht die XML Blöcke der Buchung
                ->_initXmlWriter() // Init XML Writer
                ->_schreibeXmlBloecke(); // schreiben XML Blöcke der Buchung


            return $this;
        }
        catch(nook_Exception $e){

            // Weiterleitung
            throw $e;


        }
    }

    /**
     * Löscht alle XML Blöcke der
     * Produktbuchung einer Buchungsnummer
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _loescheXmlBloecke(){

        $where = array(
            "buchungsnummer_id = ".$this->_buchungsNummerId,
            "buchungstyp = ".$this->_condition_xml_block_status_produktbuchung,
            "bereich = ".$this->_condition_bereich_hotelbuchung
        );

        $this->_tabelleXmlBuchung->delete($where);

        return $this;
    }

    /**
     * speichert den XML Block des Zusatzproduktes
     * in 'tbl_xml_buchung'
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _speichernXmlBlock($__teilrechnungId){

        $insert = array(
            "buchungsnummer_id" => $this->_buchungsNummerId,
            "buchungstabelle_id" => $this->_buchungstabelleId,
            "status" => $this->_condition_zusatzartikel_liegt_im_warenkorb,
            "bereich" => $this->_condition_bereich_hotelbuchung,
            "buchungstyp" => $this->_condition_buchungstyp_produktbuchung,
            "teilrechnungen_id" => $__teilrechnungId,
            "block" => $this->_XmlBlock
        );

        $this->_tabelleXmlBuchung->insert($insert);

        return $this;
    }

    /**
     * Ermittelt die Buchungsnummer
     * der aktuellen Session
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _ermittelnBuchungsnummer(){
        $this->_buchungsNummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();

        return $this;
    }

    /**
     * Ermittelt die gebuchten Produkte
     * einer Buchung
     *
     * @return int
     */
    private function _ermittleZusatzprodukteDerBuchung(){
        $select = $this->_tabelleProduktbuchung->select();
        $select->where('buchungsnummer_id = '.$this->_buchungsNummerId);

        $this->_gebuchteZusatzprodukte = $this->_tabelleProduktbuchung->fetchAll($select)->toArray();

        return count($this->_gebuchteZusatzprodukte);
    }

    /**
     * Schreibt alle XML Blöcke
     * der Zusattzprodukte einer
     * Buchung
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _schreibeXmlBloecke(){

        for($i=0; $i < count( $this->_gebuchteZusatzprodukte); $i++){

            $amountAfterTax = 0;

            /*** ermitteln Zusatzinformationen ***/

            // Produkt ID und ID des Buchungsdatensatzes
            $this->_ermittelnProduktId($this->_gebuchteZusatzprodukte[$i]);
            // SuppTypeCode
            $this->_ermittleSuppTypeCode();
            // Datumstyp
            $this->_ermittelnDatumstypVerpflegungstyp();
            // Beschreibung Supplement
            $this->_ermittleSupplementBeschreibung($this->_gebuchteZusatzprodukte[$i]['products_id']);
            // Anzahl Übernachtungen
            $this->_anzahlUebernachtungen = $this->_gebuchteZusatzprodukte[$i]['uebernachtungen'];



            $version = '1.0';
            $encoding = 'UTF-8';

            $this->_XmlWriter->openMemory();
            $this->_XmlWriter->setIndent(true);
            // $this->_XmlWriter->startDocument($version, $encoding);

            $this->_XmlWriter->startElement('Supplement'); // Beginn Programmbuchung
                $this->_XmlWriter->startElement('SupplementTypes');
                    $this->_XmlWriter->startElement('SupplementType');
                        $this->_XmlWriter->writeAttribute('SuppTypeCode', $this->_suppTypeCode);
                        $this->_XmlWriter->writeAttribute('NumberOfUnits', $this->_gebuchteZusatzprodukte[$i]['anzahl']);

                        // Zsatzprodukt ist Verpflegungstyp
                        if($this->_typVerpflegung == $this->_condition_produkt_ist_verpflegung)
                            $this->_XmlWriter->writeAttribute('Bt','true');

                        $this->_XmlWriter->startElement("SupplementDescription");
                            $this->_XmlWriter->writeAttribute('Name', $this->_suppName);
                            $datum = date("Y-m-d H:i:s");
                            $this->_XmlWriter->writeAttribute('LastModifyDateTime', $datum);
                        $this->_XmlWriter->endElement(); // Ende SupplementDescription

                    $this->_XmlWriter->endElement(); // Ende SupplementType
                $this->_XmlWriter->endElement(); // Ende SupplementTypes

            $this->_XmlWriter->startElement("SupplementRates");

                $this->_XmlWriter->startElement('SupplementRate');
                    $this->_XmlWriter->writeAttribute('SuppTypeCode', $this->_suppTypeCode);
                    $this->_XmlWriter->writeAttribute('NumberOfUnits', $this->_gebuchteZusatzprodukte[$i]['anzahl']);

            $this->_XmlWriter->startElement('Rates');

                    // Zuordnung des Zusatzproduktes zu einem Datumsbereich
                    if( $this->_gebuchteZusatzprodukte[$i]['produktTyp'] == $this->_condition_produkt_typ_je_person_und_uebernachtung ){

                        // Berechnung der Tage Verrechnung Produkt
                        $effektiveTage = $this->_zuordnungDatumZumProdukt($this->_gebuchteZusatzprodukte[$i]['anreisedatum']);

                        // Schleife der Verrechnungstage
                        for($j = 0; $j < count($effektiveTage); $j++){
                            $this->_XmlWriter->startElement('Rate');
                                $this->_XmlWriter->writeAttribute('EffectiveDate', $effektiveTage[$j]);

                                    $this->_XmlWriter->startElement('Base');

                                        // Preisberechnung
                                        $tagespreis = $this->_gebuchteZusatzprodukte[$i]['anzahl'] * $this->_gebuchteZusatzprodukte[$i]['aktuellerProduktPreis'];
                                        $tagespreis = number_format($tagespreis,2,'.','');
                                        $amountAfterTax += $tagespreis;

                                        $this->_XmlWriter->writeAttribute('AmountAfterTax', $tagespreis);
                                        $this->_XmlWriter->writeAttribute('CurrencyCode', "EUR");

                                    $this->_XmlWriter->endElement(); // Ende Base

                            $this->_XmlWriter->endElement(); // Ende Rate
                        }
                    }
                    // keine Datumszuordnung
                    else{
                        $this->_XmlWriter->startElement('Rate');
                            $this->_XmlWriter->writeAttribute('EffectiveDate', $this->_gebuchteZusatzprodukte[$i]['anreisedatum']);

                            $this->_XmlWriter->startElement('Base');

                               // Preisberechnung
                                $tagespreis = $this->_gebuchteZusatzprodukte[$i]['anzahl'] * $this->_gebuchteZusatzprodukte[$i]['aktuellerProduktPreis'];
                                $tagespreis = number_format($tagespreis,2,'.','');
                                $amountAfterTax = $tagespreis;

                               $this->_XmlWriter->writeAttribute('AmountAfterTax', $tagespreis);
                            $this->_XmlWriter->endElement(); // Ende Base

                        $this->_XmlWriter->endElement(); // Ende Rate
                    }
                    $this->_XmlWriter->endElement(); // Ende Rates

                    $amountAfterTax = number_format($amountAfterTax,2,'.','');
                    $this->_XmlWriter->startElement('Total');
                        $this->_XmlWriter->writeAttribute('AmountAfterTax', $amountAfterTax);
                        $this->_XmlWriter->writeAttribute('CurrencyCode', "EUR");
                        $this->_XmlWriter->writeAttribute('AdditionalFeesExcludedIndicator', "false");
                    $this->_XmlWriter->endElement(); // Ende Total

                $this->_XmlWriter->endElement(); // Ende SupplementRate
            $this->_XmlWriter->endElement(); // Ende SupplementRates
            $this->_XmlWriter->endElement(); // Ende Supplement


            // schreiben XML Block
            $this->_XmlBlock = $this->_XmlWriter->outputMemory(true);

            // Übergabe ID der Teilrechnung
            $this->_speichernXmlBlock($this->_gebuchteZusatzprodukte[$i]['teilrechnungen_id']); // speichert XML Block in 'tbl_xml_buchung'

        } // Ende Schleife XML Block

        return $this;
    }

    /**
     * Ermittelt Produkt ID und ID des
     * Buchungsdatensatzes
     *
     * @param $__zusatzProduktEinesHotel
     */
    private function _ermittelnProduktId($__zusatzProduktEinesHotel){

        // ID der Buchungstabelle
        $this->_buchungstabelleId = $__zusatzProduktEinesHotel['id']; // produktbuchung_id
        // Produkt ID
        $this->_produktId = $__zusatzProduktEinesHotel['products_id'];

        return;
    }

    /**
     * Entsprechend der Datumszuordnung werden
     * die effektiven Tage des Produktes berechnet.
     * 'datumszuordnung' = 2 ; beginnend am Anreisetag
     * 'datumszuordnung' = 3 ; letztmalig am Abreisetag
     *
     * @param $__buchungsDatum
     * @return array
     */
    private function _zuordnungDatumZumProdukt($__anreiseDatum){

        $__anreiseDatum = trim($__anreiseDatum);
        $datumsTeile = explode("-",$__anreiseDatum);
        $anreiseTagInSekunden = mktime(0,0,1,$datumsTeile[1],$datumsTeile[2],$datumsTeile[0]);

        $effektivesVerrechnungsDatum = array();

        for($i= 0; $i <= $this->_anzahlUebernachtungen; $i++){

            $monentanesDatumInSekunden = (86400 * $i) + $anreiseTagInSekunden;
            $effektivesVerrechnungsDatum[$i] = date("Y-m-d", $monentanesDatumInSekunden);
        }

        if($this->_typDatumsZuordnung == $this->_condition_erstmalig_am_anreisetag)
            unset($effektivesVerrechnungsDatum[$i-1]);

        if($this->_typDatumsZuordnung == $this->_condition_letztmalig_am_abreisetag)
            unset($effektivesVerrechnungsDatum[0]);

        // neuordnen Key $effektivesVerrechnungsDatum
        $effektivesVerrechnungsDatum = array_values($effektivesVerrechnungsDatum);

        return $effektivesVerrechnungsDatum;
    }

    /**
     * Ermittelt den Typ der Datumszuordnung
     * 1 = keine Zuordnung
     * 2 = erstmalig am Anreisetag
     * 3 = letztmalig am Abreisetag
     *
     * @throws nook_Exception
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _ermittelnDatumstypVerpflegungstyp(){

        $select = $this->_tabelleProducts->select();

        $cols = array(
            'datumszuordnung',
            'verpflegung'
        );

        $select
            ->from($this->_tabelleProducts, $cols)
            ->where("id = ".$this->_produktId);

        $rows = $this->_tabelleProducts->fetchAll($select)->toArray();

        // Exception Typ 1
        if(count($rows) > 1)
            throw new nook_Exception($this->_error_mehr_als_ein_produktdatensatz);

        $this->_typDatumsZuordnung = $rows[0]['datumszuordnung'];
        $this->_typVerpflegung = $rows[0]['verpflegung'];

        return $this;
    }

    /**
     * Ermittelt den SuppTypeCode eines
     * Produktes. Reaktion auf fehlenden ProduktCode.
     *
     * @return Front_Model_ZusatzprodukteXml
     * @throws nook_Exception
     */
    private function _ermittleSuppTypeCode(){

        try{

            // ermitteln Produkt Code
            $cols = array(
                'productCode'
            );

            $select = $this->_tabelleProducts->select();
            $select
                ->from($this->_tabelleProducts, $cols)
                ->where("id = ".$this->_produktId);

            $row = $this->_tabelleProducts->fetchRow($select)->toArray();

            // wenn kein Produktcode vorhanden
            // Exception Typ 2
            if(empty($row['productCode']))
                throw new nook_Exception($this->_error_kein_product_code_vorhanden);
        }
        catch(nook_Exception $e){
            switch($e->getMessage()){
                case '1060':
                    $row['productCode'] = $this->_fehlerbehandlung1060();
                    break;
            }
        }

        $this->_suppTypeCode = $row['productCode'];

        return $this;
    }

    /**
     * Ermittelt den Produktnamen
     *
     * @param $__produktId
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _ermittleSupplementBeschreibung($__produktId){

        $cols = array(
            'product_name'
        );

        $select = $this->_tabelleProducts->select();
        $select
            ->from($this->_tabelleProducts, $cols)
            ->where("id = ".$__produktId);

        $row = $this->_tabelleProducts->fetchRow($select)->toArray();

        $this->_suppName = $row['product_name'];

        return $this;
    }

    /**
     * Initialisiert den XML Writer
     *
     * @return Front_Model_ZusatzprodukteXml
     */
    private function _initXmlWriter(){

        /** @var _XmlWriter XMLWriter */
        $this->_XmlWriter = new XMLWriter();


        return $this;
    }

    /**
     * Wenn kein Produkt Code vorhanden ist,
     * dann wird ein Produktcode generiert.
     *
     * @return string
     */
    private function _fehlerbehandlung1060(){
        $productCode = 'produkt'.$this->_produktId;

        return $productCode;
    }





}
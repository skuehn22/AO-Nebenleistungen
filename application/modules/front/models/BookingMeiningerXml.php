<?php

/**
 * Erstellt den XML Buchungsdatensatz für ein Meininger Hotel
 *
 * @author Stephan Krauss
 * @date 05.06.2014
 * @file Front_Model_BookingMeiningerXml.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_BookingMeiningerXml
{
    protected $pimple = null;
    protected $datenHotelbuchung = null;
    protected $datenProduktbuchung = null;
    protected $teilrechnungsId = null;

    protected $xmlWriter = null;
    protected $debugModus = false;

    protected $xmlBuchungsanfrage = null;

    protected $mailBody = array();

    // PMSID
    protected $pmsid = null;

    protected $typenPmsid = array(
        1 => 'HSRREQ',
        2 => 'HSROPT',
        3 => 'HSRALL'
    );

    // Besonderheit der Übermittlung der Mehrbettzimmer
    protected $condition_meininger_berlin_airport = 42;
    protected $condition_meininger_berlin_hauptbahnhof = 25;

    protected $condition_meininger_rate_code_einzelzimmer = 'Y.SGL';
    protected $condition_meininger_rate_code_doppelzimmer = 'Y.TWN';
    protected $condition_meininger_rate_code_mehrbettzimmer = 'Y.MUL';

    protected $condition_verpflegungstypen_meininger = array(
        'LP' => 20
    );

    /**
     * Initialisiert den XML Writer
     */
    public function __construct()
    {
        $this->xmlWriter = new XMLWriter();
        $this->xmlWriter->openMemory();

        $this->xmlWriter->startDocument('1.0');
        $this->xmlWriter->setIndent(4);

        $this->xmlWriter->startElement('bookings');
        $this->xmlWriter->startElement('booking');
    }

    /**
     * @param $teilRechnungsId
     * @return Front_Model_BookingMeiningerXml
     * @throws nook_Exception
     */
    public function setTeilrechnungsId($teilRechnungsId)
    {
        $teilRechnungsId = (int)$teilRechnungsId;
        if ($teilRechnungsId == 0)
            throw new nook_Exception('Teilrechnungs ID ist kein Int');

        $this->teilrechnungsId = $teilRechnungsId;

        return $this;
    }

    /**
     * setzt die möglichen Buchungstypen
     *
     * @param $typPmsid
     * @return Front_Model_BookingMeiningerXml
     * @throws nook_Exception
     */
    public function setTypPmsid($typPmsid)
    {
        $typPmsid = (int) $typPmsid;
        if($typPmsid == 0)
            throw new nook_Exception('Buchungstyp falsch');

        $this->pmsid = $this->typenPmsid[$typPmsid];

        return $this;
    }

    /**
     * @param bool $debugModus
     * @return Front_Model_BookingMeiningerXml
     */
    public function setDebugModus($debugModus = false)
    {
        $this->debugModus = $debugModus;

        return $this;
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_BookingMeiningerXml
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $pimple = $this->checkPimple($pimple);
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Überprüft die Pflichtelemente im DIC
     *
     * @param Pimple_Pimple $pimple
     * @return Pimple_Pimple
     * @throws nook_Exception
     */
    protected function checkPimple(Pimple_Pimple $pimple)
    {
        $pflichtElemente = array(
            'dataKunde',
            'dataZusatzinformation',
            'aktuelleBuchung'
        );

        foreach ($pflichtElemente as $element) {
            if (!$pimple->offsetExists($element))
                throw new nook_Exception("Element '" . $element . "' fehlt im DIC");
        }

        return $pimple;
    }

    /**
     * @param array $dataHotelbuchungInEinemMeiningerhotel
     * @return Front_Model_BookingMeiningerXml
     */
    public function setHotelbuchungen(array $dataHotelbuchungInEinemMeiningerhotel)
    {
        $this->datenHotelbuchung = $dataHotelbuchungInEinemMeiningerhotel;

        return $this;
    }

    /**
     * @param array $dataProduktbuchungenInEinemMeiningerHotel
     * @return Front_Model_BookingMeiningerXml
     */
    public function setProduktbuchungen($dataProduktbuchungenInEinemMeiningerHotel)
    {
        $this->datenProduktbuchung = $dataProduktbuchungenInEinemMeiningerHotel;

        return $this;
    }

    /**
     * Steuert die Erstellung der XML Datei der Buchungsanfrage in einem Meininger Hotel
     *
     * @return Front_Model_BookingMeiningerXml
     * @throws Exception
     */
    public function steuerungErstellungXmlBuchungsDatei()
    {
        try {
            // PMSID
            $this->tagPmsid();

            // Lastname + Groupname
            $this->tagLastname($this->pimple['dataKunde']['lastname'], $this->pimple['dataZusatzinformation']['gruppenname']);

            // Buchungsnummer + Zaehler + Teilrechnungs ID
            $this->tagRegnr($this->pimple['hobNummer'], $this->pimple['aktuelleBuchung']['zaehler'], $this->teilrechnungsId);

            // Anreisetag
            $this->tagArrival($this->datenHotelbuchung[0]);

            // Abreisetag
            $this->tagDepart($this->datenHotelbuchung[0]);

            // Verpflegung und Zusatzprodukte
            $this->tagBoardType($this->pimple['datenHotelsMeininger'], $this->pimple['tabelleProducts'], $this->datenProduktbuchung);

            // Gesamtanzahl Personen
            $this->tagPer($this->datenHotelbuchung);

            // gebuchte Raten
            $ratenMeininger = $this->ratenMeininger($this->datenHotelbuchung);

            $this->xmlWriter->startElement('quotas');
            $this->tagQuotas($ratenMeininger);
            $this->xmlWriter->endElement();

            // Auflistung der Personen nach Geschlecht
            $this->tagRtype($this->pimple['dataZusatzinformation']);

            // Kontaktdaten Gruppenleiter
            $this->tagPad($this->pimple['dataKunde']);

            // Reservierungsinformation
            $this->tagTxt($this->pimple['dataZusatzinformation']);

            // Mail
            $this->tagMailBody($this->pimple['dataZusatzinformation']);

            // Debug - Modus
            if ($this->debugModus === true)
                $this->anzeigenXml();

            // Ausgabe XML File
            $this->outputXmlFile();

            return $this;
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Maildaten der Reservierung an Meininger
     */
    protected function tagMailBody(array $dataZusatzinformation)
    {
        $mailTxt = '';
        $mailTxt .= 'Type: ' . $this->mailBody['pmsid'] ."\n";
        $mailTxt .= 'agent: '.$this->pmsid."\n\n";

        $mailTxt .= "Group Name: " . $this->mailBody['lastname'] . "\n";
        $mailTxt .= "Request/booking number: " . $this->mailBody['regnumber'] . "\n\n";

        $mailTxt .= 'Arrival: ' . $this->mailBody['arrival'] ."\n";
        $mailTxt .= "Departure: " . $this->mailBody['depart'] . "\n\n";

        $mailTxt .= "Meals: " . $this->mailBody['board_type'] . "\n\n";

        $mailTxt .= "Total number of guests: " . $this->mailBody['per'] . "\n\n";

        $mailTxt .= "pax single: " . $this->mailBody['pax_single'] . "\n";
        $mailTxt .= "pax twin: " . $this->mailBody['pax_twin'] . "\n";
        $mailTxt .= "pax multi: " . $this->mailBody['pax_multi'] . "\n\n";

        $mailTxt .= "Students male: ".$dataZusatzinformation['maennlichSchueler']."\n";
        $mailTxt .= "Students female: ".$dataZusatzinformation['weiblichSchueler']."\n";
        $mailTxt .= "Teachers male: ".$dataZusatzinformation['maennlichLehrer']."\n";
        $mailTxt .= "Teachers female: ".$dataZusatzinformation['weiblichLehrer']."\n";
        $mailTxt .= "Bus drivers: ".$dataZusatzinformation['sicherstellung']."\n\n";

        $mailTxt .= "Contact details of the group: " . $this->mailBody['pad'] . "\n\n";

        $mailTxt .= "Notes: " .$this->mailBody['txt']. "\n";

        $this->xmlWriter->writeElement('mail_body', $mailTxt);

        return;
    }

    /**
     * Übermittlung der Buchungsinformation
     *
     * @param array $buchungsInformationen
     */
    protected function tagTxt(array $buchungsInformationen)
    {
        $this->xmlWriter->writeElement('txt', $buchungsInformationen['buchungshinweis']);
        $this->mailBody['txt'] = $buchungsInformationen['buchungshinweis'];

        return;
    }

    /**
     * Kontaktdaten des Gruppenverantwortlichen der Gruppe ( Gruppenleiter )
     *
     * @param array $dataKunde
     */
    protected function tagPad(array $dataKunde)
    {
        $padText = $dataKunde['title'] . ' ' . $dataKunde['firstname'] . ' ' . $dataKunde['lastname'] . "\n";
        $padText .= $dataKunde['street'] . "\n";
        $padText .= $dataKunde['zip'] . ' ,' . $dataKunde['city'] . "\n";

        $toolLand = new nook_ToolLand();
        $country = $toolLand->convertLaenderIdNachLandName($dataKunde['country']);
        $padText .= $country . "\n";

        $padText .= "Mail: " . $dataKunde['email'] . "\n";

        if (!empty($dataKunde['phonenumber']))
            $padText .= "Phone: " . $dataKunde['mobileVorwahl'] . " " . $dataKunde['phonenumber'] . "\n";

        $this->xmlWriter->writeElement('pad', $padText);
        $this->mailBody['pad'] = $padText;

        return;
    }

    /**
     * Generiert aus den einzelnen Elementen den Datensatz der Rate
     *
     * @param array $ratenAnEinemTagMeininger
     */
    protected function tagQuotas(array $ratenAnEinemTagMeininger)
    {
        foreach ($ratenAnEinemTagMeininger as $tag => $ratenBelegung) {

            $rate = '';

            for ($i = 1; $i < count($ratenBelegung); $i++) {
                $rate .= $ratenBelegung[$i] . " ";
            }

            $rate = $tag . ' ' . $rate;
            $rate = substr($rate, 0, -1);

            $this->xmlWriter->writeElement('quota', $rate);
        }

        return;
    }

    /**
     * rechnet den Rohdatensatz der Hotelbuchung auf das Ratenmodell Meininger um.
     *
     * @param $datenHotelbuchungen
     * @return array
     */
    protected function ratenMeininger(array $datenHotelbuchungen)
    {
        // leerer Datensatz Rate
        $ratenMeininger = array();

        for ($i = 1; $i < 14; $i++) {
            $ratenMeininger[$datenHotelbuchungen[0]['startDate']][$i] = '0';
        }

        // Berechnung der Datumsangaben der Übernachtungen
        $uebernachtungen = nook_ToolDatum::berechnungUebernachtungen($datenHotelbuchungen[0]['startDate'], $datenHotelbuchungen[0]['nights']);

        /** @var $toolRate nook_ToolRate */
        $toolRate = $this->pimple['toolRate'];

        // Rate am Anreisetag
        foreach ($datenHotelbuchungen as $hotelId => $einzelneHotelbuchung) {

            $toolRate->setRateId($einzelneHotelbuchung['otaRatesConfigId']);
            $rateDatensatz = $toolRate->getRateData();
            $rateCode = $rateDatensatz['rate_code'];

            if (($rateCode == $this->condition_meininger_rate_code_mehrbettzimmer) and (($hotelId == $this->condition_meininger_berlin_airport) or ($hotelId == $this->condition_meininger_berlin_hauptbahnhof))){
                $ratenMeininger[$einzelneHotelbuchung['startDate']][4] = '-' . $einzelneHotelbuchung['personNumbers'];
                $this->mailBody['pax_multi'] = $einzelneHotelbuchung['personNumbers'];
            }
            elseif ($rateCode == $this->condition_meininger_rate_code_mehrbettzimmer){
                $ratenMeininger[$einzelneHotelbuchung['startDate']][6] = '-' . $einzelneHotelbuchung['personNumbers'];
                $this->mailBody['pax_multi'] = $einzelneHotelbuchung['personNumbers'];
            }
            elseif ($rateCode == $this->condition_meininger_rate_code_doppelzimmer){
                $ratenMeininger[$einzelneHotelbuchung['startDate']][2] = '-' . $einzelneHotelbuchung['personNumbers'];
                $this->mailBody['pax_twin'] = $einzelneHotelbuchung['personNumbers'];
            }
            elseif ($rateCode == $this->condition_meininger_rate_code_einzelzimmer){
                $ratenMeininger[$einzelneHotelbuchung['startDate']][1] = '-' . $einzelneHotelbuchung['personNumbers'];
                $this->mailBody['pax_single'] = $einzelneHotelbuchung['personNumbers'];
            }
        }

        // Raten der nachfolgenden Uebernachtungen
        for ($i = 1; $i < count($uebernachtungen); $i++) {
            $ratenMeininger[$uebernachtungen[$i]] = $ratenMeininger[$datenHotelbuchungen[0]['startDate']];
        }

        return $ratenMeininger;
    }

    /**
     * Ermittlung der Gesamtpersonenanzahl einer Gruppe
     *
     * @param array $hotelbuchungen
     */
    protected function tagPer(array $hotelbuchungen)
    {
        $per = 0;
        foreach ($hotelbuchungen as $hotelbuchung) {
            $per += $hotelbuchung['personNumbers'];
        }

        $this->xmlWriter->writeElement('per', $per);
        $this->mailBody['per'] = $per;

        return;
    }

    /**
     * bildet den Tag Boardtyp , 'Frühstück' ist Standard
     *
     */
    protected function tagBoardType(array $datenHotel,Zend_Db_Table_Abstract $tabelleProducts, $datenProduktbuchung)
    {
        // Standard , normales Frühstück
        if(!is_array($datenProduktbuchung))
            $this->xmlWriter->writeElement('board_type', '12');
        // mögliche Veränderung der Verpflegung
        else{
            $toolSucheEintragInSpalte = new nook_ToolSucheEintragInSpalte();
            $toolSucheEintragInSpalte
                ->setTabelle($tabelleProducts)
                ->setGesuchteSpalte('productCode');

            $verpflegungsTyp = 12;
            for($i=0; $i < count($datenProduktbuchung); $i++){

                $whereSpalte = array(
                    "id = ".$datenProduktbuchung[$i]['products_id']
                );


                $produktCode = $toolSucheEintragInSpalte
                    ->setSpalteWhereKlausel($whereSpalte)
                    ->steuerungErmittlungInhaltSpalte()
                    ->getInhaltSpalte();

                // Verpflegungstypen
                if($this->condition_verpflegungstypen_meininger[$produktCode[0]['productCode']])
                    $verpflegungsTyp = $this->condition_verpflegungstypen_meininger[$produktCode[0]['productCode']];
            }

            $this->mailBody['board_type'] = $verpflegungsTyp;
            $this->xmlWriter->writeElement('board_type', $verpflegungsTyp);
        }

        return;
    }

    /**
     * Erstellt Tag '<pmsid>'
     *
     * + Kennung für Request
     */
    protected function tagPmsid()
    {
        $this->xmlWriter->writeElement('pmsid', $this->pmsid);
        $this->mailBody['pmsid'] = $this->pmsid;

        return;
    }

    /**
     * Übermittlung Familienname und wenn vorhanden Gruppenname
     *
     * @param $lastname
     * @param $groupname
     */
    protected function tagLastname($lastname, $groupname = false)
    {
        $groupname = trim($groupname);

        if( (!empty($groupname)) and (strlen($groupname) > 3) )
            $this->xmlWriter->writeElement('lastname', 'HSR' . "/" . $groupname);
        else
            $this->xmlWriter->writeElement('lastname', 'HSR' . "/" . $lastname);

        if( (!empty($groupname)) and (strlen($groupname) > 3) )
            $this->mailBody['lastname'] = $lastname;
        else
            $this->mailBody['lastname'] = $groupname;

        return;
    }

    /**
     * <f_regnr>
     *
     * + Buchungsnummer
     * + Zaehler
     * + Teilrechnungsnummer
     *
     * @param $buchungsnummer
     * @param $zaehler
     */
    protected function tagRegnr($buchungsnummer, $zaehler, $teilrechnungsId)
    {
        $this->xmlWriter->writeElement('f_regnr', $buchungsnummer . "." . $zaehler.".".$teilrechnungsId."/HSR");
        $this->mailBody['regnumber'] =  $buchungsnummer . "." . $zaehler.".".$teilrechnungsId."/HSR";

        return;
    }

    /**
     * Anreisetag der Gruppe
     *
     * @param array $hotelbuchungInDerErstenRate
     */
    protected function tagArrival(array $hotelbuchungInDerErstenRate)
    {
        $this->xmlWriter->writeElement('arrival', $hotelbuchungInDerErstenRate['startDate']);

        $this->mailBody['arrival'] = $hotelbuchungInDerErstenRate['startDate'];

        return;
    }

    /**
     * Berechnet das Abreisedatum der Gruppe
     *
     * @param array $hotelbuchungInDerErstenRate
     */
    protected function tagDepart(array $hotelbuchungInDerErstenRate)
    {
        $anreiseDatum = date_create($hotelbuchungInDerErstenRate['startDate']);
        date_add($anreiseDatum, date_interval_create_from_date_string($hotelbuchungInDerErstenRate['nights'] . ' days'));
        $abreiseDatum = date_format($anreiseDatum, 'Y-m-d');

        $this->xmlWriter->writeElement('depart', $abreiseDatum);

        $this->mailBody['depart'] = $abreiseDatum;

        return;
    }

    /**
     * Schreibt die Aufschluesselung der Personen nach Geschlecht und Funktion in 'rtype'
     *
     * @param array $dataZusatzinformation
     */
    protected function tagRtype(array $dataZusatzinformation)
    {
        // Schüler männlich
        $this->xmlWriter->writeElement('rtype1_1', $dataZusatzinformation['maennlichSchueler']);
        // Schüler weiblich
        $this->xmlWriter->writeElement('rtype1_2', $dataZusatzinformation['weiblichSchueler']);
        // Lehrer männlich
        $this->xmlWriter->writeElement('rtype1_3', $dataZusatzinformation['maennlichLehrer']);
        // Lehrer weiblich
        $this->xmlWriter->writeElement('rtype1_4', $dataZusatzinformation['weiblichLehrer']);
        // Busfahrer
        $this->xmlWriter->writeElement('rtype1_5', $dataZusatzinformation['sicherstellung']);

        return;
    }


    /**
     * Anzeigen des XML Booking File
     */
    protected function anzeigenXml()
    {
        // end booking
        $this->xmlWriter->endElement();

        // end bookings
        $this->xmlWriter->endElement();

        $this->xmlWriter->endDocument();

        // output Memory
        $xmlFile = $this->xmlWriter->outputMemory();

        // Memory löschen
        $this->xmlWriter->flush();

        echo $xmlFile;
        exit();
    }

    /**
     * schliesssen tag 'booking' , 'bookings' , befüllen xmlBuchungsanfrage , löschen Memory
     */
    protected function outputXmlFile()
    {
        // end booking
        $this->xmlWriter->endElement();

        // end bookings
        $this->xmlWriter->endElement();

        $this->xmlWriter->endDocument();

        // output Memory
        $this->xmlBuchungsanfrage = $this->xmlWriter->outputMemory();

        // Memory löschen
        $this->xmlWriter->flush();

        return $this->xmlBuchungsanfrage;
    }

    /**
     * Rückgabe XML Buchungsdatensatz Meininger nach erfolgter Konvertierung in ISO8859-1
     *
     * @return string
     */
    public function getXmlBuchungsDatei()
    {
        $xmlBuchungMeininger = $this->xmlBuchungsanfrage;
        $xmlBuchungMeininger = utf8_decode($xmlBuchungMeininger);

        return $xmlBuchungMeininger;
    }
}
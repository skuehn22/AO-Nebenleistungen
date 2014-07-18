<?php
/**
 * Steuert die Übermittlung der Buchung an die Hotelkette Meininger
 *
 * @author Stephan Krauss
 * @date 04.06.2014
 * @file Front_Model_BestellungBookingMeiningerShadow.php
 * @project HOB
 * @package front
 * @subpackage shadow
 */
class Front_Model_BestellungBookingMeiningerShadow
{
    protected $pimple = null;

    protected $vereinbarteRatenMeininger = null;
    // notice: Debugmodus XML Datei Meininger
    protected $flagDebugModusXmlMeiningerErstellen = false;

    /**
     * erstellen DIC Pimple
     */
    public function __construct()
    {
        $pimple = $this->createPimple();
        $this->pimple = $pimple;
    }

    /**
     * Erstellt den Pimple Container und stellt die Tabellen
     * im DIC zur Verfügung
     */
    protected function createPimple()
    {
        $pimpleObj = new Pimple_Pimple();

        // Tabellen
        $pimpleObj['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimpleObj['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimpleObj['tabelleProduktbuchung'] = function(){
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimpleObj['tabelleAdressen'] = function(){
            return new Application_Model_DbTable_adressen();
        };

        $pimpleObj['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimpleObj['tabelleProducts'] = function(){
            return new Application_Model_DbTable_products(array('db' => 'hotels'));
        };

        $pimpleObj['tabelleProperties'] = function(){
            return new Application_Model_DbTable_properties(array('db' => 'hotels'));
        };

        $pimpleObj['toolRate'] = function(){
            return new nook_ToolRate();
        };

        $applicationConfigMeininger = new Application_Model_Configs_Meininger();
        $pimpleObj['serverIpMeininger'] = $applicationConfigMeininger->getMeiningerIp();

        return $pimpleObj;
    }

    /**
     * @param array $aktuelleBuchung
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    public function setAktuelleBuchung(array $aktuelleBuchung)
    {
        $this->pimple['aktuelleBuchung'] = $aktuelleBuchung;

        return $this;
    }

    /**
     * @param array $datenHotelsMeininger
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    public function setHoteldatenMeininger(array $datenHotelsMeininger)
    {
        $this->pimple['datenHotelsMeininger'] = $datenHotelsMeininger;

        return $this;
    }

    /**
     * @param array $dataHotelbuchung
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    public function setDataHotelbuchung(array $dataHotelbuchung)
    {
        $this->pimple['dataHotelbuchung'] = $dataHotelbuchung;

        return $this;
    }

    /**
     * übernahme der vereinbarten Raten Meininger
     *
     * @param array $vereinbarteRatenMeininger
     * @return Front_Model_BestellungBookingMeiningerShadow
     * @throws nook_Exception
     */
    public function setVereinbarteRatenMeininger(array $vereinbarteRatenMeininger)
    {
        if(!is_array($vereinbarteRatenMeininger))
            throw new nook_Exception('die vereinbarten Raten Meininger sind unbekannt');

        $this->vereinbarteRatenMeininger = $vereinbarteRatenMeininger;

        return $this;
    }

    /**
     * Überprüft ob in der 'tbl_hotelbuchung' Buchungen für die aktuelle Buchungsnummer sind.
     *
     * + Überprüfen ob eine Hotelbuchung Meininger vorliegt
     *
     * @return bool
     */
    public function checkHotelbuchungenMeiniger()
    {
        $hotelbuchungenMeiningerVorhanden = false;

        $datenHotelsMeininger = $this->pimple->offsetGet('datenHotelsMeininger');
        $datenHotelbuchung = $this->pimple->offsetGet('dataHotelbuchung');

        // Überprüfen ob eine Hotelbuchung Meininger vorliegt
        foreach($datenHotelbuchung as $key => $hotelbuchung){
            foreach($datenHotelsMeininger as $propertyId => $hotelDaten){
                if($hotelbuchung['propertyId'] == $propertyId){
                    $hotelbuchungenMeiningerVorhanden = true;
                }
            }
        }

        return $hotelbuchungenMeiningerVorhanden;
    }

    /**
     * Ermittelt notwendige Angaben an Meininger und versendet die Buchungsinformation
     *
     * + ermitteln der produktbuchungen
     * + ermitteln der Adressdaten der Kunden
     * + ermitteln Zusatzinformationen zur Buchung
     *
     * @return Front_Model_BestellungBookingMeiningerShadow
     * @throws Exception
     */
    public function steuerungVersendenBuchungsinformationAnMeininger()
    {
        try{
            // vereinbarte Raten Meininger
            if(is_null($this->vereinbarteRatenMeininger))
                throw new nook_Exception('vereinbarte Raten Meininger fehlen');

            $this->pimple['vereinbarteRatenMeininger'] = $this->vereinbarteRatenMeininger;

            // HOB NUmmer / Registrierungsnummer
            $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
            $this->pimple['hobNummer'] = $toolRegistrierungsnummer
                ->setBuchungsnummer($this->pimple['aktuelleBuchung']['buchungsnummer'])
                ->steuerungErmittlungRegistrierungsnummer()
                ->getRegistrierungsnummer();

            // Kunden ID ermitteln
            $toolKundendaten = new nook_ToolKundendaten();
            $this->pimple['kundenId'] = $toolKundendaten->findKundenId();

            // Produkt Buchung
            $this->ermittelnProduktBuchungen($this->pimple['aktuelleBuchung']['buchungsnummer'], $this->pimple['aktuelleBuchung']['zaehler']);

            // Kundendaten
            $this->ermittelnKundendaten($this->pimple['kundenId']);

            // Zusatzinformation
            $this->ermittelnZusatzinformationKunde($this->pimple['aktuelleBuchung']['buchungsnummer'], $this->pimple['aktuelleBuchung']['zaehler']);

            // bestimmen der Property ID der Hotels Meininger
            $propertyIdMeiningerHotels = array_keys($this->pimple['datenHotelsMeininger']);

            // ausfiltern der Hotelbuchungen für Hotels Meininger
            $dataHotelbuchungMeininger = array();
            for($i=0; $i < count($this->pimple['dataHotelbuchung']); $i++){
                if(in_array($this->pimple['dataHotelbuchung'][$i]['propertyId'], $propertyIdMeiningerHotels))
                    $dataHotelbuchungMeininger[$this->pimple['dataHotelbuchung'][$i]['teilrechnungen_id']][] = $this->pimple['dataHotelbuchung'][$i];
            }

            // ausfiltern der Produktbuchungen für Hotels Meininger
            $dataProduktbuchungMeininger = array();
            for($i=0; $i < count($this->pimple['dataProduktbuchung']); $i++){
                if(array_key_exists($this->pimple['dataProduktbuchung'][$i]['teilrechnungen_id'], $dataHotelbuchungMeininger))
                    $dataProduktbuchungMeininger[$this->pimple['dataProduktbuchung'][$i]['teilrechnungen_id']][] = $this->pimple['dataProduktbuchung'][$i];
            }

            // entfernen überflüssiger Daten
            $this->pimple->offsetUnset('dataHotelbuchung');
            $this->pimple->offsetUnset('dataProduktbuchung');

            // erstellen XML und versenden Buchung für jede Property Meininger
            foreach($dataHotelbuchungMeininger as $teilRechnungsId => $dataHotelbuchungInEinemMeiningerhotel){

                // Vorbereitung der Daten für ein Meininger Hotel
                $dataProduktbuchungenInEinemMeiningerHotel = false;

                if(array_key_exists($teilRechnungsId, $dataProduktbuchungMeininger)){
                    $dataProduktbuchungenInEinemMeiningerHotel = $dataProduktbuchungMeininger[$teilRechnungsId];
                }

                // erstellen Buchungs XML
                $xmlBookingMeininger = $this->erstellenBuchungsXmlFuerEinHotel($teilRechnungsId, $dataHotelbuchungInEinemMeiningerhotel, $dataProduktbuchungenInEinemMeiningerHotel);

                // versenden Buchungs XML
                $this->versendenBuchungsXmlAnMeininger($xmlBookingMeininger, $this->pimple['datenHotelsMeininger'],  $dataHotelbuchungInEinemMeiningerhotel[0]['propertyId']);
            }

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * versendet die XML Buchungsinformation an Meininger
     *
     * @param $xmlBookingMeininger
     */
    protected function versendenBuchungsXmlAnMeininger($xmlBookingMeininger, array $datenHotelsMeininger, $propertyIdMeiningerHotel)
    {
        try{

            $this->pimple['tabelleSchnittstelleMeininger'] = function(){
                return new Application_Model_DbTable_schnittstelleMeininger();
            };

            $ipServerMeininger = $this->pimple['serverIpMeininger'];
            $portServerHotelMeininger = $datenHotelsMeininger[$propertyIdMeiningerHotel]['port'];

            $frontModelversendenBuchungsanfrageMeininger = new Front_Model_VersendenBuchungsanfrageMeininger();
            $flagUebermittlung = $frontModelversendenBuchungsanfrageMeininger
                ->setIp($ipServerMeininger)
                ->setPort($portServerHotelMeininger)
                ->setUrlErweiterungMeininger('rq_oldinterface')
                ->setXmlBuchungsanfrage($xmlBookingMeininger)
                ->setPimple($this->pimple)
                ->steuerungBuchungsanfrage()
                ->getFlagUbermittlung();

            if($flagUebermittlung == false){
                throw new nook_Exception('Buchung wurde nicht zu Meininger versandt');
            }

        }
        catch(nook_Exception $e){
            $e->kundenId = nook_ToolKundendaten::findKundenId();
            nook_ExceptionRegistration::buildAndRegisterErrorInfos($e, 2);
        }









        return;
    }

    /**
     * Ermittelt Produktbuchungen einer Buchung der Hotelkette Meininger
     *
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    protected function ermittelnProduktBuchungen($buchungsnummer, $zaehler)
    {
        $frontModelHotelprodukteEinerBuchung = new Front_Model_HotelprodukteEinerBuchung();
        $produkteEinerBuchung = $frontModelHotelprodukteEinerBuchung
            ->setBuchungsnummer($buchungsnummer)
            ->setZaehler($zaehler)
            ->setPimple($this->pimple)
            ->steuerungErmittlungProdukteEinerBuchung()
            ->getProdukteEinerBuchung();

        $this->pimple['dataProduktbuchung'] = $produkteEinerBuchung;

        return $this;
    }

    /**
     * Ermittelt die Kundendaten , Adressdaten eines Bestellers , Kunde
     *
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    protected function ermittelnKundendaten($kundenId)
    {
        $toolAdressdaten = new nook_ToolAdressdaten($this->pimple);
        $adressDatenKunde = $toolAdressdaten
            ->setKundenId($kundenId)
            ->steuerungErmittlungKundendaten()
            ->getAdressdatenKunde();

        $this->pimple['dataKunde'] = $adressDatenKunde;

        return $this;
    }

    /**
     * Ermittelt Zusatzinformationen zur Buchung
     *
     * + Gruppenname
     * + Zusatzinformation zur Buchung
     *
     * @return Front_Model_BestellungBookingMeiningerShadow
     */
    protected function ermittelnZusatzinformationKunde($buchungsnummer, $zaehler)
    {
        $frontModelBuchungshinweisGruppe = new Front_Model_BuchungsHinweisGruppe();
        $zusatzinformationBuchungGruppe = $frontModelBuchungshinweisGruppe
            ->setBuchungsNummer($buchungsnummer)
            ->setZaehler($zaehler)
            ->setPimple($this->pimple)
            ->steuerungErmittlungZusatzinformationBuchung()
            ->getZusatzinformationBuchung();

        $this->pimple['dataZusatzinformation'] = $zusatzinformationBuchungGruppe[0];

        return $this;
    }

    /**
     * Erstellt die XML Buchungsdatei für Meininger Hotels
     *
     * @param $teilRechnungsId
     * @param $dataHotelbuchungInEinemMeiningerhotel
     * @param $dataProduktbuchungenInEinemMeiningerHotel
     * @return mixed
     */
    protected function erstellenBuchungsXmlFuerEinHotel($teilRechnungsId, $dataHotelbuchungInEinemMeiningerhotel, $dataProduktbuchungenInEinemMeiningerHotel)
    {
        $frontModelBookingMeiningerXml = new Front_Model_BookingMeiningerXml();
        $xmlBookingmeininger = $frontModelBookingMeiningerXml
            ->setDebugModus($this->flagDebugModusXmlMeiningerErstellen) // Debug Modus aserwerqwe
            ->setTeilrechnungsId($teilRechnungsId)
            ->setPimple($this->pimple)
            ->setTypPmsid(3) // Buchung Typ 'Anfrage' = 'HSRREQ'
            ->setHotelbuchungen($dataHotelbuchungInEinemMeiningerhotel)
            ->setProduktbuchungen($dataProduktbuchungenInEinemMeiningerHotel)
            ->steuerungErstellungXmlBuchungsDatei()
            ->getXmlBuchungsDatei();

        return $xmlBookingmeininger;
    }


}
<?php
/**
* Der Benutzer kann sich ein Pdf seiner Vormerkung erstellen lassen
*
* + Generiert das Pdf der Vormerkung
* + ermittelt die Hotelprodukte einer Vormerkung
* + Ermittelt die Zusatzinformationen einer Vormerkung
* + Ermittelt die Programme der Vormerkung
* + Ermittelt die Hotelbuchungen der Vormerkung
* + Ermittelt die Zusatzinformationen
* + Ermitteln der Adressdaten des Kunden
* + Erstellen Pdf der Vormerkung
*
* @date 26.11.2013
* @file VormerkpdfController.php
* @package front
* @subpackage controller
*/
class Front_VormerkpdfController extends Zend_Controller_Action{

    private $realParams = null;

    /** @var $pimple Pimple_Pimple  */
    private $pimple = null;

    // Konditionen
    private $condition_zaehler_warenkorb = 0;

    private $gesamtpreisAllerArtikel = 0;
    private $hotelbuchungenVormerkung = array();
    private $gesamtRabattWarenkorb = 0;

    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->servicecontainer();

        $this->requestUrl = $this->view->url();
    }

    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleBuchungsnummer'] = function()
        {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple['tabelleAdressen'] = function()
        {
            return new Application_Model_DbTable_adressen();
        };

        $pimple['tabelleProgrammbeschreibung'] = function()
        {
            return new Application_Model_DbTable_programmbeschreibung();
        };

        $pimple['tabelleProgrammdetails'] = function()
        {
            return new Application_Model_DbTable_programmedetails();
        };

        $pimple['tabellePreise'] = function()
        {
            return new Application_Model_DbTable_preise();
        };

        $pimple['tabelleProgSprache'] = function()
        {
            return new Application_Model_DbTable_progSprache();
        };

        $pimple['tabelleTextbausteine'] = function()
        {
            return new Application_Model_DbTable_textbausteine();
        };

        $pimple['tabellePreiseBeschreibung'] = function()
        {
            return new Application_Model_DbTable_preiseBeschreibung();
        };

        $pimple['tabelleProgrammbuchung'] = function(){
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function(){
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple['tabelleProperties'] = function(){
            return new Application_Model_DbTable_properties(array('db' => 'hotels'));
        };

        $pimple['tabelleCategories'] = function(){
            return new Application_Model_DbTable_categories(array('db' => 'hotels'));
        };

        $pimple['tabelleProducts'] = function(){
            return new Application_Model_DbTable_products(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaRatesConfig'] = function(){
            return new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaPrices'] = function(){
            return new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));
        };

        $pimple['tabelleAoCityBettensteuer'] = function(){
            return new Application_Model_DbTable_aoCityBettensteuer();
        };

        $pimple['toolAdressdaten'] = function($pimple)
        {
            return new nook_ToolAdressdaten($pimple);
        };

        $pimple['frontModelBestellung'] = function()
        {
            return new Front_Model_Bestellung();
        };

        $pimple['toolWochentageName'] = function()
        {
            return new nook_ToolWochentageNamen();
        };

        $pimple['toolMonatsname'] = function()
        {
            return new nook_ToolMonatsnamen();
        };

        $pimple['toolProgrammdetails'] = function($pimple)
        {
            return new nook_ToolProgrammdetails($pimple);
        };

        $pimple['toolProgrammsprache'] = function()
        {
            return new nook_ToolProgrammsprache();
        };

        $pimple['toolPreisvariante'] = function()
        {
            return nook_ToolPreisvariante::getInstance();
        };

        $pimple['toolErmittlungAbweichendeStornofristenKosten'] = function()
        {
            return new nook_ToolErmittlungAbweichendeStornofristenKosten();
        };

        $pimple['frontModelVormerkPdf'] = function($pimple)
        {
            return new Front_Model_VormerkPdf($pimple);
        };

        $pimple['toolBasisdatenHotel'] = function($pimple)
        {
            return new nook_ToolBasisdatenHotel($pimple);
        };

        $pimple['toolBasisdatenKategorie'] = function($pimple)
        {
            return new nook_ToolBasisdatenKategorie($pimple);
        };

        $pimple['toolRate'] = function()
        {
            return new nook_ToolRate();
        };

        $pimple['toolMonatsnamen'] = function()
        {
            return new nook_ToolMonatsnamen();
        };

        $pimple['toolZaehler'] = function($pimple){
            return new nook_ToolZaehler($pimple);
        };

        $pimple['toolHotelbuchungenWarenkorb'] = function($pimple){
            return new nook_ToolHotelbuchungenWarenkorb($pimple);
        };

        $this->pimple = $pimple;

        return;
    }

    /**
     * Generiert das Pdf der Vormerkung
     */
    public function indexAction(){
        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();
            $this->getResponse()->clearBody();

            /** @var $frontModelVormerkPdf Front_Model_VormerkPdf */
            $frontModelVormerkPdf = $this->pimple['frontModelVormerkPdf'];
            $frontModelVormerkPdf->setBuchungsnummer($this->realParams['buchungsNummerId']);

            $raintpl = raintpl_rainhelp::getRainTpl();

            // Adresse
            $raintpl = $this->ermittelnAdresse($frontModelVormerkPdf, $raintpl);

            // Programme der Vormerkung
            $raintpl = $this->ermittelnProgrammeDerVormerkung($raintpl);

            // Hotelbuchungen der Vormerkung und Text Bettensteuer
            $raintpl = $this->ermittelnHotelbuchungenDerVormerkung($raintpl);

            // Hotelprodukte der Vormerkung
            $raintpl = $this->ermittelnHotelprodukteDerVormerkung($raintpl);

            // Detailinformationen
            $raintpl = $this->ermittelnDetailinformationen($frontModelVormerkPdf, $raintpl);

            // Zusatzinformationen
            $raintpl = $this->ermittelnZusatzinformationen($raintpl);

            // Gruppenrabatt
            $raintpl = $this->berechnungGruppenrabatt($raintpl);

            // Berechnung Preise
            $raintpl = $this->preisBerechnung($raintpl);

            // Erstellt Pdf
            $this->erstellenPdf($raintpl);

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * @param $raintpl
     * @return mixed
     */
    protected function abweichendeStornobedingungen($raintpl)
    {



        return $raintpl;
    }

    /**
     * ermittelt den Text der Bettensteuer in einer Stadt
     *
     * + sichtet die ankommenden Hotelbuchungen
     * + reduziert Hotelbuchungen, für jede Stadt ein Datensatz
     * + ermittelt Überschrift und Kurztext der Bettensteuer
     *
     * @param array $hotelbuchungen
     * @return string
     */
    protected function ermittlungTexteBettensteuer(array $hotelbuchungen)
    {
        $texteBettensteuer = array();
        $j = 0;

        $spracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        // vereinzeln der Datensaetze der Stadt
        $uniqueColumns = array(
            "cityId"
        );

        $frontModelUniqueRows = new Front_Model_UniqueRowsArray();
        $reduzierteHotelbuchungen = $frontModelUniqueRows
            ->setAusgangsArray($hotelbuchungen)
            ->setUniqueArray($uniqueColumns)
            ->setFlagSuchparameter('cityId')
            ->steuerungErmittlungUniqueRows()->getReduzierteArray();

        // Texte der Bettensteuer
        $frontModelBettensteuerStadt = new Front_Model_BettensteuerStadt();
        $frontModelBettensteuerStadt
            ->setTabelleAoCityBettensteuer($this->pimple['tabelleAoCityBettensteuer']);

        for($i=0; $i < count($reduzierteHotelbuchungen); $i++){

            $hotelbuchungEinerStadt = $reduzierteHotelbuchungen[$i];

            // hat die Stadt eine Bettensteuer
            $flagHasBettensteuer = $frontModelBettensteuerStadt
                ->setCityId($hotelbuchungEinerStadt['cityId'])
                ->setSpracheId($spracheId)
                ->steuerungErmittlungBettensteuerStadt()
                ->hasBettensteuer();

            // Array Texte der Bettensteuer
            if(!empty($flagHasBettensteuer)){
                $texteBettensteuer[$j]['title'] = $frontModelBettensteuerStadt->getTitleBettensteuer();
                $texteBettensteuer[$j]['kurztext'] = $frontModelBettensteuerStadt->getKurztextBettensteuer();

                $j++;
            }
        }

        return $texteBettensteuer;
    }

    /**
     * Ermittelt den Gruppenrabatt des aktiven Warenkorbes
     *
     * @param RainTPL $raintpl
     * @return RainTPL
     */
    private function berechnungGruppenrabatt(RainTPL $raintpl)
    {
        if(count($this->hotelbuchungenVormerkung) < 1)
            return $raintpl;

        // Ermitteln und hinzufügen Gruppenrabatt
        $frontModelWarenkorbGruppenrabatt = new Front_Model_WarenkorbGruppenrabatt($this->pimple);
        $gruppenRabatt = $frontModelWarenkorbGruppenrabatt
            ->setBuchungsNummerId($this->realParams['buchungsNummerId'])
            ->setZaehler($this->condition_zaehler_warenkorb)
            ->steuerungErmittlungGruppenRabatt()
            ->getGruppenRabatt();

        $gesamtRabattWarenkorb = $frontModelWarenkorbGruppenrabatt->getGesamtRabattWarenkorb();
        $this->gesamtRabattWarenkorb = $gesamtRabattWarenkorb;

        $raintpl->assign('gruppenRabatt', $gruppenRabatt);
        $raintpl->assign('gesamtRabattWarenkorb', $gesamtRabattWarenkorb);

        return $raintpl;
    }

    /**
     * ermittelt die Hotelprodukte einer Vormerkung
     *
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnHotelprodukteDerVormerkung(RainTPL $raintpl)
    {
        $frontModelVormerkPdfHotelprodukte = new Front_Model_VormerkPdfHotelprodukte($this->pimple);
        $datenHotelprodukte = $frontModelVormerkPdfHotelprodukte
            ->setBuchungsNummerId($this->realParams['buchungsNummerId'])
            ->steuerungErmittlungDatenHotelprodukteVormerkung()
            ->getDatenHotelprodukte();

        $raintpl->assign('hotelprodukte', $datenHotelprodukte);

        // Summe Gesamppreis aller Hotelprodukte
        for($i=0; $i < count($datenHotelprodukte); $i++){
            $this->gesamtpreisAllerArtikel += $datenHotelprodukte[$i]['summeProduktPreis'];
        }

        return $raintpl;
    }

    /**
     * Ermittelt die Zusatzinformationen einer Vormerkung
     *
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnZusatzinformationen(RainTPL $raintpl)
    {
        $zusatz = array();

        $toolStandardtexte = new nook_ToolStandardtexte();
        $zusatz['vormerkung'] = $toolStandardtexte
            ->setPimple($this->pimple)
            ->setBlockname('vormerkung')
            ->steuerungErmittelnText()
            ->getText();

        $raintpl->assign('zusatz', $zusatz);

        return $raintpl;
    }

    /**
     * Ermittelt die Programme der Vormerkung
     *
     * + berechnet Preis aller vorgemerkten Programme
     * + ermittelt Anzahl der Buchungspauschalen, wenn Programme vorgemerkt werden
     *
     * @param $modelVormerkPdf
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnProgrammeDerVormerkung($raintpl)
    {
        $frontModelVormerkPdfProgramme = new Front_Model_VormerkPdfProgramme($this->pimple);

        // Grundinitialisierung
        $programme = $frontModelVormerkPdfProgramme
            ->setBuchungsnummer($this->realParams['buchungsNummerId'])
            ->steuerungErmittlungProgrammbuchungen()
            ->getProgrammeVormerkung();



        $raintpl->assign('programme', $programme);

        // Gesamtpreis und Anzahl Buchungspauschalen
        $anzahlBuchungsPauschalen = 0;


        for($i=0; $i < count($programme); $i++){
            // Gesamtpreis der Artikel
            $this->gesamtpreisAllerArtikel += $programme[$i]['summeProgrammPreis']; // Summe Gesamppreis aller Programme
            $programm = $programme[$i];

            // Flag Buchungspauschale
            $programme[$i] = $this->ermittlungFlagBuchungspauschale($programm);
        }

        // wenn Programme vorgemerkt werden
        if(count($programme) > 0){
            // Ermitteln Anzahl Buchungspauschalen
            $vergleichsArray = array(
                'programmId',
                'komplettesDatum',
                'zeit'
            );

            $flagVergleich = 'buchungspauschale';

            $anzahlBuchungsPauschalen = $this->ermittelnAnzahlBuchungspauschale($programme, $vergleichsArray, $flagVergleich);
        }



        // Berechnung Preis Buchungspauschale
        if($anzahlBuchungsPauschalen > 0){
            $toolRegistryDaten = new nook_ToolRegistryDaten('buchungspauschale');
            $datenRegistryBuchungsPauschale = $toolRegistryDaten->steuerungErmittelnDaten()->getKonfigDaten();
            $preisDerBuchungspauschalen = $anzahlBuchungsPauschalen * $datenRegistryBuchungsPauschale['preis'];

            $raintpl->assign('preisBuchungsPauschalen', $preisDerBuchungspauschalen);
            $this->gesamtpreisAllerArtikel += $preisDerBuchungspauschalen;
        }

        return $raintpl;
    }

    /**
     * Ermittelt die Hotelbuchungen der Vormerkung und Text Bettensteuer
     *
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnHotelbuchungenDerVormerkung($raintpl)
    {
        // Hotelbuchungen der Vormerkung
        $frontModelVormerkPdfHotel = new Front_Model_VormerkPdfHotel($this->pimple);
        $hotelbuchungen = $frontModelVormerkPdfHotel
            ->setBuchungsnummerId($this->realParams['buchungsNummerId'])
            ->steuerungErmittlungHotelbuchungenVormerkung()
            ->getDatenHotelbuchungen();

        $this->hotelbuchungenVormerkung = $hotelbuchungen;

        $raintpl->assign('hotelbuchungen', $hotelbuchungen);

        // Summe Gesamppreis aller Programme
        for($i=0; $i < count($hotelbuchungen); $i++){
            $this->gesamtpreisAllerArtikel += $hotelbuchungen[$i]['summeZimmerPreis'];
        }

        // Standardtext 'Bettensteuer'
        if(count($hotelbuchungen) > 0){
            $textBettensteuer = $this->ermittlungTexteBettensteuer($hotelbuchungen);
            $raintpl->assign('textBettensteuer', $textBettensteuer);
        }

        return $raintpl;
    }

    /**
     * Ermittelt die Zusatzinformationen
     *
     * @param $modelVormerkPdf
     * @param $raintpl
     */
    private function ermittelnDetailinformationen(Front_Model_VormerkPdf $modelVormerkPdf, $raintpl)
    {
        $detailInformationen = $modelVormerkPdf->getDetail();
        $raintpl->assign('detail', $detailInformationen);

        return $raintpl;
    }

    /**
     * Ermitteln der Adressdaten des Kunden
     *
     * @param Front_Model_VormerkPdf $modelVormerkPdf
     * @param $raintpl
     * @return mixed
     */
    private function ermittelnAdresse(Front_Model_VormerkPdf $modelVormerkPdf, $raintpl)
    {
        $adresse = $modelVormerkPdf->erstellenAdressdatenPdfVormerkung()->getAdresse();

        $adresse['name'] = $adresse['title']." ".$adresse['firstname']." ".$adresse['lastname'];
        $adresse['anschrift'] = $adresse['street'];
        $adresse['ort'] = $adresse['zip']." ".$adresse['city'];

        $raintpl->assign('adresse', $adresse);

        return $raintpl;
    }

    /**
     * Berechnung der Preise
     *
     * + ursprünglicher Listenpreis
     *
     * @param $raintpl
     * @return mixed
     */
    private function preisBerechnung($raintpl)
    {
        $raintpl->assign('listenpreis', $this->gesamtpreisAllerArtikel);

        if($this->gesamtRabattWarenkorb == 0)
            $endpreis = $this->gesamtpreisAllerArtikel;
        else
            $endpreis = $this->gesamtpreisAllerArtikel - $this->gesamtRabattWarenkorb;

        $raintpl->assign('endpreis', $endpreis);

        return $raintpl;
    }

    /**
     * Erstellen Pdf der Vormerkung.
     *
     * + Verrechnung des Rabatt
     *
     * @param $raintpl
     */
    private function erstellenPdf($raintpl)
    {
        $tpl = $raintpl->draw( "Front_Vormerkpdf_Index", true );

        include_once('../library/html2pdf/html2pdf.class.php');

        // init HTML2PDF
        $html2pdf = new HTML2PDF('P', 'A4', 'de', true, 'UTF-8', 0);

        // display the full page
        $html2pdf->pdf->SetDisplayMode('fullpage');

        $html2pdf->writeHTML($tpl, isset($_GET['vuehtml']));

        // send the PDF
        $html2pdf->Output('Vormerkung.pdf');

        exit();
    }

    /**
     * Ermittelt ob das Programm eine Buchungspauschale hat
     *
     * @param $programmId
     * @param $anzahlBuchungsPauschalen
     * @return mixed
     */
    protected function ermittlungFlagBuchungspauschale($programm)
    {
        $toolBuchungspauschale = new nook_ToolBuchungspauschale($this->pimple);
        $hasBuchungsPauschale = $toolBuchungspauschale
            ->setProgrammId($programm['programmId'])
            ->steuerungErmittlungObProgrammBuchungspauschaleHat()
            ->hasBuchungsPauschale();

        $programm['buchungspauschale'] = $hasBuchungsPauschale;

        return $programm;
    }

    /**
     * Ermittelt die Anzahl der Datensätze eines Array die identisch sind
     *
     * + Ausgangs Array
     * + Vergleichs Array welches Unique / vergleichbare Spalten hat
     * + wenn keine Programme vorliegen, dann wird eine Anzahl 0
     *
     * @param $programme
     * @return int
     */
    private function ermittelnAnzahlBuchungspauschale($programme, $vergleichsArray, $flagVergleich)
    {
        $frontModelReduceRowsArray = new Front_Model_UniqueRowsArray();

        $anzahlRows = $frontModelReduceRowsArray
            ->setAusgangsArray($programme)
            ->setUniqueArray($vergleichsArray)
            ->setFlagSuchparameter($flagVergleich)
            ->steuerungErmittlungUniqueRows()
            ->getAnzahlRows();

        return $anzahlRows;
    }
}
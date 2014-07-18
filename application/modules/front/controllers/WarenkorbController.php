<?php
/**
* Stellt den Inhalt des Warenkorbes dar
*
*
* @date 13.08.2013
* @file WarenkorbController.php
* @package front
* @subpackage controller
*/
class Front_WarenkorbController extends Zend_Controller_Action
{
    // Konditionen
    private $condition_rolle_kunde = 3;
    protected $condition_bereich_programme = 1;

    public $session;
    private $_model;
    private $_raintpl;
    private $realParams = null;
    private $gesamtRabattWarenkorb = 0;

    /** @var $_pimple Pimple_Pimple */
    private $_pimple = null;

    // Flags
    private $flag_show_buttons = true;
    protected $flagOrderData = false;

    protected $_flagBestandsbuchung = false;
    protected $rolleBenutzer = null;

    private $requestUrl = null;

    // Anzeigesprache
    protected $anzeigeSprache = null;

    protected $errorMessage = '';


    public function init()
    {
        // $this->session = new Zend_Session_Namespace('warenkorb');

        $request = $this->getRequest();
        $params = $request->getParams();
        $this->realParams = $params;

        // DIC
        $this->_pimple = $this->getInvokeArg('bootstrap')->getResource('Container');

        // Bestimmung Rolle des Benutzers
        $toolBenutzeranmeldung = new nook_ToolBenutzeranmeldung();
        $this->rolleBenutzer = $toolBenutzeranmeldung->killNobody()->getRolleId();

        $this->anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();

        $this->requestUrl = $this->view->url();
    }

    /**
     * Servicecontainer für die Vorbereitung der Tools und Tabellen
     *
     * @return Pimple_Pimple
     */
    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleBuchungsnummer'] = function()
        {
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple['tabelleHotelbuchung'] = function()
        {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function()
        {
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple['tabelleProgrammbuchung'] = function(){
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleAoCityBettensteuer'] = function(){
            return new Application_Model_DbTable_aoCityBettensteuer();
        };

        $pimple['toolAnzahlArtikelAktuellerWarenkorb'] = function($pimple)
        {
            return new nook_ToolAnzahlArtikelAktuellerWarenkorb($pimple);
        };

        $pimple['toolZaehler'] = function($pimple)
        {
            return new nook_ToolZaehler($pimple);
        };

        $pimple['toolProgrammbuchungen'] = function($pimple){
            return new nook_ToolProgrammbuchungen($pimple);
        };

        $pimple['tabelleCategories'] = function()
        {
            return new Application_Model_DbTable_categories(array('db' => 'hotels'));
        };

        return $pimple;
    }

    /************ Darstellung des Templates ***********/

    public function indexAction()
    {
        $buchungsdaten = $this->realParams;

        // Servicecontainer
        $pimple = $this->servicecontainer();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Kontrolle des Zugang zum Warenkorb
            $buchungsnummerDatensatz = $this->kontrolleZugangWarenkorb($raintpl, $pimple);

            // Controller
            $raintpl->assign('controller', $buchungsdaten['controller']);

            // Rolle des Benutzer
            $raintpl->assign('rolleId', $this->rolleBenutzer);

            /*** alternative Blöcke  und abprüfen ob eine Buchung vorliegt ***/
            $modelAlternativeBloecke = new Front_Model_WarenkorbAlternativeBloecke();
            $modelAlternativeBloecke->bestimmeParameterDerSuche();

            // Button Link der alternativen Blöcke
            $raintpl = $modelAlternativeBloecke->buttonLink($raintpl);

            // Model Warenkorb
            $modelCart = new Front_Model_Cart();

            // Gesamtpreis aller Buchungen
            $totalPrice = 0;

            // Anzahl der Buchungen
            $counterToOrder = 0;

            // Portalbereich
            $portalbereich = new Zend_Session_Namespace('portalbereich');
            $bereich = $portalbereich->bereich;

            // anzeigen aktiver Schritt im Bestellprozess
            $navigation = $modelCart->getAktiveStep(1, 10, $buchungsdaten);
            $raintpl->assign('breadcrumb', $navigation);

            // findet alle Buchungsnummern
            $buchungsnummer = $modelCart->findBuchungsnummer();

            /*** Bereich der Programme ***/
            $this->_bereichProgramme(
                $buchungsnummer,
                $counterToOrder,
                $modelCart,
                $totalPrice,
                $shoppingCartNestedProgramme
            );

            /*** Bereich Buchungspauschalen ***/
            $buchungspauschale = $this->bereichBuchungspauschale($shoppingCartNestedProgramme, $totalPrice);

            // wenn Anzahl Buchungspauschale > 0
            if($buchungspauschale['anzahl'] > 0){
                $raintpl->assign('buchungspauschale', $buchungspauschale);
            }

            // Bestandsbuchung Programme
            if (count($shoppingCartNestedProgramme) > 0) {
                // gebuchten Programme
                $shoppingCartNestedProgramme = $this->bestandsbuchungProgramme($shoppingCartNestedProgramme);

                // Informationsblock 'gebuchte Programme'
                $raintpl = $this->blockGebuchteProgramme($raintpl);
            }

            $raintpl->assign('shoppingCartNestedProgramme', $shoppingCartNestedProgramme);

            /*** Bereich der Uebernachtungen ***/

            // Kontrolle Kapazität und Preise der Hotelbuchung
            $this->_kontrolleKapazitaetUndPreisUebernachtung($buchungsnummer);

            // Hotelbuchungen ermitteln
            $shoppingCartHotelNested = $modelCart->findHotelBuchungNested($buchungsnummer, $pimple);

            // Bettensteuer
            $raintpl = $this->setzenTextZimmerZusatztexte($shoppingCartHotelNested, $raintpl);

            // Hotel Gruppenrabatt der Übernachtungen
            $gruppenRabatt = $this->errechnenGruppenrabattHotel($shoppingCartHotelNested);
            if($this->gesamtRabattWarenkorb > 0)
                $raintpl->assign('gruppenRabatt', $gruppenRabatt);


            // Anzahl und Gesamtpreis der Raten
            $ratenPreise = $modelCart->findHotelBuchungNestedPeise($shoppingCartHotelNested);
            $counterToOrder += $ratenPreise['count'];
            $totalPrice += $ratenPreise['totalPrice'];

            /**** vorhandene Zusatzprodukte ****/
            $shoppingCartZusatzprodukte = $modelCart->findZusatzprodukteHotel($buchungsnummer);

            /*** Kombination von Hotel und Hotelprodukt ***/
            $shoppingCartHotelNested = $modelCart->kombiniereHotelUndProdukte(
                $shoppingCartHotelNested,
                $shoppingCartZusatzprodukte
            );

            $raintpl->assign('shoppingCartUebernachtungNested', $shoppingCartHotelNested);

            // Anzahl und Gesamtpreis der Zusatzprodukte
            $zusatzproduktePreise = $modelCart->berechneGesamtpreis($shoppingCartZusatzprodukte, 'zusatzprodukt');
            $counterToOrder += $zusatzproduktePreise['count'];
            $totalPrice += $zusatzproduktePreise['totalPrice'];

            // Anzahl der Bestellungen
            $raintpl->assign('counterToOrder', $counterToOrder);

            // Gesamtpreis aller Bestellungen
            // $totalPrice = nook_ToolPreise::berechneGesamtpreisAllerArtikelImWarenkorb($totalPrice);
            $raintpl->assign('totalPrice', $totalPrice);
            $raintpl->assign('gesamtRabattWarenkorb', $this->gesamtRabattWarenkorb);

            // Texte Bettensteuer
            $raintpl = $this->texteBettensteuer($raintpl, $shoppingCartHotelNested, $pimple);

            // Preis mit Rabatt
            if($this->gesamtRabattWarenkorb > 0){
                $neuerPreis = $totalPrice - $this->gesamtRabattWarenkorb;
                $raintpl->assign('neuerPreis', $neuerPreis);
            }

            // Flag: Anzeigen der Button
            $raintpl->assign('flag_show_buttons', $this->flag_show_buttons);

            // Flag: Anzeige Block Orderdata
            $raintpl->assign('flagOrderData', $this->flagOrderData);


            // Kontrolle ob alle Artikel im Warenkorb storniert
            $raintpl = $this->kontrolleStornierung($buchungsnummerDatensatz, $raintpl);

            // Kontrolle ob der Warenkorb eine Bestandsbuchung ist
            $raintpl = $this->kontrolleBestandsbuchung($buchungsnummerDatensatz, $raintpl);

            // Flag mehrere bereits gebuchte Programme
            $raintpl = $this->kontrolleMehrereBereitsGebuchteProgramme($raintpl, $shoppingCartNestedProgramme);

            // Kontrolle ob alle Artikel Programmbuchung bereits gebucht wurden
            $raintpl = $this->anzahlArtikelImWarenkorbNachStatus($raintpl, $shoppingCartNestedProgramme);

            // Farbvariante der Button im Warenkorb
            $raintpl = $this->bestimmeFarbvarianteButtonWarenkorb($raintpl);

            // Block Personenanzahl sichtbar / unsichtbar
            $raintpl = $this->blockPersonenanzahl($raintpl);

            // Fehlermeldung
            if($this->errorMessage != '')
                $raintpl->assign('errorMessage', $this->errorMessage);

            $this->view->content = $raintpl->draw("Front_Warenkorb_Index", true);

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Wenn Hotelbuchungen vorliegen und die Städte der Hotels eine Sonderabgabe haben, dann werden im Warenkorb Informationstexte angezeigt.
     *
     * + Titel der Bettensteuer
     * + Kurztext der Bettensteuer
     *
     * @param $raintpl
     * @param $shoppingCartHotelNested
     */
    protected function texteBettensteuer(RainTPL $raintpl,array $shoppingCartHotelNested,Pimple_Pimple $pimple)
    {
        $frontModelBettensteuerStadt = new Front_Model_BettensteuerStadt();
        $frontModelBettensteuerStadt
            ->setTabelleAoCityBettensteuer($pimple['tabelleAoCityBettensteuer']);

        $bettenSteuertexte = array();
        $j = 0;

        for($i=0; $i < count($shoppingCartHotelNested); $i++){

            $cityId = $shoppingCartHotelNested[$i]['cityId'];

            $flagHasBettensteuer = $frontModelBettensteuerStadt
                ->setCityId($cityId)
                ->setSpracheId($this->anzeigeSprache)
                ->steuerungErmittlungBettensteuerStadt()
                ->hasBettensteuer();

            if($flagHasBettensteuer){
                $title = $frontModelBettensteuerStadt->getTitleBettensteuer();
                if($title)
                    $bettenSteuertexte[$j]['title'] = $title;

                $kurztext = $frontModelBettensteuerStadt->getKurztextBettensteuer();
                if($kurztext)
                    $bettenSteuertexte[$j]['kurztext'] = $kurztext;

                $j++;
            }
        }

        $raintpl->assign('bettensteuer', $bettenSteuertexte);

        return $raintpl;
    }

    /**
     * Setzt den Zusatztext für die Zimmer einer Hotelbuchung
     *
     * @param $shoppingCartHotelNested
     * @param $raintpl
     * @return object
     */
    private function setzenTextZimmerZusatztexte($shoppingCartHotelNested, $raintpl)
    {
        if(count($shoppingCartHotelNested) > 0 ){
            $pimple = new Pimple_Pimple();
            $pimple['tabelleTextbausteine'] = function(){
                return new Application_Model_DbTable_textbausteine();
            };

            $toolStandardtexte = new nook_ToolStandardtexte();
            $zimmerZusatztext = $toolStandardtexte
                ->setPimple($pimple)
                ->setBlockname('zimmerZusatztext')
                ->steuerungErmittelnText()->getText();

            $raintpl->assign('zimmerZusatztext', $zimmerZusatztext);
        }

        return $raintpl;
    }

    /**
     * Fügt den gebuchten Übernachtungen den Gruppenrabatt hinzu
     *
     * @param $shoppingCartHotelNested
     * @return int
     */
    private function errechnenGruppenrabattHotel($shoppingCartHotelNested)
    {
        // Abbruch wenn keine Übernachtungen
        if(count($shoppingCartHotelNested) < 1)
            return;

        $pimple = new Pimple_Pimple();

        $pimple['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProperties'] = function(){
            return new Application_Model_DbTable_properties(array('db' => 'hotels'));
        };

        $pimple['tabelleCategories'] = function(){
            return new Application_Model_DbTable_categories(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaRatesConfig'] = function(){
            return new Application_Model_DbTable_otaRatesConfig(array('db' => 'hotels'));
        };

        $pimple['tabelleOtaPrices'] = function(){
            return new Application_Model_DbTable_otaPrices(array('db' => 'hotels'));
        };

        $pimple['toolZaehler'] = function($pimple){
            return new nook_ToolZaehler($pimple);
        };

        $pimple['toolHotelbuchungenWarenkorb'] = function($pimple){
            return new nook_ToolHotelbuchungenWarenkorb($pimple);
        };

        // Ermitteln und hinzufügen Gruppenrabatt
        $frontModelWarenkorbGruppenrabatt = new Front_Model_WarenkorbGruppenrabatt($pimple);
        $gruppenRabatt = $frontModelWarenkorbGruppenrabatt
            ->steuerungErmittlungGruppenRabatt()
            ->getGruppenRabatt();

        $gesamtRabattWarenkorb = $frontModelWarenkorbGruppenrabatt->getGesamtRabattWarenkorb();
        $this->gesamtRabattWarenkorb = $gesamtRabattWarenkorb;

        return $gruppenRabatt;
    }

    /**
     * Ermittelt einen Flag, wenn mehrere Programme bereits gebucht sind
     *
     * @param $raintpl
     * @param $shoppingCartNestedProgramme
     * @return object
     */
    private function kontrolleMehrereBereitsGebuchteProgramme($raintpl, $shoppingCartNestedProgramme)
    {
        $anzahlBereitsGebuchterProgramme = 0;

        for($i=0; $i < count($shoppingCartNestedProgramme); $i++){
            foreach($shoppingCartNestedProgramme[$i]['programme'] as $programm){
                if( ($programm['status'] >= 4) and ($programm['anzahl'] > 0) )
                    $anzahlBereitsGebuchterProgramme++;
            }
        }

        if($anzahlBereitsGebuchterProgramme < 2)
            $raintpl->assign('flagMehrereBereitsGebuchterProgramme', 1);
        else
            $raintpl->assign('flagMehrereBereitsGebuchterProgramme', 2);

        return $raintpl;
    }

    /**
     * Zählt die Artikel der Programme im Warenkorb
     *
     * + Anzahl alle Artikel im Warenkorb
     * + Anzahl neue Artikel im Warenkorb
     * + Anzahl bereits gebuchter Programme
     *
     * @param $raintpl
     * @param $shoppingCartNestedProgramme
     * @return mixed
     */
    private function anzahlArtikelImWarenkorbNachStatus($raintpl)
    {
        $frontModelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer();
        $frontModelZaehlerBuchungsnummer->findBuchungsnummerUndZaehler();
        $buchungsnummer = $frontModelZaehlerBuchungsnummer->getBuchungsnummer();
        $zaehler = $frontModelZaehlerBuchungsnummer->getZaehler();

        $toolAnzahlArtikelAktuellerWarenkorb = new nook_ToolAnzahlArtikelAktuellerWarenkorb();
        $toolAnzahlArtikelAktuellerWarenkorb
            ->setBuchungsnummer($buchungsnummer)
            ->setZaehler($zaehler);

        // Anzahl alle Artikel im Warenkorb
        $toolAnzahlArtikelAktuellerWarenkorb
            ->setStatusArtikelWarenkorb(0)
            ->steuerungErmittelnAnzahlArtikel()
            ->getAnzahlAllerArtikelImWarenkorb();

        // Anzahl neue Artikel im Warenkorb
        $toolAnzahlArtikelAktuellerWarenkorb
            ->setStatusArtikelWarenkorb(1)
            ->steuerungErmittelnAnzahlArtikel();

        $anzahlNeuerProgramme = $toolAnzahlArtikelAktuellerWarenkorb->getAnzahlProgrammbuchungen();
        $raintpl->assign('anzahlNeuerProgramme', $anzahlNeuerProgramme);

        $anzahlNeuerHotelbuchungen = $toolAnzahlArtikelAktuellerWarenkorb->getAnzahlHotelbuchungen();
        $raintpl->assign('anzahlNeuerHotelbuchungen', $anzahlNeuerHotelbuchungen);

        $anzahlNeuerArtikel = $anzahlNeuerProgramme + $anzahlNeuerHotelbuchungen;
        $raintpl->assign('anzahlNeuerArtikel', $anzahlNeuerArtikel);

        // Anzahl bereits gebuchter Programme
        $anzahlBereitsGebuchterprogramme = $toolAnzahlArtikelAktuellerWarenkorb
            ->setStatusArtikelWarenkorb(4)
            ->steuerungErmittelnAnzahlArtikel()
            ->getAnzahlProgrammbuchungen();

        return $raintpl;
    }

    /**
     * Ermittelt ob ein Warenkorb storniert wurde
     *
     * + $flagStornierung = 2, Warenkorb wurde storniert
     * + $flagStornierung = 1, Warenkorb wurde nicht storniert
     *
     * @param $buchungsnummerDatensatz
     * @param $raintpl
     * @return mixed
     */
    private function kontrolleStornierung($buchungsnummerDatensatz, $raintpl)
    {
        $aktuellerZaehler = 0;

        $toolStornierung = new nook_ToolStornierung();
        $flagStornierung = $toolStornierung
            ->setBuchungsnummer($buchungsnummerDatensatz['buchungsnummerId'])
            ->setZaehler($aktuellerZaehler)
            ->anzahlArtikelImWarenkorb()
            ->isStornierung();

        if($flagStornierung)
            $raintpl->assign('flagStornierung',2);
        else
            $raintpl->assign('flagStornierung',1);

        return $raintpl;
    }

    /**
     * Kontrolliert ob der Warenkorb eine Bestandsbuchung ist
     *
     * + Kontrolle ob Warenkorb eine Bestandsbuchung ist
     * + Kontrolle ob der Warenkorb Artikel beinhaltet die einer Bestandsbuchung entstammen
     *
     * @param $buchungsnummerDatensatz
     * @param $raintpl
     * @return mixed
     */
    private function kontrolleBestandsbuchung($buchungsnummerDatensatz, $raintpl)
    {
        $toolBestandsbuchung = new nook_ToolBestandsbuchungKontrolle();
        $flagBestandsbuchung = $toolBestandsbuchung
            ->kontrolleBestandsbuchung()
            ->getKontrolleBestandsbuchung();

        $raintpl->assign('flagBestandsbuchung', $flagBestandsbuchung);

        return $raintpl;
    }

    /**
     * Ermittelt den Text des Informationsblockes 'gebuchte Programme'
     * + Kontrolle ob eine Bestandsbuchung Programme vorliegt
     * + Ermitteln des Textes des Bausteines
     * + Rückgabe des Textes , wenn vorhanden
     * + fügt Buchungshinweis hinzu
     * + Rückgabe Template
     *
     * @return string
     */
    private function blockGebuchteProgramme($raintpl)
    {

        $this->_pimple['tabelleTextbausteine'] = function ($c) {
            return new Application_Model_DbTable_textbausteine();
        };

        $this->_pimple['toolBestandsbuchungKontrolle'] = function ($c) {
            return new nook_ToolBestandsbuchungKontrolle();
        };

        $this->_pimple['toolStandardtexte'] = function ($c) {
            return new nook_ToolStandardtexte();
        };

        $this->_pimple['toolZeilenumbruch'] = function ($c) {
            return new nook_ToolZeilenumbruch();
        };

        $shadow = new Front_Model_WarenkorbBlockGebuchteProgrammeShadow($this->_pimple);
        $zaehler = $shadow->kontrolleBestandsbuchung();

        // wenn keine Programm - Bestandsbuchung
        if (empty($zaehler)) {
            return $raintpl;
        }

        // Informationstext der gebuchten Programme
        $informationGebuchteProgramme = $shadow->getStandardtext();
        $informationGebuchteProgrammeArray = $shadow->zeilenumbruch($informationGebuchteProgramme);

        // Buchungshinweis
        $buchungshinweisArray = $shadow->buchungshinweis();
        if ((is_array($buchungshinweisArray)) and (count($buchungshinweisArray) > 0)) {
            $raintpl->assign('blockBuchungshinweis', $buchungshinweisArray);
        }

        if (empty($informationGebuchteProgramme)) {
            return $raintpl;
        } else {
            $raintpl->assign('blockGebuchteProgramme', $informationGebuchteProgrammeArray);

            return $raintpl;
        }
    }

    /**
     * Ermitteln des Buchungsdatensatzes
     *
     * + wenn noch keine HOB Nummer vergeben, dann wird die HOB Nummer = 0 gesetzt
     *
     * @return array
     */
    private function ermittelnBuchungsdatensatz()
    {
        $modelInformationBenutzerbuchung = Front_Model_InformationBenutzerBuchung::getInstance($this->_pimple);
        $buchungsdaten = $modelInformationBenutzerbuchung
            ->generateBuchungsnummerKundenId()
            ->getBuchungsdaten();

        return $buchungsdaten;
    }

    /**
     * Kontrolliert die Kapazität und die Preise
     * der Hotelbuchungen eines Warenkorbes.
     * + Wenn die Kapazität der Rate des Hotels erschöpft ist wird der Status 6 => 'ausgebucht' im Datensatz gesetzt
     * + Wenn sich der Preis geändert hat wird ein Hinweis im Warenkorb angezeigt.
     */
    private function _kontrolleKapazitaetUndPreisUebernachtung($buchungsnummer)
    {
        $this->_pimple['tabelleHotelbuchung'] = function ($c) {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $warenkorbKontrolleUebernachtungKapazitaet = new Front_Model_WarenkorbKontrolleHotelkapazitaet();
        $warenkorbKontrolleUebernachtungKapazitaet
            ->setBuchungsnummer($buchungsnummer[0]['id'])
            ->setPimple($this->_pimple)
            ->ermittelnHotelbuchungen();

        return;
    }

    /**
     * Ermittelt die gebuchten Programme
     *
     * @param $buchungsnummern
     * @param $counterToOrder
     * @param $modelCart
     * @param $totalPrice
     * @param $shoppingCartNestedProgramme
     */
    private function _bereichProgramme(
        $buchungsnummern,
        &$counterToOrder,
        $modelCart,
        &$totalPrice,
        &$shoppingCartNestedProgramme
    )
    {
        $modelCartProgramme = new Front_Model_CartProgramme();

        $modelCartProgramme->setBuchungsnummernEinesKunden($buchungsnummern);
        $modelCartProgramme->findProgrammeEinesKunden();
        $shoppingCartNestedProgramme = $modelCartProgramme->getGebuchteProgrammeNested();

        // Fehlermeldung von $modelCartProgramme
        $this->errorMessage .= $modelCartProgramme->getErrorMessage();

        // Vertragspartner
        $shoppingCartNestedProgramme = $this->vertragspartnerProgramme($shoppingCartNestedProgramme);

        $counterToOrder += count($shoppingCartNestedProgramme);
        $programmePreise = $modelCart->berechneGesamtpreis($shoppingCartNestedProgramme, 'programmbuchung');
        $counterToOrder += $programmePreise['count'];
        $totalPrice += $programmePreise['totalPrice'];

        return;
    }

    /**
     * Ermittelt den Vertragspartner der gewählten Programme in den Städten
     *
     * @param $shoppingCartNestedProgramme
     * @return mixed
     */
    protected function vertragspartnerProgramme($shoppingCartNestedProgramme)
    {
        $frontModelVertragspartner = new Front_Model_Vertragspartner();
        $frontModelVertragspartner->setBereich($this->condition_bereich_programme);

        // Städte
        for($i=0; $i < count($shoppingCartNestedProgramme); $i++){
            // Programme in den Städten
            for($j=0; $j < count($shoppingCartNestedProgramme[$i]['programme']); $j++){

                // Adresse des Vertragspartners
                $adresse = $frontModelVertragspartner
                    ->setProgrammId($shoppingCartNestedProgramme[$i]['programme'][$j]['programmdetails_id'])
                    ->steuerungErmittlungAdresseVertragspartner()
                    ->getAdresse();

                $shoppingCartNestedProgramme[$i]['programme'][$j]['vertragspartner'] = $adresse;
            }
        }

        return $shoppingCartNestedProgramme;
    }

    /**
     * Berechnung der Buchungspauschalen
     *
     * @param $shoppingCartNestedProgramme
     * @param $totalPrice
     * @param $anzahlBuchungspauschalen
     * @param $preisBuchungspauschale
     */
    private function bereichBuchungspauschale(&$shoppingCartNestedProgramme, &$totalPrice)
    {
        $buchungspauschale = array();

        $this->_pimple['tabelleProgrammdetails'] = function(){
            return new Application_Model_DbTable_programmedetails();
        };

        $this->_pimple['tabellePreiseBeschreibung'] = function(){
            return new Application_Model_DbTable_preiseBeschreibung();
        };

        $this->_pimple['viewBuchungspauschalen'] = function(){
            return new Application_Model_DbTable_viewBuchungspauschalen();
        };

        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $modelBuchungspauschale = new Front_Model_Buchungspauschale();

        // Anzahl Buchungspauschalen
        $anzahlBuchungspauschalen = $modelBuchungspauschale
            ->setBuchungsnummer($buchungsnummer)
            ->setPimple($this->_pimple)
            ->berechneAnzahlBuchungspauschalen();

        $buchungspauschale['anzahl'] = $anzahlBuchungspauschalen;

        // Einzelpreis Buchungspauschale
        $buchungspauschale['preis'] = $modelBuchungspauschale->getPreisBuchungspauschale();

        // Hinzufügen zum Gesamtpreis, die Buchungspauschalen
        $gesamtpreisBuchungspauschalen = $modelBuchungspauschale->getPreisBuchungspauschalen();
        $buchungspauschale['gesamtpreis'] = $gesamtpreisBuchungspauschalen;
        $totalPrice += $gesamtpreisBuchungspauschalen;

        // Name der Preisvariante
        $buchungspauschale['preisvarianteName'] = $modelBuchungspauschale->getNamePreisvariante();

        return $buchungspauschale;
    }

    /**
     * Kontrolliert die Stornofristen bereits gebuchter Programme
     *
     * @param $shoppingCartNestedProgramme
     */
    private function bestandsbuchungProgramme($shoppingCartNestedProgramme)
    {
        $toolBestandsbuchungKontrolle = new nook_ToolBestandsbuchungKontrolle();
        $isBestandsbuchung = $toolBestandsbuchungKontrolle
            ->kontrolleBestandsbuchung()
            ->getKontrolleBestandsbuchung();

        if ($isBestandsbuchung) {
            $buchungsnummer = $toolBestandsbuchungKontrolle->getBuchungsnummer();
            $zaehler = $toolBestandsbuchungKontrolle->getZaehler();

            $shoppingCartNestedProgramme = $this->kontrolleDerStornofristenArtikelImWarenkorb(
                'programmbuchung',
                $buchungsnummer,
                $zaehler,
                $shoppingCartNestedProgramme
            );
        }

        return $shoppingCartNestedProgramme;
    }

    /**
     * Kontrolliert die Stornofristen der Programme im Warenkorb
     *
     * @param $bereich
     * @param $buchungsnummer
     * @param $zaehler
     * @param $shoppingCartNestedProgramme
     * @return mixed
     */
    private function kontrolleDerStornofristenArtikelImWarenkorb(
        $bereich,
        $buchungsnummer,
        $zaehler,
        $shoppingCartNestedProgramme
    ) {

        $this->_pimple['tabelleProgrammdetails'] = function () {
            return new Application_Model_DbTable_programmedetails();
        };

        $this->_pimple['tabelleProgrammbuchung'] = function () {
            return new Application_Model_DbTable_programmbuchung();
        };

        // Factory Pattern
        $modelAllgemeineStornofristen = Front_Model_AllgemeineStornofristen::getInstance($bereich, $buchungsnummer);

        $modelStornofristenProgrammbuchung = $modelAllgemeineStornofristen->generateClass();
        $modelStornofristenProgrammbuchung->setPimple($this->_pimple);

        for ($i = 0; $i < count($shoppingCartNestedProgramme); $i++) {
            for ($j = 0; $j < count($shoppingCartNestedProgramme[$i]['programme']); $j++) {
                $programmdetailsId = $shoppingCartNestedProgramme[$i]['programme'][$j]['programmdetails_id'];
                $buchungstabelleId = $shoppingCartNestedProgramme[$i]['programme'][$j]['id'];

                $inStornofrist = $modelStornofristenProgrammbuchung
                    ->setProgrammdetailsId($programmdetailsId)
                    ->setBuchungstabelleId($buchungstabelleId)
                    ->steuerungKontrolleStornofrist()
                    ->isInStornofrist();

                $shoppingCartNestedProgramme[$i]['programme'][$j]['inStornofrist'] = $inStornofrist;
            }
        }

        return $shoppingCartNestedProgramme;
    }

    /**
     * Ermittelt die Anzahl der Artikel im aktuellen Warenkorb
     *
     * @param $buchungVorhanden
     */
    private function checkVorhandeneBuchungen(Pimple_Pimple $pimple, $buchungsNummerId, $zaehlerId)
    {
        /** @var $toolAnzahlArtikelAktuellerWarenkorb nook_ToolAnzahlArtikelAktuellerWarenkorb */
        $toolAnzahlArtikelAktuellerWarenkorb = $pimple['toolAnzahlArtikelAktuellerWarenkorb'];
        $anzahlArtikelImWarenkorb = $toolAnzahlArtikelAktuellerWarenkorb
            ->setBuchungsnummer($buchungsNummerId)
            ->setZaehler($zaehlerId)
            ->steuerungErmittelnAnzahlArtikel()
            ->getAnzahlAllerArtikelImWarenkorb();

        return $anzahlArtikelImWarenkorb;
    }

    /**
     * Löscht einen Eintrag des Warenkorbes
     *
     * @return void
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['delete_x']);
        unset($params['delete_y']);

        try {
            // löschen Programm Eintrag im Warenkorb
            if (array_key_exists('bereich', $params) and $params['bereich'] == 1) {
                $modelProgramme = new Front_Model_CartProgramme();
                $modelProgramme->deleteItemWarenkorb($params['bereich'], $params['idBuchungstabelle']);
            }

            // löschen Übernachtungseintrag im Warenkorb
            if (array_key_exists('bereich', $params) and $params['bereich'] == 6) {

                // verändern Verfügbarkeit Raten
                $modelHotelbuchungVerfuegbarkeitRaten = new Front_Model_WarenkorbHotelbuchungRatenverfuegbarkeit();
                $buchungstabelleId = array( $params['idBuchungstabelle'] );
                // Übergabe der Buchungs ID
                $modelHotelbuchungVerfuegbarkeitRaten->setArrayBuchungsId($buchungstabelleId);
                // herstellen Originalzustand
                $modelHotelbuchungVerfuegbarkeitRaten->setVeraenderungVerfuegbarkeitRaten(false);

                // löschen Buchungsdatensatz und XML
                $modelHotelbuchung = new Front_Model_CartHotel();
                $modelHotelbuchung->deleteItemWarenkorb($params['bereich'], $params['idBuchungstabelle']);
            }

            // löschen Zusatzprodukt
            if (array_key_exists('idZusatzprodukt', $params)) {
                $modelZustzprodukte = new Front_Model_Zusatzprodukte();
                $modelZustzprodukte->loeschenEinzelnesZusatzprodukt($params);
            }

            // umlenken auf Action 'index'
            $this->_redirector = $this->_helper->getHelper('Redirector');
            $this->_redirector->gotoUrl('/front/Warenkorb/index');
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Kontrolliert den Zugang zum Warenkorb
     *
     * + Kontrolle vorhandensein Datensatz Datensatz 'tbl_buchungsnummer'
     * + Kontrolle vorhandensein Artikel im Warenkorb
     *
     * @param $raintpl
     * @param $pimple
     * @return array
     */
    protected function kontrolleZugangWarenkorb(RainTPL $raintpl,Pimple_Pimple $pimple)
    {
        // Buchungsdatensatz / Kundeninformation
        $buchungsnummerDatensatz = $this->ermittelnBuchungsdatensatz();

        // wenn kein Buchungsdatensatz vorhanden ist
        if (empty($buchungsnummerDatensatz))
            $this->_redirect("/front/login");

        // Anzeige Daten
        $raintpl->assign('kundenId', $buchungsnummerDatensatz['kundenId']);
        $raintpl->assign('kompletteBuchungsnummer', $buchungsnummerDatensatz['kompletteBuchungsnummer']);
        $raintpl->assign('buchungsnummerZaehler', $buchungsnummerDatensatz['zaehler']);

        // Kontrolle ob Artikel im Warenkorb vorhanden sind ???
        $anzahlArtikelImWarenkorb = $this->checkVorhandeneBuchungen(
            $pimple,
            $buchungsnummerDatensatz['buchungsnummerId'],
            $buchungsnummerDatensatz['zaehler']
        );

        if ($anzahlArtikelImWarenkorb == 0)
            $this->_redirect("/front/login");return $buchungsnummerDatensatz;

        return $buchungsnummerDatensatz;
    }

    /**
     * Speichern der Programme
     *
     * @param $__params
     */
    private function _speichernProgramme($__params)
    {
        // Order - Daten aus Namespace
        $order = new Zend_Session_Namespace('order');
        foreach ($order as $key => $value) {
            $__params[$key] = $value;
        }
        Zend_Session::namespaceUnset('order');

        if (array_key_exists('ProgrammId', $__params)) {
            $this->_model->setOrderItems($__params);
            $this->_model->saveOrder();
        }

        return;
    }

    /**
     * Trägt die Kundendaten in die
     * Tabelle 'tbl_adressen' ein
     *
     * @return void
     */
    public function personaldataAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $model1 = new Front_Model_WarenkorbStep1();
            $model2 = new Front_Model_WarenkorbStep2();

            // Breadcrumb
            $aktiveStep = $model1->setAktiveStep(1, 11, $params);
            $raintpl->assign($aktiveStep);

            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            // Kontrolle auf Superuser
            /** @var $modelSuperuser nook_ToolKontrolleSuperuser */
            $modelSuperuser = new nook_ToolKontrolleSuperuser();
            $modelSuperuser->kontrolleSuperuserMitPasswort($params['password']);

            // Aktualisierung des Warenkorbes
            $aktualisierung = new Zend_Session_Namespace('aktualisierung');
            $warenkorbAktualisierung = $aktualisierung->getIterator();
            $kontrolleAktualisierungWarenkorb = nook_ToolSession::warenkorbAktualisieren(
                $warenkorbAktualisierung['zeit']
            );

            // Rücksprung auf Warenkorb
            if (!$kontrolleAktualisierungWarenkorb) {
                $redirector = $this->_helper->getHelper('Redirector');
                $redirector->gotoSimple('index', 'warenkorb', 'front');
            }

            // ermitteln Kunden ID
            $kundenId = $model1->getKundenId();
            $raintpl->assign('kundenId', $kundenId);

            // Finden Länderkennung
            $countries = $model2->findCountries($this->session->country);
            $raintpl->assign('country', $countries);

            $titles = $model2->getTitle($this->session->title);
            $raintpl->assign('titles', $titles);

            $personalData = $model2->getPersonalData();
            $raintpl->assign('personalData', $personalData);

            $raintpl->assign('doubleMailAdress', 1);

            // speichern der Personendaten
            if (array_key_exists('submitPersonaldata', $params)) {

                // Kontrolle ob Mailadresse bereits vorhanden
                $isDoubleEmail = $model2->controlIfEmailIsDouble($params['email']);
                // wenn Neukunde
                if (empty($isDoubleEmail)) {
                    $this->_speichernNeukunde($params, $model2, $modelSuperuser);

                    $this->_redirect("/front/warenkorb/orderdata-warenkorb/");
                }
                // Reaktion wenn Mailadresse schon vorhanden
                else {
                    $raintpl = $this->_reaktionDoppelteMailadresse($raintpl, $params, $model2);


                }
            }

            $this->view->content = $raintpl->draw("Front_Warenkorb_Personaldata", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Reaktion wenn die Mailadresse schon vorhanden ist.
     *
     * @param $raintpl
     * @param $params
     * @param $model2
     * @return object
     */
    private function _reaktionDoppelteMailadresse($raintpl, $params, $model2)
    {
        $raintpl->assign('doubleMailAdress', 2);
        $raintpl->assign('personalData', $params);
        $countries = $model2->findCountries($params['country']);
        $raintpl->assign('country', $countries);
        $dateofbirth_day = $model2->dateofbirth_day($params['dateofbirth_day'], true);
        $raintpl->assign('dateofbirth_day', $dateofbirth_day);

        $dateofbirth_month = $model2->dateofbirth_month($params['dateofbirth_month'], true);
        $raintpl->assign('dateofbirth_month', $dateofbirth_month);

        $dateofbirth_year = $model2->dateofbirth_year($params['dateofbirth_year'], true);
        $raintpl->assign('dateofbirth_year', $dateofbirth_year);

        $titles = $model2->getTitle($this->session->title);
        $raintpl->assign('titles', $titles);

        return $raintpl;
    }

    /**
     * Speichern eines Neukunden
     *
     * + festlegen Rolle 'Kunde'
     * + setzen der Rolle im Session_Namespace 'Auth'
     * + eintragen personendaten
     * + anlegen XML - Block Kundendaten
     *
     * @param $params
     * @param $model2
     * @param $modelSuperuser
     */
    private function _speichernNeukunde($params,Front_Model_WarenkorbStep2 $model2, $modelSuperuser)
    {
        unset($params['submitPersonaldata']);

        // Rolle 'kunde'
        $params['status'] = $this->condition_rolle_kunde;

        // speichern Peronendaten
        $kundenId = $model2->setPersonaldata($params);

        // setzen der Rolle in der Session
        $sessionNamespaceAuth = new Zend_Session_Namespace('Auth');
        $sessionNamespaceAuth->role_id = $this->condition_rolle_kunde;

        // wenn nötig zuordnen der Superuser ID zum Kunden
        $modelSuperuser->zuordnungBuchungZuSuperuser();

        // XML Block Kundendaten
        $warenkorbKundendatenXML = new Front_Model_WarenkorbPersonalDataXML();
        $warenkorbKundendatenXML->setKundenDaten($kundenId, $params);
        $warenkorbKundendatenXML->saveKundenDatenXML();

        return;
    }

    public function emailcontrolAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model2 = new Front_Model_WarenkorbStep2();
            $doubleEmail = $model2->controlIfEmailIsDouble($params['email']);

            $response = array(
                'doubleEmail' => $doubleEmail
            );

            echo "[" . json_encode($response) . "]";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * ????
     * @param $__params
     */
    private function _buildStandardsStep1($__params)
    {
        $step = 1;
        if (array_key_exists('step', $__params)) {
            $step = $__params['step'];
        }

        // anzeigen aktiver Schritt im Bestellprozess
        $aktiveStep = $this->_model->setAktiveStep($step);
        $this->_raintpl->assign($aktiveStep);

        $hotelzimmer = new Front_Model_WarenkorbHotelbuchung();
        $hotelzimmer->setLanguage();

        // gemerkte Hotelzimmer der Buchung
        $shoppingCartUebernachtung = $hotelzimmer->getShoppingCartHotelzimmer();
        $this->_raintpl->assign('shoppingCartUebernachtung', $shoppingCartUebernachtung);

        // gemerkte Programme der Buchung
        $shoppingCartProgramme = $this->_model->getShoppingCartProgramme();
        $this->_raintpl->assign('shoppingCartProgramme', $shoppingCartProgramme);

        // gemerkte Zusatzprodukte der Buchung
        $shoppingCartZusatzprodukte = $this->_model->getShoppingCartZusatzprodukte();
        $this->_raintpl->assign('shoppingCartZusatzprodukte', $shoppingCartZusatzprodukte);

        // Counter für freischalten nächster Schritt
        $counter = 0;
        $counter += count($shoppingCartUebernachtung);
        $counter += count($shoppingCartProgramme);
        $counter += count($shoppingCartZusatzprodukte);

        $this->_raintpl->assign('counterToOrder', $counter);

        // Gesamtpreis
        $totalPrice = nook_Tool::getTotalPriceBookingCart('Programme', $shoppingCartProgramme);
        if (!empty($shoppingCartUebernachtung)) {
            $totalPrice += nook_Tool::getTotalPriceBookingCart('Uebernachtung', $shoppingCartUebernachtung);
        }
        $totalPrice += nook_Tool::getTotalPriceBookingCart('Zusatzprodukte', $shoppingCartZusatzprodukte);
        $totalPrice = number_format($totalPrice, 2);

        $this->_raintpl->assign('totalPrice', $totalPrice);

        $kundenId = $this->_model->getKundenId();
        $this->_raintpl->assign('kundenId', $kundenId);

        return;
    }

    public function confirmAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $model5 = new Front_Model_WarenkorbStep5();
            $model4 = new Front_Model_WarenkorbStep4();
            // $model1 = new Front_Model_WarenkorbStep1();

            if (!array_key_exists('confirmcode', $params)) {
                throw new nook_Exception($model5->error_no_confirmcode_exists);
            }

            $model5->confirmCode = $params['confirmcode'];

            $personalData = $model5->getPersonalData();
            $raintpl->assign('personalData', $personalData);

            $model5->setStatusUser($personalData['status']);

            $shoppingCart = $model5->getShoppingCart($personalData['id']);
            $raintpl->assign('shoppingCart', $shoppingCart);

            $bankData = $model5->getBankData();

            $bankData['paymentNumber'] = $model5->findPaymentNumber($params['confirmcode']);

            $raintpl->assign('bankData', $bankData);

            $model5->setTableZahlungBestaetigt($params['confirmcode']);

            $model4->condition_send_mail_to_user = 1;
            $model4->sendOrderMail($personalData, $shoppingCart, $bankData);

            // Mail an Programmanbieter
            $model4->mailsToProgramSuppliers($personalData['id'], $personalData, $shoppingCart);

            // Mail an Kunde
            $model4->mailWithVoucherAndBill($personalData['id'], $personalData, $bankData, $shoppingCart);

            $model5->kundenId = $personalData['id'];
            $model5->setStatusBookingSendToSupport();

            $model5->logBookingIsConfirmed($shoppingCart);

            $this->view->content = $raintpl->draw("Front_Warenkorb_Confirmmessage", true);
            // Zend_Session::destroy();
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    public function captchacontrolAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model3 = new Front_Model_WarenkorbStep3();
            $controlCaptcha = $model3->evaluateCaptcha($params);

            $response = array(
                'noCorrectCaptcha' => $controlCaptcha
            );

            echo "[" . json_encode($response) . "]";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * löschen aller Produkte im Warenkorb
     *
     * + löschen Hotelbuchungen
     * + löschen Produktbuchungen
     * + löschen Programmbuchungen
     * + löschen XML Buchung
     * + setzen 'tbl_buchungsnummer'.'status' = 10
     * + vergibt neue Buchungsnummer
     *
     * @return void
     */
    public function loeschenAllesAction()
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            // Shadow
            $shadowFrontModelWarenkorbLoeschenAlles = new Front_Model_WarenkorbLoeschenAllesShadow();

            if (!$this->_pimple->offsetExists('tabelleBuchungsnummer')) {
                $this->_pimple['tabelleBuchungsnummer'] = function ($c) {
                    return new Application_Model_DbTable_buchungsnummer();
                };
            }

            if (!$this->_pimple->offsetExists('tabelleProgrammbuchung')) {
                $this->_pimple['tabelleProgrammbuchung'] = function ($c) {
                    return new Application_Model_DbTable_programmbuchung();
                };
            }

            if (!$this->_pimple->offsetExists('tabelleHotelbuchung')) {
                $this->_pimple['tabelleHotelbuchung'] = function ($c) {
                    return new Application_Model_DbTable_hotelbuchung();
                };
            }

            if (!$this->_pimple->offsetExists('tabelleProduktbuchung')) {
                $this->_pimple['tabelleProduktbuchung'] = function ($c) {
                    return new Application_Model_DbTable_produktbuchung();
                };
            }

            if (!$this->_pimple->offsetExists('tabelleXmlBuchung')) {
                $this->_pimple['tabelleXmlBuchung'] = function ($c) {
                    return new Application_Model_DbTable_xmlBuchung();
                };
            }

            if (!$this->_pimple->offsetExists('tabelleProgrammdetails')) {
                $this->_pimple['tabelleProgrammdetails'] = function ($c) {
                    return new Application_Model_DbTable_programmedetails();
                };
            }

            // DIC
            $shadowFrontModelWarenkorbLoeschenAlles->setPimple($this->_pimple);

            // Model ZaehlerBuchungsnummer
            $modelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer($this->_pimple);
            $shadowFrontModelWarenkorbLoeschenAlles->setModelZaehlerBuchungsnummer($modelZaehlerBuchungsnummer);

            // ermitteln Zaehler
            $zaehler = $shadowFrontModelWarenkorbLoeschenAlles->getZaehler();

            // Mails an die Programmanbieter
            if ($zaehler > 1) {

                /** @var  $modelKundendatenUndBuchungsdaten Front_Model_Bestellung */
                $modelKundendatenUndBuchungsdaten = new Front_Model_Bestellung();
                $shadowFrontModelWarenkorbLoeschenAlles->setModelKundenUndBuchungsdaten($modelKundendatenUndBuchungsdaten);

                // Daten ermitteln
                $shadowFrontModelWarenkorbLoeschenAlles->kundenUndBuchungsdatenErmitteln();

                // Kontrolle der Stornofristen der Programme einer Buchung
                $modelProgrammbuchungStornofrist = new Front_Model_ProgrammbuchungStornofrist();
                $shadowFrontModelWarenkorbLoeschenAlles->setmodelProgrammbuchungStornofrist($modelProgrammbuchungStornofrist);
                $anzahlProgrammeNichtInDerStornofrist = $shadowFrontModelWarenkorbLoeschenAlles->stornierenProgrammbuchungen();
            } else {
                // löschen Programmbuchung
                $shadowFrontModelWarenkorbLoeschenAlles->loeschenProgrammbuchungen();
            }

            // löschen Hotelbuchungen
            $shadowFrontModelWarenkorbLoeschenAlles->loeschenHotelbuchungen();

            // löschen Produktbuchungen
            $shadowFrontModelWarenkorbLoeschenAlles->loeschenProduktbuchungen();

            // löschen XML Buchung
            $shadowFrontModelWarenkorbLoeschenAlles->loeschenXmlBuchung();

            // setzt Status in 'tbl_buchungsnummer'
            $shadowFrontModelWarenkorbLoeschenAlles->setzenStatusTabelleBuchungsnummer();

            $redirector = $this->_helper->getHelper('Redirector');
            if ($zaehler > 0) {
                if ($anzahlProgrammeNichtInDerStornofrist > 0) {
                    $redirector->gotoUrl('/front/orderdata/');
                } else {
                    $redirector->gotoUrl('/front/bestellung/');
                }
            } else {
                // Model neue Buchungsnummer
                $modelNeueBuchungsnummer = Front_Model_NeueBuchungsnummer::getInstance($this->_pimple);
                $shadowFrontModelWarenkorbLoeschenAlles->setModelNeueBuchungsnummer($modelNeueBuchungsnummer);
                $shadowFrontModelWarenkorbLoeschenAlles->neueBuchungsnummer();

                $redirector->gotoUrl('/front/login/');
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Löscht eine einzelne Rate
     * aus dem Warebnkorb
     *
     * @return void
     */
    public function loeschensinglerateAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {

            // Kontrolle Gruppenstärke
            $flagGruppenstaerkeErreicht = $this->_ermittelnVerbleibendePersonenanzahl($params);

            if (!empty($flagGruppenstaerkeErreicht)) {
                $this->_loeschenSingleRate($params);
            }
            else {
                $this->_loeschenTeilrechnungHotelbuchung($params);
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Löschen einer einzelnen Rate
     * + ermitteln daten der gebuchten Rate
     * + löschen der gebuchten Rate
     * + Korrektur der Personenanzahl der Verpflegungstypen
     * + Korrektur der Zusatzprodukte die abhängig von der Anzahl der Personen sind.
     * + Kontrolle der XMl Datei
     * + Rücksprung zum Warenkorb
     *
     * @param $params
     */
    private function _loeschenSingleRate($params)
    {
        $buchungsdatenRate = $this->_bestimmeDatenHotelbuchung($params);

        $model = new Front_Model_Warenkorb();

        // Kontrolle des löschen der XML Dateien der Rate
        $model->deleteSingleHotelRate($params['buchungstabelle']);

        $redirectParams = array(
            "propertyId" => $buchungsdatenRate['propertyId'],
            "teilrechnungsId" => $buchungsdatenRate['teilrechnungen_id']
        );

        $redirector = $this->_helper->getHelper('Redirector');
        $redirector->gotoSimple('edit-alle-produkte', 'zusatzprodukte', 'front', $redirectParams);
    }

    /**
     * Löschen der Hotelbuchungen einer Teilrechnung
     * + löschen Hotelbuchungen
     * + löschen Zusatzprodukte
     * + löschen XML
     * + Rücksprung zu /front/hotelreservation/update-show/
     *
     * @param $params
     */
    private function  _loeschenTeilrechnungHotelbuchung($params)
    {
        $buchungsdatenRate = $this->_bestimmeDatenHotelbuchung($params);

        // löschen der Teilrechnung Hotelbuchung
        $modelTeilrechnungLoeschen = new Front_Model_TeilrechnungLoeschen();
        $modelTeilrechnungLoeschen
            ->setTeilrechnungId($buchungsdatenRate['teilrechnungen_id'])
            ->loeschenHotelbuchungEinerTeilrechnung();

        $redirectParams = array(
            "propertyId" => $buchungsdatenRate['propertyId'],
            "days" => $buchungsdatenRate['nights'],
            "from" => $buchungsdatenRate['startDate'],
            "cityId" => $buchungsdatenRate['cityId']
        );

        $redirector = $this->_helper->getHelper('Redirector');
        $redirector->gotoSimple('index', 'hotelreservation', 'front', $redirectParams);
        // $redirector->gotoUrl("/front/hotelreservation/update-show/");
    }

    /**
     * Ermittelt die Daten der Hotelbuchung einer Rate
     *
     * @return array
     */
    private function _bestimmeDatenHotelbuchung($params)
    {
        $toolHotelbuchung = new nook_ToolHotelbuchung();
        $buchungsdatenRate = $toolHotelbuchung
            ->setHotelbuchungId($params['buchungstabelle'])
            ->ermittelnDatenHotelbuchung()
            ->getDatenHotelbuchung();

        return $buchungsdatenRate;
    }

    /**
     * Ermittelt die verbleibende Personenanzahl einer Teilrechnung
     * Unterschreitet die Personenanzahl die definierte Gruppengröße, dann
     * Übergang auf Hotelreservation. Neue Teilrechnung wird angelegt.
     * + return true = Gruppenstärke korrekt
     * + return false = Gruppenstärke unterschritten
     *
     * @param $params
     */
    private function _ermittelnVerbleibendePersonenanzahl($params)
    {
        $gruppenstaerkeHotelbuchung = new Front_Model_GruppenstaerkeHotelbuchung();
        $gruppenStaerkeErreicht = $gruppenstaerkeHotelbuchung
            ->setHotelbuchungId($params['buchungstabelle'])
            ->kontrolleGruppenstaerke()
            ->getFlagGruppenstaerkeErreicht();

        return $gruppenStaerkeErreicht;
    }

    public function changeprogramAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {

            $model = new Front_Model_WarenkorbEditProgramm();
            $model
                ->kontrolleIdTabelleProgrammbuchung($params['programmbuchungId'])
                ->updatePreisvariante($params['anzahl']);

            /** @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = $this->_helper->getHelper('Redirector');
            $redirector->gotoUrl('/front/warenkorb/index/');

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * kopiert eine Altbuchung / Bestandsbuchung in die  neue Buchung In Session Namespace 'buchungsnummer'
     *
     * + löscht die Buchungspauschalen einer Buchungsnummer
     * + registrieren der 'buchungsnummer'
     * + registrieren des 'zaehler'
     * + Benutzer ID
     * + Umkopieren der Datensaetze der letzten Buchungsnummer auf Zaehler = 0
     */
    public function bestandBuchungenAction()
    {
        $params = $this->realParams;

        try {
            /** @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = $this->_helper->getHelper('Redirector');

            // Kontrolle ob Benutzer angemeldet
            $toolBenutzeranmeldung = new nook_ToolBenutzeranmeldung();

            // wenn Benutzer angemeldet
            if ($toolBenutzeranmeldung->validateUserId() and $toolBenutzeranmeldung->validateRolle()) {

                    // Servicecontainer
                    $pimple = $this->servicecontainer();

                    // löschen Buchungspauschalen
                    $frontModelVormerkung = new Front_Model_Vormerkung($pimple);
                    $frontModelVormerkung
                        ->setZaehlerDerBuchung($params['zaehler'])
                        ->loeschenBuchungspauschalen($params['buchungsnummer']);

                    // Benutzer ID
                    $userId = $toolBenutzeranmeldung->getUserId();

                    // Anzeigesprache
                    $anzeigesprache = nook_ToolSprache::getAnzeigesprache();

                    // Umkopieren der Datensaetze der letzten Buchungsnummer auf Zaehler = 0
                    $modelBestandsbuchungProgramme = new Front_Model_BestandsbuchungProgramme();
                    $modelBestandsbuchungProgramme
                        ->setUserId($userId)
                        ->setBuchungsnummer($params['buchungsnummer'])
                        ->setZaehler($params['zaehler'])
                        ->setAnzeigeSprache($anzeigesprache)
                        ->bestandsbuchung();

                // setzen Flag für 'index'
                $this->_flagBestandsbuchung = true;

                // zum Warenkorb
                $redirector->gotoUrl('/front/warenkorb/index/');
            }
            else{
                $redirector->gotoUrl('/front/login/index/');
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Ermittelt die Farbvariante der Button des Warenkorbes
     *
     * @param RainTPL $raintpl
     * @return RainTPL
     */
    private function bestimmeFarbvarianteButtonWarenkorb(RainTPL $raintpl)
    {
        $frontModelZaehlerBuchungsnummer = new Front_Model_ZaehlerBuchungsnummer();
        $frontModelZaehlerBuchungsnummer->findBuchungsnummerUndZaehler();

        $buchungsnummer = $frontModelZaehlerBuchungsnummer->getBuchungsnummer();
        $zaehler = $frontModelZaehlerBuchungsnummer->getZaehler();

        $frontModelFarbvarianteWarenkorb = new Front_Model_ButtonFarbvarianteWarenkorb();
        $farbvarianteButtonWarenkorb = $frontModelFarbvarianteWarenkorb
            ->setBuchungsnummerId($buchungsnummer)
            ->setZaehlerId($zaehler)
            ->ermittelnFarbvarianteButtonWarenkorb()
            ->getFarbVariante();

        $raintpl->assign('buttonFarbVariante', $farbvarianteButtonWarenkorb);

        return $raintpl;
    }

    /**
     * steuert die Sichtbarkeit des Blockes Personennzahl
     *
     * @param $raintpl
     * @return mixed
     */
    protected function blockPersonenanzahl($raintpl)
    {
        $toolAnzahlHotelbuchungen = new nook_ToolAnzahlRatenHotelbuchung();
        $anzahlRatenHotelbuchung = $toolAnzahlHotelbuchungen
            ->steuerungErmittlungAnzahlHotelbuchungen()
            ->getAnzahlHotelbuchungen();

        $raintpl->assign('anzahlRatenHotelbuchung', $anzahlRatenHotelbuchung);

        return $raintpl;
    }

    public function orderdataWarenkorbAction()
    {
        $params = $this->realParams;
        $this->flag_show_buttons = false;
        $this->flagOrderData = true;

        try {
            /** @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = $this->_helper->getHelper('Redirector');

            // zum Warenkorb
            $this->indexAction();

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }
}
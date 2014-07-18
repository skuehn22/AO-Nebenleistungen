<?php
/**
* Darstellung der Liste der Artikel eines Warenkorbes
*
* + Ermitteln des Buchungsdatensatzes
* + Speichern des Status der Artikel im Warenkorb
* + Löschen der ausgebuchten Zimmer
* + Berechnung der Buchungspauschalen
* + Trägt die Buchungspauschale in die Tabelle 'tbl_programmbuchung ein'
* + Trägt den Wunsch auf erhalt eines Newsletter ein
*
* @date 04.28.2013
* @file OrderdataController.php
* @package front
* @subpackage controller
*/
class Front_OrderdataController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $_realParams = null;
    private $pimple = null;
    private $requestUrl = null;

    // Flags
    private $_flag_show_buttons = false;

    // Konditionen
    private $_condition_warenkorb_status_gebucht = 3;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();

        // DIC
        $this->pimple = $this->getInvokeArg('bootstrap')->getResource('Container');
    }

    public function indexAction()
    {
        $buchungsdaten = $this->realParams;

        try {

            $this->pimple['tabelleCategories'] = function(){
                return new Application_Model_DbTable_categories(array('db' => 'hotels'));
            };


            $raintpl = raintpl_rainhelp::getRainTpl();

            // Controller
            $raintpl->assign('controller', $buchungsdaten['controller']);

            /*** alternative Blöcke  und abprüfen ob eine Buchung vorliegt ***/
            $modelAlternativeBloecke = new Front_Model_WarenkorbAlternativeBloecke();

            $buchungVorhanden = $modelAlternativeBloecke
                ->bestimmeParameterDerSuche();

            /*** wenn keine Buchung vorhanden gehe zur Startseite ***/
            if (empty($buchungVorhanden)) {
                $this->_redirect("/front/login/");
            }

            // Button Link der alternativen Blöcke
            $raintpl = $modelAlternativeBloecke->buttonLink($raintpl);

            // Model Warenkorb
            $modelCart = new Front_Model_Cart();

            // Gesamtpreis aller Buchungen
            $totalPrice = 0;

            // Anzahl der Buchungen
            $counterToOrder = 0;

            // Buchungsdatensatz / Kundeninformation
            $buchungsnummerDatensatz = $this->ermittelnBuchungsdatensatz();
            $raintpl->assign('kundenId', $buchungsnummerDatensatz[ 'kundenId' ]);
            $raintpl->assign('kompletteBuchungsnummer', $buchungsnummerDatensatz[ 'kompletteBuchungsnummer' ]);
            $raintpl->assign('buchungsnummerZaehler', $buchungsnummerDatensatz[ 'zaehler' ]);

            // anzeigen aktiver Schritt im Bestellprozess
            $navigation = $modelCart->getAktiveStep(1, 10, $buchungsdaten);
            $raintpl->assign('breadcrumb', $navigation);

            // findet alle Buchungsnummern
            $buchungsnummern = $modelCart->findBuchungsnummer();

            // löschen ausgebuchter Zimmer
            $this->loeschenAusgebuchteZimmer($modelCart, $buchungsnummern);

            /*** Bereich der Programme ***/
            $modelCartProgramme = new Front_Model_CartProgramme();
            $shoppingCartNestedProgramme = $modelCartProgramme
                ->setBuchungsnummernEinesKunden($buchungsnummern)
                ->findProgrammeEinesKunden()
                ->getGebuchteProgrammeNested(true); // aktive Programme eines Warenkorbes

            $counterToOrder += count($shoppingCartNestedProgramme);
            $programmePreise = $modelCart->berechneGesamtpreis($shoppingCartNestedProgramme, 'programmbuchung');
            $counterToOrder += $programmePreise[ 'count' ];
            $totalPrice += $programmePreise[ 'totalPrice' ];

            $raintpl->assign('shoppingCartNestedProgramme', $shoppingCartNestedProgramme);

            /*** Bereich der Buchungspauschalen ***/
            $buchungspauschale = $this->bereichBuchungspauschale($shoppingCartNestedProgramme, $totalPrice);
            // Buchungspauschale
            if($buchungspauschale['anzahl'] > 0){
                $raintpl->assign('buchungspauschale', $buchungspauschale);
            }

            /*** Bereich der Uebernachtungen ***/
            $shoppingCartHotelNested = $modelCart->findHotelBuchungNested($buchungsnummern, $this->pimple);

            // Bettensteuer
            $raintpl = $this->setzenTextBettensteuer($shoppingCartHotelNested, $raintpl);

            // Hotel Gruppenrabatt der Übernachtungen
            $gruppenRabatt = $this->errechnenGruppenrabattHotel($shoppingCartHotelNested);
            if($this->gesamtRabattWarenkorb > 0)
                $raintpl->assign('gruppenRabatt', $gruppenRabatt);

            // Anzahl und Gesamtpreis der Raten
            $ratenPreise = $modelCart->findHotelBuchungNestedPeise($shoppingCartHotelNested);
            $counterToOrder += $ratenPreise[ 'count' ];
            $totalPrice += $ratenPreise[ 'totalPrice' ];

            /**** vorhandene Zusatzprodukte ****/
            $shoppingCartZusatzprodukte = $modelCart->findZusatzprodukteHotel($buchungsnummern);

            /*** kombination von Hotel und Hotelprodukt ***/
            $shoppingCartHotelNested = $modelCart->kombiniereHotelUndProdukte(
                $shoppingCartHotelNested,
                $shoppingCartZusatzprodukte
            );

            $raintpl->assign('shoppingCartUebernachtungNested', $shoppingCartHotelNested);

            // Anzahl und Gesamtpreis der Zusatzprodukte
            $zusatzproduktePreise = $modelCart->berechneGesamtpreis($shoppingCartZusatzprodukte, 'zusatzprodukt');
            $counterToOrder += $zusatzproduktePreise[ 'count' ];
            $totalPrice += $zusatzproduktePreise[ 'totalPrice' ];

            // Anzahl der Bestellungen
            $raintpl->assign('counterToOrder', $counterToOrder);

            // setzt Flag der Stornierung, 1 = keine Stornierung
            $raintpl->assign('flagStornierung',1);

            // Gesamtpreis aller Bestellungen
            $totalPrice = nook_ToolPreise::berechneGesamtpreisAllerArtikelImWarenkorb($totalPrice);
            $raintpl->assign('totalPrice', $totalPrice);

            // Flags
            $raintpl->assign('flag_show_buttons', $this->_flag_show_buttons);

            $raintpl->assign('flagOrderData', true);

            $this->view->content = $raintpl->draw("Front_Warenkorb_Index", true);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Fügt den gebuchten Übernachtungen den Gruppenrabatt hinzu
     *
     * @param $shoppingCartHotelNested
     * @param $raintpl
     */
    private function errechnenGruppenrabattHotel($shoppingCartHotelNested)
    {

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
     * Setzt den Text der Bettensteuer, wenn Hotelbuchungen vorliegen
     *
     * @param $shoppingCartHotelNested
     * @param $raintpl
     * @return object
     */
    private function setzenTextBettensteuer($shoppingCartHotelNested, $raintpl)
    {
        if(count($shoppingCartHotelNested) > 0 ){
            $pimple = new Pimple_Pimple();
            $pimple['tabelleTextbausteine'] = function(){
                return new Application_Model_DbTable_textbausteine();
            };

            $toolStandardtexte = new nook_ToolStandardtexte();
            $textBettensteuer = $toolStandardtexte
                ->setPimple($pimple)
                ->setBlockname('bettensteuer')
                ->steuerungErmittelnText()->getText();

            $raintpl->assign('bettensteuer', $textBettensteuer);
        }

        return $raintpl;
    }


    /**
     * Ermitteln des Buchungsdatensatzes
     *
     * @return array
     */
    private function ermittelnBuchungsdatensatz()
    {

        $modelInformationBenutzerbuchung = Front_Model_InformationBenutzerBuchung::getInstance($this->pimple);
        $buchungsdaten = $modelInformationBenutzerbuchung
            ->generateBuchungsnummerKundenId()
            ->getBuchungsdaten();

        return $buchungsdaten;
    }

    public function viewAction()
    {
    }

    /**
     * Speichern des Status der Artikel im Warenkorb
     *
     * + speichern der Werte in 'tbl_rechnung'
     * + setzen Zaehler
     * + verändern Zähler
     * + eintragen Buchungspauschale
     * + Registrierung für Newsletter
     *
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $modelOrderdata = new Front_Model_Orderdata();
            $modelOrderdataStatus = new Front_Model_OrderdataStatus();

            // Kontrolle AGB
            $modelOrderdata->checkEditArtikel($params);

            // bestimmen Zaehler und verändern
            $buchungNamespace = new Zend_Session_Namespace('buchung');
            $arrayNamespace = (array) $buchungNamespace->getIterator();

            // eintragen Buchungspauschale in 'tbl_programmbuchung'
            $this->eintragenBuchungspauschale();

            // höherzählen des Zähler
            if(empty($arrayNamespace['zaehler']))
                $zaehler = 1;
            else{
                $zaehler = $arrayNamespace['zaehler'];
                $zaehler++;
            }

            // Zusatzinformation der Gruppe eintragen
            $this->eintragenDerZusatzinformationEinerGruppe($params, $modelOrderdata);


            $modelOrderdata->setZaehler($zaehler);

            // setzt Status des Warenkorbes auf status = 3, vor der Kasse
            $modelOrderdataStatus
                ->setStatus($this->_condition_warenkorb_status_gebucht) // Status gebucht
                ->setzenStatusTabelleBuchungsnummer(); // setzen Status

            // Newsletter eintragen
            $this->eintragenNewsletter($params);

            // Redirect
            $this->_redirect('/front/bestellung/');
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);

            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    public function deleteAction()
    {
    }

    /**
     * Löschen der ausgebuchten Zimmer
     *
     * @param $modelCart
     * @param $buchungsnummern
     */
    private function loeschenAusgebuchteZimmer($modelCart, $buchungsnummern)
    {
        $modelCart->loeschenAusgebuchteUebernachtungen($buchungsnummern);

        return;
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

        $this->pimple['tabelleProgrammdetails'] = function(){
            return new Application_Model_DbTable_programmedetails();
        };

        $this->pimple['tabellePreiseBeschreibung'] = function(){
            return new Application_Model_DbTable_preiseBeschreibung();
        };

        $this->pimple['viewBuchungspauschalen'] = function(){
            return new Application_Model_DbTable_viewBuchungspauschalen();
        };

        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $modelBuchungspauschale = new Front_Model_Buchungspauschale();

        // Anzahl Buchungspauschalen
        $anzahlBuchungspauschalen = $modelBuchungspauschale
            ->setPimple($this->pimple)
            ->setBuchungsnummer($buchungsnummer)
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
     * Trägt die Buchungspauschale in die Tabelle 'tbl_programmbuchung ein'
     */
    private function eintragenBuchungspauschale()
    {
        $this->pimple['tabelleProgrammbuchung'] = function()
        {
            return new Application_Model_DbTable_programmbuchung();
        };

        $this->pimple['viewBuchungspauschalen'] = function()
        {
            return new Application_Model_DbTable_viewBuchungspauschalen();
        };

        $toolBuchungsnummer = new nook_ToolBuchungsnummer();
        $buchungsnummer = $toolBuchungsnummer->findeBuchungsnummer();
        $flagExistierenProgrammbuchungen = $toolBuchungsnummer->existierenProgrammbuchungen($buchungsnummer);

        if(empty($flagExistierenProgrammbuchungen))
            return;

        $modelBuchungspauschale = new Front_Model_Buchungspauschale();
        $modelBuchungspauschale
            ->setPimple($this->pimple)
            ->setBuchungsnummer($buchungsnummer)
            ->steuerungEintragenBuchungpauschalenMitBuchungsnummer();

        return;
    }

    /**
     * Trägt den Wunsch auf erhalt eines Newsletter ein
     *
     * + 1 = keinen Newsletter
     * + 2 = Newsletter beziehen
     *
     * @param $params
     */
    private function eintragenNewsletter($params)
    {
        $newsletterWunsch = 1; // keinen Newsletter
        $userId = nook_ToolUserId::bestimmeKundenIdMitSession();
        if(array_key_exists('newsletter', $params)){
            if($params['newsletter'] == 'newsletter')
                $newsletterWunsch = 2; // Newsletter beziehen
        }

        $frontModelNewsletter = new Front_Model_Newsletter();
        $frontModelNewsletter
            ->setUserId($userId)
            ->setNewsletter($newsletterWunsch)
            ->steuerungEintragenNewsletterwunsch();

        return;
    }

    /**
     * Zusatzinformationen der Gruppe eintragen in 'tbl_buchungsnummer'
     *
     * @param array $params
     * @param Front_Model_Orderdata $modelOrderdata
     */
    protected function eintragenDerZusatzinformationEinerGruppe(array $params,Front_Model_Orderdata $modelOrderdata)
    {
        $zusatzinformationGruppe = array(
            'gruppenname' => $params['gruppenname'],
            'buchungshinweis' => $params['buchungshinweis'],
            'maennlichSchueler' => $params['maennlichSchueler'],
            'weiblichSchueler' => $params['weiblichSchueler'],
            'maennlichLehrer' => $params['maennlichLehrer'],
            'weiblichLehrer' => $params['weiblichLehrer'],
            'sicherstellung' => $params['sicherstellung']
        );

        $modelOrderdata->setEintragenZusatzinformationGruppe($zusatzinformationGruppe);

        return;
    }

}


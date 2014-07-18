<?php
/**
 * Anlegen, editieren und löschen von Zusatzprodukten
 *
 * @author Stephan Krauß
 */
class Front_ZusatzprodukteController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $_realParams = null;

    // Flags
    private $_flag_editieren_zusatzprodukte_einer_teilrechnung = false;
    private $_flag_teilrechnung_id = null;

    // Model
    private $_model_zusatzprodukteEinerTeilrechnung = null;

    private $requestUrl = null;

    public function init ()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    public function indexAction ()
    {
        $params = $this->realParams;

        try {

            /**** Zusatzprodukte eines Hotels *****/
            $zusatzprodukteModel = new Front_Model_Zusatzprodukte();
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Breadcrumb
            $navigation = $zusatzprodukteModel->breadcrumbNavigation(6, 5, $params);
            $raintpl->assign('breadcrumb', $navigation);

            // gemerkte Suchparameter der Hotelsuche in Namespace 'hotelsuche'
            if(array_key_exists('teilrechnungsId', $params)){
                $toolSpeichernWerteSessionVormerkungHotel = new nook_ToolSpeichernWerteSessionVormerkungHotel();
                $toolSpeichernWerteSessionVormerkungHotel->setTeilrechnungenId($params['teilrechnungsId'])->steuerungErmittelnWerteHotelsuche();
            }


            // Werte aus Session
            $hotelsucheGemerkteParams = nook_ToolSession::holeVariablenNamespaceSession('hotelsuche');
            $propertyId = $hotelsucheGemerkteParams['propertyId'];
            $teilrechnungsId = $hotelsucheGemerkteParams['teilrechnungsId'];

            $params['teilrechnungsId'] = $teilrechnungsId;
            $params['propertyId'] = $propertyId;

            // Produkte eines Hotel
            $produkteEinesHotels = $zusatzprodukteModel
                ->setHotelId($propertyId)
                ->setTeilrechnungId($teilrechnungsId) // setzen der Teilrechnung
                ->getZusatzprodukteEinesHotels();

            // Zusatzprodukte einer neuen Buchung
            if(empty($this->_flag_editieren_zusatzprodukte_einer_teilrechnung))
                $raintpl->assign('teilrechnungId', $teilrechnungsId);
            // Veränderung der Zusatzprodukte einer bereits vorhandenen Buchung
            else
                $raintpl->assign('teilrechnungId', $this->_flag_teilrechnung_id);

            if(is_array($produkteEinesHotels)) {

                // wird eine Teilrechnung editiert ???
                if($this->_flag_editieren_zusatzprodukte_einer_teilrechnung) {
                    $produkteEinesHotels = $this->_setzeBereitsGebuchteProdukte($produkteEinesHotels);
                }

                $raintpl->assign('zusatzprodukte', $produkteEinesHotels);
            } else {
                $raintpl->assign('zusatzprodukte', false);
            }

            // ermitteln touristische Grundleistung
            $bereitsGebuchteProdukte = $this->_touristischeGrundleistungen($params);

            if(is_array($bereitsGebuchteProdukte)) {
                $raintpl->assign('bereitsGebuchteProdukte', $bereitsGebuchteProdukte);
            } else {
                $raintpl->assign('bereitsGebuchteProdukte', false);
            }

            // Parameter zur Information Personenanzahl, Zimmeranzahl, Anreisetag
            // Informationen zum Hotel
            $information = $zusatzprodukteModel->getInformationenZurBuchung();

            // Abreisetag
            $toolAbreisetag = new nook_ToolAbreisetag();
            $abreiseDatum = $toolAbreisetag
                ->setAnreiseDatum($information['anreisetag'])
                ->setAnzahlUebernachtungen($information['uebernachtungen'])
                ->berechneAbreisetag()
                ->getAbreiseDatum();

            $information['abreisetag'] = $abreiseDatum;

            $raintpl->assign('information', $information);

            // Übergabe an Templat
            $this->view->content = $raintpl->draw("Front_Zusatzprodukte_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Ermittelt die touristischen Grundleistungen der Teilrechnung
     *
     * @param $params
     * @param $raintpl
     * @return mixed
     */
    private function _touristischeGrundleistungen(array $params)
    {
        $modelZusatzprodukteTouristischeGrundleistung = new Front_Model_ZusatzprodukteTouristischeGrundleistung();

        $bereitsGebuchteProdukte = $modelZusatzprodukteTouristischeGrundleistung
            ->setHotelId($params['propertyId'])
            ->setTeilrechnungId($params['teilrechnungsId'])
            ->ermittelnProdukteTouristischeGrundleistung()
            ->getGebuchteTouristischeGrundleistungen();

        return $bereitsGebuchteProdukte;
    }

    /**
     * Setzt die bereits gebuchten
     * Produkte im Array für Templat Engine
     *
     * @param array $__ProdukteEinesHotels
     * @return array
     */
    private function _setzeBereitsGebuchteProdukte (array $__ProdukteEinesHotels)
    {
        try {
            /** @var $modelZusatzprodukte Front_Model_ZusatzprodukteEditierenTeilrechnung */
            $modelZusatzprodukteEinerTeilrechnung = $this->_model_zusatzprodukteEinerTeilrechnung;
            $__ProdukteEinesHotels = $modelZusatzprodukteEinerTeilrechnung->ergaenzeGewahlteProdukte($__ProdukteEinesHotels);

            return $__ProdukteEinesHotels;

        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Speichert die gewahlten Zusatzprodukte eines Hotels.
     *
     * + löscht XML File der Zustzprodukte
     *
     */
    public function saveAction ()
    {
        $params = $this->realParams;

        try {

            $warenkorbZusatzprodukte = new Front_Model_WarenkorbZusatzprodukte();

            // ausfiltern Zusatzprodukte
            $params = $warenkorbZusatzprodukte->mapOptionenVerpflegung($params);

            $modelProduktbuchungXml = new Front_Model_ZusatzprodukteXml();

            // wurden Zusatzprodukte gewählt
            $zusatzprodukte = $warenkorbZusatzprodukte->checkBuchungsdatenZusatzprodukte($params);

            // speichern vorhandene Zusatzprodukte
            if(!empty($zusatzprodukte)) {

                // löschen XML File der Zusatzprodukte einer Teilrechnung
                $warenkorbZusatzprodukte->loeschenTeilrechnungXMLZusatzprodukte($zusatzprodukte[ 'teilrechnungId' ]);

                // eintragen der Zusatzprodukte einer Teilbuchung
                $warenkorbZusatzprodukte->saveZusatzprodukteEinesHotel($zusatzprodukte);

                /*** eintragen der Zusatzprodukte als XML - Block ***/
                $modelProduktbuchungXml->saveXmlZusatzprodukte();
            }

            $this->_redirect('/front/warenkorb/index');
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * editieren bereits gewählter
     * Zusatzprodukte eines Hotels.
     * Stellt ein einzelnes Produkt welches verändert
     * werden soll dar.
     *
     */
    public function editAction ()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            /**** Zusatzprodukte eines Hotels *****/
            $zusatzprodukte = new Front_Model_Zusatzprodukte();
            $modelProduktbuchungXml = new Front_Model_ZusatzprodukteXml();
            $raintpl = raintpl_rainhelp::getRainTpl();

            // Breadcrumb
            $navigation = $zusatzprodukte->breadcrumbNavigation(6, 5, $params);
            $raintpl->assign('breadcrumb', $navigation);

            // ermitteln und speichern Hotel ID und ID Zusatzprodukt
            $zusatzprodukte->setHotelIdUndDatenZusatzprodukt($params[ 'idProduktbuchung' ]);

            // einzelnes Zusatzprodukt eines Hotels für das Update
            $zusatzprodukteFuerUpdate = $zusatzprodukte->getZusatzprodukteEinesHotelsFuerUpdate();
            $raintpl->assign('zusatzprodukte', $zusatzprodukteFuerUpdate);

            // update XML - Block der Zusatzprodukte
            $modelProduktbuchungXml->saveXmlZusatzprodukte();

            // abschalten von Blöcken
            $raintpl->assign('bereitsGebuchteProdukte', false);
            $raintpl->assign('information', false);

            $this->view->content = $raintpl->draw("Front_Zusatzprodukte_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    public function deleteAction ()
    {
    }

    /**
     * Editieren aller Produkte
     * einer Teilrechnung einer Hotelbuchung
     */
    public function editAlleProdukteAction ()
    {
        $params = $this->realParams;
        $this->_flag_editieren_zusatzprodukte_einer_teilrechnung = true;
        $this->_flag_teilrechnung_id = $params[ 'teilrechnungsId' ];

        try {

            // aktualisierte Werte in Session
            $parameterHotelsuche = new Zend_Session_Namespace('hotelsuche');
            $parameterHotelsuche->teilrechnungsId = $params['teilrechnungsId'];
            $parameterHotelsuche->propertyId = $params['propertyId'];


            $modelZusatzprodukteEinerTeilrechnung = new Front_Model_ZusatzprodukteEditierenTeilrechnung();
            $modelZusatzprodukteEinerTeilrechnung
                ->setTeilrechnungId($params['teilrechnungsId'])
                ->bestimmeProdukteDerTeilrechnung();

            /** @var _model_zusatzprodukteEinerTeilrechnung Front_Model_ZusatzprodukteEditierenTeilrechnung */
            $this->_model_zusatzprodukteEinerTeilrechnung = $modelZusatzprodukteEinerTeilrechnung;

            // Übergang auf 'index'
            $this->indexAction();
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * Editieren aller Produkte
     * einer Teilrechnung einer Hotelbuchung
     */
    public function zusaetzlichBuchenAction ()
    {
        $params = $this->realParams;

        try{

            // aktualisierte Werte in Session
            $parameterHotelsuche = new Zend_Session_Namespace('hotelsuche');
            $parameterHotelsuche->teilrechnungsId = $params['teilrechnungsId'];
            $parameterHotelsuche->propertyId = $params['propertyId'];

            // Übergang auf 'index'
            $this->indexAction();
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }

    }
} // end class


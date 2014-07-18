<?php
/**
* Handelt die Vormerkung des Warenkorbes eines Benutzers
*
* + Speichern des Status der Artikel im Warenkorb
*
* @date 04.24.2013
* @file OrderdataVormerkenController.php
* @package front
* @subpackage model
*/
class Front_OrderdataVormerkenController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $_realParams = null;
    private $requestUrl = null;

    protected $condition_status_aktiver_warenkorb = 1;
    protected $condition_status_vormerkung = 2;
    protected $condition_zaehler_aktiver_warenkorb = 0;

    public $pimple = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();

        $this->servicecontainer();
    }

    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProgrammbuchung'] = function(){
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function(){
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function(){
            return new Application_Model_DbTable_produktbuchung();
        };

        $this->pimple = $pimple;


        return;
    }

    public function indexAction(){}

    public function viewAction(){}

    /**
     * Speichern des Status der Artikel im Warenkorb
     *
     * + kontrolliert ob status = 2
     * + kontrolliert agb
     * + setzt status
     * + kopiert Session Daten in 'tbl_buchungsnummer'
     * + Status Programmbuchungen
     * + Status Hotelbuchungen
     * + Status Produktbuchungen
     * + neue Session erstellen
     *
     */
    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        $sessionId = Zend_Session::getId();

        try{
            $buchungsNummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

            $raintpl = raintpl_rainhelp::getRainTpl();
            $modelOrderdataStatus = new Front_Model_OrderdataStatus();

            // Gibt es überhaupt Buchungen
            $existBuchungen = $modelOrderdataStatus->checkExistBuchungen();

            // Wenn Buchungen existieren
            if(!empty($existBuchungen))
                $this->wennBuchungenExistieren($modelOrderdataStatus, $params, $buchungsNummer);

            // Hotels in der Stadt
            $variablenNamespaceHotelsuche = nook_ToolSession::holeVariablenNamespaceSession('hotelsuche');
            $variablenNamespaceProgrammsuche = nook_ToolSession::holeVariablenNamespaceSession('programmsuche');

            if(!empty($variablenNamespaceHotelsuche['city']))
                $city = $variablenNamespaceHotelsuche['city'];
            elseif(!empty($variablenNamespaceProgrammsuche['city']))
                $city = $variablenNamespaceProgrammsuche['city'];
            else
                $city = 1;

            // existieren Buchubgen
            $raintpl->assign('existBuchungen', $existBuchungen);
            // Stadt
            $raintpl->assign('cityId', $city);
            $this->view->content = $raintpl->draw( "Front_OrderdataVormerken_Edit", true);


        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * ermittelt größte schon vorhandene HOB Nummer
     *
     * @param $buchungsNummer
     */
    protected function ermittlungMaximaleHobNummer($buchungsNummer)
    {
        $nookToolMaximaleHobNummer = new nook_ToolMaximaleHobNummer();
        $maximaleHobNummer = $nookToolMaximaleHobNummer
            ->steuerungErmittlungMaxHobNummer()
            ->getMaxHobNummer();

        return $maximaleHobNummer;
    }

    public function deleteAction(){}

    /**
     * Löschen der alten Vormerkung der Buchungsnummer
     *
     * + löschen entsprechend Buchungsnummer
     * + löschen entsprechend Zähler = 0
     * + löschen entsprechend Status = 2
     *
     * @param $buchungsNummer
     */
    protected function loeschenAlteVormerkung($buchungsNummer)
    {
        $frontModelWarenkorbArtikelAbteilungLoeschen = new Front_Model_WarenkorbArtikelAbteilungLoeschen();
        $frontModelWarenkorbArtikelAbteilungLoeschen
            ->setBuchungsnummer($buchungsNummer)
            ->setZaehler($this->condition_zaehler_aktiver_warenkorb)
            ->setStatus($this->condition_status_vormerkung);

        // löschen Buchungsdatensätze 'tbl_programmbuchung'
        $frontModelWarenkorbArtikelAbteilungLoeschen
            ->setTabelle($this->pimple['tabelleProgrammbuchung'])
            ->steuerungLoeschenWarenkorb();

        // löschen Buchungsdatensätze 'tbl_hotelbuchung'
        $frontModelWarenkorbArtikelAbteilungLoeschen
            ->setTabelle($this->pimple['tabelleHotelbuchung'])
            ->steuerungLoeschenWarenkorb();

        // löschen Buchungsdatensätze 'tbl_produktbuchung'
        $frontModelWarenkorbArtikelAbteilungLoeschen
            ->setTabelle($this->pimple['tabelleProduktbuchung'])
            ->steuerungLoeschenWarenkorb();

        return;
    }

    /**
     * Setzt den Status der Artikel in den Buchungsdatensätzen neu
     *
     * @param $modelOrderdataStatus
     * @param $params
     */
    protected function anlegenNeueVormerkung(Front_Model_OrderdataStatus $modelOrderdataStatus, $params)
    {
        $modelOrderdataStatus
            ->checkStatus($params['status']) // Kontrolle Status
            ->checkAgb($params['agb']) // Kontrolle AGB
            ->setStatus($params['status']) // setzen Status
            ->setzenStatusTabelleBuchungsnummer() // setzen Status des Warenkorbes 'tbl_buchungsnummer'
            ->setzenStatusTabelleProgrammbuchung() // setzt den Status des Warenkorbes 'tbl_programmbuchung'
            ->setzenStatusTabelleHotelbuchung() // setzen Status der Hotelbuchungen
            ->setzenStatusTabelleProduktbuchung(); // setzen Status produktbuchungen
            // ->kopierenSessionDaten(); // kopiert die Session Daten
            // ->neueSession(); // neue Session

        return;
    }

    /**
     * Anlegen eines neuen Warenkorbes mit dem Status 'aktiver Warenkorb'
     *
     * @param $buchungsNummerAlt
     * @return int
     */
    protected function neuerWarenkorb($buchungsNummerAlt)
    {
        $sessionId = Zend_Session::getId();
        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $frontModelWarenkorbNeu = new Front_Model_WarenkorbNeu();
        $neueBuchungsnummer = $frontModelWarenkorbNeu
            ->setSession($sessionId)
            ->setAlteBuchungsnummerId($buchungsNummerAlt)
            ->setTabelleBuchungsnummer($tabelleBuchungsnummer)
            ->steuerungAnlegenNeuerWarenkorbTabelleBuchungsnummer()
            ->getBuchungsnummerNeu();

        return $neueBuchungsnummer;
    }

    /**
     * Update der HOB Nummer einer Buchungsnummer
     *
     * @param $buchungsNummer
     * @param $maximaleHobNummer
     */
    protected function updateHobNummerEinerBuchung($buchungsNummer, $maximaleHobNummer)
    {
        $frontModelUpdateHobNummer = new Front_Model_UpdateHobNummer();
        $frontModelUpdateHobNummer
            ->setBuchungsNummer($buchungsNummer)
            ->setHobNummer($maximaleHobNummer)
            ->steuerungUpdateHobNummer();

        return;
    }

    /**
     * Wenn Buchungen existieren
     *
     * + bereits existierende Vormerkung , $flagVormerkung = true
     * + neue Vormerkung, $flagVormerkung = false
     *
     * @param $modelOrderdataStatus
     * @param $params
     * @param $aktuelleBuchungsNummer
     */
    protected function wennBuchungenExistieren($modelOrderdataStatus, $params, $aktuelleBuchungsNummer)
    {
        $sessionId = nook_ToolSession::getSessionId();

        // erkennen ob aktiver Warenkorb eine Vormerkung ist
        $modelErkennenVormerkung = new Front_Model_ErkennenVormerkung();
        $flagVormerkung = $modelErkennenVormerkung
            ->setSessionId($sessionId)
            ->steuerungErmittlungFlagVormerkung()
            ->getFlagVormerkung();

        // wenn es eine Vormerkung ist
        if($flagVormerkung == true){
            // ermitteln alte Buchungsnummer des 'Warenkorbes der Vormerkung'
            $buchungsNummerVormerkung = $modelErkennenVormerkung->getBuchungsNummerVormerkung();

            // loeschen alte Vormerkung mit Buchungsnummer
            $this->loeschenAlteVormerkung($buchungsNummerVormerkung);
        }
        // neue Vormerkung
        else{
            // ermitteln maximale HOB Nummer
            $maximaleHobNummer = $this->ermittlungMaximaleHobNummer($aktuelleBuchungsNummer);
            $maximaleHobNummer++;

            // vergabe der neuen HOB Nummer an die Vormerkung
            $this->updateHobNummerEinerBuchung($aktuelleBuchungsNummer, $maximaleHobNummer);
        }

        // aktiver Warenkorb Artikel mit Status 'Vormerkung' versehen
        $this->anlegenNeueVormerkung($modelOrderdataStatus, $params);

        // löschen Datensatz alte Vormerkung in 'tbl_buchungsnummer'
        if($flagVormerkung === true){
            $frontModelVormerkungLoeschen = new Front_Model_VormerkungLoeschen();
            $frontModelVormerkungLoeschen
                ->setBuchungsNummer($buchungsNummerVormerkung)
                ->steuerungVormerkungLoeschen();
        }

        // neuer leerer Warenkorb anlegen
        $neueBuchungsNummer = $this->neuerWarenkorb($aktuelleBuchungsNummer);
    }

}


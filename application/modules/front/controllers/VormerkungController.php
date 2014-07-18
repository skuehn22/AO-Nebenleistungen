<?php
/**
* Listet die Vormerkungen des Kunden auf
*
* + Ermitteln der Session ID der Vormerkung und umlenken auf Warenkorb
* + Löscht die Vormerkung
*
* @date 03.21.2013
* @file VormerkungController.php
* @package front
* @subpackage controller
*/
class Front_VormerkungController extends Zend_Controller_Action implements nook_ToolCrudController
{
    // Konditionen
    private $condition_zaehler_vormerkung = 0;
    private $condition_status_im_warenkorb = 1;
    private $condition_status_in_vormerkung = 2;

    private $condition_zaehler_aktiver_warenkorb = 0;

    private $_realParams = null;
    private $pimple = null;

    private $error_auth_fehlt = 2380;

    private $requestUrl = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->servicecontainer();

        $this->requestUrl = $this->view->url();
    }

    private function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleBuchungsnummer'] = function(){
            return new Application_Model_DbTable_buchungsnummer();
        };

        $pimple['tabelleProgrammbuchung'] = function()
        {
            return new Application_Model_DbTable_programmbuchung();
        };

        $pimple['tabelleHotelbuchung'] = function()
        {
            return new Application_Model_DbTable_hotelbuchung();
        };

        $pimple['tabelleProduktbuchung'] = function()
        {
            return new Application_Model_DbTable_produktbuchung();
        };

        $pimple['toolZaehler'] = function($pimple){
            return new nook_ToolZaehler($pimple);
        };

        $pimple['toolProgrammbuchungen'] = function($pimple){
            return new nook_ToolProgrammbuchungen($pimple);
        };

        $this->pimple = $pimple;

        return;
    }

    public function indexAction()
    {
        try {
            $raintpl = raintpl_rainhelp::getRainTpl();
            $modelVormerkung = new Front_Model_Vormerkung($this->pimple);

            $authVariablen = nook_ToolSession::holeVariablenNamespaceSession('Auth');

            if( (!is_array($authVariablen)) or empty($authVariablen['userId']))
               throw new nook_Exception('User ID oder allgemeine Auth - Variablen fehlen');

            $vormerkungen = $modelVormerkung
                ->setKundenId($authVariablen['userId'])
                ->getVormerkungen();

            $raintpl->assign('vormerkungen', $vormerkungen);

            $this->view->content = $raintpl->draw("Front_Vormerkung_Index", true);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    /**
     * schreibt einen vorgemerkten Warenkorb zu einem aktiven Warenkorb um
     *
     * + legt bei Notwendigkeit eine neue Buchungsnummer an
     * + löschen aktiven Warenkorb
     * + duplizieren Inhalt eines vorgemerkten Warenkorbes zu einem aktiven Warenkorb
     * + umlenken auf /front/warenkorb
     *
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            // momentane Buchungsnummer und Buchungsnummer Vormerkung
            $buchungsnummerVormerkung = $params['buchungsnummer'];
            $momentaneBuchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

            // wenn keine Buchungsnummer vorhanden, anlegen neue Buchungsnummer
            if(empty($momentaneBuchungsnummer))
                $momentaneBuchungsnummer = $this->neueBuchungsnummerAnlegen();

            // vererben HOB Nummer auf die neue Buchungsnummer
            $this->vererbenHobNummer($buchungsnummerVormerkung, $momentaneBuchungsnummer);

            $modelVormerkung = new Front_Model_Vormerkung($this->pimple);
            $sessionIdVormerkung = $modelVormerkung->getSessionIdDerVormerkung($momentaneBuchungsnummer);

            // löschen aktiver Warenkorb
            $anzahlGeloeshteArtikelImWarenkorb = $this->loeschenAktiverWarenkorb($momentaneBuchungsnummer);

            // duplizieren des Warenkorbes der Vormerkung
            $anzahlInsertProgrammbuchungen = $this->warenkorbDuplizieren($buchungsnummerVormerkung, $momentaneBuchungsnummer);

            // löschen der Buchungspauschalen einer vorgemerkten Buchungsnummer
            if($anzahlInsertProgrammbuchungen > 0)
                $modelVormerkung->loeschenBuchungspauschalen($params['buchungsnummer']);

            $anzeigesprache = nook_ToolSprache::getAnzeigesprache();

            // Umlenkung auf Warenkorb mit Buchungsnummer
            $this->_redirect('/front/warenkorb/index/sessionIdVormerkung/'.$sessionIdVormerkung."/translate/".$anzeigesprache);
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }



    /**
     * Löscht die Vormerkung einer Buchungsnummer
     *
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $modelVormerkung = new Front_Model_Vormerkung($this->pimple);
            $modelVormerkung->loeschenVormerkung($params['buchungsnummer']);

            $vormerkungFlag = $modelVormerkung->getStatusVormerkungen();

            if (!empty($vormerkungFlag)) {
                $this->_redirect('/front/vormerkung/index');
            } else {
                $this->_redirect('/front/login/index');
            }
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e);
        }
    }

    public function showAction()
    {
    }

    /**
     * Dupliziert die Datensätze eines vorgemerkten Warenkorbes in den Buchungstabellen
     *
     * @param $params
     * @return array
     */
    protected function warenkorbDuplizieren($buchungsnummerVormerkung, $momentaneBuchungsnummer)
    {
        // setzen der Grundwerte Warenkorb duplizieren
        $frontModelWarenkorbDuplizieren = new Front_Model_WarenkorbDuplizieren();
        $frontModelWarenkorbDuplizieren
            ->setBuchungsnummerId($buchungsnummerVormerkung)
            ->setNeueBuchungsnummer($momentaneBuchungsnummer)
            ->setZaehler($this->condition_zaehler_vormerkung)
            ->setAlterStatus($this->condition_status_in_vormerkung)
            ->setNeuerStatus($this->condition_status_im_warenkorb);

        // Tabelle Hotelbuchung
        $frontModelWarenkorbDuplizieren
            ->setTable($this->pimple['tabelleHotelbuchung'])
            ->steuerungDuplizierenDatensaetzeBuchungstabelle();

        // Tabelle Produktbuchung
        $frontModelWarenkorbDuplizieren
            ->setTable($this->pimple['tabelleProduktbuchung'])
            ->steuerungDuplizierenDatensaetzeBuchungstabelle();

        // Tabelle Programmbuchung
        $anzahlInsertProgrammbuchungen = $frontModelWarenkorbDuplizieren
            ->setTable($this->pimple['tabelleProgrammbuchung'])
            ->steuerungDuplizierenDatensaetzeBuchungstabelle();

        return $anzahlInsertProgrammbuchungen;
    }

    /**
     * Löschen des aktiven Warenkorbes entsprechend der Abteilungen des Warenkorbes
     *
     * @param $params
     */
    protected function loeschenAktiverWarenkorb($momentaneBuchungsnummer)
    {
        $anzahlGeloeschteArtikelWarenkorb = 0;
        $frontModelWarenkorbLoeschen = new Front_Model_WarenkorbArtikelAbteilungLoeschen();

        $frontModelWarenkorbLoeschen
            ->setBuchungsnummer($momentaneBuchungsnummer)
            ->setZaehler($this->condition_zaehler_aktiver_warenkorb)
            ->setStatus($this->condition_status_im_warenkorb);

        // löschen Programme
        $anzahlGeloeschteArtikelWarenkorb += $frontModelWarenkorbLoeschen
            ->setTabelle($this->pimple['tabelleProgrammbuchung'])
            ->steuerungLoeschenWarenkorb()
            ->getAnzahlGeloeschteArtikel();

        // löschen Übernachtungen
        $anzahlGeloeschteArtikelWarenkorb += $frontModelWarenkorbLoeschen
            ->setTabelle($this->pimple['tabelleHotelbuchung'])
            ->steuerungLoeschenWarenkorb()
            ->getAnzahlGeloeschteArtikel();

        // löschen Hotelprodukte
        $anzahlGeloeschteArtikelWarenkorb += $frontModelWarenkorbLoeschen
            ->setTabelle($this->pimple['tabelleProduktbuchung'])
            ->steuerungLoeschenWarenkorb()
            ->getAnzahlGeloeschteArtikel();

        return $anzahlGeloeschteArtikelWarenkorb;
    }

    /**
     * Setzt den Status der Vormerkung in 'tbl_buchungsnummer'
     *
     * @param $buchungsnummer
     * @param $zaehler
     * @return Front_Model_WarenkorbStatusSetzen
     */
    private function setzenStatusWarenkorb($buchungsnummer)
    {
        $frontModelWarenkorbStatusSetzen = new Front_Model_WarenkorbStatusSetzen();
        $frontModelWarenkorbStatusSetzen
            ->setBuchungsnummer($buchungsnummer)
            ->setStatus($this->condition_status_im_warenkorb)
            ->setTabelle($this->pimple['tabelleBuchungsnummer'])
            ->steuerungSetzenStatusWarenkorb();

        return;
    }

    /**
     * Anlegen einer neuen Buchungsnummer, wenn nötig
     *
     * @return int
     */
    protected function neueBuchungsnummerAnlegen()
    {
        $frontModelNeueBuchungsnummerAnlegen = new Front_Model_NeueBuchungsnummerAnlegen();
        $momentaneBuchungsnummer = $frontModelNeueBuchungsnummerAnlegen
            ->steuerungErstellenNeueBuchungsnummer()
            ->getBuchungsnummer();

        return $momentaneBuchungsnummer;
    }

    /**
     * Vererben der HOB Nummer einer bereits vorhandenen Buchung an eine neue Buchung
     *
     * @param $buchungsnummerVormerkung
     * @param $momentaneBuchungsnummer
     */
    protected function vererbenHobNummer($buchungsnummerVormerkung, $momentaneBuchungsnummer)
    {
        $modelDuplizierenHobNummer = new Front_Model_DuplizierenHobNummer();
        $modelDuplizierenHobNummer
            ->setAlteBuchungsnummer($buchungsnummerVormerkung)
            ->setNeueBuchungsnummer($momentaneBuchungsnummer)
            ->steuerungDuplizierenHobNummer();

        return;
    }
}
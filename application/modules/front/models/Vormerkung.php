<?php
/**
* Darstellung der Vormerkungen in einer Liste
*
* + Auswertung Servicecontainer und Kontrolle Container
* + Gibt die Vormerkungen
* + Löschen der Buchungspauschalen einer Buchungsnummer
* + Schaffen der Knoten zum andocken der Basisinformationen der Buchungen
* + Findet die Grundinformationen zu gemerkten Hotelbuchungen
* + Ermittelt die Anzeigewerte eines Programmes welches vorgemerkt wurde
* + ermittelt die Vormerkungen des Kunden in der Tabelle 'tbl_buchungsnummer'
* + Ermittelt die Anzeigesprache
* + Kontrolle der Kunden ID
* + Setzen und / oder ermitteln der Kunden ID
* + Ermittelt ob der Kunde Vormerkungen hat
* + Löscht eine Vormerkung
* + Durchführung des löschen einer Vormerkung
* + Ermittelt die Session ID einer Vormerkung
* + Setzen des Zaehler einer Buchung
*
* @date 03.31.2013
* @file Vormerkung.php
* @package front
* @subpackage model
*/
class Front_Model_Vormerkung extends nook_ToolModel
{

    // Error
    private $_error_keine_kunden_id = 1100;
    private $_error_keine_vormerkung_vorhanden = 1101;
    private $_error_datenbank = 1102;
    private $_error_sessionid_vormerkung = 1103;
    private $_error_keine_hotelbuchung_vorhanden = 1104;
    private $_error_falscher_anfangswert = 1105;
    private $_error_anfangswerte_fehlen = 1106;

    // Konditionen
    private $_condition_status_vorgemerkt = 2;
    private $_condition_vormerkung_geloescht = 10;
    private $_condition_aktueller_inhalt_warenkorb = 0;

    // Tabellen und Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = false;
    /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
    private $tabelleHotelbuchung = null;
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;

    protected $_kundenId = null;
    protected $_vormerkungen = array();
    protected $_anzeigesprache = false;
    protected $_anzeigeVormerkungen = array();
    protected $zaehler = 0;
    /** @var $toolZaehler nook_ToolZaehler */
    protected  $toolZaehler = null;
    /** @var $toolProgrammbuchungen nook_ToolProgrammbuchungen */
    protected $toolProgrammbuchungen = null;

    protected $pimple = null;


    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Auswertung Servicecontainer und Kontrolle Container
     *
     * @param $pimple
     * @throws nook_Exception
     */
    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleBuchungsnummer',
            'tabelleHotelbuchung',
            'tabelleProgrammbuchung',
            'toolZaehler',
            'toolProgrammbuchungen'
        );

        foreach($tools as $key => $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->_error_anfangswerte_fehlen);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * Gibt die Vormerkungen
     * des Kunden zurück
     *
     * @return bool
     */
    public function getStatusVormerkungen()
    {
        $statusVormerkungen = $this->checkKundenId()->_getStatusVormerkungen();

        return $statusVormerkungen;
    }

    /**
     * Steuerung der Ermittlung der Vormerkungen
     *
     * + ermittelt die Vormerkungen des Kunden in der Tabelle 'tbl_buchungsnummer'
     * + Ermittelt die Anzahl der Artikel einer Vormerkung
     *
     * @return array
     */
    public function getVormerkungen()
    {
        // ermittelt die Vormerkungen des Kunden in der Tabelle 'tbl_buchungsnummer'
        $this->_getVormerkungen();

        // Ermittelt die Anzahl der Artikel einer Vormerkung
        $this->kontrolleAnzahlArtikelVormerkung();

        // ermitteln Anzeigesprache
        $this->_setAnzeigesprache();

        // Knoten bilden
        $this->_schaffeKnotenFuerBuchungsinformation();

        // Hoteldaten
        $this->_findHoteldaten();

        // Programmdaten
        $this->_findProgrammdaten();

        return $this->_anzeigeVormerkungen;
    }

    /**
     * Ermittelt die Anzahl der Artikel einer Vormerkung
     *
     * + Anzahl der Programmbuchungen und Anzahl Hotelbuchungen
     * + Vormerkungen ohne Artikel werden gelöscht
     *
     * @return int
     */
    private function kontrolleAnzahlArtikelVormerkung()
    {
        $toolAnzahlArtikelWarenkorb = new nook_ToolAnzahlArtikelWarenkorb($this->pimple);
        $vormerkungen = array();

        for($i=0; $i < count($this->_vormerkungen);  $i++){
            $buchungsnummer = $this->_vormerkungen[$i]['id'];
            $zaehler = $this->_vormerkungen[$i]['zaehler'];

            $toolAnzahlArtikelWarenkorb
                ->setBuchungsnummer($buchungsnummer)
                ->setZaehler($zaehler)
                ->steuerungErmittlungAnzahlArtikelImWarenkorb();

            $anzahlProgrammbuchungen = $toolAnzahlArtikelWarenkorb->getAnzahlProgrammbuchungen();
            $anzahlHotelbuchungen = $toolAnzahlArtikelWarenkorb->getAnzahlHotelbuchungen();

            $anzahlArtikelImWarenkorb = $anzahlProgrammbuchungen + $anzahlHotelbuchungen;

            if($anzahlArtikelImWarenkorb > 0)
                $vormerkungen[] = $this->_vormerkungen[$i];
        }

        $this->_vormerkungen = $vormerkungen;

        return $anzahlArtikelImWarenkorb;
    }

    /**
     * Löschen der Buchungspauschalen einer Buchungsnummer
     *
     * @param $buchungsnummerId
     * @return Front_Model_Vormerkung
     */
    public function loeschenBuchungspauschalen($buchungsnummerId)
    {
        $static = nook_ToolStatic::getStaticWerte();
        $buchungspauschaleProgrammId = $static['buchungspauschale']['programmId'];
        $buchungspauschalePreisvarianteId = $static['buchungspauschale']['preisvarianteId'];

        $whereDelete = array(
            "programmdetails_id = ".$buchungspauschaleProgrammId,
            "tbl_programme_preisvarianten_id = ".$buchungspauschalePreisvarianteId,
            "buchungsnummer_id = ".$buchungsnummerId,
            "zaehler = ".$this->zaehler
        );

        $kontrolle = $this->tabelleProgrammbuchung->delete($whereDelete);

        return $this;
    }

    /**
     * Schaffen der Knoten zum andocken der Basisinformationen der Buchungen
     *
     * @return Front_Model_Vormerkung
     */
    private function _schaffeKnotenFuerBuchungsinformation()
    {

        for ($i = 0; $i < count($this->_vormerkungen); $i++) {
            $this->_anzeigeVormerkungen[$i]['id'] = $this->_vormerkungen[$i]['id'];
            $this->_anzeigeVormerkungen[$i]['registrierungsnummer'] = $this->_vormerkungen[$i]['hobNummer'];
            $this->_anzeigeVormerkungen[$i]['buchung'] = array();
        }

        return $this;
    }

    /**
     * Findet die Grundinformationen zu gemerkten Hotelbuchungen
     *
     * + durchsucht die Buchungsnummern nach Hotelbuchungen
     * + Anreisedatum
     * + Hotelname
     *
     *
     * @return Front_Model_Vormerkung
     */
    private function _findHoteldaten()
    {
        // durchsucht die Buchungsnummern nach Hotelbuchungen
        for ($i = 0; $i < count($this->_vormerkungen); $i++) {

            $select = $this->tabelleHotelbuchung->select();
            $select
                ->where("buchungsnummer_id = " . $this->_vormerkungen[$i]['id'])
                ->where("status = ".$this->_condition_status_vorgemerkt);

            $hotelbuchungen = $this->tabelleHotelbuchung->fetchAll($select)->toArray();

            // wenn Hotelbuchung vorliegen
            if (count($hotelbuchungen) > 0) {

                for ($j = 0; $j < count($hotelbuchungen); $j++) {

                    // Anreisedatum
                    $anreisedatum = $hotelbuchungen[$j]['startDate'];
                    $anreisedatum = nook_ToolZeiten::generiereDatumNachAnzeigesprache(
                        $anreisedatum,
                        $this->_anzeigesprache
                    );

                    // Hotelname
                    $hotel = new nook_ToolHotel();
                    $hotel->setHotelId($hotelbuchungen[$j]['propertyId']);
                    $hotelName = $hotel->getHotelName($hotelbuchungen[$j]['propertyId']);

                    // Ratenname
                    $toolCategory = new nook_ToolCategory();
                    $datenCategory = $toolCategory
                        ->setRateId($hotelbuchungen[$j]['otaRatesConfigId'])
                        ->getDatenCategory();

                    $hotelbuchung = array(
                        'startdatum' => $anreisedatum,
                        'name' => $hotelName
                    );

                    // Kategorie Name
                    if($this->_anzeigesprache == 1)
                        $hotelbuchung['ratenName'] = $datenCategory['categorie_name'];
                    else
                        $hotelbuchung['ratenName'] = $datenCategory['categorie_name_en'];

                    $this->_anzeigeVormerkungen[$i]['buchung'][] = $hotelbuchung;
                }
            }
        }

        return $this;
    }

    /**
     * Ermittelt die Anzeigewerte eines Programmes welches vorgemerkt wurde
     *
     * + durchsucht die Buchungsnummern nach Programmbuchungen
     * + Startdatum wenn vorhanden
     * + Startzeit wenn vorhanden
     * + ignoriert die Buchungspauschalen
     *
     * @return Front_Model_Vormerkung
     */
    private function _findProgrammdaten()
    {
        // durchsucht die Buchungsnummern nach Programmbuchungen
        for ($i = 0; $i < count($this->_vormerkungen); $i++) {

            $whereBuchungsnummerId = "buchungsnummer_id = " . $this->_vormerkungen[$i]['id'];

            $select = $this->tabelleProgrammbuchung->select();
            $select
                ->where($whereBuchungsnummerId)
                ->where("status = ".$this->_condition_status_vorgemerkt);

            $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

            $static = nook_ToolStatic::getStaticWerte();
            $buchungspauschaleProgrammId = $static['buchungspauschale']['programmId'];
            $buchungspauschalePreisvarianteId = $static['buchungspauschale']['preisvarianteId'];

            // wenn Programmbuchungen
            if (count($rows) > 0) {

                for ($j = 0; $j < count($rows); $j++) {

                    // Buchungspauschale ignorieren
                    if( ($rows[$j]['programmdetails_id'] == $buchungspauschaleProgrammId) and ($rows[$j]['tbl_programme_preisvarianten_id'] == $buchungspauschalePreisvarianteId) )
                        continue;

                    // Durchführungsdatum
                    $durchFuehrungsDatum = $rows[$j]['datum'];
                    $durchFuehrungsZeit = $rows[$j]['zeit'];

                    // Preisvariante hat ein Datum
                    if ($durchFuehrungsDatum != '0000-00-00') {
                        $durchFuehrungsDatum = nook_ToolZeiten::generiereDatumNachAnzeigesprache(
                            $durchFuehrungsDatum,
                            $this->_anzeigesprache
                        );

                        $programmbuchung = array(
                            'startdatum' => $durchFuehrungsDatum
                        );
                    } // Preisvariante hat kein Datum
                    else {
                        $programmbuchung = array();
                    }

                    // Preisvariante hat eine Startzeit
                    if (!empty($durchFuehrungsZeit)) {
                        $programmbuchung['startzeit'] = nook_ToolZeiten::kappenZeit($durchFuehrungsZeit, 2);
                    }

                    // Anzeigesprache
                    $kennzifferSprache = nook_ToolSprache::ermittelnKennzifferSprache();

                    // Programmname
                    $programmTool = new nook_ToolProgramm();
                    $programmTool->setProgrammId($rows[$j]['programmdetails_id']);
                    $programmName = $programmTool->getProgrammName($rows[$j]['programmdetails_id'], $kennzifferSprache);

                    $programmbuchung['name'] = $programmName;

                    $this->_anzeigeVormerkungen[$i]['buchung'][] = $programmbuchung;
                }
            }
        }

        return $this;
    }

    /**
     * ermittelt die Vormerkungen des Kunden in der Tabelle 'tbl_buchungsnummer'
     *
     * @return mixed
     * @return Front_Model_Vormerkung
     * @throws nook_Exception
     */
    private function _getVormerkungen()
    {
        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->where("kunden_id = " . $this->_kundenId)
            ->where("status = " . $this->_condition_status_vorgemerkt)
            ->order("hobNummer desc");

        $query = $select->__toString();

        $vormerkungen = $this->tabelleBuchungsnummer
            ->fetchAll($select)
            ->toArray();

        $this->_vormerkungen = $vormerkungen;

        return $this;
    }

    /**
     * Ermittelt die Anzeigesprache
     *
     * @return Front_Model_Vormerkung
     */
    private function _setAnzeigesprache()
    {
        $this->_anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();

        return $this;
    }

    /**
     * @param bool $__kundenId
     * @return Front_Model_Vormerkung
     */
    public function setKundenId($__kundenId = false)
    {
        if($this->tabelleBuchungsnummer->kontrolleValue('id', $__kundenId))
            $this->_setKundenId($__kundenId);
        else
            throw new nook_Exception($this->_error_falscher_anfangswert);

        return $this;
    }

    /**
     * Kontrolle der Kunden ID
     *
     * @return Front_Model_Vormerkung
     * @throws nook_Exception
     */
    public function checkKundenId()
    {

        if ($this->_kundenId === 0) {
            return $this;
        }

        $kundenId = (int) $this->_kundenId;
        if (!is_int($kundenId)) {
            throw new nook_Exception($this->_error_keine_kunden_id);
        }

        return $this;
    }

    /**
     * Setzen und / oder ermitteln der Kunden ID
     *
     *
     * @param bool $__kundenId
     * @return Front_Model_Vormerkung
     * @throws nook_Exception
     */
    private function _setKundenId($__kundenId = false)
    {

        if (empty($__kundenId)) {

            $sessionAuth = new Zend_Session_Namespace('Auth');
            $nameSpace = (array) $sessionAuth->getIterator();

            $userId = (int) $nameSpace['userId'];

            if (empty($userId)) {
                throw new nook_Exception($this->_error_keine_kunden_id);
            }

            $__kundenId = $userId;
        }

        $this->_kundenId = $__kundenId;

        return $this;
    }

    /**
     * Ermittelt ob der Kunde Vormerkungen hat
     *
     * @return bool
     */
    private function _getStatusVormerkungen()
    {
        $statusVormerkungen = false;

        $select = $this->tabelleBuchungsnummer->select();

        $cols = array(
            new Zend_Db_Expr('count(id) as vormerkungen')
        );

        $select
            ->from($this->tabelleBuchungsnummer, $cols)
            ->where("kunden_id = " . $this->_kundenId)
            ->where("status = " . $this->_condition_status_vorgemerkt);

        $ergebnis = $this->tabelleBuchungsnummer->fetchRow($select);

        if (!empty($ergebnis)) {
            $row = $ergebnis->toArray();

            if ($row['vormerkungen'] > 0) {
                $statusVormerkungen = true;
            }
        }

        return $statusVormerkungen;
    }

    /**
     * Löscht eine Vormerkung
     *
     * @param $__buchungsnummer
     * @return Front_Model_Vormerkung
     */
    public function loeschenVormerkung($__buchungsnummer)
    {
        $this
            ->_setKundenId()
            ->_loeschenVormerkung($__buchungsnummer);

        return $this;
    }

    /**
     * Durchführung des löschen einer Vormerkung
     *
     * @param $buchungsnummerId
     * @return Front_Model_Vormerkung
     * @throws nook_Exception
     */
    private function _loeschenVormerkung($buchungsnummerId)
    {

        $select = $this->tabelleBuchungsnummer->select();
        $select
            ->where("kunden_id = " . $this->_kundenId)
            ->where("id = " . $buchungsnummerId);

        $rows = $this->tabelleBuchungsnummer->fetchAll($select)->toArray();

        if (count($rows) != 1)
            throw new nook_Exception($this->_error_keine_vormerkung_vorhanden);

        $update = array(
            "status" => $this->_condition_vormerkung_geloescht
        );

        $where = "id = " . $buchungsnummerId;

        $ergebnis = $this->tabelleBuchungsnummer->update($update, $where);

        if ($ergebnis != 1) {
            throw new nook_Exception($this->_error_datenbank);
        }

        return $this;
    }

    /**
     * @param $__buchungsnummer
     * @return mixed
     */
    public function getSessionIdDerVormerkung($__buchungsnummer)
    {
        $sessionIdDerVormerkung = $this->_getSessionIdDerVormerkung($__buchungsnummer);

        return $sessionIdDerVormerkung;
    }

    /**
     * Ermittelt die Session ID einer Vormerkung
     *
     * @param $__buchungsnummer
     * @return mixed
     * @throws nook_Exception
     */
    private function _getSessionIdDerVormerkung($__buchungsnummer)
    {
        $sessionIdDerVormerkung = false;

        $rows = $this->tabelleBuchungsnummer
            ->find($__buchungsnummer)
            ->toArray();

        if (count($rows) > 1)
            throw new nook_Exception('Buchungsnummer mehrfach vorhanden');

        if(count($rows) < 1)
            throw new nook_Exception('Buchungsnummer nicht vorhanden');

        $sessionIdDerVormerkung = $rows[0]['session_id'];

        return $sessionIdDerVormerkung;
    }

    /**
     * Setzen des Zaehler einer Buchung
     *
     * @param $zaehler
     * @return Front_Model_Vormerkung
     * @throws nook_Exception
     */
    public function setZaehlerDerBuchung($zaehler)
    {
        if( (!filter_var($zaehler, FILTER_VALIDATE_INT)) or ($zaehler == 0) )
            throw new nook_Exception($this->_error_falscher_anfangswert);
        else
            $this->zaehler = $zaehler;

        return $this;
    }
}

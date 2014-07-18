<?php
/**
 * Erstellt die Liste der Hotels in einer Stadt.
 * Es werden nur die Hotels aufgelistet,
 * die über die entsprechnde Kapazität am Anreisetag verfügen.
 *
 * @author Stephan Krauß
 * @date 26.11.2012
 */
class Front_Model_Hotellist extends nook_Model_model
{

    protected $_datenSuchanfrage = array();
    protected $_dic = array();


    // Fehler
    private $_error_not_correct_input_data = 360;
    private $_error_kein_int = 361;

    // Datenbanken
    private $_tabelleAoCity = null;

    // Konditionen
    // private $_condition_xxx_yyy = 5;
    // private $_condition_xxx_zzz = "Test";

    /**
     * Datenbank Adapter und
     * Tabellen der Datenbanken
     */
    public function __construct()
    {
        $this->_db_hotel = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');

        // $this->_tabelleAoCity = new Application_Model_DbTable_aoCity();
    }

    /**
     * setzen Dependency Injection Container
     * Es gibt eine bessere Variante.
     * Diese Idee nicht mehr verwenden.
     *
     * @param $__objectName
     * @param $__object
     */
    public function setDic($__objectName, $__object)
    {
        $this->_dic[$__objectName] = $__object;

        return;
    }

    /**
     * Erstellt die Anzeige der BreadCrumb
     *
     * @param $__bereich
     * @param $__step
     * @param $__params
     * @return
     */
    public function getBreadCrumb($__bereich, $__step, $__params)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Übernahme der datn der,
     * Suchanfrage
     *
     * @param $__suchparameter
     * @return Front_Model_Hotellist
     */
    public function setDatenDerSuchanfrage($__suchparameter)
    {
        $this->_datenSuchanfrage = $__suchparameter;

        return $this;
    }

    /**
     * Gibt die Hotelbeschreibung zurück
     *
     * @return array
     */
    public function getHotelsMitBeschreibung()
    {

        /** @var $business Front_Model_Hotellistbusiness */
        $business = $this->_dic['business'];
        $ergaenzteSuchparameter = $business->setDatenDerSuchanfrage($this->_datenSuchanfrage);

        // speichern der Suchparameter in der Session
        $suchparameterHotels = new Zend_Session_Namespace('hotelsuche');

        foreach ($ergaenzteSuchparameter as $key => $value) {
            $suchparameterHotels->$key = $value;
        }

        // ermittelt die Hotels in einer Stadt
        $hotelsEinerStadt = $business->getHotelsInEinerStadt();

        /** @var $ratenkontrolle nook_ratenkontrolle */
        $ratenkontrolle = $this->_dic['kontrolleRaten'];
        $startDatum = $this->_datenSuchanfrage['from'];
        $ratenkontrolle->setStartDate($startDatum);
        $ratenkontrolle->setHotelList($hotelsEinerStadt);

        // ermittelt die Hotels mit Kapazität
        $hotelsEinerStadt = $ratenkontrolle->getKontrollierteListeDerHotels();

        // Dummy Funktion für Messe !!!
//        $modelDummyMesse =  new nook_ToolDummyMesse();
//        $hotelsEinerStadt = $modelDummyMesse
//            ->setStadtId($this->_datenSuchanfrage['city'])
//            ->getHotelsEinerStadt($hotelsEinerStadt);

        $hotelsMitBeschreibung = $business->getHotelsMitBeschreibung($hotelsEinerStadt);

        return $hotelsMitBeschreibung;
    }

    /**
     * Kontrolliert die Ankommenden
     * Suchparameter.
     * Kontrolle der Parameter die vom Client kommen
     * auf Logik.
     *
     * @param $__suchparameter
     * @return Front_Model_Hotellist
     * @throws nook_Exception
     */
    public function setDatenSuchanfrage($__suchparameter)
    {
        $__suchparameter['adult'] = (int)$__suchparameter['adult'];
        $__suchparameter['city'] = (int)$__suchparameter['city'];

        // Kontrolle Personenanzahl
        if ($__suchparameter['adult'] == 0)
            throw new nook_Exception('Personenanzahl fehlt');

        // Kontrolle Stadt ID
        if ($__suchparameter['city'] == 0)
            throw new nook_Exception('ID der Stadt fehlt');

        $datum = nook_Tool::buildUnixFromDateByLanguage($__suchparameter['from']);

        if ($datum['unixDatum'] > 0) {

            if (empty($datum['unixDatum']) or empty($datum['tag']) or empty($datum['monat']) or empty($datum['jahr']))
                throw new nook_Exception($this->_error_not_correct_input_data);

            $this->_datenSuchanfrage['startDatumUnix'] = $datum['unixDatum'];
            $this->_datenSuchanfrage['startDatumTag'] = $datum['tag'];
            $this->_datenSuchanfrage['startDatumMonat'] = $datum['monat'];
            $this->_datenSuchanfrage['startDatumJahr'] = $datum['jahr'];
        }

        return $this;
    }

    /**
     * Entfernt überflüssige Parameter.
     *
     * @param $__suchparameter
     * @return mixed
     */
    public function mapSuchdaten($__suchparameter)
    {
        unset($__suchparameter['module']);
        unset($__suchparameter['controller']);
        unset($__suchparameter['action']);
        unset($__suchparameter['suchen']);

        return $__suchparameter;
    }

    /**
     * Gibt den Stadtnamen zurück.
     *
     * @param $__params
     * @return string
     */
    public function getCityCrumb($__params)
    {
        include_once('../library/nook/breadcrumb.php');
        $breadcrumb = new breadcrumb($__params);

        return $breadcrumb->getCityName();
    }

}
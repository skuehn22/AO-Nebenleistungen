<?php
class Front_Model_Hotelsearch extends nook_Model_model
{

    // public $error_no_password_found = 350;
    private $_tabelleStadtbeschreibung = null;

    public function __construct()
    {
        /** @var _tabelleStadtbeschreibung Application_Model_DbTable_stadtbeschreibung */
        $this->_tabelleStadtbeschreibung = new Application_Model_DbTable_stadtbeschreibung();
    }

    /**
     * Ermittelt die Werte der Krümelnavigation
     *
     * @param $__bereich
     * @param $__step
     * @param array $__params
     * @return array
     */
    public function getNavigationCrumb($__bereich, $__step, array $__params){

        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Darstellen der Arbeitsschritte
     *
     * @param $__params
     * @return string
     */
    public function getCityCrumb($__params){
        include_once('../library/nook/breadcrumb.php');
        $breadcrumb = new breadcrumb($__params);

        return $breadcrumb->getCityName();
    }

    /**
     * allgemeine Beschreibung der Übernachtungsmöglichkeiten
     * in einer Stadt
     *
     * @param $__CityId
     * @return string
     */
    public function getHighlightUebernachtungDerStadt($__CityId){
        $spracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        $cols = array(
            'stadtbeschreibung'
        );

        $select = $this->_tabelleStadtbeschreibung->select();
        $select
            ->from($this->_tabelleStadtbeschreibung, $cols)
            ->where('city_id = '.$__CityId)
            ->where('sprache_id = '.$spracheId);

        $query = $select->__toString();

        $uebernachtungsmoeglichkeitenInEinerStadt = $this->_tabelleStadtbeschreibung->fetchAll($select)->toArray();

        return $uebernachtungsmoeglichkeitenInEinerStadt[0]['stadtbeschreibung'];
    }


}
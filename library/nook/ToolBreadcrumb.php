<?php
/**
 * 04.09.12 14:15
 * Fehlerbereich:
 * Beschreibung der Klasse
 *
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 * @subpackage Bausteinname
 */

class nook_ToolBreadcrumb
{

    private $_controller = null;
    private $_action = null;
    private $_bereich = null;
    private $_step = null;

    private $_datenNavigation = array();
    private $_navigation = null;

    private $_error_parameter_nicht_vorhanden = 861;

    private $_tabelleBreadcrumb = null;

    private $_condition_beginn_warenkorb = 10;
    private $_condition_kategoriewahl = 1;
    private $_condition_bereich_programme = 1;

    /**
     * Erstellt die Rohnavigation
     *
     */
    public function __construct()
    {

        /** @var $_tabelleBreadcrumb Application_Model_DbTable_breadcrumb */
        $this->_tabelleBreadcrumb = new Application_Model_DbTable_breadcrumb(array( 'db' => 'front' ));

    }

    /**
     * Übernimmt den Verarbeitungsschritt / Step
     * Übernimmt den Bereich
     *
     * @param $__step
     * @return nook_ToolBreadcrumb
     */
    public function setBereichStep($__bereich = false, $__step = false)
    {

        if (empty($__step) or !is_int($__step)) {
            throw new nook_Exception($this->_error_parameter_nicht_vorhanden);
        }

        if (empty($__bereich) or !is_int($__bereich)) {
            throw new nook_Exception($this->_error_parameter_nicht_vorhanden);
        }

        // kontrolliert ob identischer Ablauf der Bereiche
        if ($__step == $this->_condition_kategoriewahl or $__step >= $this->_condition_beginn_warenkorb) {
            $__bereich = $this->_condition_bereich_programme;
        }

        $this->_step = $__step;
        $this->_bereich = $__bereich;

        return $this;
    }

    /**
     * speichert und kontrolliert die Parameter
     * + module
     * + controller
     * + action
     *
     * @param $params
     * @return nook_ToolBreadcrumb
     */
    public function setParams($params)
    {
        // Kontrolle des Modul
        if ($params['module'] != 'front') {
            throw new nook_Exception('kein Front Baustein');
        }

        // Kontrolle controller
        if (!array_key_exists('controller', $params)) {
            throw new nook_Exception('kein Controller vorhanden');
        }

        // Kontrolle action
        if (!array_key_exists('action', $params)) {
            throw new nook_Exception('keine Action vorhanden');
        }

        $this->_controller = $params['controller'];
        $this->_action = $params['action'];

        return $this;
    }

    /**
     * Gibt die generierte Navigation zurück
     *
     * @return array
     */
    public function getNavigation()
    {

        $this
            ->_ermittleRohdatenNavigation()
            ->_erstellenNavigation();

        return $this->_navigation;
    }

    /**
     * Ermittelt die Rohdaten der Navigation
     *
     * @return nook_ToolBreadcrumb
     */
    private function _ermittleRohdatenNavigation()
    {
        /** @var $select Zend_Db_Table_Select */
        $select = $this->_tabelleBreadcrumb->select();

        // Kategoriewahl
        if ($this->_step == $this->_condition_kategoriewahl) {
            $select->where("step = " . $this->_condition_kategoriewahl);
        } // Warenkorb
        elseif ($this->_step >= $this->_condition_beginn_warenkorb) {
            $select->where(
                'bereich = ' . $this->_bereich . " and step >= " . $this->_condition_beginn_warenkorb
            );
        } // Bereiche
        else {
            $select->where(
                'bereich = ' . $this->_bereich . " or step = " . $this->_condition_kategoriewahl . " or step >= " . $this->_condition_beginn_warenkorb
            );
        }

        $select->order('step', 'asc');

        $this->_datenNavigation = $this->_tabelleBreadcrumb->fetchAll($select)->toArray();

        return $this;
    }

    /**
     * Erstellen der Navigation
     *
     * @return nook_ToolBreadcrumb
     */
    private function _erstellenNavigation()
    {
        $navigation = null;

        for ($i = 0; $i < count($this->_datenNavigation); $i++) {

            // Übersetzung
            $this->_datenNavigation[$i]['name'] = translate($this->_datenNavigation[$i]['name']);

            if ($this->_datenNavigation[$i]['step'] == $this->_condition_beginn_warenkorb and $i > 0) {
                $navigation .= "<br>";
            }

            if ($this->_step == $this->_datenNavigation[$i]['step']) {
                $navigation .= "<span id='step" . $i . "' class='aktiveStep'>" . $this->_datenNavigation[$i]['name'] . "</span> | ";
            } else {
                $navigation .= "<span id='step" . $i . "' class='passivStep'>" . $this->_datenNavigation[$i]['name'] . "</span> | ";
            }
        }

        $navigation = substr($navigation, 0, -2);

        $this->_navigation = $navigation;

        return $this;
    }

} // end class

<?php
/**
 * Bearbeitet den Datensatz der Öffnungszeiten eines Programmes
 *
 * + Kontrollier die ankommenden Öffnungszeiten
 * + Mappen der Öffnungszeiten
 * + Schreibt die Öffnungszeiten eines Programmes in 'tbl_programmdetails_oeffnungszeiten'
 * + Holt die Öffnungszeiten eines Programmes
 * + Bereitet die ankommenden Öffnungszeiten der Wochentage auf
 *
 * @author Stephan.Krauss
 * @date 21.27.2013
 * @file DatensatzOeffnungszeiten.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_DatensatzOeffnungszeiten extends nook_Model_model
{

    // Fehler
    private $_error_keine_programmId_vorhanden = 570;

    // Konditionen
    private $_montag = 1;
    private $_dienstag = 2;
    private $_mittwoch = 3;
    private $_donnerstag = 4;
    private $_freitag = 5;
    private $_sonnabend = 6;
    private $_sonntag = 7;

    private $condition_wochentag_ist_geschaeftstag = 2;

    private $_wochentage = array(
        1 => 'montag',
        2 => 'dienstag',
        3 => 'mittwoch',
        4 => 'donnerstag',
        5 => 'freitag',
        6 => 'sonnabend',
        7 => 'sonntag'
    );

    // Flags

    // Tabellen / Views

    /**
     * @var Zend_Db_Adapter
     */
    private $_db_front;

    // Programm ID
    private $_programmId;

    // Auth
    private $_auth;

    public function __construct()
    {
        $this->_db_front = Zend_Registry::get('front');
        $this->_auth = new Zend_Session_Namespace('Auth');

        return;
    }

    /**
     * Kontrollier die ankommenden Öffnungszeiten
     *
     * @param $__params
     * @throws nook_Exception
     */
    public function checkOeffnungszeiten($__params)
    {

        if (!array_key_exists('programmId', $__params)) {
            throw new nook_Exception($this->_error_keine_programmId_vorhanden);
        }

        $kontrolle = filter_var($__params['programmId'], FILTER_VALIDATE_INT);

        if (empty($kontrolle)) {
            throw new nook_Exception($this->_error_keine_programmId_vorhanden);
        }

        $this->_programmId = $__params['programmId'];

        return;
    }

    /**
     * Mappen der Öffnungszeiten
     *
     * @param $rawOeffnungszeiten
     * @return array
     */
    public function mapOeffnungszeiten($rawOeffnungszeiten)
    {
        unset($rawOeffnungszeiten['module']);
        unset($rawOeffnungszeiten['controller']);
        unset($rawOeffnungszeiten['action']);

        $oeffnungszeiten = array();
        $i = 0;

        if (array_key_exists('montag', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_montag;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startMontag'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeMontag'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('dienstag', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_dienstag;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startDienstag'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeDienstag'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('mittwoch', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_mittwoch;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startMittwoch'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeMittwoch'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('donnerstag', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_donnerstag;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startDonnerstag'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeDonnerstag'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('freitag', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_freitag;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startFreitag'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeFreitag'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('sonnabend', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_sonnabend;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startSonnabend'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeSonnabend'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        if (array_key_exists('sonntag', $rawOeffnungszeiten)) {
            $oeffnungszeiten[$i]['wochentag'] = $this->_sonntag;
            $oeffnungszeiten[$i]['von'] = $rawOeffnungszeiten['startSonntag'];
            $oeffnungszeiten[$i]['bis'] = $rawOeffnungszeiten['endeSonntag'];
            $oeffnungszeiten[$i]['programmdetails_id'] = $this->_programmId;
            $oeffnungszeiten[$i]['geschaeftstag'] = $this->condition_wochentag_ist_geschaeftstag;

            $i++;
        }

        return $oeffnungszeiten;
    }

    /**
     * @param $__programmId
     * @return Admin_Model_DatensatzOeffnungszeiten
     */
    public function setProgrammId($__programmId)
    {
        $this->_programmId = $__programmId;

        return $this;
    }

    /**
     * Schreibt die Öffnungszeiten eines Programmes in 'tbl_programmdetails_oeffnungszeiten'
     *
     * + löschen der Öffnungszeiten eines Programmes
     * + eintragen der Öffnungszeiten der Geschäftstage eines Programmes
     *
     * @param $__oeffnungszeiten
     * @return Admin_Model_DatensatzOeffnungszeiten
     */
    public function setOeffnungszeiten($__oeffnungszeiten)
    {
        // loeschen Öffnungszeiten
        $sql = "delete from tbl_programmdetails_oeffnungszeiten where programmdetails_id = " . $this->_programmId;
        $this->_db_front->query($sql);

        // eintragen Öffnungszeiten
        for ($i = 0; $i < count($__oeffnungszeiten); $i++) {
            $this->_db_front->insert('tbl_programmdetails_oeffnungszeiten', $__oeffnungszeiten[$i]);
        }

        return $this;
    }

    /**
     * Holt die Öffnungszeiten eines Programmes
     *
     * @return array
     */
    public function getOeffnungszeiten()
    {
        $sql = "select * from tbl_programmdetails_oeffnungszeiten where programmdetails_id = " . $this->_programmId;
        $oeffnungszeiten = $this->_db_front->fetchAll($sql);

        // umwandeln Öffnungszeiten für Formular
        if (!empty($oeffnungszeiten)) {
            $oeffnungszeiten = $this->_umwandelnOeffnungszeitenFuerForm($oeffnungszeiten);
        }

        return $oeffnungszeiten;
    }

    /**
     * Bereitet die ankommenden Öffnungszeiten der Wochentage auf
     * + checkt ob Wochentag ein Geschäftstag ist
     *
     * @param array $rawOeffnungsZeiten
     * @return array
     */
    private function _umwandelnOeffnungszeitenFuerForm(array $rawOeffnungsZeiten)
    {
        $oeffnungszeiten = array();

        for ($i = 0; $i < count($rawOeffnungsZeiten); $i++) {
            $tagesZahl = $rawOeffnungsZeiten[$i]['wochentag'];
            $tagesName = $this->_wochentage[$tagesZahl];
            $von = 'start' . ucfirst($tagesName);
            $bis = 'ende' . ucfirst($tagesName);

            if ($rawOeffnungsZeiten[$i]['geschaeftstag'] == $this->condition_wochentag_ist_geschaeftstag) {
                $oeffnungszeiten[$tagesName] = 'on';
            }

            $oeffnungszeiten[$von] = substr($rawOeffnungsZeiten[$i]['von'], 0, -3);
            $oeffnungszeiten[$bis] = substr($rawOeffnungsZeiten[$i]['bis'], 0, -3);
        }

        return $oeffnungszeiten;
    }

}
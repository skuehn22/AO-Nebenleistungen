<?php
/**
 * Kontrolle und wechsel des Buchungstypes eines Programmes
 *
 *
 *
 * @author Stephan.Krauss
 * @date 18.04.13
 * @file DatensatzBuchungstyp.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_DatensatzBuchungstyp extends nook_ToolModel
{

    // Error
    private $_error_daten_nicht_vorhanden = 1420;
    private $_error_datenbank_operation_fehlgeschlagen = 1421;

    // Konditionen
    private $_condition_buchungstyp_offline = 1;
    private $_condition_buchungstyp_online = 2;


    // Flags

    protected $_pimple = null;
    protected $_programmId = null;

    public function __construct ($pimple = false)
    {

        if(!empty($pimple)) {
            $this->_pimple = $pimple;
        }
    }

    /**
     * @param bool $pimple
     * @return Admin_Model_DatensatzBuchungstyp
     */
    public function setPimple ($pimple)
    {

        $this->_pimple = $pimple;

        return $this;
    }

    /**
     * @param int $programmId
     * @return Admin_Model_DatensatzBuchungstyp
     */
    public function setProgrammId ($programmId)
    {
        $programmId = (int) $programmId;
        $this->_programmId = $programmId;

        return $this;
    }

    /**
     * Kontrolle Programm ID auf Int
     *
     * @param int $programmId
     * @return Admin_Model_DatensatzBuchungstyp
     * @throws nook_Exception
     */
    public function isValidProgrammId ($programmId)
    {
        $programmId = (int) $programmId;
        if((!is_int($programmId)) or ($programmId === 0)) {
            return false;
        }

        return true;
    }

    /**
     * Steuerung des Wechsel Buchungstyp
     *
     * @return int
     * @throws nook_Exception
     */
    public function wechselBuchungstypProgramm ()
    {
        if(empty($this->_programmId)) {
            throw new nook_Exception($this->_error_daten_nicht_vorhanden);
        }

        $kontrolle = $this->_wechselBuchungstypProgramm();

        return $kontrolle;
    }

    /**
     * Wechselt den Buchungstyp
     *
     * + Kontrolliert ob ein Update stattgefunden hat
     *
     * @see Application_Model_DbTable_programmedetails
     * @return int
     */
    private function _wechselBuchungstypProgramm ()
    {
        /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->_pimple[ 'tabelleProgrammdetails' ];

        $cols = array(
            'buchungstyp' => new Zend_Db_Expr("if(buchungstyp = " . $this->_condition_buchungstyp_offline . "," . $this->_condition_buchungstyp_online . "," . $this->_condition_buchungstyp_offline . ")")
        );

        $kontrolle = $tabelleProgrammdetails->update($cols, "id = " . $this->_programmId);

        if(empty($kontrolle))
           throw new nook_Exception($this->_error_datenbank_operation_fehlgeschlagen);

        return $kontrolle;
    }

}
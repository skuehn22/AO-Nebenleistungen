<?php
/**
 * Schreibt den Log. Tabelle Log und allgemeine Loginformation . 'tbl_log'
 *
 * @author Stephan Krauss
 * @date 05.12.14
 * @package tool
 */

class nook_ToolLog {

    /**
     * Test für Firebug. Noch nicht überprüft. Verwendet Logfunktion aus der Bootstrap
     *
     * @static
     * @param $__message
     * @param null $__label
     * @param int $__status
     * @return 
     */
    public static function schreibeLog($__message, $__label = null, $__status = Zend_Log::DEBUG){
        if($__label != null)
            $message = array($__label => $__message);

        if(Zend_Registry::isRegistered('log')){
            $log = Zend_Registry::get('log');
            $log->log($__message, $__status);
        }

        return;
    }

    /**
     * Schreibt eine Information in den Log in die Tabelle 'tbl_log'
     *
     * @static
     * @param $label
     * @param $information
     * @param null $datei
     * @param null $zeile
     * @return int
     */
    public static function schreibeLogInTabelle($label, $information, $datei = null, $zeile = null){
        $log = array();

        // Umbau eines Array in einen JSON String
        if(is_array($information))
            $information = json_encode($information);

        $log['label'] = $label;
        $log['information'] = $information;

        if(!empty($datei))
            $log['datei'] = $datei;

        if(!empty($zeile))
            $log['zeile'] = $zeile;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Zend_Registry::get('front');
        $kontrolle = $db->insert('tbl_log', $log);

        return $kontrolle;
    }

} // end class

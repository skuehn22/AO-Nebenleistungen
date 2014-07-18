<?php
/**
 * Registriert die Informationen des System
 *
 * + Singleton
 * + nimmt Error Code entgegen
 * + speichert Meldung in 'tbl_exception'
 *
 *
 * @author stephan.krauss
 * @date 07.06.13
 * @file ExceptionInformationRegistration.php
 * @package tools
 */
class nook_ExceptionInformationRegistration {

    // Tabellen / Views
    private $tabelleException = null;

    // Konditionen
    private $_condition_fehler_typ_information = 2;

    // Fehler
    private $error_fehler_nummer_fehlt = 1600;

    protected $error = array();
    protected $fehlerNummer = null;
    protected  static $instance = null;

    private $_mailAdresse = null;
    private $_from = null;
    private $_mailSenden = null;

    private $_mailInformationVersenden = 1; // 1 = nein , 2 = ja

    private function __construct ()
    {
        /** @var  tabelleException Application_Model_DbTable_exception */
        $this->tabelleException = new Application_Model_DbTable_exception();

        $static = Zend_Registry::get('static')->toArray();
        $this->_mailAdresse = $static['debugModus']['mailadresse'];
        $this->_from = $static['debugModus']['from'];
        $this->_mailSenden = $static['debugModus']['mail'];
    }

    private function __clone(){}

    private function __wakeup(){}

    /**
     * Startet die Anwendung
     *
     * @param $errorCode
     * @return nook_ExceptionInformationRegistration
     */
    public function setErrorCode($errorCode)
    {
        $this->error['blockCode'] = $errorCode;
        $this->error['code'] = '';
        $this->error['reaction'] = $this->_condition_fehler_typ_information;
        $this->error['file'] = "Information";
        $this->error['date'] = date("d.m.Y H:i:s");

        $this->verarbeitenErrorCode();

        return $this;
    }

    /**
     * Führt die Verarbeitung der Information durch
     */
    private function verarbeitenErrorCode()
    {
        $this->ermittelnTrace();
        $this->eintragenError();

        return;
    }

    /**
     * Trägt die Information in der Datenbank ein
     *
     * @return int
     */
    private function eintragenError()
    {
        $this->fehlerNummer = $this->tabelleException->insert($this->error);

        return $this->fehlerNummer;
    }

    /**
     * Ermittelt den Trace der Information
     */
    private function ermittelnTrace()
    {
        $errorTrace = "";
        $backTrace = debug_backtrace();

        $i = 0;
        foreach($backTrace as $traceInformationArray){

            if(is_array($traceInformationArray)){
                $errorTrace .= "#".$i." ".$traceInformationArray['file']."(".$traceInformationArray['line'].") \n";
                $errorTrace .= "#".$i." ".$traceInformationArray['class'].$traceInformationArray['type'].$traceInformationArray['function']."(".$traceInformationArray['line'].") \n";

                if(count($traceInformationArray['args']) > 1){
                    for($j=0; $j < count($traceInformationArray['args']); $j++){

                        if( is_object($traceInformationArray['args'][$j]) and get_class($traceInformationArray['args'][$j]) == 'Zend_Controller_Request_Http' ){
                            $requestObject = $traceInformationArray['args'][$j];
                            $args = $requestObject->getParams();

                            foreach($args as $key => $value){
                                $errorTrace .= "#".$i." ".$key." = '".$value."' \n";
                            }
                        }
                    }
                }

                $i++;
            }
        }

        $this->error['trace'] = $errorTrace;

        return;
    }

    /**
     * Singleton zum starten des Wächter
     *
     * @param $errorCode
     * @return bool
     */
    public static function registerError($errorCode)
    {
        if(!isset(self::$instance)){
            $className = __CLASS__;
            self::$instance = new $className;
        }

        self::$instance->setErrorCode($errorCode);

        return true;
    }
} // end class
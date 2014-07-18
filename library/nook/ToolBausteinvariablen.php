<?php
/**
 * 04.09.12 14:15
 * Fehlerbereich: 880
 * Wiederverwendung von Parametern in einem Baustein
 * 
 * @author Stephan Krauß
 */
 
class nook_ToolBausteinvariablen {

    // private $_error = 880;
    private $_normalerAufruf = null;
    private $_bereich = null;

    /**
     * Übernimmt die Step - Nummer des vorhergehenden
     * Baustein.
     * Übernimmt die Bereichsnummer des aktuellen Baustein
     * Übernimmt die Parameter
     *
     * @param array $__params
     * @param int $__bereich
     * @param int $__normalStep
     * @return array|ArrayObject
     */
    public function ablaufBereichStep( array $__params, $__bereich, $__normalStep){
        $this->_normalerAufruf = $__normalStep;
        $this->_bereich = $__bereich;

        $params = $this->_getVariables($__params);
        return $params;
    }

    /**
     * Überprüft ob eine Step - Information vorliegt
     * Kontroliert ob der Aufruf der Abfolge des Bereiches entspricht.
     * Wenn die Abfolge nicht stimmt werden die Parameter des vorhergehenden
     * Aufrufes zurück gegeben
     *
     * @param array $__params
     * @return array|ArrayObject
     */
    private function _getVariables(array $__params){

        // Namespace mit dem Namen des Controllers
        $namespace = new Zend_Session_Namespace($__params['controller']);

        // holt aus der Session_Namespace die gespeicherten Parameter
        if( (array_key_exists('step', $__params)) and ($__params['step'] != $this->_normalerAufruf) ){
            $params = $namespace->getIterator();

            return $params;
        }
        
        // speichert die Parameter in der Session_Namespace
        foreach($__params as $key => $value){
            $namespace->$key = $value;
        }

        return $__params;
    }

} // end class

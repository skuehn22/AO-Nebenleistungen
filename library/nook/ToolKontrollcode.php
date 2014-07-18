<?php
/**
 * generiert und verschlüsselt einen Kontrollcode.
 * Entschlüsselt einen Kontrollcode.
 * Gibt ein lesbares datum zurück.
 *
 * @author Stephan.Krauss
 * @date 18.01.13
 * @file ToolKontrollcode.php
 */

class nook_ToolKontrollcode{

    /**
     * @return bool
     */
    public function getKontrollcode(){

        $kontrollcode = $this->_getKontrollcode();

        return $kontrollcode;
    }

    /**
     * Zeitstempel wird mit Salt - Wert
     * angereichert
     *
     * @return bool
     */
    private function _getKontrollcode(){
        $kontrollcode = time();
        $kontrollcode += $this->_salt();

        return $kontrollcode;
    }

    /**
     * Übernimmt Kontrollcode
     * und gibt Datum zurück
     *
     * @param $__kontrollcode
     * @return bool
     */
    public function getDatum($__kontrollcode){
        $datum = $this->_getDatum($__kontrollcode);

        return $datum;
    }

    /**
     * Übernimmt Kontrollcode
     * und gibt Datum zurück
     *
     * @param $__kontrollcode
     * @return string
     */
    private function _getDatum($__kontrollcode){
        $__kontrollcode -= $this->_salt();

        $datum = date("d.m.Y H:i:s", $__kontrollcode);

        return $datum;
    }

    /**
     * Übernimmt Kontrollcode und gibt
     * Timestamp zurück
     *
     * @param $__kontrollcode
     * @return mixed
     */
    public function getTimestamp($__kontrollcode){
        $timestamp = $this->_getTimestamp($__kontrollcode);

        return $timestamp;
    }

    /**
     * Ermittelt Timestamp
     *
     * @param $__kontrollcode
     * @return mixed
     */
    private function _getTimestamp($__kontrollcode){

        $timestamp = $__kontrollcode - $this->_salt();

        return $timestamp;
    }

    /**
     * Ermittelt Salt Wert für
     * Kontrollcode
     *
     * @return bool
     */
    private function _salt(){

        $saltWert = nook_ToolKonfiguration::getKonfigurationsVariable('bestaetigungscode','salt');

        return $saltWert;
    }

}
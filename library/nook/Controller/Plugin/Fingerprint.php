<?php
/**
 * Fingerprint einer Session
 *
 * Berechnet den Fingerprint einer Session.
 * Vergleicht den berechneten und gespeicherten
 * Fingerprint. Sperrt das System, wenn Fingerprint falsch ist.
 *
 * @author Stephan.Krauss
 * @date 07.03.13
 * @file Fingerprint.php
 * @package plugins
 */

class Plugin_Fingerprint extends Zend_Controller_Plugin_Abstract {

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request){

        $stringSalt = Zend_Registry::get('static')->geheim->salt;

        $modelSessionFingerprint = new nook_ToolSecureSession();

        $modelSessionFingerprint
            ->setIpBlocks(4)
            ->setCheckBrowser(true)
            ->setSalt($stringSalt)
            ->kontrolleFingerprint();
    }

} // end class

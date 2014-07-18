<?php
/**
 * Authentifikation des Benutzer
 *
 * + Authentifiziert den User oder Superuser
 * + Trägt den Kunden in die 'tbl_adressen_superuser'
 * + Wenn Superuser festgestellt wurde,
 * + Kontrolle Superuser
 * + Authentifiziert einen Kunden
 * + Kontrolliert Benutzerkennung und Passwort.
 *
 * @author Stephan.Krauss
 * @date 04.05.2013
 * @file Auth.php
 * @package plugins
 */
class Plugin_Subdomain extends Zend_Controller_Plugin_Abstract
{

    /**
     * Erkennen und registrieren der Subdomain
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
    {
        $hostName = $request->getHttpHost();
        preg_match("#^([a-z0-9\-]+)#i",$hostName, $subdomain);
        Zend_Registry::set('subdomain',$subdomain[1]);

        // Layout Austria
        if($subdomain[1] == 'austria'){
            $layout = Zend_Layout::getMvcInstance();
            $path = $layout->getLayoutPath();

            // ersetzt Layout verzeichnis 'scripts' mit 'austria'
            $austriaPath = str_replace('scripts','austria',$path);
            $layout->setLayoutPath($austriaPath);

            // setzen Layout Name
            $layout->setLayout('front');
        }

        return;

    }
}
<?php

/**
 * registriert die ankommende URL in der Tabelle 'tbl_url'
 *
 * + eintragen der existierenden URL in der Tabelle
 * + Alias deutsch
 * + Alias englisch
 *
 * @author Stephan Krauss
 * @date 07.07.2014
 * @file urlRewrite.php
 * @project HOB
 * @package plugin
 */
class Plugin_UrlRewrite extends Zend_Controller_Plugin_Abstract
{

    /**
     * schreibt die ankommende URL bei Bedarf in 'tbl_url'
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup($request)
    {
        $url = $request->getRequestUri();
        if(strstr($url, 'front')){
            $urlTeile = explode('/',$url);

            $urlRegistrieren = $urlTeile[1]."_".$urlTeile[2]."_".$urlTeile[3];

            // eintragen der URL in die Tabelle 'tbl_url'
            $tabelleUrl = new Application_Model_DbTable_url();
            $kontrolle = $tabelleUrl->updateUrlAlias($urlRegistrieren);

            if ($kontrolle == 2)
                throw new nook_Exception("URL in Tabelle 'tbl_url' mehrfach vorhanden");
        }

        return;
    }
}
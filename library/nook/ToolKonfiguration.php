<?php
/**
 * Werkzeuge zur Verwaltung der Konfiguration
 * der datei 'static.ini'
 *
 * @author Stephan.Krauss
 * @date 18.01.13
 * @file ToolKonfiguration.php
 */
class nook_ToolKonfiguration{

    /**
     * Gibt eine Variable der 'static.ini'
     * zurück. Ist die Variable nicht vorhanden
     * dann 'false'
     *
     * @param $__bereich
     * @param $__variable
     * @return bool
     */
    public static function getKonfigurationsVariable($__bereich, $__variable){
        /** @var $static Zend_Config_Ini */
        $static = Zend_Registry::get('static');
        $staticVariablen = $static->toArray();

        if(!array_key_exists($__variable, $staticVariablen[$__bereich]))
            return false;
        else
            return $staticVariablen[$__bereich][$__variable];
    }

}
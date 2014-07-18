<?php
/**
* Ermitteln der Anzeigesprache
*
* + Ermittelt die Kennziffer der Sprache
* + gibt die Anrede entsprechend der Sprache zurück
* + gibt die Kennung der Sprache zurück
*
* @author Stephan.Krauss
* @date 13.08.2013
* @file ToolSprache.php
* @package tools
*/
class nook_ToolSprache{

    /**
     * Ermittelt die Kennziffer der Sprache der Anzeigesprache
     *
     * @return int
     */
    static public function ermittelnKennzifferSprache(){
        $sprache = Zend_Registry::get('language');

        if(empty($sprache))
            $sprache = 'de';

        if($sprache == 'de')
            $spracheKennziffer = 1;
        else
            $spracheKennziffer = 2;

        return $spracheKennziffer;
    }

    /**
     * gibt die Anrede entsprechend der Sprache zurück
     *
     * @param $__sprache
     * @param bool $auswahl
     * @return array
     */
    static  public function getSalutation($__sprache, $auswahl = false){
        $anrede = array();

        if($__sprache == 'de'){
            $anrede[0]['title'] = "Frau";
            $anrede[1]['title'] = "Herr";

            return $anrede;
        }

        $anrede[0]['title'] = "Ms.";
        $anrede[1]['title'] = "Mr.";

        return $anrede;
    }

    /**
     * gibt die Kennung der Sprache zurück
     *
     * @return mixed
     */
    static public function getAnzeigesprache(){
        $sprache = Zend_Registry::get('language');

        return $sprache;
    }

}
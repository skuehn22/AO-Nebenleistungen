<?php

/**
 * Sammlung von Tools zur Textbearbeitung
 *
 * Zerlegt den Text mit verschiedenen Tools
 *
 *
 * @author Stephan.Krauss
 * @date 03.04.13
 * @file ToolText.php
 * @package tools
 */

class nook_ToolText{

    /**
     * Zerlegt einen Textstring nach einer bestimmten
     * Anzahl von Zeichen
     *
     * @param $__textString
     * @param $__zeichenanzahl
     * @return array
     */
    static public function splitText($__textString, $__zeichenanzahl){

        $teilstringArray = array();
        $__textString .= "#";

        $text = wordwrap($__textString, $__zeichenanzahl, '#');

        $textArray = explode('#', $text);

        for($i= 0; $i < count($textArray); $i++){
            $text = trim($textArray[$i]);

            if(!empty($text))
                $teilstringArray[] = $text;
        }

        return $teilstringArray;
    }


}
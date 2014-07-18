<?php 
/**
* Berechnungen zum Thema Mehrwertsteuer
*
* + Berechnet die Mehrwertsteuer
* + Ermittelt den Bruttobetrag
* + Ermittelt den Nettobetrag
*
* @author Stephan.Krauss
* @date 11.07.13
* @file ToolMehrwertsteuer.php
* @package tools
*/
class nook_ToolMehrwertsteuer {

    /**
     * Berechnet die Mehrwertsteuer
     *
     * + gegeben: Nettobetrag
     * + gegeben: Mehrwertsteuersatz
     * + Bsp. Mehrwertsteuersatz: 0.19
     *
     * @param $nettobetrag
     * @param $mehrwertsteuersatz
     * @return float
     */
    public static function getMehrwertsteuer($nettobetrag, $mehrwertsteuersatz)
    {
        $mehrwertsteuer = $nettobetrag * $mehrwertsteuersatz;

        return $mehrwertsteuer;
    }

    /**
     * Ermittelt den Bruttobetrag
     *
     * @param $nettobetrag
     * @param $mehrwertsteuer
     * @return mixed
     */
    public static function getBruttobetrag($nettobetrag, $mehrwertsteuer)
    {
        $bruttobetrag = $nettobetrag + $mehrwertsteuer;

        return $bruttobetrag;
    }

    /**
     * Ermittelt den Nettobetrag
     *
     * + Bsp. Mehrwertsteuersatz: 0.19
     *
     * @param $bruttobetrag
     * @param $mehrwertsteuersatz
     * @return float
     */
    public static function getNettobetrag($bruttobetrag, $mehrwertsteuersatz)
    {
        $nettobetrag = $bruttobetrag / (1 + $mehrwertsteuersatz);

        return $nettobetrag;
    }
}

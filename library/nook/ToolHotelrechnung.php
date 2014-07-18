<?php
/**
 * Ermitteln der Daten für die Übernachtungen in einem Hotel
 *
 * Ausgehend von 'tbl_hotelbuchungen'
 * werden die fehlenden daten ermittelt
 *
 * @author Stephan.Krauss
 * @date 07.03.13
 * @file index.php
 * @package tools
 */
class nook_ToolHotelrechnung
{
    public function __construct($__pimple = false)
    {
        if(!empty($__pimple))
            $this->_pimple = $__pimple;
    }



} // end class
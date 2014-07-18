<?php
/**
 * Interface Admin_Model_Hotelbuchungen
 *
 *
 * @author stephan.krauss
 * @date 13.06.13
 * @file HotelbuchungenInterface.php
 * @package admin
 * @subpackage interface
 */
interface Admin_Model_HotelbuchungenInterface {
    public function setBuchungsnummerId($buchungsnummerId);
    public function ermittelnHotelbuchungen();
} // end class

<?php
/**
 * Front_Model_ZaehlerBuchungsnummer Interface
 *
 * @author Stephan.Krauss
 * @date 20.06.13
 * @file ZaehlerBuchungsnummerInterface.php
 * @package front
 * @subpackage interface
 */
interface Front_Model_ZaehlerBuchungsnummerInterface {
    public function findBuchungsnummerUndZaehler();
    public function veraendernZaehlerInTabellen();
} // end class

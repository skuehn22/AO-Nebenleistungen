<?php
/**
 * Front_Model_InformationBenutzerBuchung Interface
 *
 * @author Stephan.Krauss
 * @date 21.40.2013
 * @file InformationBenutzerBuchungInterface.php
 * @package front
 * @subpackage interface
 */

interface Front_Model_InformationBenutzerBuchungInterface
{
    public function generateBuchungsnummerKundenId();

    public function getBuchungsdaten();
}
<?php 
 /**
 * Front_Model_BestandsbuchungProgramme Interface
 *
 * @author Stephan.Krauss
 * @date 20.06.13
 * @file BestandsbuchungProgrammeInterface.php
 * @package front
 * @subpackage interface
 */
 
interface Front_Model_BestandsbuchungProgrammeInterface {
    public function bestandsbuchung();
    public function setBuchungsnummer($buchungsnummer);
    public function setUserId($userId);
    public function setZaehler($zaehler);
} // end class

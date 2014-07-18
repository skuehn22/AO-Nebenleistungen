<?php
/**
 * Interface Admin_Model_Buchungsfehler
 *
 * @author stephan.krauss
 * @date 12.06.13
 * @file BuchungsfehlerInterface.php
 * @package admin
 * @subpackage interface
 */

interface Admin_Model_BuchungsfehlerInterface{
    public function buchungsfehlerStatus();
    public function ermittelnAktuelleFehler();
    public function getAnzahlFehler();
    public function getFehler();
    public function setBuchungsfehlerId($id);
    public function setBuchungsfehlerStatus($status);
    public function setLimit($limit);
    public function setStart($start);
}

<?php 
 /**
 * Front_Model_Buchungen Interface
 *
 * @author Stephan.Krauss
 * @date 20.06.13
 * @file BuchungenInterface.php
 * @package front
 * @subpackage interface
 */
 
interface Front_Model_BuchungenInterface {
    public function getBuchungsHistorie();
    public function getKundenId();
    public function setKundenId($kundenId);
    public function steuernErmittelnBuchungen();
    public function validateKundenId($kundenId);
} // end class

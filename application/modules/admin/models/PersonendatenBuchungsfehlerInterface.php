<?php
/**
 * Admin_Model_PersonendatenBuchungsfehler Interface
 *
 * @author stephan.krauss
 * @date 12.06.13
 * @file PersonendatenBuchungsfehlerInterface.php
 * @package admin
 * @subpackage interface
 */
interface Admin_Model_PersonendatenBuchungsfehlerInterface {
    public function setPersonendaten($kundenId);
    public function findPersonendaten();
    public function getKundendaten();
}

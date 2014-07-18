<?php 
 /**
 * Interface der Model Observer Stornierung
 *
 * @author Stephan.Krauss
 * @date 01.10.2013
 * @file StornierungWarenkorbInterface.php
 * @package front
 * @subpackage interface
 */
 
interface Front_Model_StornierungWarenkorbInterface {
    public function setFlagBestandsbuchung($flagBestandsbuchung);
    public function setPimple(Pimple_Pimple $pimple);
    public function setArtikelWarenkorb(array $artikelWarenkorb);
    public function getStatusWork();

    public function work();
}

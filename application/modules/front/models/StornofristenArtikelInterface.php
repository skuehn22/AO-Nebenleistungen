<?php
/**
 * Stornofristen bereits gebuchter Artikel Interface
 *
 * @author Stephan.Krauss
 * @date 24.23.2013
 * @file StornofristenInterface.php
 * @package front
 * @subpackage interface
 */
interface Front_Model_StornofristenArtikelInterface
{
    public function isInStornofrist();

    public function setPimple(Pimple_Pimple $pimple);

    public function steuerungKontrolleStornofrist();
}
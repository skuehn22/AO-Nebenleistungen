<?php 
 /**
 * Versendet ein Mail über die stornierten Artikel an den Kunden
 *
 * @author Stephan.Krauss
 * @date 01.10.2013
 * @file StornierungMailKunde.php
 * @package front
 * @subpackage model
 */

class Front_Model_StornierungMailKunde extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    // Fehler
    private $error = 2170;

    // Konditionen

    // Flags
    protected $flagStatusWork = true;


    // Informationen

    protected $flagBestandsbuchung = false;
    protected $pimple = null;
    protected $artikelWarenkorb = array();


    public function work()
    {
        if(empty($this->flagBestandsbuchung))
            return $this;

        return $this;
    }





}

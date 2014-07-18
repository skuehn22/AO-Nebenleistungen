<?php 
 /**
 * Korrigiert die Stornierung in der Tabelle 'tbl_zahlungen'
 *
 * @author Stephan.Krauss
 * @date 01.10.2013
 * @file StornierungTabelleZahlungen.php
 * @package front
 * @subpackage model
 */

class Front_Model_StornierungTabelleZahlungen extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    // Fehler
    private $error = 2200;

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

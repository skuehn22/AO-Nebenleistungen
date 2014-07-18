<?php
/**
* Ermitteln der Monatsnamen eines Monats
*
* + Ermitteln der Anzeigesprache
* + Gibt den Monatsnamen zurück
* + Gibt den Monatsnamen in verkürzter Form zurück
* + Kontrolliert die Ziffer des Monats
* + deutsche Monatsnamen
* + Gibt Monatsnamen deutsch verkürzt zurück
* + englische Monatsnamen
* + Gibt Monatsnamen englisch verkürzt zurück
*
* @author Stephan.Krauss
* @date 10.55.2013
* @file ToolMonatsnamen.php
* @package tools
*/
class nook_ToolMonatsnamen
{

    // Error
    private $error_keine_monatsziffer = 1870;
    private $error_falsche_monatsziffer = 1871;

    protected $monatsZiffer = null;
    protected $anzeigeSprache = null;

    /**
     * Ermitteln der Anzeigesprache
     */
    public function __construct()
    {
        $this->anzeigeSprache = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    /**
     * @param $monatsZiffer
     * @return nook_ToolMonatsnamen
     */
    public function setMonatsZiffer($monatsZiffer)
    {
        $monatsZiffer = (int) $monatsZiffer;
        $this->monatsZiffer = $monatsZiffer;

        return $this;
    }

    /**
     * @return null
     */
    public function getMonatsZiffer()
    {
        return $this->monatsZiffer;
    }

    /**
     * Gibt den Monatsnamen zurück
     * + in deutsch
     * + in englisch
     *
     * @return string / bool
     */
    public function getMonatsnamen()
    {
        $this->kontrolleMonatsziffer();

        if ($this->anzeigeSprache == 1) {
            return $this->monatsnameDeutsch();
        } else {
            return $this->monatsnameEnglisch();
        }
    }

    /**
     * Gibt den Monatsnamen in verkürzter Form zurück
     * + deutsch
     * + englisch

     */
    public function getMonatsnameShort()
    {
        $this->kontrolleMonatsziffer();

        if ($this->anzeigeSprache == 1) {
            return $this->monatsnameDeutschShort();
        } else {
            return $this->monatsnameEnglischShort();
        }
    }

    /**
     * Kontrolliert die Ziffer des Monats
     *
     * @throws nook_Exception
     */
    private function kontrolleMonatsziffer()
    {
        if (empty($this->monatsZiffer)) {
            throw new nook_Exception($this->error_keine_monatsziffer);
        }

        if (($this->monatsZiffer < 1) or ($this->monatsZiffer > 12)) {
            throw new nook_Exception($this->error_falsche_monatsziffer);
        }

        return;
    }

    /**
     * deutsche Monatsnamen
     *
     * @return mixed
     */
    private function monatsnameDeutsch()
    {
        $monatsNamenDeutsch = array(
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember'
        );

        return $monatsNamenDeutsch[$this->monatsZiffer];
    }

    /**
     * Gibt Monatsnamen deutsch verkürzt zurück
     *
     * @return string
     */
    private function monatsnameDeutschShort()
    {
        $monatsNamenDeutschShort = array(
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mär',
            4 => 'Apr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Dez'
        );

        return $monatsNamenDeutschShort[$this->monatsZiffer];
    }

    /**
     * englische Monatsnamen
     *
     * @return string
     */
    private function monatsnameEnglisch()
    {
        $monatsNamenEnglisch = array(
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        );

        return $monatsNamenEnglisch[$this->monatsZiffer];
    }

    /**
     * Gibt Monatsnamen englisch verkürzt zurück
     *
     * @return string
     */
    private function monatsnameEnglischShort()
    {
        $monatsNamenEnglischShort = array(
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec'
        );

        return $monatsNamenEnglischShort[$this->monatsZiffer];
    }
} // end class

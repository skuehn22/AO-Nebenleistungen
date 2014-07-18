<?php
/**
* Ermitteln der Namen der Wochentage , Wochentag
*
* + Steuerung der Ermittlung der Wochentage
* + Wochentage Kurzschreibweise deutsch
* + Wochentage Langschreibweise deutsch
* + Wochentage Kurzschreibweise englisch
* + Wochentage Langschreibweise englisch
* + Steuerung ermitteln des Wochentages mit einem Datum
* + Ermittelt die Ziffer des aktuellen Wochentages
* + Datum nach ISO Format
* + Gibt Name des Wochentages zurück
*
* @author Stephan.Krauss
* @date 20.08.2013
* @file ToolWochentageNamen.php
* @package tools
*/
class nook_ToolWochentageNamen
{
    // Fehler
    private $error_anfangswerte_fehlen = 1960;

    // Konditionen

    // Flags
    protected $flagDatumKorrekt = true;

    protected $anzeigespracheId = null;
    protected $namensTyp = null;
    protected $namenDerWochentage = array();

    protected $datum = null;
    protected $wochentag = null;


    /**
     * @param $anzeigespracheId
     * @return nook_ToolWochentageNamen
     */
    public function setAnzeigespracheId($anzeigespracheId)
    {
        $anzeigespracheId = (int) $anzeigespracheId;
        $this->anzeigespracheId = $anzeigespracheId;

        return $this;
    }

    /**
     * @param $namesTyp
     * @return nook_ToolWochentageNamen
     */
    public function setAnzeigeNamensTyp($namensTyp)
    {
        $namensTyp = (int) $namensTyp;
        $this->namensTyp = $namensTyp;

        return $this;
    }

    /**
     * Gibt Name des Wochentages zurück
     *
     * + wenn das Datum nicht korrekt ist, dann false
     *
     * @return array
     */
    public function getNamenDerWochentage()
    {
        if($this->flagDatumKorrekt === false)
            return false;

        return $this->namenDerWochentage;
    }

    /**
     * Steuerung der Ermittlung der Wochentage
     *
     * + Sprachabhängig
     * + Lang oder Kurzform
     * + Standard der Anzeige ist die Kurzform des Wochentages
     *
     * @param int $namensTyp
     * @return nook_ToolWochentageNamen
     */
    public function steuerungErmittlungNamenDerWochentage()
    {
        if (empty($this->namensTyp)) {
            $this->namensTyp = 1;
        } // Kurzform

        if (empty($this->anzeigespracheId)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        // deutsche Sprache
        if ($this->anzeigespracheId == 1) {
            // Kurzform
            if ($this->namensTyp == 1) {
                $this->wochentagDeutschKurz();
            } // Langform
            else {
                $this->wochentagDeutschLang();
            }
        } // englische Sprache
        else {
            // Kurzform
            if ($this->namensTyp == 1) {
                $this->wochentagEnglischKurz();
            } // Langform
            else {
                $this->wochentagEnglischLang();
            }
        }

        return $this;
    }

    /**
     * Wochentage Kurzschreibweise deutsch
     *
     */
    private function wochentagDeutschKurz()
    {
        $namenDerWochentage = array(
            '1' => 'Mo',
            '2' => 'Di',
            '3' => 'Mi',
            '4' => 'Do',
            '5' => 'Fr',
            '6' => 'Sa',
            '7' => 'So'
        );

        $this->namenDerWochentage = $namenDerWochentage;

        return;
    }

    /**
     * Wochentage Langschreibweise deutsch
     *
     */
    private function wochentagDeutschLang()
    {
        $namenDerWochentage = array(
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
            '6' => 'Samstag',
            '7' => 'Sonntag'
        );

        $this->namenDerWochentage = $namenDerWochentage;

        return;
    }

    /**
     * Wochentage Kurzschreibweise englisch
     *
     */
    private function wochentagEnglischKurz()
    {
        $namenDerWochentage = array(
            '1' => 'Mon',
            '2' => 'Tue',
            '3' => 'Wed',
            '4' => 'Thu',
            '5' => 'Fri',
            '6' => 'Sat',
            '7' => 'Sun'
        );

        $this->namenDerWochentage = $namenDerWochentage;

        return;
    }

    /**
     * Wochentage Langschreibweise englisch
     *
     */
    private function wochentagEnglischLang()
    {
        $namenDerWochentage = array(
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
            '7' => 'Sunday'
        );

        $this->namenDerWochentage = $namenDerWochentage;

        return;
    }

    /**
     * Steuerung ermitteln des Wochentages mit einem Datum
     *
     * + Datum wird nach ISO übergeben
     *
     */
    public function steuerungErmittelnWochentag()
    {
        if(empty($this->flagDatumKorrekt))
            return $this;

        if (empty($this->datum) or empty($this->anzeigespracheId) or empty($this->namensTyp))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(empty($this->datum))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittelnWochentag();

        return $this;
    }

    /**
     * Ermittelt die Ziffer des aktuellen Wochentages
     *
     * + Datum muss im ISO Format vorliegen
     * + Der Sonntag wird mit 0 gerechnet und in 7 umgerechnet
     */
    private function ermittelnWochentag()
    {
        $zeitInSekunden = strtotime($this->datum);
        $wochentagZiffer = date("w", $zeitInSekunden);

        if ($wochentagZiffer == 0) {
            $wochentagZiffer = 7;
        }

        if ($this->anzeigespracheId == 1) {
            if ($this->namensTyp == 1) {
                $this->wochentagDeutschKurz();
            } else {
                $this->wochentagDeutschLang();
            }
        } else {
            if ($this->namensTyp == 1) {
                $this->wochentagEnglischKurz();
            } else {
                $this->wochentagEnglischLang();
            }
        }

        $this->wochentag = $this->namenDerWochentage[$wochentagZiffer];

        return;
    }

    /**
     * Datum nach ISO Format
     *
     * + kontrolliert ob ein gültiges Datum nach ISO vorliegt
     *
     * @param $datum
     * @return $this
     */
    public function setDatum($datum)
    {
        if( ($datum == '0000-00-00') or (empty($datum)) )
            $this->flagDatumKorrekt = false;

        $this->datum = $datum;

        return $this;
    }

    /**
     * Gibt Name des Wochentages zurück
     *
     * + wenn das Datum nicht korrekt ist, dann false
     *
     * @return string
     */
    public function getBezeichnungWochentag()
    {
        if($this->flagDatumKorrekt === false)
            return false;

        return $this->wochentag;
    }
}

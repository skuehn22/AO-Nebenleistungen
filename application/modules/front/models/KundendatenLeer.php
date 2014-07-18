<?php
/**
* Zeigt ein leeres Formular zur Registrierung der Kundendaten an
*
* + Steuert der Darstellung eines leeren Formulares der Personendaten
* + Zeigt im Template die Mailadresse an.
* + Ermittelt die Anrede entsprechend der Sprachvariante
* + Ermittelt die Länder für eine Länderliste
* + Markiert ein gewähltes Land
*
* @file KundendatenLeer.php
* @package front
* @subpackage model
*/
class Front_Model_KundendatenLeer extends Front_Model_Kundendaten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2090;

    // Konditionen
    private $condition_anzeigesprache_deutsch_id = 1;
    private $condition_land_deutschland = 52;
    private $condition_land_england = 249;


    /**
     * Steuert der Darstellung eines leeren Formulares der Personendaten
     *
     * + Personendaten
     * + Auswahl Anrede
     * + Auswahl Länder
     *
     * @return Front_Model_KundendatenIndex
     * @throws nook_Exception
     */
    public function steuerungDarstellungLeeresFormularPersonendaten(RainTPL $raintpl)
    {
        try {

            $this->servicecontainer();

            $this->raintpl = $raintpl;

            if (empty($this->anzeigeSpracheId)) {
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            }

            // Anrede
            $this->erstellenAnredeCombobox();

            // Länder
            $laender = $this->erstellenLaenderCombobox();

            if($this->anzeigeSpracheId == $this->condition_anzeigesprache_deutsch_id)
                $laender = $this->markierenDesGewaehltenLandes($laender, $this->condition_land_deutschland);
            else
                $laender = $this->markierenDesGewaehltenLandes($laender, $this->condition_land_england);

            $this->raintpl->assign('country', $laender);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Zeigt im Template die Mailadresse an.
     *
     * + Mailadresse kann nur gesehen werden, aber nicht editiert.
     * + true = Mailadreese sichtbar, kein editieren möglich
     */
    private function viewMailadresse()
    {
        $this->raintpl->assign('mail_insert', true);

        return;
    }

    /**
     * Ermittelt die Anrede entsprechend der Sprachvariante
     *
     * + wenn eine Anrede in der Sprachvariante bereits gewaehlt, dann wird diese markiert
     *
     * @param RainTPL $raintpl
     * @return RainTPL
     */
    private function erstellenAnredeCombobox($gewaehlteAnrede = false)
    {
        if ($this->anzeigeSpracheId == $this->condition_anzeigesprache_deutsch_id) {
            $sprache = 'de';
        } else {
            $sprache = 'en';
        }

        $anreden = nook_ToolSprache::getSalutation($sprache);

        foreach ($anreden as $key => $anrede) {
            if ($gewaehlteAnrede == $anrede['title'] and !empty($gewaehlteAnrede)) {
                $anreden[$key]['checked'] = 1;
            } else {
                $anreden[$key]['checked'] = 0;
            }
        }

        $this->raintpl->assign('titles', $anreden);

        return $this->anzeigeSpracheId;
    }

    /**
     * Ermittelt die Länder für eine Länderliste
     *
     * @return array
     */
    private function erstellenLaenderCombobox()
    {
        /** @var  $frontModelPersonaldata Front_Model_Personaldata */
        $frontModelPersonaldata = $this->pimple['frontModelPersonaldata'];
        $laender = $frontModelPersonaldata->getLaender();

        return $laender;
    }

    /**
     * Markiert ein gewähltes Land
     *
     * + Auswahl über die ID des Landes
     * + Auswahl über den Namen des Landes
     *
     * @param $laender
     * @param $gewaehltesLand
     * @return array
     */
    private function markierenDesGewaehltenLandes($laender, $gewaehltesLand = 0)
    {
        $landId = (int) $gewaehltesLand;

        if ($landId > 0) {
            $kontrolleId = true;
            $kontrollwert = $landId;
        } else {
            $kontrolleId = false;
            $kontrollwert = $gewaehltesLand;
        }

        foreach ($laender as $key => $land) {
            if ($kontrolleId) {
                if ($land['id'] == $kontrollwert) {
                    $laender[$key]['checked'] = 1;
                }
            } else {
                if ($land['Name'] == $kontrollwert) {
                    $laender[$key]['checked'] = 1;
                }
            }
        }

        return $laender;
    }
}

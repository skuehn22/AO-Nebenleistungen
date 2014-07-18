<?php
/**
 * Variante von Front_Model_Kundendaten. Anzeige des Kundendatensatzes
 *
 * + Steuert die Ermittlung der Personedaten für ein Formular
 * + Zeigt im Template die Mailadresse an.
 * + Ermittelt die Anrede entsprechend der Sprachvariante
 * + Ermittelt die Länder für eine Länderliste
 * + Markiert ein gewähltes Land
 * + Ermittelt die Personendaten eines Benutzer
 *
 * @date 09.09.13
 * @file KundendatenIndex.php
 * @package front
 * @subpackage model
 */
class Front_Model_KundendatenIndex extends Front_Model_Kundendaten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2070;

    // Konditionen
    private $conditionAnzeigespracheDeutschId = 1;

    /**
     * Steuert die Ermittlung der Personedaten für ein Formular
     *
     * + Personendaten
     * + Auswahl Anrede
     * + Auswahl Länder
     *
     * @return Front_Model_KundendatenIndex
     * @throws nook_Exception
     */
    public function steuerungErmittlungDatenpersonenFormular(RainTPL $raintpl)
    {
        try {
            $this->raintpl = $raintpl;

            if (empty($this->anzeigeSpracheId)) {
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            }

            $this->ermittelnPersonendaten();

            // Personendaten
            $personenDaten = $this->pimple['personenDaten'];

            // Anrede
            $this->erstellenAnredeCombobox($personenDaten['title']);

            // Länder
            $laender = $this->erstellenLaenderCombobox();

            // anzeigen der Mailadresse
            $this->viewMailadresse();

            $gewaehltesLand = trim($personenDaten['country']);
            if (!empty($gewaehltesLand)) {
                $laender = $this->markierenDesGewaehltenLandes($laender, $gewaehltesLand);
            }

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
        if ($this->anzeigeSpracheId == $this->conditionAnzeigespracheDeutschId) {
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
    private function markierenDesGewaehltenLandes($laender, $gewaehltesLand)
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

    /**
     * Ermittelt die Personendaten eines Benutzer
     *
     * @param RainTPL $raintpl
     * @return
     */
    private function ermittelnPersonendaten()
    {
        $userId = nook_ToolUserId::kundenIdAusSession();

        /** @var $frontModelPersonalData Front_Model_Personaldata */
        $frontModelPersonalData = $this->pimple['frontModelPersonalData'];
        $frontModelPersonalData->userId = $userId;
        $personenDaten = $frontModelPersonalData->getpersonalData();

        $this->pimple['personenDaten'] = $personenDaten;
        $this->raintpl->assign('personalData', $personenDaten);

        return;
    }
}

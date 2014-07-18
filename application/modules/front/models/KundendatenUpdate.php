<?php
/**
 * Update des Adressdatensatzes 'tbl_adressen'. Update Datensatz Kundendaten
 *
 * + Update der Kundendaten in 'tbl_adressen'
 * + verschlüsselt das Passwort
 * + ermittelt die Kunden ID aus der session
 * + Führt update der Kundendaten in 'tbl_adressen' durch
 *
 * @date 09.09.13
 * @file KundendatenUpdate.php
 * @package front
 * @subpackage model
 */
class Front_Model_KundendatenUpdate extends Front_Model_Kundendaten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2080;

    // Konditionen
    private $condition_rolle_kunde = 3;

    // Konditionen

    /**
     * Update der Kundendaten in 'tbl_adressen'
     *
     * + ermitteln Kunden ID aus Session
     * + salzen Passwort
     * + update Kundendaten
     *
     * @return Front_Model_KundendatenUpdate
     */
    public function steuerungUpdateKundendaten()
    {
        try {
            if (empty($this->formularDaten)) {
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            }

            $this->servicecontainer();

            $this->formularDaten['password'] = $this->salzenPasswort($this->formularDaten['password']);
            $kundenId = $this->ermittelnKundenId();
            $this->updateKundendaten($kundenId);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $formularDaten
     * @return Front_Model_KundendatenUpdate
     */
    public function setFormularDaten(array $formularDaten)
    {
        $this->formularDaten = $formularDaten;

        return $this;
    }

    /**
     * verschlüsselt das Passwort
     *
     * @param $passwort
     * @return string
     */
    private function salzenPasswort($passwort)
    {
        $passwort = nook_ToolStatic::berechnePasswort($passwort);

        return $passwort;
    }

    /**
     * ermittelt die Kunden ID aus der session
     *
     * @return int
     */
    private function ermittelnKundenId()
    {
        $kundenId = nook_ToolKundendaten::findKundenId();

        return $kundenId;
    }

    /**
     * Führt update der Kundendaten in 'tbl_adressen' durch
     *
     * + setzt Rolle des Benutzers des System auf 'kunde' = 3
     * + Update der Daten des Benutzers
     *
     * @param $kundenId
     * @return int
     */
    private function updateKundendaten($kundenId)
    {
        // Rolle 'kunde' = 3
        $this->formularDaten['status'] = $this->condition_rolle_kunde;

        /** @var $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = $this->pimple['tabelleAdressen'];
        $kontrolle = $tabelleAdressen->update($this->formularDaten, "id = " . $kundenId);

        return $kontrolle;
    }
}

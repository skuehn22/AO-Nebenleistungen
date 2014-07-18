<?php 
/**
* Korrigiert die Stornierung in der Tabelle 'tbl_hotelbuchung'
*
* + Steuert das löschen oder die Stornierung eines Produktes
* + löscht oder storniert die Artikel einer Hotelbuchung
* + löschen einer neuen Produktbuchung
* + Stornieren einer Produktbuchung des aktuellen Warenkorbes
*
* @date 01.10.2013
* @file StornierungTabelleHotelbuchung.php
* @package front
* @subpackage model
*/
class Front_Model_StornierungTabelleHotelbuchung extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    // Fehler
    private $error_anfangswerte_fehlen = 2230;
    private $error_storno_fehlgeschlagen = 2231;

    // Konditionen
    private $condition_zaehler_aktiver_warenkorb = 0;
    private $condition_anzahl_nach_stornierung = 0;
    private $condition_bereich_uebernachtung = 6;
    private $condition_neuer_artikel_im_warenkorb = 1;

    // Flags
    protected $flagStatusWork = true;

    // Tabellen
    /** @var $tabelleHotelbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleHotelbuchung = null;

    private $condition_status_stornoauftrag = 9;


    // Informationen

    /**
     * Steuert das löschen oder die Stornierung eines Produktes
     *
     * @return Front_Model_StornierungTabelleHotelbuchung
     * @throws nook_Exception
     */
    public function work()
    {
        if(empty($this->buchungsnummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(is_null($this->zaehler))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if( !is_array($this->artikelWarenkorb) or count($this->artikelWarenkorb) == 0 )
            return $this;

        $this->tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];

        $this->loeschenOderStornierenHotelbuchung();

        return $this;
    }

    /**
     * löscht oder storniert die Artikel einer Hotelbuchung
     */
    private function loeschenOderStornierenHotelbuchung()
    {
        $kontrolle = 0;

        foreach($this->artikelWarenkorb as $artikel){

            if($artikel['bereich'] != $this->condition_bereich_uebernachtung)
                continue;

            $where = array(
                "buchungsnummer_id = ".$this->buchungsnummer,
                "zaehler = ".$this->condition_zaehler_aktiver_warenkorb,
                "id = ".$artikel['id']
            );

            if($artikel['status'] == $this->condition_neuer_artikel_im_warenkorb)
                $kontrolle += $this->loeschenArtikel($where);
            else
                $kontrolle += $this->stornierenArtikel($where, $this->condition_status_stornoauftrag, $this->zaehler);
        }

        return $kontrolle;
    }

    /**
     * löschen einer neuen Produktbuchung
     *
     * @param $where
     * @return int
     */
    private function loeschenArtikel($where)
    {
        $kontrolle = $this->tabelleHotelbuchung->delete($where);

        return $kontrolle;
    }

    /**
     * Stornieren einer Produktbuchung des aktuellen Warenkorbes
     *
     * + Anzahl wird auf 0 gesetzt
     * + 9 = Stornierung mit Arbeitsaufwand (Stornokosten oder zu Fuß zu stornieren)
     * + 10 = Stornierung erfolgt
     *
     * @param $where
     * @return int
     */
    private function stornierenArtikel($where, $status, $zaehler)
    {
        $zaehler++;

        $update = array(
            "anzahl" => $this->condition_anzahl_nach_stornierung,
            'status' => $status,
            'zaehler' => $zaehler
        );

        $kontrolle = $this->tabelleProgrammbuchung->update($update, $where);

        return $kontrolle;
    }
}

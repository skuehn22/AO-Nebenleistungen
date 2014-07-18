<?php 
/**
* Korrigiert die Stornierung in der Tabelle 'tbl_programmbuchung'
*
* + Steuert das löschen oder die Stornierung eines Produktes
* + löscht oder storniert die Artikel
* + löschen einer neuen Produktbuchung
* + Stornieren einer Produktbuchung des aktuellen Warenkorbes
*
* @date 01.10.2013
* @file StornierungTabelleProgrammbuchung.php
* @package front
* @subpackage model
*/
class Front_Model_StornierungTabelleProgrammbuchung extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    // Fehler
    private $error_anfangswerte_fehlen = 2220;
    private $error_storno_fehlgeschlagen = 2221;

    // Konditionen
    private $condition_zaehler_aktiver_warenkorb = 0;
    private $condition_anzahl_nach_stornierung = 0;
    private $condition_bereich_programme = 1;
    private $condition_neuer_artikel_im_warenkorb = 1;

    private $condition_status_stornoauftrag = 9;
    private $condition_status_storno_erfolgt = 10;

    // Flags
    protected $flagStatusWork = true;

    // Tabellen
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;


    // Informationen

    /**
     * Steuert das löschen oder die Stornierung eines Produktes
     *
     * @return Front_Model_StornierungTabelleProgrammbuchung
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

        $this->tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];

        $this->loeschenOderStornierenProgrammbuchung();

        return $this;
    }

    /**
     * löscht oder storniert die Artikel
     */
    private function loeschenOderStornierenProgrammbuchung()
    {
        $kontrolle = 0;

        foreach($this->artikelWarenkorb as $artikel){

            if($artikel['bereich'] != $this->condition_bereich_programme)
                continue;

            $where = array(
                "buchungsnummer_id = ".$this->buchungsnummer,
                "zaehler = ".$this->condition_zaehler_aktiver_warenkorb,
                "id = ".$artikel['id']
            );

            if($artikel['status'] == $this->condition_neuer_artikel_im_warenkorb)
                $kontrolle + $this->loeschenArtikel($where);
            else
                if($artikel['stornowert'] > 0)
                    $kontrolle += $this->stornierenArtikel($where, $this->condition_status_stornoauftrag, $this->zaehler);
                else
                    $kontrolle += $this->stornierenArtikel($where, $this->condition_status_storno_erfolgt, $this->zaehler);
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
        $kontrolle = $this->tabelleProgrammbuchung->delete($where);

        return $kontrolle;
    }

    /**
     * Stornieren einer Produktbuchung des aktuellen Warenkorbes
     *
     * + Anzahl wird auf 0 gesetzt
     * + Status der Stornierung wird gesetzt.
     * + 9 = Stornierung mit Arbeitsaufwand (Stornokosten oder zu Fuß zu stornieren)
          * + 10 = Stornierung erfolgt
     * + vergibt neuen Zaehler
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

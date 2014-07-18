<?php 
 /**
 * Storniert eine Buchungsnummer / Warenkorb in der Tabelle 'tbl_buchungsnummer'
 *
 * @author Stephan.Krauss
 * @date 27.01.2014
 * @file StornierungTabelleBuchungsnummer.php
 * @package front
 * @subpackage model
 */
class Front_Model_StornierungTabelleBuchungsnummer extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    protected $condition_status_stornoauftrag = 9;
    protected $condition_status_storno_erfolgt = 10;

    protected $condition_bereich_programme = 1;
    protected $condition_bereich_hotel = 6;

    /** @var $tabelleBuchungsnummer Zend_Db_Table_Abstract  */
    protected $tabelleBuchungsnummer = null;

    /**
     * Steuert das setzen des Status der tornierung in 'tbl_buchungsnummer'
     *
     * @return Front_Model_StornierungTabelleBuchungsnummer
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

        if(!$this->pimple->offsetExists('tabelleBuchungsnummer'))
            throw new nook_Exception('Tabelle Buchungsnummer fehlt');

        $this->tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];

        $anzahlArtikelStornoauftrag = $this->ermittelnStatusDerStornierung($this->artikelWarenkorb);

        if($anzahlArtikelStornoauftrag > 0)
            $anzahlUpdateDatensatzBuchungstabelle = $this->stornierenWarenkorbBuchungstabelle($this->buchungsnummer, $this->zaehler, $this->condition_status_stornoauftrag);
        else
            $anzahlUpdateDatensatzBuchungstabelle = $this->stornierenWarenkorbBuchungstabelle($this->buchungsnummer, $this->zaehler, $this->condition_status_storno_erfolgt);

        return $this;
    }

    /**
     * ermittelt die Anzahl der Artikel die einen Stornoauftrag auslösen
     *
     * @param $artikelWarenkorb
     * @return int
     */
    protected function ermittelnStatusDerStornierung(array $artikelWarenkorb)
    {
        $anzahlArtikelMitStornoauftrag = 0;

        for($i=0; $i < count($artikelWarenkorb); $i++){
            $einzelneArtikel = $artikelWarenkorb[$i];

            if($einzelneArtikel['bereich'] == $this->condition_bereich_programme){
                if($einzelneArtikel['stornowert'] > 0)
                    $anzahlArtikelMitStornoauftrag++;
            }
            elseif($einzelneArtikel['bereich'] == $this->condition_bereich_hotel)
                $anzahlArtikelMitStornoauftrag++;
        }

        return $anzahlArtikelMitStornoauftrag;
    }

    /**
     * Storniert den Warenkorb in der Buchungstabelle
     *
     * + Status = 9, Nacharbeit notwendig
     * + Status = 10, keine nacharbeit nötig
     * + hochsetzen Zaehler
     *
     * @param $buchungsnummer
     * @param $zaehler
     * @param $status
     * @return int
     */
    protected function stornierenWarenkorbBuchungstabelle($buchungsnummer, $zaehler, $status)
    {
        $where = array(
            "id = ".$buchungsnummer,
            "zaehler = ".$zaehler
        );

        $zaehler++;

        $update = array(
            "status" => $status,
            'zaehler' => $zaehler
        );

        $anzahlUpdateDatensatzBuchungstabelle = $this->tabelleBuchungsnummer->update($update, $where);

        return $anzahlUpdateDatensatzBuchungstabelle;
    }
}
 
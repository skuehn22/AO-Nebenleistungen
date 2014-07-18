<?php 
 /**
 * verwaltet die Adressdateb´n eines Kunden
 *
 * @author Stephan.Krauss
 * @date 11.11.2013
 * @file ToolAdressdaten.php
 * @package tools
 */
 
class nook_ToolAdressdaten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2370;
    private $error_anzahl_datensaetze_falsch = 2371;

    // Informationen

    // Konditionen

    // Flags

    // Tabellen / Views
    /** @var $tabelleAdressen Application_Model_DbTable_adressen */
    private $tabelleAdressen = null;



    protected $pimple = null;
    protected $kundenId = null;
    protected $kundenDaten = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
        $this->servicecontainer($pimple);
    }

    /**
     * Injection Container
     *
     * @param Pimple_Pimple $pimple
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        if(!$pimple->offsetExists('tabelleAdressen'))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->tabelleAdressen = $pimple['tabelleAdressen'];

        return;
    }

    /**
     * @param $kundenId
     * @return $this
     */
    public function setKundenId($kundenId)
    {
        $kundenId = (int) $kundenId;
        $this->kundenId = $kundenId;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Adressdaten des Kunden
     *
     * @return nook_ToolAdressdaten
     * @throws nook_Exception
     */
    public function steuerungErmittlungKundendaten()
    {
        if(is_null($this->kundenId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittelnKundenDaten($this->kundenId);

        return $this;
    }

    /**
     * Ermittelt die Adressdaten des Kunden
     *
     * @param $kundenId
     * @throws nook_Exception
     */
    private function ermittelnKundenDaten($kundenId)
    {
        $rows = $this->tabelleAdressen->find($kundenId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->kundenDaten = $rows[0];

        return;
    }

    /**
     * Gibt die Adressdaten eines Kunden zurück
     *
     * + Unterscheidung zwischen kompletten datensatz und einzelner variable des Datenatzes der Adresse
     *
     * @param bool $teilDerAdresse
     * @return array|bool
     */
    public function getAdressdatenKunde($teilDerAdresse = false)
    {
        if(false !== $teilDerAdresse){
            if(array_key_exists($teilDerAdresse, $this->kundenDaten)){
                if(!is_null($this->kundenDaten[$teilDerAdresse])){
                    return $this->kundenDaten[$teilDerAdresse];
                }
                else{
                    return false;
                }
            }
        }

        return $this->kundenDaten;
    }
}

<?php
/**
 * Sucht die Kundendaten zu einer Fehlbuchung
 *
 * @author stephan.krauss
 * @date 12.06.13
 * @file PersonendatenBuchungsfehler.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_PersonendatenBuchungsfehler implements Admin_Model_PersonendatenBuchungsfehlerInterface {

    // Fehler
    private $error_anfangswerte_fehlen = 1640;
    private $error_anzahl_datensaetze_stimmt_nicht = 1641;

    // Konditionen

    // Flags

    protected $pimple = null;
    protected $kundenId = null;
    protected $kundenDaten = null;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @param $kundenId
     * @return Admin_Model_PersonendatenBuchungsfehler
     */
    public function setPersonendaten($kundenId)
    {
        $kundenId = (int) $kundenId;
        $this->kundenId = $kundenId;

        return $this;
    }

    /**
     * Startet Suche nach Kundendaten
     *
     * @return $this
     * @throws nook_Exception
     */
    public function findPersonendaten()
    {
        if(empty($this->kundenId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $kundendaten = $this->kundendatenErmitteln($this->kundenId);
        $kundendaten = $this->mapKundendaten($kundendaten);

        $this->kundenDaten = $kundendaten;

        return $this;
    }

    private function mapKundendaten(array $kundendaten)
    {
        $kundendatenMap = array(
            'Anrede' => $kundendaten['title'],
            'Vorname' => $kundendaten['firstname'],
            'Name' => $kundendaten['lastname'],
            'Strasse' => $kundendaten['street'],
            'Hausnummer' => $kundendaten['housenumber'],
            'PLZ' => $kundendaten['zip'],
            'Mail' => $kundendaten['email'],
            'Telefon1' => $kundendaten['phonenumber'],
            'Telefon2' => $kundendaten['phonenumber1']
        );

        return $kundendatenMap;
    }

    /**
     * Ermittelt die Kundendaten
     *
     * + Kunden ID ist vorhanden
     *
     * @param $kundenId
     * @return mixed
     * @throws nook_Exception
     */
    private function kundendatenErmitteln($kundenId)
    {
        /** @var  $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = $this->pimple['tabelleAdressen'];
        $rows = $tabelleAdressen->find($kundenId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);

        return $rows[0];
    }

    /**
     * @return array
     */
    public function getKundendaten()
    {
        return $this->kundenDaten;
    }

} // end class

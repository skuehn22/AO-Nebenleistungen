<?php
/**
* Verwaltet die möglichen Programmzeiten eines Programmes. Korrektur der verhunzten Öffnungszeiten
*
* + Servicecontainer
* + Steuerung Ermittlung des Types der Oeffnungszeiten eines Programmes
* + Ermittelt den Typ der Öffnungszeit eines Programmes
* + Steuerung Eintragen des Typ der Öffnungszeiten eines Programmes
* + Eintragen des Types der Öffnungszeiten eines Programmes
*
* @date 26.08.13
* @file Front_Model_Programmzeiten.php
* @package front
* @subpackage model
*/
class Front_Model_Programmzeiten
{
    // Fehler
    private $error_anfangsdaten_fehlen = 2000;
    private $error_anzahl_datensaetze_falsch = 2001;

    // Konditionen

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $programmId = null;
    protected $typOeffnungszeiten = null;

    public function __construct()
    {

    }

    /**
     * @param $programmId
     * @return $this
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgrammId()
    {
        return $this->programmId;
    }

    /**
     * @return int
     */
    public function getTypOeffnungszeiten()
    {
        return $this->typOeffnungszeiten;
    }

    /**
     * @param $typOeffnungszeiten
     * @return Front_Model_Programmzeiten
     */
    public function setTypOeffnungszeiten($typOeffnungszeiten)
    {
        $typOeffnungszeiten = (int) $typOeffnungszeiten;
        $this->typOeffnungszeiten = $typOeffnungszeiten;

        return $this;
    }

    /**
     * Servicecontainer
     *
     * @return Front_Model_Programmzeiten
     */
    public function servicecontainer()
    {
        if (empty($this->pimple)) {
            $this->pimple = new Pimple_Pimple();
        }

        if (!$this->pimple->offsetExists['tabelleProgrammdetails']) {
            $this->pimple['tabelleProgrammdetails'] = function(){
                return new Application_Model_DbTable_programmedetails();
            };
        }

        if (!$this->pimple->offsetExists['tabelleProgrammdetailsZeiten']) {
            $this->pimple['tabelleProgrammdetailsZeiten'] = function(){
                return new Application_Model_DbTable_programmedetailsZeiten();
            };
        }

        if (!$this->pimple->offsetExists['tabelleProgrammdetailsOeffnungszeiten']) {
            $this->pimple['tabelleProgrammdetailsOeffnungszeiten'] = function(){
                return new Application_Model_DbTable_programmedetailsOeffnungszeiten();
            };
        }

        return $this;
    }

    /**
     * Steuerung Ermittlung des Types der Oeffnungszeiten eines Programmes
     *
     * + 1 = keine Oeffnungszeiten
     * + 2 = Kunde gibt Öffnungszeiten ein
     * + 3 Kunde wählt Startzeit aus Liste
     *
     * @return Front_Model_Programmzeiten
     * @throws nook_Exception
     */
    public function steuerungErmittlungProgrammzeitenTyp()
    {
        $this->servicecontainer();

        if (empty($this->programmId)) {
            throw new nook_Exception($this->error_anfangsdaten_fehlen);
        }

        $this->ermittelnTypOeffnungszeiten();

        return $this;
    }

    /**
     * Ermittelt den Typ der Öffnungszeit eines Programmes
     *
     * @throws nook_Exception
     */
    private function ermittelnTypOeffnungszeiten()
    {
        /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $rows = $tabelleProgrammdetails->find($this->programmId)->toArray();

        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);
        }

        $this->typOeffnungszeiten = $rows[0]['typOeffnungszeiten'];

        return;
    }

    /**
     * Steuerung Eintragen des Typ der Öffnungszeiten eines Programmes
     *
     * @return Front_Model_Programmzeiten
     * @throws nook_Exception
     */
    public function steuerungEintragenTypOeffnungszeit()
    {
        $this->servicecontainer();

        if (empty($this->programmId) or empty($this->typOeffnungszeiten)) {
            throw new nook_Exception($this->error_anfangsdaten_fehlen);
        }

        $this->eintragenTypOeffnungszeit();

        return $this;
    }

    /**
     * Eintragen des Types der Öffnungszeiten eines Programmes
     *
     */
    private function eintragenTypOeffnungszeit()
    {
        $update = array(
            'typOeffnungszeiten' => $this->typOeffnungszeiten
        );

        $whereProgrammId = "id = " . $this->programmId;

        /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $tabelleProgrammdetails->update($update, $whereProgrammId);

        return;
    }
}

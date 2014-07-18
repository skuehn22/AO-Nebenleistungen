<?php
/**
 * Verändert den Status eines Warenkorbes
 *
 * +
 * + Servicecontainer
 * + Setzt den Status in 'tbl_buchungsnummer' und 'tbl_programmbuchung'
 * + Setzt den Status eines Warenkorbes in 'tbl_buchungsnummer'
 * + Setzt den Staus in der Tabelle 'tbl_programmbuchung'
 *
 * @date 12.09.13
 * @file StatusProgramm.php
 * @package front | admin | tools | plugins | schnittstelle | tabelle
 * @subpackage controller | model | interface | shadow | data
 */
class Front_Model_StatusWarenkorb
{
    // Fehler
    private $error_anfangswerte_fehlen = 2100;

    // Konditionen

    // Flags

    // Informationen

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;

    protected $messages = array();

    protected $buchungsnummer = null;
    protected $zaehlerWarenkorb = null;
    protected $statusBuchungstabelle = null;
    protected $statusProgrammbuchungsTabelle = null;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_StatusWarenkorb
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_StatusWarenkorb
     */
    public function setBuchungsnummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;
        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * @param $statusBuchungstabelle
     * @return Front_Model_StatusWarenkorb
     */
    public function setStatusBuchungstabelle($statusBuchungstabelle)
    {
        $statusBuchungstabelle = (int) $statusBuchungstabelle;
        $this->statusBuchungstabelle = $statusBuchungstabelle;

        return $this;
    }

    /**
     * @param $statusProgrammbuchungsTabelle
     * @return Front_Model_StatusWarenkorb
     */
    public function setStatusProgrammbuchungsTabelle($statusProgrammbuchungsTabelle)
    {
        $statusProgrammbuchungsTabelle = (int) $statusProgrammbuchungsTabelle;
        $this->statusProgrammbuchungsTabelle = $statusProgrammbuchungsTabelle;

        return $this;
    }

    /**
     * @param $zaehlerWarenkorb
     * @return Front_Model_StatusWarenkorb
     */
    public function setZaehlerWarenkorb($zaehlerWarenkorb)
    {
        $zaehlerWarenkorb = (int) $zaehlerWarenkorb;
        $this->zaehlerWarenkorb = $zaehlerWarenkorb;

        return $this;
    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        if (empty($this->pimple)) {
            $this->pimple = new Pimple_Pimple();
        }

        if (!$this->pimple->offsetExists('tabelleBuchungsnummer')) {
            $this->pimple['tabelleBuchungsnummer'] = function () {
                return new Application_Model_DbTable_buchungsnummer();
            };
        }

        if (!$this->pimple->offsetExists('tabelleProgrammbuchung')) {
            $this->pimple['tabelleProgrammbuchung'] = function () {
                return new Application_Model_DbTable_programmbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleHotelbuchung')){
            $this->pimple['tabelleHotelbuchung'] = function(){
                return new Application_Model_DbTable_hotelbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProduktbuchung')){
            $this->pimple['tabelleProduktbuchung'] = function(){
                return new Application_Model_DbTable_produktbuchung();
            };
        }

        return;
    }

    /**
     * Setzt den Status in 'tbl_buchungsnummer' und 'tbl_programmbuchung'
     *
     * @return array|bool
     * @throws nook_Exception
     */
    public function setzenStatusTabellen()
    {
        $this->servicecontainer();

        if ((empty($this->buchungsnummer)) or (!is_int($this->zaehlerWarenkorb))) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        if ((empty($this->statusBuchungstabelle)) or (empty($this->statusProgrammbuchungsTabelle))) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        // 'tbl_buchungsnummer'
        $this->setzenStatusBuchungstabelle();

        // 'tbl_programmbuchung'
        $this->setzenStatusTabelleProgrammbuchung();

        // 'tbl_hotelbuchung'
        $this->setzenStatusTabelleHotelbuchung();

        // 'tbl_produktbuchung'
        $this->setzenStatusTabelleProduktbuchung();

        if (count($this->messages) > 0) {
            return $this->messages;
        } else {
            return false;
        }
    }

    /**
     * Setzt den Status eines Warenkorbes in 'tbl_buchungsnummer'
     *
     * @return int
     */
    private function setzenStatusBuchungstabelle()
    {
        $update = array(
            'status' => $this->statusBuchungstabelle
        );

        $where = array(
            "id = " . $this->buchungsnummer,
            "zaehler = " . $this->zaehlerWarenkorb
        );

        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $anzahlGeaenderteZeilen = $tabelleBuchungsnummer->update($update, $where);

        return $anzahlGeaenderteZeilen;
    }

    /**
     * Setzt den Staus in der Tabelle 'tbl_programmbuchung'
     *
     * @return int
     */
    private function setzenStatusTabelleProgrammbuchung()
    {
        $update = array(
            'status' => $this->statusProgrammbuchungsTabelle
        );

        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = " . $this->zaehlerWarenkorb
        );

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $anzahlGeaenderteZeilen = $tabelleProgrammbuchung->update($update, $where);

        return $anzahlGeaenderteZeilen;
    }

    /**
     * Setzt den Staus in der Tabelle 'tbl_hotelbuchung'
     *
     * @return int
     */
    private function setzenStatusTabelleHotelbuchung()
    {
        $update = array(
            'status' => $this->statusProgrammbuchungsTabelle
        );

        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = " . $this->zaehlerWarenkorb
        );

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $anzahlGeaenderteZeilen = $tabelleHotelbuchung->update($update, $where);

        return $anzahlGeaenderteZeilen;
    }

    /**
     * Setzt den Staus in der Tabelle 'tbl_produktbuchung'
     *
     * @return int
     */
    private function setzenStatusTabelleProduktbuchung()
    {
        $update = array(
            'status' => $this->statusProgrammbuchungsTabelle
        );

        $where = array(
            "buchungsnummer_id = " . $this->buchungsnummer,
            "zaehler = " . $this->zaehlerWarenkorb
        );

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $anzahlGeaenderteZeilen = $tabelleProduktbuchung->update($update, $where);

        return $anzahlGeaenderteZeilen;
    }
}

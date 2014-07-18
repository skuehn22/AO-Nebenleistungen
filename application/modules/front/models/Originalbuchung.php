<?php
/**
 * Ermittelt die Werte einer Bestandsbuchung / Originalbuchung
 *
 * @author Stephan.Krauss
 * @date 25.06.13
 * @file Originalbuchung.php
 * @package front | admin | tools | plugins | schnittstelle | tabelle
 * @subpackage controller | model | mapper | interface
 */

class Front_Model_Originalbuchung
{

    // Fehler
    private $error_anfangswerte_fehlen = 1740;
    private $error_anzahl_datensaetze_stimmt_nicht = 1741;

    // Konditionen

    // Flags

    protected $pimple = null;
    protected $buchungstyp = null;
    protected $buchungsnummer = null;
    protected $zaehler = null;
    protected $artikelId = null;
    protected $originalDaten = array();

    /**
     * Übernimmt DIC
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * @param $artikelId
     * @return Front_Model_Originalbuchung
     */
    public function setArtikelId($artikelId)
    {
        $artikelId = (int) $artikelId;
        $this->artikelId = $artikelId;

        return $this;
    }

    /**
     * @param $buchungstyp
     * @return Front_Model_Originalbuchung
     */
    public function setBuchungstyp($buchungstyp)
    {
        $this->buchungstyp = $buchungstyp;

        return $this;
    }

    /**
     * @return array
     */
    public function getOriginalDaten()
    {
        return $this->originalDaten;
    }

    /**
     * Steuert die Ermittlung der Originaldaten
     *
     * @return Front_Model_Originalbuchung
     * @throws nook_Exception
     */
    public function steuerungErmittelnOriginalbuchung()
    {
        if ((empty($this->buchungstyp)) or (empty($this->artikelId))) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $pimpleLabel =  $this->pimple->findClass('nook_ToolBestandsbuchungKontrolle');

        if (empty($pimpleLabel))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->buchungsnummer = $this->pimple[$pimpleLabel]->getBuchungsnummer();
        $this->zaehler = $this->pimple[$pimpleLabel]->getZaehler();

        // Buchungstypen
        if ($this->buchungstyp == 'programmbuchung') {

            $pimpleLabel = $this->pimple->findClass('Application_Model_DbTable_programmbuchung');

            if (!$pimpleLabel) {
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            }

            $this->programmbuchung($pimpleLabel);
        }

        return $this;
    }

    /**
     * Ermittelt die Originaldaten einer Programmbuchung
     *
     * @return mixed
     * @throws nook_Exception
     */
    private function programmbuchung($labelTabelle)
    {
        $whereArtikelId = "programmdetails_id = " . $this->artikelId;
        $whereBuchungsnummer = "buchungsnummer_id = " . $this->buchungsnummer;
        $whereZaehler = "zaehler = " . $this->zaehler;

        /** @var  $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple[$labelTabelle];
        $select = $tabelleProgrammbuchung->select();
        $select
            ->where($whereArtikelId)
            ->where($whereBuchungsnummer)
            ->where($whereZaehler);

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();
        if (count($rows) <> 1) {
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);
        }

        $this->originalDaten = $rows[ 0 ];

        return $rows[ 0 ];
    }
}

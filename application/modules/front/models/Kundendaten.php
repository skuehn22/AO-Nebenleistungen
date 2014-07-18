<?php
/**
 * Stellt die Daten für das Formular Personendaten zur Verfügung. Trägt Kundendaten erneut ein
 *
 * + Erstellt den Servicecontainer
 *
 * @date 06.09.13
 * @file Personendaten.php
 * @package front
 * @subpackage model
 */
class Front_Model_Kundendaten
{
    // Fehler
    private $error_anfangswerte_fehlen = 2050;

    // Konditionen
    private $conditionAnzeigespracheDeitschId = 1;

    // Flags

    // Informationen

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $anzeigeSpracheId = null;
    protected $raintpl = null;

    protected $formularDaten = array();

    public function __construct($anzeigeSpracheId)
    {
        $anzeigeSpracheId = (int) $anzeigeSpracheId;
        $this->anzeigeSpracheId = $anzeigeSpracheId;
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_Kundendaten
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Erstellt den Servicecontainer
     *
     * @return Front_Model_Kundendaten
     */
    public function servicecontainer()
    {
        if (empty($this->pimple)) {
            $this->pimple = new Pimple_Pimple();
        }

        if (!$this->pimple->offsetExists('frontModelPersonaldata')) {
            $this->pimple['frontModelPersonaldata'] = function () {
                return new Front_Model_Personaldata();
            };
        }

        if (!$this->pimple->offsetExists('tabelleAdressen')) {
            $this->pimple['tabelleAdressen'] = function () {
                return new Application_Model_DbTable_adressen();
            };
        }

        return $this;
    }

    /**
     * @return RainTpl
     */
    public function getRainTpl()
    {
        return $this->raintpl;
    }

}

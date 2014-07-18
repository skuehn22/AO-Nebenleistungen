<?php
/**
 * generiert die verschiedenen Varianten des Model Front_Model_Kundendaten
 *
 * + Generiert ein Objekt des Model Kundendaten, entsprechend der Variante
 * + Generiert eine Variante des Model 'Kundendaten'
 *
 * @date 09.09.13
 * @file KundendatenFactory.php
 * @package front
 * @subpackage model
 */
class Front_Model_KundendatenFactory
{
    protected $variante = null;

    /**
     * Generiert ein Objekt des Model Kundendaten, entsprechend der Variante
     *
     * + 'insert' = Anzeige der Daten
     * + 'update' = Veränderung der Daten
     *
     * @param $variantenName
     * @param $anzeigeSpracheId
     */
    public function __construct($variantenName, $anzeigeSpracheId)
    {
        $this->buildObject($variantenName, $anzeigeSpracheId);

        return;
    }

    /**
     * Generiert eine Variante des Model 'Kundendaten'
     *
     * @param $variantenName
     * @param $anzeigeSprache
     * @return Front_Model_Kundendaten
     */
    private function buildObject($variantenName, $anzeigeSpracheId)
    {
        switch ($variantenName) {
            case 'index':
                $variante = new Front_Model_KundendatenIndex($anzeigeSpracheId);
                break;
            case 'update':
                $variante = new Front_Model_KundendatenUpdate($anzeigeSpracheId);
                break;
            case 'leer':
                $variante = new Front_Model_KundendatenLeer($anzeigeSpracheId);
                break;
        }

        $this->variante = $variante;

        return;
    }

    /**
     * @return obj
     */
    public function getVariante()
    {
        return $this->variante;
    }
}

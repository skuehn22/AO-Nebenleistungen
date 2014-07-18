<?php 
 /**
 * Ermittelt den Preis einer Preisvariante
 *
 * + Übernimmt die ID der Preisvariante
 * + Gibt den Preis der Preisvariante zurück
 *
 * @author Stephan.Krauss
 * @date 27.06.13
 * @file ToolPreisvariante.php
 * @package tools
 */
 
class nook_ToolPreisvariante
{
    // Flags

    // Error
    private $error_anfangswerte_fehlen = 1760;
    private $error_anzahl_datensaetze_stimmt_nicht = 1761;

    // Konditionen

    protected static $instance = null;

    protected $pimple = null;
    protected $preisVarianteId = null;
    protected $preisVariantePreis = null;

    /**
     * Singleton , erstellt Klasse zur Ermittlung der Preisvariante
     *
     * @return nook_ToolPreisvariante
     */
    public static function getInstance(){
        if(!self::$instance){
            $pimple = self::buildPimple();
            self::$instance = new nook_ToolPreisvariante($pimple);
        }

        return self::$instance;
    }

    /**
     * Erstellt den DIC
     *
     * @return Pimple_Pimple
     */
    private static function buildPimple(){
        $pimple = new Pimple_Pimple();

        $pimple['tabellePreise'] = function(){
            return new Application_Model_DbTable_preise();
        };

        return $pimple;
    }

    /**
     * Übernimmt DIC aus Singleton
     *
     * @param $pimple
     */
    public function __construct(Pimple_Pimple $pimple){
        $this->pimple = $pimple;
    }

    /**
     * Steuert die Ermittlung des Preises der Preisvariante
     *
     * @return nook_ToolPreisvariante
     * @throws nook_Exception
     */
    public function steuerungErmittelnPreisDerPreisvariante()
    {
        if(empty($this->preisVarianteId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->ermittelnPreisDerPreisvariante();

        return $this;
    }

    /**
     * Ermittelt den Preis einer Preisvariante
     *
     * @return float
     */
    private function ermittelnPreisDerPreisvariante()
    {
        /** @var  $tabellePreise Application_Model_DbTable_preise */
        $tabellePreise = $this->pimple['tabellePreise'];
        $rows = $tabellePreise->find($this->preisVarianteId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_stimmt_nicht);

        $this->preisVariantePreis = $rows[0]['verkaufspreis'];

        return $rows[0]['verkaufspreis'];
    }

    /**
     * @param int $preisVarianteId
     */
    public function setPreisVarianteId($preisVarianteId)
    {
        $preisVarianteId = (int) $preisVarianteId;
        $this->preisVarianteId = $preisVarianteId;

        return $this;
    }

    /**
     * @return float
     */
    public function getPreisVariantePreis()
    {
        return $this->preisVariantePreis;
    }
}

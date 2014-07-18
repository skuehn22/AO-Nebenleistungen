<?php 
 /**
 * Ermittelt die Daten eines Bereiches der Konfigurationsdatei 'static'
 *
 * @author Stephan.Krauss
 * @date 26.06.13
 * @file ToolRegistryDaten.php
 * @package tools
 */
 
class nook_ToolRegistryDaten {

    // Fehler
    private $error_bereichsname_unbekannt = 1750;

    protected $bereichConfigDatei = null;
    protected $konfigDaten = array();

    /**
     * Übernimmt den Bereichsnamen der Konfig-Datei
     *
     * @param $bereichsname
     */
    public function __construct($bereichsname)
    {
        $this->bereichConfigDatei = $bereichsname;
    }

    /**
     * Steuert die Ermittlung der Daten der Konfig-Datei 'static'
     *
     * @return nook_ToolRegistryDaten
     * @throws nook_Exception
     */
    public function steuerungErmittelnDaten()
    {
        if(empty($this->bereichConfigDatei))
            throw new nook_Exception($this->error_bereichsname_unbekannt);

        $this->ermittelnKonfigDaten();

        return $this;
    }

    /**
     * Ermittelt die Daten eines Bereiches der Konfig-Datei 'static'
     *
     * @throws nook_Exception
     */
    private function ermittelnKonfigDaten()
    {
        /** @var  $static Zend_Config_Ini */
        $static = Zend_Registry::get('static');
        $support = $static->get($this->bereichConfigDatei);
        $konfigDaten = $support->toArray();

        if(empty($konfigDaten) or count($konfigDaten) == 0)
            throw new nook_Exception($this->error_bereichsname_unbekannt);

        $this->konfigDaten = $konfigDaten;

        return;
    }

    /**
     * @return array
     */
    public function getKonfigDaten()
    {
        return $this->konfigDaten;
    }
}

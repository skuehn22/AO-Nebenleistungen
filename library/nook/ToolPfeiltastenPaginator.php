<?php 
 /**
 * ermittelt die vorhergehende und nächste Seite eines Paginators
 *
 * @author Stephan.Krauss
 * @date 26.08.13
 * @file ToolPfeiltastenPaginator.php
 * @package tools
 */
class nook_ToolPfeiltastenPaginator
{
    // Fehler
    private $error = 1990;

    // Konditionen

    // Flags

    protected $paginator = array();
    protected $pfeiltasten = array();
    protected $aktuellerPaginator = null;

    public function __construct(array $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Steuerung der Ermittlung der vorhergehenden und nachfolgenden Seite eines Paginators
     *
     * @return nook_ToolPfeiltastenPaginator
     */
    public function steuerungVorhergehendeNaechste()
    {
        $this->ermittlungAktuelleSeite();
        $this->ermittelnVorgaenger();
        $this->ermittelnNachfolger();

        return $this;
    }

    /**
     * Ermittelt die aktuelle Seite im Array des Paginator
     *
     */
    private function ermittlungAktuelleSeite()
    {
        foreach($this->paginator as $key => $seite){
            if($seite['class'] == 'activStep')
                $this->aktuellerPaginator = $key;
        }

        return;
    }

    /**
     * Ermittelt die vorhergehende Seite der aktuellen Seite
     *
     */
    private function ermittelnVorgaenger()
    {
        if($this->aktuellerPaginator == 0)
            $this->pfeiltasten['vorherige'] = false;
        else{
            $vorherige = $this->aktuellerPaginator - 1;
            $this->pfeiltasten['vorherige'] = $this->paginator[$vorherige]['page'];
        }

        return;
    }

    /**
     * Ermittelt die nachfolgende Seite der aktuellen Seite
     */
    private function ermittelnNachfolger()
    {
        if(array_key_exists($this->aktuellerPaginator + 1, $this->paginator)){
            $naechste = $this->aktuellerPaginator + 1;
            $this->pfeiltasten['naechste'] =  $this->paginator[$naechste]['page'];
        }
        else
            $this->pfeiltasten['naechste'] = false;

        return;
    }

    /**
     * @return array
     */
    public function getPfeiltasten()
    {
        return $this->pfeiltasten;
    }




}

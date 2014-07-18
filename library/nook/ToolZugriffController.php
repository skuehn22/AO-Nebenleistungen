<?php
/**
* Tool zur Kontrolle des Zugriff auf einen Controller für eine Benutzergruppe. Individuelle Kontrolle der Action
*
* + Steuert die Kontrolle des Zugriff auf eine Action eines Kontroller
* + Kontrolliert die Rolle des Benutzers in Bezug der mindest geforderten Rolle der Action
*
* @date 05.11.2013
* @file ToolZugriffController.php
* @package tools
*/
class nook_ToolZugriffController
{

    // Konditionen

    // Meldungen

    // Fehler

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $benutzerRolle = null;
    protected $actionName = null;
    protected $zugriffAction = array();

    /**
     * @param array $zugriffAction
     * @return nook_ToolZugriffController
     */
    public function setZugriffAction(array $zugriffAction)
    {
        $this->zugriffAction = $zugriffAction;

        return $this;
    }

    /**
     * @param $actionName
     * @return nook_ToolZugriffController
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Steuert die Kontrolle des Zugriff auf eine Action eines Kontroller
     *
     * + Kontrolle mit Benutzerrolle aus Session
     * + Kontrolle mit Benutzerrolle aus 'tbl_adressen'
     *
     * @return nook_ToolZugriffController
     */
    public function steuerungKontrolleZugriffAction()
    {
        if( (empty($this->actionName)) or (count($this->zugriffAction) == 0) )
            throw new nook_Exception('Action Name oder Array der Zugriffsberechtigungen fehlt');

        // Kontrolle mit Benutzerrolle aus Session
        $this->ueberpruefungDesZugriffAufEineActionDesController();

        return $this;
    }

    /**
     * Kontrolliert die Rolle des Benutzers in Bezug auf die mindestens geforderten Rolle der Action
     *
     * $zugriff = array(
     *   'readAction' => 9,
     *   'suchen-benutzerAction' => 9
     * );
     */
    private function ueberpruefungDesZugriffAufEineActionDesController()
    {
        $rolleBenutzer = nook_ToolBenutzerrolle::getRolleDesBenutzers();
        $actionNameKomplett = $this->actionName."Action";

        if(array_key_exists($actionNameKomplett, $this->zugriffAction)){
            $rolleAction = $this->zugriffAction[$actionNameKomplett];

            if($rolleBenutzer < $rolleAction){
                throw new nook_Exception('Benutzerrolle ungenügend');
            }
        }
        else
            throw new nook_Exception('Zugriff auf Action '.$actionNameKomplett.' verboten');

        return;
    }
}

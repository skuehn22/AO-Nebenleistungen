<?php 
/**
* Setzt den Flag des Uebersetzungsmodus in der Session_Namespace 'translate'
*
* + Setzt den Übersetzungsmodus im Namespace 'translate'
* + Wechselt den Übersetzungsmodus im Namespace 'translate'
*
* @date 18.09.2013
* @file nook_ToolUebersetzungsModus.php
* @package tools
*/
class nook_ToolUebersetzungsModus
{
    // Fehler
    private $error = 2140;

    public function __construct()
    {

    }

    /**
     * Setzt den Übersetzungsmodus im Namespace 'translate'
     *
     * @param bool $uebersetzungsmodus
     * @return nook_ToolUebersetzungsModus
     */
    public function setUebersetzungsmodus($uebersetzungsmodus = false)
    {
        $translate = new Zend_Session_Namespace('translate');
        $translate->uebersetzungsmodus = $uebersetzungsmodus;

        return $this;
    }

    /**
     * Wechselt den Übersetzungsmodus im Namespace 'translate'
     *
     * @return nook_ToolUebersetzungsModus
     */
    public function switchUebersetzungsmodus()
    {
        $translate = new Zend_Session_Namespace('translate');
        if($translate->uebersetzungsmodus)
            $translate->uebersetzungsmodus = false;
        else
            $translate->uebersetzungsmodus = true;

        return $this;
    }
}

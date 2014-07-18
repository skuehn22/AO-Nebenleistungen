<?php 
/**
* Bricht eine Zeile nach einer Anzahl bestimmter Zeichen um.
*
* + Steuert die Generierung des Zeilenumbruch
* + Berechnet den Zeilenumbruch eines Textes
*
* @date 12.07.13
* @file ToolZeilenumbruch.php
* @package tools
*/
 class nook_ToolZeilenumbruch
{
    // Fehler
    private $error_zeilenlaenge_fehlt = 1880;

    protected $zeilenLaenge = null;
    protected $text = null;
    protected $zeilen = array();

    /**
     * @param $text
     * @return nook_ToolZeilenumbruch
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param $zeilenLaenge
     * @return nook_ToolZeilenumbruch
     */
    public function setZeilenLaenge($zeilenLaenge = 150)
    {
        $zeilenLaenge = (int) $zeilenLaenge;
        $this->zeilenLaenge = $zeilenLaenge;

        return $this;
    }

    /**
     * @return array
     */
    public function getZeilen()
    {
        return $this->zeilen;
    }

    /**
     * Steuert die Generierung des Zeilenumbruch
     *
     * @return nook_ToolZeilenumbruch
     * @throws nook_Exception
     */
    public function steuerungZeilenumbruch()
    {
        if(is_null($this->zeilenLaenge))
            throw new nook_Exception($this->error_zeilenlaenge_fehlt);

        // wenn kein Text vorhanden, dann kein Zeilenumbruch
        if(is_null($this->text))
            $this->zeilen = ' ';
        // wenn Text vorhanden
        else
            $this->erstellenZeilenumbruch();

        return $this;
    }

    /**
     * Berechnet den Zeilenumbruch eines Textes
     *
     * + gibt die Zeilen zurueck
     */
    private function erstellenZeilenumbruch()
    {
        $text = str_replace("\n"," ",$this->text);
        $text = str_replace("\r"," ",$text);

        $text = wordwrap($text, $this->zeilenLaenge, '###');
        $zeilen = explode("###",$text);
        $this->zeilen = $zeilen;

        return;
    }
}

<?php 
/**
* Shadow Front_Controller_Warenkorb->BlockGebuchteProgrammeShadow
*
* + Kontrolliert ob eine Bestandsbuchung vorliegt
* + Gibt den Standardtext zurück
* + Zeilenumbruch im Informationsblock
* + Ermittelt den Buchungshinweis einer Bestandsbuchung
*
* @author Stephan.Krauss
* @date 15.07.13
* @file WarenkorbBlockGebuchteProgrammeShadow.php
* @package front
* @subpackage shadow
*/
class Front_Model_WarenkorbBlockGebuchteProgrammeShadow
{
    // Fehler
    private $error = 1900;

    protected $pimple = null;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;
    }

    /**
     * Kontrolliert ob eine Bestandsbuchung vorliegt
     *
     * @return int
     */
    public function kontrolleBestandsbuchung()
    {
        $toolBestandsbuchungKontrolle = $this->pimple['toolBestandsbuchungKontrolle'];
        $zaehler = $toolBestandsbuchungKontrolle
            ->kontrolleBestandsbuchung()
            ->getZaehler();

        return $zaehler;
    }

    /**
     * Gibt den Standardtext zurück
     *
     * @return string
     */
    public function getStandardtext()
    {
        /** @var $informationGebuchteProgramme nook_ToolStandardtexte */
        $informationGebuchteProgramme = $this->pimple['toolStandardtexte']
            ->setPimple($this->pimple)
            ->setBlockname('bestandsbuchung_programme')
            ->steuerungErmittelnText()
            ->getText();

        return $informationGebuchteProgramme;
    }

    /**
     * Zeilenumbruch im Informationsblock
     *
     * @param $informationGebuchteProgramme
     * @return array
     */
    public function zeilenumbruch($informationGebuchteProgramme)
    {
        if(!$informationGebuchteProgramme)
            return ' ';

        /** @var  $toolZeilenumbruch nook_ToolZeilenumbruch */
        $toolZeilenumbruch = $this->pimple['toolZeilenumbruch'];
        $informationGebuchteProgramme = $toolZeilenumbruch
            ->setZeilenLaenge('150')
            ->setText($informationGebuchteProgramme)
            ->steuerungZeilenumbruch()
            ->getZeilen();

        return $informationGebuchteProgramme;
    }

    /**
     * Ermittelt den Buchungshinweis einer Bestandsbuchung
     *
     * + ermittelt Buchungsnummer
     * + ermittelt Buchungshinweis / Array
     * + Zeilenumbruch nach 70 Zeichen
     * + prüft ob ein Buchungshinweis vorhanden ist
     *
     * @return string
     */
    public function buchungshinweis()
    {
        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        $buchungsnummer = (int) $buchungsnummer;
        if( is_int($buchungsnummer) and ($buchungsnummer > 0) ){
            $buchungshinweisArray = nook_ToolBuchungsnummer::getBuchungshinweis($buchungsnummer);

            // kein Buchungshinweis vorhanden
            if(count(empty($buchungshinweisArray)))
                return false;

            // Zeilenumbruch
            $buchungsinformation = array();
            foreach($buchungshinweisArray as $buchungshinweisZeile){
                $toolZeilenumbruch = new nook_ToolZeilenumbruch();
                $zeilen = $toolZeilenumbruch
                    ->setZeilenLaenge('150')
                    ->setText($buchungshinweisZeile)
                    ->steuerungZeilenumbruch()
                    ->getZeilen();

                $buchungsinformation = array_merge($buchungsinformation, $zeilen);
            }

            return $buchungsinformation;
        }

        return false;
    }

}

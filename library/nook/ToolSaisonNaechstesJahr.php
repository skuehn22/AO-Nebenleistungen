<?php 
/**
* Berechnet die Saison des kommenden Jahres
*
* + Überprüft das vorhandensein der Ausgangswerte
* + Steuert die Ermittlung der Saisondaten des kommenden Jahres
* + Ermittelt die Saisondaten des kommenden Jahres, ISO 8601
* + wandelt den ersten moeglichen Buchungstag in Sekunden um.
*
* @author Stephan.Krauss
* @date 14.10.2013
* @file ToolSaisonNaechstesJahr.php
* @package tools
*/
class nook_ToolSaisonNaechstesJahr
{
    // Fehler
    private $error_anfangswerte_fehlen = 2300;

    // Informationen

    // Tabellen / Views
    private $tabelleProgrammdettails = null;

    // Konditionen

    // Flags
    private $flagSaisondatenVorgabe = array(
        'Saisonbeginn',
        'Saisonende'
    );


    protected $saisondatenAktuellesJahr = array();
    protected $kontrolle = true;

    protected $erstesMoeglichesBuchungsdatumInSekunden = null;
    protected $saisonStartKommendesJahr = null;
    protected $saisonEndeKommendesJahr = null;


    public function __construct()
    {
        $this->tabellen();
    }

    private function tabellen()
    {
        $this->tabelleProgrammdetails = null;

        return;
    }

    /**
     * @param array $saisonDaten
     * @return nook_ToolSaisonNaechstesJahr
     */
    public function setSaisondaten(array $saisonDaten)
    {
        $saisonDaten = $this->kontrolleGrunddaten($saisonDaten, $kontrolle);
        $this->kontrolle = $kontrolle;
        $this->saisondatenAktuellesJahr = $saisonDaten;

        return $this;
    }

    /**
     * Überprüft das vorhandensein der Ausgangswerte
     *
     * @param $saisonDaten
     * @param $kontrolle
     * @return mixed
     */
    private function kontrolleGrunddaten($saisonDaten, &$kontrolle)
    {
        foreach($this->flagSaisondatenVorgabe as $keyAusgangswerte){
            if(!array_key_exists($keyAusgangswerte, $saisonDaten))
                $kontrolle = false;
        }

        return $saisonDaten;
    }

    /**
     * Steuert die Ermittlung der Saisondaten des kommenden Jahres
     *
     * + Saisonstart und Saisonende kommendes Jahr
     * + erster möglicher Buchungstag in Sekunden
     *
     * @return nook_ToolSaisonNaechstesJahr
     * @throws nook_Exception
     */
    public function steuerungSaisonNaechstesJahr()
    {
        if(false === $this->kontrolle)
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        // Saisonstart und Saisonende kommendes Jahr
        $saisondatenKommendesJahr = $this->ermittlungSaisonKommendesJahr($this->saisondatenAktuellesJahr);
        $this->saisonStartKommendesJahr = $saisondatenKommendesJahr['saisonStartKommendesJahr'];
        $this->saisonEndeKommendesJahr = $saisondatenKommendesJahr['saisonEndeKommendesJahr'];

        // Ermittlung erster moeglicher Buchungstag
        $this->erstesMoeglichesBuchungsdatumInSekunden = $this->ermittlungErsterMoeglicherBuchungstag($this->saisonStartKommendesJahr);

        return $this;
    }

    /**
     * Ermittelt die Saisondaten des kommenden Jahres, ISO 8601
     *
     * + Saisonstart kommendes Jahr
     * + Saisonende kommendes Jahr
     *
     * @param array $saisondatenAktuellesJahr
     * @return array
     */
    private function ermittlungSaisonKommendesJahr(array $saisondatenAktuellesJahr)
    {
        $saisondatenKommendesJahr = array();

        $saisonStartKommendesJahr = new DateTime($saisondatenAktuellesJahr['Saisonbeginn']);
        $saisonStartKommendesJahr->add(new DateInterval('P1Y'));
        $saisondatenKommendesJahr['saisonStartKommendesJahr'] = $saisonStartKommendesJahr->format("Y-m-d H:i:s");

        $saisonEndeKommendesJahr = new DateTime($saisondatenAktuellesJahr['Saisonende']);
        $saisonEndeKommendesJahr->add(new DateInterval('P1Y'));
        $saisondatenKommendesJahr['saisonEndeKommendesJahr'] = $saisonEndeKommendesJahr->format("Y-m-d H:i:s");

        return $saisondatenKommendesJahr;
    }

    /**
     * wandelt den ersten moeglichen Buchungstag in Sekunden um.
     *
     * @param $ersterBuchungstag
     * @return int
     */
    private function ermittlungErsterMoeglicherBuchungstag($ersterBuchungstag)
    {

        $ersterBuchungstagInSekunden = strtotime($ersterBuchungstag);

        return $ersterBuchungstagInSekunden;
    }

    /**
     * @return int
     */
    public function getErstesMoeglichesBuchungsdatumInSekunden()
    {
        return $this->erstesMoeglichesBuchungsdatumInSekunden;
    }

    /**
     * @return string
     */
    public function getSaisonStartKommendesJahr()
    {
        return $this->saisonStartKommendesJahr;
    }

    /**
     * @return string
     */
    public function getSaisonEndeKommendesJahr()
    {
        return $this->saisonEndeKommendesJahr;
    }



}

<?php 
/**
 * Sortiert die Daten des Datensatzes 'Mail an den Programmanbieter' nach den Programmanbieter
 *
 * @author Stephan.Krauss
 * @date 25.09.2013
 * @file MailDatenProgrammanbieter.php
 * @package front
 * @subpackage model
 */
class Front_Model_MailDatenProgrammbestellung
{
    // Fehler
    private $error = 2150;

    // Message
    private $message = 2150;

    // Konditionen

    // Flags

    protected $datenMailRohform = array();
    protected $datenMailAnProgrammanbieter = array();

    /**
     * Übernimmt die Daten der Mail an den Programmanbieter
     *
     *
     * @param array $datenMailAnProgrammanbieter
     */
    public function __construct(array $datenMailAnProgrammanbieter)
    {
        $this->datenMailRohform = $datenMailAnProgrammanbieter;
    }

    /**
     * Sortiert die Maildaten nach dem Programmanbieter
     *
     * @return Front_Model_MailDatenProgrammbestellung
     */
    public function sortiereNachProgrammanbieter()
    {
        $this->anlegenProgrammanbieter();

        return $this;
    }

    /**
     * sichtet die gebuchten Programme. Reorganisiert die Datensätze
     */
    private function anlegenProgrammanbieter()
    {
        for($i=0; $i < count($this->datenMailRohform); $i++){
            // anlegen erster Datensatz der Maildaten
            if($i == 0)
                $this->anlegenDatensatzMail(0);
            else{
                // Kontrolle ob Programmanbieter schon registriert
                $programmanbieterId = $this->datenMailRohform[$i]['programmanbieter']['adressen_id'];
                $programmanbieterId = $this->kontrolleProgrammanbieterId($programmanbieterId);

                // anlegen neuer Datensatz der Maildaten
                if(false === $programmanbieterId)
                    $this->anlegenDatensatzMail($i);
                // ergaenzen Datensatz der Maildaten
                else
                    $this->ergaenzenDatensatzMail($programmanbieterId, $i);
            }
        }

        return;
    }

    /**
     * Kontrolliert ob die ID des Programmanbieters schon in $this->datenMailAnProgrammanbieter vorhanden ist
     *
     * + wenn nicht vorhanden dann, return false
     * + wenn vorhanden dann, return $programmanbieterId
     *
     * @param $adressenId
     * @return bool
     */
    private function kontrolleProgrammanbieterId($adressenId)
    {
        $kontrolleProgrammanbieterRegistriert = false;

        for($i = 0; $i < count($this->datenMailAnProgrammanbieter); $i++ ){
            if($this->datenMailAnProgrammanbieter[$i]['programmanbieter']['adressen_id'] == $adressenId){
                $kontrolleProgrammanbieterRegistriert = $this->datenMailAnProgrammanbieter[$i]['programmanbieter']['adressen_id'];

                break;
            }
        }

        return $kontrolleProgrammanbieterRegistriert;
    }

    /**
     * Legt einen neuen Datensatz der Informationen für ein Mail an einen Programmanbieter an
     *
     * @param $i
     */
    private function anlegenDatensatzMail($i)
    {
        // Buchungsdaten
        $buchungsdaten = $this->splitDaten($this->datenMailRohform[$i]);

        $daten = array(
            'programmanbieter' => $this->datenMailRohform[$i]['programmanbieter'],
            'programme' => array(
                0 => array(
                    'programmbeschreibung' => $this->datenMailRohform[$i]['programmbeschreibung'], // Programmbeschreibung
                    'programmvariante' => $this->datenMailRohform[$i]['programmvariante'], // Programmvariante
                    'buchungsdaten' => $buchungsdaten // Buchungsdaten
                )
            )
        );

         // Daten gebuchtes Programm
        $this->datenMailAnProgrammanbieter[] = $daten;

        return;
    }

    /**
     * Ergänzt den Datensatz der Daten einer Mail an den Programmanbieter
     *
     * + ermitteln Buchungsdaten
     * + ermitteln Daten des gebuchten Programmes
     * + zuordnen des Programmes zu einem bereits vorhandenen Programmanbieter
     *
     * @param $datenMailAnProgrammanbieterId
     * @param $i
     */
    private function ergaenzenDatensatzMail($programmanbieterId, $i)
    {
        // ermitteln Buchungsdaten
        $buchungsdaten = $this->splitDaten($this->datenMailRohform[$i]);

        // Daten des gebuchten Programmes
        $daten = array(
            'programmbeschreibung' => $this->datenMailRohform[$i]['programmbeschreibung'], // Programmbeschreibung
            'programmvariante' => $this->datenMailRohform[$i]['programmvariante'], // Programmvariante
            'buchungsdaten' => $buchungsdaten // Buchungsdaten
        );

        // zuordnen des Programmes zu einem bereits vorhandenen Programmanbieter
        for($j=0; $j < count($this->datenMailAnProgrammanbieter); $j++){
            if($this->datenMailAnProgrammanbieter[$j]['programmanbieter']['adressen_id'] == $programmanbieterId){
                $this->datenMailAnProgrammanbieter[$j]['programme'][] = $daten;

                break;
            }
        }

        return;
    }

    /**
     * Überprüft einen ankommenden Datensatz.
     *
     * + filtert Daten aus und gibt diese als array zurück
     *
     * @param $datensatz
     * @return array
     */
    private function splitDaten(array $datensatz)
    {
        $einzeldaten = array();

        foreach($datensatz as $key => $value){
            if(!is_array($value))
                $einzeldaten[$key] = $value;
        }

        return $einzeldaten;
    }



    /**
     * Gibt die Daten aufbereitet nach Programmanbieter zurück
     *
     * @return array
     */
    public function getDatenMailProgrammanbieter()
    {
        return $this->datenMailAnProgrammanbieter;
    }
}

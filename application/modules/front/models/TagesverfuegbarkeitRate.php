<?php
/**
 * Kontrolle ob an allen Tagen des Buchungszeitraumes im Hotel die Rate zur Verfügung steht.
 *
 * + Es werden die verfüegbaren Raten eines Hotels für ein Zeitraum übergeben.
 *
 * @author Stephan Krauss
 * @date 02.04.2014
 * @file TagesverfuegbarkeitRate.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_TagesverfuegbarkeitRate
{
    protected $ratenEinesZeitraumesInEinemHotel = array();
    protected $anreisedatum = null;
    protected $abreisedatum = null;
    protected $anzahlUebernachtungen = 0;

    /**
     * @param array $ratenEinesZeitraumesInEinemHotel
     * @return Front_Model_TagesverfuegbarkeitRate
     */
    public function setRatenEinesZeitraumesInEinemHotel(array $ratenEinesZeitraumesInEinemHotel)
    {
        $this->ratenEinesZeitraumesInEinemHotel = $ratenEinesZeitraumesInEinemHotel;

        return $this;
    }

    /**
     * @param $anreisedatum
     * @return Front_Model_TagesverfuegbarkeitRate
     */
    public function setAnreisedatum($anreisedatum)
    {
        $this->anreisedatum = $anreisedatum;

        return $this;
    }

    /**
     * @param $abreisedatum
     * @return Front_Model_TagesverfuegbarkeitRate
     */
    public function setAbreisedatum($abreisedatum)
    {
        $this->abreisedatum = $abreisedatum;

        return $this;
    }

    /**
     * @param $anzahlUenernachtungen
     * @return Front_Model_TagesverfuegbarkeitRate
     */
    public function setAnzahlUebernachtungen($anzahlUenernachtungen)
    {
        $this->anzahlUebernachtungen = $anzahlUenernachtungen;

        return $this;
    }

    /**
     * Steuert die Kontrolle der Raten eines Hotels in einem Zeitraum
     *
     * @return $this
     * @throws Exception
     */
    public function steuerungKontrolleRatenverfuegbarkeit()
    {
        try{
            if(count($this->ratenEinesZeitraumesInEinemHotel) == 0)
                throw new nook_Exception('Array ratenEinesZeitraumesInEinemHotel fehlt');

            if(is_null($this->anreisedatum))
                throw new nook_Exception('Anreisedatum fehlt');

            if(is_null($this->abreisedatum))
                throw new nook_Exception('Abreisedatum fehlt');

            if($this->anzahlUebernachtungen == 0)
                throw new nook_Exception('Anzahl Uebernachtungen fehlt');

            $moeglichRaten = $this->bestimmenDerRatenDesHotelsAmAnreisetag($this->ratenEinesZeitraumesInEinemHotel);
            $moeglichRaten = $this->pruefenObAnAllemTagenRatenVerfuegbarSind($this->anzahlUebernachtungen, $moeglichRaten);
            $moeglichRaten = $this->durchsuchenRatenNachVerfuegbarkeit($this->ratenEinesZeitraumesInEinemHotel, $moeglichRaten);
            $moeglichRaten = $this->durchsuchenRatenNachMinimum($moeglichRaten);

            $this->ratenEinesZeitraumesInEinemHotel = $moeglichRaten;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * durchsucht die Raten nach der minimalen Tagesverfuegbarkeit
     *
     * @param array $moeglichRaten
     * @return array
     */
    protected function durchsuchenRatenNachMinimum(array $moeglichRaten)
    {
        $gepruefteRatenEinesZeitraumesInEinemHotel = array();

        for($i=0; $i < count($moeglichRaten); $i++){

            $moeglicheRate = $moeglichRaten[$i];

            if(!array_key_exists($moeglicheRate['ratenId'], $gepruefteRatenEinesZeitraumesInEinemHotel)){
                $gepruefteRatenEinesZeitraumesInEinemHotel[$moeglicheRate['ratenId']] = $moeglicheRate;
            }
            else{
                if($gepruefteRatenEinesZeitraumesInEinemHotel[$moeglicheRate['ratenId']]['roomlimit'] > $moeglicheRate['roomlimit']);
                    $gepruefteRatenEinesZeitraumesInEinemHotel[$moeglicheRate['ratenId']] = $moeglicheRate;
            }
        }

        $gepruefteRatenEinesZeitraumesInEinemHotel = array_merge($gepruefteRatenEinesZeitraumesInEinemHotel);

        return $gepruefteRatenEinesZeitraumesInEinemHotel;
    }

    /**
     * löschen von Raten die nicht verfügbar sind
     *
     * @param $ratenEinesZeitraumesInEinemHotel
     * @param $moeglichRatenAnreisetag
     * @return array
     */
    protected function durchsuchenRatenNachVerfuegbarkeit(array $ratenEinesZeitraumesInEinemHotel,array $moeglichRatenAnreisetag)
    {
        $gepruefteRatenEinesZeitraumesInEinemHotel = array();

        for($i=0; $i < count($ratenEinesZeitraumesInEinemHotel); $i++){
            if(array_key_exists($ratenEinesZeitraumesInEinemHotel[$i]['ratenId'], $moeglichRatenAnreisetag))
                $gepruefteRatenEinesZeitraumesInEinemHotel[] = $ratenEinesZeitraumesInEinemHotel[$i];
        }

        return $gepruefteRatenEinesZeitraumesInEinemHotel;
    }

    /**
     * Kontrollier die Anzahl der Tage der moeglichen Raten
     *
     * + Raten die an einem Tag nicht vorhanden sind werden gelöscht
     *
     * @param $ratenEinesZeitraumesInEinemHotel
     * @param $moeglichRatenAnreisetag
     */
    protected function pruefenObAnAllemTagenRatenVerfuegbarSind($anzahlUebernachtungen ,array $moeglichRatenAnreisetag)
    {
        foreach($moeglichRatenAnreisetag as $ratenId => $anzahlTageRate){
            if($anzahlUebernachtungen != $anzahlTageRate)
                unset($moeglichRatenAnreisetag[$ratenId]);
        }

        return $moeglichRatenAnreisetag;
    }

    /**
     * Bestimmen der möglichen Raten eines Hotels am Anreisetag
     *
     * @param $ratenEinesZeitraumesInEinemHotel
     * @return array
     */
    protected function bestimmenDerRatenDesHotelsAmAnreisetag($ratenEinesZeitraumesInEinemHotel)
    {
        $moeglichRatenAnreisetag = array();

        for($i=0; $i < count($ratenEinesZeitraumesInEinemHotel); $i++){

            // Anreisedatum
            if($i == 0)
                $anreiseDatum = $ratenEinesZeitraumesInEinemHotel[0]['datum'];

            // baut Array der Raten am Anreisetag
            if($anreiseDatum == $ratenEinesZeitraumesInEinemHotel[$i]['datum'])
                $moeglichRatenAnreisetag[$ratenEinesZeitraumesInEinemHotel[$i]['ratenId']] = 0;

            $moeglichRatenAnreisetag[$ratenEinesZeitraumesInEinemHotel[$i]['ratenId']]++;
        }

        return $moeglichRatenAnreisetag;
    }

    /**
     * @return array
     */
    public function getGepruefteVerfuegbareRaten()
    {
        return $this->ratenEinesZeitraumesInEinemHotel;
    }
}
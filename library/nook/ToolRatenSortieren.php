<?php 
 /**
 * Sortieren der Übernachtungen Raten eines Warenkorbes nach einem Kriterium asc / desc
 *
 * + Aufbau der Rate eines Warenkorbes
 * + Aufbau Rate: $hotelbuchungen['hotelId']['datum ISO 8601']['Zaehler gebuchte Rate'][...]
 *
 *
 * @author Stephan.Krauss
 * @date 09.12.2013
 * @file ToolRatenSortieren.php
 * @package tools
 */
class nook_ToolRatenSortieren
{
    protected $ratenEinesWarenkorbes = array();
    protected $sortierungKriterium = null;
    protected $sortierReihenfolge = null;

    protected $sortierteRaten = array();

    /**
     * Steuert die Sortierung der Raten / Übernachtungen eines Warenkorbes entsprechend der Vorgabe.
     *
     * @return nook_ToolRatenSortieren
     */
    public function steuerungSortierungRatenEinesWarenkorbes()
    {
        if(is_null($this->sortierungKriterium))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->sortierReihenfolge))
            throw new nook_Exception('Anfangswert fehlt');

        if(count($this->ratenEinesWarenkorbes) < 1)
            return $this;

        $this->filternNachSortierkriterium($this->ratenEinesWarenkorbes, $this->sortierungKriterium, $this->sortierReihenfolge);


        return $this;
    }

    /**
     * Zerlegt die Raten eines Warenkorbes in
     *
     * + Raten eines Hotels
     * + Raten des Hotels an einem Tag
     * + Sortierung der Raten eines Hotels an einem Tag nach einem Kriterium asc / desc
     *
     * @param array $ratenEinesWarenkorbes
     * @param $sortierungKriterium
     * @param $sortierReihenfolge
     * @return array
     */
    protected function filternNachSortierkriterium(array $ratenEinesWarenkorbes, $sortierungKriterium, $sortierReihenfolge)
    {
        $sortierteRaten = array();

        foreach($ratenEinesWarenkorbes as $hotelId => $datumRaten){
            foreach($datumRaten as $datum => $raten){
                $sortierteRaten = $this->sortierenRatenDatumHotel($raten, $sortierungKriterium, $sortierReihenfolge);
                $ratenEinesWarenkorbes[$hotelId][$datumRaten] = $sortierteRaten;
            }
        }

        return $ratenEinesWarenkorbes;
    }

    /**
     * Sortiert die Raten eine Hotels zu einem Datum nach einem Kriterium asc / desc
     *
     * + Sortierkriterium entspricht Rabattpolitik des Hotels
     *
     * @param $raten
     * @param $sortierungKriterium
     * @param $sortierReihenfolge
     */
    protected function sortierenRatenDatumHotel(array $raten, $sortierungKriterium, $sortierReihenfolge)
    {
        $tmpRatenEinesHotelsAnEinemTag = array();

        foreach($raten as $key => $rate){
            $tmpRatenEinesHotelsAnEinemTag[$key] = $rate[$sortierungKriterium];
        }

        if($sortierReihenfolge === true)
            asort($tmpRatenEinesHotelsAnEinemTag);
        else
            arsort($tmpRatenEinesHotelsAnEinemTag);

        foreach($tmpRatenEinesHotelsAnEinemTag as $key => $value){
            $tmpRatenEinesHotelsAnEinemTag[$key] = $raten[$key];
        }

        return $tmpRatenEinesHotelsAnEinemTag;
    }


    /**
     * @param array $ratenEinesWarenkorbes
     * @return nook_ToolRatenSortieren
     */
    public function setRateneinesWarenkorbes(array $ratenEinesWarenkorbes)
    {
        $this->$ratenEinesWarenkorbes = $ratenEinesWarenkorbes;

        return $this;
    }

    /**
     * @return array
     */
    public function getSortierteRaten()
    {
        return $this->sortierteRaten;
    }

    /**
     * Übernahme des Sortierkriteriums und Reihenfolge der Sortierung
     *
     * + $sortierReihenfolge = true , Sortierung asc
     * + $sortierReihenfolge = false , Sortierung desc
     *
     * @param $sortierungKriterium
     * @param $sortierReihenfolge
     * @return nook_ToolRatenSortieren
     */
    public function setSortierKriterium($sortierungKriterium, $sortierReihenfolge)
    {
        $sortierReihenfolge = (bool) $sortierReihenfolge;
        if(!is_bool($sortierReihenfolge))
            throw new nook_Exception('Anfangswert falsch');

        $this->$sortierungKriterium = $sortierungKriterium;
        $this->sortierReihenfolge = $sortierReihenfolge;

        return $this;
    }

}
 
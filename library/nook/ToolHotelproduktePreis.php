<?php 
 /**
 * Ermittelt den Preis eines Hotelproduktes entsprechend des Produkttypes. Produkt Typ Preis
 *
 * @author Stephan.Krauss
 * @date 28.11.2013
 * @file ToolHotelproduktePreis.php
 * @package tools
 */
class nook_ToolHotelproduktePreis
{
    private $produktPreis = null;
    private $produktAnzahl = null;
    private $produktTyp = null;
    private $anzahlUenbernachtungen = null;
    private $anzahlPersonen = null;
    private $anzahlZimmer = null;

    private $gesamtPreisHotelprodukt = 0;

    /**
     * @param $produktTyp
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setProduktTyp($produktTyp)
    {
        $produktTyp = (int) $produktTyp;
        if($produktTyp == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->produktTyp = $produktTyp;

        return $this;
    }

    /**
     * @param $produktPreis
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setProduktPreis($produktPreis)
    {
        $produktPreis = (float) $produktPreis;
        if($produktPreis == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->produktPreis = $produktPreis;

        return $this;
    }

    /**
     * @param $produktAnzahl
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setProduktAnzahl($produktAnzahl = false)
    {
        if(empty($produktAnzahl))
            return $this;

        $produktAnzahl = (int) $produktAnzahl;
        if($produktAnzahl == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->produktAnzahl = $produktAnzahl;

        return $this;
    }

    /**
     * @param $anzahlPersonen
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setAnzahlPersonen($anzahlPersonen = false)
    {
        if(empty($anzahlPersonen))
            return $this;

        $anzahlPersonen = (int) $anzahlPersonen;
        if($anzahlPersonen == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->anzahlPersonen = $anzahlPersonen;

        return $this;
    }

    /**
     * @param $anzahlZimmer
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setAnzahlZimmer($anzahlZimmer = false)
    {
        if(empty($anzahlZimmer))
            return $this;

        $anzahlZimmer = (int) $anzahlZimmer;
        if($anzahlZimmer == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->anzahlZimmer = $anzahlZimmer;

        return $this;
    }

    /**
     * @param $anzahlUebernachtungen
     * @return nook_ToolHotelproduktePreis
     * @throws nook_Exception
     */
    public function setAnzahlUebernachtungen($anzahlUebernachtungen = false)
    {
        if(empty($anzahlUebernachtungen))
            return $this;

        $anzahlUebernachtungen = (int) $anzahlUebernachtungen;
        if($anzahlUebernachtungen == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->anzahlUenbernachtungen = $anzahlUebernachtungen;

        return $this;
    }

    /**
     * @return float
     */
    public function getGesamtpreisHotelprodukt()
    {
        return $this->gesamtPreisHotelprodukt;
    }

    /**
     * Steuert die Berechnung des Gesamtpreises eines Hotelproduktes entsprechen Typ Hotelprodukt
     *
     *
     * @return nook_ToolHotelproduktePreis
     */
    public function steuerungBerechnungGesamtpreisHotelprodukt()
    {
        if(is_null($this->produktTyp))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->produktPreis))
            throw new nook_Exception('Anfangswert fehlt');

        if(is_null($this->produktAnzahl))
            throw new nook_Exception('Anfangswert fehlt');


        // Typ 1: 'je Person'
        if($this->produktTyp == 1){
            if(is_null($this->anzahlPersonen))
                throw new nook_Exception('Anfangswert fehlt');
        }

        // Typ 2: 'je Zimmer'
        if($this->produktTyp == 2){
            if(is_null($this->anzahlZimmer))
                throw new nook_Exception('Anfangswert fehlt');
        }

        // Typ 3: 'Personen * Nächte'
        if($this->produktTyp == 3){
            if(is_null($this->anzahlPersonen) or is_null($this->anzahlUenbernachtungen))
                throw new nook_Exception('Anfangswert fehlt');
        }

        // Typ 4: 'Anzahl'
        if($this->produktTyp == 4){
            if(is_null($this->produktAnzahl))
                throw new nook_Exception('Anfangswert fehlt');
        }

        // Typ 5: 'Stück * Nächte'
        if( ($this->produktTyp == 5)){
            if(is_null($this->anzahlUenbernachtungen) or is_null($this->produktAnzahl))
                throw new nook_Exception('Anfangswert fehlt');
        }

        // Typ 6: 'Person / Datum'
        if($this->produktTyp == 6){
            if(is_null($this->anzahlUenbernachtungen) or is_null($this->anzahlPersonen))
                throw new nook_Exception('Anfangsert fehlt');
        }

        $gesamtPreisHotelprodukt = $this->ermittelnGesamtpreisHotelprodukt($this->produktTyp);
        $this->gesamtPreisHotelprodukt = $gesamtPreisHotelprodukt;

        return $this;
    }

    /**
     * Ermittelt den Gesamtpreis eines Produktes entsprehend des Produkttypes. Produkt Typ Preis Hotel Produkt
     *
     * @param $produktTyp
     * @return int|null
     */
    protected function ermittelnGesamtpreisHotelprodukt($produktTyp)
    {
        switch($produktTyp){
            case 1:
                $gesamtPreisHotelprodukt = $this->anzahlPersonen * $this->produktPreis;
                break;
            case 2:
                $gesamtPreisHotelprodukt = $this->anzahlZimmer * $this->produktPreis;
                break;
            case 3:
                $gesamtPreisHotelprodukt = $this->anzahlPersonen * $this->anzahlUenbernachtungen * $this->produktPreis;
                break;
            case 4:
                $gesamtPreisHotelprodukt = $this->produktAnzahl * $this->produktPreis;
                break;
            case 5:
                $gesamtPreisHotelprodukt = $this->produktAnzahl * $this->anzahlUenbernachtungen * $this->produktPreis;
                break;
            default:
                throw new nook_Exception('Produkttyp unbekannt');
                break;
        }

        return $gesamtPreisHotelprodukt;
    }

}
 
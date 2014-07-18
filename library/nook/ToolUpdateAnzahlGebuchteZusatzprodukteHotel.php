<?php

/**
 * Veraendert die Anzahl der Zustzprodukte eines Hotels.
 *
 * + Zusatzprodukte wurden bereits gebucht
 * + Zusatzprodukte werden durch das Hotel pro Person und Nacht berechnet
 *
 * @author Stephan Krauss
 * @date 01.07.2014
 * @file nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel.php
 * @project HOB
 * @package tool
 */
class nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel
{
    protected $pimple = null;

    protected $personenanzahl = null;
    protected $teilrechnungId = null;
    protected $anzahlNaechte = 0;
    protected $typZusatzprodukte = array();

    protected $anzahlGeaenderterProdukte = 0;

    /**
     * erstellt Pimple
     */
    public function __construct()
    {
        $pimple = $this->servicecontainer();
        $this->pimple = $pimple;
    }

    /**
     * @return callable|Pimple_Pimple
     */
    protected function servicecontainer()
    {
        $pimple = new Pimple_Pimple();

        $pimple['tabelleProduktbuchung'] = function () {
            return new Application_Model_DbTable_produktbuchung();
        };


        return $pimple;
    }

    /**
     * @param $anzahlNaechte
     * @return nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel
     * @throws nook_Exception
     */
    public function setAnzahlNaechte($anzahlNaechte){
        $anzahlNaechte = (int) $anzahlNaechte;
        if($anzahlNaechte == 0)
            throw new nook_Exception('Anzahl naechte falsch');

        $this->anzahlNaechte = $anzahlNaechte;

        return $this;
    }

    /**
     * @param $personenanzahl
     * @return nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel
     * @throws nook_Exception
     */
    public function setPersonenanzahl($personenanzahl)
    {
        $personenanzahl = (int)$personenanzahl;
        if ($personenanzahl == 0)
            throw new nook_Exception('unzulaessige Personenanzahl');

        $this->personenanzahl = $personenanzahl;

        return $this;
    }

    /**
     * @param $teilrechnungId
     * @return nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel
     * @throws nook_Exception
     */
    public function setTeilrechnungId($teilrechnungId)
    {
        $teilrechnungId = (int)$teilrechnungId;
        if ($teilrechnungId == 0)
            throw new nook_Exception('unzulaessige Buchungsnummer ID');

        $this->teilrechnungId = $teilrechnungId;

        return $this;
    }

    /**
     * @param array $typZusatzprodukte
     * @return nook_ToolUpdateAnzahlGebuchteZusatzprodukteHotel
     * @throws nook_Exception
     */
    public function setTypZusatzprodukte(array $typZusatzprodukte)
    {
        if (count($typZusatzprodukte) < 1)
            throw new nook_Exception('Array der Typen Zusatzprodukte ist falsch');

        $this->typZusatzprodukte = $typZusatzprodukte;

        return $this;
    }

    public function steuerungVeraendernZusatzprodukteHopTop(){
        try{
            if(is_null($this->typZusatzprodukte))
                throw new nook_Exception('Array Typ der Zusatzprodukte fehlt');

            if(is_null($this->teilrechnungId))
                throw new nook_Exception('Teilrechnung ID fehlt');

            if(is_null($this->personenanzahl))
                throw new nook_Exception('Personenanzahl fehlt');

            if($this->anzahlNaechte == 0)
                throw new nook_Exception('Anzahl Naechte fehlt');

            // Ändern der Anzahl
            foreach ($this->typZusatzprodukte as $typ) {
                $anzahlGeanderterProdukte = $this->veraendernAnzahlBereitsGebuchterProdukte($this->personenanzahl, $this->teilrechnungId, $this->anzahlNaechte, $typ);
                $this->anzahlGeaenderterProdukte += $anzahlGeanderterProdukte;
            }

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Verändert die Anzahl der bereits gebuchten Produkte
     *
     * @param $personenanzahl
     * @param $buchungsnummerId
     * @param $typ
     * @return int
     */
    protected function veraendernAnzahlBereitsGebuchterProdukte($personenanzahl, $teilrechnungId, $anzahlNaechte, $typ){

        $where = array(
            "teilrechnungen_id = ".$teilrechnungId,
            "produktTyp = ".$typ
        );

        $update = array(
            "anzahl" => $personenanzahl
        );

        /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];

        // Veränderung Anzahl der Produkte
        $anzahlGeanderterProdukte = $tabelleProduktbuchung->update($update, $where);

        // Veränderung summeProduktpreis
        $update = array(
            "summeProduktPreis" => new Zend_Db_Expr("anzahl * aktuellerProduktPreis * ".$anzahlNaechte)
        );

        $tabelleProduktbuchung->update($update, $where);

        return $anzahlGeanderterProdukte;
    }

    /**
     * @return int
     */
    public function getAnzahlGeanderteZusatzprodukte(){
        return $this->anzahlGeaenderterProdukte;
    }


}
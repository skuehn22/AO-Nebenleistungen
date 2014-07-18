<?php 
 /**
 * Vergleicht 2 Programme entsprechend der Check Summen.
 *
 * + wenn die Programme in allen Spalten nicht übereinstimmen
 * + $programmeIdentisch = false
 *
 * @author Stephan.Krauss
 * @date 31.01.2014
 * @file vergleicheProgrammeVariableAttribute.php
 * @package front
 * @subpackage model
 */
class Front_Model_vergleicheProgrammeVariableAttribute
{
    // konstante Werte des datensatzes eines Programmes

    protected $buchungsNummerId = null;
    protected $programmId = null;
    protected $preisVarianteId = null;


    protected $zaehlerAlt = null;
    protected $zaehlerNeu = null;

    protected $excludeCols = array(
        'id',
        'buchungsnummer_id',
        'zaehler',
        'status',
        'hobNummer',
        'anzahl',
        'teilrechnung_id',
        'treffpunktText',
        'date',
        'offlinebuchung',
        'informationen',
        'sachleistung',
        'changed_by',
        'date_change',
        'tbl_programme_preisvarianten_id',
        'programmdetails_id',
        'hobNummer'
    );

    protected $programmeIdentisch = false;

    /** @var $tabelleProgrammbuchung Zend_Db_Table_Abstract  */
    protected $tabelleProgrammbuchung = null;

    /**
     * @param int $buchungsNummerId
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @param $preisVarianteId
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setPreisVarianteId($preisVarianteId)
    {
        $this->preisVarianteId = $preisVarianteId;

        return $this;
    }

    /**
     * @param $programmId
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setProgrammId($programmId)
    {
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param $tabelleProgrammbuchung
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setTabelleProgrammbuchung(Application_Model_DbTable_programmbuchung $tabelleProgrammbuchung)
    {
        $this->tabelleProgrammbuchung = $tabelleProgrammbuchung;

        return $this;
    }

    /**
     * @param $zaehlerAlt
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setZaehlerAlt($zaehlerAlt)
    {
        $this->zaehlerAlt = $zaehlerAlt;

        return $this;
    }

    /**
     * @param $zaehlerNeu
     * @return Front_Model_vergleicheProgrammeVariableAttribute
     */
    public function setZaehlerNeu($zaehlerNeu)
    {
        $this->zaehlerNeu = $zaehlerNeu;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getProgrammeIdentisch()
    {
        return $this->programmeIdentisch;
    }

    /**
     * Steuert den Vergleich der variablen Werte zweier Programme
     *
     * @return $this
     */
    public function steuerungUeberpruefungVergleichDerProgramme()
    {
        try{
            if(is_null($this->buchungsNummerId))
                throw new nook_Exception('Buchungsnummer fehlt');

            if(is_null($this->zaehlerAlt))
                throw new nook_Exception('Zaehler Alt fehlt');

            if(is_null($this->zaehlerNeu))
                throw new nook_Exception('Zaehler Neu fehlt');

            if(is_null($this->programmId))
                throw new nook_Exception('Programm ID fehlt');

            if(is_null($this->preisVarianteId))
                throw new nook_Exception('Preisvariante ID fehlt');

            if(is_null($this->tabelleProgrammbuchung))
                throw new nook_Exception('Tabelle Programmbuchung fehlt');

            $gebuchteProgrammePreisvariante = bestimmeCheckSummeProgrammbuchung($this->buchungsNummerId, $this->zaehlerAlt, $this->programmId, $this->preisVarianteId);

            $checkSummeNeuProgrammbuchung = bestimmeCheckSummeProgrammbuchung($this->buchungsNummerId, $this->zaehlerNeu, $this->programmId, $this->preisVarianteId);

            if($gebuchteProgrammePreisvariante == $checkSummeNeuProgrammbuchung)
                $this->programmeIdentisch = true;

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Ermittelt die gebuchten Programme die identische
     *
     * + Buchungsnummer
     * + Zaehler
     * + Programm ID
     * + Preisvariante ID
     *
     * @param $buchungsNummerId
     * @param $zaehlerNeu
     * @param $programmId
     * @param $preisVarianteId
     * @return array
     */
    protected function bestimmeProgrammePreisvariante($buchungsNummerId, $zaehler, $programmId, $preisVarianteId)
    {
        $checkSummeProgrammbuchung = false;

        $whereBuchungsnummerId = "buchungsnummer_id = ".$buchungsNummerId;
        $whereZaehler = "zaehler = ".$zaehler;
        $whereProgrammId = "programmdetails_id = ".$programmId;
        $wherePreisvarianteId = "tbl_programme_preisvarianten_id = ".$preisVarianteId;

        $select = $this->tabelleProgrammbuchung->select();
        $select
            ->where($whereBuchungsnummerId)
            ->where($wherePreisvarianteId)
            ->where($whereProgrammId)
            ->where($whereZaehler);

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows;
    }

}
 
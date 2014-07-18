<?php

/**
 * Ermittelt die Grunddaten der Programmbuchungen eines Warenkorbes
 *
 * + wertet Buchungsnummer ID aus
 * + berücksichtigt Anzeigesprache ID
 *
 * @author Stephan Krauss
 * @date 03.07.2014
 * @file Front_Model_ProgrammbuchungGrunddaten.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_ProgrammbuchungGrunddaten
{
    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $anzeigeSpracheId = null;

    protected $grunddatenProgrammbuchung = array();

    // Konditionen
    protected $condition_status_aktiver_warenkorb = 1;

    /**
     * @param $anzeigeSpracheId
     * @return Front_Model_ProgrammbuchungGrunddaten
     */
    public function setAnzeigeSpracheId($anzeigeSpracheId)
    {
        $this->anzeigeSpracheId = $anzeigeSpracheId;

        return $this;
    }

    /**
     * @param $buchungsNummerId
     * @return Front_Model_ProgrammbuchungGrunddaten
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_ProgrammbuchungGrunddaten
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Grunddaten der gebuchten Programme des aktuellen Warenkorbes
     *
     * + Grunddaten der gebuchten Programme
     * + Programmname und Preisvariante der Programme
     *
     * @return Front_Model_ProgrammbuchungGrunddaten
     */
    public function steuerungErmittlungGrunddatenProgrammbuchung()
    {
        try {
            if (is_null($this->buchungsNummerId))
                throw new nook_Exception('Buchungsnummer ID fehlt');

            if (is_null($this->anzeigeSpracheId))
                throw new nook_Exception('Anzeigesprache ID fehlt');

            if (is_null($this->pimple))
                throw new nook_Exception('Servicecontainer fehlt');

            // Grunddaten der gebuchten Programme
            $this->grunddatenProgrammbuchung = $this->ermittelnGrunddatenProgrammbuchung($this->buchungsNummerId, $this->pimple['tabelleProgrammbuchung']);

            // Programmname der Programme
            $this->ermittelnProgrammnamenGebuchteProgramme = $this->ermittelnProgrammnamenGebuchteProgramme($this->anzeigeSpracheId, $this->grunddatenProgrammbuchung, $this->pimple['tabelleProgrammbeschreibung']);

            // Name Preisvariante der Programme
            $this->ermittelnNamePreisvarianteGebuchteProgramme = $this->ermittelnNamePreisvarianteGebuchteProgramme($this->anzeigeSpracheId, $this->grunddatenProgrammbuchung, $this->pimple['tabellePreiseBeschreibung']);

            return $this;
        } catch (Exception $e) {

        }
    }

    /**
     * Ermittelt den Programmnamen eines gebuchten Programmes
     *
     * @param $anzeigeSpracheId
     * @param $grunddatenProgrammbuchung
     * @param Zend_Db_Table_Abstract $tabelleProgrammbeschreibung
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittelnProgrammnamenGebuchteProgramme($anzeigeSpracheId, $grunddatenProgrammbuchung,Zend_Db_Table_Abstract $tabelleProgrammbeschreibung){
        $cols = array(
            'progname'
        );

        foreach($grunddatenProgrammbuchung as $key => $gebuchtesProgramm){
            $select = $tabelleProgrammbeschreibung->select();
            $select
                ->from($tabelleProgrammbeschreibung, $cols)
                ->where("sprache = ".$anzeigeSpracheId)
                ->where("programmdetail_id = ".$gebuchtesProgramm['programmdetails_id']);

            $query = $select->__toString();

            $rows = $tabelleProgrammbeschreibung->fetchAll($select)->toArray();

            if(count($rows) <> 1)
                throw new nook_Exception('Anzahl Datensaetze Programme falsch');

            $grunddatenProgrammbuchung[$key]['programmname'] = $rows[0]['progname'];
        }

        return $grunddatenProgrammbuchung;
    }

    /**
     * Ermittelt den Namen der Preisvariante
     *
     * @param $anzeigeSpracheId
     * @param $grunddatenProgrammbuchung
     * @param Zend_Db_Table_Abstract $tabellePreiseBeschreibung
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittelnNamePreisvarianteGebuchteProgramme($anzeigeSpracheId, $grunddatenProgrammbuchung,Zend_Db_Table_Abstract $tabellePreiseBeschreibung){
        $cols = array(
            'preisvariante'
        );

        foreach($grunddatenProgrammbuchung as $key => $gebuchtesProgramm){
            $select = $tabellePreiseBeschreibung->select();
            $select
                ->from($tabellePreiseBeschreibung, $cols)
                ->where("sprachen_id = ".$anzeigeSpracheId)
                ->where("preise_id = ".$gebuchtesProgramm['tbl_programme_preisvarianten_id']);

            $query = $select->__toString();

            $rows = $tabellePreiseBeschreibung->fetchAll($select)->toArray();

            if(count($rows) <> 1)
                throw new nook_Exception('Anzahl Datensaetze Preisvariante falsch');

            $grunddatenProgrammbuchung[$key]['preisvariante'] = $rows[0]['preisvariante'];
        }

        return $grunddatenProgrammbuchung;
    }

    /**
     * Ermittelt die gebuchten Programme
     *
     * @param $buchungsNummerId
     * @param Zend_Db_Table_Abstract $tabelleProgrammbuchung
     * @return array
     */
    protected function ermittelnGrunddatenProgrammbuchung($buchungsNummerId, Zend_Db_Table_Abstract $tabelleProgrammbuchung)
    {
        $cols = array(
            'programmdetails_id',
            'tbl_programme_preisvarianten_id',
            'anzahl',
            'datum',
            'zeit'
        );

        $select = $tabelleProgrammbuchung->select();
        $select->from($tabelleProgrammbuchung, $cols)
            ->where("buchungsnummer_id = ".$buchungsNummerId)
            ->where("status = ".$this->condition_status_aktiver_warenkorb);

        $query = $select->__toString();

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return array
     */
    public function getGrunddatenProgrammbuchung()
    {
        return $this->grunddatenProgrammbuchung;
    }


}
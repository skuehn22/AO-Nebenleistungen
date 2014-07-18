<?php
/**
 * Ermittelt die Anzahl der Raten der Hotelbuchungen eines Warenkorbes mit der Session ID , Hotel Buchungen Anzahl Datensätze Datensaetze
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package tool
 */
class nook_ToolAnzahlRatenHotelbuchung
{
    protected $pimple = null;
    protected $sessionid = null;
    protected $buchungsNummerId = null;
    protected $anzahlHotelbuchungen = 0;

    /**
     * erstellt den DIC und ermittelt Session ID
     */
    public function __construct()
    {
        $pimple = new Pimple_Pimple();
        $pimple = $this->servicecontainer($pimple);
        $this->pimple = $pimple;

        $this->sessionid = Zend_Session::getId();
    }

    /**
     * Servicecontainer Pimple
     *
     * @param Pimple_Pimple $pimple
     * @return Pimple_Pimple
     */
    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $pimple['tabelleHotelbuchung'] = function()
        {
            return new Application_Model_DbTable_hotelbuchung();
        };

        return $pimple;
    }


    /**
     * Steuert die Ermittlung der Anzahl der gebuchten Raten eines Warenkorbes
     *
     * @return nook_ToolAnzahlRatenHotelbuchung
     */
    public function steuerungErmittlungAnzahlHotelbuchungen()
    {
        try{

            $buchungsNummerId = nook_ToolBuchungsnummer::findeBuchungsnummer();
            if($buchungsNummerId === false)
                throw new nook_Exception('keine Buchungsnummer vorhanden');

            $this->buchungsNummerId = $buchungsNummerId;

            $anzahlHotelbuchungen = $this->ermittlungAnzahlHotelbuchungen($this->pimple['tabelleHotelbuchung'], $this->buchungsNummerId);
            $this->anzahlHotelbuchungen = $anzahlHotelbuchungen;

            return $this;
        }
        catch(Exception $e){
            throw new $e;
        }
    }

    /**
     * Ermittelt die Anzahl der gebuchten Raten eines Warenkorbes
     *
     * @param Zend_Db_Table_Abstract $tabelleHotelbuchung
     * @param $buchungsnummerId
     * @return mixed
     */
    protected function ermittlungAnzahlHotelbuchungen(Zend_Db_Table_Abstract $tabelleHotelbuchung, $buchungsnummerId)
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where("buchungsnummer_id = ".$buchungsnummerId);

        $query = $select->__toString();

        $rows = $tabelleHotelbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * @return int
     */
    public function getAnzahlHotelbuchungen()
    {
        return $this->anzahlHotelbuchungen;
    }


}
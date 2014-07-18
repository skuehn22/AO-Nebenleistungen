<?php 
 /**
 * Ermittelt den Status 'Offlinekunde' aus der tabelle 'tbl_adresse'
 *
 * @author Stephan.Krauss
 * @date 27.11.2013
 * @file ToolOfflinekunde.php
 * @package tools
 */
class nook_ToolOfflinekunde
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;
    /** @var $tabelleAdressen Application_Model_DbTable_adressen */
    private $tabelleAdressen = null;

    // Tools

    // Konditionen

    // Zustände


    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $statusOfflinekunde = null;

    public function __construct($pimple = false)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    protected function servicecontainer($pimple)
    {
        if(!empty($pimple)){
            $tools = array(
                'tabelleBuchungsnummer',
                'tabelleAdressen'
            );

            foreach($tools as $tool){
                if(!$pimple->offsetExists($tool))
                    throw new nook_Exception('Anfangswert fehlt');
                else
                    $this->$tool = $pimple[$tool];
            }

            $this->pimple = $pimple;
        }
        else{
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
            $this->tabelleAdressen = new Application_Model_DbTable_adressen();
        }

        return;
    }

    /**
     * @param $buchungsNummerId
     * @return nook_ToolOfflinekunde
     */
    public function setBuchungsNummerId($buchungsNummerId)
    {
        $buchungsNummerId = (int) $buchungsNummerId;
        if($buchungsNummerId == 0)
            throw new nook_Exception('Anfangswert falsch');

        $this->buchungsNummerId = $buchungsNummerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusOfflinekunde()
    {
        return $this->statusOfflinekunde;
    }

    /**
     * @return nook_ToolOfflinekunde
     */
    public function steuerungErmittlungStatusOfflinekunde()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception('Anfangswert fehlt');

        // Ermittlung ID des Kunden
        $kundenId = $this->ermittlungKundenId($this->buchungsNummerId);

        // Ermittlung Status Offlinekunde eines Kunden
        $statusOfflinekunde = $this->ermittlungStatusOfflinekunde($kundenId);
        $this->statusOfflinekunde = $statusOfflinekunde;

        return $this;
    }

    /**
     * Ermittelt die Kunden ID mittels Buchungsnummer ID
     *
     * @param $buchungsNummerId
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittlungKundenId($buchungsNummerId)
    {
        $rows = $this->tabelleBuchungsnummer->find($buchungsNummerId)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensätze falsch');

        return $rows[0]['kunden_id'];
    }

    /**
     * Ermittelt den Status 'Offlinekunde' eines benutzers
     *
     * @param $kundenId
     * @return mixed
     * @throws nook_Exception
     */
    protected function ermittlungStatusOfflinekunde($kundenId)
    {
        $rows = $this->tabelleAdressen->find($kundenId)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensätze falsch');

        return $rows[0]['offlinekunde'];
    }
}
 
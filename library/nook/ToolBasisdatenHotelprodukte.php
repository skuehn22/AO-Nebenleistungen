<?php 
 /**
 * Ermittelt die Basisdaten eines Hotelproduktes
 *
 * @author Stephan.Krauss
 * @date 26.11.2013
 * @file ToolBasisdatenHotelprodukte.php
 * @package tools
 */
class nook_ToolBasisdatenHotelprodukte
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleProducts Application_Model_DbTable_products */
    private $tabelleProducts = null;

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;
    protected $produktId = null;
    protected $basisdatenHotelProdukt = array();


    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    protected function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProducts'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Anfangswert fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $produktId
     * @return nook_ToolBasisdatenHotelprodukte
     */
    public function setProduktId($produktId)
    {
        $produktId = (int) $produktId;
        if($produktId == 0)
            throw new nook_Exception('falscher Anfangswert');

        $this->produktId = $produktId;

        return $this;
    }

    /**
     * Ermittelt die Basisdaten eines Hotelproduktes entsprechend der Anzeigesprache
     *
     * @return nook_ToolBasisdatenHotelprodukte
     */
    public function getBasisdatenHotelProdukt()
    {
        return $this->basisdatenHotelProdukt;
    }

    /**
     * Steuerung der Ermittlung der basisdaten eines Hotelproduktes
     *
     * @return $this
     */
    public function steuerungErmittlungBasisdatenEinesHotelprodukt()
    {
        if(is_null($this->produktId))
            throw new nook_Exception('Anfangswert fehlt');

        $basisdatenEinesHotelprodukt = $this->ermittlungBasisdatenEinesHotelprodukt($this->produktId);
        $this->basisdatenHotelProdukt = $basisdatenEinesHotelprodukt;

        return $this;
    }

    /**
     * Ermittelt die Basisdaten eines produktes
     *
     * @param $produktId
     * @return array
     * @throws nook_Exception
     */
    protected function ermittlungBasisdatenEinesHotelprodukt($produktId)
    {
        $rows = $this->tabelleProducts->find($produktId)->toArray();
        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensätze falsch');

        return $rows[0];
    }



}
 
<?php 
 /**
 * Ermittelt die Basisdaten eines Hotels
 *
 * @author Stephan.Krauss
 * @date 21.11.2013
 * @file ToolBasisdatenHotel.php
 * @package tools
 */
class nook_ToolBasisdatenHotel
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleProperties Application_Model_DbTable_properties */
    private $tabelleProperties = null;

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;
    protected $propertyId = null;
    protected $basisdatenHotel = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProperties'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception('Anfangswert falsch');
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $propertyId
     * @return nook_ToolBasisdatenHotel
     */
    public function setPropertyId($propertyId)
    {
        $propertyId = (int) $propertyId;

        $kontrolle = $this->tabelleProperties->kontrolleValue('id', $propertyId);
        if(false === $kontrolle)
            throw new nook_Exception('Anfangswert falsch');

        $this->propertyId = $propertyId;

        return $this;
    }

    /**
     * @return array
     */
    public function getBasisdatenHotel()
    {
        return $this->basisdatenHotel;
    }

    /**
     * Steuert die Ermittlung der Basisdaten eines Hotels
     *
     * @return nook_ToolBasisdatenHotel
     */
    public function steuerungErmittlungBasisdatenHotel()
    {
        if(is_null($this->propertyId))
            throw new nook_Exception('Anfangswert fehlt');

        // Hotelname
        $basisdatenHotel = $this->ermittlungHoteldaten($this->propertyId);
        $this->basisdatenHotel = $basisdatenHotel;

        return $this;
    }

    /**
     * Ermittelt die basisdaten eines Hotels
     *
     * @param $propertyId
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittlungHoteldaten($propertyId)
    {
        $rows = $this->tabelleProperties->find($propertyId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        return $rows[0];
    }
}
 
<?php 
 /**
 * Berechnet den Gruppenrabatt einer Reisegruppe in Abhängigkeit der Rabatte eines Hotels
 *
 * @author Stephan.Krauss
 * @date 25.11.2013
 * @file HotelreservationGruppenrabatt.php
 * @package front
 * @subpackage model
 */
class Front_Model_HotelreservationGruppenrabatt
{
    // Informationen

    // Tabellen / Views

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;

    /** @var $toolHotelrabatt nook_ToolHotelrabatt */
    protected $toolHotelrabatt = null;

    protected $realParams = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Kontrolle und einfügen der benötigten Tools
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'toolHotelrabatt'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Tool nicht vorhanden');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * Übernahme der übergebenen Parameter
     *
     * @param array $realParams
     * @return Front_Model_HotelreservationGruppenrabatt
     */
    public function setRealParams(array $realParams)
    {
        if(!array_key_exists('suchdatum', $realParams))
            throw new nook_Exception('Wert fehlt');
        if(!array_key_exists('hotelId', $realParams))
            throw new nook_Exception('Wert fehlt');
        if(!array_key_exists('uebernachtungen', $realParams))
            throw new nook_Exception('Wert fehlt');

        $this->realParams = $realParams;

        return $this;
    }

    /**
     * Ermittelt den Gruppenrabatt eines Hotels
     *
     * @param array $raten
     * @return array
     */
    public function steuerungErmittlungGruppenrabatt(array $raten)
    {
        $rabattHotelRabattInformation = $this->toolHotelrabatt
            ->setPropertyId($this->realParams['hotelId'])
            ->setStartDatum($this->realParams['suchdatum'])
            ->setAnzahlUebernachtungen($this->realParams['uebernachtungen'])
            ->setRaten($raten)
            ->steuerungBerechnungRabatt()
            ->getHotelRabattInformation();

        return $rabattHotelRabattInformation;
    }
}
 
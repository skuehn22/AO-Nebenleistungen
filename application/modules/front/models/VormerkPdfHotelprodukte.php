<?php 
 /**
 * Ermittelt die Daten der Hotelprodukte für das Pdf einer Vormerkung
 *
 * @author Stephan.Krauss
 * @date 26.11.2013
 * @file VormerkPdfHotelprodukte.php
 * @package front
 * @subpackage model
 */
 
class Front_Model_VormerkPdfHotelprodukte
{
    // Informationen

    // Tabellen / Views

    // Tools
    /** @var $toolBasisdatenHotel nook_ToolBasisdatenHotel */
    private $toolBasisdatenHotel = null;

    // Konditionen
    private $condition_zaehler_warenkorb = 0;
    private $condition_status_warenkorb_vorgemerkt = 2;

    // Zustände

    protected $pimple = null;
    protected $buchungsNummerId = null;
    protected $datenHotelprodukte = array();
    protected $anzeigeSpracheId = null;

    /**
     * Auswertung Servicecontainer und Ermittlung Anzeigesprache
     *
     * @param Pimple_Pimple $pimple
     */
    public function __construct(Pimple_Pimple $pimple)
    {
        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $this->servicecontainer($pimple);
    }

    /**
     * Servicecontainer
     *
     * @param Pimple_Pimple $pimple
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'toolBasisdatenHotel'
        );

        foreach($tools as $tool){
            if(!$pimple->offsetExists($tool))
                throw new nook_Exception('Anfangswert Tool fehlt');
            else
                $this->$tool = $pimple[$tool];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $buchungsNummerId
     * @return Front_Model_VormerkPdfHotelprodukte
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
     * @return array
     */
    public function getDatenHotelprodukte()
    {
        return $this->datenHotelprodukte;
    }

    /**
     * Steuert die Ermittlung der daten der Hotelprodukte einer Vormerkung
     *
     * @return Front_Model_VormerkPdfHotelprodukte
     */
    public function steuerungErmittlungDatenHotelprodukteVormerkung()
    {
        if(is_null($this->buchungsNummerId))
            throw new nook_Exception('fehlender Anfangswert');

        // gebuchte Hotelprodukte
        $gebuchteHotelprodukte = $this->ermittlungGebuchteHotelprodukte($this->buchungsNummerId);

        // weitere Informationenzu den gebuchten Hotelprodukten
        $gebuchteHotelprodukte = $this->basisdatenGebuchteHotelprodukte($gebuchteHotelprodukte);

        // Hotelname und ID Stadt
        $gebuchteHotelprodukte = $this->basisDatenHotelUndStadtname($gebuchteHotelprodukte);

        $this->datenHotelprodukte = $gebuchteHotelprodukte;

        return $this;
    }

    /**
     * Ermittlung die Basisdaten eines Hotel und den Stadtnamen
     *
     * @param $gebuchteHotelprodukte
     * @return array
     */
    private function basisDatenHotelUndStadtname(array $gebuchteHotelprodukte)
    {
        for($i=0; $i < count($gebuchteHotelprodukte); $i++){
            $gebuchteHotelprodukt = $gebuchteHotelprodukte[$i];

            $basisDatenHotel =  $this->toolBasisdatenHotel
                ->setPropertyId($gebuchteHotelprodukt['property_id'])
                ->steuerungErmittlungBasisdatenHotel()
                ->getBasisdatenHotel();

            $gebuchteHotelprodukte[$i]['property_name'] = $basisDatenHotel['property_name'];

            // Stadtname
            $stadtName = nook_ToolStadt::getStadtNameMitStadtId($basisDatenHotel['city_id']);
            $gebuchteHotelprodukte[$i]['stadt'] = $stadtName;
        }

        return $gebuchteHotelprodukte;
    }

    /**
     * Ermittelt die Daten der Hotelprodukte einer Vormerkung
     *
     * @param $buchungsNummerId
     * @return array
     */
    private function ermittlungGebuchteHotelprodukte($buchungsNummerId)
    {
        $toolGebuchteHotelprodukte = new nook_ToolGebuchteHotelprodukte($this->pimple);
        $gebuchteHotelprodukte = $toolGebuchteHotelprodukte
            ->setBuchungsNummerId($buchungsNummerId)
            ->setZaehlerBuchungsnummer($this->condition_zaehler_warenkorb)
            ->setBuchungsStatus($this->condition_status_warenkorb_vorgemerkt)
            ->steuerungErmittlungGebuchteHotelprodukte()
            ->getGebuchteHotelProdukte();

        return $gebuchteHotelprodukte;
    }

    /**
     * Ermittelt Zusatzinformationen zu den gebuchten Hotelprodukten einer Vormerkung
     *
     * @param array $gebuchteHotelprodukte
     * @return array
     */
    private function basisdatenGebuchteHotelprodukte(array $gebuchteHotelprodukte)
    {
        $toolBaisdatenHotelprodukte = new nook_ToolBasisdatenHotelprodukte($this->pimple);

        for($i=0; $i < count($gebuchteHotelprodukte); $i++){
            $gebuchtesHotelprodukt = $gebuchteHotelprodukte[$i];

            $basisDatenHotelprodukt = $toolBaisdatenHotelprodukte
                ->setProduktId($gebuchtesHotelprodukt['products_id'])
                ->steuerungErmittlungBasisdatenEinesHotelprodukt()
                ->getBasisdatenHotelProdukt();

            $gebuchteHotelprodukte[$i]['price'] = $basisDatenHotelprodukt['price'];
            $gebuchteHotelprodukte[$i]['typ'] = $basisDatenHotelprodukt['typ'];
            $gebuchteHotelprodukte[$i]['property_id'] = $basisDatenHotelprodukt['property_id'];

            if($this->anzeigeSpracheId == 1)
                $gebuchteHotelprodukte[$i]['product_name'] = $basisDatenHotelprodukt['product_name'];
            else{
                $gebuchteHotelprodukte[$i]['product_name'] = $basisDatenHotelprodukt['product_name_en'];
            }
        }

        return $gebuchteHotelprodukte;
    }


}
 
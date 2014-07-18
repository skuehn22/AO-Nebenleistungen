<?php 
/**
* Ermittelt die Basisdaten einer Kategorie
*
* + Servicecontainer
* + Steuert die Ermittlung der basisdaten einer Kategorie
* + Ermittelt die Basisdaten einer Kategorie
*
* @date 22.11.2013
* @file ToolBasisdatenKategorie.php
* @package tools
*/
class nook_ToolBasisdatenKategorie
{
    // Informationen

    // Tabellen / Views
    /** @var $tabelleCategories Application_Model_DbTable_categories */
    private $tabelleCategories = null;

    // Tools

    // Konditionen

    // Zustände

    protected $pimple = null;
    protected $categorieId = null;
    protected $propertyId = null;

    protected $datenCategorie = array();

    public function __construct(Pimple_Pimple $pimple)
    {
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
            'tabelleCategories'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception('Anfangswert fehlt');
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $categorieId
     * @return nook_ToolBasisdatenKategorie
     * @throws nook_Exception
     */
    public function setCategorieId($categorieId)
    {
        $categorieId = (int) $categorieId;
        $kontrolle = $this->tabelleCategories->kontrolleValue('id', $categorieId);
        if(false === $kontrolle)
            throw new nook_Exception('Anfangswert falsch');

        $this->categorieId = $categorieId;

        return $this;
    }

    /**
     * @return array
     */
    public function getDatenCategorie()
    {
        return $this->datenCategorie;
    }

    /**
     * Steuert die Ermittlung der basisdaten einer Kategorie
     *
     * @return $this
     */
    public function steuerungErmittlungDatenCategorie()
    {
        if(is_null($this->categorieId))
            throw new nook_Exception('Anfangswert fehlt');

        $basisDatenKategorie = $this->ermittlungBasisdatenKategorie($this->categorieId);
        $this->datenCategorie = $basisDatenKategorie;

        return $this;
    }

    /**
     * Ermittelt die Basisdaten einer Kategorie
     *
     * @param $kategorieId
     * @return mixed
     * @throws nook_Exception
     */
    private function ermittlungBasisdatenKategorie($kategorieId)
    {
        $whereKategorieId = "id = ".$kategorieId;

        $select = $this->tabelleCategories->select();
        $select->where($whereKategorieId);

        // $query = $select->__toString();

        $rows = $this->tabelleCategories->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze falsch');

        return $rows[0];
    }
}
 
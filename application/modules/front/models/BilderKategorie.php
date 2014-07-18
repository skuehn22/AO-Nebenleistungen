<?php 
 /**
 * Ermittelt die Bild ID der Kategorie eines Hotels. ID Bild Kategorie Zimmer
 *
 * @author Stephan.Krauss
 * @date 08.01.2014
 * @file BilderKategorie.php
 * @package front
 * @subpackage model
 */
class Front_Model_BilderKategorie
{
    protected $pimple = null;

    /** @var $tabelleCategories Application_Model_DbTable_categories */
    protected $tabelleCategories = null;
    protected $kategorieId = null;
    protected $kategorieBildId = null;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->serviceontainer($pimple);
        $this->pimple = $pimple;
    }

    /**
     * Übernahme der vorhandenen Objekte
     *
     * @param Pimple_Pimple $pimple
     */
    protected function serviceontainer(Pimple_Pimple $pimple)
    {
        if($pimple->offsetExists('tabelleCategories'))
            $this->tabelleCategories = $pimple['tabelleCategories'];

        return;
    }

    /**
     * @param $kategorieId
     * @return Front_Model_BilderKategorie
     * @throws nook_Exception
     */
    public function setKategorieId($kategorieId)
    {
        $kategorieId = (int) $kategorieId;
        if($kategorieId == 0)
            throw new nook_Exception('keine gueltige ID der Kategorie');

        $this->kategorieId = $kategorieId;

        return $this;
    }

    /**
     * @return int
     */
    public function getKategorieBildId()
    {
        return $this->kategorieBildId;
    }

    /**
     *
     *
     * @return Front_Model_BilderKategorie
     */
    public function steuerungErmittlungKategorieBildId()
    {
        if(is_null($this->kategorieId))
            throw new nook_Exception('Kategorie ID fehlt');

        if(is_null($this->tabelleCategories))
            throw new nook_Exception('Tabelle categories fehlt');

        $kategorieBildId = $this->ermittlungBildIdKategorie($this->kategorieId);

        if(!is_null($kategorieBildId))
            $this->kategorieBildId = $kategorieBildId;

        return $this;
    }

    /**
     * Ermitteld die ID des Bildes einer Kategorie
     *
     * @param $kategorieId
     * @return mixed
     */
    protected function ermittlungBildIdKategorie($kategorieId)
    {
        $row = $this->tabelleCategories->find($kategorieId)->toArray();

        // logger: Logger abschalten !!!
        if(is_null($row[0]['bildId']))
            nook_ToolLog::schreibeLogInTabelle('fehlendes Kategorie Bild: ',$kategorieId,'Front_Models_BilderKategorie','95');

        return $row[0]['bildId'];
    }
}
 
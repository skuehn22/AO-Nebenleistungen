<?php 
 /**
 * Ermittelt Bildbeschreibung und Copyright der Bilder.
 *
 * + Bildbeschreibung und Copyright der Städte
 * + Bildbeschreibung und Copyright der Hotels
 * + Bildbeschreibung und Copyright der Kategorien
 * 
 * @author Stephan.Krauss
 * @date 06.01.2014
 * @file Bildinformation.php
 * @package front
 * @subpackage model
 */
class Front_Model_Bildinformation
{
    protected $bildname = null;
    protected $copyright = null;
    protected $bildText = null;

    protected $bildId = null;
    protected $bildTypId = null;

    protected $tabelleBilder = null;

    public function __construct()
    {

    }

    /**
     * @param int $bildId
     * @return Front_Model_Bildinformation
     * @throws nook_Exception
     */
    public function setBildId($bildId)
    {
        $bildId = (int) $bildId;
        if($bildId == 0)
            throw new nook_Exception('Bild ID falsch');

        $this->bildId = $bildId;

        return $this;
    }

    /**
     * @param int $bildTyp
     * @return Front_Model_Bildinformation
     * @throws nook_Exception
     */
    public function setBildTyp($bildTyp)
    {
        $bildTyp = (int) $bildTyp;
        if($bildTyp == 0)
            throw new nook_Exception('Bildtyp falsch');

        $this->bildTypId = $bildTyp;

        return $this;
    }

    /**
     * @return string
     */
    public function getBildname()
    {
        return $this->bildname;
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * @return string
     */
    public function getBildText()
    {
        return $this->bildText;
    }

    /**
     * @param Application_Model_DbTable_bilder $tabelleBilder
     * @return Front_Model_Bildinformation
     */
    public function setTabelleBilder(Application_Model_DbTable_bilder $tabelleBilder)
    {
        $this->tabelleBilder = $tabelleBilder;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Bildinformation
     *
     * @return Front_Model_Bildinformation
     */
    public function steuerungErmittlungBildinformation()
    {
        if(is_null($this->bildId))
            throw new nook_Exception('Bild ID fehlt');

        if(is_null($this->bildTypId))
            throw new nook_Exception('Bild Typ ID fehlt');

        if(is_null($this->tabelleBilder))
            throw new nook_Exception('Tabelle Bilder fehlt');

        $bildinformationen = $this->ermittelnBildbeschreibung($this->bildId, $this->bildTypId, $this->tabelleBilder);

        $this->bildname = $bildinformationen['bildname'];
        $this->copyright = $bildinformationen['copyright'];
        $this->bildText = $bildinformationen['text'];

        return $this;
    }

    /**
     * @param $boldId
     * @param $bildTypid
     */
    protected function ermittelnBildbeschreibung($bildId, $bildTypId,Application_Model_DbTable_bilder $tabelleBilder)
    {
        $cols = array(
            'bildname',
            'copyright',
            'text'
        );

        $whereFremdschluessel = "fremdschluessel = ".$bildId;
        $whereBildTyp = "bildtyp = ".$bildTypId;

        $select = $tabelleBilder->select();
        $select
            ->from($tabelleBilder, $cols)
            ->where($whereFremdschluessel)
            ->where($whereBildTyp);

        $rows = $tabelleBilder->fetchAll($select)->toArray();

        if(count($rows) > 1)
            throw new nook_Exception('Anzahl Bildinformationen falsch');

        return $rows[0];
    }










}
 
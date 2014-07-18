<?php

/**
 * Ermittelt die Bettensteuer einer Stadt in Abhängigkeit der Sprache
 *
 * + ermittelt, hat die Stadt eine Bettensteuer
 * + gibt Texte der Bettensteuer der stadt zurück
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package front
 * @subpackage model
 */

class Front_Model_BettensteuerStadt
{
    /** @var $tabelleAoCityBettensteuer Zend_Db_Table_Abstract  */
    protected $tabelleAoCityBettensteuer = null;

    protected $cityId = null;
    protected $spracheId = null;

    protected $titleBettensteuer = null;
    protected $kurztextBettensteuer = null;
    protected $textBettensteuer = null;

    protected $flagHasBettensteuer = false;

    /**
     * @param $spracheId
     * @return Front_Model_BettensteuerStadt
     */
    public function setSpracheId($spracheId)
    {
        $this->spracheId = $spracheId;

        return $this;
    }

    /**
     * @param $cityId
     * @return Front_Model_BettensteuerStadt
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @param $tabelleAoCityBettensteuer
     * @return Front_Model_BettensteuerStadt
     */
    public function setTabelleAoCityBettensteuer($tabelleAoCityBettensteuer)
    {
        $this->tabelleAoCityBettensteuer = $tabelleAoCityBettensteuer;

        return $this;
    }

    /**
     * Steuert die Ermittlung der Bettensteuer einer Stadt
     *
     * @return Front_Model_BettensteuerStadt
     * @throws Exception
     */
    public function steuerungErmittlungBettensteuerStadt()
    {
        try{
            if(is_null($this->cityId))
                throw new nook_Exception('City ID fehlt');

            if(is_null($this->spracheId))
                throw new nook_Exception('Sprache ID fehlt');

            $this->titleBettensteuer = null;
            $this->kurztextBettensteuer = null;
            $this->textBettensteuer = null;

            $rows = $this->ermittelnDatenBettensteuer($this->cityId, $this->spracheId);

            if(count($rows) == 1){
                $this->flagHasBettensteuer = true;
                $this->splittenDatenBettensteuer($rows[0]);
            }
            elseif(count($rows) > 1)
                throw new nook_Exception('Anzahl Datensaetze Bettensteuer falsch');

            return $this;
        }
        catch(Exception $e){
            throw $e;
        }
    }

    /**
     * Aufsplitten der Daten der Bettensteuer einer Stadt
     *
     * @param array $datenBettensteuer
     */
    protected function splittenDatenBettensteuer(array $datenBettensteuer)
    {
        $this->titleBettensteuer = $datenBettensteuer['title'];
        $this->kurztextBettensteuer = $datenBettensteuer['kurztextBettensteuer'];
        $this->textBettensteuer = $datenBettensteuer['bettensteuerText'];

        return;
    }

    /**
     * Ermittelt die Bettensteuer einer Stadt in Abhängigkeit der Sprache
     *
     * @param $cityId
     * @param $spracheId
     * @return array
     */
    protected function ermittelnDatenBettensteuer($cityId, $spracheId)
    {
        $cols = array(
            "title",
            "kurztextBettensteuer",
            "bettensteuerText"
        );

        $whereCityId = "tblAoCity_id = ".$cityId;
        $whereSpracheId = "tblSprache_id = ".$spracheId;

        $select = $this->tabelleAoCityBettensteuer->select();
        $select
            ->from($this->tabelleAoCityBettensteuer, $cols)
            ->where($whereCityId)
            ->where($whereSpracheId);

        $query = $select->__toString();

        $rows = $this->tabelleAoCityBettensteuer->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return string
     */
    public function getTitleBettensteuer()
    {
        return $this->titleBettensteuer;
    }

    /**
     * @return null
     */
    public function getTextBettensteuer()
    {
        return $this->textBettensteuer;
    }

    /**
     * @return null
     */
    public function getKurztextBettensteuer()
    {
        return $this->kurztextBettensteuer;
    }

    /**
     * @return bool
     */
    public function hasBettensteuer()
    {
        return $this->flagHasBettensteuer;
    }
}

/**************/

//include_once('../../../../vendor/autoload.php');
//
//$tabelleAoCityBettensteuer = new Application_Model_DbTable_aoCityBettensteuer();
//
//$frontModelBettensteuerStadt = new Front_Model_BettensteuerStadt();
//
//$flagBettensteuer = $frontModelBettensteuerStadt
//    ->setTabelleAoCityBettensteuer($tabelleAoCityBettensteuer)
//    ->setCityId(1)
//    ->setSpracheId(1)
//    ->steuerungErmittlungBettensteuerStadt()
//    ->hasBettensteuer();
//
//$title = $frontModelBettensteuerStadt->getTitleBettensteuer();
//$kurztextBettensteuer = $frontModelBettensteuerStadt->getKurztextBettensteuer();
//$textBettensteuer = $frontModelBettensteuerStadt->getTextBettensteuer();
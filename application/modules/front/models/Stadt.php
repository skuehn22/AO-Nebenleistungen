<?php
class Front_Model_Stadt extends nook_Model_model{
	// public $error_plz_to_short = 590;

    /**
     * @var Zend_Db_Adapter
     */
    private $_db_front;
    private $_cityId;
    private $_sprache;

	public function __construct(){
		$this->_db_front = Zend_Registry::get('front');
        $this->_sprache = nook_ToolSprache::ermittelnKennzifferSprache();
	}

    /**
     * City ID
     *
     * @param $cityId
     * @return Front_Model_Stadt
     */
    public function     setCityId($cityId){

        $cityId = (int) $cityId;
        if($cityId == 0)
            throw new nook_Exception('City ID ist nicht Ganzzahl');

        $this->_cityId = $cityId;

        return $this;
    }

    public function getCityName(){
        $sql = "select AO_City from tbl_ao_city where AO_City_ID = ".$this->_cityId;
        $cityname = $this->_db_front->fetchOne($sql);

        return $cityname;
    }

    public function getCityTextKurz(){
        $cityTextKurz = '';

        $sql = "
        SELECT
            `kurzbeschreibung`
        FROM
            `tbl_stadtbeschreibung`
        WHERE (`city_id` = ". $this->_cityId ."
            AND `sprache_id` = '". $this->_sprache ."')";

        $cityTextKurz .= $this->_db_front->fetchOne($sql);

        return $cityTextKurz;
    }

    /**
     * Allgemeine Beschreibung einer Stadt
     *
     * @return string
     */
    public function getCityTextLang()
    {
        $cols = array(
            "stadtbeschreibung"
        );

        $whereSpracheId = "sprache_id = ".$this->_sprache;
        $whereCityId = "city_id = ".$this->_cityId;

        /** @var $tabelleStadtbeschreibung Application_Model_DbTable_stadtbeschreibung */
        $tabelleStadtbeschreibung = new Application_Model_DbTable_stadtbeschreibung();
        $select = $tabelleStadtbeschreibung->select();
        $select
            ->from($tabelleStadtbeschreibung, $cols)
            ->where($whereSpracheId)
            ->where($whereCityId);

        $rows = $tabelleStadtbeschreibung->fetchAll($select)->toArray();

        return $rows[0]['stadtbeschreibung'];
    }

    /**
     * Holt die Zusatzinformation aus 'tbl_stadtbeschreibung'
     *
     * @return string
     */
    public function getCityTextZusatzinformation()
    {
        $cityTextZusatzinformation = '';

        $sql = "
        SELECT
            `zusatzinformation`
        FROM
            `tbl_stadtbeschreibung`
        WHERE (`city_id` = ". $this->_cityId ."
            AND `sprache_id` = '". $this->_sprache ."')";

        $cityTextZusatzinformation .= $this->_db_front->fetchOne($sql);

        return $cityTextZusatzinformation;
    }
}
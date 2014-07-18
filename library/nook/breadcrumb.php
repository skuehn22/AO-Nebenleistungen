<?php
 
class breadcrumb {
    protected $_modul = null;
    protected $_controller = null;
    protected $_action = null;

    protected $_cityId;
    protected $_cityName;

    private $_error_city_id_not_integer = 430;

    private $_condition_no_module_name = 'kein Modulname vorhande';
    private $_condotion_no_controller_name = 'kein Controller Name vorhanden';
    private $_condition_no_actin_name = 'kein Aktionsname vorhanden';

    /**
     * Ermitteln der Grundwerte
     *
     * @param array $__params
     */
    public function __construct(array $__params){
        if(!array_key_exists('module', $__params))
            $this->_modul = $this->_condition_no_module_name;
        else
            $this->_modul = $__params['module'];

        if(!array_key_exists('action', $__params))
            $this->_action = $this->_condition_no_actin_name;
        else
            $this->_action = $__params['action'];

        if(!array_key_exists('controller', $__params))
            $this->_controller = $this->_condotion_no_controller_name;
        else
            $this->_controller = $__params['controller'];
        
        $this->_cityId = $__params['city'];
    }

    /**
     * Gibt den Namen der Stadt zurück
     *
     * @return string
     */
    public function getCityName(){
        $bereich = $this->_findePortalBereich();

        return $this->_findCityNameById();
    }

    /**
     * Findet den Bereich der App
     *
     * @return
     */
    private function _findePortalBereich(){
        $portalbereich = new Zend_Session_Namespace('portalbereich');
        if($this->_controller == 'programmstart')
            $portalbereich->bereich = "programme";
        elseif($this->_controller == 'hotelsearch')
            $portalbereich->bereich = 'hotels';

        return $portalbereich->bereich;
    }

    /**
     * Findet den Namen der Action
     *
     * @return string
     */
    public function getActionName(){
        return $this->_action;
    }

    /**
     * Findet einen Stadtnamen mit der ID
     *
     * @throws nook_Exception
     * @return string
     */
    protected function _findCityNameById(){
        $validator = new Zend_Validate_Int();
		if(!$validator->isValid($this->_cityId))
			throw new nook_Exception($this->_error_city_id_not_integer);

		$db = Zend_Registry::get('front');
		$sql = "select AO_City, AO_City_ID from tbl_ao_city where AO_City_ID = '".$this->_cityId."'";
		$city = $db->fetchRow($sql);

        $portalbereich = new Zend_Session_Namespace('portalbereich');
        $portalbereichItems = $portalbereich->getIterator();

        if($portalbereichItems['bereich'] == 'programme')
            return "<a href='/front/programmstart/index/city/".$city['AO_City_ID']."'>".ucfirst($portalbereichItems['bereich'])." : ".$city['AO_City']."</a>";
        elseif($portalbereichItems['bereich'] == 'hotels')
            return "<a href='/front/hotelsearch/index/city/".$city['AO_City_ID']."'>".ucfirst($portalbereichItems['bereich'])." : ".$city['AO_City']."</a>";
        else
            return "";
    }
}

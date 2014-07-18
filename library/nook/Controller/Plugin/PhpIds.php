<?php
class Plugin_PhpIds extends Zend_Controller_Plugin_Abstract {
    private $config = null;
    private $_idsError = 9999;
 
    public function __construct(Zend_Config_Ini $config){
        $this->config = $config;
    }

    /**
     * Ignorliste
     * Parameter die nicht kontrolliert werden
     *
     * @param $__params
     * @return array
     */
    private function _ignoreList($__params){
    	$kontrolliereParameter = array();

        // ignoriere den Html - Editor
        foreach($__params as $key => $value){
            if($key != 'editor' and $key != 'txt' and $key != "fieldZusatzinformation")
                $kontrolliereParameter[$key] = $value;
        }

    	return $kontrolliereParameter;
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request){
		$params = $request->getParams();

        try{
            // Ignorliste
            $params = $this->_ignoreList($params);

            $init = IDS_Init::init();
            $init->config = $this->config->toArray();
            // run the ids monitor
            $ids = new IDS_Monitor($params, $init);
            $result = $ids->run();

            if (!$result->isEmpty()) {

                // ermittelt Impact - Wert
                $impact = $result->getImpact();

                // Kontrolle ob PhpIds den Impact - Wert überschreitet
                if($impact >= Zend_Registry::get('static')->phpids->maximpact) {
                    throw new nook_Exception($this->_idsError);
                }
            }
        }
        catch(Exception $e){
            $errorNumber = nook_ExceptionRegistration::registerException($e, 1, $params);
            Zend_Session::regenerateId();

            $request->setModuleKey('front');
            $request->setControllerKey('login');
            $request->setActionKey('login');
            $request->setParam('error',$errorNumber);
        }

    }


}

?>
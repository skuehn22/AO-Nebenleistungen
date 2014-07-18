<?php

/**
 * Wenn im Request kein Module angegeben ist, dann werden in den Request die Standardwerte gesetzt.
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package plugin
 */

class Plugin_Systemstart extends Zend_Controller_Plugin_Abstract
{
    protected $startModule = 'front';
    protected $startController = 'login';
    protected $startAction = 'index';

    /**
     * Wenn kein Modul im Request angegeben wird.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void|Zend_Controller_Request_Abstract
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        // Parameter
        $params = $request->getParams();
        $module = $params['module'];

        if( (empty($module)) or ($module == 'default') ){
            $request->setModuleName($this->startModule);
            $request->setControllerName($this->startController);
            $request->setActionName($this->startAction);
        }
    }
}

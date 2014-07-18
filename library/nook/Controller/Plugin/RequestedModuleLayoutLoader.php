<?php
class Plugin_RequestedModuleLayoutLoader extends Zend_Controller_Plugin_Abstract {
	
	 public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request){

         // bestimmen Modulname
        $activeModuleName = $request->getModuleName();
	 	$activeModuleName = strtolower($activeModuleName);

        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout($activeModuleName);
    }
}
?>

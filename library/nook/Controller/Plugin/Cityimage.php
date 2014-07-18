<?php

class Plugin_Cityimage extends Zend_Controller_Plugin_Abstract {

	public function postDispatch(Zend_Controller_Request_Abstract $request){
        $params = $request->getParams();
        Zend_Registry::set('cityImage', '');

        if(array_key_exists('city', $params)){
            $cityImage = ABSOLUTE_PATH.'/images/city/'.$params['city'].'.jpg';

            if(file_exists($cityImage)){
                $cityImageBlock = '<div class="span-24"><img src="/images/city/'.$params['city'].'.jpg" id="cityImage"></div>';
                Zend_Registry::set('cityImage', $cityImageBlock);
            }
        }
	}
}
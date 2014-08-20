<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap{
	
	protected function _initConfig() {
		Zend_Registry::set('config', new Zend_Config($this->getOptions()));
	}

	protected function _initMvc(){
		$layout = Zend_Layout::startMvc(); // MVC
	}

	protected function _initStatics() {
		$statics = new Zend_Config_Ini('../application/configs/static.ini', null, true);
		
		Zend_Registry::set('static', $statics);

		return $this;
	}
	
	protected function _initDatabases() {

        try{
            $this->bootstrap('multidb');
            $resource = $this->getPluginResource('multidb');
            $databases = Zend_Registry::get('config')->resources->multidb;

            foreach ($databases as $name => $adapter) {
                $db_adapter = $resource->getDb($name);
                Zend_Registry::set($name, $db_adapter);
            }
        }
        catch (Exception $e){
            throw $e;
        }


	}

    protected function _initErrorMessage(){

    	$front = Zend_Controller_Front::getInstance();
    	$front->throwExceptions();

    	return;
    }

    protected function _initModuleAutoload(){

        try{
            $autoloader = new Zend_Application_Module_Autoloader(
                array(
                    'namespace' => 'Front_',
                    'basePath'  => dirname(__FILE__) . '/modules/front'
                )
            );

            $autoloader_admin = new Zend_Application_Module_Autoloader(
                array(
                    'namespace' => 'Admin_',
                    'basePath'  => dirname(__FILE__) . '/modules/admin'
                )
            );

            return;
        }
        catch (Exception $e){
            $test = 123;
        }

    }

    // Logger für Firebug
    protected function _initLog() {
        try{
            if(Zend_Registry::get('static')->firebug->firebug == 2){

                $cols = array(
                    'datum' => 'timestamp',
                    'level' => 'priority',
                    'information' => 'message',
                    'label' => 'priorityName'
                );

                $log = new Zend_Log();
                $log->addWriter(new Zend_Log_Writer_Db(Zend_Registry::get('front'),'tbl_log',$cols));

                Zend_Registry::set('log', $log);
            }

            return;
        }
        catch (Exception $e){
       }

    }

    protected function _initContainer(){
        try{
            $container = new Pimple_Pimple();

            return $container;
        }
        catch(Exception $e){
        }
    }
    
    protected function _initPlugins(){
        try{
            $front = Zend_Controller_Front::getInstance();

            $loader = new Zend_Loader_PluginLoader();
            $loader->addPrefixPath('Plugin', 'nook/Controller/Plugin');

            // Session Verwaltung / umschreiben Session für Vormerkung
            $session = $loader->load('Session');
            $front->registerPlugin(new $session);

            // Authentifikation
            $authPlugin = $loader->load('Auth');
            $front->registerPlugin(new $authPlugin);

            // PhpIDS
            // $phpidsPlugin = $loader->load('PhpIds');
            // $front->registerPlugin(new $phpidsPlugin(new Zend_Config_Ini('../application/configs/ids.ini', null, true)));

            //eigene Abfragen

            // Sprachen
            $language = $loader->load('Language');
            $front->registerPlugin(new $language);

            // Layout
            $layoutPlugin = $loader->load('RequestedModuleLayoutLoader');
            $front->registerPlugin(new $layoutPlugin);

            // Warenkorb erneuern
            $warenkorbRefresh = $loader->load('WarenkorbRefresh');
            $front->registerPlugin(new $warenkorbRefresh);

            // berechnen Summe der aktiven Artikel eines Warenkorbes
            $shoppingCart = $loader->load('ShoppingCart');
            $front->registerPlugin(new $shoppingCart);

            // darstellen des Debug Modus
            $debugModus = $loader->load('DebugModus');
            $front->registerPlugin(new $debugModus);

            // Warenkorb / Vormerkungen
            // Teile der Navigation nach Login
             $navigation = $loader->load('Navigation');
             $front->registerPlugin(new $navigation);

            // speichert User ID in den
            // Datenbanken
            //$trigger = $loader->load('Triggerauth');
            //$front->registerPlugin(new $trigger);

            // Logout Button
            $logout = $loader->load('Logout');
            $front->registerPlugin(new $logout);

            // Stadtbilder
            $cityImage = $loader->load('Cityimage');
            $front->registerPlugin(new $cityImage);

            // Fingerprint der Session
            $fingerprint = $loader->load('Fingerprint');
            $front->registerPlugin(new $fingerprint);

            // Redirect zu 'http://www.herden-studienreisen.de'
            $redirect = $loader->load('redirect');
            $front->registerPlugin(new $redirect);

            // erkennen Subdomain
            $subdomain = $loader->load('Subdomain');
            $front->registerPlugin(new $subdomain);

            // ergänzen Controller und Action
            $systemstart = $loader->load('systemstart');
            $front->registerPlugin(new $systemstart);

            //$UrlRewrite = $loader->load('UrlRewrite');
            //$front->registerPlugin(new $UrlRewrite);
        }
        catch(Exception $e){
            throw $e;
        }
	}
}
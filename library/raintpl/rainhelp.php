<?php
/**
 * Initialisiert dei Templat Engine.
 * Liefert Instance.
 * Verwendet Singleton
 *
 */
class raintpl_rainhelp{

    protected static $_instanceRaintpl = null;

	public static function getRainTpl(){
        if(self::$_instanceRaintpl !== null)
            return self::$_instanceRaintpl;

		include_once('raintpl/rain.tpl.class.php');

        $subdomain = Zend_Registry::get('subdomain');
        $domain = "http://$_SERVER[HTTP_HOST]";


        $rainTplObj = new raintpl();

        if($subdomain == 'austria'){
            $rainTplObj::$tpl_dir = "tpl_austria/";
        }
        else{
            if ($domain == 'http://www.wa.dev'){
                $rainTplObj::$tpl_dir = "tpl_wa/";
            }else{
              $rainTplObj::$tpl_dir = "tpl/";
            }
        }

        self::$_instanceRaintpl = $rainTplObj;

		return self::$_instanceRaintpl;
	}

    private function __construct(){}

    private function __clone(){}
}
?>

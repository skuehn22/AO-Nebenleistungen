<?php
/**
 * Lenkt den Aufruf auf 'http://www.herden-studienreisen.de' um. Erkennung der Verzeichnisse
 *
 * + für 'develop' und 'stable'
 *
 * @author Stephan Krauss
 * @date 15.04.2014
 * @file Redirect.php
 * @project HOB
 * @package plugin
 */
class Plugin_Redirect extends Zend_Controller_Plugin_Abstract
{

    /**
     * Erkennen und registrieren der Subdomain
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
    {
        $params = $request->getParams();

        $redirect = array(
            'st',
            'va',
            'ikw',
            'auktion',
            'ftpfrei',
            'bjl',
            'byp',
            'jkt',
            'media',
            'redaktion'
        );

        if( (in_array($params['controller'], $redirect) )){

            if($params['controller'] == 'st'){
                header("Location: http://www.herden-studienreisen.de/index.php");
            }
            elseif( ($params['controller'] == 'bjl') or ($params['controller'] == 'byp') or ($params['controller'] == 'media') ){
                switch($params['controller']){
                    case 'bjl':
                        header("Location: http://www.herden-studienreisen.de/berlin-fuer-junge-leute-c8.html");
                        break;
                    case 'byp':
                        header("Location: http://www.herden-studienreisen.de/berlin-for-young-people-c16.html");
                        break;
                    case 'media':
                        header("Location: http://www.herden-studienreisen.de/mediadaten-c142.html");
                        break;
                }
            }
            else{
                header("Location: http://www.herden-studienreisen.de/".$params['controller']."/");
            }

            die();
        }

        return;

    }
}
<?php
/**
 * Plugin Navigation
 *
 * + Kontrolliert ob der Kunde angemeldet ist
 *
 * @author Stephan.Krauss
 * @date 14.36.2013
 * @file Navigation.php
 * @package plugins
 */
class Plugin_Navigation extends Zend_Controller_Plugin_Abstract {

    // Fehler
    private $_error = 1120;

    // Tabellen / Views

    // Konditionen
	public $condition_user_is_admin = 5;

    protected $_realParams = array();
	
	public function postDispatch(Zend_Controller_Request_Abstract $request){
    	$front = Zend_Controller_Front::getInstance();

		$db = Zend_Registry::get('front');
		$sql = "select * from tbl_ao_city where aktiv = '2' order by AO_City asc";
		$cities = $db->fetchAll($sql);

        // $cityNavigation = "<ul>";
        $cityNavigation = '';

        $uebernachtungen = translate('Übernachtungen');
        $programme = translate('Programme');

        foreach($cities as $key => $cityItems){
            $cityItems['AO_City'] = translate($cityItems['AO_City']);
            $cityNavigation .= "<li><a href='/front/stadt/index/city/".$cityItems['AO_City_ID']."'> ".$cityItems['AO_City']." </a></li>";
        }
        // $cityNavigation .= "</ul>";

        Zend_Registry::set('nav', $cityNavigation);
		Zend_Registry::set('service', '');
		Zend_Registry::set('admin', '');
        
		$control = Zend_Session::getId();
		if(empty($control))
			return;
		
		$serviceNavigation = array();

		$Auth = new Zend_Session_Namespace('Auth');
        $status = $Auth->role_id;
        $userId = $Auth->userId;

		if((!empty($status)) and ($status > 1) and (!empty($userId))){
			$serviceItems = Zend_Registry::get('static')->servicebereich;
			
			$service = $serviceItems->toArray();
			foreach($service['service'] as $name => $path){
				$serviceNavigation[translate(ucfirst($name))] = $path;
			}
			
			Zend_Registry::set('service', $serviceNavigation);
		}
		
		$auth = new Zend_Session_Namespace('Auth');
		$role_id = $auth->role_id;


		if($role_id >= $this->condition_user_is_admin){
			$adminNavigation = array();
			$adminNavigation[0]['label'] = translate("Startseite Administration");
			$adminNavigation[0]['path'] = "/admin/whiteboard/index/";
			
			Zend_Registry::set('admin', $adminNavigation);
		}

        // Vormerkungen
        if($role_id > 1)
            $this->_checkVormerkungen();
        else
            Zend_Registry::set('vormerkungen', ' ');
		
		return;
	}

    /**
     * Kontrolliert ob der Kunde angemeldet ist
     * Vormerkungen von Warenkörben
     * hat
     */
    private function _checkVormerkungen(){
        try{
            // User ist nicht angemeldet
            $vormerkungenStatus = false;

            // Ist der User angemeldet
            $authSession = new Zend_Session_Namespace('Auth');
            $params = (array) $authSession->getIterator();

            $this->realParams = $params;

            if(is_array($params) and array_key_exists('userId', $params) and $params['userId'] !== null){
                $toolAnzahlVormerkungen = new nook_ToolAnzahlVormerkungen();
                $anzahlVormerkungen = $toolAnzahlVormerkungen->setKundenId($params['userId'])->steuerungErmittlungAnzahlVormerkungen()->getAnzahlvormerkungen();

                Zend_Registry::set('vormerkungen', $anzahlVormerkungen);
            }

            if(!$anzahlVormerkungen)
                Zend_Registry::set('vormerkungen', 0);

            return;
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }


    }
}
<?php

class Plugin_DebugModus extends Zend_Controller_Plugin_Abstract {

	public function postDispatch(Zend_Controller_Request_Abstract $request){
    	$modus = Zend_Registry::get('static')->debugModus->modus;

        $auth = new Zend_Session_Namespace('Auth');
        $authDaten = $auth->getIterator();

    	if($modus == 1){
    		Zend_Registry::set('debugModus', ''); 
        }
        // darstellen Debug - Modus
    	else{
    		$params = $request->getParams();
            $sessionId = session_id();

            $debugInfo = 'Debugmodus: '.$params['module']." > ".$params['controller']." > ".$params['action']."<br>";
            $debugInfo .= "Rolle: ".$auth->role_id."<br>";
            $debugInfo .= "<a href='javascript:gridAn();'>Blueprint An</a><br>";
            $debugInfo .= "<a href='javascript:gridAus();'>Blueprint Aus</a><br>";
            $debugInfo .= "InfoSession-ID:".$sessionId."<br>";

            $werteBuchungstabelle = $this->ermittelnWerteTabelleBuchungsnummer($sessionId);

            $debugInfo .= "Buchungsnummer ID: ".$werteBuchungstabelle['id']."<br>";
            $debugInfo .= "HOB - Nummer: ".$werteBuchungstabelle['hobNummer']."<br>";

    		Zend_Registry::set('debugModus', $debugInfo);
    	}

    	return;
	}

    protected function ermittelnWerteTabelleBuchungsnummer($sessionId)
    {
        $cols = array(
            'id',
            'hobNummer'
        );

        $tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols)->where("session_id = '".$sessionId."'");
        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if( (is_array($rows)) and (count($rows) == 2))
            return $rows[0];
        else
            return array(
                'id' => 0,
                'hobNummer' => 0
            );
    }
}
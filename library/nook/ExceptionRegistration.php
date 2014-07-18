<?php
class nook_ExceptionRegistration{
	
	public static function registerException($__exception, $__verhalten = 1, $__variables = false, $requestUrl = false)
    {
        // Fehlerbehandlung Typen
        //  $condition_blockiere = 1;
        //  $condition_reagiere = 2;
        //  $condition_informiere = 3;

		// KundenId
		$__exception->kundenId = self::getKundenId();

        // Registrierung der Exception in 'tbl_exception'
        $error = self::buildAndRegisterErrorInfos($__exception, $__verhalten, $__variables, $requestUrl);

        // versende Error-Mail
        self::errorMail($error);

		return $error['idFehlermeldung'];
	}

    /**
     * Registrierung des Fehlers.
     *
     * Registrierung des Fehlers in der Datenbank.
     * Aufbereitung des Fehlers für SMS.
     *
     * @param $exception
     * @param $verhalten
     * @param bool $variables
     * @return array $fehlermeldung
     */
    public static function buildAndRegisterErrorInfos($exception, $verhalten, $variablen = false, $requestUrl = false)
    {
        $sessionInhalt = self::getSessionInhalt();

        $sessionAuth = new Zend_Session_Namespace('Auth');
        $sessionAuthArray = $sessionAuth->getIterator();

		$error = array(
			'file' =>  $exception->getFile(),
			'line' => $exception->getLine(),
			'code' => $exception->getCode(),
			'blockCode' => $exception->getMessage(),
			'reaction' => $verhalten,
			'trace' => $exception->getTraceAsString(),
			'date' => date("Y-m-d H:i:s"),
			'session' => Zend_Session::getId(),
			'kundenId' => $exception->kundenId,
            'buchungsnummer' => nook_ToolBuchungsnummer::findeBuchungsnummer(),
            'sessioninhalt' => $sessionInhalt,
            'rolleId' => $sessionAuthArray['role_id']
		);

        // registrieren des Aufruf
        if(method_exists($exception,'getQuery'))
            $error['query'] = $exception->getQuery();
        else
            $error['query'] = ' ';

        // reale Parameter
        if(!empty($variablen)){
            // Codieren Variablen
            if(!empty($variablen))
                $variablen = json_encode($variablen);

            $error['variables'] = $variablen;
        }

        // aufrufende Position
        if(!empty($requestUrl))
            $error['requestUrl'] = $requestUrl;

		$db = Zend_Registry::get('front');
		$db->insert('tbl_exception', $error);
        $error['idFehlermeldung'] = $db->lastInsertId();

		return $error;
	}

    /**
     * Ermittelt den Inhalt eines Session
     *
     * @return mixed
     */
    public static function getSessionInhalt()
    {
        $sessionId = Zend_Session::getId();

        $cols = array(
            'sess_data'
        );

        $whereSessId = "sess_id = '".$sessionId."'";

        $tabelleSession = new Application_Model_DbTable_sessions();
        $select = $tabelleSession->select();
        $select->from($tabelleSession, $cols)->where($whereSessId);

        $rows = $tabelleSession->fetchAll($select)->toArray();

        return $rows[0]['sess_data'];
    }

    public static function errorMail(array $error){

        // versenden der Mail
        $modelErrorMail = new nook_ToolErrorMail();
        $modelErrorMail->sendeMail($error);

        return;
    }



	public static function getKundenId(){
        $sessionId = false;
        $kundenId = 0;

        // ermitteln SessionId
		$sessionId = Zend_Session::getId();

		if(!empty($sessionId)){

            $auth = new Zend_Session_Namespace('Auth');
            $user = (array) $auth->getIterator();

            if(!empty($user['userId'])){
                $kundenId = (int) $user['userId'];

                if(empty($kundenId))
                    $kundenId = 0;
            }
		}
		
		return $kundenId;
	}

} 

?>

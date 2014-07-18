<?php
class Front_Model_Personaldata extends nook_Model_model{

    // Fehler
	public $error_no_kunden_id = 110;
	public $error_no_status = 111;
	public $error_wrong_status = 112;
    public $error_formulardaten_unvollstaendig = 113;

    // Konditionen
	public $condition_role_optionator = 2;
    public $condition_role_guest = 1;
    public $condition_user_unbekannt = 'unbekannt';
    public $condition_kennung_deutschland = 52;
    public $condition_aktive_anzeige = 2;

    // Tabelle / Views

    private $_db_front = null;
    public $userId = 0;

    /**
     * Herstellen der Datenbankverbindung
     * Feststellen der User ID
     */
    public function __construct(){
        // Datenbankverbindung
        $this->_db_front = Zend_Registry::get('front');

        // bestimmen der Kunden ID
        $auth = new Zend_Session_Namespace('Auth');
        $userDaten = $auth->getIterator();
        if(!empty($userDaten['userId']))
            $this->userId = $userDaten['userId'];

    }

    /**
     * Erstellt die User ID für die angezeigte
     * Information.
     *
     * @return int|string
     */
    public function getUserIdInformation(){
        if($this->userId === 0){
            $userId = translate($this->condition_user_unbekannt);
        }
        else
            $userId = $this->userId;

        return $userId;
    }

    /**
     * Überprüft den Status des Kunden
     *
     * @return mixed
     * @throws nook_Exception
     */
	public function checkStatus(){
		$warenkorbPersonalData = new Zend_Session_Namespace('warenkorb');

        if(empty($warenkorbPersonalData->kundenId)){
            $auth = new Zend_Session_Namespace('Auth');

            $sql = "select * from tbl_adressen where id = ".$this->userId;
            $userData = $this->_db_front->fetchRow($sql);
            $userData['kundenId'] = $userData['id'];
            unset($userData['id']);

            foreach($userData as $key => $value){
                $warenkorbPersonalData->$key = $value;
            }
        }

		if(empty($warenkorbPersonalData->kundenId))
			throw new nook_Exception($this->error_no_kunden_id);

		if(empty($warenkorbPersonalData->status))
			throw new nook_Exception($this->error_no_status);

		if($warenkorbPersonalData->status < $this->condition_role_optionator)
			throw new nook_Exception($this->error_wrong_status);

		return;
	}

    /**
     * Holt die Personendaten eines
     * bereits gespeicherten Benutzers.
     *
     *
     */
    public function getpersonalData(){
        $personendaten = array();
        
        if(empty($this->userId))
            return $personendaten;

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;
        $sql = "select * from tbl_adressen where id = ".$this->userId;
        $personendaten = $db->fetchRow($sql);

        return $personendaten;
    }

    /**
     * baut ein Array mit Class Informationen zum
     * darstellen der Schrittfolge im Bestellprozess
     *
     * @param $__step
     * @return void
     */
    public function getAktiveStep($__bereich, $__step,array $__params){
        // Breadcrumb
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Holt die verfügbaren Anreden entsprechend der Sprache
     *
     * @return void
     */
    public function getTitles(){
        $translate = new Zend_Session_Namespace('translate');

        $anrede = nook_ToolSprache::getSalutation($translate->language);

        return $anrede;
    }

    /**
     * Ermittelt eine Zeitspanne zur Darstellung
     *
     */
    public function darstellenZeitspanne($__von, $__bis, $__checked = false){
        $zeitraum = array();

        $j = 0;
        for($i= $__von; $i <= $__bis; $i++){
            $zeitraum[$j]['zeit'] = $i;

            if(empty($__checked) or $__checked != $i)
                $zeitraum[$j]['checked'] = 0;

            $j++;
        }

        return $zeitraum;
    }

    /**
     * Holt die Länder und setzt Deutschland als Standardwert
     *
     * @return void
     */
    public function getLaender()
    {
        $anzeigesprache = nook_ToolSprache::getAnzeigesprache();

        $tabelleCountries = new Application_Model_DbTable_countries();
        $select = $tabelleCountries->select();

        $cols = array(
            'id'
        );

        if($anzeigesprache == 'de')
            $cols[] = "Name";
        else
            $cols[] = new Zend_Db_Expr("IntName as Name");

        $whereAnzeige = "anzeige = ".$this->condition_aktive_anzeige;

        $select
            ->from($tabelleCountries, $cols)
            ->where($whereAnzeige)
            ->order("Name asc");

        $query = $select->__toString();

        $laender = $tabelleCountries->fetchAll($select)->toArray();

        for($i=0; $i< count($laender); $i++){
            if($laender[$i]['id'] == $this->condition_kennung_deutschland)
                $laender[$i]['checked'] = 1; // Länderauswahl
            else
                $laender[$i]['checked'] = 0;
        }

        return $laender;
    }

    /**
     * Kontrolliert ob eine Mailadresse bereits vorhanden ist.
     *
     * @param $__mail
     * @return bool
     */
    public function controlIfEmailIsDouble($__email){

		$sql = "
			SELECT
			    count(email) as anzahl
			FROM
			    `tbl_adressen`
			WHERE (`email` = '".$__email."');
		";

		$control = $this->_db_front->fetchOne($sql);
		if($control > 0)
			return true;
		else
			return false;
    }

    /**
     * Findet einen Kunden entsprechend vorgegebener Suchparameter
     * und trägt die ID des Kunden in den Session_Namespace['Auth']
     *
     */
    public function findUserByParams($__formElements){

        if($this->_checkFormularPruefen($__formElements)){
            $__formElements = $this->_mapFormularpruefen($__formElements);
            return $this->_checkSingleUser($__formElements);
        }

        return false;
    }

    /**
     * Überprüfen der Einträge des Formulars 'pruefen'
     *
     */
    private function _checkFormularPruefen($__formElements){

        if( strlen( trim( $__formElements['firstname'] )) < 2 )
            throw new nook_Exception($this->error_formulardaten_unvollstaendig);

        if( strlen( trim( $__formElements['lastname'] ) ) < 2 )
            throw new nook_Exception($this->error_formulardaten_unvollstaendig);

        if( !filter_var($__formElements['email'], FILTER_VALIDATE_EMAIL)  )
            throw new nook_Exception($this->error_formulardaten_unvollstaendig);

        return true;
    }

    /**
     * Mappen des Formular 'pruefen'
     * leere Formularelemente werden geloescht
     *
     */
     private function _mapFormularpruefen($__formElements){
         $elements = array();

         foreach($__formElements as $key => $value){
            if(!empty( $value )){
                $elements[$key] = $value;
            }
         }

         if(array_key_exists('day', $elements) and array_key_exists('month', $elements) and array_key_exists('year', $elements)){
            $elements['birthday'] = $elements['day'].".".$elements['month'].".".$elements['year'];
         }

         unset($elements['superuser']);
         unset($elements['day']);
         unset($elements['month']);
         unset($elements['year']);

         return $elements;
     }

     /**
      * Erstellt aus den Formularwerten eine query zur identifikation des Users
      */
    private function _checkSingleUser($__formElements){
        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = $this->_db_front;

        $sql = "select id as userId, status as role_id from tbl_adressen where ";

        foreach($__formElements as $key => $value){
            $sql .= $key." = '".$value."' and ";
        }

        $sql = substr($sql, 0, -5);
        $result = $db->fetchAll($sql);

        if(count($result) == 1){
            $auth = Zend_Session_Namespace('Auth');
            $auth->userId = $result[0]['userId'];
            $auth->role_id = $result[0]['role_id'];

            return $result[0]['userId'];
        }

        return 0;

    }


}
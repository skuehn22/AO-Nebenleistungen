<?php
/**
 * Generiert die Link der Button der alternativen
 * Blöcke. Jeder Bereich hat einen alternativen Block
 *
 * @author Stephan.Krauss
 * @date 23.01.13
 * @file WarenkorbAlternativeBloecke.php
 */
 
class Front_Model_WarenkorbAlternativeBloecke extends nook_ToolModel implements arrayaccess{

    // Fehler
    private $_error_keine_buchung_vorhanden = 1200;

    // Konditionen

    // Tabellen / Views
    private $_tabelleBuchungsnummer = null;
    private $_tabelleHotelbuchung = null;

    protected $_sessionId = null;
    protected $_buchungsnummer = null;

    // Suchvariablen des Namespace des Bereiches
    protected $_suchparameterNamespace = array(
        'hotelsuche' => array(
            'city' =>  0,
            'propertyId' => 0
        ),
        'programmsuche' => array(
            'city' => 0
        )
    );

    public function __construct(){
        /** @var _tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $this->_tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        $this->_tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();

    }

    /**
     * Bestimmt die Session ID und die Suchparameter
     * des Namespace
     *
     * @return Front_Model_WarenkorbAlternativeBloecke
     */
    public function bestimmeParameterDerSuche(){

        $this->_sessionId = $this->_bestimmeSessionId();
        $this->_bestimmeSuchparameter();
        $buchungVorhanden = $this->_bestimmeBuchungsnummer();

        return $buchungVorhanden;
    }

    /**
     * Bestimmt die Suchparameter der Suche eines Bereiches.
     *
     * + speichert ID der City und Id des Hotels
     *
     * @return Front_Model_WarenkorbAlternativeBloecke
     */
    private function _bestimmeSuchparameter(){
        // Bereiche in der Session
        $sessionBereiche = (array) Zend_Session::getIterator();

        for($i=0; $i < count($sessionBereiche); $i++){

            if(!array_key_exists($sessionBereiche[$i], $this->_suchparameterNamespace))
                continue;

            $sessionNamespace = new Zend_Session_Namespace($sessionBereiche[$i]);
            $namespaceVariablen = (array) $sessionNamespace->getIterator();

            foreach($namespaceVariablen as $key => $value){

                // übernimmt Suchparameter
                if(array_key_exists($key, $this->_suchparameterNamespace[$sessionBereiche[$i]]))
                    $this->_suchparameterNamespace[$sessionBereiche[$i]][$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Ermittelt die aktuelle Session ID
     *
     * @return Front_Model_WarenkorbAlternativeBloecke
     */
    private function _bestimmeSessionId(){

        $sessionId = nook_ToolSession::getSessionId();
        $this->_sessionId = $sessionId;

        return $this;
    }

    /**
     * Bestimmt die Buchungsnummer der
     * aktuellen Session.
     *
     * @return Front_Model_WarenkorbAlternativeBloecke
     * @throws nook_Exception
     */
    private function _bestimmeBuchungsnummer(){
        $buchungsNummerVorhanden = true;

        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        if(empty($buchungsnummer))
            $buchungsNummerVorhanden = false;

        $this->_buchungsnummer = $buchungsnummer;

        return $buchungsNummerVorhanden;
    }

    /**
     * Schaltet alternative Blöcke
     *
     * @param RainTPL $__rainTpl
     * @return RainTPL
     */
    public function buttonLink(RainTPL $__rainTpl){

        // Alternativ Button Hotelbuchung
        $alternativButtonHotelbuchung = $this->_getAlternativHotelbuchungButtonLink();
        $__rainTpl->assign('alternativButtonHotelbuchung', $alternativButtonHotelbuchung);

        // Alternativ Button Programmbuchung
        $alternativButtonProgrammbuchung = $this->_getAlternativProgrammbuchungButtonLink();
        $__rainTpl->assign('alternativButtonProgrammbuchung', $alternativButtonProgrammbuchung);

        // Alternativ Button Produktbuchung
        $alternativButtonZusatzprodukte = $this->_getAlternativProduktbuchungButtonLink();
        $__rainTpl->assign('alternativButtonZusatzprodukte', $alternativButtonZusatzprodukte);

        return $__rainTpl;
    }

    /**
     * Ermittelt den Button Link Hotelbuchung
     * entsprechend der Parameter.
     * Vorbelegung der Parameter ist 0.
     *
     * @return string
     */
    private function _getAlternativHotelbuchungButtonLink(){

        // Suchparameter Hotelsuche
        if( ($this->_suchparameterNamespace['hotelsuche']['city'] > 0) and ($this->_suchparameterNamespace['hotelsuche']['propertyId'] > 0) )
            return "/front/hotelreservation/index/propertyId/".$this->_suchparameterNamespace['hotelsuche']['propertyId'];

        elseif($this->_suchparameterNamespace['hotelsuche']['city'] > 0)
            return "/front/hotelsearch/index/city/".$this->_suchparameterNamespace['hotelsuche']['city'];

        // Suchparameter Programmbuchung
        elseif($this->_suchparameterNamespace['programmsuche']['city'] > 0)
            return "/front/hotelsearch/index/city/".$this->_suchparameterNamespace['programmsuche']['city'];

        // keine Suchparameter in der Session
        else
            return "/front/login/";
    }

    /**
     * Ermittelt Button Link Programme
     * entsprechend der Parameter
     *
     * @return string
     */
    private function _getAlternativProgrammbuchungButtonLink(){

        // Suchparameter Programmbuchung
        if($this->_suchparameterNamespace['programmsuche']['city'] > 0)
            return  "/front/programmstart/index/city/".$this->_suchparameterNamespace['programmsuche']['city'];

        // Suchparameter Hotelsuche
        elseif( $this->_suchparameterNamespace['hotelsuche']['city'] > 0 )
            return "/front/programmstart/index/city/".$this->_suchparameterNamespace['hotelsuche']['city'];

        // keine Suchparameter in der Session
        else
            return "/front/login/";
   }

    /**
     * Ermittelt Button Link Programme
     * entsprechend der Parameter
     *
     * @return string
     */
    private function _getAlternativProduktbuchungButtonLink(){

        // Bestimmung Datensaetze Hotelbuchung
        $propertyId = $this->_datensaetzeHotelbuchung();

        // Hotel ID entsprechend Buchungsnummer
        if(!empty($propertyId))
            return "/front/zusatzprodukte/index/propertyId/".$propertyId;
        // Hotel ID entsprechend Suchparameter
        elseif( $this->_suchparameterNamespace['hotelsuche']['propertyId'] > 0 )
            return "/front/zusatzprodukte/index/propertyId/".$this->_suchparameterNamespace['hotelsuche']['propertyId'];
        else
            return '';

    }

    /**
     * Sucht nach den Datensaetzen
     * der Hotelbuchung einer Session.
     *
     * + Wenn keine Hotelbuchung vorhanden dann
     *   return false
     * + Wenn Hotelbuchung vorhanden, dann Hotel Id
     *   der letzten Hotelbuchung.
     *
     * @return array
     */
    private function _datensaetzeHotelbuchung(){
        $datensaetzeHotelbuchung = false;

        $where = "buchungsnummer_id = ".$this->_buchungsnummer;

        $order = array(
            "id desc"
        );

        $select = $this->_tabelleHotelbuchung->select();
        $select
            ->where($where)
            ->order($order);

        $datensaetzeHotelbuchung = $this->_tabelleHotelbuchung->fetchAll($select)->toArray();

        if(count($datensaetzeHotelbuchung) > 0)
            return $datensaetzeHotelbuchung[0]['propertyId'];

        return $datensaetzeHotelbuchung;
    }








}

<?php
/**
 * Model der Abfahrtsorte
 *
 * @author Stephan.Krauss
 * @date 23.40.2014
 * @file Abfahrtsorte.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_Abfahrtsorte extends nook_Model_model{

    // errors
    private $_error_keine_korrekte_treffpunkt_query = 610;
    private $_error_keine_neue_zeit_eingetragen = 611;
    private $_error_eingabe_daten_falsch = 612;
    private $_error_eingabe_treffpunkt_falsch = 613;
    private $_error_anzahl_treffpunkte_stimmt_nicht = 614;
    private $_error_programmid_nicht_vorhanden = 615;
    private $_error_programmid_nicht_integer = 616;

    // conditionen

    /** @var $_db_front Zend_Db_Adapter */
    private $_db_front;

    // Auth
    // private  $_auth;

    /** @var $pimple Pimple_Pimple */
    public $pimple;

   
	public function __construct(){
		$this->_db_front = Zend_Registry::get('front');
        $this->_auth = new Zend_Session_Namespace('Auth');
		
		return;
	}

	/**
	* Das ist der erste Eintrag zum testen
	*
	*/
    public function checkInsertTreffpunkt($__params){
        $inArray= array('programmId','InformationDeutsch','InformationEnglisch','StartDatum','EndDatum');

        foreach($inArray as $keyName){
            if(!array_key_exists($keyName, $__params) || empty($__params[$keyName]))
                throw new nook_Exception($this->_error_eingabe_treffpunkt_falsch);
        }

        return;
    }

    public function checkVorhandenerTreffpunkt($__params){
        if(!array_key_exists('programmId', $__params))
            throw new nook_Exception($this->_error_programmid_nicht_vorhanden);

        if(!filter_var($__params['programmId'], FILTER_VALIDATE_INT))
            throw new nook_Exception($this->_error_programmid_nicht_integer);

        return;
    }

    public function checkQueryStore($__query){

        $checkQuery = new Zend_Validate();
        $checkQuery->addValidator( new Zend_Validate_StringLength(array('min' => 3)));
       
        if(!$checkQuery->isValid($__query))
            throw new nook_Exception($this->_error_keine_korrekte_treffpunkt_query);

        return;
    }

    public function checkInsertZeiten($__params){

        $inArray= array('programmId');

        foreach($inArray as $keyName){
            if(!array_key_exists($keyName, $__params) || empty($__params[$keyName]))
                throw new nook_Exception($this->_error_eingabe_daten_falsch);
        }

        if(empty($__params['abfahrt']))
            throw new nook_Exception($this->_error_eingabe_daten_falsch);

        return $__params;
    }

    public function map($__params){
        if(array_key_exists('module', $__params))
            unset($__params['module']);
        if(array_key_exists('controller', $__params))
            unset($__params['controller']);
        if(array_key_exists('action', $__params))
            unset($__params['action']);

        if(array_key_exists('query', $__params)){
            $__params['query'] = trim($__params['query']);
            $__params['query'] = str_replace('%20','',$__params['query']);
        }

        // beseitigen Element Kennung
        foreach($__params as $key => $value){
            if(strstr($key, 'Form')){
                unset($__params[$key]);

                $newKey = str_replace('Form', '', $key);
                $__params[$newKey] = $value;
            }
        }

        foreach($__params as $key => $value){
            // Datums Formatierung
            if(($key == 'StartDatum') || ($key == 'EndDatum')){
                $__params[$key] = nook_Tool::buildEnglishDateFromGermanDate($value);
            }
        }

        return $__params;
    }

    public function getVorhandeneDaten($__programmId){
        $treffpunktDaten = array(
            'FormInformationDeutsch' => '',
            'FormInformationEnglisch' => '',
            'Treffpunkt' => '',
            'FormStartDatum' => '',
            'FormEndDatum' => ''
        );

        $sql = "
            SELECT
                `tbl_programmdetails_abfahrten`.`deutsch`
                , `tbl_programmdetails_abfahrten`.`englisch`
                , `tbl_programmdetails_abfahrten`.`startDatum`
                , `tbl_programmdetails_abfahrten`.`endDatum`
                , `tbl_treffpunkt`.`treffpunkt`
                , `tbl_treffpunkt`.`id`
            FROM
                `tbl_programmdetails_abfahrten`
                INNER JOIN `tbl_treffpunkt`
                    ON (`tbl_programmdetails_abfahrten`.`treffpunkt_id` = `tbl_treffpunkt`.`id`)
            WHERE (`tbl_programmdetails_abfahrten`.`programm_id` = ".$__programmId.");
        ";

        $rohDaten = $this->_db_front->fetchRow($sql);

        if(count($rohDaten) > 1){
            $treffpunktDaten = array(
                'FormInformationDeutsch' => $rohDaten['deutsch'],
                'FormInformationEnglisch' => $rohDaten['englisch'],
                'Treffpunkt' => $rohDaten['treffpunkt'],
                'FormStartDatum' => $rohDaten['startDatum'],
                'FormEndDatum' => $rohDaten['endDatum'],
                'valueId' => $rohDaten['id']
            );
        }

        return $treffpunktDaten;
    }

    public function getTreffpunkte( $__query, $__start, $__limit){
        $treffpunkte = array();

        $sql = "
            SELECT
                count(`id`) as anzahl
            FROM
                `tbl_treffpunkt`
            WHERE (`tbl_treffpunkt` like '%".$__query."%')";

        $treffpunkte['anzahl'] = $this->_db_front->fetchOne($sql);

        $sql = "
            SELECT
                `id`,
                `treffpunkt`
            FROM
                `tbl_treffpunkt`
            WHERE (`tbl_treffpunkt` like '%".$__query."%')
            ORDER BY `treffpunkt`
            LIMIT ".$__start.",".$__limit;

        $treffpunkte['data'] = $this->_db_front->fetchAll($sql);

        return $treffpunkte;
    }

    public function setProgrammZeit($__params){
        $__params['programm_id'] = $__params['programmId'];
        unset($__params['programmId']);

        /** @var $db Zend_Db_Adapter_Mysqli  */
        $db = $this->_db_front;
        $db->insert('tbl_programmdetails_zeiten', $__params);

        return;
    }

    public function getProgrammZeiten($__programmId){

        $sql = "
            SELECT
                DATE_FORMAT(`abfahrt`,'%H:%i') as abfahrt
                , DATE_FORMAT(`ankunft`, '%H:%i') as ankunft
                , `id`
            FROM
                `tbl_programmdetails_zeiten`
            WHERE (`programm_id` = ".$__programmId.")";

        $zeiten = $this->_db_front->fetchAll($sql);

        return $zeiten;
    }

    public function removeProgrammZeit($__id){

        $sql = "delete from tbl_programmdetails_zeiten where id = ".$__id;
        $db = $this->_db_front->query($sql);

        return;
    }

    public function setTreffpunkte($__params){

        $__params['Treffpunkt'] = intval($__params['Treffpunkt']);

        $sql = "select count(programm_id) as anzahl from tbl_programmdetails_abfahrten where programm_id = ".$__params['programmId'];
        $anzahl = $this->_db_front->fetchOne($sql);

        if($anzahl == 1){
            $update = array(
                'deutsch' => $__params['InformationDeutsch'],
                'englisch' => $__params['InformationEnglisch'],
                'startDatum' => $__params['StartDatum'],
                'endDatum' => $__params['EndDatum']
            );

            if($__params['Treffpunkt'] > 0)
                $update['treffpunkt_id'] = $__params['Treffpunkt'];

            $this->_db_front->update('tbl_programmdetails_abfahrten', $update, "programm_id = ".$__params['programmId']);
        }
        elseif($anzahl == 0){
             $insert = array(
                'programm_id' => $__params['programmId'],
                'deutsch' => $__params['InformationDeutsch'],
                'englisch' => $__params['InformationEnglisch'],
                'treffpunkt_id' => $__params['Treffpunkt'],
                'startDatum' => $__params['StartDatum'],
                'endDatum' => $__params['EndDatum']
            );

            $this->_db_front->insert('tbl_programmdetails_abfahrten', $insert);
        }
        else{
            throw new nook_Exception($this->_error_anzahl_treffpunkte_stimmt_nicht);
        }



        return;
    }

}
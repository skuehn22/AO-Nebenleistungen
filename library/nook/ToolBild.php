<?php
/**
 * Ermittelt Zusatzinformationen zum Bild.
 * Städtebilder und programmbilder werden unterstützt.
 *
 * Es kann ermittelt werden:
 * - Die Copyright Information
 * - Der Beschreibungstext 'mini' zum Bild
 * - Der Beschreibungstext 'lang' zum Bild
 * - Der Bildname für Bildattribut 'alt'
 *
 * Factory Pattern
 *
 * @author Stephan.Krauss
 * @date 21.02.13
 * @file ToolBild.php
 */
class nook_ToolBild{

    // Tabellen / Views
    private $_tabelleBilder = null;


    // Fehler
    private $_error_ein_int_wert = 1250;
    private $_error_falscher_bildtyp = 1251;
    private $_error_angaben_unvollstaendig = 1252;
    private $_error_zu_viele_datensaetze = 1253;
    private $_error_bildbereich_nicht_definiert = 1254;

    // Konditionen
    private $_condition_stadt_bild = 10;
    private $_condition_programm_bild = 1;
    private $_condition_hotel_bild = 6;

    private static $instance = null;
    protected  $_bildId = null;
    protected $_bildTyp = null;
    protected $_bildVorhanden = null;

    // unterstützte Bildbereiche
    protected $_bildTypen = array(
        '1',
        '10',
        '6'
    );

    private $_zusatzInformationBild = array();

    public function __construct(){
        /** @var _tabelleBilder Application_Model_DbTable_bilder */
        $this->_tabelleBilder = new Application_Model_DbTable_bilder();
    }

    /**
     * Factory Pattern. Verhindert die Neuerstellung
     * des Objektes.
     *
     * @return nook_ToolBild|null
     */
    public  static function factory(){

        if(!is_object(self::$instance)){
            self::$instance = new nook_ToolBild();
        }

        return self::$instance;
    }

    private function __clone(){}

    public function __wakeup(){}

    /**
     * @param $__bildId
     * @return nook_ToolBild
     * @throws nook_Exception
     */
    public function setBildId($__bildId){

        $bildId = (int) $__bildId;

        if(!is_int($bildId) )
            throw new nook_Exception($this->_error_ein_int_wert);

        $this->_bildId = $bildId;

        return $this;
    }

    /**
     * Kontrolle auf Int und
     * richtiger Bildtyp
     *
     * @param $__bildTyp
     * @return nook_ToolBild
     * @throws nook_Exception
     */
    public function setBildTyp($__bildTyp){

        $bildTyp = (int) $__bildTyp;
        if(!is_int($__bildTyp))
            throw new nook_Exception('keine Integer');

        if(!in_array($__bildTyp, $this->_bildTypen))
            throw new nook_Exception('Bildtyp unbekannt');

        $this->_bildTyp = $bildTyp;

        return $this;
    }

    /**
     * Findet die Zusatzinformationen zum Bild
     */
    private function _findBildInformationEinesBildes(){

        // wenn Bild nicht vorhanden
        if($this->_bildVorhanden === false)
            return '';

        if(empty($this->_bildId) or empty($this->_bildTyp))
            throw new nook_Exception($this->_error_angaben_unvollstaendig);

        $cols = array(
            'bildname',
            'copyright'
        );

        $select = $this->_tabelleBilder->select();
        $select
            ->from($this->_tabelleBilder, $cols)
            ->where("fremdschluessel = ".$this->_bildId)
            ->where("bildtyp = '".$this->_bildTyp."'");

        $rows = $this->_tabelleBilder->fetchAll($select)->toArray();

        $this->_zusatzInformationBild['bildname'] = '';
        $this->_zusatzInformationBild['copyright'] = false;

//        if(count($rows) > 1)
//            throw new nook_Exception($this->_error_zu_viele_datensaetze);

        if(count($rows) == 1){
            $this->_zusatzInformationBild['bildname'] = $rows[0]['bildname'];
            $this->_zusatzInformationBild['copyright'] = $rows[0]['copyright'];
        }

        return $this;
    }

    /**
     * Findet das Bild
     *
     * @return nook_ToolBild
     */
    private function _findAbgelegtesBild(){

        $bildTyp = $this->_bildTyp;
        $bildPfadAbsolute = ABSOLUTE_PATH.'/images/';

        if($this->_bildTyp == $this->_condition_stadt_bild){
            $bildPfadAbsolute .= "city/maxi/";
            $bildPfad = "/images/city/maxi/";
        }
        elseif($this->_bildTyp == $this->_condition_programm_bild){
            $bildPfadAbsolute .= "program/midi/";
            $bildPfad = "/images/program/midi/";
        }
        elseif($this->_bildTyp == $this->_condition_hotel_bild){
            $bildPfadAbsolute .= "propertyImages/midi/";
            $bildPfad = "/images/propertyImages/midi/";
        }

        $this->_zusatzInformationBild['bildpfad'] = null;

        if(is_file($bildPfadAbsolute.$this->_bildId.".jpg")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".jpg";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".jpg";
        }
        elseif(is_file($bildPfadAbsolute.$this->_bildId.".png")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".png";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".png";
        }
        elseif(is_file($bildPfadAbsolute.$this->_bildId.".gif")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".gif";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".gif";
        }
        elseif(is_file($bildPfadAbsolute.$this->_bildId.".JPG")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".JPG";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".JPG";
        }
        elseif(is_file($bildPfadAbsolute.$this->_bildId.".PNG")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".PNG";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".PNG";
        }
        elseif(is_file($bildPfadAbsolute.$this->_bildId.".GIF")){
            $this->_bildVorhanden = $bildPfadAbsolute.$this->_bildId.".GIF";
            $this->_zusatzInformationBild['bildpfad'] = $bildPfad.$this->_bildId.".GIF";
        }

        clearstatcache();

        return $this;
    }

    /**
     * Findet die Zusatzinformation
     * eines Bildes
     *
     * @return array
     */
    public function getZusatzinformationBild(){
        if($this->_bildId == 0)
            return ' ';

        $this->_findAbgelegtesBild();
        $this->_findBildInformationEinesBildes();

        return $this->_zusatzInformationBild;
    }
}
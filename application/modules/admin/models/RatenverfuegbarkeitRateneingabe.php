<?php
class Admin_Model_RatenverfuegbarkeitRateneingabe extends nook_Model_model{

    // Fehler
    private $_error_falsche_hotel_id = 530;
    private $_error_kein_category_code_vorhanden = 531;

    private $_condition_rate_ist_aktiv = 1;

    /**
     * @var nook_ZugriffAufHotels
     */
    public $zugriff;

    /**
     * @var Zend_Db_Adapter
     */
    private $_db_hotels;

    /**
     * @var Zend_Db_Adapter
     */
    private $_db_front;

    private $_tagInSekunden = 86400;
    private $_startZeitSekunden;
    private $_endZeitSekunden;
    private $_anreisetage = array();
    private $_abreisetage = array();
    private $_ratenParams = array();
	
	public function __construct(){
		$this->_db_hotels = Zend_Registry::get('hotels');
        $this->_db_front = Zend_Registry::get('front');
		
		return;
	}

    public function mapData($__params){
        $__params['preis'] = str_replace(',','.',$__params['preis']);

        return $__params;
    }

    public function getHotels(){
        $listeDerHotels = array();

        $stringListeDerHotels = $this->zugriff
                            ->setKundenDaten()
                            ->getStringHotels();

        $sql = "
        SELECT
            `id`
            , `property_name` as 'hotelname'
        FROM
            `tbl_properties`
        WHERE id IN (".$stringListeDerHotels.") order by property_name asc";

        $listeDerHotels = $this->_db_hotels->fetchAll($sql);

        return $listeDerHotels;
    }

    /**
     * Gibt die Liste der Raten eines Hotels zurück
     *
     * @param $__hotelId
     * @return array
     */
    public function getListeDerRaten($__hotelId)
    {
        $listeDerRaten = array();

        $sql = "
        SELECT
            `property_code`
        FROM
            `tbl_properties`
        WHERE (`id` = ".$__hotelId.")";

        $hotelCode = trim($this->_db_hotels->fetchOne($sql));

        $sql = "
        SELECT
            `id`
            , `name` AS ratenname
        FROM
            `tbl_ota_rates_config`
        WHERE (`hotel_code` = '".$hotelCode."') ORDER BY NAME";

        $listeDerRaten = $this->_db_hotels->fetchAll($sql);

        return $listeDerRaten;
    }

    public function checkZugriffAufHotel($__hotelId){

        $kontrolle = $this->zugriff->checkIstZugriffAufHotelErlaubt($__hotelId);
        if(empty($kontrolle))
            throw new   nook_Exception($this->_error_falsche_hotel_id);

        $this->_ratenParams['hotelId'] = $__hotelId;

        return;
    }

    public function checkStartEnddatum($__startDatum, $__endDatum){
        $kontrolle = true;

        $startTeile = explode('.',$__startDatum);
        $startZeit = mktime(0,0,0,$startTeile[1],$startTeile[0],$startTeile[2]);

        $endTeile = explode('.', $__endDatum);
        $endZeit = mktime(0,0,0,$endTeile[1],$endTeile[0],$endTeile[2]);

        if($endZeit < $startZeit)
            $kontrolle = false;

        $this->_startZeitSekunden = $startZeit;
        $this->_endZeitSekunden = $endZeit;

        return $kontrolle;
    }

    /**
     * Ablaufsteuerung der manuellen Eingabe
     * der Raten eines Hotels
     *
     * @param $__params
     * @return Admin_Model_RatenverfuegbarkeitRateneingabe
     */
    public function buildRaten($__params){
         $this
             ->_setAnreisetage($__params)
             ->_setAbreisetage($__params)
             ->_setAllgemeineParameter($__params)
             ->_ermittelnCategoryCode()
             ->_durchlaufenZeitraum();

        return $this;
    }

    private function _ermittelnCategoryCode(){

        $sql = "
            SELECT
                tbl_ota_rates_config.id
                , tbl_ota_rates_config.category_id
                , tbl_ota_rates_config.properties_id
                , tbl_categories.categorie_code
                , tbl_categories.categorie_name
                , tbl_categories.standard_persons
            FROM
                tbl_ota_rates_config
                INNER JOIN tbl_categories 
                    ON (tbl_ota_rates_config.category_id = tbl_categories.id)
            WHERE (tbl_ota_rates_config.id = ".$this->_ratenParams['ratenId']."
                AND tbl_ota_rates_config.properties_id = ".$this->_ratenParams['hotelId'].")";

        $row = $this->_db_hotels->fetchRow($sql);

        if(!is_array($row))
            throw new nook_Exception($this->_error_kein_category_code_vorhanden);

        $this->_ratenParams['categoryCode'] = $row['categorie_code'];

        return $this;
    }

    private function _durchlaufenZeitraum(){
        for($tagInSekunden = $this->_startZeitSekunden; $tagInSekunden <= $this->_endZeitSekunden; $tagInSekunden += $this->_tagInSekunden){
            $aktuellerTag = date('Y-m-d',$tagInSekunden);

            $this->_loeschenAlteRate($aktuellerTag);
            $this->_eintragenNeueRate($aktuellerTag, $tagInSekunden);
            $this->_loeschenAlterRatenpreis($aktuellerTag);
            $this->_eintragenNeuerRatenpreis($aktuellerTag, $tagInSekunden);
        }

        return $this;
    }

    private function _loeschenAlterRatenpreis($__aktuellerTag){
        
        $sql = "delete from tbl_ota_prices where datum = '".$__aktuellerTag."' and hotel_code = '".$this->_ratenParams['hotelCode']."' and rate_code = '".$this->_ratenParams['ratenCode']."'";
        $this->_db_hotels->query($sql);

        return $this;
    }

    private function _eintragenNeuerRatenpreis($__aktuellerTag, $__tagInSekunden){
        $wochentagNummer = date("N",$__tagInSekunden);

        $insert = array();
        $insert['datum'] = $__aktuellerTag;
        $insert['amount'] = $this->_ratenParams['preis'];
        $insert['min_stay'] = $this->_ratenParams['minimal'];

         if(!empty($this->_anreisetage[$wochentagNummer]))
            $insert['allowed_arrival'] = 1;
        else
            $insert['allowed_arrival'] = 0;

        if(!empty($this->_abreisetage[$wochentagNummer]))
            $insert['allowed_departure'] = 1;
        else
            $insert['allowed_departure'] = 0;

        $insert['release_from'] = $this->_ratenParams['fruehestens'];
        $insert['release_to'] = $this->_ratenParams['spaetestens'];
        $insert['rates_config_id'] = $this->_ratenParams['ratenId'];
        $insert['rate_code'] = $this->_ratenParams['ratenCode'];
        $insert['hotel_code'] = $this->_ratenParams['hotelCode'];
        $insert['category_code'] = $this->_ratenParams['categoryCode'];
        $insert['pricePerPerson'] = $this->_ratenParams['preistyp'];

        $this->_db_hotels->insert('tbl_ota_prices', $insert);

        return $this;
    }

    private function _loeschenAlteRate($__aktuellerTag){
        $sql = "delete from tbl_ota_rates_availability where datum = '".$__aktuellerTag."'";
        $sql .= " and hotel_code = '".trim($this->_ratenParams['hotelCode'])."'";
        $sql .= " and rate_code = '".trim($this->_ratenParams['ratenCode'])."'";

        $this->_db_hotels->query($sql);

        return;
    }

    private function _eintragenNeueRate($__aktuellerTag, $__tagInSekunden){

        $wochentagNummer = date("N",$__tagInSekunden);

        $insert = array();
        $insert['datum'] = $__aktuellerTag;
        $insert['availibility'] = $this->_condition_rate_ist_aktiv;
        $insert['roomlimit'] = $this->_ratenParams['anzahl'];
        $insert['min_stay'] = $this->_ratenParams['minimal'];

        if(!empty($this->_anreisetage[$wochentagNummer]))
            $insert['arrival'] = 1;
        else
            $insert['arrival'] = 0;

        if(!empty($this->_abreisetage[$wochentagNummer]))
            $insert['departure'] = 1;
        else
            $insert['departure'] = 0;

        $insert['release_from'] = $this->_ratenParams['fruehestens'];
        $insert['release_to'] = $this->_ratenParams['spaetestens'];
        $insert['rate_code'] = $this->_ratenParams['ratenCode'];
        $insert['hotel_code'] = $this->_ratenParams['hotelCode'];
        $insert['category_code'] = $this->_ratenParams['categoryCode'];
        $insert['rates_config_id'] = $this->_ratenParams['ratenId'];
        $insert['property_id'] = $this->_ratenParams['hotelId'];

        $this->_db_hotels->insert('tbl_ota_rates_availability', $insert);

        return;
    }

    private function _setAllgemeineParameter($__params){
        $this->_ratenParams['ratenId'] = $__params['ratenId'];
        $this->_ratenParams['anzahl'] = $__params['anzahl'];

        $this->_ratenParams['preis'] = $__params['preis'];
        $this->_ratenParams['fruehestens'] = $__params['fruehestens'];
        $this->_ratenParams['spaetestens'] = $__params['spaetestens'];
        $this->_ratenParams['minimal'] = $__params['minimal'];

        $sql = "select property_code as hotelCode from tbl_properties where id = ".$this->_ratenParams['hotelId'];
        $this->_ratenParams['hotelCode'] = $this->_db_hotels->fetchOne($sql);

        $sql = "select rate_code as ratenCode from tbl_ota_rates_config where hotel_code = '".$this->_ratenParams['hotelCode']."' and id = ".$this->_ratenParams['ratenId'];
        $this->_ratenParams['ratenCode'] = $this->_db_hotels->fetchOne($sql);

        if($__params['preistypen'] == 'Zimmerpreis')
            $this->_ratenParams['preistyp'] = 'false';
        else
            $this->_ratenParams['preistyp'] = 'true';

        return $this;
    }

    /**
     * legt den Anreisetag im
     * Array $this->_anreisetage
     * fest
     *
     * @param $__params
     * @return Admin_Model_RatenverfuegbarkeitRateneingabe
     */
    private function _setAnreisetage($__params){

        if(array_key_exists('montagAn',$__params))
            $this->_anreisetage[1] = true;
        else
            $this->_anreisetage[1] = false;

        if(array_key_exists('dienstagAn',$__params))
            $this->_anreisetage[2] = true;
        else
            $this->_anreisetage[2] = false;

        if(array_key_exists('mittwochAn',$__params))
            $this->_anreisetage[3] = true;
        else
            $this->_anreisetage[3] = false;

        if(array_key_exists('donnerstagAn',$__params))
            $this->_anreisetage[4] = true;
        else
            $this->_anreisetage[4] = false;

        if(array_key_exists('freitagAn',$__params))
            $this->_anreisetage[5] = true;
        else
            $this->_anreisetage[5] = false;

        if(array_key_exists('sonnabendAn',$__params))
            $this->_anreisetage[6] = true;
        else
            $this->_anreisetage[6] = false;

        if(array_key_exists('sonntagAn',$__params))
            $this->_anreisetage[7] = true;
        else
            $this->_anreisetage[7] = false;

         return $this;
    }

    /**
     * legt den Abreisetag im
     * Array $this->_abreisetage fest
     *
     * @param $__params
     * @return Admin_Model_RatenverfuegbarkeitRateneingabe
     */
    private function _setAbreisetage($__params){

            if(array_key_exists('montagAb',$__params))
                $this->_abreisetage[1] = true;
            else
                $this->_abreisetage[1] = false;

            if(array_key_exists('dienstagAb',$__params))
                $this->_abreisetage[2] = true;
            else
                $this->_abreisetage[2] = false;

            if(array_key_exists('mittwochAb',$__params))
                $this->_abreisetage[3] = true;
            else
                $this->_abreisetage[3] = false;

            if(array_key_exists('donnerstagAb',$__params))
                $this->_abreisetage[4] = true;
            else
                $this->_abreisetage[4] = false;

            if(array_key_exists('freitagAb',$__params))
                $this->_abreisetage[5] = true;
            else
                $this->_abreisetage[5] = false;

            if(array_key_exists('sonnabendAb',$__params))
                $this->_abreisetage[6] = true;
            else
                $this->_abreisetage[6] = false;

            if(array_key_exists('sonntagAb',$__params))
                $this->_abreisetage[7] = true;
            else
                $this->_abreisetage[7] = false;

             return $this;
        }

}
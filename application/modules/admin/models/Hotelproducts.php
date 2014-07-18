<?php
class Admin_Model_Hotelproducts extends nook_ToolModel{
	private $_db;
    private $_auth;

    // Konditionen
    private $_condition_role_provider = 5;

    // Fehler
	private $_error_produkt_kein_update = 320;
    private $_error_kein_bild_upload = 321;
    private $_error_zu_viele_datensaetze = 322;
    private $_error_produkt_code_mehrmals_im_hotel = 323;
    private $_error_anlegen_neues_hotelprodukt_fehlgeschlagen = 324;
    private $_error__kein_produktcode_angegeben = 325;

    // Tabellen und View
    private $_tabelleProducts = null;
	
	public function __construct(){
        /** @var _tabelleProducts Application_Model_DbTable_products */
        $this->_tabelleProducts = new Application_Model_DbTable_products(array('db' => 'hotels'));

		$this->_db = Zend_Registry::get('hotels');
        $this->_auth = new Zend_Session_Namespace('Auth');
		
		return;
	}

	public function getCountHotels($__stringListeDerHotels, $__alleHotels = false){
		$sql = "select count(id) from tbl_properties";

        if(empty($__alleHotels))
            $sql .= " where id IN (". $__stringListeDerHotels .")";

		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getHotels($__stringListeDerHotels, $__alleHotels = false, $__start = false, $__limit = false){
		$start = 0;
		$limit = 10;	
		
		if(!empty($__start)){
			$start = $__start;
			$limit = $__limit;
		}
		
		$sql = "select * from tbl_properties";

        if(empty($__alleHotels))
            $sql .= " where id IN (". $__stringListeDerHotels .")";

		$sql .= " order by property_name asc";
		$sql .= " limit ".$start.",".$limit;
		
		$hotels = $this->_db->fetchAll($sql);
		return $hotels;
	}

    /**
     * darstellen der Produkte eines Hotels
     *
     * @param $__start
     * @param $__limit
     * @param $__params
     * @return mixed
     */
    public function getProductsFromHotel($__start, $__limit, $__params){
		$sql = "select * from tbl_products where property_id = '".$__params['hotelId']."' order by aktiv, product_name limit ".$__start.", ".$__limit;
		$hotelProducts = $this->_db->fetchAll($sql);
		
		return $hotelProducts;
	}

    public function getAnzahlProdukteHotel($__params){
        $anzahlProdukteEinesHotel = 0;

        $sql = "
            SELECT
                count(`id`) AS `anzahl`
            FROM
                `tbl_products`
            WHERE (`property_id` = ".$__params['hotelId'].")";

        $anzahlProdukteEinesHotel = $this->_db->fetchOne($sql);

        return $anzahlProdukteEinesHotel;
    }

    /**
     * Holt die Daten eines Hotelproduktes
     *
     * @param $__productId
     * @return mixed
     */
    public function getSingleProductFromHotel($__productId){
        $sql = "select * from tbl_products where id = '".$__productId."' order by aktiv";
        $singleProductFromHotel = $this->_db->fetchRow($sql);

        // Checkbox Verpflegung
        if($singleProductFromHotel['verpflegung'] == 1)
            unset($singleProductFromHotel['verpflegung']);
        if($singleProductFromHotel['verpflegung'] == 2)
            $singleProductFromHotel['verpflegung'] = 'on';


        return $singleProductFromHotel;
    }

    /**
     * Mappen der Parameter eines
     * Hotelproduktes.
     * Setzen Produkt - Code der Verpflegung.
     *
     * @param $__params
     * @return mixed
     */
    public function mapPropertiesFromSingleProduct($__params){
        unset($__params['module']);
        unset($__params['controller']);
        unset($__params['action']);

        if($__params['productCode'] == 'Frühstück')
            $__params['productCode'] = 'BR';
        elseif($__params['productCode'] == 'Halbpension')
            $__params['productCode'] = 'HP';
        elseif($__params['productCode'] == 'Vollverpflegung')
            $__params['productCode'] = 'VB';
        elseif($__params['productCode'] == 'Mittagessen')
            $__params['productCode'] = 'LU';
        elseif($__params['productCode'] == 'Abendessen')
            $__params['productCode'] = 'DI';
        elseif($__params['productCode'] == 'Lunch')
            $__params['productCode'] = 'LU';
        elseif($__params['productCode'] == 'Dinner')
            $__params['productCode'] = 'DI';
        elseif($__params['productCode'] == 'all inklusive')
            $__params['productCode'] = 'AI';

        return $__params;
    }

    /**
     * Upload eines Produktbildes
     *
     * @param $__params
     * @return string
     * @throws nook_Exception
     */
    public function uploadProduktImage($__params){
        try{
            $image = $_FILES['productImage'];
            $imagePath = ABSOLUTE_PATH . "/images/product/";
            $imageName = $__params['productId'];

            // wenn ein Bild vorhanden ist
            if(!empty($image['tmp_name'])){
                $uploadImage = nook_upload::getInstance();
                $kontrolleImageTyp = $uploadImage->setImage($image)->setImagePath($imagePath)->setImageName($imageName)->checkImageTyp();
                if($kontrolleImageTyp){
                    $kontrolleMove = $uploadImage->moveImage();
                    if(!$kontrolleMove)
                        throw new nook_Exception($this->_error_kein_bild_upload);
                }
            }

            return;
        }
        catch(nook_Exception $e){

            switch($e->getMessage()){
                case '321':
                    $e = nook_ExceptionRegistration::registerException($e, 3, $__params);
                    return 'Produktbild kein upload';
                    break;
            }
        }
    }

    /**
     * Update eines Hotelproduktes.
     * Message der Verarbeitung an Benutzer.
     *
     * @param $__params
     * @return string
     * @throws nook_Exception
     */
    public function updateSingleHotelProduct($__params){

        try{
            $productId = $__params['productId'];
            unset($__params['productId']);

            // Checkbox Verpflegung
            if(array_key_exists('verpflegung', $__params))
                $__params['verpflegung'] = 2;
            else
                $__params['verpflegung'] = 1;

            // Korrektur Bruttopreis
            $__params['price'] = str_replace(',', '.', $__params['price']);

            $kontrolle = $this->_db->update('tbl_products', $__params, "id = '".$productId."'");
            if($kontrolle != 1)
                throw new nook_Exception($this->_error_produkt_kein_update);

            return 'Hotelprodukt wurde überarbeitet';
        }
        catch(nook_Exception $e){

            switch($e->getMessage()){
                case '320':
                    $e = nook_ExceptionRegistration::registerException($e, 3, $__params);
                    return 'Hotelprodukt kein Update';
                    break;
            }
        }
    }

    /**
     * Kontrolliert ob der Produktcode
     * im Hotel bereits anderweitig verwendet wird.
     *
     * @param $__params
     */
    public function checkProduktCode($__params){
        try{

            $__params['productCode'] = trim($__params['productCode']);

            // Wenn Produkt Code leer ist
            if(empty($__params['productCode']))
                throw new nook_Exception($this->_error__kein_produktcode_angegeben);

            // ermitteln ID des Hotels
            $cols = array(
                'property_id'
            );

            $select = $this->_tabelleProducts->select();
            $select
                ->from($this->_tabelleProducts, $cols)
                ->where("id = ".$__params['productId']);

            $rowHotelId = $this->_tabelleProducts->fetchAll($select)->toArray();
            if(count($rowHotelId) != 1)
                throw new nook_Exception($this->_error_zu_viele_datensaetze);

            // Kontrolle das der Produkt Code nur einmal existiert
            // im Hotel
            $cols = array(
                'id'
            );

            $select = $this->_tabelleProducts->select();
            $select
                ->from($this->_tabelleProducts, $cols)
                ->where("productCode = '".$__params['productCode']."'")
                ->where("property_id =".$rowHotelId[0]['property_id']);

            $rowsProduktCode = $this->_tabelleProducts->fetchAll($select)->toArray();

            // Produkt Code mehrfach vorhanden
            if(count($rowsProduktCode) > 1)
                throw new nook_Exception($this->_error_produkt_code_mehrmals_im_hotel);

            return false;
        }
        catch(nook_Exception $e){
            switch($e->getMessage()){
                case '323':
                    return 'Produkt Code wird im Hotel schon verwendet';
                    break;
                case '325':
                    return "Produktcode darf nicht leer sein";
                    break;
            }
        }
    }

    /**
     * Anlegen eines neuen Produktes eines Hotels
     *
     * @param $hotelId
     * @throws nook_Exception
     */
    public function newHotelProduct($hotelId)
    {

        $hotelId = trim($hotelId);
        $hotelId = (int) $hotelId;

        $insert = array(
            'property_id' => $hotelId
        );

        $kontrolle = $this->_tabelleProducts->insert($insert);
        if(!$kontrolle)
            throw new nook_Exception('anlegen neues Hotelprodukt fehlgeschlagen');

        return;
    }
	
}
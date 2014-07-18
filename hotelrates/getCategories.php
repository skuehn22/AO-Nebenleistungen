<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 01.09.11
 * Time: 12:08
 * To change this template use File | Settings | File Templates.
 */

class getCategories{

    private $_db_connect;
    private $_db_result;
    private $_categories = array();
    private $_propertycode;

    public function __construct($__propertycode){
        $this->_propertycode = $__propertycode;
        $this->_db_connect = mysqli_connect('localhost', 'db1154036-hotel', 'HuhnHotelsHuhn');
		mysqli_select_db($this->_db_connect, 'db1154036-hotels') or die( "keine Verbindung zur Datenbank");
    }

    public function start(){
        @date_default_timezone_set("GMT");
        $this->_findActivCategories();
        $this->_writeXML();
    }

    /**
     * Finde aktive Kategorien
     * die einer rate zugeordnet sind
     *
     */
    private function _findActivCategories(){

        $sql = "
            SELECT
                tbl_ota_rates_config.id AS RateId
                , tbl_ota_rates_config.rate_code AS RateCode
                , tbl_ota_rates_config.name AS RateName
                , tbl_categories.id AS CategoryId
                , tbl_categories.categorie_code AS CategoryCode
                , tbl_categories.categorie_name AS CategoryName
            FROM
                tbl_properties
                INNER JOIN tbl_ota_rates_config 
                    ON (tbl_properties.property_code = tbl_ota_rates_config.hotel_code)
                INNER JOIN tbl_categories 
                    ON (tbl_ota_rates_config.category_id = tbl_categories.id)
            WHERE (tbl_properties.property_code = '".$this->_propertycode."')
            ORDER BY RateId ASC, CategoryId ASC";

        $this->_db_result = $this->_db_connect->query($sql);

        $i = 0;
        while($row = $this->_db_result->fetch_array(MYSQLI_ASSOC)){
            $this->_categories[$i]['CategoryCode'] = $row['CategoryCode'];
            $this->_categories[$i]['CategoryName'] = $row['CategoryName'];
            $this->_categories[$i]['CategoryId'] = $row['CategoryId'];

            $this->_categories[$i]['RateCode'] = $row['RateCode'];
            $this->_categories[$i]['RateName'] = $row['RateName'];
            $this->_categories[$i]['RateId'] = $row['RateId'];

            $i++;
        }

        return;
    }

    private function _writeXML(){

        $newCategory = true;
        $anzahlDurchlaeufe = count($this->_categories);
        $anzahlDurchlaeufe--;

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0','utf-8');
        $writer->setIndent(8);

        $writer->startElement('Categories');

        for($i=0; $i<count($this->_categories); $i++){

            // Beginn der Kategorie
            if($newCategory == true){
                $writer->startElement('Category');
                    $writer->writeAttribute('CategoryId', $this->_categories[$i]['CategoryId']);
                    $writer->writeAttribute('CategoryName', $this->_categories[$i]['CategoryName']);
                    $writer->writeAttribute('CategoryCode', $this->_categories[$i]['CategoryCode']);
                    $writer->startElement('Rates');
            }

            $writer->startElement('Rate');
                $writer->writeAttribute('RateId', $this->_categories[$i]['RateId']);
                $writer->writeAttribute('RateName', $this->_categories[$i]['RateName']);
                $writer->writeAttribute('RateCode', $this->_categories[$i]['RateCode']);
            $writer->endElement();

            // Ende der Category ???
            if( ($i < $anzahlDurchlaeufe) and ($this->_categories[$i]['CategoryId'] == $this->_categories[$i+1]['CategoryId']) ){
                $newCategory = false;
                // echo "Durchlauf: ".$i."<br>";
            }
            else{
                $newCategory = true;
                $writer->endElement(); // End Rates
                $writer->endElement(); // End Category
            }
        }

        $writer->endElement(); // End Categories

        $xmlData = $writer->outputMemory(true);
        $this->_writeFile($xmlData);
        echo $xmlData;
    }

    private function _writeFile($__xml){
        file_put_contents("categories.xml",$__xml);

        return;
    }
    
}

if(array_key_exists('propertycode', $_GET)){
    $rates = new getCategories($_GET['propertycode']);
    $rates->start();
}
else{
    echo "missing property code";
}

?>
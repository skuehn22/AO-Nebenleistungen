<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 01.09.11
 * Time: 12:08
 * To change this template use File | Settings | File Templates.
 */

class getProperties{

    private $_db_connect;
    private $_db_result;
    private $_properties = array();

    public function __construct(){

        // Verbindung zur Datenbank
        $this->_db_connect = mysqli_connect('localhost', 'db1154036-hotel', 'HuhnHotelsHuhn');

        // Notiz:
        // $this->_db_connect->query("SET NAMES 'utf-8'");

		mysqli_select_db($this->_db_connect, 'db1154036-hotels') or die( "keine Verbindung zur Datenbank");
    }

    public function start(){
        @date_default_timezone_set("GMT");
        $this->_findActivProperties();
        $this->_writeXML();

    }

    private function _findActivProperties(){
        $sql = "select id, property_name as name, property_code as code from tbl_properties where aktiv = 3 and NOT property_code = 'neu' order by property_name asc";
        $this->_db_result = $this->_db_connect->query($sql);

        $i = 0;
        while($row = $this->_db_result->fetch_array(MYSQLI_ASSOC)){
            $this->_properties[$i] = $row;
            $i++;
        }

        return;
    }

    /**
     * schreibt den XMl - File
     */
    private function _writeXML(){
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0','utf-8');
        $writer->setIndent(4);

        $writer->startElement('properties');
                for($i=0; $i<count($this->_properties); $i++){
                    $writer->startElement('property');
                    $writer->writeElement('id', $this->_properties[$i]['id']);
                    $writer->writeElement('propertyname', $this->_properties[$i]['name']);
                    $writer->writeElement('propertycode', $this->_properties[$i]['code']);
                    $writer->endElement();
                }
        $writer->endElement();

        $xmlData = $writer->outputMemory(true);
        $this->_writeFile($xmlData);
        echo $xmlData;
    }

    private function _writeFile($__xml){
        file_put_contents("properties.xml",$__xml);

        return;
    }
    
}

$properties = new getProperties();
$properties->start();

?>
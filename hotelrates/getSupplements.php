<?php
/**
 * Übermittelt die Zusatzartikel eines Hotels
 * an das ASSD. Zusatzartikel die keinen Produkt Code haben erhalten einen
 * Produkt Code der sich aufbaut:
 *
 * "produkt" + 'tbl_products'.'id'
 *
 * Bsp: "produkt125"
 *
 * @author Stephan.Krauss
 * @since 17.09.12 11:59
 */

class supplements{

    private $_propertyCode = null;
    private $_supplements = array();
    private $_supplementsXml = null;

    // Kondition
    private $_condition_produkt_aktiv = 3; // Produkt ist 'aktiv' geschaltet

    /*** Database ***/
    private $_db_connect = null;

    public function __construct(){
        $this->_db_connect = mysqli_connect('localhost', 'db1154036-hotel', 'HuhnHotelsHuhn');
        mysqli_select_db($this->_db_connect, 'db1154036-hotels') or die( "keine Verbindung zur Datenbank");

        return;
    }

    /**
     * speichert den Property Code
     *
     * @param $__propertycode
     * @return supplements
     */
    public function setPropertyCode($__propertycode){
        $this->_propertyCode = $__propertycode;

        // ermittelt die Zusatzprodukte eines Hotels
        $this->_findSupplementsproperty();
        // baut XML Antwort
        $this->_buildXmlSupplements();

        return $this;
    }

    /**
     * Gibt die Zusatzartikel eines Hotels zurück
     *
     * @return array
     */
    public function getSupplements(){

        return $this->_supplementsXml;
    }

    /**
     * Sucht die Zusatzprodukte eines Hotels
     *
     * @return supplements
     */
    private function _findSupplementsproperty(){

        $sql = "
            SELECT
                tbl_products.id AS id
                 , `tbl_products`.`productCode` AS `code`
                 , `tbl_products`.`product_name` AS `name`
            FROM
                tbl_properties
            INNER JOIN tbl_products
            ON (tbl_properties.id = tbl_products.property_id)
            WHERE (tbl_properties.property_code = '".$this->_propertyCode."' and tbl_products.aktiv = ".$this->_condition_produkt_aktiv.")";

        $result = $this->_db_connect->query($sql);

        $i = 0;
        while($row = $result->fetch_array(MYSQLI_ASSOC)){

            // wenn kein Produkt Code vergeben wurde
            if(empty($row['code']))
                $row['code'] = "produkt".$row['id'];

            $this->_supplements[$i] = $row;
            $i++;
        }

       return $this;
    }

    /**
     * Baut die XML Antwort
     *
     */
    private function _buildXmlSupplements(){

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0','utf-8');
        $writer->setIndent(4);

        $writer->startElement('supplements');
            for($i=0; $i<count($this->_supplements); $i++){
                $writer->startElement('supplement');
                $writer->writeElement('id', $this->_supplements[$i]['code']);
                $writer->writeElement('name', $this->_supplements[$i]['name']);
                $writer->endElement();
            }
        $writer->endElement();

        $this->_supplementsXml = $writer->outputMemory(true);

        return;
    }

} // end class

if(array_key_exists('propertycode', $_GET)){
    $supplements = new supplements();
    $xmlSupplements = $supplements
        ->setPropertyCode($_GET['propertycode'])
        ->getSupplements();

    echo $xmlSupplements;
}
else{
    echo 'missing property code';
}

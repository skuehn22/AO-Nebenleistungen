<?php
/**
 * User: Stephan Krauss
 * Date: 02.04.12
 * Time: 17:31
 *
 * Traegt die Buchung des Users in die Tabelle 'xml_buchung' ein.
 * Sendet die Buchung ab.
 *
 */
 
class Front_Model_Buchungsuebersicht extends Pimple_Pimple{
    // private $_error_hotelbuchung_nicht_geloescht = 720;

    private $_db_front; // Datenbank Groups
    private $_db_hotel; // Datenbank Hotel


    public function __construct(){ 
        // Datenbanken
        $this->_db_front = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');

        // Dependency Injection Controller
        $this->offsetSet('HotelRatenverfuegbarkeit', function(){
            return new Front_Model_BuchungsuebersichtRatenverfuegbarkeit();
        });

        $this->offsetSet('HotelRateXmlBlock', function(){
                return new Front_Model_BuchungsuebersichtHotelbuchungXML();
            });

        return;
    }

    // NOTICE: Methode noch überarbeiten
    /**
     *
     *
     * @return void
     */
    public function buchenHotelprodukteWarenkorb(){

        // verändern Verfügbarkeit Rate
        $hotelbuchungRatenVerfuegbarkeit = $this->offsetGet('HotelRatenverfuegbarkeit');
        $hotelbuchungRatenVerfuegbarkeit->setTransferData($hotelbuchung);
        $hotelbuchungRatenVerfuegbarkeit->setVeraenderungVerfuegbarkeitRaten(true); // true = verringern

        // speichern der Hotelbuchung als XML
        $hotelbuchungXML = $this->offsetGet('HotelRateXmlBlock');
        $hotelbuchungXML
            ->setDebugModus(false)
            ->setTransferData($hotelbuchung);
        $hotelbuchungXML->saveXML();

    }


}

<?php
/**
 * 07.11.12 09:22
 * Ermitteln von Hoteldaten
 *
 *
 * @author Stephan Krauß
 */

class nook_ToolSuchparameterHotel {

    // Fehler
    private $_error_keine_suchparameter_vorhanden = 1090;

    /**
     * Ermittelt die Suchparameter einer
     * Hotelbuchung
     *
     * @return array
     * @throws nook_Exception     * @return array
     */
    public function getSuchparameterHotelsuche(){

        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $suchparameterHotelsuche = (array) $hotelsuche->getIterator();

        if(!is_array($suchparameterHotelsuche))
            throw new nook_Exception($this->_error_keine_suchparameter_vorhanden);

        return $suchparameterHotelsuche;
    }

    /**
     * Übernimmt eine Personenanzahl in die Suchparameter der Hotelsuche
     *
     *
     * @param $personen
     * @return nook_ToolSuchparameterHotel
     */
    public function setPersonenanzahlHotelsuche($personen){
        $hotelsuche = new Zend_Session_Namespace('hotelsuche');
        $hotelsuche->adult = $personen;

        return $this;
    }

} // end class

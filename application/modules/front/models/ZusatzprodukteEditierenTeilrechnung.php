<?php
/**
 * Ermittelt die bereits gebuchten Zusatzprodukte einer Teilrechnung
 *
 * + bestimmt Produkte der Teilrechnung
 * + ergaenzt gewahlte Produkte
 * + gibt die Produkte einer Teilrechnung zurück
 * + setzt die Teilrechnungs ID
 * + Ermittelt die Teilrechnungs ID an Hand der Suchparameter Hotelsuche
 *
 * @date 05.02.13 10:27
 * @author Stephan Krauß
 */

class Front_Model_ZusatzprodukteEditierenTeilrechnung extends nook_ToolModel
{

    // Fehler
    private $_error_kein_int = 1230;
    private $_error_keine_produkte_zur_teilrechnung_vorhanden = 1231;
    private $_error_keine_hotelprodukte_zum_abgleich_vorhanden = 1232;

    // Tabellen / Views
    private $_tabelleProduktbuchung = null;

    // Konditionen
    private $_condition_bereich_hotel = 6;

    protected $_teilrechnung_id = null;
    protected $_produkteTeilrechnung = array();
    protected $_personenAnzahlTeilrechnung = null;

    public function __construct ()
    {
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();
    }

    /**
     * @param $__id
     * @return Front_Model_ZusatzprodukteEditierenTeilrechnung
     * @throws nook_Exception
     */
    public function setTeilrechnungId ($__id)
    {

        $__id = (int) $__id;

        if(!$__id or empty($__id)) {
            throw new nook_Exception($this->_error_kein_int);
        }

        $this->_teilrechnung_id = $__id;

        return $this;
    }

    /**
     * Ergänzt die Produkte eines Hotels
     *
     * umd die Anzahl der gewählten
     * Produkte einer Teilrechnung.
     * Vergleich der ID der möglichen Produkte eines Hotels
     * mit den bereits gebuchten Produkten einer Teilrechnung
     *
     * + Kontrolle auf max. mögliche Personenanzahl der bereits gebuchten Produkte
     * + Ergänzen der bereits gebuchten Produkte um die möglichen Produkte des Hotels
     *
     * @param $__produkteEinesHotels
     * @return mixed
     */
    public function ergaenzeGewahlteProdukte (array $__produkteEinesHotels)
    {
        if(count($__produkteEinesHotels) == 0) {
            throw new nook_Exception($this->_error_keine_hotelprodukte_zum_abgleich_vorhanden);
        }

         // Kontrolle Personenanzahl der Zusatzprodukte Hotel
        $korrigierteProdukteEinerTeilrechnung = $this->_kontrollePersonenAnzahlDerProdukte();

        // merge gewählte Produkte und mögliche Produkte
        $__produkteEinesHotels = $this->_ergaenzeGewahlteProdukte($__produkteEinesHotels,$korrigierteProdukteEinerTeilrechnung);

        return $__produkteEinesHotels;
    }

    /**
     * Kontrolliert die Personenanzahl der gebuchten Produkte
     *
     * + Rechnet die maximale Anzahl der möglichen Produkte entsprechend des produkttyp / Personenanzahl
     * + Korrigiert bei Bedarf die Anzahl der Produkte in 'tbl_produktbuchung' der Teilrechnung
     * + Gibt die korrigierten und gebuchten Produkte aus
     *
     * @return array
     */
    private function _kontrollePersonenAnzahlDerProdukte ()
    {
        $kontrollePersonenAnzahlProduktbuchung = new Front_Model_KontrollePersonenAnzahlProduktbuchung();

        $korrigierteProdukteEinerTeilrechnung = $kontrollePersonenAnzahlProduktbuchung
            ->setTeilrechnungId($this->_teilrechnung_id)
            ->bestimmenDatenHotelbuchung()
            ->korrekturPersonenanzahlHotelbuchungenEinerTeilrechnung()
            ->getKorrigierteProdukteHotelbuchungEinerTeilrechnung();

        return $korrigierteProdukteEinerTeilrechnung;
    }

    /**
     * Ergänzen der bereits gebuchten Produkte einer Buchung.
     *
     * + Verschmelzen der noch möglichen Produkte eines Hotels mit
     * + den bereits gebuchten und in der Anzahl korrigierten Produkten
     * + markieren / checked bereits gewahlter verpflegungstypen
     * + verpflegungGewaehlt = 1 , im Template nicht ausgewahlt
     * + verpflegungGewaehlt = 2 , im Template ausgewaehlt
     *
     * @param array $produkteEinesHotels
     * @param array $korrigierteProdukteEinesHotels
     * @return array
     */
    private function _ergaenzeGewahlteProdukte (array $produkteEinesHotels, array $korrigierteProdukteEinesHotels)
    {
        for($j = 0; $j < count($produkteEinesHotels); $j++) {

            // Verpflegung wurde nicht gewaehlt
            if($produkteEinesHotels[ $j ]['verpflegung'] == 2){
                $produkteEinesHotels[$j]['verpflegungGewaehlt'] = 1;
            }

            for($i = 0; $i < count($korrigierteProdukteEinesHotels); $i++) {
                if($produkteEinesHotels[ $j ][ 'id' ] == $korrigierteProdukteEinesHotels[ $i ][ 'products_id' ]) {

                    // Korrektur Anzahl
                    $produkteEinesHotels[ $j ][ 'personenanzahl' ] = $korrigierteProdukteEinesHotels[ $i ][ 'anzahl' ];

                    // markieren / checked bereits gewahlter verpflegungstypen
                    if($produkteEinesHotels[ $j ]['verpflegung'] == 2){
                        $produkteEinesHotels[$j]['verpflegungGewaehlt'] = 2;
                    }

                }
            }
        }

        return $produkteEinesHotels;
    }

    /**
     * Ermittelt die Produkte einer Teilrechnung
     *
     * @return Front_Model_ZusatzprodukteEditierenTeilrechnung
     * @throws
     */
    private function  _bestimmeProdukteDerTeilrechnung ()
    {

        $where = "teilrechnungen_id = " . $this->_teilrechnung_id;

        $select = $this->_tabelleProduktbuchung->select();
        $select->where($where);

        // $query = $select->__toString();

        $rows = $this->_tabelleProduktbuchung->fetchAll($select)->toArray();

        try{
            if(count($rows) == 0) {
                throw new nook_Exception($this->_error_keine_produkte_zur_teilrechnung_vorhanden);
            }
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 3);
        }

        $this->_produkteTeilrechnung = $rows;

        return $this;
    }

    /**
     * Gibt die Produkte einer
     * Teilrechnung zurück
     *
     * @return array
     * @throws nook_Exception
     */
    public function getProdukteEinerTeilrechnung ()
    {
        $this->_bestimmeProdukteDerTeilrechnung();

        if(count($this->_produkteTeilrechnung) == 0) {
            throw new nook_Exception($this->_error_keine_produkte_zur_teilrechnung_vorhanden);
        }

        return $this->_produkteTeilrechnung;
    }

    /**
     * Bestimmt und speichert die Zusatzprodukte
     *
     * einer Teilrechnung
     * + bestimmt Personenanzahl der Teilrechnung
     * + Korrektur der Personenanzahl der Zusatzprodukte entsprechend Typ des Zusatzproduktes
     *
     * @return Front_Model_ZusatzprodukteEditierenTeilrechnung
     */
    public function bestimmeProdukteDerTeilrechnung ()
    {

        $this->_ermittelnPersonenAnzahlTeilrechnung();
        $this->_bestimmeProdukteDerTeilrechnung();

        return $this;
    }

    /**
     * Ermitteln der Personenanzahl einer Teilrechnung
     *
     * @return int
     */
    private function _ermittelnPersonenAnzahlTeilrechnung ()
    {
        $toolTeilrechnungPersonenanzahl = new nook_ToolTeilrechnungPersonenanzahl();
        $personenAnzahlTeilrechnung = $toolTeilrechnungPersonenanzahl
            ->setTeilrechnungId($this->_teilrechnung_id)
            ->ermittelnPersonenanzahlTeilrechnung()
            ->getPersonenanzahl();

        $this->_personenAnzahlTeilrechnung = $personenAnzahlTeilrechnung;

        return $personenAnzahlTeilrechnung;
    }

    /**
     * Ermittelt die ID der Teilrechnungs
     *
     *  Nummer einer Hotelbuchung aus den Parametern der Hotelsuche
     */
    public function getTeilrechnungsIdEinerHotelbuchung ()
    {

        $hotelSuche = new nook_ToolSuchparameterHotel();
        $suchParameterHotelsuche = $hotelSuche->getSuchparameterHotelsuche();

        $buchungsNummer = nook_ToolBuchungsnummer::findeBuchungsnummer();

        $toolTeilrechnungen = new nook_ToolTeilrechnungen();
        $idTeilrechnung = $toolTeilrechnungen
            ->getIdTeilrechnungZimmerbuchung(
                $buchungsNummer,
                $this->_condition_bereich_hotel,
                $suchParameterHotelsuche[ 'propertyId' ],
                $suchParameterHotelsuche[ 'from' ],
                $suchParameterHotelsuche[ 'adult' ],
                $suchParameterHotelsuche[ 'days' ]
            );

        return $idTeilrechnung;
    }

} // end class

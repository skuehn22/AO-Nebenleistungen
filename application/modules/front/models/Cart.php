<?php
/**
 * Allgemeine Verwaltungsfunktionen des
 * Warenkorbes.
 *
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 02.04.12
 * Time: 17:24
 * To change this template use File | Settings | File Templates.
 */

class Front_Model_Cart extends Pimple_Pimple
{

    private $_sessionId = null;
    private $_kundenId = null;
    private $_buchungsnummern = array();
    private $_userData = array();

    // Konditionen
    private $_condition_programm_status_gebucht = 3;
    private $_condition_zusatzprodukt_status_gebucht = 4;

    // Flags

    // Tabellen / Views / Datenbanken
    private $_db_front; // Datenbank Programme
    private $_tabelleProduktbuchung = null;
    protected $tabelleBuchungsnummer = null;

    // Fehler
    // private $_error = 620;

    public function __construct()
    {
        // Datenbank
        $this->_db_front = Zend_Registry::get('front');
        /** @var _tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $this->_tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

        return;
    }

    /**
     * @return Application_Model_DbTable_buchungsnummer
     */
    public function getTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return $this->tabelleBuchungsnummer;
    }

    /**
     * Ermitteln der Kunden Daten
     *
     * @return array
     */
    public function findUserId()
    {
        /** @var $auth Zend_Session_Namespace */
        $auth = new Zend_Session_Namespace('Auth');
        $kundenDaten = $auth->getIterator();

        // wenn Kunden ID unbekannt
        if (empty($kundenDaten['userId'])) {
            return '';
        } // Kunden ID bekannt
        else {
            return $kundenDaten['userId'];
        }
    }

    /**
     * baut ein Array mit Class Informationen zum
     * darstellen der Schrittfolge im Bestellprozess
     *
     * @param $__step
     * @return void
     */
    public function getAktiveStep($__bereich, $__step, array $__params)
    {
        $breadcrumb = new nook_ToolBreadcrumb();
        $navigation = $breadcrumb
            ->setBereichStep($__bereich, $__step)
            ->setParams($__params)
            ->getNavigation();

        return $navigation;
    }

    /**
     * Ermittelt die Programme des Warenkorbes.
     * Entweder über Session ID oder Kunden ID
     *
     * @return void
     */
    public function findBuchungsnummer()
    {
        $this
            ->_findSessionidOderKundenid()
            ->_findBuchungsnummern();

        return $this->_buchungsnummern;

    }

    /**
     * finden der Session ID oder Kunden ID des Kunden
     *
     */
    private function _findSessionidOderKundenid()
    {

        /** @var $auth Zend_Session_Namespace */
        $auth = new Zend_Session_Namespace('Auth');
        $kundenDaten = $auth->getIterator();

        if (array_key_exists('userId', $kundenDaten) and !empty($kundendaten['userId'])) {
            $this->_kundenId = $kundendaten['userId'];
        }

        $this->_sessionId = Zend_Session::getId();

        return $this;
    }

    private function _findBuchungsnummern()
    {
        $cols = array(
            'id',
            new Zend_Db_Expr("date as buchungsdatum")
        );

        /** @var $tabelleBuchungsnummer Zend_Db_Table_Abstract */
        $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();
        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols);

        if ($this->_kundenId)
            $select->where("kunden_id = ".$this->_kundenId);
        else
            $select->where("session_id = '".$this->_sessionId."'");

        $query = $select->__toString();
        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();
        $this->_buchungsnummern = $rows;

        return $this;
    }

    /**
     * Ermittelt den Gesamtpreis aller Produkte
     * die sich im Warenkorb befinden.
     *
     * + Preise bereits gebuchter Programme werden nicht gewertet
     *
     * @param $__artikelImWarenkorb
     * @return int|mixed
     */
    public function berechneGesamtpreis($__artikelImWarenkorb, $__produktTyp)
    {

        $preise = array(
            'totalPrice' => 0,
            'count' => 0
        );

        for ($i = 0; $i < count($__artikelImWarenkorb); $i++) {

            // Programmbuchung
            if ($__produktTyp == 'programmbuchung') {
                $programme = $__artikelImWarenkorb[$i]['programme'];

                foreach ($programme as $programm) {
                    $programm['preisvarianteGesamtpreis'] = str_replace(
                        ',',
                        '.',
                        $programm['preisvarianteGesamtpreis']
                    );
                    $preise['totalPrice'] += $programm['preisvarianteGesamtpreis'];
                    $preise['count']++;
                }
            }

            // Zusatzprodukte
            if ($__produktTyp == 'zusatzprodukt') {

                if ($__artikelImWarenkorb[$i]['status'] >= $this->_condition_zusatzprodukt_status_gebucht) {
                    continue;
                }

                $__artikelImWarenkorb[$i]['summeProduktPreis'] = str_replace(
                    ',',
                    '.',
                    $__artikelImWarenkorb[$i]['summeProduktPreis']
                );
                $preise['totalPrice'] += $__artikelImWarenkorb[$i]['summeProduktPreis'];
                $preise['count']++;
            }

        }

        return $preise;
    }

    /**
     * Löscht die Übernachtungen die ausgebucht sind
     *
     * oder nicht mehr verfügbar sind
     * + Übernahme der Buchungsnummern
     * + löschen ausgebuchte Übernachtungen in
     *   'tbl_hotelbuchung' und 'tbl_xml_buchung'
     *
     * @return Front_Model_Cart
     */
    public function loeschenAusgebuchteUebernachtungen(array $buchungsnummern)
    {

        $modelHotelbuchungAusgebucht = new Front_Model_HotelbuchungAusgebucht();

        for ($i = 0; $i < count($buchungsnummern); $i++) {
            $buchungsnummer = $buchungsnummern[$i]['id'];

            $modelHotelbuchungAusgebucht
                ->setBuchungsNummer($buchungsnummer)
                ->loeschenHotelbuchungen();
        }

        return $this;
    }

    /**
     * Ermittelt die Übernachtungsprodukte des Kunden.
     * Gibt die Produkte als nested Set zurück.
     * Einem Hotel sind die Raten zugeodnet
     *
     * @param $buchungsnummern
     * @return array
     */
    public function findHotelBuchungNested($buchungsnummern, Pimple_Pimple $pimple)
    {
        $modelHotelbuchung = new Front_Model_CartHotel();
        $modelHotelbuchung->setBuchungsnummern($buchungsnummern);
        $modelHotelbuchung->getHotelbuchungen();

        // ermittelt Daten des Warenkorb 'nested'
        $shoppingCartHotel = $modelHotelbuchung->buildNestedTemplateHotelbuchung();

        $frontModelBilderKategorie = new Front_Model_BilderKategorie($pimple);

        for($i=0; $i < count($shoppingCartHotel); $i++){
            $raten = $shoppingCartHotel[$i]['raten'];

            for($j=0; $j < count($raten); $j++){

                $shoppingCartHotel[$i]['raten'][$j]['kategorieBildId'] = $frontModelBilderKategorie
                    ->setKategorieId($raten[$j]['categoryId'])
                    ->steuerungErmittlungKategorieBildId()
                    ->getKategorieBildId();
            }
        }

        return $shoppingCartHotel;
    }

    /**
     * Ermittelt die Anzahl der Raten
     * sowie den Gesamtpreis.
     * gebuchten Hotels.
     *
     * @param $__shoppingCartHotel
     * @return
     */
    public function findHotelBuchungNestedPeise($__shoppingCartHotel)
    {
        $modelHotelbuchung = new Front_Model_CartHotel();
        $ratenPreise = $modelHotelbuchung->findProductsItems($__shoppingCartHotel);

        return $ratenPreise;
    }

    /**
     * Findet die gebuchten Zusatzprodukte eines Users
     *
     * @param $__buchungsnummern
     * @return mixed
     */
    public function findZusatzprodukteHotel($__buchungsnummern)
    {
        $modelZusatzprodukte = new Front_Model_WarenkorbShoppingcartZusatzprodukte();
        $shoppingCartZusatzprodukte = $modelZusatzprodukte->getShoppingcartZusatzprodukte($__buchungsnummern);

        return $shoppingCartZusatzprodukte;
    }

    /**
     * Bildet den Knoten der Hotelprodukte
     *
     * @param $shoppingCartHotelNested
     * @param $shoppingCartZusatzprodukte
     * @return mixed
     */
    public function kombiniereHotelUndProdukte($shoppingCartHotelNested, $shoppingCartZusatzprodukte)
    {

        for ($i = 0; $i < count($shoppingCartHotelNested); $i++) {

            $shoppingCartHotelNested[$i]['produkte'] = array();
            for ($j = 0; $j < count($shoppingCartZusatzprodukte); $j++) {

                // bilden Knoten der Hotelprodukte
                if ($shoppingCartHotelNested[$i]['teilrechnung_id'] == $shoppingCartZusatzprodukte[$j]['teilrechnungen_id']) {
                    $shoppingCartHotelNested[$i]['produkte'][] = $shoppingCartZusatzprodukte[$j];
                }
            }
        }

        return $shoppingCartHotelNested;
    }

    /**
     * Entfernt verwaiste Hotelprodukte aus der Anzeigeliste, wenn
     * keine Raten des Hotels mehr vorhanden sind
     *
     * @param $__hotelProdukte
     * @param $diffProdukte
     * @param $i
     */
    private function _entfernenVerwaisteHotelprodukteAusanzeigeliste($__hotelProdukte, $diffProdukte, $i)
    {
        foreach ($__hotelProdukte as $key => $hotelprodukt) {
            if ($hotelprodukt['teilrechnungen_id'] == $diffProdukte[$i]) {
                unset($__hotelProdukte[$key]);
            }
        }

        return $__hotelProdukte;
    }

}

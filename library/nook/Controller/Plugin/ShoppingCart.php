<?php
/**
* Ermittlung der Summe der aktiven Artikel des aktuellen Warenkorbes
*
* + Ermittelt die Anzahl aller Artikel im Warenkorb
* + Ermittelt die Summe der aktiven Programmbuchungen des aktuellen Warenkorbes
* + Ermittelt die Summe der aktiven Hotelbuchungen des aktuellen Warenkorbes
* + Ermittelt die Summe der aktiven Produktbuchungen des aktuellen Warenkorbes
* + Setzt die 'where' Klausel in den Abfragen
*
* @date 09.10.2013
* @file ShoppingCart.php
* @package plugins
*/
class Plugin_ShoppingCart extends Zend_Controller_Plugin_Abstract {

    // Konditionen
    private $condition_zaehler_aktuelle_buchung = 0;
    private $condition_warenkorb_vorgemerkt = 2;
    private $condition_im_warenkorb = 1;

    protected $buchungsnummer = null;

    /**
     * Ermittelt die Anzahl aller Artikel im Warenkorb
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
		$sessionId = Zend_Session::getId();
    	if(empty($sessionId)){
    		Zend_Registry::set('countShoppingCart', 0);
    		
    		return;
    	}

        $buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer();
        if(empty($buchungsnummer)){
            Zend_Registry::set('countShoppingCart', 0);

            return;
        }

        $this->buchungsnummer = $buchungsnummer;
        $anzahl = 0;

        // Programmbuchung
        $anzahl = $this->summeArtikelProgrammbuchung();

        // Hotelbuchung
        $anzahl += $this->summeArtikelHotelbuchung();

        // Produktbuchung
        $anzahl += $this->summeArtikelProduktbuchung();

    	Zend_Registry::set('countShoppingCart', $anzahl);
	}

    /**
     * Ermittelt die Summe der aktiven Programmbuchungen des aktuellen Warenkorbes
     *
     * @param $whereBuchungsnummer
     * @param $whereZaehlerAktuellebuchung
     * @param $whereStatusArtikelImWarenkorb
     * @return int
     */
    private function summeArtikelProgrammbuchung()
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();

        $select = $tabelleProgrammbuchung->select();
        $select->from($tabelleProgrammbuchung, $cols);
        $select = $this->erstellenWhere($select, 'programmbuchung');

        $rows = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Summe der aktiven Hotelbuchungen des aktuellen Warenkorbes
     *
     * @param $whereBuchungsnummer
     * @param $whereZaehlerAktuellebuchung
     * @param $whereStatusArtikelImWarenkorb
     * @return int
     */
    private function summeArtikelHotelbuchung()
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung();

        $select = $tabelleHotelbuchung->select();
        $select->from($tabelleHotelbuchung, $cols);
        $select = $this->erstellenWhere($select, 'hotelbuchung');

        $rows = $tabelleHotelbuchung->fetchAll($select);

        return $rows[0]['anzahl'];
    }

    /**
     * Ermittelt die Summe der aktiven Produktbuchungen des aktuellen Warenkorbes
     *
     * @param $whereBuchungsnummer
     * @param $whereZaehlerAktuellebuchung
     * @param $whereStatusArtikelImWarenkorb
     * @return int
     */
    private function summeArtikelProduktbuchung()
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $tabelleProduktbuchung = new Application_Model_DbTable_produktbuchung();

        $select = $tabelleProduktbuchung->select();
        $select->from($tabelleProduktbuchung, $cols);
        $select = $this->erstellenWhere($select, 'produktbuchung');

        $rows = $tabelleProduktbuchung->fetchAll($select)->toArray();

        return $rows[0]['anzahl'];
    }

    /**
     * Setzt die 'where' Klausel in den Abfragen
     *
     * @param Zend_Db_Table_Select $select
     * @return Zend_Db_Table_Select
     */
    private function erstellenWhere(Zend_Db_Table_Select $select, $buchungstabelle)
    {
        $whereBuchungsnummer = "buchungsnummer_id = ".$this->buchungsnummer;
        $whereZaehlerAktuellebuchung = "zaehler = ".$this->condition_zaehler_aktuelle_buchung;
        $whereStatusArtikelImWarenkorb = "status <> ".$this->condition_warenkorb_vorgemerkt;

        $whereAnzahlZimmer = "roomNumbers > 0";
        $whereAnzahlArtikel = "anzahl > 0";


        $select
            ->where($whereBuchungsnummer)
            ->where($whereStatusArtikelImWarenkorb)
            ->where($whereZaehlerAktuellebuchung);

        // Hotelbuchung
        if($buchungstabelle == 'hotelbuchung')
            $select->where($whereAnzahlZimmer);
        else
            $select->where($whereAnzahlArtikel);

        return $select;
    }
}
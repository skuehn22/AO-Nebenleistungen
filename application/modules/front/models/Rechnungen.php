<?php
/**
 * Das System trägt die Rechnungen in die Tabelle 'tbl_rechnungen' ein.
 *
 * @author Stephan Krauss
 * @date 28.03.2014
 * @file Rechnungen.php
 * @project HOB
 * @package front
 * @subpackage model
 */
class Front_Model_Rechnungen
{

    protected $pimple = null;

    protected $prefix_rechnungsnummer = null;

    protected $condition_artikelStatusGebucht = 3;

    protected $condition_mwst_uebernachtung = 0.07;
    protected $condition_rechnungsZaehler = 1;
    protected $condition_rechnung_gestellt = 1;

    protected $gesamtsummeRechnung = 0;
    protected $gesamtsummeHotelbuchungen = 0;
    protected $gesamtsummeProduktbuchung = 0;
    protected $gesamtsummeProgrammbuchung = 0;

    protected $mwst = array();

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_Rechnungen
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $pimple = $this->kontrollePimple($pimple);
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Kontrolliert den Inhalt des DIC
     *
     * @param Pimple_Pimple $pimple
     * @return Pimple_Pimple
     */
    protected function kontrollePimple(Pimple_Pimple $pimple)
    {
        $kontrollePimpleListe = array(
            'tabelleHotelbuchung' => 'object',
            'tabelleProduktbuchung' => 'object',
            'tabelleProducts' => 'object',
            'tabellePreise' => 'object',
            'tabelleProgrammbuchung' => 'object',
            'tabelleProgrammdetails' => 'object',
            'tabelleRechnungen' => 'object',
            'tabelleRechnungenMwst' => 'object',
            'aktuelleBuchungsnummer' => true,
            'aktuelleKundenId' => true,
            'buchungsnummer_id' => true,
            'zaehler' => true
        );

        foreach ($kontrollePimpleListe as $key => $value) {

            $test = 123;

            if (!$pimple->offsetExists($key))
                throw new nook_Exception('Element ' . $key . " nicht in DIC");

            if ($value === 'object') {
                if (!is_object($pimple[$key]))
                    throw new nook_Exception($key . ' ist kein Objekt');
            } elseif ($value === 'array') {
                if (!is_array($pimple[$key]))
                    throw new nook_Exception($key . ' ist kein Array');
            }
        }

        return $pimple;
    }

    /**
     * Steuert das eintragen der Rechnungen in 'tbl_rechnungen'
     *
     * @return Front_Model_Rechnungen
     * @throws Exception
     */
    public function steuerungEintragenRechnungen()
    {
        try {
            if (is_null($this->pimple))
                throw new nook_Exception('DIC fehlt');

            $this->ermittelnGesamtpreisHotelbuchungenEinerSession();
            $this->ermittelnMwstHotelbuchungenEinerSession($this->mwst);
            $this->ermittelnGesamtpreisZusatzprodukteEinerSession($this->mwst);

            $this->ermittelnGesamtpreisProgrammbuchungenEinerSession($this->mwst);

            $this->eintragenArtikelInTabelleRechnung();

            return $this;
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Ermittelt Gesamtpreis der Hotelbuchungen
     *
     * + Ermittelt Summe der Preise der Hotelbuchungen einer Session
     *
     * @return int
     * @throws nook_Exception
     */
    protected function ermittelnGesamtpreisHotelbuchungenEinerSession()
    {

        $cols = array(
            'nights',
            'roomNumbers',
            'personNumbers',
            'roomPrice',
            'personPrice'
        );

        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $aktuelleBuchungsnummer = $this->pimple['aktuelleBuchungsnummer'];

        $select = $tabelleHotelbuchung->select();
        $select
            ->from($tabelleHotelbuchung, $cols)
            ->where("buchungsnummer_id = " . $aktuelleBuchungsnummer);

        $query = $select->__toString();

        $hotelBuchungenDatensaetze = $tabelleHotelbuchung->fetchAll($select)->toArray();

        // Berechnung Gesamtpreis der Artikel Hotelbuchung
        if (count($hotelBuchungenDatensaetze) > 0) {

            // Prefix Rechnungsnummer
            $this->prefix_rechnungsnummer .= 'H';

            for ($i = 0; $i < count($hotelBuchungenDatensaetze); $i++) {

                // Berechnung Brutto Zimmerpreis
                if (($hotelBuchungenDatensaetze[$i]['roomPrice'] != 0) and ($hotelBuchungenDatensaetze[$i]['personPrice'] == 0)) {
                    $summeRechnung = $hotelBuchungenDatensaetze[$i]['nights'] * $hotelBuchungenDatensaetze[$i]['roomPrice'] * $hotelBuchungenDatensaetze[$i]['roomNumbers'];
                    $this->gesamtsummeRechnung += $summeRechnung;
                    $this->gesamtsummeHotelbuchungen += $summeRechnung;
                }

                // Berechnung Brutto Personenpreis
                if (($hotelBuchungenDatensaetze[$i]['roomPrice'] == 0) and ($hotelBuchungenDatensaetze[$i]['personPrice'] != 0)) {
                    $summeRechnung = $hotelBuchungenDatensaetze[$i]['nights'] * $hotelBuchungenDatensaetze[$i]['personPrice'] * $hotelBuchungenDatensaetze[$i]['personNumbers'];
                    $this->gesamtsummeRechnung += $summeRechnung;
                    $this->gesamtsummeHotelbuchungen += $summeRechnung;
                }

                // Fehler
                if (($hotelBuchungenDatensaetze[$i]['roomPrice'] == 0) and ($hotelBuchungenDatensaetze[$i]['personPrice'] == 0))
                    throw new nook_Exception('Zimmerpreis und Personenpreis nicht vorhanden');
            }
        }

        return $this->gesamtsummeRechnung;
    }

    /**
     * Ermitteln der Mwst aus den gebuchten Hotelbuchungen
     *
     * + Berechnung Netto Hotelbuchungen
     * + Mehrwertsteuer 7% für Übernachtungen
     *
     * @param $mwst
     * @return mixed
     */
    protected function ermittelnMwstHotelbuchungenEinerSession($mwst)
    {
        // Mehrwertsteuer 7%
        $nettoSummeHotelbuchungen = nook_ToolMehrwertsteuer::getNettobetrag($this->gesamtsummeHotelbuchungen, $this->condition_mwst_uebernachtung);
        $this->mwst[7]['mehrwertsteuer'] += $this->gesamtsummeHotelbuchungen - $nettoSummeHotelbuchungen;
        $this->mwst[7]['nettoBetrag'] += $nettoSummeHotelbuchungen;
        $this->mwst[7]['bruttoBetrag'] += $this->gesamtsummeHotelbuchungen;

        return $mwst;
    }

    /**
     * Ermittelt Datensaetze der Zusatzprodukte einer Session.
     *
     * @param $mwst
     * @return array
     */
    protected function ermittelnGesamtpreisZusatzprodukteEinerSession($mwst)
    {

        $cols = array(
            'summeProduktPreis',
            'products_id'
        );

        /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $aktuelleBuchungsnummer = $this->pimple['aktuelleBuchungsnummer'];

        $select = $tabelleProduktbuchung->select();
        $select
            ->from($tabelleProduktbuchung, $cols)
            ->where("buchungsnummer_id = " . $aktuelleBuchungsnummer);

        $query = $select->__toString();

        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $produkteBuchungenDatensaetze = $tabelleProduktbuchung->fetchAll($select)->toArray();

        // Berechnung Gesamtpreis der Zusatzprodukte einer Session
        if (count($produkteBuchungenDatensaetze) > 0) {

            for ($i = 0; $i < count($produkteBuchungenDatensaetze); $i++) {
                $summeProduktbuchung = $produkteBuchungenDatensaetze[$i]['summeProduktPreis'];
                $this->gesamtsummeRechnung += $summeProduktbuchung;
                $this->gesamtsummeProduktbuchung += $summeProduktbuchung;

                $mwst = $this->ermittelnMwstProduktbuchungenEinerSession($mwst, $produkteBuchungenDatensaetze[$i]['summeProduktPreis'], $produkteBuchungenDatensaetze[$i]['products_id']);
            }
        }

        return $mwst;
    }

    /**
     * Ermittelt Mwst eines Hotelproduktes und zurechnen zu Array Mwst
     *
     * + ermitteln Mwst - satz des Produktes
     * + aufsummieren des Mwst - Array
     *
     * @param array $mwst
     * @param $preis
     * @param $produktId
     * @return array
     * @throws nook_Exception
     */
    protected function ermittelnMwstProduktbuchungenEinerSession(array $mwst, $preis, $produktId)
    {
        $cols = array(
            'vat'
        );

        $whereId = "id = " . $produktId;

        /** @var  $tabelleProducts Application_Model_DbTable_products */
        $tabelleProducts = $this->pimple['tabelleProducts'];

        $select = $tabelleProducts->select();
        $select
            ->from($tabelleProducts, $cols)
            ->where($whereId);

        $query = $select->__toString();

        // Mwst - Satz des Produktes
        $rows = $tabelleProducts->fetchAll($select)->toArray();

        if (count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze falsch');

        $mwstSatz = $rows[0]['vat'];
        $mwstSatzRechnungsgroesse = $mwstSatz / 100;

        $nettoSummeProduktbuchung = nook_ToolMehrwertsteuer::getNettobetrag($preis, $mwstSatzRechnungsgroesse);

        $this->mwst[$mwstSatz]['mehrwertsteuer'] += $preis - $nettoSummeProduktbuchung;
        $this->mwst[$mwstSatz]['nettoBetrag'] += $nettoSummeProduktbuchung;
        $this->mwst[$mwstSatz]['bruttoBetrag'] += $preis;

        return $mwst;
    }

    /**
     * Ermittelt die Datensätze der
     * Programmbuchungen einer Session.
     * Ermittelt den Gesamtpreis aller
     * gebuchten Programmdatensätze
     * einer Session.
     *
     * @param $mwst
     * @return array
     */
    protected function ermittelnGesamtpreisProgrammbuchungenEinerSession($mwst)
    {

        $cols = array(
            'tbl_programme_preisvarianten_id',
            'anzahl',
            'zaehler'
        );

        /** @var Application_Model_DbTable_programmbuchung $tabelleProgrammbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $aktuelleBuchungsnummer = $this->pimple['aktuelleBuchungsnummer'];
        $zaehler = $this->pimple['zaehler'];

        $select = $tabelleProgrammbuchung->select();
        $select
            ->from($tabelleProgrammbuchung, $cols)
            ->where("buchungsnummer_id = " . $aktuelleBuchungsnummer);

        if($zaehler > 1){
            $letzteZaehler = $zaehler - 1;
            $select
                ->where("zaehler = ".$zaehler." or zaehler = ".$letzteZaehler)
                ->order('zaehler desc');
        }

        else
            $select->where("zaehler = ".$zaehler);

        $query = $select->__toString();

        $programmeBuchungenDatensaetze = $tabelleProgrammbuchung->fetchAll($select)->toArray();

        // ermitteln Differenz der momentanen zur letzten Buchung
        if($zaehler > 1)
            $programmeBuchungenDatensaetze = $this->ermittlungDifferenzProgrammbuchungen($programmeBuchungenDatensaetze, $zaehler);

        if (count($programmeBuchungenDatensaetze) > 0) {

            // Prefix Rechnungsnummer
            $this->prefix_rechnungsnummer .= 'P';

            for ($i = 0; $i < count($programmeBuchungenDatensaetze); $i++) {

                /** Zend_Db_Table_Abstract $preisProgramm */
                $preisProgramm = $this->pimple['tabellePreise'];

                $rows = $preisProgramm->find($programmeBuchungenDatensaetze[$i]['tbl_programme_preisvarianten_id'])->toArray();
                $preis = $rows[0]['verkaufspreis'];

                $preisProgrammbuchung = $preis * $programmeBuchungenDatensaetze[$i]['anzahl'];
                $this->gesamtsummeRechnung += $preisProgrammbuchung;
                $this->gesamtsummeProgrammbuchung += $preisProgrammbuchung;

                $mwst = $this->ermittelnMwstProgrammbuchungEinerSession($mwst, $preisProgrammbuchung, $programmeBuchungenDatensaetze[$i]['tbl_programme_preisvarianten_id']);
            }
        }

        return $mwst;
    }

    /**
     * Ermittelt die Differenz der Anzahl der gebuchten Programme einer Preisvariante der momentanen zuir vorhergehenden Buchung
     *
     * @param array $programmbuchungen
     * @return array
     */
    protected function ermittlungDifferenzProgrammbuchungen(array $programmbuchungen, $aktuellerZaehler)
    {
        $differenzProgrammbuchungen = array();

        for($i=0; $i < count($programmbuchungen); $i++){
            $programmbuchung = $programmbuchungen[$i];

            if($programmbuchung['zaehler'] == $aktuellerZaehler)
                $differenzProgrammbuchungen[] = $programmbuchung;
            else
                for($j=0; $j < count($differenzProgrammbuchungen); $j++){
                    if($differenzProgrammbuchungen[$j]['tbl_programme_preisvarianten_id'] == $programmbuchung['tbl_programme_preisvarianten_id'])
                        $differenzProgrammbuchungen[$j]['anzahl'] -= $programmbuchung['anzahl'];
                }
        }

        return $differenzProgrammbuchungen;
    }

    /**
     * Berechnet die Mwst einer Programmbuchung
     *
     * + ermitteln Programm ID
     * + Mwst eines Programmes
     * + aufsummieren des Array Mwst entsprechend der vorhandenen Mwst - Sätzen
     *
     * @param array $mwst
     * @param $preisProgrammbuchung
     * @param $preisvarianteId
     * @return array
     * @throws nook_Exception
     */
    protected function ermittelnMwstProgrammbuchungEinerSession(array $mwst, $preisProgrammbuchung, $preisvarianteId)
    {
        // Ermitteln der Programm-ID
        $colsPreisvariante = array(
            'programmdetails_id'
        );

        $wherePreisvarianteId = "id = " . $preisvarianteId;

        /** @var $tabellePreisvariante Zend_Db_Table */
        $tabellePreisvariante = $this->pimple['tabellePreise'];

        $select = $tabellePreisvariante->select();
        $select
            ->from($tabellePreisvariante, $colsPreisvariante)
            ->where($wherePreisvarianteId);

        $query = $select->__toString();

        $rows = $tabellePreisvariante->fetchAll($select)->toArray();

        if (count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze stimmt nicht');

        // Ermitteln der Mwst
        $colsProgrammdetails = array(
            'mwst'
        );

        $whereProgrammId = "id = " . $rows[0]['programmdetails_id'];

        /** @var $tabelleProgrammdetails Zend_Db_Table */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $select = $tabelleProgrammdetails->select();

        $select
            ->from($tabelleProgrammdetails, $colsProgrammdetails)
            ->where($whereProgrammId);

        $query = $select->__toString();

        $rows = $tabelleProgrammdetails->fetchAll($select)->toArray();

        if (count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze stimmt nicht');

        $mwstSatz = $rows[0]['mwst'];

        $nettoSummeProgrammbuchung = nook_ToolMehrwertsteuer::getNettobetrag($preisProgrammbuchung, $mwstSatz);

        $mwstSatz = $mwstSatz * 100;
        $mwstSatz = (int)$mwstSatz;
        $this->mwst[$mwstSatz]['mehrwertsteuer'] += $preisProgrammbuchung - $nettoSummeProgrammbuchung;
        $this->mwst[$mwstSatz]['nettoBetrag'] += $nettoSummeProgrammbuchung;
        $this->mwst[$mwstSatz]['bruttoBetrag'] += $preisProgrammbuchung;

        return $mwst;
    }

    /**
     * Tabellen 'tbl_rechnungen' und 'tbl_rechnungen_mwst' befüllen
     *
     * + Eintragen der gebuchten Artikel in Tabelle 'tbl_rechnungen'
     * + eintragen in 'tbl_rechnungen_mwst'
     *
     * @param $mwst
     * @return mixed
     */
    protected function eintragenArtikelInTabelleRechnung()
    {
        $cols = array(
            "kunde_id" => $this->pimple['aktuelleKundenId'],
            "buchungsnummer_id" => $this->pimple['aktuelleBuchungsnummer'],
            "rechnungsstatus" => $this->condition_rechnung_gestellt,
            "brutto" => $this->gesamtsummeRechnung,
            "rechnungsnummer" => $this->prefix_rechnungsnummer . "_" . $this->pimple['aktuelleBuchungsnummer'] . "_" . $this->pimple['zaehler'],
            "zaehler" => $this->pimple['zaehler']
        );

        /** @var $tabelleRechnungen Zend_Db_Table */
        $tabelleRechnungen = $this->pimple['tabelleRechnungen'];
        $rechnungId = $tabelleRechnungen->insert($cols);

        $this->eintragenMwstInTabelleRechnungenMwst($rechnungId);

        return $rechnungId;
    }

    /**
     * Trägt die Mwst in 'tbl_rechnungen_mwst' ein
     *
     * @param $rechnungId
     */
    protected function eintragenMwstInTabelleRechnungenMwst($rechnungId)
    {
        /** @var $tabelleRechnungenMwst Zend_Db_Table */
        $tabelleRechnungenMwst = $this->pimple['tabelleRechnungenMwst'];

        foreach ($this->mwst as $key => $value) {

            $this->mwst[$key]['bruttoBetrag'] = number_format($this->mwst[$key]['bruttoBetrag'], 2);
            if($this->mwst[$key]['bruttoBetrag'] == 0.00)
                continue;

            $input = array(
                'rechnungen_id' => $rechnungId,
                'mwst' => $key,
                'betrag' => $this->mwst[$key]['bruttoBetrag'],
                'netto' => $this->mwst[$key]['nettoBetrag']
            );

            $tabelleRechnungenMwst->insert($input);
        }

        return;
    }

    /**
     * @return int
     */
    public function getGesamtsummeRechnung()
    {
        return $this->gesamtsummeRechnung;
    }

    /**
     * @return int
     */
    public function getGesamtsummeHotelbuchungen()
    {
        return $this->gesamtsummeHotelbuchungen;
    }

    /**
     * @return int
     */
    public function getGesamtsummeProduktbuchung()
    {
        return $this->gesamtsummeProduktbuchung;
    }

    /**
     * @return int
     */
    public function getGesamtsummeProgrammbuchung()
    {
        return $this->gesamtsummeProduktbuchung;
    }

    /**
     * @return array
     */
    public function getMwst()
    {
        return $this->mwst;
    }
}

/******/
//include_once('../../../../autoload_cts.php');
//
//$test = new Application_Model_DbTable_programmbuchung();
//
//$pimple = new Pimple_Pimple();
//
//$pimple['tabelleHotelbuchung'] = function($c){
//    return new Application_Model_DbTable_hotelbuchung();
//};
//
//$pimple['tabelleProduktbuchung'] = function($c){
//    return new Application_Model_DbTable_produktbuchung();
//};
//
//$pimple['tabelleProgrammbuchung'] = function($c){
//    return new Application_Model_DbTable_programmbuchung();
//};
//
//$pimple['tabelleProducts'] = function($c){
//    return new Application_Model_DbTable_products(array('db' => 'hotels'));
//};
//
//$pimple['tabellePreise'] = function($c){
//    return new Application_Model_DbTable_preise();
//};
//
//$pimple['tabelleRechnungen'] = function($c){
//    return new Application_Model_DbTable_rechnungen();
//};
//
//$pimple['tabelleRechnungenMwst'] = function($c){
//    return new Application_Model_DbTable_rechnungenMwst();
//};
//
//$pimple['tabelleProgrammdetails'] = function($c){
//    return new Application_Model_DbTable_programmedetails();
//};
//
//$pimple['aktuelleBuchungsnummer'] = 1648;
//$pimple['aktuelleKundenId'] = 118;
//$pimple['buchungsnummer_id'] = 1648;
//$pimple['zaehler'] = 0;
//
//$frontModelRechnungen = new Front_Model_Rechnungen();
//$frontModelRechnungen
//    ->setPimple($pimple)
//    ->steuerungEintragenRechnungen();

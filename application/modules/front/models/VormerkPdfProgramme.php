<?php 
/**
* Ermittelt die Programme einer Vormerkung
*
* + Übernimmt die Werkzeuge
* + Steuert die Ermittlung der vorgemerkten Programme einer Buchung
* + Ermittelt die Stornofristen eines Programmes
* + Ermittelt die Bezeichnung einer Preisvariante eines Programmes
* + Ermitteln Preis einer preisvariante eines Programmes
* + Ermittelt die Programmsprache eines Programmes entsprechend der Anzeigesprache
* + Ermittelt den Programmnamen
* + Kürzt die Zeitangabe
* + Ermittlung komplettes Durchführungsdatum des Programmes mit Monatsname
* + Ermittelt
*
* @date 12.11.2013
* @file VormerkPdfProgramme.php
* @package front
* @subpackage model
*/
class Front_Model_VormerkPdfProgramme
{
    // Fehler
    private $error_anfangswert_nicht_vorhanden = 2430;
    private $error_anfangswert_falsch = 2431;

    // Informationen

    // Tabellen / Views
    /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
    private $tabelleBuchungsnummer = null;
    /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
    private $tabelleProgrammbuchung = null;

    // Tools
    /** @var $toolMonatsname nook_ToolMonatsnamen */
    private $toolMonatsname = null;
    /** @var $toolProgrammdetails nook_ToolProgrammdetails */
    private $toolProgrammdetails = null;
    /** @var $toolErmittlungAbweichendeStornofristenKosten nook_ToolErmittlungAbweichendeStornofristenKosten */
    private $toolErmittlungAbweichendeStornofristenKosten = null;

    // Konditionen
    private $condition_programm_vorgemerkt = 2;
    private $condition_zaehler_einer_vormerkung = 0;
    private $condition_status_vormerkung = 2;
    protected $condition_bereich_programme = 1;

    // Zustände

    protected $pimple = null;
    protected $buchungsnummer = null;
    protected $anzahlProgramme = 0;
    protected $anzeigeSpracheId = null;

    protected $vorgemerkteProgramme = array();
    protected $datenProgramme = array();

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->servicecontainer($pimple);
    }

    /**
     * Übernimmt die Werkzeuge
     *
     * @param Pimple_Pimple $pimple
     * @throws nook_Exception
     */
    private function servicecontainer(Pimple_Pimple $pimple)
    {
        $tools = array(
            'tabelleProgrammbuchung',
            'toolMonatsname',
            'tabelleBuchungsnummer',
            'toolProgrammdetails',
            'toolErmittlungAbweichendeStornofristenKosten'
        );

        foreach($tools as $value){
            if(!$pimple->offsetExists($value))
                throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);
            else
                $this->$value = $pimple[$value];
        }

        $this->pimple = $pimple;

        return;
    }

    /**
     * @param $buchungsnummer
     * @return Front_Model_VormerkPdfProgramme
     */
    public function setBuchungsNummer($buchungsnummer)
    {
        $buchungsnummer = (int) $buchungsnummer;

        $kontrolle = $this->tabelleBuchungsnummer->kontrolleValue('id', $buchungsnummer);
        if(!$kontrolle)
            throw new nook_Exception($this->error_anfangswert_falsch);

        $this->buchungsnummer = $buchungsnummer;

        return $this;
    }

    /**
     * Steuert die Ermittlung der vorgemerkten Programme einer Buchung
     *
     * + ermitteln Anzeigesprahe ID
     * + ermitteln vorgemerkte Programme
     * + ermitteln komplettes Datum
     * + Zeit
     * + Programmname
     * + Programmsprache
     *
     * @return Front_Model_VormerkPdfProgramme
     * @throws nook_Exception
     */
    public function steuerungErmittlungProgrammbuchungen()
    {
        if(is_null($this->tabelleBuchungsnummer))
            throw new nook_Exception($this->error_anfangswert_nicht_vorhanden);

        // Anzeigesprache
        $this->anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();

        // ermitteln vorgemerkte Programme
        $vorgemerkteProgramme = $this->vorgemerkteProgrammeEinerBuchung($this->buchungsnummer);
        $this->vorgemerkteProgramme = $vorgemerkteProgramme;
        $this->anzahlProgramme = count($vorgemerkteProgramme);

        $this->ermittelnParameterprogramm();

        return $this;
    }

    /**
     * Ermittelt die Stornofristen eines Programmes
     *
     * @param $programm
     * @return array
     */
    private function ermittelnStornofristenProgramm($programm)
    {
        $stornofristenProgramm = $this->toolErmittlungAbweichendeStornofristenKosten
            ->setProgrammId($programm['programmdetails_id'])
            ->ermittleStornofristenProgramm()
            ->getStornofristen();

        return $stornofristenProgramm;
    }

    /**
     * Ermittelt die Adresse eines Vertragspartners Programme
     *
     * @param $programmId
     * @return array
     */
    protected function adresseVertragspartner($programmId)
    {
        $frontModelVertragspartner = new Front_Model_Vertragspartner();
        $adresseVertragspartner = $frontModelVertragspartner
            ->setBereich($this->condition_bereich_programme)
            ->setProgrammId($programmId)
            ->steuerungErmittlungAdresseVertragspartner()
            ->getAdresse();

        return $adresseVertragspartner;
    }

    /**
     * Ermittelt die Bezeichnung einer Preisvariante eines Programmes
     *
     * @param $i
     * @param $programm
     * @return array
     */
    private function ermittelnBasisangabenPreisvariante($programm)
    {
        $toolPreisvarianteBasisAngaben = new nook_ToolPreisvarianteBasisAngaben($this->pimple);
        $basisangabenPreisvariante = $toolPreisvarianteBasisAngaben
            ->setPreisvarianteId($programm['tbl_programme_preisvarianten_id'])
            ->setSprachenId($this->anzeigeSpracheId)
            ->steuerungErmittlungBasisangabenPreisvariante()
            ->getBasisAngabenPreisvariante();

        return $basisangabenPreisvariante[0];
    }

    /**
     * Ermitteln Preis einer preisvariante eines Programmes
     *
     * @param $i
     * @param $programm
     * @return float
     */
    private function ermittelnPreisvariantePreis($programm)
    {
        $toolPreisvariante = nook_ToolPreisvariante::getInstance();
        $preisvariantePreis = $toolPreisvariante
            ->setPreisVarianteId($programm['tbl_programme_preisvarianten_id'])
            ->steuerungErmittelnPreisDerPreisvariante()
            ->getPreisVariantePreis();

        return $preisvariantePreis;
    }

    /**
     * Ermittelt die Programmsprache eines Programmes entsprechend der Anzeigesprache
     *
     * @param $i
     * @param $programm
     * @return mixed
     *
     * @param $i
     * @param $programm
     * @return bool|string
     */
    private function programmsprache($programm)
    {
        if($programm['sprache'] == 0)
            return false;
        else{
            $toolProgrammsprache = new nook_ToolProgrammsprache();
            $programmsprache = $toolProgrammsprache
                ->setAnzeigespracheId($this->anzeigeSpracheId)
                ->setProgrammsprache($programm['sprache'])
                ->setPimple($this->pimple)
                ->steuerungErmittlungProgrammsprache()
                ->getBezeichnungProgrammsprache();

            return $programmsprache;
        }
    }

    /**
     * Ermittelt den Programmnamen
     *
     * @param $i
     * @param $programm
     * @return mixed
     */
    private function programmname($programm)
    {
        $anzeigeSpracheId = nook_ToolSprache::ermittelnKennzifferSprache();
        $toolProgrammBasisAngaben = new nook_ToolProgrammBasisAngaben($this->pimple);
        $toolProgrammBasisAngaben
            ->setAnzeigesprache($anzeigeSpracheId)
            ->setProgrammdetailId($programm['programmdetails_id']);

        $basisangabenProgrammbeschreibung = $toolProgrammBasisAngaben
            ->steuerungErmittlungBasisangabenProgrammbeschreibung()
            ->getBasisangabenProgrammbeschreibung();

        return $basisangabenProgrammbeschreibung;
    }

    /**
     * Kürzt die Zeitangabe
     *
     * @param $i
     * @param $programm
     * @return mixed
     */
    private function zeit($programm)
    {
        if(is_null($programm['zeit']))
            return ' ';

        $zeit = nook_ToolZeiten::kappenZeit($programm['zeit'], 2);

        return $zeit;
    }

    /**
     * Ermittlung komplettes Durchführungsdatum des Programmes mit Monatsname
     *
     *
     * @param $i
     * @param array $programm
     * @return string
     */
    private function komplettesDatum(array $programm)
    {
        if($programm['datum'] == '0000-00-00')
            return ' ';

        $teileDatum = explode("-", $programm['datum']);
        $teileDatum[1] = (int) $teileDatum[1];


        $monatsname = $this->toolMonatsname
            ->setMonatsZiffer($teileDatum[1])
            ->getMonatsnameShort();

        $kompletteDatum = $teileDatum[2].". ".$monatsname.". ".$teileDatum[0];

        return $kompletteDatum;
    }

    /**
     * Ermittelt
     *
     * @param $buchungsnummer
     * @return array
     */
    private function vorgemerkteProgrammeEinerBuchung($buchungsnummer)
    {
        $select = $this->tabelleProgrammbuchung->select();

        $whereBuchungsnummer = "buchungsnummer_id = ".$buchungsnummer;
        $whereZaehler = "zaehler = ".$this->condition_zaehler_einer_vormerkung;
        $whereStatus = "status = ".$this->condition_status_vormerkung;

        $select
            ->where($whereBuchungsnummer)
            ->where($whereZaehler)
            ->where($whereStatus)
            ->order('datum asc')
            ->order('zeit asc');

        $query = $select->__toString();

        $rows = $this->tabelleProgrammbuchung->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * @return int
     */
    public function getAnzahlProgrammeVormerkung()
    {
        return $this->anzahlProgramme;
    }

    /**
     * @return array
     */
    public function getProgrammeVormerkung()
    {
        return $this->datenProgramme;
    }

    /**
     * Ergänzt die gebuchten Programme um weitere Angaben / abweichende Stornobedingungen
     */
    protected function ermittelnParameterprogramm()
    {


        if ($this->anzahlProgramme > 0) {

            for ($i = 0; $i < count($this->vorgemerkteProgramme); $i++) {
                $programm = $this->vorgemerkteProgramme[$i];

                // Id des Programmes
                $this->datenProgramme[$i]['programmId'] = $programm['programmdetails_id'];

                // komplettes Datum
                $kompletteDatum = $this->komplettesDatum($programm);
                $this->datenProgramme[$i]['komplettesDatum'] = $kompletteDatum;

                // Zeit
                $zeit = $this->zeit($programm);
                $this->datenProgramme[$i]['zeit'] = $zeit;

                // Programmname
                $basisangabenProgrammbeschreibung = $this->programmname($programm);
                $this->datenProgramme[$i]['programmname'] = $basisangabenProgrammbeschreibung['progname'];

                // Programmsprache
                $programmsprache = $this->programmsprache($programm);
                if ($programmsprache)
                    $this->datenProgramme[$i]['programmSprache'] = $programmsprache;

                // Preisvariante, Preis eines Programmes
                $preisvariantePreis = $this->ermittelnPreisvariantePreis($programm);
                $this->datenProgramme[$i]['programmpreis'] = $preisvariantePreis;

                // Gesamtpreis des Programmes
                $summeProgrammPreis = $preisvariantePreis * $programm['anzahl'];
                $this->datenProgramme[$i]['summeProgrammPreis'] = $summeProgrammPreis;

                // Anzahl
                $this->datenProgramme[$i]['anzahl'] = $programm['anzahl'];

                // Preis des Programmes
                $this->datenProgramme[$i]['programmpreis'] = $preisvariantePreis;

                // Basis Angaben Preisvariante
                $basisangabenPreisvariante = $this->ermittelnBasisangabenPreisvariante($programm);
                $this->datenProgramme[$i]['preisvariante'] = $basisangabenPreisvariante['preisvariante'];

                // Stornofristen eines Programmes
                $stornofristenProgramm = $this->ermittelnStornofristenProgramm($programm);
                if ($stornofristenProgramm)
                    $this->datenProgramme[$i]['stornofristen'] = $stornofristenProgramm;

                // Vertragspartner
                $adresseVertragspartner = $this->adresseVertragspartner($programm['programmdetails_id']);
                $this->datenProgramme[$i]['vertragspartner'] = $adresseVertragspartner;

                // abweichende Stornobedingungen
            }
        }
    }


}

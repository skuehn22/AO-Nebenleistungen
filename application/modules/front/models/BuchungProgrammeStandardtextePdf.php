<?php 
/**
* Ermittelt die Standardtexte für das Pdf einer Programmbuchung
*
* + Steuert die Übernahme der übergebenen Datensätze
* + Übernimmt Daten eines externen Models
* + Ermittelt die Kundendaten
* + Ermitteln der Überschrift in der jeweiligen Anzeigesprache
* + Aktuelles Datum und Ortsangabe
* + Ermittelt den Gruppenname
* + Gibt die Standardtextblöcke nach der Rechnung zurück
* + Gibt die Texte einer Erstbuchung zurück
*
* @date 03.07.13
* @file BuchungProgrammeStandardtextePdf.php
* @package front
* @subpackage model
*/
class Front_Model_BuchungProgrammeStandardtextePdf
{
    // Fehler
    private $error_anzahl_datensaetze_falsch = 1800;

    protected $pimple = null;
    protected $modelData;
    protected $anzeigesprache = null; // 1 = deutsch, 2 = englisch

    protected $statischeTexte = null;
    protected $datenRechnung = null;
    protected $kundenDaten = null;
    protected $kundenId = null;
    protected $buchungsNummerId = null;
    protected $zaehler = null;
    protected $gebuchteProgramme = null;
    protected $registrierungsnummer = null;

    public function __construct(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        $this->anzeigesprache = nook_ToolSprache::ermittelnKennzifferSprache();
    }

    /**
     * Steuert die Übernahme der übergebenen Datensätze
     *
     * @param $modelKundendatenUndBuchungsdaten
     * @return Front_Model_BuchungProgrammeStandardtextePdf
     */
    public function setProgrammDaten($modelKundendatenUndBuchungsdaten)
    {
        $this->importModelData($modelKundendatenUndBuchungsdaten);

        $this->statischeTexte = $this->modelData['_statischeFirmenTexte'];
        $this->datenRechnung = $this->modelData['_datenRechnungen'];
        $this->kundenDaten = $this->modelData['_kundenDaten'];
        $this->kundenId = $this->modelData['_kundenId'];
        $this->buchungsNummerId = $this->modelData['aktuelleBuchungsnummer'];
        $this->zaehler = $this->modelData['aktuellerZaehler'];
        $this->gebuchteProgramme = $this->modelData['_datenProgrammbuchungen'];

        return $this;
    }

    /**
     * @param $registrierungsnummer
     * @return Front_Model_BuchungProgrammeStandardtextePdf
     */
    public function setRegistrierungsnummer($registrierungsnummer)
    {
        $registrierungsnummer = (int) $registrierungsnummer;
        $this->registrierungsnummer = $registrierungsnummer;

        return $this;
    }

    /**
     * Übernimmt Daten eines externen Models
     *
     * @param $__fremdModel
     * @param bool $__modelName
     * @return Front_Model_BuchungProgrammeStandardtextePdf
     */
    protected function importModelData($modelKundendatenUndBuchungsdaten, $modelName = false){

        // löscht Transfer - Container
        if(empty($modelName)){
            $this->modelData = array();
            $this->modelData = $modelKundendatenUndBuchungsdaten->_exportModelData();
        }
        // neuer Datenbereich eines Model
        else
            $this->_modelData[$modelName] = $modelKundendatenUndBuchungsdaten->_exportModelData();

        return $this;
    }

    /**
     * Ermittelt die Kundendaten
     *
     * @return array
     */
    public function getKundenblock()
    {
        $kundendaten = array();
        $kundendaten[0] = $this->kundenDaten['company'];
        $kundendaten[1] = $this->kundenDaten['title']." ".$this->kundenDaten['firstname']." ".$this->kundenDaten['lastname'];
        $kundendaten[2] = $this->kundenDaten['street']." ".$this->kundenDaten['housenumber'];
        $kundendaten[3] = $this->kundenDaten['zip']." ".$this->kundenDaten['city'];

        return $kundendaten;
    }

    /**
     * Ermitteln der Überschrift in der jeweiligen Anzeigesprache
     *
     * @return string
     */
    public function getUeberschrift()
    {
        if($this->zaehler == 1)
            $ueberschrift = translate("Rechnung Nr.: HOB ");
        else
            $ueberschrift = translate("Rechnung - Veränderung Nr.: HOB ");

        $ueberschrift = translate($ueberschrift);
        $ueberschrift .= $this->registrierungsnummer."-".$this->zaehler;

        return $ueberschrift;
    }

    /**
     * Aktuelles Datum und Ortsangabe
     *
     * + Datum entsprechend des Anzeigesprache
     * + Ortsangabe
     *
     * @return string
     */
    public function getDatum()
    {
        if($this->anzeigesprache == 1){
            $monatsziffer = date("n");
            $toolMonatsnamen = new nook_ToolMonatsnamen();
            $monatsname = $toolMonatsnamen
                ->setMonatsZiffer($monatsziffer)
                ->getMonatsnamen();

            $datum = date("d").". ".$monatsname." ".date("Y");
        }
        else
            $datum = date("d. F Y");

        $ort = translate('Berlin, den');
        $ort .= " ".$datum;

        return $ort;
    }

    /**
     * Ermittelt den Gruppenname
     *
     * @return mixed
     * @throws nook_Exception
     */
    public function getGruppenname()
    {
        /** @var  $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $rows = $tabelleBuchungsnummer->find($this->buchungsNummerId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $gruppenname = $rows[0]['gruppenname'];

        return $gruppenname;
    }

    /**
     * Gibt die Standardtextblöcke nach der Rechnung zurück
     *
     * + Auswertung ob Erstrechnung
     * + oder Veränderung einer Rechnung
     *
     * @param $zaehler
     */
    public function getTextBloecke($zaehler = false)
    {
        $texte = $this->texteErstbuchung();

        return $texte;
    }

    /**
     * Gibt die Texte einer Erstbuchung zurück
     *
     * + Text Testphase
     * + Text Bank
     * + Text Bank Ausland
     * + Grußformel
     *
     * @return array
     */
    private function texteErstbuchung()
    {
        $texte = array();

        $teile = explode("\n",$this->statischeTexte['text_bank']);
        $texte = array_merge($texte, $teile);
        $texte[] = true;

        $teile = explode("\n",$this->statischeTexte['text_bic']);
        $texte = array_merge($texte, $teile);
        $texte[] = true;
        $texte[] = true;


        $teile = explode("\n",$this->statischeTexte['gruss']);
        $texte[] = $teile[0];
        $texte[] = true;
        $texte[] = $teile[2];


        return $texte;
    }

    private function texteBestandsbuchung()
    {
        $text = array();


        return $text;
    }

}

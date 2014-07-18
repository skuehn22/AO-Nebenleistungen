<?php

include_once('sparrow.php');
include_once('zugangswerte.php');

/**
 * Interface einlesen Adressdatensaetze
 *
 * @author Stephan.Krauss
 * @date 09.23.2014
 * @file index.php
 * @package schnittstelle
 */

class adressen{

    protected $datenbankZugang = array();
    protected $adressenXml = null;
    protected $sparrowDb = null;

    protected $mailAdresseStephan = "suppenterrine@gmail.com";
    protected $error_mail_from = 'info@guideberlin.com';

    protected $xmlReader = null;
    protected $spaltennamen = array();

    protected $anzahlDatensaetze = 0;
    protected $sollAnzahlDatensaetze = 0;
    protected $anzahlDatensaetzeStart = 0;
    protected $anzahlDatensaetzeEnde = 0;

    protected $datum = null;

    protected $flagAuthentication = false;
    protected $authentication = array();

    /**
     * Übernahme Datenbank Zugangswerte
     *
     * @param array $datenbankZugang
     */
    public function __construct(array $datenbankZugang)
    {
        $this->datenbankZugang = $datenbankZugang;
    }

    /**
     * Übernimmt Datenbankklasse
     *
     * @param Sparrow $sparrowDb
     * @return adressen
     */
    public function setSparrowDb(Sparrow $sparrowDb)
    {
        $this->sparrowDb = $sparrowDb;

        return $this;
    }

    /**
     * Passwort und Benutzerkennung
     *
     * @param array $authentication
     * @return $this
     */
    public function setAuthentication(array $authentication)
    {
        $this->authentication = $authentication;

        return $this;
    }

    /**
     * Übernimmt XML Daten
     *
     * @param $adressenXml
     * @return adressen
     */
    public function setAdressenXml($adressenXml)
    {
        $this->adressenXml = $adressenXml;

        return $this;
    }

    /**
     * Steuert das einlesen der XML Datei in die Tabelle
     *
     * @return adressen
     */
    public function steuerungEinlesenXml()
    {
        try{
            $this->lesenXmlDatei('adressen.xml');
            $this->iterateXmlNodes();
            $this->responseSuccess();
            $this->protokoll(true);
        }
        catch(Exception $e){
            $this->fehlerbehandlung($e);
            $this->responseFailure($e);
            $this->protokoll(false);
        }

        return $this;
    }

    /**
     * Trägt die Übermittlung der Datensätze in der Protokolltabelle 'mailing_protokoll' ein
     *
     * @param $erfolg
     * @return mixed
     */
    private function protokoll($erfolg)
    {
        if(!empty($erfolg))
            $erfolg = 2;
        else
            $erfolg = 1;

        $insertMailingProtokoll = array(
            'datum' => $this->datum,
            'erfolg' => $erfolg,
            'sollAnzahlDatensaetze' => $this->sollAnzahlDatensaetze,
            'anzahlDatensaetzeStart' => $this->anzahlDatensaetzeStart,
            'anzahlDatensaetzeEnde' => $this->anzahlDatensaetzeEnde
        );

        $result = $this->sparrowDb
            ->from('mailing_protokoll')
            ->insert($insertMailingProtokoll)
            ->execute();

        return $result;
    }

    /**
     * Fehlerbehandlung
     *
     * @param Exception $e
     */
    private function fehlerbehandlung(Exception $e)
    {
        $datum = date('Y_m_d', time());
        $errorDatei = 'daten_error_'.$datum.'.xml';

        copy('adressen.xml', $errorDatei);

        // senden einer Fehler Mail
        $mailText = "http://avail.herden.de \n";
        $mailText .= "Ein Fehler ist aufgetreten ! \n";
        $mailText .= "Bitte Fehlerdatei '".$errorDatei."' kontrollieren. \n";
        $mailText .= "Datum: ".date("d.m.Y H:i:s")." \n";
        $mailText .= "Felhercode: ".$e->getCode()."\n";
        $mailText .= "Fehlermeldung: ".$e->getMessage()."\n";
        $mailText .= "Zeile: ".$e->getLine();

        // sendet Mail an Herden
        mail($this->error_mail_from, 'Fehler http://address.herden.de',$mailText, "From: ".$this->error_mail_from);

        // senden Mail an Handy Stephan
        mail($this->mailAdresseStephan, 'Fehler Schnittstelle Adressen','Fehler Schnittstelle Mailing. Datum: '.date("d.m.Y H:i:s"), "From: ".$this->error_mail_from);

        return;
    }

    /**
     * Gibt als Response den Erfolg zurück
     */
    private function responseSuccess()
    {
        $response = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $response .= "<RS_AssdBookInfo>\n";
        $response .= "<Success/>\n";
        $response .= "</RS_AssdBookInfo>\n";

        echo $response;
    }

    /**
     * @param Exception $e
     */
    private function responseFailure(Exception $e)
    {
        $response = "<?xml version='1.0' encoding='UTF-8'?>\n<RS_AssdBookInfo>\n";
        $response .= "<Error Code='".$e->getLine()."'>".$e->getMessage()."</Error>\n";
        $response .= "</RS_AssdBookInfo>\n";

        echo $response;
    }

    /**
    * Setzt den Dateinamen der XML Datei
    * Erstellt den XML - Reader
    *
    * @param $__xmlFileName
    * @return void
    */
    protected function lesenXmlDatei($xmlFileName){

        // bilden XML - Reader
        $xmlReader = new XMLReader();

        // öffen Datei
        $xmlReader->open($xmlFileName);

        // lesen XML - Datei
        $this->xmlReader = $xmlReader;

        return;
    }

    /**
    * Durchläuft die XML Knoten
    * Prüft die Codes der Knoten
    * Differenzierung der Aktionen
    *
    * @return void
    */
    private function iterateXmlNodes(){

        while($this->xmlReader->read()){

            // lesen Datum / Uhrzeit
            if($this->xmlReader->name == 'RQ_AssdBookInfo' and XMLReader::ELEMENT){
                $this->datum = $this->xmlReader->getAttribute('TimeStamp');
            }
            // lesen Benutzerkennung / Passwort
            elseif( ($this->xmlReader->name == 'Authentication') and ($this->xmlReader->nodeType != XMLReader::END_ELEMENT) and ($this->xmlReader->nodeType == XMLReader::ELEMENT) ){

                $UserName = false;
                $Password = false;

                // innere Schleife für Benutzerkennung und Passwort
                while($this->xmlReader->read()){

                    // Benutzerkennung
                    if( ($this->xmlReader->name == 'UserName') and ($this->xmlReader->nodeType != XMLReader::END_ELEMENT) ){
                        $this->xmlReader->read();
                        $UserName = $this->xmlReader->value;
                    }

                    // Passwort
                    if( ($this->xmlReader->name == 'Password') and ($this->xmlReader->nodeType != XMLReader::END_ELEMENT) ){
                        $this->xmlReader->read();
                        $Password = $this->xmlReader->value;
                    }

                    // Kontrolle UserName und Password
                    if($UserName == $this->authentication['UserName'] and $Password == $this->authentication['Password'])
                        $this->flagAuthentication = true;

                    // Ende der inneren Schleife
                    if($this->flagAuthentication)
                        break;

                    // End - Element Authentication
                    if(($this->xmlReader->name == 'Authentication') and ($this->xmlReader->nodeType == XMLReader::END_ELEMENT))
                        break;
                }

                // War Authentification erfolgreich ?
                if(empty($this->flagAuthentication))
                    throw new Exception('Authentification fehlgeschlagen');

            }
            // lesen Spaltennamen der Tabelle
            elseif($this->xmlReader->name == 'Cols' and XMLReader::ELEMENT){

                if($this->flagAuthentication === false)
                    throw new Exception('Authentication fehlt');

                // Soll Anzahl Datensaetze
                $this->sollAnzahlDatensaetze = $this->xmlReader->getAttribute('rowCountAll');

                // Datensaetze Start
                $this->anzahlDatensaetzeStart = $this->xmlReader->getAttribute('rowCountDocStart');

                // Datensetze Ende
                $this->anzahlDatensaetzeEnde = $this->xmlReader->getAttribute('rowCountDocEnd');

                $this->ermittelnSpaltennamen();
            }
            // einlesen eines Datensatzes
            elseif($this->xmlReader->name == 'Tr' and XMLReader::ELEMENT){
                if($this->flagAuthentication === false)
                    throw new Exception('Authentication fehlt');

                $this->anzahlDatensaetze++;
                $this->filternAdressdatensatz();
            }

        }

        // Berechnung übernommene Datensätze
        $anzahlUebermittelteDatensaetze = ($this->anzahlDatensaetzeEnde - $this->anzahlDatensaetzeStart) + 1;

        // wenn die Anzahl der übermittelten Datensätze mit der Anzahl der eingelesenen Datensätze nicht übereinstimmt
        if($this->anzahlDatensaetze <> $anzahlUebermittelteDatensaetze)
            throw new Exception('Datensaetze, Soll Anzahl: '.$anzahlUebermittelteDatensaetze." eingelesen: ".$this->anzahlDatensaetze);

        return;
    }

    /**
     * Speichert Adressdatensatz in Tabelle 'mailing'
     *
     * @param array $adressDatenSatz
     */
    private function speichernDatenInTabelle(array $adressDatenSatz)
    {
        $sql = $this->sparrowDb->from('mailing')->insert($adressDatenSatz)->execute();

        return;
    }

    /**
     * Ermittelt Spaltennamen aus der ersten zeile der XML Datei
     */
    private function ermittelnSpaltennamen()
    {
        $flagSpaltenname = false;
        $spaltennamen = array();

        while($this->xmlReader->read()){
            if($this->xmlReader->name == 'Col' and XMLReader::ELEMENT)
                $flagSpaltenname = true;

            if( ($flagSpaltenname === true) && ($this->xmlReader->nodeType == XMLReader::TEXT) ){
                $spaltenname = $this->xmlReader->value;
                $spaltenname = strtolower($spaltenname);
                $spaltennamen[$spaltenname] = '';

                $flagSpaltenname = false;
            }

            if( ($this->xmlReader->name == 'Cols') and ($this->xmlReader->nodeType == XMLReader::END_ELEMENT) ){
                $this->spaltennamen = $spaltennamen;

                break;
            }
        }

        return;
    }

    /**
     * Filtert Adressdatensatz aus
     */
    private function filternAdressdatensatz()
    {

        $spaltenInhalte = array();
        $adressDatenSatz = array();

        $j = 0;
        $flagSpalteninhalt = false;

        while($this->xmlReader->read()){

            if($this->xmlReader->name == 'Td' and XMLReader::ELEMENT)
                $flagSpalteninhalt = true;

            if( ($flagSpalteninhalt === true) and ($this->xmlReader->nodeType == XMLReader::TEXT) ){
                $spaltenInhalte[$j] = $this->xmlReader->value;

                $flagSpalteninhalt = false;
                $j++;
            }

            // kombination von Spalteninhalt mit den bereits ermittelten Spaltennamen
            if( ($this->xmlReader->name == 'Tr') and ($this->xmlReader->nodeType == XMLReader::END_ELEMENT) ){

                $i = 0;
                foreach($this->spaltennamen as $key => $spaltenInhalt){
                    $adressDatenSatz[$key] = $spaltenInhalte[$i];
                    $i++;
                }

                // Korrektur der Daten
                $adressDatenSatz = $this->korrekturInhaltSpalten($adressDatenSatz);

                // speichern Datensatz
                $this->speichernDatenInTabelle($adressDatenSatz);
                break;
            }
        }

        return;
    }

    /**
     * Formatiert Inhalte des Adressdatensatzes
     *
     * @param array $adressDatenSatz
     * @return array
     */
    private function korrekturInhaltSpalten(array $adressDatenSatz)
    {
        $datumsFormate = array(
            'last_stay',
            'cdate',
            'wdate',
            'arrival',
            'departure',
            'angelegt_assd',
            'letztebearb_ma',
            'anfragevom'
        );

        foreach($adressDatenSatz as $key => $inhalt){
            if($inhalt == 'NULL')
                $inhalt = null;

            if(in_array($key, $datumsFormate)){
                $datumUhrzeit = explode(' ',$inhalt);
                $datum = explode('.',$datumUhrzeit[0]);

                $inhalt = $datum[2]."-".$datum[1]."-".$datum[0]." ".$datumUhrzeit[1];
            }

            $inhalt = utf8_decode($inhalt);
            $adressDatenSatz[$key] = $inhalt;
        }

        return $adressDatenSatz;
    }
}

// speichern Datei
$fp = fopen('adressen.xml','w');
fputs($fp, $_POST['address']);
fclose($fp);

// Sicherung Datei
$datumXml = date('Y_m_d_H_i_s',time());
copy('adressen.xml',$datumXml.'.xml');

// einlesen Datensaetze
$sparrowDb = new  Sparrow();
$sparrowDb->setDb($datenbankZugang);

$adressenObj = new adressen($datenbankZugang);
$adressenObj
    ->setSparrowDb($sparrowDb)
    ->setAuthentication($authentication)
    ->steuerungEinlesenXml();
<?php

/**
 * Versendet Text Mail in verschiedenen
 * Varianten
 *
 * @author Stephan.Krauss
 * @date 17.01.13
 * @file ToolMail.php
 */

class nook_ToolMail{

    // Tabellen / Views
    private $_tabelleTextbausteine = null;
    private $_tabelleAdressen = null;

    // Fehler
    private $_error_mail_nicht_versandt = 1170;
    private $_error_kein_standardmail_vorhanden = 1171;
    private $_error_keine_kundendaten_vorhanden = 1172;
    private $_error_name_kontrollvariable_unbekannt = 1173;

    // Konditionen
    private $_condition_kennung_platzhalter_beginn = "{";
    private $_condition_kennung_platzhalter_ende = "}";
    private $_condition__kennungKontrollLink = "{kontrollLink}";

    protected  $_mailVariante = null;
    protected  $_kundenId = null;
    protected  $_spracheId = null;
    protected  $_kundenDaten = array();
    protected  $_kontrollCode = null;
    protected  $_mailText = null;
    protected  $_mailBetreff = null;
    protected  $_kontrollLink = null;
    protected  $_nameKontrollVariable = null;

    public function __construct(){
        /** @var _tabelleTextbausteine Application_Model_DbTable_textbausteine */
        $this->_tabelleTextbausteine = new Application_Model_DbTable_textbausteine();
        /** @var _tabelleAdressen Application_Model_DbTable_adressen */
        $this->_tabelleAdressen = new Application_Model_DbTable_adressen();
    }

    /**
     * @param $__kundenId
     * @return nook_MailLand
     */
    public function setKundenId($__kundenId){
        $this->_kundenId = $__kundenId;

        $this->_bestimmeKundenDaten(); // Kundendaten
        $this->_setSpracheId(); // Sprache
        $this->_setzeKontrollCodeKunde(); // Kontrollcode

        return $this;
    }


    /**
     * Übernimmt den Kontroll Link
     *
     * @param $__kontrollLink
     * @return nook_ToolMail
     */
    public function setKontrollLink($__kontrollLink = false){

        if(empty($__kontrollLink))
            return $this;

        if(empty($this->_nameKontrollVariable))
            throw new nook_Exception($this->_error_name_kontrollvariable_unbekannt);

            $config = new nook_ToolKonfiguration();
        $server = $config->getKonfigurationsVariable('server','server');

        $kontrollLink = "http://".$server.$__kontrollLink."/".$this->_nameKontrollVariable."/".$this->_kontrollCode;

        $this->_kontrollLink = $kontrollLink;

        return $this;
    }

    /**
     * Setzt den Namen der Kontrollvariable
     * im Link der Mail
     *
     * @param $__nameKontrollVariable
     * @return nook_ToolMail
     */
    public function setNameKontrollVariable($__nameKontrollVariable = false){

        if(empty($__nameKontrollVariable))
            return $this;

        $this->_nameKontrollVariable = $__nameKontrollVariable;

        return $this;
    }

    /**
     * Setzt Kontrollcode des Kunden
     *
     * @return nook_ToolMail
     */
    private function _setzeKontrollCodeKunde(){

        // bestimmen Kontroll Code
        $toolKontrollcode = new nook_ToolKontrollcode();
        $kontrollCode = $toolKontrollcode->getKontrollcode();

        // eintragen Kontrollcode in Datensatz Kunde
        $update = array(
            'controlcode' => $kontrollCode
        );

        $where = "id = ".$this->_kundenDaten['id'];

        $this->_tabelleAdressen->update($update, $where);

        $this->_kontrollCode = $kontrollCode;

        return $this;
    }

    /**
     * Bestimmen der Kundendaten mittels
     * Kunden ID
     *
     * @return nook_ToolMail
     */
    private function _bestimmeKundenDaten(){
        $kundenDaten = $this->_tabelleAdressen
                           ->find($this->_kundenId)
                           ->toArray();

        if(count($kundenDaten) <> 1)
            throw new nook_Exception($this->_error_keine_kundendaten_vorhanden);

        $this->_kundenDaten = $kundenDaten[0];

        return $this;
    }

    /**
     * Ermitteln der Schriftwechselsprache
     *
     * @return nook_MailLand
     */
    private function _setSpracheId(){
        $this->_spracheId = $this->_kundenDaten['schriftwechsel'];

        return $this;
    }

    /**
     * Typ der Mail.
     * Standard Anmeldemail, ...
     *
     * @param $__mailVariante
     * @return nook_MailLand
     * @throws nook_Exception
     */
    public function setMailVariante($__mailVariante){
        $this->_mailVariante = $__mailVariante;

        return $this;
    }

    /**
     * Holt den Textbaustein.
     * Ersetze Platzhalter
     * speicher Text ab
     *
     * @return nook_ToolMail
     */
    private function _holeTextbaustein(){

        // hole Textbaustein
        $select = $this->_tabelleTextbausteine->select();
        $select
            ->where("blockname = '".$this->_mailVariante."'")
            ->where("sprache_id = ".$this->_spracheId);

        $rows = $this->_tabelleAdressen->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->_error_kein_standardmail_vorhanden);

        $mailText = $rows[0]['text'];

        $rows[0]['blockname'] = str_replace('_',' ', $rows[0]['blockname']);
        $this->_mailBetreff = $rows[0]['blockname'];

        // ersetzen Platzhalter
        foreach($this->_kundenDaten as $key => $ersetze){
            $suche = $this->_condition_kennung_platzhalter_beginn.$key.$this->_condition_kennung_platzhalter_ende;
            $mailText = str_replace($suche, $ersetze, $mailText);
        }

        // setzen Kontrollink
        if(!empty($this->_kontrollLink))
            $mailText = str_replace($this->_condition__kennungKontrollLink, $this->_kontrollLink, $mailText );


        $this->_mailText = $mailText;

        return $this;
    }

    /**
     * Versendet ein Mail
     */
    private function _sendMail(){

        $mailFrom = nook_ToolKonfiguration::getKonfigurationsVariable('debugModus', 'from');

        $mail = new Zend_Mail('UTF-8');

        $kontrolle = $mail
            ->setFrom($mailFrom)
            ->setSubject($this->_mailBetreff)
            ->addTo($this->_kundenDaten['email'])
            ->setBodyHtml($this->_mailText)
            ->setBodyText($this->_mailText)
            ->send();

        if(empty($kontrolle))
            throw new nook_Exception($this->_error_mail_nicht_versandt);


        return $this;
    }

    /**
     * Versendet das Mail gibt
     * ein Kontroll Flag zurück
     *
     * @return bool
     */
    public function sendMail(){

        $this
            ->_holeTextbaustein()
            ->_sendMail();

        return;
    }

}
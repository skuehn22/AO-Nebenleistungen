<?php
/**
 * 31.07.12 10:42
 * Fehlerbereich: 750
 *
 * Der Kunde bestaetigt die Buchung von Produkten.
 * Der Buchungsstatus wird geandert.
 * Produkte können einen Superuser zugeordnet werden.
 *
 * @author Stephan Krauß
 */
 
class Front_Model_WarenkorbSetOrderStatus {

    private $_condition_agb_bestaetigt = 'agb';
    private $_condition_status_kundenwunsch = 2;
    private $_condition_newsletter_empfangen = 2;

    private $_error_agb_nicht_gewaehlt = 750;
    private $_error_buchungsnummer_nicht_vorhanden = 751;
    private $_error_user_id_nicht_vorhanden = 752;

    private $_buchungsnummer = null;
    private $_userId = null;
    private $_superUser = null;

    /**
     * Kontrolliert ob die AGB bestaetigt wurden
     *
     * @param $__buchungsbestaetigung
     * @return
     */
    public function checkDatenBuchungsbestaetigung($__buchungsbestaetigung){

        if($__buchungsbestaetigung['agb'] != $this->_condition_agb_bestaetigt)
            throw new nook_Exception($this->_error_agb_nicht_gewaehlt);

        return;
    }


    /**
     * Speichern Bestellstatus der gewählten Programme des Kunden.
     * Wenn nötig zuordnen eines Superusers
     * Wenn nötig speichern Newsletterempfang
     *
     * @param $__buchungsbestaetigung
     * @return void
     */
    public function setzeZusatzinformationenBuchung($__buchungsbestaetigung){

        // gibt es die Buchungsnummer ???
        if( $this->_buchungsnummer = nook_ToolBuchungsnummer::findeBuchungsnummer() ){
            $this->_setzeBestellStatus();

            // eintragen Newsletter
            if($__buchungsbestaetigung['newsletter'] == 'newsletter')
                $this->_eintragenEmpfangNewsletter();
        }
        else
            throw new nook_Exception($this->_error_buchungsnummer_nicht_vorhanden);


        return;
    }

    /**
     * Setzt die produkte die zur Buchungsnummer gehören
     * auf 2 = 'kundenwunsch'
     *
     */
    private function _setzeBestellStatus(){
        $tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung(array('db' => 'front'));
        $tabelleProgrammbuchung->update(array('status' => $this->_condition_status_kundenwunsch), 'buchungsnummer_id = '.$this->_buchungsnummer);

        $tabelleHotelbuchung = new Application_Model_DbTable_hotelbuchung(array('db' => 'front'));
        $tabelleHotelbuchung->update(array('status' => $this->_condition_status_kundenwunsch), 'buchungsnummer_id = '.$this->_buchungsnummer);

        $tabelleXmlBuchung = new Application_Model_DbTable_xmlBuchung(array('db' => 'front'));
        $tabelleXmlBuchung->update(array('status' => $this->_condition_status_kundenwunsch), 'buchungsnummer_id = '.$this->_buchungsnummer);



        return;
    }

    /**
     * Registrieren das der Kunde die
     * Zusendung eines Newsletter wünscht
     *
     * qreturn
     */
    private function _eintragenEmpfangNewsletter(){
        $this->_userId = nook_ToolUserId::bestimmeKundenIdMitSession();
        if(empty($this->_userId))
            throw new nook_Exception($this->_error_user_id_nicht_vorhanden);

        $tabelleKunde = new Application_Model_DbTable_adressen(array('db' => 'front'));
        $tabelleKunde->update(array('newsletter' => $this->_condition_newsletter_empfangen), 'id = '.$this->_userId);

        return;
    }

    /**
     * Wenn eine Fremdbuchung vorliegt
     * wird der Ausführende / Superuser der
     * Buchung zugeordnet.
     *
     * @return void
     */
    private function _speichereSuperuserZurBuchung(){
        $tabelleFremdbuchung = new Application_Model_DbTable_fremdbuchung(array('db' => 'front'));

        $insert = array();
        $insert['buchungsnummer'] = $this->_buchungsnummer;
        $insert['user_id'] = $this->_superUser;

        $tabelleFremdbuchung->insert($insert);
    }

    /**
     * Gibt Kundennummer zurück
     *
     * @return null
     */
    public function getKundenId(){
        return $this->_userId;
    }




} // end class

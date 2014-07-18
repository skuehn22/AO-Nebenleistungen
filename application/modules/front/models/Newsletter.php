<?php
/**
* Verwaltet den Newsletter eines Kunden
*
* + Gibt die Information zurück ob der Kunde einen Newsletter wünscht
* + Steuerung zur Ermittlung ob der Kunde einen Newsletter wünscht
* + Ermittelt ob der Kunde einen Newsletter wünscht
* + Steuerung des eintragen des Newsletterwunsch eines Kunden
* + Trägt den Newsletterwunsch in die 'tbl_adressen ein'
*
* @date 30.29.2013
* @file Newsletter.php
* @package front
* @subpackage model
*/
class Front_Model_Newsletter
{
    // Konditionen
    private $condition_kein_newsletter_zusenden = 1;
    private $condition_newsletter_zusenden = 2;

    // Fehler
    private $error_anfangswerte_fehlen = 2020;
    private $error_anzahl_datensaetze_falsch = 2021;

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $userId = null;
    protected $newsletter = null;


    public function __construct($pimple = false)
    {
        if(!empty($pimple))
            $this->pimple = $pimple;

        $this->servicecontainer();

    }

    private function servicecontainer()
    {
        if(empty($this->pimple)){
            $this->pimple = new Pimple_Pimple();
        }

        if(!$this->pimple->offsetExists('tabelleAdressen')){
            $this->pimple['tabelleAdressen'] = function(){
                return new Application_Model_DbTable_adressen();
            };
        }
    }

    /**
     * @param $userId
     * @return Front_Model_Newsletter
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Gibt die Information zurück ob der Kunde einen Newsletter wünscht
     *
     * + 1 = kein Newsletter
     * + 2 = Newsletter erwünscht
     *
     * @return int
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param $newsletterWunsch
     * @return Front_Model_Newsletter
     */
    public function setNewsletter($newsletterWunsch)
    {
        $newsletterWunsch = (int) $newsletterWunsch;
        $this->newsletter = $newsletterWunsch;

        return $this;
    }

    /**
     * Steuerung zur Ermittlung ob der Kunde einen Newsletter wünscht
     *
     * @return Front_Model_Newsletter
     */
    public function steuerungErmittlungWunschNewsletter()
    {
        if(empty($this->userId))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        return $this;
    }

    /**
     * Ermittelt ob der Kunde einen Newsletter wünscht
     *
     * + 1 = kein Newsletter
     * + 2 = Newsletter gewünscht
     *
     * @return int
     */
    private function ermittlungWunschNewsletter()
    {
        /** @var  $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = $this->pimple['tabelleAdressen'];
        $rows = $tabelleAdressen->find($this->userId)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception($this->error_anzahl_datensaetze_falsch);

        $this->newsletter = $rows[0]['newsletter'];

        return;
    }

    /**
     * Steuerung des eintragen des Newsletterwunsch eines Kunden
     *
     * @return Front_Model_Newsletter
     */
    public function steuerungEintragenNewsletterwunsch()
    {
        $this->servicecontainer();

        if( empty($this->userId) or empty($this->newsletter) )
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        $this->eintragenNewsletterwunsch();

        return $this;
    }

    /**
     * Trägt den Newsletterwunsch in die 'tbl_adressen ein'
     *
     */
    private function eintragenNewsletterwunsch()
    {
        $update = array(
            'newsletter' => $this->newsletter
        );

        $whereUserid = "id = ".$this->userId;

        /** @var $tabelleAdressen Application_Model_DbTable_adressen */
        $tabelleAdressen = $this->pimple['tabelleAdressen'];
        $tabelleAdressen->update($update, $whereUserid);

        return;
    }
}

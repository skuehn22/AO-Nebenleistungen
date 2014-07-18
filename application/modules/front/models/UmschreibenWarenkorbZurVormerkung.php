<?php 
 /**
 * Schreibt einen Warenkorb zur Vormerkung um.
 *
 * @author Stephan.Krauss
 * @date 16.09.13
 * @file UmschreibenWarenkorbVormerkung.php
 * @package front
 * @subpackage model
 */
 
class Front_Model_UmschreibenWarenkorbZurVormerkung
{

    // Fehler
    private $error_anfangswerte_fehlen = 2120;

    // Konditionen
    private $condition_rolle_neuling = 2;
    private $condition_aktueller_zaehler = 0;
    private $condition_status_vormerkung = 2;

    // Flags

    // Informationen

    /** @var $pimple Pimple_Pimple  */
    protected $pimple = null;
    protected $anzahlArtikel = 0;
    protected $benutzerId = null;
    protected $rolleId = null;
    protected $sessionId = null;

    public function __construct()
    {
        $this->sessionId = Zend_Session::getId();

        $auth = new Zend_Session_Namespace('Auth');
        $sessionVars = (array) $auth->getIterator();

        $this->benutzerId = $sessionVars['userId'];
        $this->rolleId = $sessionVars['role_id'];
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Front_Model_UmschreibenWarenkorbZurVormerkung
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Servicecontainer
     */
    private function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleBuchungsnummer')){
            $this->pimple['tabelleBuchungsnummer'] = function(){
                return new Application_Model_DbTable_buchungsnummer();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProgrammbuchung')){
            $this->pimple['tabelleProgrammbuchung'] = function(){
                return new Application_Model_DbTable_programmbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleHotelbuchung')){
            $this->pimple['tabelleHotelbuchung'] = function(){
                return new Application_Model_DbTable_hotelbuchung();
            };
        }

        if(!$this->pimple->offsetExists('tabelleProduktbuchung')){
            $this->pimple['tabelleProduktbuchung'] = function(){
                return new Application_Model_DbTable_produktbuchung();
            };
        }

        return;
    }

    /**
     * Update Tabelle 'tbl_buchungsnummer', fügt KundenId zur Buchungstabelle
     *
     * @return Front_Model_UmschreibenWarenkorbZurVormerkung
     */
    public function updateTabelleBuchungsnummer()
    {
        $this->servicecontainer();

        $update = array(
            "kunden_id" => $this->benutzerId,
            "status" => $this->condition_status_vormerkung
        );

        $where = array(
            "session_id = '".$this->sessionId."'"
        );

        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $tabelleBuchungsnummer->update($update, $where);

        return $this;
    }

    /**
     * Schreibt die Artikel eines Warenkorbes zur Vormerkung um
     *
     * + return false, wenn kein umschreiben möglich
     *
     */
    public function umschreibenWarenkorb()
    {
        if( ($this->rolleId == null) or ($this->benutzerId == null) )
            return false;

        if($this->rolleId < $this->condition_rolle_neuling)
            return false;

        $this->servicecontainer();

        $eintragTabelleBuchungsnummer = $this->existiertEintragInTblBuchungsnummer();
        if(empty($eintragTabelleBuchungsnummer))
            return false;

        $this->umschreibenArtikelImWarenkorb($eintragTabelleBuchungsnummer['id']);

        if($this->anzahlArtikel > 0){
            $this->umschreibenTabelleBuchungsnummer($eintragTabelleBuchungsnummer['id']);
        }

    }

    /**
     * Ermittelt den Eintrag in 'tbl_buchungsnummer'
     *
     * + Eintrag vorhanden, Rückgabe Datensatz
     * + kein Eintrag, return false
     * + wenn zaehler = 0
     * + wenn Kunden ID
     * + wenn Session
     *
     * @return bool / array
     */
    private function existiertEintragInTblBuchungsnummer()
    {
        $whereAktuelleZaehler = "zaehler = '".$this->condition_aktueller_zaehler."'";
        $whereKundenId = "kunden_id = ".$this->benutzerId;

        $sessionId = Zend_Session::getId();
        $whereSessionId = " session_id = '".$sessionId."'";

        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];

        $select = $tabelleBuchungsnummer->select();
        $select
            ->where($whereKundenId)
            ->where($whereAktuelleZaehler)
            ->where($whereSessionId);

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            return false;

        return $rows[0];
    }

    /**
     * Verändert den Status des aktuellen Warenkorbes zu einer Vormerkung
     *
     * @param $buchungsnummerId
     */
    private function umschreibenArtikelImWarenkorb($buchungsnummerId)
    {
        $update = array(
            'status' => $this->condition_status_vormerkung
        );

        $where = array(
            "buchungsnummer_id = ".$buchungsnummerId,
            "zaehler = ".$this->condition_aktueller_zaehler
        );

        /** @var $tabelleProgrammbuchung Application_Model_DbTable_programmbuchung */
        $tabelleProgrammbuchung = $this->pimple['tabelleProgrammbuchung'];
        $this->anzahlArtikel += $tabelleProgrammbuchung->update($update, $where);

        /** @var $tabelleHotelbuchung Application_Model_DbTable_hotelbuchung */
        $tabelleHotelbuchung = $this->pimple['tabelleHotelbuchung'];
        $this->anzahlArtikel += $tabelleHotelbuchung->update($update, $where);

        /** @var $tabelleProduktbuchung Application_Model_DbTable_produktbuchung */
        $tabelleProduktbuchung = $this->pimple['tabelleProduktbuchung'];
        $this->anzahlArtikel += $tabelleProduktbuchung->update($update, $where);

        return;
    }

    /**
     * Schreibt 'tbl_buchungsnummer' auf Vorgemerkt um
     *
     * @param $buchungsnummerId
     */
    private function umschreibenTabelleBuchungsnummer($buchungsnummerId)
    {
        $update = array(
            'status' => $this->condition_status_vormerkung
        );

        $where = array(
            "id = ".$buchungsnummerId,
            "zaehler = ".$this->condition_aktueller_zaehler
        );

        /** @var $tabelleBuchungsnummer Application_Model_DbTable_buchungsnummer */
        $tabelleBuchungsnummer = $this->pimple['tabelleBuchungsnummer'];
        $tabelleBuchungsnummer->update($update, $where);

        return;
    }


}

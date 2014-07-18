<?php 
 /**
 * Schaltet den Zusatnd eines datensatzes zwischen 'aktiv' und 'passiv'
 *
 * @author Stephan.Krauss
 * @date 02.09.13
 * @file DatensatzAktivPassiv.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_DatensatzAktivPassiv
{
    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;

    protected $message = array();

    protected $programmId = null;
    protected $spaltenName = null;

    /**
     * Konstruktor
     */
    public function __construct()
    {

    }

    /**
     * Destructor
     */
    public function __destruct()
    {

        return;
    }

    /**
     * Gibt Meldungen an den Benutzer zurück
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Admin_Model_DatensatzAktivPassiv
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * stellt Klassen bereit
     */
    private function servicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if(!$this->pimple->offsetExists('tabelleProgrammdetails')){
            $this->pimple['tabelleProgrammdetails'] = function(){
                return new Application_Model_DbTable_programmedetails();
            };
        }
    }

    /**
     * @param $programmId
     * @return Admin_Model_DatensatzAktivPassiv
     */
    public function setProgrammId($programmId)
    {
        $programmId = filter_var($programmId, FILTER_VALIDATE_INT);
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param $spltenName
     * @return Admin_Model_DatensatzAktivPassiv
     */
    public function setSpalte($spaltenName)
    {
        $this->spaltenName = $spaltenName;

        return $this;
    }

    /**
     * Steuert den Wechsel der Aktivität des Datensatzes
     *
     * @return Admin_Model_DatensatzAktivPassiv
     * @throws nook_Exception
     */
    public function steuerungWechselStatusAktiv()
    {
        $this->servicecontainer();

        if(empty($this->programmId))
            throw new nook_Exception('Programm ID fehlt');

        if(empty($this->spaltenName))
            throw new nook_Exception('Spaltenname fehlt');

        $this->wechselStatusSpalte($this->spaltenName, $this->programmId);

        return $this;
    }

    /**
     * Wechselt den Status eines Programmes
     *
     * + 1 = Programm wird im Frontend nicht angezeigt
     */
    private function wechselStatusSpalte($spaltenName, $programmId)
    {
        $update = array(
            $spaltenName => new Zend_Db_Expr("if(".$spaltenName." = 1,2,1)")
        );

        $whereProgrammId = "id = ".$programmId;

        /** @var $tabelleProgrammdetails Application_Model_DbTable_programmedetails */
        $tabelleProgrammdetails = $this->pimple['tabelleProgrammdetails'];
        $kontrolle = $tabelleProgrammdetails->update($update, $whereProgrammId);

        if($kontrolle == 1)
            $this->message[] = 'Status erfolgreich gewechselt';
        else
            throw new nook_Exception('Fehler während Update Status Datensatz Programm');

        return;
    }
}
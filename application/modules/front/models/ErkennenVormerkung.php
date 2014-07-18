<?php 
 /**
 * Erkennt ob ein Warenkorb aus einer Vormerkung entstammt.
 *
 * @author Stephan.Krauss
 * @date 18.02.2014
 * @file ErkennenVormerkung.php
 * @package front
 * @subpackage model
 */
class Front_Model_ErkennenVormerkung
{
    protected $flagVormerkung = false;
    protected $sessionId = null;
    protected $tabelleBuchungsnummer = null;
    protected $hobNummer = 0;
    protected $buchungsNummerVormerkung = false;

    protected $condition_status_vormerkung = 2;

    /**
     * @param $sessionId
     * @return Front_Model_ErkennenVormerkung
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return Front_Model_ErkennenVormerkung
     */
    public function setTabelleBuchungsnummer(Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $this->tabelleBuchungsnummer = $tabelleBuchungsnummer;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_buchungsnummer
     */
    public function getTabelleBuchungsnummer()
    {
        if(is_null($this->tabelleBuchungsnummer))
            $this->tabelleBuchungsnummer = new Application_Model_DbTable_buchungsnummer();

        return $this->tabelleBuchungsnummer;
    }

    /**
     * Steuert die Ermittlung ob ein Warenkorb zu einer Vormerkung gehört
     *
     * + wenn es eine Vormerkung ist, dann werden weitere Werte bestimmt
     *
     * @return $this
     * @throws Exception
     */
    public function steuerungErmittlungFlagVormerkung()
    {
        try{
            if(is_null($this->sessionId))
                throw new nook_Exception('Session ID fehlt');

            $tabelleBuchungsnummer = $this->getTabelleBuchungsnummer();

            $flagVormerkung = $this->ermittelnIstWarenkorbEineVormerkung($this->sessionId, $tabelleBuchungsnummer);

            // wenn es eine Vormerkun ist !
            if($flagVormerkung === true){
                $buchungsnummerVormerkung = $this->ermittelnBuchungsnummerDerVormerkung($this->hobNummer, $this->condition_status_vormerkung, $tabelleBuchungsnummer);
                $this->buchungsNummerVormerkung = $buchungsnummerVormerkung;

            }


            return $this;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Ermittelt die Buchungsnummer einer Vormerkung mit der HOB - Nummer
     *
     * @param $hobNummer
     * @param $condition_status_vormerkung
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return int
     * @throws nook_Exception
     */
    protected function ermittelnBuchungsnummerDerVormerkung($hobNummer, $condition_status_vormerkung,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $cols = array(
            'id'
        );

        $whereHobNummer = "hobNummer = ".$hobNummer;
        $whereStatusVormerkung = "status = ".$condition_status_vormerkung;

        $select = $tabelleBuchungsnummer->select();
        $select->from($tabelleBuchungsnummer, $cols)->where($whereHobNummer)->where($whereStatusVormerkung);

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) > 1)
            throw new nook_Exception('Zu viele Vormerkungen');

        $this->buchungsNummerVormerkung = $rows[0]['id'];

        return $this->buchungsNummerVormerkung;
    }

    /**
     * Ermittelt ob ein Warenkorb einer Vormerkung entstammt
     *
     * @param $sessionId
     * @param Zend_Db_Table_Abstract $tabelleBuchungsnummer
     * @return bool
     * @throws nook_Exception
     */
    protected function ermittelnIstWarenkorbEineVormerkung($sessionId,Zend_Db_Table_Abstract $tabelleBuchungsnummer)
    {
        $cols = array(
            'hobNummer',
            'zaehler',
            'status'
        );

        $whereSessionId = "session_id = '".$sessionId."'";

        $select = $tabelleBuchungsnummer->select();
        $select
            ->from($tabelleBuchungsnummer, $cols)
            ->where($whereSessionId);

        $query = $select->__toString();

        $rows = $tabelleBuchungsnummer->fetchAll($select)->toArray();

        if(count($rows) <> 1)
            throw new nook_Exception('Anzahl Datensaetze falsch');

        if($rows[0]['hobNummer'] > 0){
            $this->flagVormerkung = true;
            $this->hobNummer = $rows[0]['hobNummer'];
        }
        else
            $this->flagVormerkung = false;

        return $this->flagVormerkung;
    }

    /**
     * @return bool
     */
    public function getFlagVormerkung()
    {
        return $this->flagVormerkung;
    }

    /**
     * @return int
     */
    public function getHobNummer()
    {
        return $this->hobNummer;
    }

    /**
     * @return int
     */
    public function getBuchungsNummerVormerkung()
    {
        return $this->buchungsNummerVormerkung;
    }
}
 
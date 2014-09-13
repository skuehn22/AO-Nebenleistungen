<?php
/**
* Überprüft die Anmeldung eines Benutzers
*
* + Kontrolliert das der Benutzer kein 'Nobody' ist
* + Kontrolle der User Id
* + Überprüfen der Rolle des Benutzers
*
* @date 03.05.13
* @file ToolBenutzeranmeldung.php
* @package tools
*/
class nook_ToolBenutzeranmeldung
{
    // Fehler
    private $error_benutzer_ist_nobody = 2110;

    protected $_rolleId = null;
    protected $_userId = null;
    protected $_anbieter = null;
    protected $_companyId = null;

    public function __construct ()
    {
        $authSession = new Zend_Session_Namespace('Auth');
        $authUser = (array) $authSession->getIterator();

        $this->_rolleId = $authUser['role_id'];
        $this->_userId = $authUser['userId'];
       // $this->_anbieter = $authUser['anbieter'];
       // $this->_companyId = $authUser['company_id'];
    }

    /**
     * Kontrolliert das der Benutzer kein 'Nobody' ist
     *
     * @return nook_ToolBenutzeranmeldung
     */
    public function killNobody()
    {
        $rolleId = (int) $this->_rolleId;

        if($rolleId === 0)
            throw new nook_Exception($this->error_benutzer_ist_nobody);

        return $this;
    }

    /**
     * Kontrolle der User Id
     *
     * @return bool
     */
    public function validateUserId()
    {
        $userId = trim($this->_userId);
        $userId = (int) $userId;

        if(!is_int($userId))
            return false;

        if(empty($userId))
            return false;

        return true;
    }

    /**
     * Überprüfen der Rolle des Benutzers
     *
     * @return bool
     */
    public function validateRolle()
    {
        $rolle = trim($this->_rolleId);
        $rolle = (int) $rolle;

        if(!is_int($rolle))
            return false;

        if(empty($rolle))
            return false;

        return true;
    }

    /**
     * @return int
     */
    public function getRolleId ()
    {
        return $this->_rolleId;
    }

    /**
     * @return int
     */
    public function getUserId ()
    {
        return $this->_userId;
    }
}
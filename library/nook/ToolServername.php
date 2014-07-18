<?php
/**
 * Tool zur Verwaltung der Servernamen
 *
 *
 * @author Stephan.Krauss
 * @date 03.05.13
 * @file ToolServername.php
 * @package tools
 */

class nook_ToolServername
{

    protected  $_serverName = null;

    /**
     * ermittelt den virtuellen Servernamen
     *
     * @return nook_ToolServername
     */
    public function findServername()
    {
        $this->_serverName = Zend_Registry::get('static')->server->virtualserver;

        return $this;
    }

    /**
     * @param $serverName
     *
     * @param $serverName
     * @return nook_ToolServername
     */
    public function setServerName ($serverName)
    {
        $this->_serverName = $serverName;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerName ()
    {
        return $this->_serverName;
    }
}
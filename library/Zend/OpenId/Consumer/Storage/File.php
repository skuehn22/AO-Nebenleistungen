<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @subpackage Zend_OpenId_Consumer
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_OpenId_Consumer_Storage
 */
require_once "Zend/OpenId/Consumer/Storage.php";

/**
 * External storage implemmentation using serialized files
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @subpackage Zend_OpenId_Consumer
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_OpenId_Consumer_Storage_File extends Zend_OpenId_Consumer_Storage
{

    /**
     * Directory name to store data files in
     *
     * @var string $_dir
     */
    private $_dir;

    /**
     * Constructs storage object and creates storage directory
     *
     * @param string $dir directory name to store data files in
     * @throws Zend_OpenId_Exception
     */
    public function __construct($dir = null)
    {
        if ($dir === null) {
            $tmp = getenv('TMP');
            if (empty($tmp)) {
                $tmp = getenv('TEMP');
                if (empty($tmp)) {
                    $tmp = "/tmp";
                }
            }
            $user = get_current_user();
            if (is_string($user) && !empty($user)) {
                $tmp .= '/' . $user;
            }
            $dir = $tmp . '/openid/consumer';
        }
        $this->_dir = $dir;
        if (!is_dir($this->_dir)) {
            if (!@mkdir($this->_dir, 0700, 1)) {
                /**
                 * @see Zend_OpenId_Exception
                 */
                require_once 'Zend/OpenId/Exception.php';
                throw new Zend_OpenId_Exception(
                    'Cannot access storage directory ' . $dir,
                    Zend_OpenId_Exception::ERROR_STORAGE);
            }
        }
        if (($f = fopen($this->_dir.'/assoc.lock', 'w+')) === null) {
            /**
             * @see Zend_OpenId_Exception
             */
            require_once 'Zend/OpenId/Exception.php';
            throw new Zend_OpenId_Exception(
                'Cannot create a lock file in the directory ' . $dir,
                Zend_OpenId_Exception::ERROR_STORAGE);
        }
        fclose($f);
        if (($f = fopen($this->_dir.'/discovery.lock', 'w+')) === null) {
            /**
             * @see Zend_OpenId_Exception
             */
            require_once 'Zend/OpenId/Exception.php';
            throw new Zend_OpenId_Exception(
                'Cannot create a lock file in the directory ' . $dir,
                Zend_OpenId_Exception::ERROR_STORAGE);
        }
        fclose($f);
        if (($f = fopen($this->_dir.'/nonce.lock', 'w+')) === null) {
            /**
             * @see Zend_OpenId_Exception
             */
            require_once 'Zend/OpenId/Exception.php';
            throw new Zend_OpenId_Exception(
                'Cannot create a lock file in the directory ' . $dir,
                Zend_OpenId_Exception::ERROR_STORAGE);
        }
        fclose($f);
    }

    /**
     * Stores information about association identified by $url/$handle
     *
     * @param string $url OpenID server URL
     * @param string $handle assiciation handle
     * @param string $macFunc HMAC function (sha1 or sha256)
     * @param string $secret shared secret
     * @param long $expires expiration UNIX time
     * @return bool
     */
    public function addAssociation($url, $handle, $macFunc, $sec
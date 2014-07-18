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
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/** Zend_Oauth */
require_once 'Zend/Oauth.php';

/** Zend_Http_Client */
require_once 'Zend/Http/Client.php';

/** Zend_Oauth_Http_Utility */
require_once 'Zend/Oauth/Http/Utility.php';

/** Zend_Oauth_Config */
require_once 'Zend/Oauth/Config.php';

/**
 * @category   Zend
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Oauth_Client extends Zend_Http_Client
{
    /**
     * Flag to indicate that the client has detected the server as supporting
     * OAuth 1.0a
     */
    public static $supportsRevisionA = false;

    /**
     * Holds the current OAuth Configuration set encapsulated in an instance
     * of Zend_Oauth_Config; it's not a Zend_Config instance since that level
     * of abstraction is unnecessary and doesn't let me escape the accessors
     * and mutators anyway!
     *
     * @var Zend_Oauth_Config
     */
    protected $_config = null;

    /**
     * True if this request is being made with data supplied by
     * a stream object instead of a raw encoded string.
     *
     * @var bool
     */
    protected $_streamingRequest = null;

    /**
     * Constructor; creates a new HTTP Client instance which itself is
     * just a typical Zend_Http_Client subclass with some OAuth icing to
     * assist in automating OAuth parameter generation, addition and
     * cryptographioc signing of requests.
     *
     * @param  array|Zend_Config $oauthOptions
     * @param  string            $uri
     * @param  array|Zend_Config $config
     * @return void
     */
    public function __construct($oauthOptions, $uri = null, $config = null)
    {
        if ($config instanceof Zend_Config && !isset($config->rfc3986_strict)) {
            $config                   = $config->toArray();
            $config['rfc3986_strict'] = true;
        } else if (null === $config ||
                   (is_array($config) && !isset($config['rfc3986_strict']))) {
            $config['rfc3986_strict'] = true;
        }
        parent::__construct($uri, $config);
        $this->_config = new Zend_Oauth_Config;
        if ($oauthOptions !== null) {
            if ($oauthOptions instanceof Zend_Config) {
                $oauthOptions = $oauthOptions->toArray();
            }
            $this->_config->setOptions($oauthOptions);
        }
    }

   /**
     * Load the connection adapter
     *
     * @param Zend_Http_Client_Adapter_Interface $adapter
     * @return void
     */
    public function setAdapter($adapter)
    {
        if ($adapter == null) {
            $this->adapter = $adapter;
        } else {
              parent::setAdapter($adapter);
        }
    }

    /**
     * Set the streamingRequest variable which controls whether we are
     * sending the raw (already encoded) POST data from a stream source.
     *
     * @param boolean $value The value to set.
     * @return void
     */
    public function setStreamingRequest($value)
    {
        $this->_streamingRequest = $value;
    }

    /**
     * Check whether the client is set to perform streaming requests.
     *
     * @return boolean True if yes, false otherwise.
     */
    public function getStreamingRequest()
    {
        if ($this->_streamingRequest) {
            return true;
        } else {
            return false;

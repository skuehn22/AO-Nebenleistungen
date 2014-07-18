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

/** Zend_Oauth_Http */
require_once 'Zend/Oauth/Http.php';

/** Zend_Oauth_Token_Access */
require_once 'Zend/Oauth/Token/Access.php';

/**
 * @category   Zend
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Oauth_Http_AccessToken extends Zend_Oauth_Http
{
    /**
     * Singleton instance if required of the HTTP client
     *
     * @var Zend_Http_Client
     */
    protected $_httpClient = null;

    /**
     * Initiate a HTTP request to retrieve an Access Token.
     *
     * @return Zend_Oauth_Token_Access
     */
    public function execute()
    {
        $params   = $this->assembleParams();
        $response = $this->startRequestCycle($params);
        $return   = new Zend_Oauth_Token_Access($response);
        return $return;
    }

    /**
     * Assemble all parameters for an OAuth Access Token request.
     *
     * @return array
     */
    public function assembleParams()
    {
        $params = array(
            'oauth_consumer_key'     => $this->_consumer->getConsumerKey(),
            'oauth_nonce'            => $this->_httpUtility->generateNonce(),
            'oauth_signature_method' => $this->_consumer->getSignatureMethod(),
            'oauth_timestamp'        => $this->_httpUtility->generateTimestamp(),
            'oauth_token'            => $this->_consumer->getLastRequestToken()->getToken(),
            'oauth_version'          => $this->_consumer->getVersion(),
        );

        if (!empty($this->_parameters)) {
            $params = array_merge($params, $this->_parameters);
        }

        $params['oauth_signature'] = $this->_httpUtility->sign(
            $params,
            $this->_consumer->getSignatureMethod(),
            $this->_consumer->getConsumerSecret(),
            $this->_consumer->getLastRequestToken()->getTokenSecret(),
            $this->_preferredRequestMethod,
            $this->_consumer->getAccessTokenUrl()
        );

        return $params;
    }

    /**
     * Generate and return a HTTP Client configured for the Header Request Scheme
     * specified by OAuth, for use in requesting an Access Token.
     *
     * @param  array $params
     * @return Zend_Http_Client
     */
    public function getRequestSchemeHeaderClient(array $params)
    {
        $params      = $this->_cleanParamsOfIllegalCustomParameters($params);
        $headerValue = $this->_toAuthorizationHeader($params);
        $client      = Zend_Oauth::getHttpClient();

        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setHeaders('Authorization', $headerValue);
        $client->setMethod($this->_preferredRequestMethod);

        return $client;
    }

    /**
     * Generate and return a HTTP Client configured for the POST Body Request
     * Scheme specified by OAuth, for use in requesting an Access Token.
     *
     * @param  array $params
     * @return Zend_Http_Client
     */
    public function getRequestSchemePostBodyClient(array $params)
    {
        $params = $this->_cleanParamsOfIllegalCustomParameters($params);
        $client = Zend_Oauth::getHttpClient();
        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setMethod($this->_preferredRequestMethod);
        $client->setRawData(
            
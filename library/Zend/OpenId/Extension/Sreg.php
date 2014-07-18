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
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_OpenId_Extension
 */
require_once "Zend/OpenId/Extension.php";

/**
 * 'Simple Refistration Extension' for Zend_OpenId
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_OpenId_Extension_Sreg extends Zend_OpenId_Extension
{
    /**
     * SREG 1.1 namespace. All OpenID SREG 1.1 messages MUST contain variable
     * openid.ns.sreg with its value.
     */
    const NAMESPACE_1_1 = "http://openid.net/extensions/sreg/1.1";

    private $_props;
    private $_policy_url;
    private $_version;

    /**
     * Creates SREG extension object
     *
     * @param array $props associative array of SREG variables
     * @param string $policy_url SREG policy URL
     * @param float $version SREG version
     * @return array
     */
    public function __construct(array $props=null, $policy_url=null, $version=1.0)
    {
        $this->_props = $props;
        $this->_policy_url = $policy_url;
        $this->_version = $version;
    }

    /**
     * Returns associative array of SREG variables
     *
     * @return array
     */
    public function getProperties() {
        if (is_array($this->_props)) {
            return $this->_props;
        } else {
            return array();
        }
    }

    /**
     * Returns SREG policy URL
     *
     * @return string
     */
    public function getPolicyUrl() {
        return $this->_policy_url;
    }

    /**
     * Returns SREG protocol version
     *
     * @return float
     */
    public function getVersion() {
        return $this->_version;
    }

    /**
     * Returns array of allowed SREG variable names.
     *
     * @return array
     */
    public static function getSregProperties()
    {
        return array(
            "nickname",
            "email",
            "fullname",
            "dob",
            "gender",
            "postcode",
            "country",
            "language",
            "timezone"
        );
    }

    /**
     * Adds additional SREG data to OpenId 'checkid_immediate' or
     * 'checkid_setup' request.
     *
     * @param array &$params request's var/val pairs
     * @return bool
     */
    public function prepareRequest(&$params)
    {
        if (is_array($this->_props) && count($this->_props) > 0) {
            foreach ($this->_props as $prop => $req) {
                if ($req) {
                    if (isset($required)) {
                        $required .= ','.$prop;
                    } else {
                        $required = $prop;
                    }
                } else {
                    if (isset($optional)) {
                        $optional .= ','.$prop;
                    } else {
                        $optional = $prop;
                    }
                }
            }
            if ($this->_version >= 1.1) {
                $params['openid.ns.sreg'] = Zend_OpenId_Extension_Sreg::NAMESPACE_1_1;
            }
            if (!empty($required)) {
                $params['openid.sreg.required'] = $required;
            }
            if (!empty($optional)) {
                $params['openid.sreg.optional'] = $optional;
            }
            if (!empty($this->_policy_url)) {
                $params['openid.sreg.policy_url'] = $t
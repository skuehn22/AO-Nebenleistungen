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
 * @package    Zend_Navigation
 * @subpackage Page
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_Navigation_Page
 */
require_once 'Zend/Navigation/Page.php';

/**
 * @see Zend_Controller_Action_HelperBroker
 */
require_once 'Zend/Controller/Action/HelperBroker.php';

/**
 * Used to check if page is active
 *
 * @see Zend_Controller_Front
 */
require_once 'Zend/Controller/Front.php';

/**
 * Represents a page that is defined using module, controller, action, route
 * name and route params to assemble the href
 *
 * @category   Zend
 * @package    Zend_Navigation
 * @subpackage Page
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Navigation_Page_Mvc extends Zend_Navigation_Page
{
    /**
     * Action name to use when assembling URL
     *
     * @var string
     */
    protected $_action;

    /**
     * Controller name to use when assembling URL
     *
     * @var string
     */
    protected $_controller;

    /**
     * Module name to use when assembling URL
     *
     * @var string
     */
    protected $_module;

    /**
     * Params to use when assembling URL
     *
     * @see getHref()
     * @var array
     */
    protected $_params = array();

    /**
     * Route name to use when assembling URL
     *
     * @see getHref()
     * @var string
     */
    protected $_route;

    /**
     * Whether params should be reset when assembling URL
     *
     * @see getHref()
     * @var bool
     */
    protected $_resetParams = true;

    /**
     * Whether href should be encoded when assembling URL
     *
     * @see getHref()
     * @var bool
     */
    protected $_encodeUrl = true;

    /**
     * Whether this page should be considered active
     *
     * @var bool
     */
    protected $_active = null;

    /**
     * Scheme to use when assembling URL
     *
     * @see getHref()
     * @var string
     */
    protected $_scheme;

    /**
     * Cached href
     *
     * The use of this variable minimizes execution time when getHref() is
     * called more than once during the lifetime of a request. If a property
     * is updated, the cache is invalidated.
     *
     * @var string
     */
    protected $_hrefCache;

    /**
     * Action helper for assembling URLs
     *
     * @see getHref()
     * @var Zend_Controller_Action_Helper_Url
     */
    protected static $_urlHelper = null;

    /**
     * View helper for assembling URLs with schemes
     *
     * @see getHref()
     * @var Zend_View_Helper_ServerUrl
     */
    protected static $_schemeHelper = null;

    // Accessors:

    /**
     * Returns whether page should be considered active or not
     *
     * This method will compare the page properties against the request object
     * that is found in the front controller.
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     * @return bool             whether page should be considered active or not
     */
    public function isActive($recursive = false)
    {
        if (null === $this->_active) {
            $front     = Zend_Controller_Front::getInstance();
            $request   = $front->getRequest();
            $reqParams = array();
            if ($request) {
                $re
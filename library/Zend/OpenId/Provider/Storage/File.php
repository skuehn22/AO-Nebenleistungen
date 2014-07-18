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
 * @subpackage Zend_OpenId_Provider
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @see Zend_OpenId_Provider_Storage
 */
require_once "Zend/OpenId/Provider/Storage.php";

/**
 * External storage implemmentation using serialized files
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @subpackage Zend_OpenId_Provider
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_OpenId_Provider_Storage_File extends Zend_OpenId_Provider_Storage
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
            $dir = $tmp . '/openid/provider';
        }
        $this->_dir = $dir;
        if (!is_dir($this->_dir)) {
            if (!@mkdir($this->_dir, 0700, 1)) {
                throw new Zend_OpenId_Exception(
                    "Cannot access storage directory $dir",
                    Zend_OpenId_Exception::ERROR_STORAGE);
            }
        }
        if (($f = fopen($this->_dir.'/assoc.lock', 'w+')) === null) {
            throw new Zend_OpenId_Exception(
                'Cannot create a lock file in the directory ' . $dir,
                Zend_OpenId_Exception::ERROR_STORAGE);
        }
        fclose($f);
        if (($f = fopen($this->_dir.'/user.lock', 'w+')) === null) {
            throw new Zend_OpenId_Exception(
                'Cannot create a lock file in the directory ' . $dir,
                Zend_OpenId_Exception::ERROR_STORAGE);
        }
        fclose($f);
    }

    /**
     * Stores information about session identified by $handle
     *
     * @param string $handle assiciation handle
     * @param string $macFunc HMAC function (sha1 or sha256)
     * @param string $secret shared secret
     * @param string $expires expiration UNIX time
     * @return bool
     */
    public function addAssociation($handle, $macFunc, $secret, $expires)
    {
        $name = $this->_dir . '/assoc_' . md5($handle);
        $lock = @fopen($this->_dir . '/assoc.lock', 'w+');
        if ($lock === false) {
            return false;
        }
        if (!flock($lock, LOCK_EX)) {
            fclose($lock);
            return false;
        }
        try {
            $f = @fopen($name, 'w+');
            if ($f === false) {
                fclose($lock);
                return false;
            }
            $data = serialize(array($handle, $macFunc, $secret, $expires));
            fwrite($f, $data);
            fclose($f);
            fclose($lock);
            return true;
        } catch (Exception $e) {
            fclose($lock);
            throw $e;
        }
    }

    /**
     * Gets information about association identified by $handle
     * Returns true if given association found_' . strtoupper($country)] = $quality;
            }

            foreach ($country2 as $country) {
                $languages[$region . '_' . strtoupper($country)] = $quality;
            }

            if ((isset($languages[$region]) === false) || ($languages[$region] < $quality)) {
                $languages[$region] = $quality;
            }
        }

        self::$_browser = $languages;
        return $languages;
    }

    /**
     * Sets a new locale
     *
     * @param  string|Zend_Locale $locale (Optional) New locale to set
     * @return void
     */
    public function setLocale($locale = null)
    {
        $locale = self::_prepareLocale($locale);

        if (isset(self::$_localeData[(string) $locale]) === false) {
            $region = substr((string) $locale, 0, 3);
            if (isset($region[2]) === true) {
                if (($region[2] === '_') or ($region[2] === '-')) {
                    $region = substr($region, 0, 2);
                }
            }

            if (isset(self::$_localeData[(string) $region]) === true) {
                $this->_locale = $region;
            } else {
                $this->_locale = 'root';
            }
        } else {
            $this->_locale = $locale;
        }
    }

    /**
     * Returns the language part of the locale
     *
     * @return string
     */
    public function getLanguage()
    {
        $locale = explode('_', $this->_locale);
        return $locale[0];
    }

    /**
     * Returns the region part of the locale if available
     *
     * @return string|false - Regionstring
     */
    public function getRegion()
    {
        $locale = explode('_', $this->_locale);
        if (isset($locale[1]) === true) {
            return $locale[1];
        }

        return false;
    }

    /**
     * Return the accepted charset of the client
     *
     * @return string
     */
    public static function getHttpCharset()
    {
        $httpcharsets = getenv('HTTP_ACCEPT_CHARSET');

        $charsets = array();
        if ($httpcharsets === false) {
            return $charsets;
        }

        $accepted = preg_split('/,\s*/', $httpcharsets);
        foreach ($accepted as $accept) {
            if (empty($accept) === true) {
                continue;
            }

            if (strpos($accept, ';') !== false) {
                $quality        = (float) substr($accept, (strpos($accept, '=') + 1));
                $pos            = substr($accept, 0, strpos($accept, ';'));
                $charsets[$pos] = $quality;
            } else {
                $quality           = 1.0;
                $charsets[$accept] = $quality;
            }
        }

        return $charsets;
    }

    /**
     * Returns true if both locales are equal
     *
     * @param  Zend_Locale $object Locale to check for equality
     * @return boolean
     */
    public function equals(Zend_Locale $object)
    {
        if ($object->toString() === $this->toString()) {
            return true;
        }

        return false;
    }

    /**
     * Returns localized informations as array, supported are several
     * types of informations.
     * For detailed information about the types look into the documentation
     *
     * @param  string             $path   (Optional) Type of information to return
     * @param  string|Zend_Locale $locale (Optional) Locale|Language for which this informations should be returned
     * @param  string             $value  (Optional) Value for detail list
     * @return array Array with the wished information in the given language
     */
    public static function getTranslationList($path = null, $locale = null, $value = null)
    {
        require_once 'Zend/Locale/Data.php';
        $locale = self::findLocale($locale);
        $result = Zend_Locale_Data::getList($locale, $path, $value);
        if (empty($result) === true) {
            return false;
        }

        return $result;
    }

    /**
     * Returns an array with the name of all languages translated to the given language
     *
     * @param  string|Zend_Locale $locale (Optional) Locale for language translation
     * @return array
     * @deprecated
     */
    public static function getLanguageTranslationList($locale = null)
    {
        trigger_error("The method getLanguageTranslationList is deprecated. Use getTranslationList('language', $locale) instead", E_USER_NOTICE);
        return self::getTranslationList('language', $locale);
    }

    /**
     * Returns an array with the name of all scripts translated to the given language
     *
     * @param  string|Zend_Locale $locale (Optional) Locale for script translation
     * @return array
     * @deprecated
     */
    public static function getScriptTranslationList($locale = null)
    {
        trigger_error("The method getScriptTranslationList is deprecated. Use getTranslationList('script', $locale) instead", E_USER_NOTICE);
        return self::getTranslationList('script', $locale);
    }

    /**
     * Returns an array with the name of all countries translated to the given language
     *
     * @param  string|Zend_Locale $locale (Optional) Locale for country translation
     * @return array
     * @deprecated
     */
    public static function getCountryTranslationList($locale = null)
    {
        trigger_error("The method getCountryTranslationList is deprecated. Use getTranslationList('territory', $locale, 2) instead", E_USER_NOTICE);
        return self::getTranslationList('territory', $locale, 2);
    }

    /**
     * Returns an array with the name of all territories translated to the given language
     * All territories contains other countries.
     *
     * @param  string|Zend_Locale $locale (Optional) Locale for territory translation
     * @return array
     * @deprecated
     */
    public static function getTerritoryTranslationList($locale = null)
    {
        trigger_error("The method getTerritoryTranslationList is deprecated. Use getTranslationList('territory', $locale, 1) instead", E_USER_NOTICE);
        return self::getTranslationList('territory', $locale, 1);
    }

    /**
     * Returns a localized information string, supported are several types of informations.
     * For detailed information about the types look into the documentation
     *
     * @param  string             $value  Name to get detailed information about
     * @param  string             $path   (Optional) Type of information to return
     * @param  string|Zend_Locale $locale (Optional) Locale|Language for which this informations should be returned
     * @return string|false The wished information in the given language
     */
    public static function getTranslation($value = null, $path = null, $locale = null)
    {
        require_once 'Zend/Locale/Data.php';
        $locale = self::findLocale($locale);
        $result = Zend_Locale_Data::getContent($locale, $path, $value);
        if (empty($result) === true && '0' !== $result) {
            return false;
        }

        return $result;
    }

    /**
     * Returns the localized language name
     *
     * @param  string $value  Name to get detailed information about
     * @param  string $locale (Optional) Locale for language translation
     * @return array
     * @deprecated
     */
    public static function getLanguageTranslation($value, $locale = null)
    {
        trigger_error("The method getLanguageTranslation is deprecated. Use getTranslation($value, 'language', $locale) instead", E_USER_NOTICE);
        return self::getTranslation($value, 'language', $locale);
    }

    /**
     * Returns the localized script name
     *
     * @param  string $value  Name to get detailed information about
     * @param  string $locale (Optional) locale for script translation
     * @return array
     * @deprecated
     */
    public static function getScriptTranslation($value, $locale = null)
    {
        trigger_error("The method getScriptTranslation is deprecated. Use getTranslation($value, 'script', $locale) instead", E_USER_NOTICE);
        return self::getTranslation($value, 'script', $locale);
    }

    /**
     * Returns the localized country name
     *
     * @param  string             $value  Name to get detailed information about
     * @param  string|Zend_Locale $locale (Optional) Locale for country translation
     * @return array
     * @deprecated
     */
    public static function getCountryTranslation($value, $locale = null)
    {
        trigger_error("The method getCountryTranslation is deprecated. Use getTranslation($value, 'country', $locale) instead", E_USER_NOTICE);
        return self::getTranslation($value, 'country', $locale);
    }

    /**
     * Returns the localized territory name
     * All territories contains other countries.
     *
     * @param  string             $value  Name to get detailed information about
     * @param  string|Zend_Locale $locale (Optional) Locale for territory translation
     * @return array
     * @deprecated
     */
    public static function getTerritoryTranslation($value, $locale = null)
    {
        trigger_error("The method getTerritoryTranslation is deprecated. Use getTranslation($value, 'territory', $locale) instead", E_USER_NOTICE);
        return self::getTranslation($value, 'territory', $locale);
    }

    /**
     * Returns an array with translated yes strings
     *
     * @param  string|Zend_Locale $locale (Optional) Locale for language translation (defaults to $this locale)
     * @return array
     */
    public static function getQuestion($locale = null)
    {
        require_once 'Zend/Locale/Data.php';
        $locale            = self::findLocale($locale);
        $quest             = Zend_Locale_Data::getList($locale, 'question');
        $yes               = explode(':', $quest['yes']);
        $no                = explode(':', $quest['no']);
        $quest['yes']      = $yes[0];
        $quest['yesarray'] = $yes;
        $quest['no']       = $no[0];
        $quest['noarray']  = $no;
        $quest['yesexpr']  = self::_prepareQuestionString($yes);
        $quest['noexpr']   = self::_prepareQuestionString($no);

        return $quest;
    }

    /**
     * Internal function for preparing the returned question regex string
     *
     * @param  string $input Regex to parse
     * @return string
     */
    private static function _prepareQuestionString($input)
    {
        $regex = '';
        if (is_array($input) === true) {
            $regex = '^';
            $start = true;
            foreach ($input as $row) {
                if ($start === false) {
                    $regex .= '|';
                }

                $start  = false;
                $regex .= '(';
                $one    = null;
                if (strlen($row) > 2) {
                    $one = true;
                }

                foreach (str_split($row, 1) as $char) {
                    $regex .= '[' . $char;
                    $regex .= strtoupper($char) . ']';
                    if ($one === true) {
                        $one    = false;
                        $regex .= '(';
                    }
                }

                if ($one === false) {
                    $regex .= ')';
                }

                $regex .= '?)';
            }
        }

        return $regex;
    }

    /**
     * Checks if a locale identifier is a real locale or not
     * Examples:
     * "en_XX" refers to "en", which returns true
     * "XX_yy" refers to "root", which returns false
     *
     * @param  string|Zend_Locale $locale     Locale to check for
     * @param  boolean            $strict     (Optional) If true, no rerouting will be done when checking
     * @param  boolean            $compatible (DEPRECATED) Only for internal usage, brakes compatibility mode
     * @return boolean If the locale is known dependend on the settings
     */
    public static function isLocale($locale, $strict = false, $compatible = true)
    {
        if (($locale instanceof Zend_Locale)
            || (is_string($locale) && array_key_exists($locale, self::$_localeData))
        ) {
            return true;
        }

        if (($locale === null) || (!is_string($locale) and !is_array($locale))) {
            return false;
        }

        try {
            $locale = self::_prepareLocale($locale, $strict);
        } catch (Zend_Locale_Exception $e) {
            return false;
        }

        if (($compatible === true) and (self::$compatibilityMode === true)) {
            trigger_error('You are running Zend_Locale in compatibility mode... please migrate your scripts', E_USER_NOTICE);
            if (isset(self::$_localeData[$locale]) === true) {
                return $locale;
            } else if (!$strict) {
                $locale = explode('_', $locale);
                if (isset(self::$_localeData[$locale[0]]) === true) {
                    return $locale[0];
                }
            }
        } else {
            if (isset(self::$_localeData[$locale]) === true) {
                return true;
            } else if (!$strict) {
                $locale = explode('_', $locale);
                if (isset(self::$_localeData[$locale[0]]) === true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Finds the proper locale based on the input
     * Checks if it exists, degrades it when necessary
     * Detects registry locale and when all fails tries to detect a automatic locale
     * Returns the found locale as string
     *
     * @param string $locale
     * @throws Zend_Locale_Exception When the given locale is no locale or the autodetection fails
     * @return string
     */
    public static function findLocale($locale = null)
    {
        if ($locale === null) {
            require_once 'Zend/Registry.php';
            if (Zend_Registry::isRegistered('Zend_Locale')) {
                $locale = Zend_Registry::get('Zend_Locale');
            }
        }

        if ($locale === null) {
            $locale = new Zend_Locale();
        }

        if (!Zend_Locale::isLocale($locale, true, false)) {
            if (!Zend_Locale::isLocale($locale, false, false)) {
                $locale = Zend_Locale::getLocaleToTerritory($locale);

                if (empty($locale)) {
                    require_once 'Zend/Locale/Exception.php';
                    throw new Zend_Locale_Exception("The locale '$locale' is no known locale");
                }
            } else {
                $locale = new Zend_Locale($locale);
            }
        }

        $locale = self::_prepareLocale($locale);
        return $locale;
    }

    /**
     * Returns the expected locale for a given territory
     *
     * @param string $territory Territory for which the locale is being searched
     * @return string|null Locale string or null when no locale has been found
     */
    public static function getLocaleToTerritory($territory)
    {
        $territory = strtoupper($territory);
        if (array_key_exists($territory, self::$_territoryData)) {
            return self::$_territoryData[$territory];
        }

        return null;
    }

    /**
     * Returns a list of all known locales where the locale is the key
     * Only real locales are returned, the internal locales 'root', 'auto', 'browser'
     * and 'environment' are suppressed
     *
     * @return array List of all Locales
     */
    public static function getLocaleList()
    {
        $list = self::$_localeData;
        unset($list['root']);
        unset($list['auto']);
        unset($list['browser']);
        unset($list['environment']);
        return $list;
    }

    /**
     * Returns the set cache
     *
     * @return Zend_Cache_Core The set cache
     */
    public static function getCache()
    {
        require_once 'Zend/Locale/Data.php';
        return Zend_Locale_Data::getCache();
    }

    /**
     * Sets a cache
     *
     * @param  Zend_Cache_Core $cache Cache to set
     * @return void
     */
    public static function setCache(Zend_Cache_Core $cache)
    {
        require_once 'Zend/Locale/Data.php';
        Zend_Locale_Data::setCache($cache);
    }

    /**
     * Returns true when a cache is set
     *
     * @return boolean
     */
    public static function hasCache()
    {
        require_once 'Zend/Locale/Data.php';
        return Zend_Locale_Data::hasCache();
    }

    /**
     * Removes any set cache
     *
     * @return void
     */
    public static function removeCache()
    {
        require_once 'Zend/Locale/Data.php';
        Zend_Locale_Data::removeCache();
    }

    /**
     * Clears all set cache data
     *
     * @param string $tag Tag to clear when the default tag name is not used
     * @return void
     */
    public static function clearCache($tag = null)
    {
        require_once 'Zend/Locale/Data.php';
        Zend_Locale_Data::clearCache($tag);
    }

    /**
     * Disables the set cache
     *
     * @param  boolean $flag True disables any set cache, default is false
     * @return void
     */
    public static function disableCache($flag)
    {
        require_once 'Zend/Locale/Data.php';
        Zend_Locale_Data::disableCache($flag);
    }

    /**
     * Internal function, returns a single locale on detection
     *
     * @param  string|Zend_Locale $locale (Optional) Locale to work on
     * @param  boolean            $strict (Optional) Strict preparation
     * @throws Zend_Locale_Exception When no locale is set which is only possible when the class was wrong extended
     * @return string
     */
    private static function _prepareLocale($locale, $strict = false)
    {
        if ($locale instanceof Zend_Locale) {
            $locale = $locale->toString();
        }

        if (is_array($locale)) {
            return '';
        }

        if (empty(self::$_auto) === true) {
            self::$_browser     = self::getBrowser();
            self::$_environment = self::getEnvironment();
            self::$_breakChain  = true;
            self::$_auto        = self::getBrowser() + self::getEnvironment() + self::getDefault();
        }

        if (!$strict) {
            if ($locale === 'browser') {
                $locale = self::$_browser;
            }

            if ($locale === 'environment') {
                $locale = self::$_environment;
            }

            if ($locale === 'default') {
                $locale = self::$_default;
            }

            if (($locale === 'auto') or ($locale === null)) {
                $locale = self::$_auto;
            }

            if (is_array($locale) === true) {
                $locale = key($locale);
            }
        }

        // This can only happen when someone extends Zend_Locale and erases the default
        if ($locale === null) {
            require_once 'Zend/Locale/Exception.php';
            throw new Zend_Locale_Exception('Autodetection of Locale has been failed!');
        }

        if (strpos($locale, '-') !== false) {
            $locale = strtr($locale, '-', '_');
        }

        $parts = explode('_', $locale);
        if (!isset(self::$_localeData[$parts[0]])) {
            if ((count($parts) == 1) && array_key_exists($parts[0], self::$_territoryData)) {
                return self::$_territoryData[$parts[0]];
            }

            return '';
        }

        foreach($parts as $key => $value) {
            if ((strlen($value) < 2) || (strlen($value) > 3)) {
                unset($parts[$key]);
            }
        }

        $locale = implode('_', $parts);
        return (string) $locale;
    }

    /**
     * Search the locale automatically and return all used locales
     * ordered by quality
     *
     * Standard Searchorder is Browser, Environment, Default
     *
     * @param  string  $searchorder (Optional) Searchorder
     * @return array Returns an array of all detected locales
     */
    public static function getOrder($order = null)
    {
        switch ($order) {
            case self::ENVIRONMENT:
                self::$_breakChain = true;
                $languages         = self::getEnvironment() + self::getBrowser() + self::getDefault();
                break;

            case self::ZFDEFAULT:
                self::$_breakChain = true;
                $languages         = self::getDefault() + self::getEnvironment() + self::getBrowser();
                break;

            default:
                self::$_breakChain = true;
                $languages         = self::getBrowser() + self::getEnvironment() + self::getDefault();
                break;
        }

        return $languages;
    }
}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
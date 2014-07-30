<?php
/**
 * This file contains the the effective setting class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Effective setting manager class.
 * Provides a interface to retrieve effective
 * settings.
 *
 * Requested effective settings will be cached at first time. Further requests
 * will
 * return cached settings.
 *
 * The order to retrieve a effective setting is:
 * System => Client => Client (language) => Group => User
 *
 * - System properties can be overridden by the client
 * - Client properties can be overridden by client language
 * - Client language properties can be overridden by group
 * - Group properties can be overridden by user
 *
 * @package Core
 * @subpackage Backend
 */
class cEffectiveSetting {

    /**
     *
     * @var array
     */
    protected static $_settings = array();

    /**
     *
     * @var cApiUser
     */
    protected static $_user;

    /**
     *
     * @var cApiClient
     */
    protected static $_client;

    /**
     *
     * @var cApiClientLanguage
     */
    protected static $_clientLanguage;

    /**
     *
     * @var cApiLanguage
     */
    protected static $_language;

    /**
     * Returns effective setting for a property.
     *
     * The order is: System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group properties
     * can be overridden by the user.
     *
     * NOTE: If you provide a default value (other than empty string), then it will be returned back
     *       in case of not existing or empty setting.
     *
     * @param  string  $type  The type of the item
     * @param  string  $name  The name of the item
     * @param  string  $default  Optional default value
     * @return  bool|string  Setting value or false
     */
    public static function get($type, $name, $default = '') {
        global $contenido;

        // If the DB object is not available, just return the default value in order
        // to avoid PHP notices
        try {
            $db = new cDb();
        } catch (cException $e) {
            return $default;
        }

        $key = self::_makeKey($type, $name);

        $value = self::_get($key);
        if (false !== $value) {
            return $value;
        }

        if (self::_isAuthenticated() && isset($contenido)) {
            $value = self::_getUserInstance()->getUserProperty($type, $name, true);
        }

        if (false === $value) {
            $value = self::_getLanguageInstance()->getProperty($type, $name);
        }

        if (false === $value) {
            $value = self::_getClientLanguageInstance()->getProperty($type, $name);
        }

        if (false === $value) {
            $value = self::_getClientInstance()->getProperty($type, $name);
        }

        if (false === $value) {
            $value = getSystemProperty($type, $name);
        }

        if (false === $value || NULL === $value) {
            $value = $default;
        } else if ('' === $value && '' !== $default) {
            // NOTE: An non empty default value overrides an empty value
            $value = $default;
        }

        self::_set($key, $value);

        return $value;
    }

    /**
     * Returns effective setting for a type of properties.
     * Caches also the collected settings, but contrary to get() it returns
     * never cached entries.
     *
     * The order is:
     * System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group
     * properties can be overridden by the user.
     *
     * @param string $type The type of the item
     * @return array Assoziative array like $arr[name] = value
     */
    public static function getByType($type) {
        global $contenido;

        $settings = getSystemPropertiesByType($type);
        $settings = array_merge($settings, self::_getClientInstance()->getPropertiesByType($type));
        $settings = array_merge($settings, self::_getClientLanguageInstance()->getPropertiesByType($type));
        if (self::_isAuthenticated() && cRegistry::isBackendEditMode()) {
            $settings = array_merge($settings, self::_getUserInstance()->getUserPropertiesByType($type, true));
        }

        // cache all settings, to return them from cache in case of calling
        // get()
        foreach ($settings as $setting => $value) {
            $key = self::_makeKey($type, $setting);
            self::_set($key, $value);
        }

        return $settings;
    }

    /**
     * Sets a effective setting.
     *
     * Note:
     * The setting will be set only in cache, not in persistency layer.
     *
     * @param string $type The type of the item
     * @param string $name The name of the item
     * @param string $value The value of the setting
     */
    public static function set($type, $name, $value) {
        $key = self::_makeKey($type, $name);
        self::_set($key, $value);
    }

    /**
     * Deletes a effective setting.
     *
     * Note:
     * The setting will be deleted only from cache, not from persistency layer.
     *
     * @param string $type The type of the item
     * @param string $name The name of the item
     */
    public static function delete($type, $name) {
        $keySuffix = '_' . $type . '_' . $name;
        foreach (self::$_settings as $key => $value) {
            if (strpos($key, $keySuffix) !== false) {
                unset(self::$_settings[$key]);
            }
        }
    }

    /**
     * Resets all properties of the effective settings class.
     * Usable to start getting settings from scratch.
     */
    public static function reset() {
        self::$_settings = array();
        unset(self::$_user, self::$_client, self::$_clientLanguage);
    }

    /**
     * Returns the user object instance.
     *
     * @return cApiUser
     */
    protected static function _getUserInstance() {
        global $auth;

        if (!isset(self::$_user)) {
            self::$_user = new cApiUser($auth->auth['uid']);
        }
        return self::$_user;
    }

    /**
     * Returns the client language object instance.
     *
     * @return cApiClientLanguage
     */
    protected static function _getClientLanguageInstance() {
        global $client, $lang;

        if (!isset(self::$_clientLanguage)) {
            self::$_clientLanguage = new cApiClientLanguage(false, $client, $lang);
        }
        return self::$_clientLanguage;
    }

    /**
     * Returns the language object instance.
     *
     * @return cApiLanguage
     */
    protected static function _getLanguageInstance() {
        global $lang;

        if (!isset(self::$_language)) {
            self::$_language = new cApiLanguage($lang);
        }
        return self::$_language;
    }

    /**
     * Returns the client language object instance.
     *
     * @return cApiClient
     */
    protected static function _getClientInstance() {
        global $client;

        if (!isset(self::$_client)) {
            self::$_client = new cApiClient($client);
        }
        return self::$_client;
    }

    /**
     * Setting getter.
     *
     * @param string $key The setting key
     * @return string bool setting value or false
     */
    protected static function _get($key) {
        return (isset(self::$_settings[$key])) ? self::$_settings[$key] : false;
    }

    /**
     * Setting setter.
     *
     * @param string $key The setting key
     * @param string $value Value to store
     */
    protected static function _set($key, $value) {
        self::$_settings[$key] = $value;
    }

    /**
     * Setting key getter.
     *
     * @param string $type The type of the item
     * @param string $name Name of the item
     * @return string The setting key
     */
    protected static function _makeKey($type, $name) {
        global $auth;

        if ($auth instanceof cAuth) {
            $key = $auth->auth['uid'] . '_' . $type . '_' . $name;
        } else {
            $key = '_' . $type . '_' . $name;
        }
        return $key;
    }

    /**
     * Checks global authentication object and if current user is authenticated.
     *
     * @return bool
     */
    protected static function _isAuthenticated() {
        global $auth;
        return ($auth instanceof cAuth && $auth->isAuthenticated() && !$auth->isLoginForm());
    }
}
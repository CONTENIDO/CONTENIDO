<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Effective setting manager class. Provides a interface to retrieve effective
 * settings.
 *
 * Requested effective settings will be cached at first time. Further requests will
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
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package     CONTENIDO Backend Classes
 * @subpackage  Setting
 * @version     0.1
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 * @since       file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created 2011-11-03
 *
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cEffectiveSetting {

    /**
     * @var array
     */
    protected static $_settings = array();

    /**
     * @var cApiUser
     */
    protected static $_user;

    /**
     * @var cApiClient
     */
    protected static $_client;

    /**
     * @var cApiClientLanguage
     */
    protected static $_clientLanguage;

    /**
     * @var cApiLanguage
     */
    protected static $_language;

    /**
     * Returns effective setting for a property. The requested setting will be cached
     * at first time, the next time the cached value will be returned.
     *
     * The order is:
     * System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group
     * properties can be overridden by the user.
     *
     * @param   string  $type The type of the item
     * @param   string  $name The name of the item
     * @param   string  $default Optional default value
     * @return  string|bool  The setting value or false
     */
    public static function get($type, $name, $default = '') {
        global $contenido;

        // if the DB connection is not possible, just return
        // the default value in order to avoid PHP notices
        try {
            $db = new DB_Contenido();
        } catch (cException $e) {
            return $default;
        }

        $key = self::_makeKey($type, $name);

        $value = self::_get($key);
        if (false !== $value) {
            return $value;
        }

        if (self::_isAuthenticated() && isset($contenido)) {
            $obj = self::_getUserInstance();
            $value = $obj->getUserProperty($type, $name, true);
        }

        if (false == $value) {
            $obj = self::_getLanguageInstance();
            $value = $obj->getProperty($type, $name);
        }

        if (false == $value) {
            $obj = self::_getClientLanguageInstance();
            $value = $obj->getProperty($type, $name);
        }

        if (false == $value) {
            $obj = self::_getClientInstance();
            $value = self::$_client->getProperty($type, $name);
        }

        if ($value == false) {
            $value = getSystemProperty($type, $name);
        }

        if ($value == false) {
            $value = $default;
        }

        self::_set($key, $value);

        return $value;
    }

    /**
     * Returns effective setting for a type of properties. Caches also the
     * collected settings, but contrary to get() it returns never cached entries.
     *
     * The order is:
     * System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group
     * properties can be overridden by the user.
     *
     * @param   string  $type The type of the item
     * @return  array  Assoziative array like $arr[name] = value
     */
    public static function getByType($type) {
        global $contenido;

        $settings = getSystemPropertiesByType($type);

        $obj = self::_getClientInstance();
        $settings = array_merge($settings, $obj->getPropertiesByType($type));

        $obj = self::_getClientLanguageInstance();
        $settings = array_merge($settings, $obj->getPropertiesByType($type));

        if (self::_isAuthenticated() && isset($contenido)) {
            $obj = self::_getUserInstance();
            $settings = array_merge($settings, $obj->getUserPropertiesByType($type, true));
        }

        // cache all settings, to return them from cache in case of calling get()
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
     * @param   string  $type The type of the item
     * @param   string  $name The name of the item
     * @param   string  $value The value of the setting
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
     * @param   string  $type The type of the item
     * @param   string  $name The name of the item
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
     * @return  cApiUser
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
     * @return  cApiClientLanguage
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
     * @return  cApiLanguage
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
     * @return  cApiClient
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
     * @param   string  $key  The setting key
     * @return  string|bool  The setting value or false
     */
    protected static function _get($key) {
        return (isset(self::$_settings[$key])) ? self::$_settings[$key] : false;
    }

    /**
     * Setting setter.
     *
     * @param   string  $key  The setting key
     * @param   string  $value  Value to store
     * @return  string|bool  The setting value or false
     */
    protected static function _set($key, $value) {
        self::$_settings[$key] = $value;
    }

    /**
     * Setting key getter.
     *
     * @param   string  $type The type of the item
     * @param   string  $name Name of the item
     * @return  string  The setting key
     */
    protected static function _makeKey($type, $name) {
        global $auth;

        if ($auth instanceof Contenido_Auth) {
            $key = $auth->auth['uid'] . '_' . $type . '_' . $name;
        } else {
            $key = '_' . $type . '_' . $name;
        }
        return $key;
    }

    /**
     * Checks global authentication object and if current user is authenticated.
     *
     * @return  bool
     */
    protected static function _isAuthenticated() {
        global $auth;
        return ($auth instanceof cAuth && $auth->isAuthenticated() && !$auth->isLoginForm());
    }

}
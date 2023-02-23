<?php
/**
 * This file contains the effective setting class.
 *
 * @package Core
 * @subpackage Backend
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Effective setting manager class.
 *
 * Provides an interface to retrieve effective settings.
 *
 * Requested effective settings will be cached at first time. Further requests
 * will return cached settings.
 *
 * NOTE:
 * Don't use this class to retrieve setting within an early bootstrapping process
 * of the CONTENIDO application, e.g. in plugin configuration files, which are
 * loaded before detecting/setting the global variables $client and $lang!
 *
 * The order to retrieve an effective setting is:
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
class cEffectiveSetting
{

    /**
     * @var array
     */
    protected static $_settings = [];

    /**
     * @var cApiUser|NULL
     */
    protected static $_user;

    /**
     * @var cApiClient|NULL
     */
    protected static $_client;

    /**
     * @var cApiClientLanguage|NULL
     */
    protected static $_clientLanguage;

    /**
     * @var bool[]
     */
    protected static $_loaded = [];

    /**
     * @var cApiLanguage
     */
    protected static $_language;

    /**
     * Loads all client, clientLanguage a system properties into a static array.
     *
     * The order is: System => Client => Client (language)
     *
     * @throws cDbException
     * @throws cException
     */
    private static function _loadSettings()
    {
        if (!isset(self::$_loaded[self::_getKeyPrefix()])) {
            $typeGroup = [];

            // Get all client settings
            $client = self::_getClientInstance();
            $settings = $client->getProperties();
            if (is_array($settings)) {
                self::_takeoverTypeGroupSettings($settings, $typeGroup);
            }

            // Get all clientLang setting
            $clientLang = self::_getClientLanguageInstance();
            $settings = $clientLang->getProperties();
            if (is_array($settings)) {
                self::_takeoverTypeGroupSettings($settings, $typeGroup);
            }

            // Get user settings
            if (self::_isAuthenticated() && !empty(cRegistry::getBackendSessionId())) {
                $user = self::_getUserInstance();
                $settings = $user->getUserProperties();
                if (is_array($settings)) {
                    self::_takeoverTypeGroupSettings($settings, $typeGroup);
                }
            }

            // Write cache by type settings
            foreach ($typeGroup as $key => $group) {
                $key = self::_makeKey($key, ' ');
                self::_set($key, $group);
            }
        }

        self::$_loaded[self::_getKeyPrefix()] = true;
    }

    /**
     * Returns effective setting for a property.
     *
     * The order is: System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group properties
     * can be overridden by the user.
     *
     * NOTE: If you provide a default value (other than empty string),
     * then it will be returned in case of not existing or empty setting.
     *
     * @param string $type
     *                        The type of the item
     * @param string $name
     *                        The name of the item
     * @param string $default [optional]
     *                        default value
     *
     * @return bool|string
     *         Setting value or false
     *
     * @throws cDbException
     * @throws cException
     */
    public static function get($type, $name, $default = '')
    {
        self::_loadSettings();

        $key = self::_makeKey($type, $name);

        $value = self::_get($key);
        if ($value !== false) {
            return $value;
        }

        $value = getSystemProperty($type, $name);

        if ($value === false || $value === NULL) {
            $value = $default;
        } elseif ($value === '' && $default !== '') {
            // NOTE: A non empty default value overrides an empty value
            $value = $default;
        }

        return $value;
    }

    /**
     * Returns effective setting for a type of properties.
     *
     * Caches also the collected settings, but contrary to get() it returns never cached entries.
     *
     * The order is: System => Client => Client (language) => Group => User
     *
     * System properties can be overridden by the group, and group properties can be overridden by the user.
     *
     * @param string $type
     *         The type of the item
     *
     * @return array
     *         Associative array like $arr[name] = value
     *
     * @throws cDbException
     * @throws cException
     */
    public static function getByType($type)
    {
        self::_loadSettings();

        $settings = getSystemPropertiesByType($type);

        $key = self::_makeKey($type, ' ');
        if (is_array(self::_get($key))) {
            $settings = array_merge($settings, self::_get($key));
        }

        if (isset($settings) && is_array($settings)) {
            return $settings;
        } else {
            return [];
        }
    }

    /**
     * Sets an effective setting.
     *
     * Note: The setting will be set only in cache, not in persistence layer.
     *
     * @param string $type
     *         The type of the item
     * @param string $name
     *         The name of the item
     * @param string $value
     *         The value of the setting
     */
    public static function set($type, $name, $value)
    {
        $key = self::_makeKey($type, $name);
        self::_set($key, $value);
    }

    /**
     * Deletes an effective setting.
     *
     * Note: The setting will be deleted only from cache, not from persistence layer.
     *
     * @param string $type
     *         The type of the item
     * @param string $name
     *         The name of the item
     */
    public static function delete($type, $name)
    {
        $keySuffix = '_' . $type . '_' . $name;
        foreach (self::$_settings as $key => $value) {
            if (cString::findFirstPos($key, $keySuffix) !== false) {
                unset(self::$_settings[$key]);
            }
        }
    }

    /**
     * Resets all properties of the effective settings class.
     *
     * Usable to start getting settings from scratch.
     */
    public static function reset()
    {
        self::$_settings = [];
        self::$_user = self::$_client = self::$_clientLanguage = NULL;
    }

    /**
     * Returns the user object instance.
     *
     * @return cApiUser
     * @throws cDbException
     * @throws cException
     */
    protected static function _getUserInstance()
    {
        if (!isset(self::$_user)) {
            $auth = cRegistry::getAuth();
            self::$_user = new cApiUser($auth->auth['uid']);
        }
        return self::$_user;
    }

    /**
     * Returns the client language object instance.
     *
     * @return cApiClientLanguage
     * @throws cDbException
     * @throws cException
     */
    protected static function _getClientLanguageInstance()
    {
        if (!isset(self::$_clientLanguage)) {
            $client = cRegistry::getClientId();
            $lang = cRegistry::getLanguageId();
            self::$_clientLanguage = new cApiClientLanguage(false, $client, $lang);
        }
        return self::$_clientLanguage;
    }

    /**
     * Returns the language object instance.
     *
     * @return cApiLanguage
     * @throws cDbException
     * @throws cException
     */
    protected static function _getLanguageInstance()
    {
        if (!isset(self::$_language)) {
            $lang = cRegistry::getLanguageId();
            self::$_language = new cApiLanguage($lang);
        }
        return self::$_language;
    }

    /**
     * Returns the client language object instance.
     *
     * @return cApiClient
     * @throws cDbException
     * @throws cException
     */
    protected static function _getClientInstance()
    {
        if (!isset(self::$_client)) {
            $client = cRegistry::getClientId();
            self::$_client = new cApiClient($client);
        }
        return self::$_client;
    }

    /**
     * Setting getter.
     *
     * @param string $key
     *         The setting key
     * @return string|string[]
     *         bool setting value or false
     */
    protected static function _get($key)
    {
        return self::$_settings[$key] ?? false;
    }

    /**
     * Setting setter.
     *
     * @param string $key
     *         The setting key
     * @param string $value|string[]
     *         Value to store
     */
    protected static function _set($key, $value)
    {
        self::$_settings[$key] = $value;
    }

    /**
     * Setting key getter.
     *
     * @param string $type
     *         The type of the item
     * @param string $name
     *         Name of the item
     * @return string
     *         The setting key
     */
    protected static function _makeKey($type, $name)
    {
        return self::_getKeyPrefix() . '_' . $type . '_' . $name;
    }

    /**
     * Returns the prefix for the internal key.
     *
     * @return string
     */
    protected static function _getKeyPrefix()
    {
        $auth = cRegistry::getAuth();
        $prefix = '';

        if ($auth instanceof cAuth) {
            if (!self::_isAuthenticated()) {
                $prefix = cAuth::AUTH_UID_NOBODY;
            } else {
                $prefix = $auth->auth['uid'];
            }
        }

        if (cString::getStringLength($prefix) == 0) {
            $prefix = cAuth::AUTH_UID_NOBODY;
        }

        return $prefix;
    }

    /**
     * Checks global authentication object and if current user is authenticated.
     *
     * @return bool
     */
    protected static function _isAuthenticated()
    {
        $auth = cRegistry::getAuth();
        return $auth instanceof cAuth && $auth->isAuthenticated() && !$auth->isLoginForm();
    }

    /**
     * Saves the passed settings array structure in the type group array.
     *
     * @param array $settings
     * @param array $typeGroup
     *
     * @return void
     */
    protected static function _takeoverTypeGroupSettings(array $settings, array &$typeGroup)
    {
        foreach ($settings as $setting) {
            $key = self::_makeKey($setting['type'], $setting['name']);
            self::_set($key, $setting['value']);
            if (!isset($typeGroup[$setting['type']])) {
                $typeGroup[$setting['type']] = [];
            }
            $typeGroup[$setting['type']][$setting['name']] = $setting['value'];
        }
    }

}
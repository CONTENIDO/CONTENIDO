<?php

/**
 * This file contains the registry class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for global interaction in CONTENIDO.
 *
 * @package    Core
 * @subpackage Backend
 */
class cRegistry
{

    /**
     * Container for application variables.
     * Meant to set and get application wide variables as an alternative to
     * store them in global scope.
     *
     * @var array
     */
    protected static $_appVars = [];

    /**
     * Container for ok messages.
     *
     * @author frederic.schneider
     * @var array
     */
    protected static $_okMessages = [];

    /**
     * Container for information messages.
     *
     * @author konstantinos.katikakis
     * @var array
     */
    protected static $_infoMessages = [];

    /**
     * Container for error messages.
     *
     * @author konstantinos.katikakis
     * @var array
     */
    protected static $_errMessages = [];

    /**
     * Container for warning messages.
     *
     * @author konstantinos.katikakis
     * @var array
     */
    protected static $_warnMessages = [];

    /**
     * @var cApiLanguage|null
     */
    private static $_language;

    /**
     * @var cApiClient|null
     */
    private static $_client;

    /**
     * @var cApiArticle|null
     */
    private static $_article;

    /**
     * @var cApiArticleLanguage|null
     */
    private static $_articleLanguage;

    /**
     * @var cApiCategory|null
     */
    private static $_category;

    /**
     * @var cApiCategoryLanguage|null
     */
    private static $_categoryLanguage;

    /**
     * @var cApiCategoryArticle|null
     */
    private static $_categoryArticle;

    /**
     * Function which returns path after the last possible place changing via
     * configuration file.
     *
     * @param bool $relativeToRoot
     *         Flag to return relative path from project root
     *         Since CONTENIDO 4.10.2
     * @return string
     *         path
     * @author konstantinos.katikakis
     */
    public static function getBackendPath(bool $relativeToRoot = false): string
    {
        $cfg = self::getConfig();
        if (!$relativeToRoot) {
            return $cfg['path']['contenido'];
        } else {
            return str_replace($cfg['path']['frontend'] . '/', '', $cfg['path']['contenido']);
        }
    }

    /**
     * Function which returns the backend URL after the last possible place
     * changing via configuration file.
     *
     * @return string
     *         URL
     * @author konstantinos.katikakis
     */
    public static function getBackendUrl(): string
    {
        $cfg = self::getConfig();
        return $cfg['path']['contenido_fullhtml'];
    }

    /**
     * Function which returns path after the last possible place changing via
     * configuration file.
     * The path point to the current client
     *
     * @return string
     *         path
     * @author konstantinos.katikakis
     */
    public static function getFrontendPath(): string
    {
        $cfgClient = self::getClientConfig();
        $client = self::getClientId();
        return $cfgClient[$client]['path']['frontend'];
    }

    /**
     * Function which returns URL after the last possible place changing via
     * configuration file.
     * The path point to the current client
     *
     * @return string
     *         URL
     * @author konstantinos.katikakis
     */
    public static function getFrontendUrl(): string
    {
        $cfgClient = self::getClientConfig();
        $client = self::getClientId();
        return $cfgClient[$client]['path']['htmlpath'];
    }

    /**
     * Returns the CONTENIDO Session ID stored in the global variable
     * "contenido".
     *
     * @return string
     */
    public static function getBackendSessionId(): string
    {
        return (string) self::_fetchGlobalVariable('contenido');
    }

    /**
     * Returns the CONTENIDO backend language stored in the global variable
     * "belang"
     *
     * @return string
     */
    public static function getBackendLanguage(): string
    {
        return (string) self::_fetchGlobalVariable('belang');
    }

    /**
     * Checks if the edit mode in backend is active or not stored in the global
     * variable "edit"
     *
     * @return bool
     */
    public static function isBackendEditMode(): bool
    {
        return self::_fetchGlobalVariable('edit', false);
    }

    /**
     * Checks if the visual edit mode in backend is active (contenido session and
     * global variable "tpl_visual").
     *
     * @return bool
     * @since CONTENIDO 4.10.2
     */
    public static function isBackendVisualEditMode(): bool
    {
        return self::getBackendSessionId() && self::getArea() === 'tpl_visual';
    }

    /**
     * Returns the current language ID stored in the global variable "lang".
     *
     * @return int
     */
    public static function getLanguageId(): int
    {
        return (int) self::_fetchGlobalVariable(
            'lang',
            self::_fetchGlobalVariable('load_lang', 0)
        );
    }

    /**
     * Returns the loaded cApiLanguage object for the current language.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiLanguage
     * @throws cInvalidArgumentException
     */
    public static function getLanguage(bool $reload = false): cApiLanguage
    {
        $id = self::getLanguageId();
        if ($reload || !self::$_language instanceof cApiLanguage
            || (int) self::$_language->getId() !== $id) {
            self::$_language = self::_fetchItemObject('cApiLanguage', $id);
        }

        return self::$_language;
    }

    /**
     * Returns the current client ID stored in the global variable "client".
     *
     * @return int
     */
    public static function getClientId(): int
    {
        return (int) self::_fetchGlobalVariable(
            'client',
            self::_fetchGlobalVariable('load_client', 0)
        );
    }

    /**
     * Returns the loaded cApiClient object for the current client.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiClient
     * @throws cInvalidArgumentException
     */
    public static function getClient(bool $reload = false): cApiClient
    {
        $id = self::getClientId();
        if ($reload || !self::$_client instanceof cApiClient
            || (int) self::$_client->getId() !== $id) {
            self::$_client = self::_fetchItemObject('cApiClient', $id);
        }

        return self::$_client;
    }

    /**
     * Returns the article id stored in the global variable "idart".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getArticleId(bool $autoDetect = false): int
    {
        // TODO: autoDetect from front_content.php
        return (int) self::_fetchGlobalVariable('idart', 0);
    }

    /**
     * Returns the loaded cApiArticle object for the current article.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiArticle
     * @throws cInvalidArgumentException
     */
    public static function getArticle(bool $reload = false): cApiArticle
    {
        $id = self::getArticleId();
        if ($reload || !self::$_article instanceof cApiArticle
            || (int) self::$_article->getId() !== $id) {
            self::$_article = self::_fetchItemObject('cApiArticle', $id);
        }

        return self::$_article;
    }

    /**
     * Returns the article language id stored in the global variable
     * "idartlang".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getArticleLanguageId(bool $autoDetect = false): int
    {
        // TODO: autoDetect from front_content.php
        return (int) self::_fetchGlobalVariable('idartlang', 0);
    }

    /**
     * Returns the loaded cApiArticleLanguage object for the current article.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiArticleLanguage
     * @throws cInvalidArgumentException
     */
    public static function getArticleLanguage(bool $reload = false): cApiArticleLanguage
    {
        $id = self::getArticleLanguageId();
        if ($reload || !self::$_articleLanguage instanceof cApiArticleLanguage 
            || (int) self::$_articleLanguage->getId() !== $id) {
            self::$_articleLanguage = self::_fetchItemObject('cApiArticleLanguage', $id);
        }

        return self::$_articleLanguage;
    }

    /**
     * Returns the category id stored in the global variable "idcat".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryId(bool $autoDetect = false): int
    {
        // TODO: autoDetect from front_content.php
        return (int) self::_fetchGlobalVariable('idcat', 0);
    }

    /**
     * Returns the loaded cApiCategory object for the current category.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiCategory
     * @throws cInvalidArgumentException
     */
    public static function getCategory(bool $reload = false): cApiCategory
    {
        $id = self::getCategoryId();
        if ($reload || !self::$_category instanceof cApiCategory
            || (int) self::$_category->getId() !== $id) {
            self::$_category = self::_fetchItemObject('cApiCategory', $id);
        }

        return self::$_category;
    }

    /**
     * Returns the category language id stored in the global variable
     * "idcatlang".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryLanguageId(bool $autoDetect = false): int
    {
        // TODO: autoDetect from front_content.php
        return (int) self::_fetchGlobalVariable('idcatlang', 0);
    }

    /**
     * Returns the loaded cApiCategoryLanguage object for the current category.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiCategoryLanguage
     * @throws cInvalidArgumentException
     */
    public static function getCategoryLanguage(bool $reload = false): cApiCategoryLanguage
    {
        $id = self::getCategoryLanguageId();
        if ($reload || !self::$_categoryLanguage instanceof cApiCategoryLanguage
            || (int) self::$_categoryLanguage->getId() !== $id) {
            self::$_categoryLanguage = self::_fetchItemObject('cApiCategoryLanguage', $id);
        }

        return self::$_categoryLanguage;
    }

    /**
     * Returns the category/article relation id stored in the global variable
     * "idcatart".
     *
     * @param bool $autoDetect [optional; default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryArticleId(bool $autoDetect = false): int
    {
        // TODO: autoDetect from front_content.php
        return (int) self::_fetchGlobalVariable('idcatart', 0);
    }

    /**
     * Returns the loaded cApiCategoryArticle object for the current
     * category/article relation.
     *
     * @param bool $reload Flag to re-instantiate an existing object.
     * @return cApiCategoryArticle
     * @throws cInvalidArgumentException
     */
    public static function getCategoryArticle(bool $reload = false): cApiCategoryArticle
    {
        $id = self::getCategoryArticleId();
        if ($reload || !self::$_categoryArticle instanceof cApiCategoryArticle
            || (int) self::$_categoryArticle->getId() !== $id) {
            self::$_categoryArticle = self::_fetchItemObject('cApiCategoryArticle', $id);
        }

        return self::$_categoryArticle;
    }

    /**
     * Returns the current module ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentModuleId(): int
    {
        return (int) self::_fetchGlobalVariable('cCurrentModule', 0);
    }

    /**
     * Returns the current container ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentContainerId(): int
    {
        return (int) self::_fetchGlobalVariable('cCurrentContainer', 0);
    }

    /**
     * Returns the current frame id stored in the global variable "frame".
     *
     * @return string
     * @author thomas.stauer
     */
    public static function getFrame(): string
    {
        return (string) self::_fetchGlobalVariable('frame', '');
    }

    /**
     * Return the session object stored in the global variable "sess".
     *
     * @return cSession|null
     */
    public static function getSession()
    {
        return self::_fetchGlobalVariable('sess');
    }

    /**
     * Returns the auth object stored in the global variable "auth".
     *
     * @return cAuth|null
     */
    public static function getAuth()
    {
        return self::_fetchGlobalVariable('auth');
    }

    /**
     * Returns the area stored in the global variable "area".
     *
     * @return string
     * @author thomas.stauer
     */
    public static function getArea(): string
    {
        return (string) self::_fetchGlobalVariable('area');
    }

    /**
     * Returns the action stored in the global variable "action".
     *
     * @return string
     * @author jann.diekmann
     */
    public static function getAction(): string
    {
        return (string) self::_fetchGlobalVariable('action');
    }

    /**
     * Returns the language when switching languages. Must be set for URL-Build.
     * Stored in the global variable "changelang".
     *
     * @return int
     * @author jann.diekmann
     */
    public static function getChangeLang(): int
    {
        return (int) self::_fetchGlobalVariable('changelang');
    }

    /**
     * Returns the global "idcat" and "idart" of the Error-Site stored in the
     * Client Configurations
     *
     * @return array [
     *      'idcat' => (int)
     *      'idart' => (int)
     * ];
     * @author jann.diekmann
     */
    public static function getErrSite(): array
    {
        $idcat = self::_fetchGlobalVariable('errsite_idcat');
        $idart = self::_fetchGlobalVariable('errsite_idart');

        return [
            'idcat' => $idcat[1],
            'idart' => $idart[1]
        ];
    }

    /**
     * Returns the permission object stored in the global variable "perm".
     *
     * @return cPermission|null
     */
    public static function getPerm()
    {
        return self::_fetchGlobalVariable('perm');
    }

    /**
     * Returns the configuration array stored in the global variable "cfg".
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return self::_fetchGlobalVariable('cfg', []);
    }

    /**
     * This function returns either a full configuration section or the value
     * for a certain configuration option if a $optionName is given.
     * In this case a $default value can be given which will be returned if this
     * option is not defined.
     *
     * @param string $sectionName [optional]
     * @param string $optionName [optional]
     * @param mixed $defaultValue [optional]
     * @return mixed
     */
    public static function getConfigValue(
        string $sectionName = null,
        string $optionName = null,
        $defaultValue = null
    )
    {
        // get general configuration array
        $cfg = self::getConfig();

        // determine configuration section
        $section = [];
        if (isset($cfg[$sectionName])) {
            $section = $cfg[$sectionName];
        }
        if ($optionName === null) {
            return $section;
        }

        // determine configuration value for certain option name of
        // configuration section
        if (is_array($section) && isset($section[$optionName])) {
            return $section[$optionName];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Returns the client configuration array stored in the global variable
     * "cfgClient".
     * If no client ID is specified or is 0 the complete array is returned.
     *
     * @param int $clientId [optional]
     *         Client ID
     * @return array
     */
    public static function getClientConfig($clientId = 0): array
    {
        $clientConfig = self::_fetchGlobalVariable('cfgClient', []);

        if ($clientId == 0) {
            return $clientConfig;
        }

        return $clientConfig[$clientId] ?? [];
    }

    /**
     * Returns a new CONTENIDO database object.
     *
     * @return cDb
     * @todo perhaps its better to instantiate only one object and reset it on
     *       call
     */
    public static function getDb(): cDb
    {
        try {
            $db = new cDb();
        } catch (Exception $e) {
            die($e->getMessage());
        }

        return $db;
    }

    /**
     * Fetches the database table name with its prefix.
     *
     * @param string $index
     *         name of the index
     * @return string
     */
    public static function getDbTableName(string $index): string
    {
        $cfg = self::getConfig();

        if (!is_array($cfg['tab']) || !isset($cfg['tab'][$index])) {
            return '';
        }

        return $cfg['tab'][$index];
    }

    /**
     * Return the global CONTENIDO Execution Chain Registry.
     *
     * @return cApiCecRegistry
     */
    public static function getCecRegistry(): cApiCecRegistry
    {
        return self::_fetchGlobalVariable('_cecRegistry');
    }

    /**
     * Setter for an application variable.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setAppVar(string $key, $value)
    {
        self::$_appVars[$key] = $value;
    }

    /**
     * Getter for an application variable.
     *
     * @param string $key
     * @param mixed $default [optional]
     *         Default value to return, if the application variable doesn't exist
     * @return mixed
     */
    public static function getAppVar(string $key, $default = null)
    {
        return self::$_appVars[$key] ?? $default;
    }

    /**
     * Unsets an existing application variable.
     *
     * @param string $key
     */
    public static function unsetAppVar(string $key)
    {
        if (isset(self::$_appVars[$key])) {
            unset(self::$_appVars[$key]);
        }
    }

    /**
     * Fetches the global variable requested.
     * If variable is not set, the default value is returned.
     *
     * @param string $variableName
     *         name of the global variable
     * @param mixed $defaultValue [optional]
     *         default value
     * @return mixed
     */
    protected final static function _fetchGlobalVariable(string $variableName, $defaultValue = null)
    {
        return $GLOBALS[$variableName] ?? $defaultValue;
    }

    /**
     * Fetches the corresponding Item object for the specific class name and its
     * primary key value.
     *
     * @param string $apiClassName
     *         name of the api class
     * @param int $objectId
     *         primary key value
     *
     * @return Item|object
     *
     * @throws cInvalidArgumentException
     *         if the given objectId is not greater than 0 or the given class does not exist
     */
    protected final static function _fetchItemObject(string $apiClassName, $objectId)
    {
        if ((int)$objectId <= 0) {
            throw new cInvalidArgumentException('Object ID must be greater than 0.');
        }

        if (!class_exists($apiClassName)) {
            throw new cInvalidArgumentException(
                'Requested API object was not found: \'' . $apiClassName . '\''
            );
        }

        return new $apiClassName($objectId);
    }

    /**
     * Bootstraps the CONTENIDO framework and initializes the global variables
     * sess, auth and perm.
     *
     * @param array $features
     *         array with class name definitions
     */
    public final static function bootstrap(array $features)
    {
        $cfg = self::getConfig();

        $sessClass = $authClass = $permClass = NULL;

        $bootstrapFeatures = [
            'sess',
            'auth',
            'perm',
        ];

        foreach ($bootstrapFeatures as $feature) {
            $varFeatureClass = $feature . 'Class';
            if (isset($cfg['bootstrap'][$feature]) && class_exists($cfg['bootstrap'][$feature])) {
                $$varFeatureClass = $cfg['bootstrap'][$feature];
            } elseif (isset($features[$feature]) && class_exists($features[$feature])) {
                $$varFeatureClass = $features[$feature];
            }
        }

        if (isset($sessClass)) {
            global $sess;
            if (!$sess instanceof cSession) {
                /** @var cSession $sess */
                $sess = new $sessClass();
                $sess->start();
            }
        }

        if (isset($authClass)) {
            global $auth;
            if (!$auth instanceof cAuth) {
                /** @var cAuth $auth */
                $auth = new $authClass();
            }
            $auth->start();
        }

        if (isset($permClass)) {
            global $perm;
            if (!$perm instanceof cPermission) {
                /** @var cPermission $perm */
                $perm = new $permClass();
            }
        }
    }

    /**
     * Shutdowns the CONTENIDO framework on page close.
     *
     * @param bool $debugShowAll [optional]
     *
     * @throws cInvalidArgumentException
     * @author frederic.schneider
     *
     */
    public final static function shutdown(bool $debugShowAll = true)
    {
        static $shutdownInvoked;

        if (isset($shutdownInvoked)) {
            return;
        }
        $shutdownInvoked = true;

        if ($debugShowAll) {
            cDebug::showAll();
        }

        $sess = self::getSession();
        if (isset($sess)) {
            $sess->freeze();
        }
    }

    /**
     * Stores an ok message in the cRegistry.
     *
     * @param string $message
     * @author frederic.schneider
     */
    public static function addOkMessage(string $message)
    {
        self::$_okMessages[] = $message;
    }

    /**
     * Stores an information massage in the cRegistry.
     *
     * @param string $message
     * @author konstantinos.katikakis
     */
    public static function addInfoMessage(string $message)
    {
        self::$_infoMessages[] = $message;
    }

    /**
     * Stores an error message in the cRegistry.
     *
     * @param string $message
     * @author konstantinos.katikakis
     */
    public static function addErrorMessage(string $message)
    {
        self::$_errMessages[] = $message;
    }

    /**
     * Stores a warning massage in the cRegistry.
     *
     * @param string $message
     * @author konstantinos.katikakis
     */
    public static function addWarningMessage(string $message)
    {
        self::$_warnMessages[] = $message;
    }

    /**
     * Appends the last ok message that will be outputted
     *
     * @param string $message
     * @author frederic.schneider
     */
    public static function appendLastOkMessage(string $message)
    {
        $key = cArray::getLastKey(self::$_okMessages);
        if (is_null($key)) {
            self::$_okMessages[] = $message;
        } else {
            self::$_okMessages[$key] .= "<br>" . $message;
        }
    }

    /**
     * Appends the last info message that will be outputted
     *
     * @param string $message
     * @author mischa.holz
     */
    public static function appendLastInfoMessage(string $message)
    {
        $key = cArray::getLastKey(self::$_infoMessages);
        if (is_null($key)) {
            self::$_infoMessages[] = $message;
        } else {
            self::$_infoMessages[$key] .= "<br>" . $message;
        }
    }

    /**
     * Appends the last error message that will be outputted
     *
     * @param string $message
     * @author mischa.holz
     */
    public static function appendLastErrorMessage(string $message)
    {
        $key = cArray::getLastKey(self::$_errMessages);
        if (is_null($key)) {
            self::$_errMessages[] = $message;
        } else {
            self::$_errMessages[$key] .= "<br>" . $message;
        }
    }

    /**
     * Appends the last warning that will be outputted
     *
     * @param string $message
     * @author mischa.holz
     */
    public static function appendLastWarningMessage(string $message)
    {
        $key = cArray::getLastKey(self::$_warnMessages);
        if (is_null($key)) {
            self::$_warnMessages[] = $message;
        } else {
            self::$_warnMessages[$key] .= "<br>" . $message;
        }
    }

    /**
     * Return an array with ok message
     *
     * @return array
     * @author frederic.schneider
     */
    public static function getOkMessages(): array
    {
        return self::$_okMessages;
    }

    /**
     * Returns an array with information messages.
     *
     * @return array
     * @author konstantinos.katikakis
     */
    public static function getInfoMessages(): array
    {
        return self::$_infoMessages;
    }

    /**
     * Returns an array with error messages.
     *
     * @return array
     * @author konstantinos.katikakis
     */
    public static function getErrorMessages(): array
    {
        return self::$_errMessages;
    }

    /**
     * Returns an array with warning messages.
     *
     * @return array
     * @author konstantinos.katikakis
     */
    public static function getWarningMessages(): array
    {
        return self::$_warnMessages;
    }

    /**
     * Returns true if the DNT header is not set or not equal to 1.
     * Returns false if the DNT header is equal to 1.
     *
     * @return bool
     *         whether tracking is allowed by the DNT header
     */
    public static function isTrackingAllowed(): bool
    {
        return cSecurity::toInteger($_SERVER['HTTP_DNT'] ?? '0') != 1;
    }

    /**
     * Returns the actual encoding (standard: utf-8)
     *
     * @return string|bool
     *         name of encoding or false if no language found
     */
    public static function getEncoding()
    {
        $encodings = self::getAppVar('languageEncodings', []);
        $id = self::getLanguageId();

        if (!isset($encodings[$id])) {
            try {
                $apiLanguage = new cApiLanguage($id);
            } catch (cDbException $e) {
                return false;
            } catch (cException $e) {
                return false;
            }
            if ($apiLanguage->isLoaded()) {
                $encodings[$id] = trim($apiLanguage->get('encoding'));
                self::setAppVar('languageEncodings', $encodings);
            }
        }

        return $encodings[$id] ?? false;
    }

}

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
class cRegistry {

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
     * Function which returns path after the last possible place changing via
     * configuration file.
     *
     * @param  bool  $relativeToRoot
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
     * @author konstantinos.katikakis
     * @return string
     *         URL
     */
    public static function getBackendUrl()
    {
        $cfg = self::getConfig();
        return $cfg['path']['contenido_fullhtml'];
    }

    /**
     * Function which returns path after the last possible place changing via
     * configuration file.
     * The path point to the current client
     *
     * @author konstantinos.katikakis
     * @return string
     *         path
     */
    public static function getFrontendPath()
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
     * @author konstantinos.katikakis
     * @return string
     *         URL
     */
    public static function getFrontendUrl()
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
    public static function getBackendSessionId()
    {
        return self::_fetchGlobalVariable('contenido');
    }

    /**
     * Returns the CONTENIDO backend language stored in the global variable
     * "belang"
     *
     * @return string
     */
    public static function getBackendLanguage()
    {
        return self::_fetchGlobalVariable('belang');
    }

    /**
     * Checks if the edit mode in backend is active or not stored in the global
     * variable "edit"
     *
     * @return bool
     */
    public static function isBackendEditMode()
    {
        return self::_fetchGlobalVariable('edit', false);
    }

    /**
     * Checks if the visual edit mode in backend is active (contenido session and
     * global variable "tpl_visual").
     *
     * @since CONTENIDO 4.10.2
     * @return bool
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
    public static function getLanguageId()
    {
        return self::_fetchGlobalVariable('lang', self::_fetchGlobalVariable('load_lang', 0));
    }

    /**
     * Returns the loaded cApiLanguage object for the current language.
     *
     * @return cApiLanguage
     *
     * @throws cInvalidArgumentException
     */
    public static function getLanguage()
    {
        /** @var cApiLanguage $obj */
        $obj = self::_fetchItemObject('cApiLanguage', self::getLanguageId());
        return $obj;
    }

    /**
     * Returns the current client ID stored in the global variable "client".
     *
     * @return int
     */
    public static function getClientId()
    {
        return self::_fetchGlobalVariable('client', self::_fetchGlobalVariable('load_client', 0));
    }

    /**
     * Returns the loaded cApiClient object for the current client.
     *
     * @return cApiClient
     *
     * @throws cInvalidArgumentException
     */
    public static function getClient()
    {
        /** @var cApiClient $obj */
        $obj = self::_fetchItemObject('cApiClient', self::getClientId());
        return $obj;
    }

    /**
     * Returns the article id stored in the global variable "idart".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getArticleId($autoDetect = false)
    {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idart', 0);
    }

    /**
     * Returns the loaded cApiArticle object for the current article.
     *
     * @return cApiArticle
     *
     * @throws cInvalidArgumentException
     */
    public static function getArticle()
    {
        /** @var cApiArticle $obj */
        $obj = self::_fetchItemObject('cApiArticle', self::getArticleId());
        return $obj;
    }

    /**
     * Returns the article language id stored in the global variable
     * "idartlang".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getArticleLanguageId($autoDetect = false)
    {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idartlang', 0);
    }

    /**
     * Returns the loaded cApiArticleLanguage object for the current article.
     *
     * @return cApiArticleLanguage
     *
     * @throws cInvalidArgumentException
     */
    public static function getArticleLanguage()
    {
        /** @var cApiArticleLanguage $obj */
        $obj = self::_fetchItemObject('cApiArticleLanguage', self::getArticleLanguageId());
        return $obj;
    }

    /**
     * Returns the category id stored in the global variable "idcat".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryId($autoDetect = false)
    {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcat', 0);
    }

    /**
     * Returns the loaded cApiCategory object for the current category.
     *
     * @return cApiCategory
     *
     * @throws cInvalidArgumentException
     */
    public static function getCategory()
    {
        /** @var cApiCategory $obj */
        $obj = self::_fetchItemObject('cApiCategory', self::getCategoryId());
        return $obj;
    }

    /**
     * Returns the category language id stored in the global variable
     * "idcatlang".
     *
     * @param bool $autoDetect [optional, default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryLanguageId($autoDetect = false)
    {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcatlang', 0);
    }

    /**
     * Returns the loaded cApiCategoryLanguage object for the current category.
     *
     * @return cApiCategoryLanguage
     *
     * @throws cInvalidArgumentException
     */
    public static function getCategoryLanguage()
    {
        /** @var cApiCategoryLanguage $obj */
        $obj = self::_fetchItemObject('cApiCategoryLanguage', self::getCategoryLanguageId());
        return $obj;
    }

    /**
     * Returns the category/article relation id stored in the global variable
     * "idcatart".
     *
     * @param bool $autoDetect [optional; default: false]
     *         If true, the value is tried to detected automatically.
     * @return int
     */
    public static function getCategoryArticleId($autoDetect = false)
    {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcatart', 0);
    }

    /**
     * Returns the loaded cApiCategoryArticle object for the current
     * category/article relation.
     *
     * @return cApiCategoryArticle
     *
     * @throws cInvalidArgumentException
     */
    public static function getCategoryArticle()
    {
        /** @var cApiCategoryArticle $obj */
        $obj = self::_fetchItemObject('cApiCategoryArticle', self::getCategoryArticleId());
        return $obj;
    }

    /**
     * Returns the current module ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentModuleId()
    {
        return self::_fetchGlobalVariable('cCurrentModule', 0);
    }

    /**
     * Returns the current container ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentContainerId()
    {
        return self::_fetchGlobalVariable('cCurrentContainer', 0);
    }

    /**
     * Returns the current frame id stored in the global variable "frame".
     *
     * @author thomas.stauer
     * @return string
     */
    public static function getFrame()
    {
        return self::_fetchGlobalVariable('frame', '');
    }

    /**
     * Return the session object stored in the global variable "sess".
     *
     * @return cSession
     */
    public static function getSession()
    {
        return self::_fetchGlobalVariable('sess');
    }

    /**
     * Returns the auth object stored in the global variable "auth".
     *
     * @return cAuth
     */
    public static function getAuth()
    {
        return self::_fetchGlobalVariable('auth');
    }

    /**
     * Returns the area stored in the global variable "area".
     *
     * @author thomas.stauer
     * @return string
     */
    public static function getArea()
    {
        return self::_fetchGlobalVariable('area');
    }

   /**
     * Returns the action stored in the global variable "action".
     *
     * @author jann.diekmann
     * @return string
     */
    public static function getAction()
    {
        return self::_fetchGlobalVariable('action');
    }

    /**
     * Returns the language when switching languages. Must be set for URL-Build.
     * Stored in the global variable "changelang".
     *
     * @author jann.diekmann
     * @return string
     */
    public static function getChangeLang()
    {
        return self::_fetchGlobalVariable('changelang');
    }

    /**
     * Returns the global "idcat" and "idart" of the Error-Site stored in the
     * Client Configurations
     *
     * @author jann.diekmann
     * @return array [
     *      'idcat' => (int)
     *      'idart' => (int)
     * ];
     */
    public static function getErrSite()
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
     * @return cPermission
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
    public static function getConfig()
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
     * @param string $defaultValue [optional]
     * @return mixed
     */
    public static function getConfigValue($sectionName = NULL, $optionName = NULL, $defaultValue = NULL)
    {
        // get general configuration array
        $cfg = self::getConfig();

        // determine configuration section
        $section = [];
        if (isset($cfg[$sectionName])) {
            $section = $cfg[$sectionName];
        }
        if (NULL === $optionName) {
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
    public static function getClientConfig($clientId = 0)
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
     * @todo perhaps its better to instantiate only one object and reset it on
     *       call
     * @return cDb
     */
    public static function getDb()
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
    public static function getDbTableName($index)
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
    public static function getCecRegistry()
    {
        return self::_fetchGlobalVariable('_cecRegistry');
    }

    /**
     * Setter for an application variable.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setAppVar($key, $value)
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
    public static function getAppVar($key, $default = NULL)
    {
        return self::$_appVars[$key] ?? $default;
    }

    /**
     * Unsets an existing application variable.
     *
     * @param string $key
     */
    public static function unsetAppVar($key)
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
    protected final static function _fetchGlobalVariable($variableName, $defaultValue = NULL)
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
     * @return Item
     *
     * @throws cInvalidArgumentException
     *         if the given objectId is not greater than 0 or the given class does not exist
     */
    protected final static function _fetchItemObject($apiClassName, $objectId)
    {
        if ((int) $objectId <= 0) {
            throw new cInvalidArgumentException('Object ID must be greater than 0.');
        }

        if (!class_exists($apiClassName)) {
            throw new cInvalidArgumentException('Requested API object was not found: \'' . $apiClassName . '\'');
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
    public final static function bootstrap($features)
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
            /** @var cSession $sess */
            $sess = new $sessClass();
            $sess->start();
            if (isset($authClass)) {
                global $auth;
                if (!isset($auth)) {
                    /** @var cAuth $auth */
                    $auth = new $authClass();
                }
                $auth->start();
                if (isset($permClass)) {
                    global $perm;
                    if (!isset($perm)) {
                        /** @var cPermission $perm */
                        $perm = new $permClass();
                    }
                }
            }
        }
    }

    /**
     * Shutdowns the CONTENIDO framework on page close.
     *
     * @author frederic.schneider
     *
     * @param bool $debugShowAll [optional]
     *
     * @throws cInvalidArgumentException
     */
    public final static function shutdown(bool $debugShowAll = true)
    {
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
     * @author frederic.schneider
     * @param string $message
     */
    public static function addOkMessage($message)
    {
        self::$_okMessages[] = $message;
    }

    /**
     * Stores an information massage in the cRegistry.
     *
     * @author konstantinos.katikakis
     * @param string $message
     */
    public static function addInfoMessage($message)
    {
        self::$_infoMessages[] = $message;
    }

    /**
     * Stores an error message in the cRegistry.
     *
     * @author konstantinos.katikakis
     * @param string $message
     */
    public static function addErrorMessage($message)
    {
        self::$_errMessages[] = $message;
    }

    /**
     * Stores a warning massage in the cRegistry.
     *
     * @author konstantinos.katikakis
     * @param string $message
     */
    public static function addWarningMessage($message)
    {
        self::$_warnMessages[] = $message;
    }

    /**
     * Appends the last ok message that will be outputted
     *
     * @author frederic.schneider
     * @param string $message
     */
    public static function appendLastOkMessage($message)
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
     * @author mischa.holz
     * @param string $message
     */
    public static function appendLastInfoMessage($message)
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
     * @author mischa.holz
     * @param string $message
     */
    public static function appendLastErrorMessage($message)
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
     * @author mischa.holz
     * @param string $message
     */
    public static function appendLastWarningMessage($message)
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
     * @author frederic.schneider
     * @return array
     */
    public static function getOkMessages()
    {
        return self::$_okMessages;
    }

    /**
     * Returns an array with information messages.
     *
     * @author konstantinos.katikakis
     * @return array
     */
    public static function getInfoMessages()
    {
        return self::$_infoMessages;
    }

    /**
     * Returns an array with error messages.
     *
     * @author konstantinos.katikakis
     * @return array
     */
    public static function getErrorMessages()
    {
        return self::$_errMessages;
    }

    /**
     * Returns an array with warning messages.
     *
     * @author konstantinos.katikakis
     * @return array
     */
    public static function getWarningMessages()
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

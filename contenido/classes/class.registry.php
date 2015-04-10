<?php
/**
 * This file contains the the registry class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for global interaction in CONTENIDO.
 *
 * @package Core
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
    protected static $_appVars = array();

    /**
     * Container for information messages.
     *
     * @var array
     */
    protected static $_infoMessages = array();

    /**
     * Container for error messages.
     *
     * @var array
     */
    protected static $_errMessages = array();

    /**
     * Container for warning messages.
     *
     * @var array
     */
    protected static $_warnMessages = array();

    /**
     * Function wich returns path after the last possible place changing via
     * configuration file.
     *
     * @return string path
     */
    public static function getBackendPath() {
        $cfg = self::getConfig();
        return $cfg['path']['contenido'];
    }

    /**
     * Function wich returns the backend URL after the last possible place
     * changing via configuration file.
     *
     * @return string URL
     */
    public static function getBackendUrl() {
        $cfg = self::getConfig();
        return $cfg['path']['contenido_fullhtml'];
    }

    /**
     * Function wich returns path after the last possible place changing via
     * configuration file.
     * The path point to the current client
     *
     * @return string path
     */
    public static function getFrontendPath() {
        $cfgClient = self::getClientConfig();
        $client = self::getClientId();
        return $cfgClient[$client]['path']['frontend'];
    }

    /**
     * Function wich returns URL after the last possible place changing via
     * configuration file.
     * The path point to the current client
     *
     * @return string URL
     */
    public static function getFrontendUrl() {
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
    public static function getBackendSessionId() {
        return self::_fetchGlobalVariable('contenido');
    }

    /**
     * Returns the CONTENIDO backend language stored in the global variable
     * "belang"
     *
     * @return string
     */
    public static function getBackendLanguage() {
        return self::_fetchGlobalVariable('belang');
    }

    /**
     * Checks if the edit mode in backend is active or not stored in the global
     * variable "edit"
     *
     * @return boolean
     */
    public static function isBackendEditMode() {
        return self::_fetchGlobalVariable('edit', false);
    }

    /**
     * Returns the current language ID stored in the global variable "lang".
     *
     * @return int
     */
    public static function getLanguageId() {
        return self::_fetchGlobalVariable('lang', self::_fetchGlobalVariable('load_lang', 0));
    }

    /**
     * Returns the loaded cApiLanguage object for the current language.
     *
     * @return cApiLanguage
     */
    public static function getLanguage() {
        return self::_fetchItemObject('cApiLanguage', self::getLanguageId());
    }

    /**
     * Returns the current client ID stored in the global variable "client".
     *
     * @return int
     */
    public static function getClientId() {
        return self::_fetchGlobalVariable('client', self::_fetchGlobalVariable('load_client', 0));
    }

    /**
     * Returns the loaded cApiClient object for the current client.
     *
     * @return cApiClient
     */
    public static function getClient() {
        return self::_fetchItemObject('cApiClient', self::getClientId());
    }

    /**
     * Returns the article id stored in the global variable "idart".
     *
     * @param bool $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return int
     */
    public static function getArticleId($autoDetect = false) {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idart', 0);
    }

    /**
     * Returns the loaded cApiArticle object for the current article.
     *
     * @return cApiArticle
     */
    public static function getArticle() {
        return self::_fetchItemObject('cApiArticle', self::getArticleId());
    }

    /**
     * Returns the article language id stored in the global variable
     * "idartlang".
     *
     * @param bool $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return int
     */
    public static function getArticleLanguageId($autoDetect = false) {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idartlang', 0);
    }

    /**
     * Returns the loaded cApiArticleLanguage object for the current article.
     *
     * @return cApiArticleLanguage
     */
    public static function getArticleLanguage() {
        return self::_fetchItemObject('cApiArticleLanguage', self::getArticleLanguageId());
    }

    /**
     * Returns the category id stored in the global variable "idcat".
     *
     * @param bool $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return int
     */
    public static function getCategoryId($autoDetect = false) {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcat', 0);
    }

    /**
     * Returns the loaded cApiCategory object for the current category.
     *
     * @return cApiCategory
     */
    public static function getCategory() {
        return self::_fetchItemObject('cApiCategory', self::getCategoryId());
    }

    /**
     * Returns the category language id stored in the global variable
     * "idcatlang".
     *
     * @param bool $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return int
     */
    public static function getCategoryLanguageId($autoDetect = false) {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcatlang', 0);
    }

    /**
     * Returns the loaded cApiCategoryLanguage object for the current category.
     *
     * @return cApiCategoryLanguage
     */
    public static function getCategoryLanguage() {
        return self::_fetchItemObject('cApiCategoryLanguage', self::getCategoryLanguageId());
    }

    /**
     * Returns the category/article relation id stored in the global variable
     * "idcatart".
     *
     * @param bool $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return int
     */
    public static function getCategoryArticleId($autoDetect = false) {
        // TODO: autoDetect from front_content.php
        return self::_fetchGlobalVariable('idcatart', 0);
    }

    /**
     * Returns the loaded cApiCategoryArticle object for the current
     * category/article relation.
     *
     * @return cApiCategoryArticle
     */
    public static function getCategoryArticle() {
        return self::_fetchItemObject('cApiCategoryArticle', self::getCategoryArticleId());
    }

    /**
     * Returns the current module ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentModuleId() {
        return self::_fetchGlobalVariable('cCurrentModule', 0);
    }

    /**
     * Returns the current container ID.
     * Note: This function will work only within module code.
     *
     * @return int
     */
    public static function getCurrentContainerId() {
        return self::_fetchGlobalVariable('cCurrentContainer', 0);
    }

    /**
     * Returns the current frame id stored in the global variable "frame".
     *
     * @return string
     */
    public static function getFrame() {
        return self::_fetchGlobalVariable('frame', '');
    }

    /**
     * Return the session object stored in the global variable "sess".
     *
     * @return cSession
     */
    public static function getSession() {
        return self::_fetchGlobalVariable('sess');
    }

    /**
     * Returns the auth object stored in the global variable "auth".
     *
     * @return cAuth
     */
    public static function getAuth() {
        return self::_fetchGlobalVariable('auth');
    }

    /**
     * Returns the area stored in the global variable "area".
     *
     * @return string
     */
    public static function getArea() {
        return self::_fetchGlobalVariable('area');
    }

    /**
     * Returns the permission object stored in the global variable "perm".
     *
     * @return cPermission
     */
    public static function getPerm() {
        return self::_fetchGlobalVariable('perm');
    }

    /**
     * Returns the configuration array stored in the global variable "cfg".
     *
     * @return array
     */
    public static function getConfig() {
        return self::_fetchGlobalVariable('cfg', array());
    }

    /**
     * This function returns either a full configuration section or the value
     * for a certain configuration option if a $optionName is given.
     * In this case a $default value can be given which will be returned if this
     * option is not defined.
     *
     * @param string $sectionName
     * @param string $optionName optional
     * @param string $defaultValue optional
     * @return array string
     */
    public static function getConfigValue($sectionName = NULL, $optionName = NULL, $defaultValue = NULL) {
        // get general configuration array
        $cfg = self::getConfig();

        // determine configuration section
        $section = array();
        if (array_key_exists($sectionName, $cfg)) {
            $section = $cfg[$sectionName];
        }
        if (NULL === $optionName) {
            return $section;
        }

        // determine configuration value for certain option name of
        // configuration section
        $value = $defaultValue;
        if (is_array($cfg[$sectionName])) {
            if (array_key_exists($optionName, $section)) {
                $value = $section[$optionName];
            }
        }
        return $value;
    }

    /**
     * Returns the client configuration array stored in the global variable
     * "cfgClient".
     * If no client ID is specified or is 0 the complete array is returned.
     *
     * @param int $clientId Client ID (optional)
     * @return array
     */
    public static function getClientConfig($clientId = 0) {
        $clientConfig = self::_fetchGlobalVariable('cfgClient', array());

        if ($clientId == 0) {
            return $clientConfig;
        }

        return (isset($clientConfig[$clientId]) ? $clientConfig[$clientId] : array());
    }

    /**
     * Returns a new CONTENIDO database object.
     *
     * @todo perhaps its better to instantiate only one object and reset it on
     *       call
     * @return cDb
     */
    public static function getDb() {
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
     * @param string $index name of the index
     * @return string
     */
    public static function getDbTableName($index) {
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
    public static function getCecRegistry() {
        return self::_fetchGlobalVariable('_cecRegistry');
    }

    /**
     * Setter for an application variable.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setAppVar($key, $value) {
        self::$_appVars[$key] = $value;
    }

    /**
     * Getter for an application variable.
     *
     * @param string $key
     * @param mixed $default Default value to return, if the application
     *        variable doesn't exists
     * @return mixed
     */
    public static function getAppVar($key, $default = NULL) {
        return (isset(self::$_appVars[$key])) ? self::$_appVars[$key] : $default;
    }

    /**
     * Unsets an existing application variable.
     *
     * @param string $key
     */
    public static function unsetAppVar($key) {
        if (isset(self::$_appVars[$key])) {
            unset(self::$_appVars[$key]);
        }
    }

    /**
     * Fetches the global variable requested.
     * If variable is not set, the default value is returned.
     *
     * @param string $variableName name of the global variable
     * @param mixed $defaultValue default value
     * @return mixed
     */
    protected final static function _fetchGlobalVariable($variableName, $defaultValue = NULL) {
        if (!isset($GLOBALS[$variableName])) {
            return $defaultValue;
        }

        return $GLOBALS[$variableName];
    }

    /**
     * Fetches the corresponding Item object for the specific class name and its
     * primary key value.
     *
     * @param string $apiClassName name of the api class
     * @param int $objectId primary key value
     * @throws cInvalidArgumentException if the given objectId is not greater
     *         than 0 or the given class does not exist
     * @return Item
     */
    protected final static function _fetchItemObject($apiClassName, $objectId) {
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
     * @param array $features array with class name definitions
     */
    public final static function bootstrap($features) {
        $cfg = self::getConfig();

        $sessClass = $authClass = $permClass = NULL;

        $bootstrapFeatures = array(
            'sess',
            'auth',
            'perm'
        );

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

            $sess = new $sessClass();
            $sess->start();
            if (isset($authClass)) {
                global $auth;
                if (!isset($auth)) {
                    $auth = new $authClass();
                }
                $auth->start();

                if (isset($permClass)) {
                    global $perm;
                    if (!isset($perm)) {
                        $perm = new $permClass();
                    }
                }
            }
        }
    }

    /**
     * Shutdowns the CONTENIDO framework on page close.
     *
     * @param bool $debugShowAll
     */
    public final static function shutdown($debugShowAll = true) {
        if ($debugShowAll == true) {
            cDebug::showAll();
        }

        $sess = self::getSession();
        if (isset($sess)) {
            $sess->freeze();
        }
    }

    /**
     * Stores an information massage in the cRegistry.
     *
     * @param string $message
     */
    public static function addInfoMessage($message) {
        array_push(self::$_infoMessages, $message);
    }

    /**
     * Stores an error massage in the cRegistry.
     *
     * @param string $message
     */
    public static function addErrorMessage($message) {
        array_push(self::$_errMessages, $message);
    }

    /**
     * Stores an warning massage in the cRegistry.
     *
     * @param string $message
     */
    public static function addWarningMessage($message) {
        array_push(self::$_warnMessages, $message);
    }

    /**
     * Appends the last info message that will be outputted
     *
     * @param string $message
     */
    public static function appendLastInfoMessage($message) {
        if(count(self::$_infoMessages) == 0) {
            self::$_infoMessages[] = $message;
            return;
        }
        end(self::$_infoMessages);
        $lastKey = key(self::$_infoMessages);
        self::$_infoMessages[$lastKey] .= "<br>" . $message;
        reset(self::$_infoMessages);
    }

    /**
     * Appends the last error message that will be outputted
     *
     * @param string $message
     */
    public static function appendLastErrorMessage($message) {
        if(count(self::$_errMessages) == 0) {
            self::$_errMessages[] = $message;
            return;
        }
        end(self::$_errMessages);
        $lastKey = key(self::$_errMessages);
        self::$_errMessages[$lastKey] .= "<br>" . $message;
        reset(self::$_errMessages);
    }

    /**
     * Appends the last warning that will be outputted
     *
     * @param string $message
     */
    public static function appendLastWarningMessage($message) {
        if(count(self::$_warnMessages) == 0) {
            self::$_warnMessages[] = $message;
            return;
        }
        end(self::$_warnMessages);
        $lastKey = key(self::$_warnMessages);
        self::$_warnMessages[$lastKey] .= "<br>" . $message;
        reset(self::$_warnMessages);
    }

    /**
     * Returns an array with information messages.
     *
     * @return array
     */
    public static function getInfoMessages() {
        return self::$_infoMessages;
    }

    /**
     * Returns an array with error messages.
     *
     * @return array
     */
    public static function getErrorMessages() {
        return self::$_errMessages;
    }

    /**
     * Returns an array with warning messages.
     *
     * @return array
     */
    public static function getWarningMessages() {
        return self::$_warnMessages;
    }

    /**
     * Returns true if the DNT header is set and equal to 1.
     * Returns false if the DNT header is unset or not equal to 1.
     *
     * @return boolean whether tracking is allowed by the DNT header
     */
    public static function isTrackingAllowed() {
        return (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] != 1) || !isset($_SERVER['HTTP_DNT']);
    }

    /**
    * Returns the actual encoding (standard: utf-8)
    *
    * @return string name of encoding
    * @return boolean false if no language found
     */
    public static function getEncoding() {
    
        $apiLanguage = new cApiLanguage(self::getLanguageId());
        if ($apiLanguage->isLoaded()) {
            return trim($apiLanguage->get('encoding'));
        }

        return false;
    }
}

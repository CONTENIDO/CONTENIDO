<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file contains the global registry class.
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.1
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9.0
 */

/**
 * This class contains functions for global interaction in CONTENIDO.
 */
class cRegistry {

    /**
     * Container for application variables.
     * Meant to set and get application wide
     * variables as an alternative to store them in global scope.
     *
     * @var array
     */
    protected static $_appVars = array();

    /**
     * Container for information messages
     *
     * @var array
     */
    public static $_infoMessages = array();

    /**
     * Container for error messages
     *
     * @var array
     */
    public static $_errMessages = array();

    /**
     * Container for warning messages
     *
     * @var array
     */
    public static $_warnMessages = array();

    /**
     * Function wich returns path after the last possible place
     * changing via configuration file.
     *
     * @return path
     */
    public static function getBackendPath() {
        $cfg = self::getConfig();
        return $cfg['path']['contenido'];
    }

    /**
     * Function wich returns path after the last possible place
     * changing via configuration file.
     * The path point to the current client
     *
     * @return path
     */
    public static function getFrontendPath() {
        $cfgClient = self::getClientConfig();
        $client = self::getClientId();
        return $cfgClient[$client]['path']['frontend'];
    }

    /**
     * Function wich returns the backend URL after the last possible
     * place changing via configuration file.
     *
     * @return URL
     */
    public static function getBackendUrl() {
        $cfg = self::getConfig();
        return $cfg['path']['contenido_fullhtml'];
    }

    /**
     * Function wich returns URL after the last possible place
     * changing via configuration file.
     * The path point to the current client
     *
     * @return URL
     */
    public static function getFrontendUrl() {
        $cfgClient = self::getClientConfig();
        $client = self::getClientId();
        return $cfgClient[$client]['path']['htmlpath'];
    }

    /**
     * Returns the CONTENIDO Session ID stored in the global variable
     * "contenido"
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
     * @return integer
     */
    public static function getLanguageId() {
        return self::_fetchGlobalVariable('lang', self::_fetchGlobalVariable('load_lang', 0) );
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
     * @return integer
     */
    public static function getClientId() {
        return self::_fetchGlobalVariable('client', self::_fetchGlobalVariable('load_client', 0) );
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
     * @param boolean $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     *
     * @return integer
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
     * @param boolean $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     *
     * @return integer
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
     * @param boolean $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return integer
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
     * @param boolean $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return integer
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
     * @param boolean $autoDetect If true, the value is tried to detected
     *        automatically. (default: false)
     * @return integer
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
     * @return integer
     */
    public static function getCurrentModuleId() {
        return self::_fetchGlobalVariable('cCurrentModule', 0);
    }

    /**
     * Returns the current container ID.
     * Note: This function will work only within module code.
     *
     * @return integer
     */
    public static function getCurrentContainerId() {
        return self::_fetchGlobalVariable('cCurrentContainer', 0);
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
     * Returns the client configuration array stored in the global variable
     * "cfgClient".
     * If no client ID is specified or is 0 the complete array is returned.
     *
     * @param integer $clientId Client ID (optional)
     *
     * @return array
     */
    public static function getClientConfig($clientId = 0) {
        $clientConfig = self::_fetchGlobalVariable('cfgClient', array());

        if ($clientId == 0) {
            return $clientConfig;
        }

        return (isset($clientConfig[$clientId])? $clientConfig[$clientId] : array());
    }

    /**
     * Returns a new CONTENIDO database object.
     *
     * @todo : Perhaps its better to instantiate only one object and reset it on
     *       call.
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
     *
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
     *        variable
     *        doesn't exists
     * @return mixed
     */
    public static function getAppVar($key, $default = null) {
        return (isset(self::$_appVars[$key]))? self::$_appVars[$key] : $default;
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
     *
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
     * @param integer $objectId primary key value
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
     * Bootstraps the CONTENIDO framework and initializes
     * the global variables sess, auth and perm.
     *
     * @param $features array array with class name definitions
     *
     * @return void
     */
    public final static function bootstrap($features) {
        $cfg = self::getConfig();

        $bootstrapFeatures = array(
            'sess',
            'auth',
            'perm'
        );
        foreach ($bootstrapFeatures as $feature) {
            $varFeatureClass = $feature . 'Class';
            if (isset($features[$feature])) {
                $$varFeatureClass = $features[$feature];
            } else {
                $$varFeatureClass = $cfg['bootstrap'][$feature];
            }
        }

        if (class_exists($sessClass)) {
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
     * @return void
     */
    public final static function shutdown() {
        cDebug::showAll();

        $sess = self::_fetchGlobalVariable('sess');
        if (isset($sess)) {
            $sess->freeze();
        }
    }

    /**
     * Stores an information massage in the cRegistry
     *
     * @param string message
     *
     */
    public static function addInfoMessage($message) {
        self::$_infoMessages[] = $message;
    }

    /**
     * Stores an information massage in the cRegistry
     *
     * @param string message
     *
     */
    public static function addErrorMessage($message) {
        array_push(self::$_errMessages, $message);
    }

    /**
     * Stores an information massage in the cRegistry
     *
     * @param string message
     *
     */
    public static function addWarningMessage($message) {
        array_push(self::$_warnMessages, $message);
    }

    /**
     * Returns an array with information messages
     *
     * @return array
     *
     */
    public static function getInfoMessages() {
        return self::$_infoMessages;
    }

    /**
     * Returns an array with error messages
     *
     * @return array
     *
     */
    public static function getErrorMessages() {
        return self::$_errMessages;
    }

    /**
     * Returns an array with warning messages
     *
     * @return array
     *
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
        return (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1);
    }

}

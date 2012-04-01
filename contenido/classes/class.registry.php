<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file contains the global registry class.
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

/**
 * This class contains functions for global interaction in CONTENIDO.
 */
class cRegistry {
	/**
	 * Returns the CONTENIDO Session ID stored in the global variable "contenido"
	 * @return string
	 */
	public static function getBackendSessionId() {
		return self::_fetchGlobalVariable('contenido');
	}
	
	/**
	 * Checks if the edit mode in backend is active or not stored in the global variable "edit"
	 * @return boolean
	 */
	public static function isBackendEditMode() {
		return self::_fetchGlobalVariable('edit', false);
	}
	
	/**
	 * Returns the current language ID stored in the global variable "lang".
	 * @return integer
	 */
	public static function getLanguageId() {
		return self::_fetchGlobalVariable('lang', 0);
	}
	
	/**
	 * Returns the loaded cApiLanguage object for the current language.
	 * @return cApiLanguage
	 */
	public static function getLanguage() {
		return self::_fetchItemObject('cApiLanguage', self::getLanguageId());
	}
	
	/**
	 * Returns the current client ID stored in the global variable "client".
	 * @return integer
	 */
	public static function getClientId() {
		return self::_fetchGlobalVariable('client', 0);
	}
	
	/**
	 * Returns the loaded cApiClient object for the current client.
	 * @return cApiClient
	 */
	public static function getClient() {
		return self::_fetchItemObject('cApiClient', self::getClientId());
	}
	
	/**
	 * Returns the article id stored in the global variable "idart".
	 * 
	 * @param	boolean	$autoDetect	If true, the value is tried to detected automatically. (default: false)
	 *
	 * @return	integer
	 */
	public static function getArticleId($autoDetect = false) {
		// TODO: autoDetect from front_content.php
		
		return self::_fetchGlobalVariable('idart', 0);
	}
	
	/**
	 * Returns the loaded cApiArticle object for the current article.
	 * @return cApiArticle
	 */
	public static function getArticle() {
		return self::_fetchItemObject('cApiArticle', self::getArticleId());
	}
	
	/**
	 * Returns the article language id stored in the global variable "idartlang".
	 * 
	 * @param	boolean	$autoDetect	If true, the value is tried to detected automatically. (default: false)
	 *
	 * @return	integer
	 */
	public static function getArticleLanguageId($autoDetect = false) {
		// TODO: autoDetect from front_content.php
	
		return self::_fetchGlobalVariable('idartlang', 0);
	}
	
	/**
	 * Returns the loaded cApiArticleLanguage object for the current article.
	 * @return cApiArticleLanguage
	 */
	public static function getArticleLanguage() {
		return self::_fetchItemObject('cApiArticleLanguage', self::getArticleLanguageId());
	}
	
	/**
	 * Returns the category id stored in the global variable "idcat".
	 * 
	 * @param	boolean	$autoDetect	If true, the value is tried to detected automatically. (default: false)
	 *
	 * @return	integer
	 */
	public static function getCategoryId($autoDetect = false) {
		// TODO: autoDetect from front_content.php
		
		return self::_fetchGlobalVariable('idcat', 0);
	}
	
	/**
	 * Returns the loaded cApiCategory object for the current category.
	 * @return cApiCategory
	 */
	public static function getCategory() {
		return self::_fetchItemObject('cApiCategory', self::getCategoryId());
	}
	
	/**
	 * Returns the category language id stored in the global variable "idcatlang".
	 * 
	 * @param	boolean	$autoDetect	If true, the value is tried to detected automatically. (default: false)
	 *
	 * @return	integer
	 */
	public static function getCategoryLanguageId($autoDetect = false) {
		// TODO: autoDetect from front_content.php
		
		return self::_fetchGlobalVariable('idcatlang', 0);
	}
	
	/**
	 * Returns the loaded cApiCategoryLanguage object for the current category.
	 * @return cApiCategoryLanguage
	 */
	public static function getCategoryLanguage() {
		return self::_fetchItemObject('cApiCategoryLanguage', self::getCategoryLanguageId());
	}
	
	/**
	 * Returns the category/article relation id stored in the global variable "idcatart".
	 * 
	 * @param	boolean	$autoDetect	If true, the value is tried to detected automatically. (default: false)
	 *
	 * @return	integer
	 */
	public static function getCategoryArticleId($autoDetect = false) {
		// TODO: autoDetect from front_content.php
		
		return self::_fetchGlobalVariable('idcatart', 0);
	}
	
	/**
	 * Returns the loaded cApiCategoryArticle object for the current category/article relation.
	 * @return cApiCategoryArticle
	 */
	public static function getCategoryArticle() {
		return self::_fetchItemObject('cApiCategoryArticle', self::getCategoryArticleId());
	}
	
	/** 
	 * Returns the current module ID.
	 * Note: This function will work only within module code.
	 * @return integer
	 */
	public static function getCurrentModuleId() {
		return self::_fetchGlobalVariable('cCurrentModule', 0);
	}

	/** 
	 * Returns the current container ID.
	 * Note: This function will work only within module code.
	 * @return integer
	 */
	public static function getCurrentContainerId() {
		return self::_fetchGlobalVariable('cCurrentContainer', 0);
	}
	
	/**
	 * Return the session object stored in the global variable "sess".
	 * @return Contenido_Session
	 */
	public static function getSession() {
		return self::_fetchGlobalVariable('sess');
	}
	
	/**
	 * Returns the auth object stored in the global variable "auth".
	 * @return Auth
	 */
	public static function getAuth() {
		return self::_fetchGlobalVariable('auth');
	}
	
	/**
	 * Returns the permission object stored in the global variable "perm".
	 * @return Contenido_Perm
	 */
	public static function getPerm() {
		return self::_fetchGlobalVariable('perm');
	}
	
	/**
	 * Returns the configuration array stored in the global variable "cfg".
	 * @return array
	 */
	public static function getConfig() {
		return self::_fetchGlobalVariable('cfg', array());
	}

	/**
	 * Returns the client configuration array stored in the global variable "cfgClient".
	 * If no client ID is specified or is 0 the complete array is returned.
	 *
	 * @param	integer $clientId	Client ID (optional)
	 *
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
	 * @TODO: Perhaps its better to instantiate only one object and reset it on call.
	 * @return DB_Contenido
	 */
	public static function getDb() {
		return new DB_Contenido();
	}
	
	/**
	 * Fetches the database table name with its prefix.
	 *
	 * @param	string	$index	name of the index
	 *
	 * @return	string
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
	 * @return cApiCECRegistry
	 */
	public static function getCecRegistry() {
		return self::_fetchGlobalVariable('_cecRegistry');
	}
	
	/**
	 * Fetches the global variable requested. If variable is not set, the default value is returned.
	 *
	 * @param	string	$variableName	name of the global variable
	 * @param	mixed	$defaultValue	default value
	 *
	 * @return	mixed
	 */
	protected final static function _fetchGlobalVariable($variableName, $defaultValue = NULL) {
		if (!isset($GLOBALS[$variableName])) {
			return $defaultValue;
		}
		
		return $GLOBALS[$variableName];
	}
	
	/**
	 * Fetches the corresponding Item object for the specific class name and its primary key value.
	 *
	 * @param	string	$apiClassName	name of the api class
	 * @param	integer	$objectId		primary key value
	 *
	 * @throws	UnexpectedValueException
	 *
	 * @return	Item
	 */
	protected final static function _fetchItemObject($apiClassName, $objectId) {
		if ((int) $objectId <= 0) {
			throw new UnexpectedValueException('Object ID must be greater than 0.');
		}
		
		if (!class_exists($apiClassName)) {
			throw new UnexpectedValueException('Requested API object was not found: \'' . $apiClassName . '\'');
		}
		
		return new $apiClassName($objectId);
	}
}
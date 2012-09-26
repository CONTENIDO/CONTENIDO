<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Abstract upgade job class
 *
 * @package    CONTENIDO Setup upgrade
 * @version    0.1
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */


if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


abstract class cUpgradeJobAbstract {

    protected $_oDb;
    protected $_aCfg;
    protected $_setupType;
    protected static $_clients;
    protected static $_languages;
    protected static $_rootPath;
    protected static $_rootHttpPath;

    /**
     * Constructor, sets some properties
     * @param  DB_Contenido  $db
     * @param  array  $cfg  Main configuration array
     * @param  array  $cfgClient  Clients configuration array
     */
    public function __construct($db, $cfg, $cfgClient) {
        $this->_oDb = $db;
        $this->_aCfg = (is_array($cfg)) ? $cfg : $GLOBALS['cfg'];
        $this->_aCfgClient = (is_array($cfgClient)) ? $cfg : $GLOBALS['cfgClient'];
        $this->_setupType = $_SESSION['setuptype'];
        // set default configuration for DB connection
        DB_Contenido::setDefaultConfiguration($cfg['db']);

        if (!isset(self::$_rootPath)) {
            list($rootPath, $rootHttpPath) = getSystemDirectories();
            self::$_rootPath = $rootPath;
            self::$_rootHttpPath = $rootHttpPath;
        }

        if (!isset(self::$_clients)) {
            self::$_clients = $this->_getAllClients();
        }
        if (!isset(self::$_languages)) {
            self::$_languages = $this->_getAllLanguages();
        }
    }

    /**
     * Main function for each upgrade job. Each upgrade job has to implement this!
     */
    public abstract function execute();

    /**
     * Returns list of all available clients
     * @return cApiClient[]
     */
    protected function _getAllClients() {
        $aClients = array();
        $oClientColl = new cApiClientCollection();
        $oClientColl->select();
        while (($oClient = $oClientColl->next()) !== false) {
            $obj = clone $oClient;
            $aClients[$obj->get('idclient')] = $obj;
        }
        return $aClients;
    }

    /**
     * Returns list of all available languages
     * @return cApiLanguage[]
     */
    protected function _getAllLanguages() {
        $aLanguages = array();
        $oLanguageColl = new cApiLanguageCollection();
        $oLanguageColl->select();
        while (($oLang = $oLanguageColl->next()) !== false) {
            $obj = clone $oLang;
            $aLanguages[$obj->get('idlang')] = $obj;
        }
        return $aLanguages;
    }

    /**
     * Logs passed setup error, wrapper for logSetupFailure() function
     * @param  string  $errorMsg
     */
    protected _logError($errorMsg) {
        $className = get_class($this);
        logSetupFailure($className . ': ' . $errorMsg. "\n");
    }
}

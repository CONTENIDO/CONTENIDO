<?php
/**
 * This file contains the abstract upgrade job class.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract upgrade job class.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
abstract class cUpgradeJobAbstract {

    /**
     * @var cDb
     */
    protected $_oDb;

    /**
     * @var array
     */
    protected $_aCfg;

    /**
     * @var string
     */
    protected $_setupType;

    /**
     * @var string
     */
    protected $_version;

    /**
     * @var array
     */
    protected $_aCfgClient;

    /**
     * @var cApiClient[]
     */
    protected static $_clients;

    /**
     * @var cApiLanguage[]
     */
    protected static $_languages;

    /**
     * @var string
     */
    protected static $_rootPath;

    /**
     * @var string
     */
    protected static $_rootHttpPath;

    /**
     * This must be set. 0 means this upgrade job will be executed every time.
     * Anyhting else should be a valid CONTENIDO version. Only if the upgraded version
     * is older than this string the job will be executed.
     *
     * Setting this to '4.8.18' would mean that any version lower than 4.8.18 will get the upgrade job.
     * @var string
     */
    public $maxVersion = "0";

    /**
     * Constructor, sets some properties
     * @param  cDb $db
     * @param  array|mixed  $cfg  Main configuration array
     * @param  array|mixed  $cfgClient  Clients configuration array
     * @param  string $version The CONTENIDO version which is upgraded
     */
    public function __construct(cDb $db, $cfg, $cfgClient, $version) {
        $this->_version = $version;
        $this->_oDb = $db;
        $this->_aCfg = (is_array($cfg)) ? $cfg : $GLOBALS['cfg'];
        $this->_aCfgClient = (is_array($cfgClient)) ? $cfg : $GLOBALS['cfgClient'];
        $this->_setupType = $_SESSION['setuptype'];
        // set default configuration for DB connection
        cDb::setDefaultConfiguration($cfg['db']);

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
     * This function will perform the version check and execute the job if it succeeds.
     *
     * Do not override this.
     */
    final public function execute() {
        if (version_compare($this->_version, $this->maxVersion, "<") || $this->maxVersion === "0") {
            $this->_execute();
        }
    }

    /**
     * Main function for each upgrade job. Each upgrade job has to implement this!
     */
    public abstract function _execute();

    /**
     * Returns list of all available clients
     * @return cApiClient[]
     */
    protected function _getAllClients() {
        $oClientColl = new cApiClientCollection();
        $oClientColl->select();

        $aClients = [];
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
        $oLanguageColl = new cApiLanguageCollection();
        $oLanguageColl->select();

        $aLanguages = [];
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
    protected function _logError($errorMsg) {
        $className = get_class($this);
        logSetupFailure($className . ': ' . $errorMsg. "\n");
    }
}

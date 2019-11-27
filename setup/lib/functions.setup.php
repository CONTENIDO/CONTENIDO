<?php
/**
 * This file contains various helper functions to deal with the setup process.
 *
 * @package    Setup
 * @subpackage Helper
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Generates the step display.
 *
 * @param   int  $iCurrentStep  The current step to display active.
 * @return  string
 */
function cGenerateSetupStepsDisplay($iCurrentStep) {
    if (!defined('CON_SETUP_STEPS')) {
        return '';
    }
    $sStepsPath = '';
    for ($i = 1; $i < CON_SETUP_STEPS + 1; $i++) {
        $sCssActive = '';
        if ($iCurrentStep == $i) {
            $sCssActive = 'active';
        }
        $sStepsPath .= '<span class="' . $sCssActive . '">&nbsp;' . strval($i) . '&nbsp;</span>&nbsp;&nbsp;&nbsp;';
    }
    return $sStepsPath;
}

/**
 * Logs general setup failures into setuplog.txt in logs directory.
 *
 * @param string $sErrorMessage Message to log in file
 * @throws cInvalidArgumentException
 * @global  array $cfg
 */
function logSetupFailure($sErrorMessage) {
    global $cfg;
    cFileHandler::write($cfg['path']['contenido_logs'] . 'setuplog.txt', $sErrorMessage . PHP_EOL . PHP_EOL, true);
}

/**
 * Initializes clients configuration, if not done before
 * @param bool $reset Flag to reset any existing client configuration
 * @throws cDbException
 * @throws cInvalidArgumentException
 * @global  array $cfg
 * @global  array $cfgClient
 */
function setupInitializeCfgClient($reset = false) {
    global $cfg, $cfgClient;

    if (true === $reset) {
        $cfgClient = array();
    }

    // Load client configuration
    if (empty($cfgClient) || !isset($cfgClient['set'])) {
        if (cFileHandler::exists($cfg['path']['contenido_config'] . 'config.clients.php')) {
            require($cfg['path']['contenido_config'] . 'config.clients.php');
        } else {
            $db = getSetupMySQLDBConnection();

            $db->query("SELECT * FROM `%s`", $cfg["tab"]["clients"]);
            while ($db->nextRecord()) {
                updateClientCache($db->f("idclient"), $db->f("htmlpath"), $db->f("frontendpath"));
            }
        }
    }
}

/**
 * Check configuration path for the environment
 * If no configuration for environment found, copy from production
 * @param $installationPath
 * @throws cException
 * @throws cInvalidArgumentException
 */
function setupCheckConfiguration($installationPath) {
    $configPath = $installationPath . '/data/config/' . CON_ENVIRONMENT;
    if (!cFileHandler::exists($configPath)) {
        // create environment config
        mkdir($configPath);
        // if not successful throw exception
        if (!cFileHandler::exists($configPath)) {
            throw new cException('Can not create environment directory: data folder is not writable');
        }
        // get config source path
        $configPathProduction = $installationPath . '/data/config/production/';
        // load config source directory
        $directoryIterator = new DirectoryIterator($configPathProduction);
        // iterate through files
        foreach ($directoryIterator as $dirContent) {
            // check file is not dot and file
            if ($dirContent->isFile() && !$dirContent->isDot()) {
                // get filename
                $configFileName = $dirContent->getFilename();
                // build source string
                $source = $configPathProduction . $configFileName;
                // build target string
                $target = $configPath . '/' . $configFileName;
                // try to copy from source to target, if not successful throw exception
                if (!copy($source, $target)) {
                    throw new cException('Can not copy configuration files for the environment: environment folder is not writable');
                }
            }
        }
    }
}

/**
 * Initializes the configuration
 * @global $cfg
 */
function setupInitializeConfig() {
    global $cfg;

    // Prepare $cfg array
    if (!is_array($cfg)) {
        $cfg = [];
    }
    foreach (['wysiwyg', 'path', 'sql'] as $name) {
        if (!isset($cfg[$name]) || !is_array($cfg[$name])) {
            $cfg[$name] = [];
        }
    }

    $systemDirs = getSystemDirectories();

    // Set some basic configuration
    $cfg['wysiwyg']['editor'] = 'tinymce4';
    $cfg['path']['frontend'] = CON_FRONTEND_PATH;
    $cfg['path']['contenido'] = $cfg['path']['frontend'] . '/contenido/';
    $cfg['path']['contenido_config'] = CON_FRONTEND_PATH . '/data/config/' . CON_ENVIRONMENT . '/';
    $cfg['path']['contenido_fullhtml'] = $systemDirs[1] . '/contenido/';
    $cfg['path']['all_wysiwyg'] = $cfg['path']['contenido']  . 'external/wysiwyg/';
    $cfg['path']['all_wysiwyg_html'] = $cfg['path']['contenido_fullhtml'] . 'external/wysiwyg/';
    $cfg['path']['wysiwyg_html'] = $cfg['path']['all_wysiwyg_html'] . $cfg['wysiwyg']['editor'] . '/';

    // DB related settings
    $cfg['sql']['sqlprefix'] = (isset($_SESSION['dbprefix'])) ? $_SESSION['dbprefix'] : 'con';
    $cfg['db'] = array(
        'connection' => array(
            'host' => (isset($_SESSION['dbhost'])) ? $_SESSION['dbhost'] : '',
            'database' => (isset($_SESSION['dbname'])) ? $_SESSION['dbname'] : '',
            'user' => (isset($_SESSION['dbuser'])) ? $_SESSION['dbuser'] : '',
            'password' => (isset($_SESSION['dbpass'])) ? $_SESSION['dbpass'] : '',
            'charset' => (isset($_SESSION['dbcharset'])) ? $_SESSION['dbcharset'] : ''
        ),
        'haltBehavior' => 'report',
        'haltMsgPrefix' => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
        'enableProfiling' => false,
    );
}

/**
 * Updates the configuration and set PHP settings
 * @global $cfg
 */
function setupUpdateConfig() {
    global $cfg;

    // Takeover configured PHP settings
    if (isset($cfg['php_settings']) && is_array($cfg['php_settings'])) {
        foreach ($cfg['php_settings'] as $settingName => $value) {
            // date.timezone is handled separately
            if ($settingName !== 'date.timezone') {
                @ini_set($settingName, $value);
            }
        }
    }
    error_reporting($cfg['php_error_reporting']);

    // force date.timezone setting
    $timezoneCfg = $cfg['php_settings']['date.timezone'];
    if (!empty($timezoneCfg) && ini_get('date.timezone') !== $timezoneCfg) {
        // if the timezone setting from the cfg differs from the php.ini setting, set timezone from CFG
        date_default_timezone_set($timezoneCfg);
    } else if (empty($timezoneCfg) && (ini_get('date.timezone') === '' || ini_get('date.timezone') === false)) {
        // if there are no timezone settings, set UTC timezone
        date_default_timezone_set('UTC');
    }
}

?>
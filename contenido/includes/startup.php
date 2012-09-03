<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Central CONTENIDO file to initialize the application. Performs following steps:
 * - Initial PHP setting
 * - Does basic security check
 * - Includes configurations
 * - Runs validation of request variables
 * - Loads available login languages
 * - Initializes CEC
 * - Includes userdefined configuration
 * - Sets/Checks DB connection
 * - Initializes UrlBuilder
 *
 * @TODO: Collect all startup (bootstrap) related jobs into this file...
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.2
 * @author     four for Business AG
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

/* Initial PHP error handling settings.
 * NOTE: They will be overwritten below...
 */
// Don't display errors
@ini_set('display_errors', false);

// Log errors to a file
@ini_set('log_errors', true);

// Report all errors except warnings
error_reporting(E_ALL ^E_NOTICE);

/*
 * Do not edit this value!
 *
 * If you want to set a different enviroment value please define it in your .htaccess file
 * or in the server configuration.
 *
 * SetEnv CONTENIDO_ENVIRONMENT development
 */
if (!defined('CONTENIDO_ENVIRONMENT')) {
    if (getenv('CONTENIDO_ENVIRONMENT')) {
        $sEnvironment = getenv('CONTENIDO_ENVIRONMENT');
    } else {
        // @TODO: provide a possibility to set the environment value via file
        $sEnvironment = 'production';
    }

    define('CONTENIDO_ENVIRONMENT', $sEnvironment);
}

// (string) Path to folder containing all contenido configuration files
//          Use environment setting!
$cfg['path']['contenido_config'] = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../..')) . '/data/config/' . CONTENIDO_ENVIRONMENT . '/';

// Security check: Include security class and invoke basic request checks
require_once(str_replace('\\', '/', realpath(dirname(__FILE__) . '/..')) . '/classes/class.registry.php');
require_once(str_replace('\\', '/', realpath(dirname(__FILE__) . '/..')) . '/classes/class.security.php');
require_once(str_replace('\\', '/', realpath(dirname(__FILE__) . '/..')) . '/classes/class.requestvalidator.php');
require_once(str_replace('\\', '/', realpath(dirname(__FILE__) . '/..')) . '/classes/class.filehandler.php');
$oRequestValidator = new cRequestValidator(realpath(dirname(__FILE__) . '/../..') . '/data/config/' . CONTENIDO_ENVIRONMENT);

// "Workaround" for register_globals=off settings.
require_once(dirname(__FILE__) . '/globals_off.inc.php');

// Check if configuration file exists, this is a basic indicator to find out, if CONTENIDO is installed
if (!cFileHandler::exists($cfg['path']['contenido_config'] . 'config.php')) {
    $msg  = "<h1>Fatal Error</h1><br>"
          . "Could not open the configuration file <b>config.php</b>.<br><br>"
          . "Please make sure that you saved the file in the setup program."
          . "If you had to place the file manually on your webserver, make sure that it is placed in your contenido/data/config/{environment}/ directory.";
    die($msg);
}

// Include some basic configuration files
require_once($cfg['path']['contenido_config'] . 'config.php');
require_once($cfg['path']['contenido_config'] . 'config.path.php');
require_once($cfg['path']['contenido_config'] . 'config.misc.php');
require_once($cfg['path']['contenido_config'] . 'config.colors.php');
require_once($cfg['path']['contenido_config'] . 'config.templates.php');
require_once($cfg['path']['contenido_config'] . 'cfg_sql.inc.php');

if (cFileHandler::exists($cfg['path']['contenido_config'] . 'config.clients.php')) {
    require_once($cfg['path']['contenido_config'] . 'config.clients.php');
}

// Include userdefined configuration (if available), where you are able to
// extend/overwrite core settings from included configuration files above
if (cFileHandler::exists($cfg['path']['contenido_config'] . 'config.local.php')) {
    require_once($cfg['path']['contenido_config'] . 'config.local.php');
}

// Takeover configured PHP settings
if ($cfg['php_settings'] && is_array($cfg['php_settings'])) {
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

$backendPath = cRegistry::getBackendPath();

// Various base API functions
require_once($backendPath . $cfg['path']['includes'] . 'api/functions.api.general.php');

// Initialization of autoloader
require_once($backendPath . $cfg['path']['classes'] . 'class.autoload.php');
cAutoload::initialize($cfg);

// Generate arrays for available login languages
// Author: Martin Horwath
$localePath = $cfg['path']['contenido_locale'];
$handle = opendir($localePath);
while ($locale = readdir($handle)) {
    if (is_dir($localePath . $locale) && $locale != '..' && $locale != '.') {
        if (cFileHandler::exists($localePath . $locale . '/LC_MESSAGES/contenido.po') &&
            cFileHandler::exists($localePath . $locale . '/LC_MESSAGES/contenido.mo')) {
            $cfg['login_languages'][] = $locale;
            $cfg['lang'][$locale] = 'lang_' . $locale . '.xml';
        }
    }
}

// Some general includes
cInclude('includes', 'functions.general.php');
cInclude('conlib', 'prepend.php');
cInclude('includes', 'functions.i18n.php');

// Initialization of CEC
$_cecRegistry = cApiCecRegistry::getInstance();
require_once($cfg['path']['contenido_config'] . 'config.chains.php');

// Set default database connection parameterecho '<pre>';
DB_Contenido::setDefaultConfiguration($cfg['db']);

// Initialize UrlBuilder, configuration is set in data/config/{environment}/config.misc.php
Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);

?>
<?php
/**
 * Main CONTENIDO setup bootstrap file.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Don't display errors
@ini_set('display_errors', false);

// Report all errors except warnings
error_reporting(E_ALL ^E_NOTICE);


header('Content-Type: text/html; charset=ISO-8859-1');

// Check version in the 'first' line, as class.security.php uses
// PHP5 object syntax not compatible with PHP < 5
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    die("You need PHP >= 5.0.0 for CONTENIDO. Sorry, even the setup doesn't work otherwise. Your version: " . PHP_VERSION . "\n");
}

// Check version
//PHP >= 5.0.0 and < 6.0.0
if (version_compare(PHP_VERSION, '6.0.0', '>=')) {
    die("You need PHP >= 5.0.0  < 6.0.0 for CONTENIDO. Sorry, even the setup doesn't work otherwise. Your version: " . PHP_VERSION . "\n");
}

/*
 * Do not edit this value!
 *
 * If you want to set a different enviroment value please define it in your .htaccess file
 * or in the server configuration.
 *
 * SetEnv CONTENIDO_ENVIRONMENT development
 */
if (!defined('CON_ENVIRONMENT')) {
    if (getenv('CONTENIDO_ENVIRONMENT')) {
        $sEnvironment = getenv('CONTENIDO_ENVIRONMENT');
    } else if (getenv('CON_ENVIRONMENT')) {
        $sEnvironment = getenv('CON_ENVIRONMENT');
    } else {
        // @TODO: provide a possibility to set the environment value via file
        $sEnvironment = 'production';
    }

    define('CON_ENVIRONMENT', $sEnvironment);
}

/**
 * Setup file inclusion
 *
 * @param  string  $filename
 */
function checkAndInclude($filename) {
    if (file_exists($filename) && is_readable($filename)) {
        include_once($filename);
    } else {
        echo "<pre>";
        echo "Setup was unable to include necessary files. The file $filename was not found. Solutions:\n\n";
        echo "- Make sure that all files are correctly uploaded to the server.\n";
        echo "- Make sure that include_path is set to '.' (of course, it can contain also other directories). Your include path is: " . ini_get("include_path") . "\n";
        echo "</pre>";
    }
}

// Include security class and check request variables
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.filehandler.php');
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.requestvalidator.php');
try {
    $requestValidator = cRequestValidator::getInstance();
    $requestValidator->checkParams();
} catch (cFileNotFoundException $e) {
    die($e->getMessage());
}

session_start();

if (is_array($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if ($key == 'c') {
            // c = setup controller to process
            continue;
        }
        if (($value != '' && $key != 'dbpass' && $key != 'adminpass' && $key != 'adminpassrepeat') || ($key == 'dbpass' && $_REQUEST['dbpass_changed'] == 'true') || ($key == 'adminpass' && $_REQUEST['adminpass_changed'] == 'true') || ($key == 'adminpassrepeat' && $_REQUEST['adminpassrepeat_changed'] == 'true')) {
            $_SESSION[$key] = $value;
        }
    }
}


// set max_execution_time
if (ini_get('max_execution_time') < 60) {
    ini_set('max_execution_time', 60);
}

// Some basic configuration
global $cfg;

$cfg['path']['frontend'] = CON_FRONTEND_PATH;
$cfg['path']['contenido'] = $cfg['path']['frontend'] . '/contenido/';
$cfg['path']['contenido_config'] = CON_FRONTEND_PATH . '/data/config/' . CON_ENVIRONMENT . '/';

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

checkAndInclude($cfg['path']['contenido_config'] . 'config.path.php');
checkAndInclude($cfg['path']['contenido_config'] . 'config.misc.php');
checkAndInclude($cfg['path']['contenido_config'] . 'cfg_sql.inc.php');

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

// Initialization of autoloader
checkAndInclude($cfg['path']['contenido'] . $cfg['path']['classes'] . 'class.autoload.php');
cAutoload::initialize($cfg);

// Set generateXHTML property of cHTML class to prevent db query, especially at
// the beginning of an new installation where we have no db
cHTML::setGenerateXHTML(false);

// Common includes
checkAndInclude(CON_SETUP_PATH . '/lib/defines.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.php54.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.i18n.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/api/functions.api.general.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');
checkAndInclude($cfg['path']['contenido'] . 'classes/class.template.php');
checkAndInclude(CON_SETUP_PATH . '/lib/class.setupcontrols.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.filesystem.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.environment.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.safe_mode.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.mysql.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.phpinfo.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.libraries.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.system.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.sql.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.setup.php');
checkAndInclude(CON_SETUP_PATH . '/lib/class.setupmask.php');

// PHP version check
if (false === isPHPCompatible()) {
    $sNotInstallableReason = 'php_version';
    checkAndInclude(CON_SETUP_PATH . '/steps/notinstallable.php');
}

// PHP ini session check
if (getPHPIniSetting('session.use_cookies') == 0) {
    $sNotInstallableReason = 'session_use_cookies';
    checkAndInclude(CON_SETUP_PATH . '/steps/notinstallable.php');
}

// PHP database extension check
$extension = getMySQLDatabaseExtension();
if (!is_null($extension)) {
    $cfg['database_extension'] = $extension;
} else {
    $sNotInstallableReason = 'database_extension';
    checkAndInclude(CON_SETUP_PATH . '/steps/notinstallable.php');
}

if (isset($_SESSION['language'])) {
    i18nInit('locale/', $_SESSION['language'], 'setup');
}

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

global $cfg;

// Report all errors except warnings
error_reporting(E_ALL ^E_NOTICE);

header('Content-Type: text/html; charset=ISO-8859-1');

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


include_once(__DIR__ . '/defines.php');

// Check minimum required PHP version in the 'first' line
if (version_compare(PHP_VERSION, CON_SETUP_MIN_PHP_VERSION, '<')) {
    die(sprintf("You need PHP >= %s for CONTENIDO. Sorry, even the setup doesn't work otherwise. Your version: %s\n", CON_SETUP_MIN_PHP_VERSION, PHP_VERSION));
}

// Include the environment definer file
checkAndInclude(CON_FRONTEND_PATH . '/contenido/environment.php');

// Include CONTENIDO defines
checkAndInclude(CON_FRONTEND_PATH . '/contenido/includes/defines.php');

// Include cStringMultiByteWrapper and cString
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.string.multi.byte.wrapper.php');
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.string.php');

// Include security class and check request variables
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.filehandler.php');
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.requestvalidator.php');

// Include some function files, we need them in a very early stage
checkAndInclude(CON_SETUP_PATH . '/lib/functions.setup.php');
checkAndInclude(CON_SETUP_PATH . '/lib/functions.system.php');

// Check configuration path for the environment
// If no configuration for environment found, copy from production
setupCheckConfiguration(str_replace('\\', '/', realpath(__DIR__ . '/../..')));

try {
    $requestValidator = cRequestValidator::getInstance();
    $requestValidator->checkParams();
} catch (cFileNotFoundException $e) {
    die($e->getMessage());
}

session_start();

// Save setup request variables in session
if (is_array($_REQUEST)) {
    takeoverRequestToSession($_REQUEST);
}

// Set max_execution_time
$maxExecutionTime = (int) ini_get('max_execution_time');
if ($maxExecutionTime < 60 && $maxExecutionTime !== 0) {
    ini_set('max_execution_time', 60);
}

// Setup some basic configuration ans then include configuration files
setupInitializeConfig();
checkAndInclude($cfg['path']['contenido_config'] . 'config.path.php');
checkAndInclude($cfg['path']['contenido_config'] . 'config.misc.php');
checkAndInclude($cfg['path']['contenido_config'] . 'cfg_sql.inc.php');

// Takeover configured PHP settings and set some PHP settings
setupUpdateConfig();

// Initialization of autoloader
checkAndInclude($cfg['path']['contenido'] . $cfg['path']['classes'] . 'class.autoload.php');
cAutoload::initialize($cfg);

// Set generateXHTML property of cHTML class to prevent db query, especially at
// the beginning of an new installation where we have no db
cHTML::setGenerateXHTML(false);

// Common includes
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
checkAndInclude(CON_SETUP_PATH . '/lib/functions.sql.php');
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

<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Main CONTENIDO setup bootstrap file.
 *
 * @package    CONTENIDO setup bootstrap
 * @version    0.0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.17
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

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
 * SetEnv CON_ENVIRONMENT development
 */
if (!defined('CON_ENVIRONMENT')) {
    if (getenv('CONTENIDO_ENVIRONMENT')) {  // CONTENIDO_ENVIRONMENT @deprecated!
        $sEnvironment = getenv('CONTENIDO_ENVIRONMENT');
    } else if(getenv('CON_ENVIRONMENT')) {
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
        echo "Setup was unable to include neccessary files. The file $filename was not found. Solutions:\n\n";
        echo "- Make sure that all files are correctly uploaded to the server.\n";
        echo "- Make sure that include_path is set to '.' (of course, it can contain also other directories). Your include path is: " . ini_get("include_path") . "\n";
        echo "</pre>";
    }
}

// Include security class and check request variables
checkAndInclude(CON_FRONTEND_PATH . '/contenido/classes/class.security.php');
Contenido_Security::checkRequests();

session_start();

if (is_array($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (($value != '' && $key != 'dbpass') || ($key == 'dbpass' && $_REQUEST['dbpass_changed'] == 'true')) {
            $_SESSION[$key] = $value;
        }
    }
    /*
      ############################################################################
      // FIXME  Following lines of code would enshure that previous selected optional
      //        settings will be removed from session, if they are unselected afterwards.
      //        But, how should we handle not selected plugins, whose files will be included
      //        even if the are not installed?

      // check for not selected options (radio button or checkbox)
      $aSetupOptionalSettingsList = array(
      'setup7' => array(
      'plugin_newsletter',
      'plugin_content_allocation',
      'plugin_mod_rewrite',
      )
      );

      if (isset($_REQUEST['step']) && isset($aSetupOptionalSettingsList[$_REQUEST['step']])) {
      $aList = $aSetupOptionalSettingsList[$_REQUEST['step']];
      foreach ($aList as $key) {
      if (isset($_SESSION[$key]) && !isset($_REQUEST[$key])) {
      unset($_SESSION[$key]);
      }
      }
      }
      ############################################################################
     */
}


// Some basic configuration
global $cfg, $contenido_host, $contenido_database, $contenido_user, $contenido_password;

$cfg['path']['frontend'] = CON_FRONTEND_PATH;
$cfg['path']['contenido'] = $cfg['path']['frontend'] . '/contenido/';
$cfg['path']['phplib'] = $cfg['path']['frontend'] . '/conlib/';
$cfg['path']['pear'] = $cfg['path']['frontend'] . '/pear/';

// DB related settings
// @todo: Replace usage of database related session values in setup against the global variables below.
//        We are setting them here and there is no need to use $_SESSION db stuff anymore...
$cfg['sql']['sqlprefix'] = (isset($_SESSION['dbprefix'])) ? $_SESSION['dbprefix'] : 'con';
$contenido_host = (isset($_SESSION['dbhost'])) ? $_SESSION['dbhost'] : '';
$contenido_database = (isset($_SESSION['dbname'])) ? $_SESSION['dbname'] : '';
$contenido_user = (isset($_SESSION['dbuser'])) ? $_SESSION['dbuser'] : '';
$contenido_password = (isset($_SESSION['dbpass'])) ? $_SESSION['dbpass'] : '';

checkAndInclude($cfg['path']['contenido'] . 'includes/config.path.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/config.misc.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/cfg_sql.inc.php');

// Initialization of autoloader
checkAndInclude($cfg['path']['contenido'] . $cfg['path']['classes'] . 'class.autoload.php');
cAutoload::initialize($cfg);

// Common includes
checkAndInclude(CON_SETUP_PATH . '/lib/defines.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.i18n.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.php54.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/api/functions.api.general.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.database.php');

// Set generateXHTML property of cHTML class to prevent db query, especially at 
// the beginning of an new installation where we have no db
// NOTE: Set this after including 'functions.api.general.php'!
cHTML::setGenerateXHTML(false);

// Continue with common includes
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

// PHP ini session check
if (getPHPIniSetting('session.use_cookies') == 0) {
    $sNotInstallableReason = 'session_use_cookies';
    checkAndInclude(CON_SETUP_PATH . '/steps/notinstallable.php');
}

// PHP database extension check
if (hasMySQLiExtension() && !hasMySQLExtension()) {
    // use MySQLi extension by default if available
    $cfg['database_extension'] = 'mysqli';
} elseif (hasMySQLExtension()) {
    // use MySQL extension if available
    $cfg['database_extension'] = 'mysql';
} else {
    $sNotInstallableReason = 'database_extension';
    checkAndInclude(CON_SETUP_PATH . '/steps/notinstallable.php');
}

checkAndInclude($cfg['path']['phplib'] . 'prepend.php');

if (isset($_SESSION['language'])) {
    i18nInit('locale/', $_SESSION['language']);
}

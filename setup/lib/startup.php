<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Main CONTENIDO setup bootstrap file.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO setup bootstrap
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-02-28
 *
 *   $Id: $
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// uncomment this lines during development if needed
#@ini_set('display_errors', true);
#error_reporting(E_ALL);


header('Content-Type: text/html; charset=ISO-8859-1');


// Check version in the 'first' line, as class.security.php uses
// PHP5 object syntax not compatible with PHP < 5
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    die("You need PHP >= 5.0.0 for CONTENIDO. Sorry, even the setup doesn't work otherwise. Your version: " . PHP_VERSION . "\n");
}


/**
 * Setup file inclusion
 *
 * @param  string  $filename
 */
function checkAndInclude($filename)
{
    if (file_exists($filename) && is_readable($filename)) {
        include_once($filename);
    } else {
        echo "<pre>";
        echo "Setup was unable to include neccessary files. The file $filename was not found. Solutions:\n\n";
        echo "- Make sure that all files are correctly uploaded to the server.\n";
        echo "- Make sure that include_path is set to '.' (of course, it can contain also other directories). Your include path is: ".ini_get("include_path")."\n";
        echo "</pre>";
    }
}


// Include security class and check request variables
checkAndInclude(C_FRONTEND_PATH . 'contenido/classes/class.security.php');
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
global $cfg;

$cfg['path']['frontend']  = C_FRONTEND_PATH;
$cfg['path']['setup']     = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';
$cfg['path']['contenido'] = $cfg['path']['frontend'] . 'contenido/';
$cfg['path']['phplib']    = $cfg['path']['frontend'] . 'conlib/';
$cfg['path']['pear']      = $cfg['path']['frontend'] . 'pear/';
$cfg['generate_xhtml']    = true;

// DB related settings
$cfg['sql']['sqlprefix']  = (isset($_SESSION['dbprefix'])) ? $_SESSION['dbprefix'] : 'con';
$cfg['db'] = array(
    'connection' => array(
        'host'     => (isset($_SESSION['dbhost'])) ? $_SESSION['dbhost'] : '',
        'database' => (isset($_SESSION['dbname'])) ? $_SESSION['dbname'] : '',
        'user'     => (isset($_SESSION['dbuser'])) ? $_SESSION['dbuser'] : '',
        'password' => (isset($_SESSION['dbpass'])) ? $_SESSION['dbpass'] : '',
    ),
    'nolock'          => false,
    'sequenceTable'   => '',
    'haltBehavior'    => 'report',
    'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
    'enableProfiling' => false,
);

checkAndInclude($cfg['path']['contenido'] . 'includes/config.path.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/config.misc.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/cfg_sql.inc.php');

$cfg['db']['sequenceTable'] = $cfg['tab']['sequence'];


// Initialization of autoloader
checkAndInclude($cfg['path']['contenido'] . $cfg['path']['classes'] . 'class.autoload.php');
Contenido_Autoload::initialize($cfg);


// Common includes
checkAndInclude($cfg['path']['setup'] . 'lib/defines.php');
checkAndInclude($cfg['path']['pear'] . 'HTML/Common.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.i18n.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');
checkAndInclude($cfg['path']['setup'] . 'lib/class.setupcontrols.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.filesystem.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.environment.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.safe_mode.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.mysql.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.phpinfo.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.libraries.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.system.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.sql.php');
checkAndInclude($cfg['path']['setup'] . 'lib/functions.setup.php');
checkAndInclude($cfg['path']['setup'] . 'lib/class.template.php');
checkAndInclude($cfg['path']['setup'] . 'lib/class.setupmask.php');

// PHP verion check
if (phpversion() < C_SETUP_MIN_PHP_VERSION) {
    $sNotInstallableReason = 'php_version';
    checkAndInclude($cfg['path']['setup'] . 'steps/notinstallable.php');
}

// PHP ini session check
if (getPHPIniSetting('session.use_cookies') == 0) {
    $sNotInstallableReason = 'session_use_cookies';
    checkAndInclude($cfg['path']['setup'] . 'steps/notinstallable.php');
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
    checkAndInclude($cfg['path']['setup'] . 'steps/notinstallable.php');
}

checkAndInclude($cfg['path']['phplib'] . 'prepend.php');

if (isset($_SESSION['language'])) {
    i18nInit('locale/', $_SESSION['language']);
}

?>
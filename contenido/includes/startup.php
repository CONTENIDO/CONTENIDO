<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Central CONTENIDO file to initialize the application. Performs following steps:
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
 * @package    CONTENIDO Backend includes
 * @version    1.1.0
 * @author     four for Business AG
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-25, Frederic Schneider, add con_framework check and include contenido_secure
 *   modified 2008-07-02, Frederic Schneider, removed contenido_secure include
 *   modified 2008-08-28, Murat Purc, changed instantiation of $_cecRegistry
 *   modified 2008-11-18, Murat Purc, add initialization of UrlBuilder configuration
 *   modified 2010-05-20, Murat Purc, taken over security checks (Contenido_Security and HttpInputValidator)
 *                        from various files and some modifications, see [#CON-307]
 *   modified 2010-12-28, Murat Purc, changed order of some includes to provide more user defined 
 *                                    settings in config.local.php
 *   modified 2011-03-03, Murat Purc, Initialize database with settings
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


// 1. security check: Include security class and invoke basic request checks
include_once(str_replace('\\', '/', realpath(dirname(__FILE__) . '/..')) . '/classes/class.security.php');
try {
    Contenido_Security::checkRequests();
} catch (Exception $e) {
    die($e->getMessage());
}


// "Workaround" for register_globals=off settings.
require_once(dirname(__FILE__) . '/globals_off.inc.php');


// Check if configuration file exists, this is a basic indicator to find out, if CONTENIDO is installed
if (!file_exists(dirname(__FILE__) . '/config.php')) {
    $msg  = "<h1>Fatal Error</h1><br>";
    $msg .= "Could not open the configuration file <b>config.php</b>.<br><br>";
    $msg .= "Please make sure that you saved the file in the setup program. If you had to place the file manually on your webserver, make sure that it is placed in your contenido/includes directory.";
    die($msg);
}


// Include some basic configuration files
include_once(dirname(__FILE__) . '/config.php');
include_once(dirname(__FILE__) . '/config.path.php');
include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.misc.php');
include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.colors.php');
include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.path.php');
include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.templates.php');
include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/cfg_sql.inc.php');

// Include userdefined configuration (if available), where you are able to
// extend/overwrite core settings from included configuration files above
if (file_exists($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.local.php')) {
    include_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.local.php');
}

// Various base API functions
require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . '/api/functions.api.general.php');


// Initialization of autoloader
include_once($cfg['path']['contenido'] . $cfg['path']['classes'] . 'class.autoload.php');
Contenido_Autoload::initialize($cfg);


// 2. security check: Check HTTP parameters, if requested
if ($cfg['http_params_check']['enabled'] === true) {
    $oHttpInputValidator =
        new HttpInputValidator($cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.http_check.php');
}


/* Generate arrays for available login languages
 * ---------------------------------------------
 * Author: Martin Horwath
 */

global $cfg;

$handle = opendir($cfg['path']['contenido'] . $cfg['path']['locale']);

while ($locale = readdir($handle)) {
   if (is_dir($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale) && $locale != '..' && $locale != '.') {
      if (file_exists($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale . '/LC_MESSAGES/contenido.po') &&
         file_exists($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale . '/LC_MESSAGES/contenido.mo') &&
         file_exists($cfg['path']['contenido'] . $cfg['path']['xml'] . 'lang_'.$locale.'.xml') ) {

         $cfg['login_languages'][] = $locale;
         $cfg['lang'][$locale] = 'lang_'.$locale.'.xml';
      }
   }
}


// Some general includes
cInclude('includes', 'functions.general.php');
cInclude('conlib', 'prepend.php');
cInclude('includes', 'functions.i18n.php');


// Initialization of CEC
$_cecRegistry = cApiCECRegistry::getInstance();
cInclude('includes', 'config.chains.php');

// Set default database connection parameter
$cfg['db']['sequenceTable'] = $cfg['tab']['sequence'];
DB_Contenido::setDefaultConfiguration($cfg['db']);

// @TODO: This should be done by instantiating a DB_Contenido class, creation of DB_Contenido object
checkMySQLConnectivity();


// Initialize UrlBuilder, configuration is set in /contenido/includes/config.misc.php
Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);


?>
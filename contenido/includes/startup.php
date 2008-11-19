<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Global contenido file with includes
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.3
 * @author     four for Business AG
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-25, Frederic Schneider, add con_framework check and include contenido_secure
 *   modified 2008-07-02, Frederic Schneider, removed contenido_secure include
 *   modified 2008-08-28, Murat Purc, changed instantiation of $_cecRegistry
 *   modified 2008-11-18, Murat Purc, add initialization of UrlBuilder configuration
 *
 *   $Id$:
 * }}
 * 
 */

if(CON_FRAMEWORK != true) {
    die('Illegal call');
}

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}

/* "Workaround" for register_globals=off settings. */
require_once ( dirname(__FILE__) . '/globals_off.inc.php');

if (!file_exists( dirname(__FILE__) . '/config.php'))
{
	$msg = "<h1>Fatal Error</h1><br>";
	$msg .= "Could not open the configuration file <b>config.php</b>.<br><br>";
	$msg .= "Please make sure that you saved the file in the setup program. If you had to place the file manually on your webserver, make sure that it is placed in your contenido/includes directory.";
	
	die ($msg);
}

/* Include the config first */
include_once ( dirname(__FILE__) . '/config.php');
include_once ( dirname(__FILE__) . '/config.path.php');

/* Various base API functions */
require_once ( dirname(__FILE__) . '/api/functions.api.general.php');

/* Include configurations */
include_once( dirname(__FILE__) . "/config.misc.php");
include_once( dirname(__FILE__) . "/config.colors.php");
include_once( dirname(__FILE__) . "/config.path.php");
include_once( dirname(__FILE__) . "/config.templates.php");

/* Generate arrays for available login languages 
 * --------------------------------------------- 
 * Author: Martin Horwath 
 */ 

global $cfg; 

$handle = opendir($cfg['path']['contenido'] . $cfg['path']['locale'] ); 

while ($locale = readdir($handle)) 
{ 
   if (is_dir($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale ) && $locale != ".." && $locale != "." ) 
   { 
      if (file_exists($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale . DIRECTORY_SEPARATOR . "LC_MESSAGES" . DIRECTORY_SEPARATOR . "contenido.po") && 
         file_exists($cfg['path']['contenido'] . $cfg['path']['locale'] . $locale . DIRECTORY_SEPARATOR . "LC_MESSAGES" . DIRECTORY_SEPARATOR . "contenido.mo") && 
         file_exists($cfg['path']['contenido'] . $cfg['path']['xml'] . "lang_".$locale.".xml") ) { 

         $cfg["login_languages"][] = $locale; 
         $cfg["lang"][$locale] = "lang_".$locale.".xml"; 
      }
   }
}

cInclude("includes", "cfg_sql.inc.php");
cInclude("includes", "functions.general.php");
cInclude("conlib", "prepend.php");
cInclude("includes", "functions.i18n.php");

cInclude("classes", "class.cec.php");
cInclude("classes", "class.cec_hook.php");
$_cecRegistry = cApiCECRegistry::getInstance();

cInclude("includes", "config.chains.php");

if (file_exists(dirname(__FILE__) . "/config.local.php"))
{
	include_once( dirname(__FILE__) . "/config.local.php");
}

checkMySQLConnectivity();

// Initialize UrlBuilder, configuration is set in /contenido/includes/config.misc.php
cInclude('classes', 'Url/Contenido_Url.class.php');
cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderConfig.class.php');
Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);


?>
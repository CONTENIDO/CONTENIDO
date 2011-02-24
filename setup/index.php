<?php
 /**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-01-21, Ortwin Pinke, added php-errorhandling function calls, uncomment if needed
 *   modified 2011-02-24, Murat Purc, extended mysql extension detection and some other changes
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// uncomment this lines during development if needed
# @ini_set("display_errors",true);
# error_reporting (E_ALL);

header("Content-Type: text/html; charset=ISO-8859-1");

// Check version in the "first" line, as class.security.php uses
// PHP5 object syntax not compatible with PHP < 5
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    die("You need PHP >= 5.0.0 for Contenido. Sorry, even the setup doesn't work otherwise. Your version: " . PHP_VERSION . "\n");
}

// include security class and check request variables
include_once ('../contenido/classes/class.security.php');
Contenido_Security::checkRequests();

function checkAndInclude ($filename)
{
	if (file_exists($filename) && is_readable($filename))
	{
		include_once($filename);
	} else {
		echo "<pre>";
		echo "Setup was unable to include neccessary files. The file $filename was not found. Solutions:\n\n";
		echo "- Make sure that all files are correctly uploaded to the server.\n";
		echo "- Make sure that include_path is set to '.' (of course, it can contain also other directories). Your include path is: ".ini_get("include_path")."\n"; 
		echo "</pre>";
			
	}
}

session_start();
if (is_array($_REQUEST))
{
	foreach ($_REQUEST as $key => $value)
	{
		if (($value != "" && $key != "dbpass") || ($key == "dbpass" && $_REQUEST["dbpass_changed"] == "true"))
		{
			$_SESSION[$key] = $value;
		}
	}
}

/* Includes */
checkAndInclude("lib/defines.php");
checkAndInclude("../pear/HTML/Common.php");
checkAndInclude("../contenido/classes/class.htmlelements.php");
checkAndInclude("../contenido/includes/functions.i18n.php");
checkAndInclude("lib/class.setupcontrols.php");
checkAndInclude("lib/functions.filesystem.php");
checkAndInclude("lib/functions.environment.php");
checkAndInclude("lib/functions.safe_mode.php");
checkAndInclude("lib/functions.mysql.php");
checkAndInclude("lib/functions.phpinfo.php");
checkAndInclude("lib/functions.system.php");
checkAndInclude("lib/functions.libraries.php");
checkAndInclude("lib/functions.sql.php");
checkAndInclude("lib/functions.setup.php");
checkAndInclude("lib/class.template.php");
checkAndInclude("lib/class.setupmask.php");

if (getPHPIniSetting("session.use_cookies") == 0)
{
    $sNotInstallableReason = 'session_use_cookies';
	checkAndInclude("steps/notinstallable.php");
}

if (hasMySQLiExtension() && !hasMySQLExtension())
{
	// use MySQLi extension by default if available
	$cfg["database_extension"] = "mysqli";	
}
elseif (hasMySQLExtension())
{
	// use MySQL extension if available
	$cfg["database_extension"] = "mysql";	
}
else
{
    $sNotInstallableReason = 'database_extension';
	checkAndInclude("steps/notinstallable.php");
}

checkAndInclude("../conlib/prepend.php");

if (array_key_exists("language", $_SESSION))
{
	i18nInit("locale/", $_SESSION["language"]);
}

if (phpversion() < C_SETUP_MIN_PHP_VERSION)
{
    $sNotInstallableReason = 'php_version';
	checkAndInclude("steps/notinstallable.php");
}

if (array_key_exists("step", $_REQUEST))
{
	$iStep = $_REQUEST["step"];
} else {
	$iStep = "";	
}

switch ($iStep)
{

	case "setuptype":
		checkAndInclude("steps/setuptype.php");
		break;
	case "setup1":
		checkAndInclude("steps/setup/step1.php");
		break;
	case "setup2":
		checkAndInclude("steps/setup/step2.php");
		break;
	case "setup3":
		checkAndInclude("steps/setup/step3.php");
		break;
	case "setup4":
		checkAndInclude("steps/setup/step4.php");
		break;
	case "setup5":
		checkAndInclude("steps/setup/step5.php");
		break;		
	case "setup6":
		checkAndInclude("steps/setup/step6.php");
		break;
	case "setup7":
		checkAndInclude("steps/setup/step7.php");
		break;	
	case "setup8":
		checkAndInclude("steps/setup/step8.php");
		break;
	case "migration1":
		checkAndInclude("steps/migration/step1.php");
		break;	
	case "migration2":
		checkAndInclude("steps/migration/step2.php");
		break;		
	case "migration3":
		checkAndInclude("steps/migration/step3.php");
		break;		
	case "migration4":
		checkAndInclude("steps/migration/step4.php");
		break;		
	case "migration5":
		checkAndInclude("steps/migration/step5.php");
		break;
	case "migration6":
		checkAndInclude("steps/migration/step6.php");
		break;
	case "migration7":
		checkAndInclude("steps/migration/step7.php");
		break;		
	case "migration8":
		checkAndInclude("steps/migration/step8.php");
		break;
	case "upgrade1":
		checkAndInclude("steps/upgrade/step1.php");
		break;	
	case "upgrade2":
		checkAndInclude("steps/upgrade/step2.php");
		break;		
	case "upgrade3":
		checkAndInclude("steps/upgrade/step3.php");
		break;		
	case "upgrade4":
		checkAndInclude("steps/upgrade/step4.php");
		break;		
	case "upgrade5":
		checkAndInclude("steps/upgrade/step5.php");
		break;	
	case "upgrade6":
		checkAndInclude("steps/upgrade/step6.php");
		break;			
	case "upgrade7":
		checkAndInclude("steps/upgrade/step7.php");
		break;
	case "domigration":
		checkAndInclude("steps/migration/domigration.php");
		break;			
	case "doupgrade":
		checkAndInclude("steps/upgrade/doupgrade.php");
		break;	
	case "doinstall":
		checkAndInclude("steps/setup/doinstall.php");
		break;
	case "languagechooser":			
	default:
		checkAndInclude("steps/languagechooser.php");
		break;
}

?>

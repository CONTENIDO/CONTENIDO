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
 * @version    0.2
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
 *   $Id: index.php 652 2008-07-31 22:13:57Z HerrB $:
 * }}
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}
header("Content-Type: text/html; charset=ISO-8859-1");

define('CON_SETUP_PATH', str_replace('\\', '/', realpath(dirname(__FILE__))));

define('CON_FRONTEND_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')));

include_once('lib/startup.php');

if (array_key_exists("step", $_REQUEST)) {
    $iStep = $_REQUEST["step"];
} else {
    $iStep = "";
}

switch ($iStep) {

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
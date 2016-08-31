<?php
/**
 * CONTENIDO setup step 6 - admin password.
 *
 * @package    Setup
 * @subpackage Step_Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

checkAndInclude("steps/forms/adminpassword.php");

$cSetupSetupSummary = new cSetupAdminPassword(6, "setup5", "setup7");
$cSetupSetupSummary->render();
?>
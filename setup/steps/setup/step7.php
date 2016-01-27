<?php
/**
 * CONTENIDO setup step 7 - setup summary.
 *
 * @package    Setup
 * @subpackage Step_Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

 echo '<!-- Hello begin -->';

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(7, "setup6", "doinstall");
$cSetupSetupSummary->render();
?>
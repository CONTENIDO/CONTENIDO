<?php
/**
 * CONTENIDO upgrade step doupgrade.
 *
 * @package    Setup
 * @subpackage Step_Upgrade
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

checkAndInclude("steps/forms/installer.php");

$cSetupInstaller = new cSetupInstaller(6);
$cSetupInstaller->render();
?>
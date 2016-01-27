<?php
/**
 * CONTENIDO upgrade step 3 - config mode.
 *
 * @package    Setup
 * @subpackage Step_Upgrade
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

checkAndInclude("steps/forms/configmode.php");

$cSetupConfigMode = new cSetupConfigMode(3, "upgrade2", "upgrade4");
$cSetupConfigMode->render();
?>
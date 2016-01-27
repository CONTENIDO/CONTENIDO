<?php
/**
 * CONTENIDO setup step 5 - client mode.
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

checkAndInclude("steps/forms/clientmode.php");

$cSetupClientMode = new cSetupClientMode(5, "setup4", "setup6", true);
$cSetupClientMode->render();
?>
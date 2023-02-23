<?php
/**
 * CONTENIDO upgrade step 1 - system data.
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

checkAndInclude("steps/forms/systemdata.php");

$cSetupSystemData = new cSetupSystemData(1, "setuptype", "upgrade2");
$cSetupSystemData->render();
?>
<?php

/**
 * CONTENIDO setup step 2 - path information.
 *
 * @package    Setup
 * @subpackage Step_Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

checkAndInclude("steps/forms/pathinfo.php");

$cSetupConfigMode = new cSetupPath(2, "setup1", "setup3");
$cSetupConfigMode->render();

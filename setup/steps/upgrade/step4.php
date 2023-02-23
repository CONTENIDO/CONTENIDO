<?php

/**
 * CONTENIDO upgrade step 4 - system test.
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

checkAndInclude("steps/forms/systemtest.php");

$cSetupSystemtest = new cSetupSystemtest(4, "upgrade3", "upgrade5", true);
$cSetupSystemtest->render();

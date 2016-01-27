<?php
/**
 * CONTENIDO setup step 4 - system test.
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

checkAndInclude("steps/forms/systemtest.php");

$cSetupSystemtest = new cSetupSystemtest(4, "setup3", "setup5", true);
$cSetupSystemtest->render();
?>
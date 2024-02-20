<?php

/**
 * CONTENIDO setup step doinstall.
 *
 * @package    Setup
 * @subpackage Step_Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

checkAndInclude('steps/forms/installer.php');

$cSetupInstaller = new cSetupInstaller(7);
$cSetupInstaller->render();
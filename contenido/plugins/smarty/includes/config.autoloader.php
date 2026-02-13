<?php
/**
 * autoloader class file for smarty plugin
 *
 * @package    Plugin
 * @subpackage SmartyWrapper
 * @author     Ortwin Pinke
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

$pluginName = basename(dirname(__DIR__));
$autoloadClassPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes/";

return [
    'cSmartyWrapper' => $autoloadClassPath . 'class.smarty.wrapper.php',
    'cSmartyFrontend' => $autoloadClassPath . 'class.smarty.frontend.php',
    'cSmartyBackend' => $autoloadClassPath . 'class.smarty.backend.php',
];

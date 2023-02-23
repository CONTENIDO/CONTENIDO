<?php
/**
 * This file contains configuration for plugin.
 *
 * @package    Plugin
 * @subpackage SIWECOS
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

define('SIWECOS_VERSION', '1.0.0');
define('SIWECOS_API_URL', 'https://bla.siwecos.de/api/v1');

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

// define plugin path
$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// define table names
$cfg['tab']['siwecos'] = $cfg['sql']['sqlprefix'] . '_pi_siwecos';

// define templates
$pluginTemplatesPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/templates";
$cfg['templates']['siwecos_right_bottom_form'] = $pluginTemplatesPath . '/template.right_bottom.tpl';
$cfg['templates']['siwecos_report_form']       = $pluginTemplatesPath . '/template.siwecos_report.tpl';
$cfg['templates']['siwecos_verification_form'] = $pluginTemplatesPath . '/template.siwecos_verification.tpl';

// setup autoloader
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'SIWECOSLeftBottomPage'  => $pluginClassesPath . '/class.siwecos.gui.php',
    'SIWECOSCollection'      => $pluginClassesPath . '/class.siwecos.form.php',
    'SIWECOS'                => $pluginClassesPath . '/class.siwecos.form.php',
    'SIWECOSException'       => $pluginClassesPath . '/class.siwecos.form.php',
    'SIWECOSRightBottomPage' => $pluginClassesPath . '/class.siwecos.gui.php',
    'CurlService'            => $pluginClassesPath . '/CurlService.php',
]);

unset($pluginName, $pluginTemplatesPath, $pluginClassesPath);

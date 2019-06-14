<?php
/**
 * This file contains configuration for plugin.
 *
 * @package Plugin
 * @subpackage SIWECOS
 * @author Fulai Zhang
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


// define plugin path
$cfg['plugins']['siwecos'] = 'siwecos/';

// define table names
$cfg['tab']['siwecos'] = $cfg['sql']['sqlprefix'] . '_pi_siwecos';

// include necessary sources, setup autoloader for plugin
$pluginClassPath = 'contenido/plugins/' . $cfg['plugins']['siwecos'];

cAutoload::addClassmapConfig(array(
    'SIWECOSLeftBottomPage' => $pluginClassPath . 'classes/class.siwecos.gui.php',
    'SIWECOSCollection' => $pluginClassPath . 'classes/class.siwecos.form.php',
    'SIWECOS' => $pluginClassPath . 'classes/class.siwecos.form.php',
    'SIWECOSException' => $pluginClassPath . 'classes/class.siwecos.form.php',
    'SIWECOSRightBottomPage' => $pluginClassPath . 'classes/class.siwecos.gui.php',
    'CurlService' => $pluginClassPath . 'classes/CurlService.php',
));

define( 'SIWECOS_VERSION', '1.0.0' );
define( 'SIWECOS_API_URL', 'https://bla.siwecos.de/api/v1' );

$cfg['templates']['siwecos_right_bottom_form'] = cRegistry::getBackendPath() .'plugins/'. $cfg['plugins'][SIWECOS::getName()] . 'templates/template.right_bottom.tpl';
$cfg['templates']['siwecos_report_form'] = cRegistry::getBackendPath() .'plugins/'. $cfg['plugins'][SIWECOS::getName()] . 'templates/template.siwecos_report.tpl';
$cfg['templates']['siwecos_verification_form'] = cRegistry::getBackendPath() .'plugins/'. $cfg['plugins'][SIWECOS::getName()] . 'templates/template.siwecos_verification.tpl';

?>
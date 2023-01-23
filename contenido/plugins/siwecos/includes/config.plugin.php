<?php
/**
 * This file contains configuration for plugin.
 *
 * @package    Plugin
 * @subpackage SIWECOS
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

define('SIWECOS_VERSION', '1.0.0');
define('SIWECOS_API_URL', 'https://bla.siwecos.de/api/v1');

global $cfg;

// define plugin path
$cfg['plugins']['siwecos'] = 'siwecos/';

// define table names
$cfg['tab']['siwecos'] = $cfg['sql']['sqlprefix'] . '_pi_siwecos';

// setup autoloader
$classesPath = 'contenido/plugins/siwecos/';
cAutoload::addClassmapConfig(
    [
        'SIWECOSLeftBottomPage'  => $classesPath . 'classes/class.siwecos.gui.php',
        'SIWECOSCollection'      => $classesPath . 'classes/class.siwecos.form.php',
        'SIWECOS'                => $classesPath . 'classes/class.siwecos.form.php',
        'SIWECOSException'       => $classesPath . 'classes/class.siwecos.form.php',
        'SIWECOSRightBottomPage' => $classesPath . 'classes/class.siwecos.gui.php',
        'CurlService'            => $classesPath . 'classes/CurlService.php',
    ]
);

// define templates
$templatePath = cRegistry::getBackendPath() . 'plugins/siwecos/';
$cfg['templates']['siwecos_right_bottom_form'] = $templatePath . 'templates/template.right_bottom.tpl';
$cfg['templates']['siwecos_report_form']       = $templatePath . 'templates/template.siwecos_report.tpl';
$cfg['templates']['siwecos_verification_form'] = $templatePath . 'templates/template.siwecos_verification.tpl';

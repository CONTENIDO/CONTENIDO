<?php

/**
 * Configuration file for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $lngAct;

$pluginName = basename(dirname(__DIR__, 1));

// Plugin configuration
$cfg['pi_cronjob_overview'] = [
    'pluginName' => $pluginName,
];

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// Plugin translation for usage in backend areas (menus, right, etc.)
$lngAct['cronjob']['cronjob_overview'] = i18n('Cronjob overview', $pluginName);
$lngAct['cronjob']['crontab_edit'] = i18n('Edit cronjob', $pluginName);
$lngAct['cronjob']['cronjob_execute'] = i18n('Execute cronjob', $pluginName);

// Add classes to autoloader
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'Cronjobs' => "$pluginClassesPath/class.cronjobs.php",
]);

unset($pluginName, $pluginClassesPath);
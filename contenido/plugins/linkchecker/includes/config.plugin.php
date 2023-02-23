<?php

/**
 * This is the configuration file for the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

// Plugin configuration
$cfg['pi_linkchecker'] = [
    'pluginName' => $pluginName,
    'whitelistTimeout' => 2592000, // 30 days
    'cacheLifeTime' => 1209600, // two weeks
];

// Paths & tables
$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";
$cfg['tab']['whitelist']     = $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist';

// Add templates to templates configuration
$pluginTemplatesPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/templates/standard";
$cfg['templates']['linkchecker_test']            = "$pluginTemplatesPath/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors']     = "$pluginTemplatesPath/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = "$pluginTemplatesPath/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing']    = "$pluginTemplatesPath/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors']        = "$pluginTemplatesPath/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist']       = "$pluginTemplatesPath/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls']  = "$pluginTemplatesPath/template.linkchecker_whitelist_urls.html";

// Add classes to autoloader
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'cLinkcheckerCategoryHelper' => "$pluginClassesPath/class.linkchecker.category_helper.php",
    'cLinkcheckerHelper'         => "$pluginClassesPath/class.linkchecker.helper.php",
    'cLinkcheckerRepair'         => "$pluginClassesPath/class.linkchecker.repair.php",
    'cLinkcheckerSearchLinks'    => "$pluginClassesPath/class.linkchecker.search_links.php",
    'cLinkcheckerTester'         => "$pluginClassesPath/class.linkchecker.tester.php",
]);

unset($pluginName, $pluginClassesPath, $pluginTemplatesPath);

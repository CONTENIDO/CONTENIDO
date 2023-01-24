<?php

/**
 * This is the configuration file for the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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

// paths
$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";
$cfg['tab']['whitelist']     = $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist';

// Add classes to autoloader
$pluginClassesPath = "contenido/plugins/$pluginName/classes";
cAutoload::addClassmapConfig([
    'cLinkcheckerCategoryHelper' => "$pluginClassesPath/class.linkchecker.category_helper.php",
    'cLinkcheckerRepair'         => "$pluginClassesPath/class.linkchecker.repair.php",
    'cLinkcheckerSearchLinks'    => "$pluginClassesPath/class.linkchecker.search_links.php",
    'cLinkcheckerTester'         => "$pluginClassesPath/class.linkchecker.tester.php",
]);

// Add templates to templates configuration
$templatePath = $cfg['plugins'][$pluginName] . 'templates/standard';
$cfg['templates']['linkchecker_test']            = "$templatePath/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors']     = "$templatePath/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = "$templatePath/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing']    = "$templatePath/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors']        = "$templatePath/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist']       = "$templatePath/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls']  = "$templatePath/template.linkchecker_whitelist_urls.html";

unset($pluginName, $pluginClassesPath, $templatePath);

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

// configuration
$iWhitelistTimeout = 2592000; // 30 days
$iCacheLifeTime    = 1209600; // two weeks

// paths
$plugin_name                   = "linkchecker";
$cfg['plugins']['linkchecker'] = cRegistry::getBackendPath() . "plugins/" . $plugin_name . "/";
$cfg['tab']['whitelist']       = $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist';
$classPath                     = 'contenido/plugins/linkchecker/classes';
$templatePath                  = $cfg['plugins']['linkchecker'] . 'templates/standard';

// add classes to autoloader
cAutoload::addClassmapConfig(
    [
        'cLinkcheckerCategoryHelper' => "$classPath/class.linkchecker.category_helper.php",
        'cLinkcheckerRepair'         => "$classPath/class.linkchecker.repair.php",
        'cLinkcheckerSearchLinks'    => "$classPath/class.linkchecker.search_links.php",
        'cLinkcheckerTester'         => "$classPath/class.linkchecker.tester.php",
    ]
);
unset($classPath);

// add templates to config
$cfg['templates']['linkchecker_test']            = "$templatePath/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors']     = "$templatePath/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = "$templatePath/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing']    = "$templatePath/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors']        = "$templatePath/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist']       = "$templatePath/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls']  = "$templatePath/template.linkchecker_whitelist_urls.html";
unset($templatePath);

?>
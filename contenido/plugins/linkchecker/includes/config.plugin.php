<?php
/**
 * This is the configuration file for the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// configuration
$iWhitelistTimeout = 2592000; // 30 days
$iCacheLifeTime = 1209600; // two weeks

// paths
$plugin_name = "linkchecker";
$cfg['plugins']['linkchecker'] = cRegistry::getBackendPath() . "plugins/" . $plugin_name . "/";
$cfg['tab']['whitelist'] = $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist';

// plugin include
plugin_include('linkchecker', 'classes/class.linkchecker.repair.php');

// Templates
$cfg['templates']['linkchecker_test'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist_urls.html";
?>
<?php
/**
 * Project: CONTENIDO Content Management System
 * Description: Config file for the plugin linkchecker
 * Requirements: @con_php_req 5.0
 *
 *
 * @package CONTENIDO Plugins
 * @subpackage Linkchecker
 * @version 2.0.1
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.8.7 {@internal created
 *        2007-08-08 modified 2007-12-13, 2008-05-15 $Id: config.plugin.php 3076
 *        2012-08-28 12:43:55Z konstantinos.katikak $: }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$plugin_name = "linkchecker";
$cfg['plugins']['linkchecker'] = cRegistry::getBackendPath() . "plugins/" . $plugin_name . "/";
$cfg['tab']['whitelist'] = $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist';

// Templates
$cfg['templates']['linkchecker_test'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist_urls.html";
?>
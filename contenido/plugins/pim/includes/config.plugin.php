<?php
/**
 * This file contains Plugin Manager configurations.
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// plugin includes
plugin_include($pluginName, 'classes/class.pim.plugin.collection.php');
plugin_include($pluginName, 'classes/class.pim.plugin.relations.collection.php');
plugin_include($pluginName, 'classes/util/zip/class.pimpluginarchiveextractor.php');
plugin_include($pluginName, 'classes/setup/class.pimpluginsetup.php');
plugin_include($pluginName, 'classes/setup/class.pimpluginsetup.install.php');
plugin_include($pluginName, 'classes/setup/class.pimpluginsetup.uninstall.php');
plugin_include($pluginName, 'classes/setup/class.pimpluginsetup.update.php');
plugin_include($pluginName, 'classes/setup/class.pimpluginsetup.status.php');
plugin_include($pluginName, 'classes/view/class.pimpluginview.dependencies.php');
plugin_include($pluginName, 'classes/view/class.pimpluginview.navsub.php');
<?php
/**
 * This file contains Plugin Manager configurations.
 *
 * @package Plugin
 * @subpackage PluginManager
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// plugin includes
plugin_include('pim', 'classes/class.pim.plugin.collection.php');
plugin_include('pim', 'classes/class.pim.plugin.relations.collection.php');
plugin_include('pim', 'classes/util/zip/class.pimpluginarchiveextractor.php');
plugin_include('pim', 'classes/setup/class.pimpluginsetup.php');
plugin_include('pim', 'classes/setup/class.pimpluginsetup.install.php');
plugin_include('pim', 'classes/setup/class.pimpluginsetup.uninstall.php');
plugin_include('pim', 'classes/setup/class.pimpluginsetup.update.php');
plugin_include('pim', 'classes/setup/class.pimpluginsetup.status.php');
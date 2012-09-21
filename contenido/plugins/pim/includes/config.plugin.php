<?php
/**
 * Plugin Manager configurations
 *
 * @package plugin
 * @subpackage Plugin Manager
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */


if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// plugin includes
plugin_include('pim', 'classes/class.pim.plugin.collection.php');
plugin_include('pim', 'classes/class.pim.plugin.relations.collection.php');
plugin_include('pim', 'classes/Setup/class.pimpluginsetup.php');
plugin_include('pim', 'classes/Util/Zip/class.pimpluginarchiveextractor.php');

// old
plugin_include('pim', 'classes/Contenido_Plugin_Base.class.php');
plugin_include('pim', 'classes/View/Contenido_PluginView.class.php');

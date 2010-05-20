<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Include Plugins Functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-06-16, Holger Librenz, Hotfix: checking for dirty calls!
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2009-04-04, Oliver Lohkemper, add scan-time and SystemProperty
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$pluginorder = getSystemProperty("system", "plugin-order");
$lastscantime = getSystemProperty("system", "plugin-lastscantime");

$ipc_conpluginpath = $cfg["path"]["contenido"].$cfg["path"]["plugins"];
$plugins = array ();

/* Fetch and trim the plugin order */
if ($pluginorder != "")
{
	$plugins = explode(",", $pluginorder);

	foreach ($plugins as $key => $plugin)
	{
		$plugins[$key] = trim($plugin);
	}
}

/* Don't scan all the time, but each 60 seconds */
if ($lastscantime +60 < time())
{
	setSystemProperty("system", "plugin-lastscantime", time());

	$dh = opendir($ipc_conpluginpath);

	while (($file = readdir($dh)) !== false)
	{

		if (is_dir($ipc_conpluginpath.$file."/") && $file != "includes" && $file != "." && $file != ".." && !in_array($file, $plugins) )
		{
			$plugins[] = $file;
		}
	}

	foreach ($plugins as $key => $value)
	{
		if (!is_dir($ipc_conpluginpath.$value."/"))
		{
			unset ($plugins[$key]);
		}
	}
	closedir($dh);
}

foreach ($plugins as $key => $ipc_plugin)
{
	if (!is_dir($ipc_conpluginpath.$ipc_plugin."/"))
	{
		unset ($plugins[$key]);
	}
	else 
	{
		$ipc_localedir  = $ipc_conpluginpath.$ipc_plugin. "/locale/";
		$ipc_langfile   = $ipc_conpluginpath.$ipc_plugin. "/includes/language.plugin.php";
		$ipc_configfile = $ipc_conpluginpath.$ipc_plugin. "/includes/config.plugin.php";

		if (file_exists($ipc_localedir))
		{
			i18nRegisterDomain($ipc_plugin, $ipc_localedir);
		}
		if (file_exists($ipc_langfile))
		{
			include_once($ipc_langfile);
		}
		if (file_exists($ipc_configfile))
		{
			include_once($ipc_configfile);
		}
	}
}

$pluginorder = implode(",", $plugins);
setSystemProperty("system", "plugin-order", $pluginorder);

unset( $plugins );

?>
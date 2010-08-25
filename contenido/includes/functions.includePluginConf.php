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
 * @version    1.1.3
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
 *   modified 2010-06-22, Oliver Lohkemper, scan and save only in BE for FE performance & security [#CON-322]
 *   modified 2010-08-25, Munkh-Ulzii Balidar, defined the plugin path independent of BE und FE 
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$pluginorder = getSystemProperty("system", "plugin-order");

$plugins = explode(",", $pluginorder);

$ipc_conpluginpath = $cfg["path"]["contenido"].$cfg["path"]["plugins"];

/*
 * Scan and save only by the BE 
 */
if ($contenido)
{
	$lastscantime = getSystemProperty("system", "plugin-lastscantime");

	/* Clean up: Fetch and trim the plugin order */
	$plugins = array ();
	
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
	
		/* scan for new Plugins */
		$dh = opendir($ipc_conpluginpath);
	
		while (($file = readdir($dh)) !== false)
		{
			if ( is_dir($ipc_conpluginpath.$file."/") && $file != "includes" && $file != "." && $file != ".." && !in_array($file, $plugins) )
			{
				$plugins[] = $file;
			}
		}
		
		setSystemProperty("system", "plugin-lastscantime", time());
		
		closedir($dh);
	
		
		/* Remove plugins do not exist */
		foreach ($plugins as $key => $ipc_plugin)
		{
			if (!is_dir($ipc_conpluginpath.$ipc_plugin."/"))
			{
				unset ($plugins[$key]);
			}
		}
		
		/* Save Scanresult */
		$pluginorder = implode(",", $plugins);
		setSystemProperty("system", "plugin-order", $pluginorder);
		
	}
}



/*
 * Load Plugin-Config and Plugin-Translation
 */
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

unset( $plugins );

?>
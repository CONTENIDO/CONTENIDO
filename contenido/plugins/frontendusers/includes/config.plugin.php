<?php
/*****************************************
* File      :   $RCSfile: config.plugin.php,v $
* Project   :   Contenido
* Descr     :   Plugin configurations for frontend users
* Modified  :   $Date: 2005/05/24 13:19:32 $
*
* © four for business AG, www.4fb.de
*
* $Id: config.plugin.php,v 1.2 2005/05/24 13:19:32 timo.hummel Exp $
******************************************/
cInclude("includes", "functions.general.php");

$pluginorder = getSystemProperty("plugin", "frontendusers-pluginorder");
$lastscantime = getSystemProperty("plugin", "frontendusers-lastscantime");

$plugins = array();

if ($pluginorder != "")
{
	$plugins = explode(",",$pluginorder);

    foreach ($plugins as $key => $plugin)
    {
    	$plugins[$key] = trim($plugin);
    }
}

$basedir = $cfg["path"]["contenido"].$cfg["path"]["plugins"]. "frontendusers/";
	
if ($lastscantime + 60 < time())
{
	setSystemProperty("plugin", "frontendusers-lastscantime", time());	
	
	$dh = opendir($basedir);
	
	while (($file_loop = readdir($dh)) !== false)
	{
		
		if (is_dir($basedir.$file_loop) && $file_loop != "includes" && $file_loop != "." && $file_loop != "..")
		{
			if (!in_array($file_loop, $plugins))
			{
				if (file_exists($basedir.$file_loop."/".$file_loop.".php"))
				{
					$plugins[] = $file_loop;
				}	
			}
		}
	}

	foreach ($plugins as $key => $value)
	{
    	if (!is_dir($basedir.$value) || !file_exists($basedir.$value."/".$value.".php"))
    	{
			unset($plugins[$key]);
    	}
	}
	$pluginorder = implode(",", $plugins);
	setSystemProperty("plugin", "frontendusers-pluginorder", $pluginorder);
}

foreach ($plugins as $key => $value)
{
	if (!is_dir($basedir.$value) || !file_exists($basedir.$value."/".$value.".php"))
	{
		unset($plugins[$key]);
	} else {
		i18nRegisterDomain("frontendusers_$value", $basedir.$value."/locale/");
	}
}

$cfg['plugins']['frontendusers'] = $plugins;
?>
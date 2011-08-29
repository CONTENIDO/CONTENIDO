<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    CONTENIDO Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * 
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


function getSafeModeStatus ()
{
	if (getPHPIniSetting("safe_mode") == "1")
	{
		return true;	
	} else {
		return false;	
	}
}

function getSafeModeGidStatus ()
{
	if (getPHPIniSetting("safe_mode_gid") == "1")
	{
		return true;	
	} else {
		return false;	
	}
}

function getSafeModeIncludeDir ()
{
	return getPHPIniSetting("safe_mode_include_dir");	
}

function getOpenBasedir ()
{
	return getPHPIniSetting("open_basedir");
}

function getDisabledFunctions ()
{
	return getPHPIniSetting("disable_functions");	
}
?>
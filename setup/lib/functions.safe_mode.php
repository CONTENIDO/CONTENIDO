<?php

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
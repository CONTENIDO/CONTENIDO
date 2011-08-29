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
 * @package    CONTENIDO Backend <Area>
 * @version    0.3
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
 *   modified 2011-02-08, Dominik Ziegler, removed old PHP compatibility stuff as CONTENIDO now requires at least PHP 5
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


define("E_EXTENSION_AVAILABLE",		1);
define("E_EXTENSION_UNAVAILABLE",	2);
define("E_EXTENSION_CANTCHECK", 	3);


/**
 * getPHPIniSetting ($setting)
 * 
 * Retrieves the setting $setting from the PHP setup.
 * Wrapper to avoid warnings if ini_get is in the
 * disable_functions directive.
 */
function getPHPIniSetting ($setting)
{
	/* Avoid errors if ini_get is in the disable_functions directive */
	$value = @ini_get($setting);
	
	return $value;
}

/**
 * canPHPurlfopen: Checks if PHP is able to use
 * allow_url_fopen.
 */
function canPHPurlfopen ()
{
	return getPHPIniSetting("allow_url_fopen");	
}

/**
 * checkPHPiniget: Checks if the ini_get function
 * is available and not disabled. Returns true if the
 * function is available.
 * 
 * Uses the PHP configuration value y2k_compilance which
 * is available in all PHP4 versions.
 */
function checkPHPiniget ()
{
	$value = @ini_get("y2k_compliance");
	
	if ($value === NULL)
	{
		return false;	
	} else {
		return true;
	}
}

function getPHPDisplayErrorSetting ()
{
	return getPHPIniSetting("display_errors");	
}

function getPHPFileUploadSetting ()
{
	return getPHPIniSetting("file_uploads");	
}

function getPHPGPCOrder ()
{
	return getPHPIniSetting("gpc_order");	
}

function getPHPMagicQuotesGPC ()
{
	return getPHPIniSetting("magic_quotes_gpc");	
}

function getPHPMagicQuotesRuntime ()
{
	return getPHPIniSetting("magic_quotes_runtime");	
}

function getPHPMagicQuotesSybase ()
{
	return getPHPIniSetting("magic_quotes_sybase");	
}

function getPHPMaxExecutionTime ()
{
	return getPHPIniSetting("max_execution_time");	
}

function getPHPOpenBasedirSetting ()
{
	return getPHPIniSetting("open_basedir");	
}

function getPHPMaxPostSize ()
{
	return getPHPIniSetting("post_max_size");	
}

function checkPHPSQLSafeMode ()
{
	return getPHPIniSetting("sql.safe_mode");	
}

function checkPHPUploadMaxFilesize ()
{
	return getPHPIniSetting("upload_max_filesize");	
}

function return_bytes($val) {
	if (strlen($val) == 0)
	{
		return 0;	
	}
    $val = trim($val);
    $last = $val{strlen($val)-1};
    switch($last) {
        case 'k':
        case 'K':
            return (int) $val * 1024;
            break;
        case 'm':
        case 'M':
            return (int) $val * 1048576;
            break;
        default:
            return $val;
    }
}

function isPHPExtensionLoaded ($extension)
{
	$value = extension_loaded($extension);
	
	
	if ($value === NULL)
	{
		return E_EXTENSION_CANTCHECK;	
	}
	
	if ($value === true)
	{
		return E_EXTENSION_AVAILABLE;	
	}
	
	if ($value === false)
	{
		return E_EXTENSION_UNAVAILABLE;	
	}
}

function isRegisterLongArraysActive ()
{	
	if (version_compare(phpversion(), "5.0.0", ">=") == true)
	{
		if (getPHPIniSetting("register_long_arrays") == false)
		{
			return false;
		}
	}
	
	return true;
}
function isPHPCompatible ()
{
	if (version_compare(phpversion(), "4.1.0", ">=") == true)
	{
		return true;	
	} else {
		return false;	
	}
}

function isPHP423 ()
{
	if (phpversion() == "4.2.2" || phpversion() == "4.2.3")
	{
		return true;	
	} else {
		return false;
	}
}
?>
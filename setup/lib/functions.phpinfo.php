<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: functions.phpinfo.php 740 2008-08-27 10:45:04Z timo.trautmann $:
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


/**
 * Replace version_compare()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.version_compare
 * @author      Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision$
 * @since       PHP 4.1.0
 * @require     PHP 4.0.0 (user_error)
 */
if (!function_exists('version_compare')) {
    function version_compare($version1, $version2, $operator = '<')
    {
        // Check input
        if (!is_scalar($version1)) {
            user_error('version_compare() expects parameter 1 to be string, ' .
                gettype($version1) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_scalar($version2)) {
            user_error('version_compare() expects parameter 2 to be string, ' .
                gettype($version2) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_scalar($operator)) {
            user_error('version_compare() expects parameter 3 to be string, ' .
                gettype($operator) . ' given', E_USER_WARNING);
            return;
        }

        // Standardise versions
        $v1 = explode('.',
            str_replace('..', '.',
                preg_replace('/([^0-9\.]+)/', '.$1.',
                    str_replace(array('-', '_', '+'), '.',
                        trim($version1)))));

        $v2 = explode('.',
            str_replace('..', '.',
                preg_replace('/([^0-9\.]+)/', '.$1.',
                    str_replace(array('-', '_', '+'), '.',
                        trim($version2)))));

        // Replace empty entries at the start of the array
        while (empty($v1[0]) && array_shift($v1)) {}
        while (empty($v2[0]) && array_shift($v2)) {}

        // Release state order
        // '#' stands for any number
        $versions = array(
            'dev'   => 0,
            'alpha' => 1,
            'a'     => 1,
            'beta'  => 2,
            'b'     => 2,
            'RC'    => 3,
            '#'     => 4,
            'p'     => 5,
            'pl'    => 5);

        // Loop through each segment in the version string
        $compare = 0;
        for ($i = 0, $x = min(count($v1), count($v2)); $i < $x; $i++) {
            if ($v1[$i] == $v2[$i]) {
                continue;
            }
            $i1 = $v1[$i];
            $i2 = $v2[$i];
            if (is_numeric($i1) && is_numeric($i2)) {
                $compare = ($i1 < $i2) ? -1 : 1;
                break;
            }
            // We use the position of '#' in the versions list
            // for numbers... (so take care of # in original string)
            if ($i1 == '#') {
                $i1 = '';
            } elseif (is_numeric($i1)) {
                $i1 = '#';
            }
            if ($i2 == '#') {
                $i2 = '';
            } elseif (is_numeric($i2)) {
                $i2 = '#';
            }
            if (isset($versions[$i1]) && isset($versions[$i2])) {
                $compare = ($versions[$i1] < $versions[$i2]) ? -1 : 1;
            } elseif (isset($versions[$i1])) {
                $compare = 1;
            } elseif (isset($versions[$i2])) {
                $compare = -1;
            } else {
                $compare = 0;
            }

            break;
        }

        // If previous loop didn't find anything, compare the "extra" segments
        if ($compare == 0) {
            if (count($v2) > count($v1)) {
                if (isset($versions[$v2[$i]])) {
                    $compare = ($versions[$v2[$i]] < 4) ? 1 : -1;
                } else {
                    $compare = -1;
                }
            } elseif (count($v2) < count($v1)) {
                if (isset($versions[$v1[$i]])) {
                    $compare = ($versions[$v1[$i]] < 4) ? -1 : 1;
                } else {
                    $compare = 1;
                }
            }
        }

        // Compare the versions
        if (func_num_args() > 2) {
            switch ($operator) {
                case '>':
                case 'gt':
                    return (bool) ($compare > 0);
                    break;
                case '>=':
                case 'ge':
                    return (bool) ($compare >= 0);
                    break;
                case '<=':
                case 'le':
                    return (bool) ($compare <= 0);
                    break;
                case '==':
                case '=':
                case 'eq':
                    return (bool) ($compare == 0);
                    break;
                case '<>':
                case '!=':
                case 'ne':
                    return (bool) ($compare != 0);
                    break;
                case '':
                case '<':
                case 'lt':
                    return (bool) ($compare < 0);
                    break;
                default:
                    return;
            }
        }

        return $compare;
    }
}

?>
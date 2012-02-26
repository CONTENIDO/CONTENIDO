<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Some system functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.2.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-06-27, Timo Trautmann, add check to emptyLogFile if there is a permission to write file
 *   modified 2008-07-07, Dominik Ziegler, fixed language bugs
 *   modified 2008-11-21, Andreas Lindner, enhance formatting of client information
 *   modified 2008-11-21, Andreas Lindner, beautify output for empty configuration values
 *   modified 2011-05-18, Ortwin Pinke, bugfix fill missing tpl-values for bgcolor and rowid
 *   modified 2011-08-23, Dominik Ziegler, removed support for old function sendBugReport()
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Clears CONTENIDO standard errorlog.txt
 * @return string returns message if clearing was successfull or not		
 */
function emptyLogFile() {
	global $cfg, $notification;
	
	$tmp_notification = false;
	
	// clear errorlog.txt
	$filename = $cfg['path']['contenido'] . "logs/errorlog.txt";
	
	if (file_exists($filename) && is_writeable($filename)) {
		$errorLogHandle = fopen($filename, "wb+");
		fclose($errorLogHandle);
		$tmp_notification = $notification->returnNotification("info", i18n("Error log successfully cleared!"));
	} else if (file_exists($filename) && !is_writeable($filename)) {
		$tmp_notification = $notification->returnNotification("error", i18n("Can't clear error log : Access is denied!"));
	}
	
	return $tmp_notification;
}

/**
 * phpInfoToHtml - grabs phpinfo() output
 *
 * grabs phpinfo() HTML output
 * 
 * @return string returns phpinfo() HTML output		
 * @author Marco Jahn
 */
function phpInfoToHtml()
{
	/* get output */
	ob_start();
	phpinfo();
	$phpInfoToHtml = ob_get_contents();
	ob_end_clean();

	return $phpInfoToHtml;
}

/**
 * check users right for a client
 *
 * check if the user has a right for a defined client
 * 
 * @param integer client id
 *
 * @return boolean wether user has access or not	
 * @author Marco Jahn
 */
function system_have_perm($client)
{
	global $auth;

	if (!isset ($auth->perm['perm']))
	{
		$auth->perm['perm'] = '';
	}

	$userPerm = explode(',', $auth->auth['perm']);

	if (in_array('sysadmin', $userPerm))
	{ // is user sysadmin ?
		return true;
	}
	elseif (in_array('admin['.$client.']', $userPerm))
	{ // is user admin for this client ?
		return true;
	}
	elseif (in_array('client['.$client.']', $userPerm))
	{ // has user access to this client ?
		return true;
	}
	return false;
}

/**
* check for valid ip adress
*
* @param string ip adress
*
* @return boolean if string is a valid ip or not
*/
function isIPv4($strHostAdress)
{
	// ip pattern needed for validation
	$ipPattern = "([0-9]|1?\d\d|2[0-4]\d|25[0-5])";
	if (preg_match("/^$ipPattern\.$ipPattern\.$ipPattern\.$ipPattern?$/", $strHostAdress))
	{ // ip is valid
		return true;
	}
	return false;
}

/**
* must be done
*
* must be done
*
* @param string CONTENIDO fullhtmlPath
* @param string current browser string
*
* @return string status of path comparement
*/
function checkPathInformation($strConUrl, $strBrowserUrl)
{
	// parse url
	$arrConUrl = parse_url($strConUrl);
	$arrBrowserUrl = parse_url($strBrowserUrl);

	if (isIPv4($arrConUrl['host']))
	{ // is
		if (isIPv4($arrBrowserUrl['host']))
		{ // is
			if (compareUrlStrings($arrConUrl, $arrBrowserUrl))
			{
				return '1';
			}

			return '2';
		} else
		{ // isn't
			$arrBrowserUrl['host'] = gethostbyname($arrBrowserUrl['host']);
			if (!isIPv4($arrBrowserUrl['host']))
			{
				return '3';
			}

			if (compareUrlStrings($arrConUrl, $arrBrowserUrl))
			{
				return '1';
			}

			return '2';
		}
	} else
	{ // isn't
		if (isIPv4($arrBrowserUrl['host']))
		{ //is
			$tmpAddr = gethostbyaddr($arrBrowserUrl['host']);
			$arrBrowserUrl['host'] = str_replace('-', '.', substr($tmpAddr, 0, strpos($tmpAddr, ".")));

			if (isIPv4($arrBrowserUrl['host']))
			{
				return '3';
			}

			if (compareUrlStrings($arrConUrl, $arrBrowserUrl, true))
			{
				return '1';
			}

			return '2';

		} else
		{ // isn't
			if (compareUrlStrings($arrConUrl, $arrBrowserUrl))
			{
				return '1';
			}

			return '2';
		}
	}
}

/**
* check path informations
*
* checks two path informations against each other to get potential nonconformities
*/
function compareUrlStrings($arrConUrl, $arrBrowserUrl, $isIP = false)
{
		// && $isIP == false

		// remove 'www.' if needed
	if (strpos($arrConUrl['host'], 'www.') == 0 || strpos($arrBrowserUrl['host'], 'www.') == 0)
	{
		$arrConUrl['host'] = str_replace('www.', '', $arrConUrl);
		$arrBrowserUrl['host'] = str_replace('www.', '', $arrBrowserUrl);
	}

	$strConUrl = $arrConUrl['scheme'].'://'.$arrConUrl['host'].$arrConUrl['path'];
	$strBrowserUrl = $arrBrowserUrl['scheme'].'://'.$arrBrowserUrl['host'].$arrBrowserUrl['path'];

	if (strcmp($strConUrl, $strBrowserUrl) != 0)
	{
		return false;
	}
	return true;
}

/**
 * writeSystemValuesOutput - get several server and CONTENIDO settings
 *
 * parse system and CONTENIDO output into a string
 *
 * @deprecated 2012-02-26 Moved directly to include.system_sysvalues.php
 * 
 * @return void
 */
function writeSystemValuesOutput($usage)
{
	cDeprecated("This function is not longer supported.");
}
?>

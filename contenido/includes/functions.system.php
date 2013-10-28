<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Some system functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.2.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-06-27, Timo Trautmann, add check to emptyLogFile if there is a permission to write file
 *   modified 2008-07-07, Dominik Ziegler, fixed language bugs
 *   modified 2008-11-21, Andreas Lindner, enhance formatting of client information
 *   modified 2008-11-21, Andreas Lindner, beautify output for empty configuration values     
 *
 *   $Id: functions.system.php 888 2008-11-21 10:19:10Z andreas.lindner $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * emptyLogFile - clears errorlog.txt
 *
 * clears Contenido standard errorlog.txt
 * 
 * @return string returns message if clearing was successfull or not		
 * @author Marco Jahn
 */
function emptyLogFile()
{
	global $cfg, $notification;
	if ($_GET['log'] == 1)
	{ // clear errorlog.txt
		if (file_exists($cfg['path']['contenido']."logs/errorlog.txt") && is_writeable($cfg['path']['contenido']."logs/errorlog.txt"))
		{
			$errorLogHandle = fopen($cfg['path']['contenido']."logs/errorlog.txt", "wb+");
			fclose($errorLogHandle);
			$tmp_notification = $notification->returnNotification("info", i18n("error log successfully cleared"));
		} else if (file_exists($cfg['path']['contenido']."logs/errorlog.txt") && !is_writeable($cfg['path']['contenido']."logs/errorlog.txt")) {
		    $tmp_notification = $notification->returnNotification("error", i18n("Can't clear error log: Access is denied!"));
		}
	}
	elseif ($_GET['log'] == 2)
	{
		if (file_exists($cfg['path']['contenido']."logs/install.log.txt") && is_writeable($cfg['path']['contenido']."logs/install.log.txt"))
		{ // clear install.log.txt
			$errorLogHandle = fopen($cfg['path']['contenido']."logs/install.log.txt", "wb+");
			fclose($errorLogHandle);
			$tmp_notification = $notification->returnNotification("info", i18n("install error log successfully cleared"));
		} else if (file_exists($cfg['path']['contenido']."logs/install.log.txt") && !is_writeable($cfg['path']['contenido']."logs/install.log.txt")) {
		    $tmp_notification = $notification->returnNotification("error", i18n("Can't clear install error log: Access is denied!"));
		}
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
* @param string contenido fullhtmlPath
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
 * writeSystemValuesOutput - get several server and Contenido settings
 *
 * parse system and Contenido output into a string
 * 
 * @return string returns a string containing several server and Contenido settings		
 * @author Marco Jahn
 */
function writeSystemValuesOutput($usage)
{

	global $db, $_SERVER, $cfg, $i18n, $tpl;

	/* variables to proof against each other*/

	$contenidoFullHtml = $cfg['path']['contenido_fullhtml'];
	$browserPath  = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http'; 
    $browserPath .= "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$browserPath = substr($browserPath, 0, strrpos($browserPath, "/") + 1);

	$status = checkPathInformation($contenidoFullHtml, $browserPath);

	if ($status == 1)
	{ // green
		$contenidoFullHtml = "<span style=\"color:green;\">".$contenidoFullHtml."</span><br>";
		$browserPath = "<span style=\"color:green;\">".$browserPath."</span>";

	}
	elseif ($status == 2)
	{ // red
		$contenidoFullHtml = "<span style=\"color:red;\">".$contenidoFullHtml."</span><br>";
		$browserPath = "<span style=\"color:red;\">".$browserPath."</span>";

	}
	elseif ($status == 3)
	{ //orange
		$contenidoFullHtml = "<span style=\"color:orange;\">".$contenidoFullHtml."</span><br>";
		$browserPath = "<span style=\"color:orange;\">".$browserPath."</span>";

	}

	/* generate sysvalue output */
	$i = 0; // array start value
	// current Contenido version
	$sysvalues[$i]['variable'] = i18n("Contenido version");
	$sysvalues[$i ++]['value'] = $cfg['version'];
	// paths from config.php
	$sysvalues[$i]['variable'] = i18n("Contenido path");
	$sysvalues[$i ++]['value'] = $cfg['path']['contenido'];
	$sysvalues[$i]['variable'] = i18n("Contenido HTML path");
	$sysvalues[$i ++]['value'] = $cfg['path']['contenido_html'];
	$sysvalues[$i]['variable'] = i18n("Contenido full HTML path");
	$sysvalues[$i ++]['value'] = $contenidoFullHtml;
	$sysvalues[$i]['variable'] = i18n("Contenido frontend path");
	$sysvalues[$i ++]['value'] = $cfg['path']['frontend'];
	$sysvalues[$i]['variable'] = i18n("Contenido PHPLIB path");
	$sysvalues[$i ++]['value'] = $cfg['path']['phplib'];
	$sysvalues[$i]['variable'] = i18n("Contenido wysiwyg path");
	$sysvalues[$i ++]['value'] = $cfg['path']['wysiwyg'];
	$sysvalues[$i]['variable'] = i18n("Contenido wysiwyg HTML path");
	$sysvalues[$i ++]['value'] = $cfg['path']['wysiwyg_html'];
	// host name
	$sysvalues[$i]['variable'] = i18n("Host name");
	$sysvalues[$i ++]['value'] = $_SERVER['HTTP_HOST'];
	// Contenido browser path 
	$sysvalues[$i]['variable'] = i18n("Browser path");
	/* cut of file information */
	$sysvalues[$i ++]['value'] = $browserPath;
	// get number of clients
	$sql = "SELECT count(name) clientcount FROM ".$cfg["tab"]["clients"];
	$db->query($sql);
	$db->next_record();
	$clientcount = $db->f("clientcount");

	// get all clients and their language
	$sql = "SELECT count(a.name) clientcount,
	    		a.name clientname,
	    		a.idclient
	    		FROM
	    		".$cfg["tab"]["clients"]." a
	    		GROUP BY a.name";
	$db->query($sql);

	// create 'value' output
	$db2 = new DB_Contenido;
	$clientInformation = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
	        <tr class=\"textw_medium\" style=\"background-color: #E2E2E2\">
					<td width=\"20%\" class=\"textg_medium\" style=\"border:1px; border-color:#B3B3B3; border-style:solid;; border-bottom: none;\" nowrap=\"nowrap\">".i18n("client settings")."</td>
	            <td class=\"textg_medium\" style=\"border:1px; border-left:0px; border-color: #B3B3B3; border-style: solid;border-bottom: none;\" nowrap=\"nowrap\">".i18n("values")."</td>
	        </tr>";

	$clientPermCount = 0;
	while ($db->next_record())
	{
		if (system_have_perm($db->f("idclient")))
		{
			$clientlang = "";

			// get client name
			$clientName = urldecode($db->f("clientname"));
			$clientInformation .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
			                	<td colspan=\"2\" class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid\" nowrap=\"nowrap\" align=\"left\" valign=\"top\"><i>$clientName</i></td>
			                </tr>";
			$clientlang = "";
			// select languages belong to a client
			$sql = "SELECT c.name clientlang
			        	FROM ".$cfg["tab"]["clients"]." a
			        	LEFT JOIN ".$cfg["tab"]["clients_lang"]." b ON a.idclient = b.idclient
			        	LEFT JOIN ".$cfg["tab"]["lang"]." c ON b.idlang = c.idlang
			        	WHERE a.idclient=".Contenido_Security::toInteger($db->f("idclient"))." AND c.name IS NOT NULL";
			$db2->query($sql);
			while ($db2->next_record())
			{
				$clientlang .= $db2->f("clientlang").", ";
			}
			// cut off last ","
			$clientlang = substr($clientlang, 0, strlen($clientlang) - 2);

			$clientInformation .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
			                	<td class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid; \" nowrap=\"nowrap\" align=\"left\" valign=\"top\">".i18n("language(s)")."</td>
			                	<td class=\"text_medium\" width=\"60%\" style=\"border:1px; border-left:0px; border-top:0px; border-color: #B3B3B3; border-style: solid; \" nowrap=\"nowrap\">$clientlang&nbsp;</td>
			           		</tr>";

			$sql = "SELECT frontendpath, htmlpath FROM ".$cfg["tab"]["clients"]." WHERE idclient='".Contenido_Security::toInteger($db->f("idclient"))."'";
			$db2->query($sql);
			while ($db2->next_record())
			{
				$clientInformation .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
				                	<td class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid\" nowrap=\"nowrap\" align=\"left\" valign=\"top\">".i18n("htmlpath")."</td>
				                	<td class=\"text_medium\" width=\"60%\" style=\"border:1px; border-left:0px; border-top:0px; border-color: #B3B3B3; border-style: solid;\" nowrap=\"nowrap\">".$db2->f("htmlpath")."&nbsp;</td>
				           		</tr>";
				$clientInformation .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
				                	<td class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid\" nowrap=\"nowrap\" align=\"left\" valign=\"top\">".i18n("frontendpath")."</td>
				                	<td class=\"text_medium\" width=\"60%\" style=\"border:1px; border-left:0px; border-top:0px; border-color: #B3B3B3; border-style: solid;\" nowrap=\"nowrap\">".$db2->f("frontendpath")."&nbsp;</td>
				           		</tr>";
			}
			$clientPermCount ++;
		}

	}

	if ($clientPermCount == 0)
	{
		$clientInformation .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
		                	<td colspan=\"2\" class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid; \" nowrap=\"nowrap\" align=\"left\" valign=\"top\">".i18n("No permissions!")."</td>
		           		</tr>";
	}

	$clientInformation .= '</table>';

	$clientdata = i18n("Number of installed clients: ").$clientcount."<br>".$clientInformation;

	// client quantity and their assigned language and are they online 
	$sysvalues[$i]['variable'] = i18n("Client informations");
	$sysvalues[$i ++]['value'] = "$clientdata";
	// get number of users installed
	$sql = "SELECT count(user_id) usercount FROM ".$cfg["tab"]["phplib_auth_user_md5"];
	$db->query($sql);
	$db->next_record();
	// number of users
	$sysvalues[$i]['variable'] = i18n("Number of users");
	$sysvalues[$i ++]['value'] = $db->f("usercount");
	//get number of articles
	$sql = "SELECT count(idart) articlecount FROM ".$cfg["tab"]["art"];
	$db->query($sql);
	$db->next_record();
	// number of articles
	$sysvalues[$i]['variable'] = i18n("Number of articles");
	$sysvalues[$i ++]['value'] = $db->f("articlecount");
	// server operating system
	$sysvalues[$i]['variable'] = i18n("Server operating system");
	$sysvalues[$i ++]['value'] = $_SERVER['SERVER_SOFTWARE'];
	// SQL version
	$sql_server_info = $db->server_info();
	$sysvalues[$i]['variable'] = i18n("PHP database extension");
	$sysvalues[$i ++]['value'] = $cfg["database_extension"];
	$sysvalues[$i]['variable'] = i18n("Database server version");
	$sysvalues[$i ++]['value'] = $sql_server_info['description'];
	// php version
	$sysvalues[$i]['variable'] = i18n("Installed PHP version");
	$sysvalues[$i ++]['value'] = phpversion();
	// php config values
	// config values
	// php safe_mode
	 (ini_get('safe_mode') == 1) ? $safe_mode = "<span stlye=\"color:red;\">".i18n("activated")."</span>" : $safe_mode = "<span style=\"color:green;\">".i18n("deactivated")."</span>";
	$sysvalues[$i]['variable'] = "safe_mode";
	$sysvalues[$i ++]['value'] = $safe_mode;
	// magig quotes GPC
	 (ini_get('magic_quotes_gpc') == 1) ? $magic_quotes_gpc = i18n("activated") : $magic_quotes_gpc = i18n("deactivated");
	$sysvalues[$i]['variable'] = "magic_quotes_gpc";
	$sysvalues[$i ++]['value'] = $magic_quotes_gpc;
	// magic quotes runtime
	 (ini_get('magic_quotes_runtime') == 1) ? $magic_quotes_runtime = i18n("activated") : $magic_quotes_runtime = i18n("deactivated");
	$sysvalues[$i]['variable'] = "magic_quotes_runtime";
	$sysvalues[$i ++]['value'] = $magic_quotes_runtime;
	// GPC order
	$sysvalues[$i]['variable'] = "gpc_order";
	$sysvalues[$i ++]['value'] = ini_get('gpc_order');
	// memory limit
	$sysvalues[$i]['variable'] = "memory_limit";
	$sysvalues[$i ++]['value'] = ini_get('memory_limit');
	// max execution time
	$sysvalues[$i]['variable'] = "max_execution_time";
	$sysvalues[$i ++]['value'] = ini_get('max_execution_time');
	// disabled functions
	 (strlen(ini_get('disable_functions')) > 0) ? $disable_functions = "<span style=\"color:red;\">".ini_get('disable_functions')."</span>" : $disable_functions = "<span style=\"color:green\">".i18n("nothing disabled")."</span>";
	$sysvalues[$i]['variable'] = i18n("Disabled functions");
	$sysvalues[$i ++]['value'] = $disable_functions;
	// gettext loaded
	 (extension_loaded('gettext') == true) ? $gettext = "<span style=\"color:green;\">".i18n("loaded")."</span>" : $gettext = "<span stlye=\"color:red;\">".i18n("not loaded")."</span>";
	$sysvalues[$i]['variable'] = i18n("Gettext extension");
	$sysvalues[$i ++]['value'] = $gettext;
	// sql.safe_mode
	 (ini_get('sql.safe_mode') == 1) ? $sql_safe_mode = "<span style=\"color:red;\">".i18n("activated")."</span>" : $sql_safe_mode = "<span style=\"color:green;\">".i18n("deactivated")."</span>";
	$sysvalues[$i]['variable'] = "sql.safe_mode";
	$sysvalues[$i ++]['value'] = $sql_safe_mode;
	// gdlib with installed features
	$gdLib = array();
	$gdLib = getPhpModuleInfo($moduleName = 'gd');
	$gdLibFeatures = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
	        <tr class=\"textg_medium\" style=\"background-color: #E2E2E2\">
	            <td width=\"20%\" class=\"textg_medium\" style=\"border:1px; border-color:#B3B3B3; border-style:solid;border-bottom:none\" nowrap=\"nowrap\">".i18n("Settings")."</td>
	            <td class=\"textg_medium\" style=\"border:1px; border-left:0px; border-color: #B3B3B3; border-style: solid; border-bottom:none\" nowrap=\"nowrap\">".i18n("Values")."</td>
	        </tr>";

	foreach ($sysvalues as $key => $value) {
		if (trim ($value['value']) == '') {
			$sysvalues[$key]['value'] = '&nbsp;'; 
		}
	}

	foreach ($gdLib as $setting => $value)
	{
		$gdLibFeatures .= "<tr class=\"text_medium\" style=\"background-color: {BGCOLOR};\" >
		            <td class=\"text_medium\" style=\"border:1px; border-top:0px; border-color: #B3B3B3; border-style: solid;\" nowrap=\"nowrap\" align=\"left\" valign=\"top\">".$setting."</td>
		            <td class=\"text_medium\" width=\"60%\" style=\"border:1px; border-left:0px; border-top:0px; border-color: #B3B3B3; border-style: solid;\" nowrap=\"nowrap\">".$value[0]."</td>
		        </tr>";
	}
	$gdLibFeatures .= '</table>';
	$sysvalues[$i]['variable'] = i18n("GD library");
	$sysvalues[$i ++]['value'] = $gdLibFeatures;

	// include path settings
	$sysvalues[$i]['variable'] = "include_path";
	$sysvalues[$i ++]['value'] = ini_get('include_path');
	//loop array for every parameter
	foreach ($sysvalues AS $sysvalue)
	{
		$tpl->set('d', 'VARIABLE', $sysvalue['variable']);
		$tpl->set('d', 'LOCALVALUE', $sysvalue['value']);
		$tpl->next();
	}

	/* irgendwas sinnvolles :) */
	if ($usage == 'mail')
	{
		return $tpl->generate($cfg['path']['templates'].$cfg['templates']['systam_variables_mailattach'], true);
	}
	elseif ($usage == 'output')
	{
		// do nothing
	}

}

/**
 * sendBugReport - send users bugreport
 *
 * send users bugreport
 * contains 4 attachements
 * (1) errorlog.txt
 * (2) install.log.txt
 * (3) systemvariablen.html (generaten of writeSystemValuesOutput())
 * (4) phpinfo.html (generated of phpInfoToHtml())
 * 
 * to avoid errors when filesize attachement (1) or (2)
 * is 0kb temporarily attachements with a specified message
 * will be created
 *
 * return value:
 * - error code (0-3)
 *   0) mail send successfull (no errors or missing data)
 *   1) not all fields are filled out
 *   2) email adress is not valid
 *   3) user hasn't agreed to the declaration of consent
 * 
 *
 * @return string returns several server and Contenido settings		
 * @author Marco Jahn
 */
function sendBugReport()
{
	global $_POST, $notification, $cfg;

	/* will be set to another value than 0 if an error attempts */
	$mailSendError = 0;

	/* check if email is filled out */
	if (strlen($_POST['sender']) == 0)
	{
		$mailSendError = 1;
	}

	/* check if forename is filled out */
	if (strlen($_POST['forename']) == 0)
	{
		$mailSendError = 1;
	}

	/* check if surname is filled out */
	if (strlen($_POST['surname']) == 0)
	{
		$mailSendError = 1;
	}

	/* check if bugreport is filled out */
	if (strlen($_POST['bugreport']) == 0)
	{
		$mailSendError = 1;
	}

	/* check if email adress is valid */
	if (isValidMail($_POST['sender']) == false)
	{
		$mailSendError = 2;
	}

	/* user has not agreed */
	if ($_POST['agreement'] != 'on')
	{
		$mailSendError = 3;
	}

	if ($mailSendError == 0)
	{
		/* send mail */

		/* initialize mail class */
		$mail = new PHPMailer();

		/* set sender information */
		$mail->From = strip_tags($_POST['sender']);
		$mail->FromName = strip_tags($_POST['forename']." ".$_POST['surname']);

		/* set recipient */
		$mail->AddAddress($cfg['bugreport']['targetemail'], "Bugreport recipient");

		/* set mail function to use */
		$mail->Mailer = "mail"; //use php mail function

		/* generate subject & body */
		$mail->Subject = "Bugreport";
		$mail->Body = "Fehlerbereich: ".$_POST['selectarea']."<br><br>".nl2br(strip_tags($_POST['bugreport']));
		$mail->AltBody = "Fehlerbereich: ".$_POST['selectarea']."\n\n".strip_tags($_POST['bugreport']);

		/* add attachements */
		if ($_POST['errorlog'] == 'on')
		{
			if (filesize($cfg['path']['contenido']."logs/errorlog.txt") > 0)
			{ //filesize > 0 send alternative attachement
				$mail->AddAttachment($cfg['path']['contenido']."logs/errorlog.txt", "errorlog.txt");
			} else
			{
				$mail->AddStringAttachment("No error log entries found\n", "errorlog.txt");
			}
		}

		if ($_POST['upgradeerrorlog'] == 'on')
		{
			if (filesize($cfg['path']['contenido']."logs/install.log.txt") > 0)
			{ //filesize > 0 send alternative attachement
				$mail->AddAttachment($cfg['path']['contenido']."logs/install.log.txt", "install.log.txt");
			} else
			{
				$mail->AddStringAttachment("No install error log entries found\n", "install.log.txt");
			}
		}

		if ($_POST['sysvalues'] == 'on')
		{
			//send sysvalue output
			$mail->AddStringAttachment(writeSystemValuesOutput($usage = 'mail'), "systemvariables.html");
		}

		if ($_POST['phpinfo'] == 'on')
		{
			//send phpinfo output
			$mail->AddStringAttachment(phpInfoToHtml(), "phpinfo.html");
		}

		if (!$mail->Send())
		{
			$tmp_notification = $notification->returnNotification("error", i18n("an error occured while sending your bug report! Please try again"));
		} else
		{
			$tmp_notification = $notification->returnNotification("info", i18n("bug report forwarded"));
		}
	}
	elseif ($mailSendError == 1)
	{
		/* user should fill all fields */
		$tmp_notification = $notification->returnNotification("warning", i18n("please fill out all mandatory fields"));
	}
	elseif ($mailSendError == 2)
	{ /* email adress is not valid */
		$tmp_notification = $notification->returnNotification("warning", i18n("please enter a valid E-Mail adress"));
	}
	elseif ($mailSendError == 3)
	{ /* user hasn't agreed to the declaration of consent */
		$tmp_notification = $notification->returnNotification("warning", i18n("you must agree the declaration of consent"));
	}
	return $mailSendError."||".$tmp_notification;
}
?>

<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Output of important system variables
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.7.1
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-08-15
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2011-05-18, Ortwin Pinke, clean url due to validate page
 *   modified 2012-01-17, Mischa Holz, removed the upgrade error log
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->reset();

/*
 * print out tmp_notifications if any action has been done
*/
if (isset($tmp_notification))
{
	$tpl->set('s', 'TEMPNOTIFICATION', $tmp_notification);
}
else
{
	$tpl->set('s', 'TEMPNOTIFICATION', '');	
}

/* get system variables for output */
writeSystemValuesOutput($usage='output');

// error log
if (file_exists($cfg['path']['contenido']."logs/errorlog.txt"))
{
    $errorLogHandle = fopen ($cfg['path']['contenido']."logs/errorlog.txt", "rb");
    $txtAreaHeight = "200";
    
    /* If the file is larger than 16KB, seek to the file's length - 16KB) */
    fseek($errorLogHandle, -16384,SEEK_END);
    
    while (!feof($errorLogHandle))
    {
        $errorLogBuffer .= fgets($errorLogHandle, 16384);
    }
    fclose ($errorLogHandle);
    if (strlen ($errorLogBuffer) == 0)
    {
    	$errorLogBuffer = i18n("No error log entries found");
    	$txtAreaHeight = "20";	
    }
    
}
else
{
	$errorLogBuffer = i18n("No error log file found");
	$txtAreaHeight = "20";	
}
$tpl->set('s', 'TXTERRORLOGSIZE', $txtAreaHeight);
$tpl->set('s', 'ERRORLOG', $errorLogBuffer);

/*
 * parameter which log shoult be cleared
 * log = 1	clear /contenido/logs/errorlog.txt
*/
$tpl->set('s', 'LOGEMPTYURL', htmlentities($sess->url("main.php?area=$area&frame=$frame&action=emptyLog&log=1")));

// parse out template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['systam_variables']);

?>
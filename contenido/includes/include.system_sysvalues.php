<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Output of important system variables
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.0
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-08-15
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.system_sysvalues.php 371 2008-06-27 14:35:19Z frederic.schneider $:
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

// upgrade error log
if (file_exists($cfg['path']['contenido']."logs/install.log.txt"))
{
    $upgErrorLogHandle = fopen ($cfg['path']['contenido']."logs/install.log.txt", "rb");
    $txtAreaHeight = "200";
    
    /* If the file is larger than 200KB, seek to the file's length - 200KB) */
    fseek($upgErrorLogHandle, -16384,SEEK_END);
        
    while (!feof($upgErrorLogHandle))
    {
        $upgErrorLogBuffer.= fgets($upgErrorLogHandle, 16384);
    }
    fclose ($upgErrorLogHandle);
    if (strlen ($upgErrorLogBuffer) == 0)
    {
    	$upgErrorLogBuffer = i18n("No install error log entries found");
    	$txtAreaHeight = "20";	
    }
    
}
else
{
	$upgErrorLogBuffer = i18n("No error log entries found");
	$txtAreaHeight = "20";	
}
$tpl->set('s', 'TXTUPGERRORLOGSIZE', $txtAreaHeight);
$tpl->set('s', 'UPGERRORLOG', $upgErrorLogBuffer);

/*
 * parameter which log shoult be cleared
 * log = 1	clear /contenido/logs/errorlog.txt
 * log = 2	clear /contenido/upgrade_errorlog.txt
*/
$tpl->set('s', 'LOGEMPTYURL', $sess->url("main.php?area=$area&frame=$frame&action=emptyLog&log=1"));
$tpl->set('s', 'UPGLOGEMPTYURL', $sess->url("main.php?area=$area&frame=$frame&action=emptyLog&log=2"));

// parse out template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['systam_variables']);

?>
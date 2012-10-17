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
 * @version    1.7.2
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-08-15
 *   $Id: include.system_sysvalues.php 3292 2012-09-22 20:27:03Z dominik.ziegler $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$tpl->reset();

// print out tmp_notifications if any action has been done
if (isset($tmp_notification)) {
    $tpl->set('s', 'TEMPNOTIFICATION', $tmp_notification);
} else {
    $tpl->set('s', 'TEMPNOTIFICATION', '');
}

// error log
if (cFileHandler::exists($cfg['path']['contenido_logs'] . 'errorlog.txt')) {
    $info = cFileHandler::info($cfg['path']['contenido_logs'] . 'errorlog.txt');
    if ($info['size'] >= 16384) {
        $errorLogBuffer = cFileHandler::read($cfg['path']['contenido_logs'] . 'errorlog.txt', 16384, 0, true);
    } else {
        $errorLogBuffer = cFileHandler::read($cfg['path']['contenido_logs'] . 'errorlog.txt');
    }
    $txtAreaHeight = "200";

    if (strlen($errorLogBuffer) == 0) {
        $errorLogBuffer = i18n("No error log entries found");
        $txtAreaHeight = "20";
    }
} else {
    $errorLogBuffer = i18n("No error log file found");
    $txtAreaHeight = "20";
}

$tpl->set('s', 'TXTERRORLOGSIZE', $txtAreaHeight);
$tpl->set('s', 'ERRORLOG', $errorLogBuffer);
$tpl->set('s', 'LOGEMPTYURL', conHtmlentities($sess->url("main.php?area=$area&frame=$frame&action=emptyLog")));

// parse out template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_log_variables']);
?>
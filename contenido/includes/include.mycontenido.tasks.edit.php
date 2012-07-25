<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MyContenido task edit page
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.4
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$cpage = new cGuiPage("mycontenido.tasks.edit");

$todoitem = new TODOItem();
$todoitem->loadByPrimaryKey($idcommunication);

$ui = new cGuiTableForm("reminder");
$ui->addHeader(i18n("Edit reminder item"));

$ui->addCancel($sess->url("main.php?area=mycontenido_tasks&frame=$frame"));

$ui->setVar("area", "mycontenido_tasks");
$ui->setVar("frame", $frame);
$ui->setVar("action", "todo_save_item");
$ui->setVar("idcommunication", $idcommunication);

$userselect = new cHTMLSelectElement("userassignment");

$userColl = new cApiUserCollection();
foreach ($userColl->getAccessibleUsers(explode(',', $auth->auth['perm'])) as $key => $value) {
    $acusers[$key] = $value["username"] . " (".$value["realname"] . ")";
}
asort($acusers);

$userselect->autoFill($acusers);
$userselect->setDefault($auth->auth["uid"]);

$ui->add(i18n("Assigned to"), $userselect->render());

$subject = new cHTMLTextbox("subject", $todoitem->get("subject"), 60);
$ui->add(i18n("Subject"), $subject->render());

$message = new cHTMLTextarea("message", $todoitem->get("message"));
$ui->add(i18n("Description"), $message->render());

$reminderdue = new cHTMLTextbox("enddate", $todoitem->getProperty("todo", "enddate"), '', '', "enddate");

$ui->add(i18n("End date"),'<table border="0"><tr><td>' . $reminderdue->render() . '</td><td></td></tr></table>');

$notiemail = new cHTMLCheckbox("notiemail", i18n("E-Mail notification"));
$notiemail->setChecked($todoitem->getProperty("todo", "emailnoti"));
$notiemail->setEvent("click","if(this.checked){ document.forms['reminder'].reminderdate.disabled=false; } else { document.forms['reminder'].reminderdate.disabled=true; }");

$ui->add(i18n("Reminder options"), $notiemail->toHTML());

$remindertimestamp = $todoitem->getProperty("todo", "reminderdate");

if ($remindertimestamp != 0) {
    $mydate = date("Y-m-d H:i:s", $remindertimestamp);
} else {
    $mydate = "";
}

$path_to_calender_pic =  $cfg['path']['contenido_fullhtml']. $cfg['path']['images'] . 'calendar.gif';


$reminderdate = new cHTMLTextbox("reminderdate", $mydate, '', '', "reminderdate");

if (!$todoitem->getProperty("todo", "emailnoti")) {
    $reminderdate->setDisabled(true);
}

$ui->add(i18n("Reminder date"), '<table border="0"><tr><td>' . $reminderdate->render() . '</td><td></td></tr></table>');

$todos = new TODOCollection();

$priorityselect = new cHTMLSelectElement("priority");
$priorityselect->autoFill($todos->getPriorityTypes());
$priorityselect->setDefault($todoitem->getProperty("todo", "priority"));
$ui->add(i18n("Priority"), $priorityselect->render());

$statusselect = new cHTMLSelectElement("status");
$statusselect->autoFill($todos->getStatusTypes());
$statusselect->setDefault($todoitem->getProperty("todo", "status"));
$ui->add(i18n("Status"), $statusselect->render());

$progress = new cHTMLTextbox("progress", (int)$todoitem->getProperty("todo", "progress"), 5);
$ui->add(i18n("Progress"), $progress->render()."%");

$cpage->setContent(array($ui));
$cpage->addStyle("datetimepicker/jquery-ui-timepicker-addon.css");
$cpage->addStyle("smoothness/jquery-ui-1.8.20.custom.css");

$cpage->addScript("datetimepicker/jquery-ui-timepicker-addon.js");
$cpage->addScript("jquery/jquery-ui.js");

if(($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
    $cpage->addScript("datetimepicker/jquery-ui-timepicker-".$lang_short.".js");
    $cpage->addScript("jquery/jquery.ui.datepicker-".$lang_short.".js");
}

$cpage->render();
?>
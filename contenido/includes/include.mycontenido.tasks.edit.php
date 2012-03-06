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
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$cpage = new cPage;

$todoitem = new TODOItem;
$todoitem->loadByPrimaryKey($idcommunication);

$ui = new UI_Table_Form("reminder");
$ui->addHeader(i18n("Edit reminder item"));

$ui->addCancel($sess->url("main.php?area=mycontenido_tasks&frame=$frame"));

$ui->setVar("area","mycontenido_tasks");
$ui->setVar("frame", $frame);
$ui->setVar("action", "todo_save_item");
$ui->setVar("idcommunication", $idcommunication);
    
$userselect = new cHTMLSelectElement("userassignment");

$userclass = new User;
foreach ($userclass->getAvailableUsers(explode(',', $auth->auth['perm'])) as $key => $value)
{
	$acusers[$key] = $value["username"]." (".$value["realname"].")";
}

asort($acusers);

$userselect->autoFill($acusers);
$userselect->setDefault($auth->auth["uid"]);

$ui->add(i18n("Assigned to"), $userselect->render());

$subject = new cHTMLTextbox("subject", $todoitem->get("subject"),60);
$ui->add(i18n("Subject"), $subject->render());
    
$message = new cHTMLTextarea("message", $todoitem->get("message"));
$ui->add(i18n("Description"), $message->render());
    
$reminderdue = new cHTMLTextbox("enddate", $todoitem->getProperty("todo", "enddate"), '', '', "enddate");
$duepopup = '<img src="images/calendar.gif" width="16" height="16" border="0" id="end_date" alt="' . i18n("Choose end date") . '">';
$ui->add(i18n("End date"),'<table border="0"><tr><td>'.$reminderdue->render().'</td><td>'.$duepopup.'</td></tr></table>');

$notiemail = new cHTMLCheckbox("notiemail", i18n("E-Mail notification"));
$notiemail->setChecked($todoitem->getProperty("todo", "emailnoti"));
$notiemail->setEvent("click","if(this.checked){ document.forms['reminder'].reminderdate.disabled=false; } else { document.forms['reminder'].reminderdate.disabled=true; }");

$ui->add(i18n("Reminder options"), $notiemail->toHTML());

$remindertimestamp = $todoitem->getProperty("todo", "reminderdate");

if ($remindertimestamp != 0)
{ 
	$mydate = date("Y-m-d H:i:s", $remindertimestamp);
} else {
	$mydate = "";	
} 

$reminderdate = new cHTMLTextbox("reminderdate", $mydate, '', '', "reminderdate");

if (!$todoitem->getProperty("todo", "emailnoti"))
{
	$reminderdate->setDisabled(true);	
}

$datepopup = '<img src="images/calendar.gif" width="16" height="16" border="0" id="reminder_date" alt="' . i18n("Choose end date") . '"></a>';
$ui->add(i18n("Reminder date"),'<table border="0"><tr><td>'.$reminderdate->render().'</td><td>'.$datepopup.'</td></tr></table>');

$calscript = '<script language="JavaScript">'."

        Calendar.setup(
            {
                inputField  : \"enddate\",
                ifFormat    : \"%Y-%m-%d %H:%M\",
                button      : \"end_date\",
                weekNumbers	: true,
                firstDay	:	1,
                showsTime	: true
            }
        );
        
        Calendar.setup(
            {
                inputField  : \"reminderdate\",
                ifFormat    : \"%Y-%m-%d %H:%M\",
                button      : \"reminder_date\",
                weekNumbers	: true,
                firstDay	:	1,
                showsTime	: true
            }
        );
</script>";

$todos = new TODOCollection;

$priorityselect = new cHTMLSelectElement("priority");
$priorityselect->autoFill($todos->getPriorityTypes());
$priorityselect->setDefault($todoitem->getProperty("todo", "priority"));
$ui->add(i18n("Priority"), $priorityselect->render());

$statusselect = new cHTMLSelectElement("status");
$statusselect->autoFill($todos->getStatusTypes());
$statusselect->setDefault($todoitem->getProperty("todo", "status"));
$ui->add(i18n("Status"), $statusselect->render());

$progress = new cHTMLTextbox("progress", (int)$todoitem->getProperty("todo", "progress"),5);
$ui->add(i18n("Progress"), $progress->render()."%");

$cpage->setcontent($ui->render().$calscript);

$cpage->addScript("cal", '<style type="text/css">@import url(scripts/jscalendar/calendar-contenido.css);</style>
	                      <script type="text/javascript" src="scripts/jscalendar/calendar.js"></script>
	                      <script type="text/javascript" src="scripts/jscalendar/lang/calendar-'.substr(strtolower($belang), 0, 2).'.js"></script>
	                      <script type="text/javascript" src="scripts/jscalendar/calendar-setup.js"></script>');

$cpage->render();
?>
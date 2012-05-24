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
 * @version    1.0.3
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

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$cpage = new cPage();

$todoitem = new TODOItem();
$todoitem->loadByPrimaryKey($idcommunication);

$ui = new UI_Table_Form("reminder");
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

if(($lang_short = substr(strtolower($belang), 0, 2)) != "en") {

	$langscripts=  '<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-'.$lang_short.'.js"></script>
	<script type="text/javascript" src="scripts/jquery/jquery.ui.datepicker-'.$lang_short.'.js"></script>';
}

$path_to_calender_pic =  $cfg['path']['contenido_fullhtml']. $cfg['path']['images'] . 'calendar.gif';


$reminderdate = new cHTMLTextbox("reminderdate", $mydate, '', '', "reminderdate");

if (!$todoitem->getProperty("todo", "emailnoti")) {
    $reminderdate->setDisabled(true);
}

$ui->add(i18n("Reminder date"), '<table border="0"><tr><td>' . $reminderdate->render() . '</td><td></td></tr></table>');

$calscript = '<script language="JavaScript">
 $(document).ready(function() {
	$("#reminderdate").datetimepicker({
    		 buttonImage:"'. $path_to_calender_pic.'",
  	        buttonImageOnly: true,
  	        showOn: "both",
  	        dateFormat: "yy-mm-dd",  
    	    onClose: function(dateText, inst) {
    	        var endDateTextBox = $("#enddate");
    	        if (endDateTextBox.val() != "") {
    	            var testStartDate = new Date(dateText);
    	            var testEndDate = new Date(endDateTextBox.val());
    	            if (testStartDate > testEndDate)
    	                endDateTextBox.val(dateText);
    	        }
    	        else {
    	            endDateTextBox.val(dateText);
    	        }
    	    },
    	    onSelect: function (selectedDateTime){
    	        var start = $(this).datetimepicker("getDate");
    	        $("#enddate").datetimepicker("option", "minDate", new Date(start.getTime()));
    	    }
    	});
    	$("#enddate").datetimepicker({
    		 buttonImage: "'. $path_to_calender_pic .'",
   	        buttonImageOnly: true,
   	        showOn: "both",
   	        dateFormat: "yy-mm-dd",
    	    onClose: function(dateText, inst) {
    	        var startDateTextBox = $("#reminderdate");
    	        if (startDateTextBox.val() != "") {
    	            var testStartDate = new Date(startDateTextBox.val());
    	            var testEndDate = new Date(dateText);
    	            if (testStartDate > testEndDate)
    	                startDateTextBox.val(dateText);
    	        }
    	        else {
    	            startDateTextBox.val(dateText);
    	        }
    	    },
    	    onSelect: function (selectedDateTime){
    	        var end = $(this).datetimepicker("getDate");
    	        $("#reminderdate").datetimepicker("option", "maxDate", new Date(end.getTime()) );
    	    }
    	});

});
</script>';

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

$cpage->setcontent($ui->render().$calscript);

$cpage->addScript("cal", '<link rel="stylesheet" type="text/css" href="styles/datetimepicker/jquery-ui-timepicker-addon.css">
    				<link rel="stylesheet" type="text/css" href="styles/smoothness/jquery-ui-1.8.20.custom.css">
    				<script type="text/javascript" src="scripts/jquery/jquery.js"></script>
    				<script type="text/javascript" src="scripts/jquery/jquery-ui.js"></script>
    				<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-addon.js"></script>'
					.$langscripts);

$cpage->render();
?>
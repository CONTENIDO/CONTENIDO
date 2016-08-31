<?php

/**
 * This file contains the backend page for editing tasks.
 *
 * @package Core
 * @subpackage Backend
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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
    $acusers[$key] = $value["username"] . " (" . $value["realname"] . ")";
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

$ui->add(i18n("End date"), $reminderdue->render());

$notiemail = new cHTMLCheckbox("notiemail", 1);
$notiemail->setLabelText(i18n("E-Mail notification"));
$notiemail->setChecked($todoitem->getProperty("todo", "emailnoti"));
$notiemail->setEvent("click", "if (this.checked) { document.forms['reminder'].reminderdate.disabled = false; } else { document.forms['reminder'].reminderdate.disabled = true; }");

$ui->add(i18n("Reminder options"), $notiemail->toHtml());

$remindertimestamp = $todoitem->getProperty("todo", "reminderdate");

if ($remindertimestamp != 0) {
    $mydate = date("Y-m-d H:i:s", $remindertimestamp);
} else {
    $mydate = "";
}

$path_to_calender_pic = cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif';

$reminderdate = new cHTMLTextbox("reminderdate", $mydate, '', '', "reminderdate");

if (!$todoitem->getProperty("todo", "emailnoti")) {
    $reminderdate->setDisabled(true);
}

$ui->add(i18n("Reminder date"), $reminderdate->render());

$todos = new TODOCollection();

$priorityselect = new cHTMLSelectElement("priority");
$priorityselect->autoFill($todos->getPriorityTypes());
$priorityselect->setDefault($todoitem->getProperty("todo", "priority"));
$ui->add(i18n("Priority"), $priorityselect->render());

$statusselect = new cHTMLSelectElement("status");
$statusselect->autoFill($todos->getStatusTypes());
$statusselect->setDefault($todoitem->getProperty("todo", "status"));
$ui->add(i18n("Status"), $statusselect->render());

$progress = new cHTMLTextbox("progress", (int) $todoitem->getProperty("todo", "progress"), 5);
$ui->add(i18n("Progress"), $progress->render() . "%");

$calscript = '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $("#reminderdate").datetimepicker({
            buttonImage:"' . $path_to_calender_pic . '",
                buttonImageOnly: true,
                showOn: "both",
                dateFormat: "yy-mm-dd",
                onClose: function(dateText, inst) {
                    var endDateTextBox = $("#enddate");
                    if (endDateTextBox.val() != "") {
                        var testStartDate = new Date(dateText);
                        var testEndDate = new Date(endDateTextBox.val());
                        if (testStartDate > testEndDate) {
                            endDateTextBox.val(dateText);
                        }
                    } else {
                        endDateTextBox.val(dateText);
                    }
                },
                onSelect: function(selectedDateTime) {
                    var start = $(this).datetimepicker("getDate");
                    $("#enddate").datetimepicker("option", "minDate", new Date(start.getTime()));
                }
        });
        $("#enddate").datetimepicker({
            buttonImage: "' . $path_to_calender_pic . '",
            buttonImageOnly: true,
            showOn: "both",
            dateFormat: "yy-mm-dd",
            onClose: function(dateText, inst) {
                var startDateTextBox = $("#reminderdate");
                if (startDateTextBox.val() != "") {
                    var testStartDate = new Date(startDateTextBox.val());
                    var testEndDate = new Date(dateText);
                    if (testStartDate > testEndDate) {
                        startDateTextBox.val(dateText);
                    }
                } else {
                    startDateTextBox.val(dateText);
                }
            },
            onSelect: function(selectedDateTime) {
                var end = $(this).datetimepicker("getDate");
                $("#reminderdate").datetimepicker("option", "maxDate", new Date(end.getTime()));
            }
        });
    });
})(Con, Con.$);
</script>';

$cpage->addScript($calscript);
$cpage->setContent(array(
    $ui
));
$cpage->addStyle("jquery/plugins/timepicker.css");
// $cpage->addStyle("jquery/jquery-ui.css");

$cpage->addScript("jquery/plugins/timepicker.js");
// $cpage->addScript("jquery/jquery-ui.js");

if (($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
    $cpage->addScript("jquery/plugins/timepicker-" . $lang_short . ".js");
    $cpage->addScript("jquery/plugins/datepicker-" . $lang_short . ".js");
}

$cpage->render();

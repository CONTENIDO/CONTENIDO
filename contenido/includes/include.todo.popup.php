<?php

/**
 * This file contains the backend page for the to-do popup.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oPage = new cGuiPage("todo.popup");

if ($action == 'todo_save_item') {
    $todo = new TODOCollection();

    $subject = stripslashes($subject);
    $message = stripslashes($message);
    $notiemail = !empty($notiemail) ? '1' : '';
    $notibackend = !empty($notibackend) ? '1' : '';

    if (!empty($userassignment) && is_array($userassignment)) {
        foreach ($userassignment as $key => $value) {
            $item = $todo->createItem($itemtype, $itemid, strtotime($reminderdate), $subject, $message, $notiemail, $notibackend, $auth->auth['uid']);
            $item->set('recipient', $value);
            $item->setProperty('todo', 'enddate', $enddate);
            $item->store();
        }
    }

    $oPage->addScript('<script>window.close();</script>');
} else {
    $ui = new cGuiTableForm('reminder');
    $ui->addHeader(i18n('Add TODO item'));

    $ui->setVar('area', $area);
    $ui->setVar('frame', $frame);
    $ui->setVar('action', 'todo_save_item');
    $ui->setVar('itemtype', $itemtype);
    $ui->setVar('itemid', $itemid);

    $subject = new cHTMLTextbox('subject', stripslashes(urldecode($subject)), 60);
    $ui->add(i18n('Subject'), $subject->render());

    $message = new cHTMLTextarea('message', stripslashes(urldecode($message)));
    $ui->add(i18n('Description'), $message->render());

    $reminderdate = new cHTMLTextbox('reminderdate', '', '', '', 'reminderdate');

    $ui->add(i18n('Reminder date'), $reminderdate->render());

    $reminderdue = new cHTMLTextbox('enddate', '', '', '', 'enddate');
    $ui->add(i18n('End date'),$reminderdue->render());
    $notiemail = new cHTMLCheckbox('notiemail', '1');
    $notiemail->setLabelText(i18n('E-mail notification'));

    $langScripts = [];
    if (($langCodeShort = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != 'en') {
        $langScripts[] = 'jquery/plugins/timepicker-' . $langCodeShort . '.js';
        $langScripts[] = 'jquery/plugins/datepicker-' . $langCodeShort . '.js';
    }

    $calendarButtonImage = cRegistry::getBackendUrl(). $cfg['path']['images'] . 'calendar.gif';

    $ui->add(i18n('Reminder options'), $notiemail->toHtml());
    $calScript = '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $("#reminderdate").datetimepicker({
            buttonImage:"'. $calendarButtonImage.'",
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
            buttonImage: "'. $calendarButtonImage .'",
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

    $userSelect = new cHTMLSelectElement("userassignment[]");

    $userColl = new cApiUserCollection();
    $assignedUsers = [];
    foreach ($userColl->getAccessibleUsers(explode(',', $auth->auth['perm']), true) as $key => $value) {
        $assignedUsers[$key] = $value["username"] . " (" . $value["realname"] . ")";
    }

    asort($assignedUsers);

    $userSelect->autoFill($assignedUsers);
    $userSelect->setDefault($auth->auth["uid"]);
    $userSelect->setMultiselect();
    $userSelect->setSize(5);

    $ui->add(i18n("Assigned to"), $userSelect->render());

    $oPage->addStyle('jquery/plugins/timepicker.css');
    $oPage->addScript('jquery/plugins/timepicker.js');
    foreach ($langScripts as $langScript) {
        $oPage->addScript($langScript);
    }
    $oPage->addScript($calScript);
    $oPage->setContent($ui);
}

$oPage->render();

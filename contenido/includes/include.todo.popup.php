<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Popup for todo
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
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$cpage = new cGuiPage("todo.popup");

if ($action == 'todo_save_item') {
    $todo = new TODOCollection();

    $subject = stripslashes($subject);
    $message = stripslashes($message);

    if (is_array($userassignment)) {
        foreach ($userassignment as $key => $value) {
            $item = $todo->createItem($itemtype, $itemid, strtotime($reminderdate), $subject, $message, $notiemail, $notibackend, $auth->auth['uid']);
            $item->set('recipient', $value);
            $item->setProperty('todo', 'enddate', $enddate);
            $item->store();
        }
    }

    $cpage->addScript('<script>window.close();</script>');
} else {
    $ui = new cGuiTableForm('reminder');
    $ui->addHeader(i18n('Add TODO item'));

    $ui->setVar('area', $area);
    $ui->setVar('frame', $frame);
    $ui->setVar('action', 'todo_save_item');
    $ui->setVar('itemtype', $itemtype);
    $ui->setVar('itemid', $itemid);

    $subject = new cHTMLTextbox('subject', htmldecode(stripslashes(urldecode($subject))),60);
    $ui->add(i18n('Subject'), $subject->render());

    $message = new cHTMLTextarea('message', htmldecode(stripslashes(urldecode($message))));
    $ui->add(i18n('Description'), $message->render());

    $reminderdate = new cHTMLTextbox('reminderdate', '', '', '', 'reminderdate');

    $ui->add(i18n('Reminder date'), $reminderdate->render());

    $reminderdue = new cHTMLTextbox('enddate', '', '', '', 'enddate');
    $ui->add(i18n('End date'),$reminderdue->render());
    $notiemail = new cHTMLCheckbox('notiemail', i18n('E-mail notification'));
    $langscripts = array();

    if(($lang_short = substr(strtolower($belang), 0, 2)) != 'en') {
        $langscripts[] = 'datetimepicker/jquery-ui-timepicker-' . $lang_short . '.js';
        $langscripts[] = 'jquery/jquery.ui.datepicker-' . $lang_short . '.js';
    }

    $path_to_calender_pic =   cRegistry::getBackendUrl(). $cfg['path']['images'] . 'calendar.gif';


    $ui->add(i18n('Reminder options'), $notiemail->toHTML());
    $calscript = '
<script type="text/javascript">
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
                if (testStartDate > testEndDate) {
                    endDateTextBox.val(dateText);
                }
            } else {
                endDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime) {
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
                if (testStartDate > testEndDate) {
                    startDateTextBox.val(dateText);
                }
            } else {
                startDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime) {
            var end = $(this).datetimepicker("getDate");
            $("#reminderdate").datetimepicker("option", "maxDate", new Date(end.getTime()));
        }
    });
});
</script>';

    $userselect = new cHTMLSelectElement("userassignment[]");

    $userColl = new cApiUserCollection();
    foreach ($userColl->getAccessibleUsers(explode(',', $auth->auth['perm']), true) as $key => $value) {
       $acusers[$key] = $value["username"] . " (" . $value["realname"] . ")";
    }

    asort($acusers);

    $userselect->autoFill($acusers);
    $userselect->setDefault($auth->auth["uid"]);
    $userselect->setMultiselect();
    $userselect->setSize(5);

    $ui->add(i18n("Assigned to"), $userselect->render());

    $cpage->addStyle('datetimepicker/jquery-ui-timepicker-addon.css');
    $cpage->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
    $cpage->addScript('datetimepicker/jquery-ui-timepicker-addon.js');
    foreach ($langscripts as $langscript) {
        $cpage->addScript($langscript);
    }
    $cpage->addScript($calscript);
    $cpage->setcontent($ui);
}
$cpage->render();

?>
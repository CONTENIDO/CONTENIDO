<?php

/**
 * This file contains the backend page for managing tasks.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cApiUser $currentuser
 * @var array $cfg
 * @var int $client
 * @var int $frame
 * @var string $area
 *
 * @var int $idcommunication
 * @var int $progress
 * @var string $subject
 * @var string $message
 * @var string $userassignment
 * @var string $notiemail
 * @var string $status
 * @var string $priority
 * @var string $enddate
 */

$oPage = new cGuiPage("mycontenido.tasks", "", "1");

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$action = $action ?? '';

if (!isset($sortmode)) {
    $sortmode = $currentuser->getUserProperty("system", "tasks_sortmode");
    $sortby = $currentuser->getUserProperty("system", "tasks_sortby");
}

$dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

if (isset($_REQUEST["listsubmit"])) {
    if (isset($c_restrict)) {
        $c_restrict = true;
        $currentuser->setUserProperty("mycontenido", "hidedonetasks", "true");
    } else {
        $c_restrict = false;
        $currentuser->setUserProperty("mycontenido", "hidedonetasks", "false");
    }
} else {
    if ($currentuser->getUserProperty("mycontenido", "hidedonetasks") == "true") {
        $c_restrict = true;
    } else {
        $c_restrict = false;
    }
}

if ($action == "todo_save_item") {
    $subject = stripslashes($subject);
    $message = stripslashes($message);

    $todoItem = new TODOItem();
    $todoItem->loadByPrimaryKey($idcommunication);

    $todoItem->set("subject", $subject);
    $todoItem->set("message", $message);
    $todoItem->set("recipient", $userassignment);

    if (isset($reminderdate)) {
        $todoItem->setProperty("todo", "reminderdate", strtotime($reminderdate));
    }

    if (isset($notibackend)) {
        $todoItem->setProperty("todo", "backendnoti", $notibackend);
    }

    if (isset($notiemail)) {
        $todoItem->setProperty("todo", "emailnoti", $notiemail);
    }

    $todoItem->setProperty("todo", "status", $status);

    // Progress can be between 0 - 100
    $progress = min(max(0, $progress), 100);

    $todoItem->setProperty("todo", "priority", $priority);
    $todoItem->setProperty("todo", "progress", $progress);

    $todoItem->setProperty("todo", "enddate", $enddate);

    $todoItem->store();
}

$todoItems = new TODOCollection();
if ($action == "mycontenido_tasks_delete") {
    $todoItems->delete($idcommunication);
}
$recipient = $auth->auth["uid"];
$todoItems->select("recipient = '" . $todoItems->escape($recipient) . "' AND idclient = " . (int) $client);

$editLink = new cHTMLLink();
$editLink->setCLink("mycontenido_tasks_edit", 4, "");
$editLink->setCustom("sortmode", $sortmode);
$editLink->setCustom("sortby", $sortby);

$deleteLink = new cHTMLLink();
$deleteLink->setCLink("mycontenido_tasks", 4, "mycontenido_tasks_delete");
$deleteLink->setCustom("sortby", $sortby);
$deleteLink->setCustom("sortmode", $sortmode);

$list = new cGuiScrollListMyContenidoTasks($todoItems, $editLink, $dateformat);

$list->setHeader(
    '&nbsp;', i18n("Subject"), i18n("Created"), i18n("End Date"), i18n("Status"),
    i18n("Priority"), sprintf(i18n("%% complete")), i18n("Due in"), i18n("Actions")
);

$listCount = 0;

while ($todo = $todoItems->next()) {
    if ((($todo->getProperty("todo", "status") != "done") && $c_restrict) || (empty($c_restrict))) {

        $idcommunication = cSecurity::toInteger($todo->get("idcommunication"));
        $subject = $todo->get("subject");
        $created = $todo->get("created");

        $reminder = $todo->getProperty("todo", "enddate");
        $status = $todo->getProperty("todo", "status");
        $priority = $todo->getProperty("todo", "priority");
        $complete = $todo->getProperty("todo", "progress");

        if (trim($subject) == "") {
            $subject = i18n("Unnamed item");
        }

        if (trim($reminder) == "") {
            $reminder = i18n("No end date set");
        } else {
            $reminder = date($dateformat, strtotime($reminder));
        }

        if (trim($status) == "") {
            $status = i18n("No status set");
        }

        $image = cHTMLImage::img("images/but_todo.gif", i18n("Edit item"));

        $img = cHTMLImage::img("images/delete.gif", i18n("Delete item"));
        $deleteLink->setClass('con_img_button');
        $deleteLink->setCustom("idcommunication", $idcommunication);
        $deleteLink->setContent($img);

        $img = cHTMLImage::img("images/but_art_conf2.gif", i18n("Edit item"));
        $editLink->setClass('con_img_button mgr5');
        $editLink->setCustom('idcommunication', $idcommunication);
        $editLink->setContent($img);

        $actions = $editLink->render() . $deleteLink->render();

        if ($todo->getProperty("todo", "enddate") != "") {
            $duein = round((time() - strtotime($todo->getProperty("todo", "enddate"))) / 86400, 2);
        } else {
            $duein = "";
        }

        switch ($priority) {
            case "medium":
                $p = 1;
                break;
            case "high":
                $p = 2;
                break;
            case "immediately":
                $p = 3;
                break;
            default:
                $p = 0;
                break;
        }

        $list->setData($listCount, $image, $subject, $created, $reminder, $status, $p, $complete, $duein, $actions);
        $list->setHiddenData($listCount, $idcommunication, $idcommunication);

        $listCount++;
    }
}

$form = new cGuiTableForm("restrict");
$form->setTableID("todoList");
$form->setHeader(i18n("Restrict display"));
$form->setVar("listsubmit", "true");

$form->unsetActionButton("submit");
$form->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

$form->setVar("area", $area);
$form->setVar("frame", $frame);

$restrict = new cHTMLCheckbox("c_restrict", "true");
$restrict->setLabelText(i18n("Hide done tasks"));

if ($c_restrict) {
    $restrict->setChecked(true);
}

$submit = new cHTMLButton("submit");
$submit->setMode("image");
$submit->setImageSource("images/submit.gif");

$form->add(i18n("Options"), $restrict->render());

if ($listCount == 0) {
    $oPage->displayInfo(i18n("No tasks found"));
    $oPage->setContent([$form]);
} else {
    $sortby = $sortby ?? 1;
    $sortmode = $sortmode ?? 'ASC';

    $list->setSortable(1, true);
    $list->setSortable(2, true);
    $list->setSortable(3, true);
    $list->setSortable(4, true);
    $list->setSortable(5, true);
    $list->setSortable(6, true);
    $list->setSortable(7, true);
    $list->sort(cSecurity::toInteger($sortby), $sortmode);

    $oPage->setContent([$form, $list]);
}
$oPage->render();

$currentuser->setUserProperty("system", "tasks_sortby", $sortby);
$currentuser->setUserProperty("system", "tasks_sortmode", $sortmode);

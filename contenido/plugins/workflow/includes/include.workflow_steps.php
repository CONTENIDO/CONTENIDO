<?php
/**
 * This file contains the workflow allocation management.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author unkown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $adduser, $wfactions,
       $wftaskselect, $wfstepname, $wfstepdescription, $wfemailnoti, $wfescalnoti;

plugin_include('workflow', 'includes/functions.workflow.php');
cInclude("includes", "functions.encoding.php");

$page = new cGuiPage("workflow_steps", "workflow");
$page->addStyle('workflow.css');

$requestIdWorkflowItem = cSecurity::toInteger($_REQUEST['idworkflowitem'] ?? '0');
$requestIdWorkflow = cSecurity::toInteger($_GET['idworkflow'] ?? '0');
$requestPosition = cSecurity::toInteger($_GET['position'] ?? '0');
$requestIdUserSequence = cSecurity::toInteger($_GET['idusersequence'] ?? '0');

$workflowActions = new WorkflowActions();

$availableWorkflowActions = $workflowActions->getAvailableWorkflowActions();

$sCurrentEncoding = cRegistry::getEncoding();

if (conHtmlentities($adduser, ENT_COMPAT, $sCurrentEncoding) == i18n("Add User", "workflow")) {
    $action = "workflow_create_user";
}

// Function: Move step up
if ($action == "workflow_step_up") {
    $workflowItems = new WorkflowItems();
    $workflowItems->swap($requestIdWorkflow, $requestPosition, $requestPosition - 1);
}

// Function: Move step down
if ($action == "workflow_step_down") {
    $workflowItems = new WorkflowItems();
    $workflowItems->swap($requestIdWorkflow, $requestPosition, $requestPosition + 1);
}

// Function: Move user up
if ($action == "workflow_user_up") {
    $workflowItems = new WorkflowUserSequences();
    $workflowItems->swap($requestIdWorkflowItem, $requestPosition, $requestPosition - 1);
}

// Function: Move step down
if ($action == "workflow_user_down") {
    $workflowItems = new WorkflowUserSequences();
    $workflowItems->swap($requestIdWorkflowItem, $requestPosition, $requestPosition + 1);
}

// Function: Create new step
if ($action == "workflow_create_step") {
    $workflowItems = new WorkflowItems();
    $item = $workflowItems->create($requestIdWorkflow);
    $item->set("name", i18n("New Workflow Step", "workflow"));
    $item->store();
    $requestIdWorkflowItem = $item->get("idworkflowitem");
}

// Function: Delete step
if ($action == "workflow_step_delete") {
    $workflowItems = new WorkflowItems();
    $workflowItems->delete($requestIdWorkflowItem);
}

// Function: Add user
if ($action == "workflow_create_user") {
    $workflowUsers = new WorkflowUserSequences();
    $new = $workflowUsers->create($requestIdWorkflowItem);
}

// Function: Remove user
if ($action == "workflow_user_delete") {
    $workflowUsers = new WorkflowUserSequences();
    $workflowUsers->delete($requestIdUserSequence);
}

// Function: Save step
if ($action == "workflow_save_step" || $action == "workflow_create_user") {
    $workflowActions = new WorkflowActions();

    foreach ($availableWorkflowActions as $key => $value) {
        if (isset($wfactions[$key]) && $wfactions[$key] == 1) {
            $workflowActions->set($requestIdWorkflowItem, $key);
        } else {
            $workflowActions->remove($requestIdWorkflowItem, $key);
        }
    }

    $workflowItem = new WorkflowItem();
    $workflowItem->loadByPrimaryKey($requestIdWorkflowItem);
    $workflowItem->setField('idtask', $wftaskselect);
    $workflowItem->setField('name', str_replace('\\','',$wfstepname));
    $workflowItem->setField('description', str_replace('\\','',$wfstepdescription));
    $workflowItem->store();

    $userSequences = new WorkflowUserSequences();
    $userSequences->select("idworkflowitem = '$requestIdWorkflowItem'");

    while (($userSequence = $userSequences->next()) !== false) {
        $wftime = "time" . $userSequence->get("idusersequence");
        $wfuser = "user" . $userSequence->get("idusersequence");

        $wftimelimit = "wftimelimit" . $userSequence->get("idusersequence");
        $userSequence->set("timeunit", $$wftime);
        $userSequence->set("iduser", $$wfuser);
        $userSequence->set("timelimit", $$wftimelimit);
        $userSequence->set("emailnoti", $wfemailnoti[$userSequence->get("idusersequence")]);
        $userSequence->set("escalationnoti", $wfescalnoti[$userSequence->get("idusersequence")]);
        $userSequence->store();
    }
}

/**
 * @param $listid
 * @param $default
 *
 * @return string
 * @throws cInvalidArgumentException
 */
function getTimeUnitSelector($listid, $default) {
    $cfg = cRegistry::getConfig();
    $timeunits = [];
    $timeunits['Seconds'] = i18n("Seconds", "workflow");
    $timeunits['Minutes'] = i18n("Minutes", "workflow");
    $timeunits['Hours'] = i18n("Hours", "workflow");
    $timeunits['Days'] = i18n("Days", "workflow");
    $timeunits['Weeks'] = i18n("Weeks", "workflow");
    $timeunits['Months'] = i18n("Months", "workflow");
    $timeunits['Years'] = i18n("Years", "workflow");

    $tpl2 = new cTemplate();
    $tpl2->set('s', 'NAME', 'time' . $listid);
    $tpl2->set('s', 'CLASS', 'text_small');
    $tpl2->set('s', 'OPTIONS', 'size=1');

    foreach ($timeunits as $key => $value) {
        $tpl2->set('d', 'VALUE', $key);
        $tpl2->set('d', 'CAPTION', $value);

        if ($default == $key) {
            $tpl2->set('d', 'SELECTED', 'SELECTED');
        } else {
            $tpl2->set('d', 'SELECTED', '');
        }

        $tpl2->next();
    }

    return $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
}

/**
 * @param int $idWorkflow
 * @param int $idWorkflowItem
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function getWorkflowList($idWorkflow, $idWorkflowItem) {
    $cfg = cRegistry::getConfig();
    $backendUrl = cRegistry::getBackendUrl();

    $ui = new cGuiMenu();
    $workflowItems = new WorkflowItems();

    $workflowItems->select("idworkflow = $idWorkflow", "", "position ASC");

    while (($workflowItem = $workflowItems->next()) !== false) {
        $pos = $workflowItem->get("position");
        $name = preg_replace("/\"/","",($workflowItem->get("name")));
        $id = cSecurity::toInteger($workflowItem->get("idworkflowitem"));

        $edititem = new cHTMLLink();
        $edititem->setClass("show_item");
        $edititem->setCLink("workflow_steps", 4, "workflow_step_edit");
        $edititem->setCustom("idworkflowitem", $id);
        $edititem->setCustom("idworkflow", $idWorkflow);

        $moveup = new cHTMLLink();
        $moveup->setCLink("workflow_steps", 4, "workflow_step_up");
        $moveup->setCustom("idworkflowitem", $id);
        $moveup->setCustom("idworkflow", $idWorkflow);
        $moveup->setCustom("position", $pos);
        $moveup->setAlt(i18n("Move step up", "workflow"));
        $moveup->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/no_verschieben.gif" . '">');

        $movedown = new cHTMLLink();
        $movedown->setCLink("workflow_steps", 4, "workflow_step_down");
        $movedown->setCustom("idworkflowitem", $id);
        $movedown->setCustom("idworkflow", $idWorkflow);
        $movedown->setCustom("position", $pos);
        $movedown->setAlt(i18n("Move step down", "workflow"));
        $movedown->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/nu_verschieben.gif" . '">');

        $deletestep = new cHTMLLink();
        $deletestep->setCLink("workflow_steps", 4, "workflow_step_delete");
        $deletestep->setCustom("idworkflowitem", $id);
        $deletestep->setCustom("idworkflow", $idWorkflow);
        $deletestep->setCustom("position", $pos);
        $deletestep->setAlt(i18n("Delete step", "workflow"));
        $deletestep->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_step_delete.gif" . '">');

        $ui->setTitle($id, "$pos. $name");
        $ui->setLink($id, $edititem);

        if ($pos > 1) {
            $ui->setActions($id, "moveup", $moveup->render());
        } else {
            $ui->setActions($id, "moveup", '<img src="images/spacer.gif" width="15" height="1">');
        }

        if ($pos < $workflowItems->count()) {
            $ui->setActions($id, "movedown", $movedown->render());
        } else {
            $ui->setActions($id, "movedown", '<img src="images/spacer.gif" width="15" height="1">');
        }

        $ui->setActions($id, "delete", $deletestep->render());

        if ($idWorkflowItem === $id) {
            $ui->setMarked($id);
        }
    }

    $content = $ui->render(false);

    return ($content);
}

/**
 * @param int $idWorkflow
 * @return string
 * @throws cInvalidArgumentException
 */
function createNewWorkflow($idWorkflow) {
    $cfg = cRegistry::getConfig();
    $backendUrl = cRegistry::getBackendUrl();

    $ui = new cGuiMenu('new_workflow_menu_list');
    $rowmark = false;

    $createstep = new cHTMLLink();
    $createstep->setCLink("workflow_steps", 4, "workflow_create_step");
    $createstep->setCustom("idworkflow", $idWorkflow);

    // ui->setLink("spacer", NULL);
    $ui->setTitle("create", i18n("Create new step", "workflow"));
    $ui->setImage("create", $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_step_new.gif");
    $ui->setLink("create", $createstep);
    $ui->setRowmark($rowmark);

    return $ui->render(false);
}

/**
 * @param int $idWorkflow
 * @param int $idWorkflowItem
 *
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function editWorkflowStep($idWorkflow, $idWorkflowItem) {
    global $availableWorkflowActions;

    $workflowItem = new WorkflowItem();

    if ($workflowItem->loadByPrimaryKey($idWorkflowItem) == false) {
        return "&nbsp;";
    }

    $area = cRegistry::getArea();
    $frame = cRegistry::getFrame();

    $workflowActions = new WorkflowActions();

    $stepname = str_replace('\\','',conHtmlSpecialChars($workflowItem->get("name")));
    $stepdescription = str_replace('\\','',conHtmlSpecialChars($workflowItem->get("description")));
    $id = $workflowItem->get("idworkflowitem");
    $task = $workflowItem->get("idtask");

    $form = new cGuiTableForm("workflow_edit");

    $form->setVar("area", $area);
    $form->setVar("action", "workflow_save_step");
    $form->setVar("idworkflow", $idWorkflow);
    $form->setVar("idworkflowitem", $idWorkflowItem);
    $form->setVar("frame", $frame);

    $form->addHeader(i18n("Edit workflow step", "workflow"));
    $oTxtStep = new cHTMLTextbox("wfstepname", $stepname, 40, 255);
    $form->add(i18n("Step name", "workflow"), $oTxtStep->render());
    $oTxtStepDesc = new cHTMLTextarea("wfstepdescription", $stepdescription, 60, 10);
    $form->add(i18n("Step description", "workflow"), $oTxtStepDesc->render());

    $actions = '';

    foreach ($availableWorkflowActions as $key => $value) {
        $oCheckbox = new cHTMLCheckbox("wfactions[" . $key . "]", "1", "wfactions[" . $key . "]1", $workflowActions->get($id, $key));
        $oCheckbox->setLabelText($value);
        $actions .= $oCheckbox->toHtml();
    }

    $form->add(i18n("Actions", "workflow"), $actions);
    $form->add(i18n("Assigned users", "workflow"), getWorkflowUsers($idWorkflow, $idWorkflowItem));

    return $form->render(true);
}

/**
 * @param int $idWorkflow
 * @param int $idWorkflowItem
 *
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function getWorkflowUsers($idWorkflow, $idWorkflowItem) {
    $cfg = cRegistry::getConfig();
    $backendUrl = cRegistry::getBackendUrl();

    $ui = new cGuiMenu('workflow_users_menu_list');
    $ui->setRowmark(false);
    $workflowUsers = new WorkflowUserSequences();

    $workflowUsers->select("idworkflowitem = '$idWorkflowItem'", "", "position ASC");

    while (($workflowItem = $workflowUsers->next()) !== false) {
        $pos = $workflowItem->get("position");
        $iduser = $workflowItem->get("iduser");
        $timelimit = $workflowItem->get("timelimit");
        $timeunit = $workflowItem->get("timeunit");
        $email = $workflowItem->get("emailnoti");
        $escalation = $workflowItem->get("escalationnoti");
        $timeunit = $workflowItem->get("timeunit");
        $id = $workflowItem->get("idusersequence");

        $moveup = new cHTMLLink();
        $moveup->setCLink("workflow_steps", 4, "workflow_user_up");
        $moveup->setCustom("idworkflowitem", $idWorkflowItem);
        $moveup->setCustom("idworkflow", $idWorkflow);
        $moveup->setCustom("position", $pos);
        $moveup->setAlt(i18n("Move user up", "workflow"));
        $moveup->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/no_verschieben.gif" . '">');

        $movedown = new cHTMLLink();
        $movedown->setCLink("workflow_steps", 4, "workflow_user_down");
        $movedown->setCustom("idworkflowitem", $idWorkflowItem);
        $movedown->setCustom("idworkflow", $idWorkflow);
        $movedown->setCustom("position", $pos);
        $movedown->setAlt(i18n("Move user down", "workflow"));
        $movedown->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/nu_verschieben.gif" . '">');

        $deletestep = new cHTMLLink();
        $deletestep->setCLink("workflow_steps", 4, "workflow_user_delete");
        $deletestep->setCustom("idworkflowitem", $idWorkflowItem);
        $deletestep->setCustom("idworkflow", $idWorkflow);
        $deletestep->setCustom("position", $pos);
        $deletestep->setCustom("idusersequence", $id);
        $deletestep->setAlt(i18n("Delete user", "workflow"));
        $deletestep->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_step_delete.gif" . '">');

        $title = "$pos. " . getUsers($id, $iduser);

        $oTxtTime = new cHTMLTextbox("wftimelimit" . $id, $timelimit, 3, 6);
        $title .= $oTxtTime->render();
        $title .= getTimeUnitSelector($id, $timeunit);
        $altmail = i18n("Notify this user via E-Mail", "workflow");
        $altnoti = i18n("Escalate to this user via E-Mail", "workflow");

        $oCheckbox = new cHTMLCheckbox("wfemailnoti[" . $id . "]", "1", "wfemailnoti[" . $id . "]1", $email);
        $title .= $oCheckbox->toHtml(false) . '<label for="wfemailnoti[' . $id . ']1"><img alt="' . $altmail . '" title="' . $altmail . '" src="' . $backendUrl . $cfg["path"]["plugins"] . 'workflow/images/workflow_email_noti.gif"></label>';

        $oCheckbox = new cHTMLCheckbox("wfescalnoti[" . $id . "]", "1", "wfescalnoti[" . $id . "]1", $escalation);
        $title .= $oCheckbox->toHtml(false) . '<label for="wfescalnoti[' . $id . ']1"><img alt="' . $altnoti . '" title="' . $altnoti . '" src="' . $backendUrl . $cfg["path"]["plugins"] . 'workflow/images/workflow_escal_noti.gif"></label>';

        $ui->setTitle($id, $title);
        $ui->setLink($id, NULL);

        if ($pos > 1) {
            $ui->setActions($id, "moveup", $moveup->render());
        } else {
            $ui->setActions($id, "moveup", '<img src="images/spacer.gif" width="15" height="1">');
        }

        if ($pos < $workflowUsers->count()) {
            $ui->setActions($id, "movedown", $movedown->render());
        } else {
            $ui->setActions($id, "movedown", '<img src="images/spacer.gif" width="15" height="1">');
        }

        $ui->setActions($id, "delete", $deletestep->render());

        $ui->setImage($id, $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_user.gif");
    }

    $createstep = new cHTMLLink();
    $createstep->setCLink("workflow_steps", 4, "workflow_create_user");
    $createstep->setCustom("idworkflow", $idWorkflow);
    $createstep->setCustom("idworkflowitem", $idWorkflowItem);

    $ui->setLink("spacer", NULL);

    $ui->setTitle("create", '<input class="text_medium" type="submit" name="adduser" value="' . i18n("Add User", "workflow") . '">');
    $ui->setLink("create", NULL);
    return $ui->render(false);
}

$page->set('s', 'NEW', createNewWorkflow($requestIdWorkflow));
$page->set('s', 'STEPS', getWorkflowList($requestIdWorkflow, $requestIdWorkflowItem));
$page->set('s', 'EDITSTEP', editWorkflowStep($requestIdWorkflow, $requestIdWorkflowItem));
$page->set('s', 'WARNING', i18n('Warning: Changes will reset active Workflows', 'workflow'));

$page->render();

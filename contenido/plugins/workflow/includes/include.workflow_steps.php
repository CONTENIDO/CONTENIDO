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

global $adduser, $idworkflow, $idworkflowitem, $idusersequence, $wfactions,
       $wftaskselect, $wfstepname, $wfstepdescription, $wfemailnoti, $wfescalnoti;

plugin_include('workflow', 'classes/class.workflow.php');
plugin_include('workflow', 'includes/functions.workflow.php');
cInclude("includes", "functions.encoding.php");

$page = new cGuiPage("workflow_steps", "workflow");
$page->addStyle('workflow.css');

$iIdMarked = (isset($_REQUEST['idworkflowitem'])) ? cSecurity::toInteger($_REQUEST['idworkflowitem']) : 0;

$workflowActions = new WorkflowActions();

$availableWorkflowActions = $workflowActions->getAvailableWorkflowActions();

$sCurrentEncoding = cRegistry::getEncoding();

if (conHtmlentities($adduser, ENT_COMPAT, $sCurrentEncoding) == i18n("Add User", "workflow")) {
    $action = "workflow_create_user";
}

// Function: Move step up
if ($action == "workflow_step_up") {
    $workflowitems = new WorkflowItems();
    $workflowitems->swap($idworkflow, $position, $position - 1);
}

// Function: Move step down
if ($action == "workflow_step_down") {
    $workflowitems = new WorkflowItems();
    $workflowitems->swap($idworkflow, $position, $position + 1);
}

// Function: Move user up
if ($action == "workflow_user_up") {
    $workflowitems = new WorkflowUserSequences();
    $workflowitems->swap($idworkflowitem, $position, $position - 1);
}

// Function: Move step down
if ($action == "workflow_user_down") {
    $workflowitems = new WorkflowUserSequences();
    $workflowitems->swap($idworkflowitem, $position, $position + 1);
}

// Function: Create new step
if ($action == "workflow_create_step") {
    $workflowitems = new WorkflowItems();
    $item = $workflowitems->create($idworkflow);
    $item->set("name", i18n("New Workflow Step", "workflow"));
    $item->store();
    $idworkflowitem = $item->get("idworkflowitem");
}

// Function: Delete step
if ($action == "workflow_step_delete") {
    $workflowitems = new WorkflowItems();
    $workflowitems->delete($idworkflowitem);
}

// Function: Add user
if ($action == "workflow_create_user") {
    $workflowusers = new WorkflowUserSequences();
    $new = $workflowusers->create($idworkflowitem);
}

// Function: Remove user
if ($action == "workflow_user_delete") {
    $workflowusers = new WorkflowUserSequences();
    $workflowusers->delete($idusersequence);
}

// Function: Save step
if ($action == "workflow_save_step" || $action == "workflow_create_user") {
    $workflowactions = new WorkflowActions();

    foreach ($availableWorkflowActions as $key => $value) {
        if ($wfactions[$key] == 1) {
            $workflowactions->set($idworkflowitem, $key);
        } else {
            $workflowactions->remove($idworkflowitem, $key);
        }
    }

    $workflowitem = new WorkflowItem();
    $workflowitem->loadByPrimaryKey($idworkflowitem);
    $workflowitem->setField('idtask', $wftaskselect);
    $workflowitem->setField('name', str_replace('\\','',$wfstepname));
    $workflowitem->setField('description', str_replace('\\','',$wfstepdescription));
    $workflowitem->store();

    $usersequences = new WorkflowUserSequences();
    $usersequences->select("idworkflowitem = '$idworkflowitem'");

    while (($usersequence = $usersequences->next()) !== false) {
        $wftime = "time" . $usersequence->get("idusersequence");
        $wfuser = "user" . $usersequence->get("idusersequence");

        $wftimelimit = "wftimelimit" . $usersequence->get("idusersequence");
        $usersequence->set("timeunit", $$wftime);
        $usersequence->set("iduser", $$wfuser);
        $usersequence->set("timelimit", $$wftimelimit);
        $usersequence->set("emailnoti", $wfemailnoti[$usersequence->get("idusersequence")]);
        $usersequence->set("escalationnoti", $wfescalnoti[$usersequence->get("idusersequence")]);
        $usersequence->store();
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
    global $idclient, $cfg, $auth;

    $timeunits = array();
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
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function getWorkflowList() {
    global $idworkflow, $iIdMarked, $cfg;

    $backendUrl = cRegistry::getBackendUrl();

    $ui = new cGuiMenu();
    $workflowitems = new WorkflowItems();

    $workflowitems->select("idworkflow = '$idworkflow'", "", "position ASC");

    while (($workflowitem = $workflowitems->next()) !== false) {
        $pos = $workflowitem->get("position");
        $name = preg_replace("/\"/","",($workflowitem->get("name")));
        $id = cSecurity::toInteger($workflowitem->get("idworkflowitem"));

        $edititem = new cHTMLLink();
        $edititem->setClass("show_item");
        $edititem->setCLink("workflow_steps", 4, "workflow_step_edit");
        $edititem->setCustom("idworkflowitem", $id);
        $edititem->setCustom("idworkflow", $idworkflow);

        $moveup = new cHTMLLink();
        $moveup->setCLink("workflow_steps", 4, "workflow_step_up");
        $moveup->setCustom("idworkflowitem", $id);
        $moveup->setCustom("idworkflow", $idworkflow);
        $moveup->setCustom("position", $pos);
        $moveup->setAlt(i18n("Move step up", "workflow"));
        $moveup->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/no_verschieben.gif" . '">');

        $movedown = new cHTMLLink();
        $movedown->setCLink("workflow_steps", 4, "workflow_step_down");
        $movedown->setCustom("idworkflowitem", $id);
        $movedown->setCustom("idworkflow", $idworkflow);
        $movedown->setCustom("position", $pos);
        $movedown->setAlt(i18n("Move step down", "workflow"));
        $movedown->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/nu_verschieben.gif" . '">');

        $deletestep = new cHTMLLink();
        $deletestep->setCLink("workflow_steps", 4, "workflow_step_delete");
        $deletestep->setCustom("idworkflowitem", $id);
        $deletestep->setCustom("idworkflow", $idworkflow);
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

        if ($pos < $workflowitems->count()) {
            $ui->setActions($id, "movedown", $movedown->render());
        } else {
            $ui->setActions($id, "movedown", '<img src="images/spacer.gif" width="15" height="1">');
        }

        $ui->setActions($id, "delete", $deletestep->render());

        if ($iIdMarked === $id) {
            $ui->setMarked($id);
        }
    }

    $content = $ui->render(false);

    return ($content);
}

/**
 * @return string
 * @throws cInvalidArgumentException
 */
function createNewWorkflow() {
    global $idworkflow, $cfg;

    $backendUrl = cRegistry::getBackendUrl();

    $content = "";
    $ui = new cGuiMenu('new_workflow_menu_list');
    $rowmark = false;

    $createstep = new cHTMLLink();
    $createstep->setCLink("workflow_steps", 4, "workflow_create_step");
    $createstep->setCustom("idworkflow", $idworkflow);

    // ui->setLink("spacer", NULL);
    $ui->setTitle("create", i18n("Create new step", "workflow"));
    $ui->setImage("create", $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_step_new.gif");
    $ui->setLink("create", $createstep);
    $ui->setRowmark($rowmark);

    $content = $ui->render(false);
    return $content;
}

/**
 * @param $idworkflowitem
 *
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function editWorkflowStep($idworkflowitem) {
    global $area, $idworkflow, $idworkflowitem, $frame, $availableWorkflowActions;
    global $notification;

    $workflowitem = new WorkflowItem();

    if ($workflowitem->loadByPrimaryKey($idworkflowitem) == false) {
        return "&nbsp;";
    }

    $workflowactions = new WorkflowActions();

    $stepname = str_replace('\\','',conHtmlSpecialChars($workflowitem->get("name")));
    $stepdescription = str_replace('\\','',conHtmlSpecialChars($workflowitem->get("description")));
    $id = $workflowitem->get("idworkflowitem");
    $task = $workflowitem->get("idtask");

    $form = new cGuiTableForm("workflow_edit");

    $form->setVar("area", $area);
    $form->setVar("action", "workflow_save_step");
    $form->setVar("idworkflow", $idworkflow);
    $form->setVar("idworkflowitem", $idworkflowitem);
    $form->setVar("frame", $frame);

    $form->addHeader(i18n("Edit workflow step", "workflow"));
    $oTxtStep = new cHTMLTextbox("wfstepname", $stepname, 40, 255);
    $form->add(i18n("Step name", "workflow"), $oTxtStep->render());
    $oTxtStepDesc = new cHTMLTextarea("wfstepdescription", $stepdescription, 60, 10);
    $form->add(i18n("Step description", "workflow"), $oTxtStepDesc->render());

    $actions = '';

    foreach ($availableWorkflowActions as $key => $value) {
        $oCheckbox = new cHTMLCheckbox("wfactions[" . $key . "]", "1", "wfactions[" . $key . "]1", $workflowactions->get($id, $key));
        $oCheckbox->setLabelText($value);
        $actions .= $oCheckbox->toHtml();
    }

    $form->add(i18n("Actions", "workflow"), $actions);
    $form->add(i18n("Assigned users", "workflow"), getWorkflowUsers($idworkflowitem));

    return $form->render(true);
}

/**
 * @param $idworkflowitem
 *
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function getWorkflowUsers($idworkflowitem) {
    global $idworkflow, $cfg;

    $backendUrl = cRegistry::getBackendUrl();

    $ui = new cGuiMenu('workflow_users_menu_list');
    $ui->setRowmark(false);
    $workflowusers = new WorkflowUserSequences();

    $workflowusers->select("idworkflowitem = '$idworkflowitem'", "", "position ASC");

    while (($workflowitem = $workflowusers->next()) !== false) {
        $pos = $workflowitem->get("position");
        $iduser = $workflowitem->get("iduser");
        $timelimit = $workflowitem->get("timelimit");
        $timeunit = $workflowitem->get("timeunit");
        $email = $workflowitem->get("emailnoti");
        $escalation = $workflowitem->get("escalationnoti");
        $timeunit = $workflowitem->get("timeunit");
        $id = $workflowitem->get("idusersequence");

        $moveup = new cHTMLLink();
        $moveup->setCLink("workflow_steps", 4, "workflow_user_up");
        $moveup->setCustom("idworkflowitem", $idworkflowitem);
        $moveup->setCustom("idworkflow", $idworkflow);
        $moveup->setCustom("position", $pos);
        $moveup->setAlt(i18n("Move user up", "workflow"));
        $moveup->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/no_verschieben.gif" . '">');

        $movedown = new cHTMLLink();
        $movedown->setCLink("workflow_steps", 4, "workflow_user_down");
        $movedown->setCustom("idworkflowitem", $idworkflowitem);
        $movedown->setCustom("idworkflow", $idworkflow);
        $movedown->setCustom("position", $pos);
        $movedown->setAlt(i18n("Move user down", "workflow"));
        $movedown->setContent('<img src="' . $backendUrl . $cfg["path"]["plugins"] . "workflow/images/nu_verschieben.gif" . '">');

        $deletestep = new cHTMLLink();
        $deletestep->setCLink("workflow_steps", 4, "workflow_user_delete");
        $deletestep->setCustom("idworkflowitem", $idworkflowitem);
        $deletestep->setCustom("idworkflow", $idworkflow);
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

        if ($pos < $workflowusers->count()) {
            $ui->setActions($id, "movedown", $movedown->render());
        } else {
            $ui->setActions($id, "movedown", '<img src="images/spacer.gif" width="15" height="1">');
        }

        $ui->setActions($id, "delete", $deletestep->render());

        $ui->setImage($id, $backendUrl . $cfg["path"]["plugins"] . "workflow/images/workflow_user.gif");
    }

    $createstep = new cHTMLLink();
    $createstep->setCLink("workflow_steps", 4, "workflow_create_user");
    $createstep->setCustom("idworkflow", $idworkflow);
    $createstep->setCustom("idworkflowitem", $idworkflowitem);

    $ui->setLink("spacer", NULL);

    $ui->setTitle("create", '<input class="text_medium" type="submit" name="adduser" value="' . i18n("Add User", "workflow") . '">');
    $ui->setLink("create", NULL);
    $content = $ui->render(false);

    return $content;
}

$page->set('s', 'NEW', createNewWorkflow());
$page->set('s', 'STEPS', getWorkflowList());
$page->set('s', 'EDITSTEP', editWorkflowStep($idworkflowitem));
$page->set('s', 'WARNING', i18n('Warning: Changes will reset active Workflows', 'workflow'));

$page->render();

?>
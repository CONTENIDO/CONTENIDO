<?php

/**
 * This file contains the workflow allocation management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author unkown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $adduser, $wfactions,
       $wftaskselect, $wfstepname, $wfstepdescription, $wfemailnoti, $wfescalnoti;

plugin_include('workflow', 'includes/functions.workflow.php');
cInclude('includes', 'functions.encoding.php');

$page = new cGuiPage('workflow_steps', 'workflow');
$page->addStyle('workflow.css');

$requestIdWorkflowItem = cSecurity::toInteger($_REQUEST['idworkflowitem'] ?? '0');
$requestIdWorkflow = cSecurity::toInteger($_GET['idworkflow'] ?? '0');
$requestPosition = cSecurity::toInteger($_GET['position'] ?? '0');
$requestIdUserSequence = cSecurity::toInteger($_GET['idusersequence'] ?? '0');
$action = $action ?? '';

$workflowActions = new WorkflowActions();

$availableWorkflowActions = $workflowActions->getAvailableWorkflowActions();

$sCurrentEncoding = cRegistry::getEncoding();

$adduser = $adduser ?? '';

if (conHtmlentities($adduser, ENT_COMPAT, $sCurrentEncoding) == i18n("Add User", "workflow")) {
    $action = 'workflow_create_user';
}

// Function: Move step up
if ($action === 'workflow_step_up') {
    $workflowItems = new WorkflowItems();
    $workflowItems->swap($requestIdWorkflow, $requestPosition, $requestPosition - 1);
}

// Function: Move step down
if ($action === 'workflow_step_down') {
    $workflowItems = new WorkflowItems();
    $workflowItems->swap($requestIdWorkflow, $requestPosition, $requestPosition + 1);
}

// Function: Move user up
if ($action === 'workflow_user_up') {
    $workflowItems = new WorkflowUserSequences();
    $workflowItems->swap($requestIdWorkflowItem, $requestPosition, $requestPosition - 1);
}

// Function: Move step down
if ($action === 'workflow_user_down') {
    $workflowItems = new WorkflowUserSequences();
    $workflowItems->swap($requestIdWorkflowItem, $requestPosition, $requestPosition + 1);
}

// Function: Create new step
if ($action === 'workflow_create_step') {
    $workflowItems = new WorkflowItems();
    $item = $workflowItems->create($requestIdWorkflow);
    $item->set('name', i18n("New Workflow Step", "workflow"));
    $item->store();
    $requestIdWorkflowItem = $item->get('idworkflowitem');
}

// Function: Delete step
if ($action === 'workflow_step_delete') {
    $workflowItems = new WorkflowItems();
    $workflowItems->delete($requestIdWorkflowItem);
}

// Function: Add user
if ($action === 'workflow_create_user') {
    $workflowUsers = new WorkflowUserSequences();
    $new = $workflowUsers->create($requestIdWorkflowItem);
}

// Function: Remove user
if ($action === 'workflow_user_delete') {
    $workflowUsers = new WorkflowUserSequences();
    $workflowUsers->delete($requestIdUserSequence);
}

// Function: Save step
if ($action === 'workflow_save_step' || $action === 'workflow_create_user') {
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
    $workflowItem->setField('name', str_replace('\\', '', $wfstepname));
    $workflowItem->setField('description', str_replace('\\', '', $wfstepdescription));
    $workflowItem->store();

    $userSequences = new WorkflowUserSequences();
    $userSequences->select("idworkflowitem = '$requestIdWorkflowItem'");

    while ($userSequence = $userSequences->next()) {
        $wftime = "time" . $userSequence->get('idusersequence');
        $wfuser = "user" . $userSequence->get('idusersequence');

        $wftimelimit = "wftimelimit" . $userSequence->get('idusersequence');
        $userSequence->set('timeunit', $$wftime);
        $userSequence->set('iduser', $$wfuser);
        $userSequence->set('timelimit', $$wftimelimit);
        $userSequence->set('emailnoti', $wfemailnoti[$userSequence->get('idusersequence')]);
        $userSequence->set('escalationnoti', $wfescalnoti[$userSequence->get('idusersequence')]);
        $userSequence->store();
    }
}

$page->set('s', 'NEW', piwf_createNewWorkflow($requestIdWorkflow));
$page->set('s', 'STEPS', piwf_getWorkflowList($requestIdWorkflow, $requestIdWorkflowItem));
$page->set('s', 'EDITSTEP', piwf_editWorkflowStep($requestIdWorkflow, $requestIdWorkflowItem));
$page->set('s', 'WARNING', i18n('Warning: Changes will reset active Workflows', 'workflow'));

$page->render();

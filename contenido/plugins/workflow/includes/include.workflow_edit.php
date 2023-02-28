<?php

/**
 * This file contains the workflow editing functions.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idworkflow, $wfname, $wfdescription;

/**
 * @var int $frame
 * @var string $area
 */

$requestIdWorkflow = cSecurity::toInteger($_GET['idworkflow'] ?? '0');

$page = new cGuiPage("workflow_edit", "workflow");
$page->addStyle('workflow.css');

$workflows = new Workflows();

$action = $action ?? '';

if ($action == "workflow_delete" && $requestIdWorkflow) {
    $workflows->delete($requestIdWorkflow);

    $page->setSubnav('blank', 'workflow');
    $page->reloadLeftBottomFrame(['idworkflow' => null]);
    $page->displayOk(i18n('Deleted workflow successfully!', 'workflow'));
    $page->render();
    exit();
}

$form = new cGuiTableForm("workflow_edit");

$workflow = $workflows->loadItem($requestIdWorkflow);

if ($action == "workflow_save") {
    if ($requestIdWorkflow <= 0) {
        $workflow = $workflows->create();
        $page->displayOk(i18n("Created new workflow successfully!", 'workflow'));
    } elseif ($idworkflow > 0) {
        $page->displayOk(i18n("Saved changes successfully!", 'workflow'));
    }
    $workflow->set("name",  str_replace('\\', '', $wfname));
    $workflow->set("description", str_replace('\\', '', $wfdescription));
    $idworkflow = cSecurity::toInteger($workflow->get("idworkflow"));
    $workflow->store();
}

if ($idworkflow <= 0) {
    $idworkflow = $requestIdWorkflow;
}

$form->setVar("area", $area);
$form->setVar("action", "workflow_save");
$form->setVar("idworkflow", $idworkflow);
$form->setVar("frame", $frame);

if (true !== $workflow->isLoaded()) {
    $name = i18n("New Workflow", "workflow");
    $header = i18n("Create new workflow", "workflow");
    $description = '';
    $author = '';
} else {
    $header = i18n("Edit workflow", "workflow");
    $description = preg_replace("/\"/","",($workflow->getField("description")));
    $name = preg_replace("/\"/","",($workflow->getField("name")));
    $created = cDate::formatDatetime($workflow->get("created"));
    $userObj = new cApiUser($workflow->get("idauthor"));
    $author = $userObj->getEffectiveName();
}

$form->setHeader($header);
$oTxtWFName = new cHTMLTextbox("wfname", $name, 40, 255);
$form->add(i18n("Workflow name", "workflow"), $oTxtWFName->render());
$oTxtWFDesc = new cHTMLTextarea("wfdescription", $description, 50, 10);
$form->add(i18n("Description", "workflow"), $oTxtWFDesc->render());
if (!empty($author)) {
    $form->add(i18n("Author", "workflow"), $author);
}
if (!empty($created)) {
    $form->add(i18n("Created", "workflow"), $created);
}

$page->setContent($form);

if ($idworkflow) {
    $page->reloadLeftBottomFrame(['idworkflow' => $idworkflow]);
} else {
    $page->reloadLeftBottomFrame(['idworkflow' => null]);
}

$page->render();

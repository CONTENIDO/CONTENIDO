<?php
/**
 * This file contains the workflow editing functions.
 *
 * @package Plugin
 * @subpackage Workflow
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('workflow', 'classes/class.workflow.php');

$page = new cGuiPage("workflow_edit", "workflow");

if ($action == "workflow_delete") {
    $page->displayInfo(i18n('Deleted workflow successfully!', 'workflow'));
    $page->render();
    exit();
}

$form = new cGuiTableForm("workflow_edit");
$workflows = new Workflows();

$workflow = $workflows->loadItem($idworkflow);

if ($action == "workflow_save") {
    if ($idworkflow == "-1") {
        $workflow = $workflows->create();
        $page->displayInfo(i18n("Created new workflow successfully!", 'workflow'));
    } elseif ($idworkflow > 0) {
        $page->displayInfo(i18n("Saved changes successfully!", 'workflow'));
    }
    $workflow->set("name",  str_replace('\\','',$wfname));
    $workflow->set("description", str_replace('\\','',$wfdescription));
    $idworkflow = $workflow->get("idworkflow");
    $workflow->store();
}

if ((int) $idworkflow == 0) {
    $idworkflow = $_GET['idworkflow'];
}

if ($idworkflow) {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = top.content.frames['left'].frames['left_bottom'];
                         var right_top = top.content.frames['right'].frames['right_top'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&action=workflow_delete/, '');
                             left_bottom.location.href = href+'&idworkflow='+" . $idworkflow . ";
                         }

                         if (right_top) {
                            right_top.location.href = right_top.location.href+'&idworkflow='+" . $idworkflow . ";
                         }
                     </script>";
} else {
    $sReloadScript = '';
}

$form->setVar("area", $area);
$form->setVar("action", "workflow_save");
$form->setVar("idworkflow", $idworkflow);
$form->setVar("frame", $frame);

if ($workflow->virgin) {
    $name = i18n("New Workflow", "workflow");
    $header = i18n("Create new workflow", "workflow");
} else {
    $header = i18n("Edit workflow", "workflow");
    $description = preg_replace("/\"/","",($workflow->getField("description")));
    $name = preg_replace("/\"/","",($workflow->getField("name")));
    $created = displayDatetime($workflow->get("created"));
    $userclass = new cApiUser($workflow->get("idauthor"));
    $author = $userclass->getEffectiveName();
}

$form->addHeader($header);
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
if (!empty($sReloadScript)) {
    $page->addScript($sReloadScript);
}

$page->render();

?>
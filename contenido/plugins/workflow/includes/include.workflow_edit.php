<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Contains workflow editing functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-05-20
 *   
 *   $Id: include.workflow_edit.php,v 1.3 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


plugin_include('workflow', 'classes/class.workflow.php');
 
$form = new UI_Table_Form("workflow_edit");
$userclass = new User;
$workflows = new Workflows;

$workflow = $workflows->loadItem($idworkflow);

if ($action == "workflow_save")
{
	if ($idworkflow == "-1")
	{
		$workflow = $workflows->create();
	}

	$workflow->set("name",htmlspecialchars($wfname));
	$workflow->set("description",htmlspecialchars($wfdescription));
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
                             left_bottom.location.href = href+'&idworkflow='+".$idworkflow.";
                         }
                         
                         if (right_top) {
                            right_top.location.href = right_top.location.href+'&idworkflow='+".$idworkflow.";
                         }
                     </script>";
} else {
    $sReloadScript = '';
}


//function formGenerateField ($type, $name, $initvalue, $width, $maxlen)
$form->setVar("area",$area);
$form->setVar("action","workflow_save");
$form->setVar("idworkflow", $idworkflow);
$form->setVar("frame", $frame);

if ($workflow->virgin)
{
	$name = i18n("New Workflow", "workflow");
	$header = i18n("Create new workflow", "workflow");
} else {
	$header = i18n("Edit workflow", "workflow");
    $description = $workflow->get("description");
    $name = $workflow->get("name");
    $created = $workflow->get("created");
    $author = $userclass->getRealname($workflow->get("idauthor"));
}

$form->addHeader($header);
$form->add(i18n("Workflow name", "workflow"),formGenerateField("text","wfname",$name,40,255));
$form->add(i18n("Description", "workflow"),formGenerateField("textbox","wfdescription",$description,50,10));
$form->add(i18n("Author", "workflow"),$author);
$form->add(i18n("Created", "workflow"),$created);

$page = new UI_Page;
$page->setContent($form->render(true));
$page->addScript('reload', $sReloadScript);

$page->render();


?>
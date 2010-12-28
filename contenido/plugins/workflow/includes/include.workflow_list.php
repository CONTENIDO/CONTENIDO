<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Workflow list
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.5
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2006-01-13
 *   
 *   $Id: include.workflow_list.php,v 1.5 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$iIdMarked = (int) $_GET['idworkflow'];

plugin_include('workflow', 'classes/class.workflow.php');

$workflows = new Workflows;
$sScript = '';
if ($action == "workflow_delete")
{
	$workflows->delete($idworkflow);
    $sScript = '<script type="text/javascript">
                    var right_top = top.content.frames["right"].frames["right_top"];
                    var right_bottom = top.content.frames["right"].frames["right_bottom"];

                    if (right_top) {
                        right_top.location.href = "'.$sess->url('main.php?area=workflow&frame=3').'";
                    }
                    if (right_bottom) {
                        right_bottom.location.href = "'.$sess->url('main.php?area=workflow&frame=4').'";
                    }
                </script>';
}

$ui = new UI_Menu;
$workflows->select("idclient = '$client' AND idlang = '$lang'");

while ($workflow = $workflows->next())
{
	$wfid = $workflow->getField("idworkflow");
	$wfname = $workflow->getField("name");
	$wfdescription = $workflow->getField("description");
	
	/* Create the link to show/edit the workflow */
	$link = new Link;
	$link->setMultiLink("workflow","","workflow_common","workflow_show");
	$link->setAlt($wfdescription);
	$link->setCustom("idworkflow",$wfid);

 	$delTitle = i18n("Delete workflow", "workflow");
  	$delDescr = sprintf(i18n("Do you really want to delete the following workflow:<br><br>%s<br>", "workflow"),$wfname);
	$delete = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteWorkflow(\\\''.$wfid.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';	
	
	$ui->setTitle($wfid, $wfname);
	$ui->setLink($wfid, $link);	
	
	$ui->setActions($wfid, 'delete', $delete);
		
	if ($wfid == $iIdMarked) {
		$ui->setExtra ($wfid, 'id="marked" ');
	}
	
}
$content = $ui->render(false);

$delScript = '
    <script type="text/javascript">

        
        function foo(){return true;}

        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox
           instance */
        box = new messageBox("", "", "", 0, 0);

        /* Function for deleting
           modules */

        function deleteWorkflow(idworkflow) {
            url  = "main.php?area=workflow";
            url += "&action=workflow_delete";
            url += "&frame=2";
            url += "&idworkflow=" + idworkflow;
            url += "&contenido=" + sid;
            parent.left_bottom.location.href = url;

        }
		</script>';
		
$sInitRowMark = "<script type=\"text/javascript\">
                     if (document.getElementById('marked')) {
                         row.markedRow = document.getElementById('marked');
                     }
                 </script>";

$msgboxInclude = '    <script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>';        
$page = new UI_Page;
$page->addScript('include', $msgboxInclude);
$page->addScript('del',$delScript);
$page->addScript('refresh', $sScript);
$page->setMargin(0);
$page->setContent($content.$sInitRowMark);
$page->render();

?>
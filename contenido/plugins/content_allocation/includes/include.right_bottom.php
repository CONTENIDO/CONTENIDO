<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * right_bottom frame for Content Allocation
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Plugins
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_REQUEST['treeItem'])) {
    die ('Illegal call!');
}

#added 24.06.08 timo.trautmann security fix filter submitted treeItemPost array before insertion, name also changed according to security fix
$aPostTreeItem = array();
if (!is_object($db)) {
	$db = new DB_Contenido();
}

if (isset($_REQUEST['treeItemPost']['idpica_alloc'])) {
  $aPostTreeItem['idpica_alloc'] = (int) $_REQUEST['treeItemPost']['idpica_alloc'];
}

if (isset($_REQUEST['treeItemPost']['parentid'])) {
  $aPostTreeItem['parentid'] = (int) $_REQUEST['treeItemPost']['parentid'];
}

if (isset($_REQUEST['treeItemPost']['name'])) {
  $sName = stripslashes($_REQUEST['treeItemPost']['name']);
  $sName =$db->escape($sName); 
  $aPostTreeItem['name'] = $sName;
}

$_GET['idpica_alloc'] = (int) $_GET['idpica_alloc'];
#end added 24.06.08 timo.trautmann

$oPage = new cPage();
$oPage->setMargin(10);
$oPage->setMessageBox();
$oTree = new pApiContentAllocationTreeView('f7771624-4874-4745-8b7e-21a49a71a447');

// store item
if ($_POST['step'] == 'store') { 
	$pNotify = '<div style="width:410px;margin-bottom:20px;">';
	$sMessage = sprintf(i18n("New Category %s successfully stored!"), $treeItem['name']);
    $notification->displayNotification("info", $sMessage);
	$pNotify .= '</div>';
	$oTree->storeItem($aPostTreeItem);
}
// rename item
if ($_POST['step'] == 'storeRename') { 
	$pNotify = '<div style="width:410px;margin-bottom:20px;">';
	$sMessage = sprintf(i18n("Category %s successfully renamed!"), $treeItem['name']);
    $notification->displayNotification("info", $sMessage);
	$pNotify .= '</div>';
	$oTree->storeItem($aPostTreeItem);
}
// rename item
if ($_GET['step'] == 'moveup') { 
	$oTree->itemMoveUp($_GET['idpica_alloc']);
}

if ($_GET['step'] == 'deleteItem') { // delete item
	$pNotify = '<div style="width:410px;margin-bottom:20px;">';
	$sMessage = i18n("Category successfully deleted!");
    $notification->displayNotification("info", $sMessage);
	$pNotify .= '</div>';
	$oTree->deleteItem($_GET['idpica_alloc']);
}
if ($_GET['step'] == 'collapse') {
	$oTree->setTreeStatus($_GET['idpica_alloc']);
}
if ($_GET['step'] == 'online') {
	$oTree->setOnline($_GET['idpica_alloc']);	
}
if ($_GET['step'] == 'offline') {
	$oTree->setOffline($_GET['idpica_alloc']);	
}

$oDiv = new cHTMLDiv;
$oDiv->updateAttributes(array('style' => 'padding: 5px; width: 400px; border: 1px #B3B3B3 solid; background-color: #FFFFFF;'));
$sTemp = '';

if ($_GET['step'] == 'createRoot') { // create new root item
	$form = '
		<table cellspacing="0" cellpaddin="0" border="0">
		<form name="create" action="main.php" method="POST" onsubmit="return fieldCheck();">
		<input type="hidden" name="action" value="'.$action.'" />
		<input type="hidden" name="frame" value="'.intval($frame).'" />
		<input type="hidden" name="contenido" value="'.$sess->id.'" />
		<input type="hidden" name="area" value="'.$area.'" />
		<input type="hidden" name="step" value="store" />
		<input type="hidden" name="treeItemPost[parentid]" value="root" />
		<tr><td colspan="2" class="text_medium">'.i18n("Create new tree").'</td></tr>
		<tr>
			<td class="text_medium"><input id="itemname" class="text_medium" type="text" name="treeItemPost[name]" value=""></td>
			<td>&nbsp;<a href="main.php?action='.$action.'&frame='.$frame.'&area='.$area.'&contenido='.$sess->id.'"><img src="images/but_cancel.gif" border="0" /></a>
			<input type="image" src="images/but_ok.gif" /></td>
		</tr>
		</form>
		</table>
		<script language="JavaScript">
			controller = document.getElementById("itemname");
			controller.focus();
			function fieldCheck() {
				if (controller.value == "") {
					alert("'.i18n("Please enter a category name.").'");
					controller.focus();
					return false;
				}
                return true;
			}
		</script>';
	$oDiv->setContent($form);
	$sTemp = $oDiv->render();
} else {
    $newTree = '<a href="main.php?action='.$action.'&step=createRoot&frame='.$frame.'&area='.$area.'&contenido='.$sess->id.'"><img  src="images/folder_new.gif" border="0" style="vertical-align: middle; margin-right: 5px;">'.i18n("Create new tree").'</a><div style="height:10px"></div>';
}

$result = $oTree->renderTree(true);

if ($result === false) {
	$result = '&nbsp;';
}

$js = '
<script language="javascript">
/* Function for deleting categories*/
function deleteCategory(idpica_alloc) {
    var url = "main.php?area='.$area.'&action='.$action.'&step=deleteItem&idpica_alloc=" + idpica_alloc + "&frame='.$frame.'&contenido='.$sess->id.'";
    window.location.href = url;
}
</script>';

$oPage->addScript('deleteCategory', $js);

$oPage->setContent($pNotify . $newTree . $sTemp. '<br/>' . $result);
$oPage->render();

?>
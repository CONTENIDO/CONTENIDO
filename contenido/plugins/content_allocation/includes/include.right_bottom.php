<?php

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
	$oTree->storeItem($_POST['treeItem']);
}
// rename item
if ($_POST['step'] == 'storeRename') { 
	$pNotify = '<div style="width:410px;margin-bottom:20px;">';
	$sMessage = sprintf(i18n("Category %s successfully renamed!"), $treeItem['name']);
    $notification->displayNotification("info", $sMessage);
	$pNotify .= '</div>';
	$oTree->storeItem($_POST['treeItem']);
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
		<input type="hidden" name="frame" value="'.$frame.'" />
		<input type="hidden" name="contenido" value="'.$sess->id.'" />
		<input type="hidden" name="area" value="'.$area.'" />
		<input type="hidden" name="step" value="store" />
		<input type="hidden" name="treeItem[parentid]" value="root" />
		<tr><td colspan="2" class="text_medium">'.i18n("Create new tree").'</td></tr>
		<tr>
			<td class="text_medium"><input id="itemname" class="text_medium" type="text" name="treeItem[name]" value=""></td>
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
//	  $oLink = new Link;
//		$oLink->setCLink($area, 4, '');
//		$oLink->setCustom('step', 'createRoot');
//		$oLink->setContent('<img src="images/folder_new.gif" border="0" style="vertical-align: middle;">Create new tree');
//		$oLink->updateAttributes(array('style' => 'text-decoration: none;'));
//	  $oDiv->setContent($oLink->render());
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

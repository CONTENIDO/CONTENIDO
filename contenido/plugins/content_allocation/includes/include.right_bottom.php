<?php
/**
 * This file contains the right bottom frame backend page for the content allocation plugin in content area.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (isset($_REQUEST['treeItem'])) {
    die ('Illegal call!');
}

#added 24.06.08 timo.trautmann security fix filter submitted treeItemPost array before insertion, name also changed according to security fix
$aPostTreeItem = array();
if (!is_object($db)) {
    $db = cRegistry::getDb();
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

$oPage = new cGuiPage("content_allocation_edit", "content_allocation");
$oTree = new pApiContentAllocationTreeView('f7771624-4874-4745-8b7e-21a49a71a447');

// store item
if ($_POST['step'] == 'store') {
    $oTree->storeItem($aPostTreeItem);
    $oPage->displayOk(sprintf(i18n("New Category %s successfully stored!", 'content_allocation'), $treeItem['name']));
}
// rename item
if ($_POST['step'] == 'storeRename') {
    $oTree->storeItem($aPostTreeItem);
    $oPage->displayOk(sprintf(i18n("Category %s successfully renamed!", 'content_allocation'), $treeItem['name']));
}
// rename item
if ($_GET['step'] == 'moveup') {
    $oTree->itemMoveUp($_GET['idpica_alloc']);
}

if ($_GET['step'] == 'deleteItem') { // delete item
    $oPage->displayOk(i18n("Category successfully deleted!", 'content_allocation'));
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
if ($_GET['step'] == 'expanded') {
	$oTree->setTreeStatus($_GET['idpica_alloc']);
}

$oDiv = new cHTMLDiv;
$sTemp = '';

if ($_GET['step'] == 'createRoot') { // create new root item
    $form = '
        <table cellspacing="0" cellpaddin="0" border="0">
        <form name="create" action="main.php" method="POST" onsubmit="return fieldCheck();">
        <input type="hidden" name="action" value="'.$action.'">
        <input type="hidden" name="frame" value="'.intval($frame).'">
        <input type="hidden" name="contenido" value="'.$sess->id.'">
        <input type="hidden" name="area" value="'.$area.'">
        <input type="hidden" name="step" value="store">
        <input type="hidden" name="treeItemPost[parentid]" value="root">
        <tr><td colspan="2" class="text_medium">'.i18n("Create new tree", 'content_allocation').'</td></tr>
        <tr>
            <td class="text_medium"><input id="itemname" class="text_medium" type="text" name="treeItemPost[name]" value=""></td>
            <td>&nbsp;<a href="main.php?action='.$action.'&frame='.$frame.'&area='.$area.'&contenido='.$sess->id.'"><img src="images/but_cancel.gif" alt=""></a>
            <input type="image" src="images/but_ok.gif"></td>
        </tr>
        </form>
        </table>
        <script type="text/javascript">
        var controller = document.getElementById("itemname");
        controller.focus();
        function fieldCheck() {
            if (controller.value == "") {
                alert("'.i18n("Please enter a category name.", 'content_allocation').'");
                controller.focus();
                return false;
            }
            return true;
        }
        </script>';
    $oDiv->updateAttributes(array('style' => 'padding: 5px; width: 400px; border: 1px #B3B3B3 solid; background-color: #FFFFFF;'));
    $oDiv->setContent($form);
} else {
    $oDiv->setContent('<a href="main.php?action='.$action.'&step=createRoot&frame='.$frame.'&area='.$area.'&contenido='.$sess->id.'"><img  src="images/folder_new.gif" class="vAlignMiddle"><span class="tableElement">'.i18n("Create new tree", 'content_allocation').'</span></a>');
}

$treeDiv = new cHTMLDiv();
$result = $oTree->renderTree(true);

if ($result === false) {
    $result = '&nbsp;';
}
$treeDiv->setContent($result);

$js = '
<script type="text/javascript">
// Function for deleting categories
function deleteCategory(idpica_alloc) {
    var url = "main.php?area='.$area.'&action='.$action.'&step=deleteItem&idpica_alloc=" + idpica_alloc + "&frame='.$frame.'&contenido='.$sess->id.'";
    window.location.href = url;
}
</script>';

$oPage->addScript($js);

$oPage->setContent(array($oDiv, $treeDiv));
$oPage->render();

?>
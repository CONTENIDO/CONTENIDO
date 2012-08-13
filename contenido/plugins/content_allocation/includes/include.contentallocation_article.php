<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * content_allocation Articles
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage content_allocation
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.pathresolver.php');

// fetch idartlang for idart
$sql = "SELECT idartlang FROM " . $cfg['tab']['art_lang'] . " WHERE idart=" . (int) $idart . " AND idlang=" . (int) $lang;
$db->query($sql);
$db->next_record();
$this_idartlang = $db->f('idartlang');

$oPage = new cGuiPage("contentallocation_article", "content_allocataion", "7");

$oTree = new pApiContentAllocationComplexList('06bd456d-fe76-40cb-b041-b9ba90dc400a');
$oAlloc = new pApiContentAllocation;

if ($_POST['action'] == 'storeallocation') {
    $oAlloc->storeAllocations($this_idartlang, $_POST['allocation']);
}
if ($_GET['step'] == 'collapse') {
    $oTree->setTreeStatus($_GET['idpica_alloc']);
}

#build category path
$catString = '';
prCreateURLNameLocationString($idcat, ' > ', $catString, true, 'breadcrumb');
$oArticle = new cApiArticleLanguage();
$oArticle->loadByArticleAndLanguageId($idart, $lang);
$sArticleTitle = $oArticle->getField('title');

$sLocationString = '<div id="categorypath" class="categorypath">' . i18n("You are here") . ": " . $catString . ' > ' . htmlspecialchars($sArticleTitle) . '</div>';

// load allocations
$loadedAllocations = $oAlloc->loadAllocations($this_idartlang);

$oTree->setChecked($loadedAllocations);
$result = $oTree->renderTree(true);

if ($result == false) {
    $result = $notification->returnNotification('warning', i18n('There is no tagging tree.', 'content_allocation'));
} else {
    if (!is_object($tpl)) {
        $tpl = new cTemplate();
    }
    $hiddenfields = '<input type="hidden" name="action" value="storeallocation">
        <input type="hidden" name="idart" value="' . $idart . '">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <input type="hidden" name="area" value="' . $area . '">
        <input type="hidden" name="frame" value="' . $frame . '">
        <input type="hidden" name="idcat" value="' . $idcat . '">';
    $tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

    if (sizeof($loadedAllocations) > 0) {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [' . implode(',', $loadedAllocations) . '];');
    } else {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [];');
    }

    $oDiv = new cHTMLDiv();
    $oDiv->updateAttributes(array('style' => 'text-align:right;padding:5px;width:730px;border:1px #B3B3B3 solid;background-color:#FFF;'));
    $oDiv->setContent('<input type="image" src="images/but_ok.gif">');
    $tpl->set('s', 'DIV', '<br>' . $oDiv->render());

    $tpl->set('s', 'TREE', $result);

    $tpl->set('s', 'REMOVE_ALL', i18n("Remove all", 'content_allocation'));
    $tpl->set('s', 'REMOVE', i18n("Remove", 'content_allocation'));

    $result = $tpl->generate($cfg['pica']['treetemplate_complexlist'], true);

    $oPage->addStyle($cfg['pica']['style_complexlist']);
    $oPage->addScript($cfg['pica']['script_complexlist']);
}
//breadcrumb onclick
if (!isset($syncfrom)) {
    $syncfrom = -1;
}
$syncoptions = $syncfrom;
$sLocationString .= "<script type='text/javascript'>
        $(document).ready(function(){
            $('div#categorypath > a').click(function () {
                var url = $(this).attr('href');
                var sVal = url.split('idcat=');
                var aVal = sVal[1].split('&');
                var iIdcat = aVal[0];
                sVal = url.split('idtpl=');
                aVal = sVal[1].split('&');
                var iIdtpl = aVal[0];
                conMultiLink('right_top', 'main.php?area=con&frame=3&idcat=' + iIdcat + '&idtpl=' + iIdtpl + '&display_menu=1&syncoptions=" . $syncoptions . "&contenido=" . $contenido . "',
                'right_bottom', url,
                'left_bottom', 'main.php?area=con&frame=2&idcat=' + iIdcat + '&idtpl=' + iIdtpl + '&contenido=" . $contenido . "');
                return false;
            });
        });
    </script>";

$div = new cHTMLDiv();
$div->setContent($sLocationString . $result);


$oPage->setContent($div);
$oPage->render();
?>
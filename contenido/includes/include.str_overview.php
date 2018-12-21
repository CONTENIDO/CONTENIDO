<?php

/**
 * This file contains the backend page for displaying structure (category tree)
 * overview.
 *
 * @package Core
 * @subpackage Backend
 * @author Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendUrl = cRegistry::getBackendUrl();

// before we do anything, let's check if everything works
// in case something doesn't work the user doesn't just see a white page
$ret = strCheckTreeForErrors();
if (is_array($ret)) {
    $string = '';
    foreach ($ret as $errorMessage) {
        $string .= $errorMessage . '<br>';
    }
    $string .= '<br>' . i18n('Be careful! Further editing of the category tree might corrupt it more. Please fix the errors first.');
    $notification->displayNotification(cGuiNotification::LEVEL_WARNING, $string);
}

strRemakeTreeTable();

$tmp_area = 'str';

// Duplicate category
if ($action == 'str_duplicate' && ($perm->have_perm_area_action('str', 'str_duplicate') || $perm->have_perm_area_action_item('str', 'str_duplicate', $idcat))) {
    strCopyTree($idcat, $parentid);
}

$oDirectionDb = cRegistry::getDb();

/**
 * Build a category select box containg all categories which the current
 * user is allowed to create new categories.
 *
 * @return string HTML
 *                
 * @throws cDbException
 * @throws cException
 */
function buildCategorySelectRights() {
    global $cfg, $client, $lang, $idcat, $perm, $tmp_area;

    $db = cRegistry::getDb();

    $oHtmlSelect = new cHTMLSelectElement('idcat', '', 'new_idcat');

    $oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);

    $sql = "SELECT a.idcat AS idcat, b.name AS name, c.level
            FROM " . $cfg["tab"]["cat"] . " AS a
            , " . $cfg["tab"]["cat_lang"] . " AS b
            , " . $cfg["tab"]["cat_tree"] . " AS c
            WHERE a.idclient = '" . cSecurity::toInteger($client) . "'
            AND b.idlang = '" . cSecurity::toInteger($lang) . "'
            AND b.idcat = a.idcat
            AND c.idcat = a.idcat
            ORDER BY c.idtree";

    $db->query($sql);

    $categories = array();

    while ($db->nextRecord()) {
        $categories[$db->f("idcat")]["name"] = $db->f("name");
        $categories[$db->f("idcat")]["idcat"] = $db->f("idcat");
        if ($perm->have_perm_area_action($tmp_area, 'str_newcat') || $perm->have_perm_area_action_item($tmp_area, 'str_newcat', $db->f('idcat'))) {
            $categories[$db->f("idcat")]["perm"] = 1;
        } else {
            $categories[$db->f("idcat")]["perm"] = 0;
        }
        $categories[$db->f("idcat")]["level"] = $db->f("level");
    }

    $aCategoriesReversed = array_reverse($categories);

    $iLevel = 0;
    foreach ($aCategoriesReversed as $iKeyIdCat => $aValues) {
        if ($aValues['level'] > $iLevel && $aValues['perm']) {
            $iLevel = $aValues['level'];
        } else if ($aValues['level'] < $iLevel) {
            $iLevel = $aValues['level'];
        } else {
            if (!$aValues['perm']) {
                unset($categories[$aValues["idcat"]]);
            }
        }
    }

    foreach ($categories as $tmpidcat => $props) {
        $spaces = '&nbsp;&nbsp;';
        for ($i = 0; $i < $props['level']; $i++) {
            $spaces .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        $sCategoryname = $props['name'];
        $sCategoryname = cString::trimHard($sCategoryname, 30);
        $oHtmlSelectOption = new cHTMLOptionElement($spaces . ">" . conHtmlSpecialChars($sCategoryname), $tmpidcat, false, !$props['perm']);
        $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
    }

    return $oHtmlSelect->toHtml();
}

/**
 *
 * @param TreeItem $item
 * @param string   $catName
 *
 * @return string
 * 
 * @throws cException
 */
function getStrExpandCollapseButton($item, $catName) {
    global $sess, $frame, $area;
    $selflink = 'main.php';

    $img = new cHTMLImage();
    $img->updateAttributes(array(
        'style' => 'padding:4px;'
    ));

    // show additional information as tooltip
    // if current user is admin or sysadmin
    $auth = cRegistry::getAuth();
    $currentUser = new cApiUser($auth->auth['uid']);
    $userPerms = $currentUser->getPerms();
    if (cString::findFirstPos($userPerms, 'sysadmin') !== false || cString::findFirstPos($userPerms, 'admin[') !== false) {
        $title = " title=\"idcat: {$item->getId()}, parentid: {$item->getCustom('parentid')}, preid: {$item->getCustom('preid')}, postid: {$item->getCustom('postid')}\"";
    } else {
        $title = '';
    }

    $catName = cSecurity::unFilter($catName);

    if (count($item->getSubItems()) > 0) {
        if ($item->isCollapsed() == true) {
            $expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=" . $item->getId());
            $img->setSrc($item->getCollapsedIcon());
            $img->setAlt(i18n("Open category"));
            return '<a href="' . $expandlink . '">' . $img->render() . '</a>&nbsp;' . '<a href="' . $expandlink . '"' . $title . '>' . conHtmlSpecialChars($catName) . '</a>';
        } else {
            $collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=" . $item->getId());
            $img->setSrc($item->getExpandedIcon());
            $img->setAlt(i18n("Close category"));
            return '<a href="' . $collapselink . '">' . $img->render() . '</a>&nbsp;' . '<a href="' . $collapselink . '"' . $title . '>' . conHtmlSpecialChars($catName) . '</a>';
        }
    } else {
        return '<img src="images/spacer.gif" width="14" height="7">&nbsp;<span' . $title . '>' . conHtmlSpecialChars($catName) . '</span>';
    }
}

/**
 *
 * @return string
 * 
 * @throws cDbException
 * @throws cException
 */
function getTemplateSelect() {
    global $client, $cfg, $db;

    $oHtmlSelect = new cHTMLSelectElement('cat_template_select', '', 'cat_template_select');

    $oHtmlSelectOption = new cHTMLOptionElement('--- ' . i18n("none") . ' ---', 0, false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);

    $sql = "SELECT idtpl, name, defaulttemplate
            FROM " . $cfg['tab']['tpl'] . "
            WHERE idclient = '" . $client . "'
            ORDER BY name";

    if ($db->query($sql)) {
        while ($db->nextRecord()) {
            $bDefaultTemplate = $db->f('defaulttemplate');
            $oHtmlSelectOption = new cHTMLOptionElement($db->f('name'), $db->f('idtpl'), $bDefaultTemplate);
            $oHtmlSelect->appendOptionElement($oHtmlSelectOption);
        }
    }

    return $oHtmlSelect->toHtml();
}

/**
 *
 * @param array $listColumns
 */
function insertEmptyStrRow($listColumns) {
    global $tpl;

    $tpl->set('d', 'BGCOLOR', '#FFFFFF');
    $tpl->set('d', 'BGCOLOR_EDIT', '#F1F1F1');
    $tpl->set('d', 'ALIAS', '&nbsp;');
    $tpl->set('d', 'INDENT', '3px');
    $tpl->set('d', 'RENAMEBUTTON', '&nbsp;');
    $tpl->set('d', 'NEWCATEGORYBUTTON', '&nbsp;');
    $tpl->set('d', 'VISIBLEBUTTON', '&nbsp;');
    $tpl->set('d', 'PUBLICBUTTON', '&nbsp;');
    $tpl->set('d', 'DELETEBUTTON', '&nbsp;');
    $tpl->set('d', 'UPBUTTON', '&nbsp;');
    $tpl->set('d', 'COLLAPSE_CATEGORY_NAME', '&nbsp;');
    $tpl->set('d', 'TPLNAME', '&nbsp;');
    $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
    $tpl->set('d', 'DOWNBUTTON', '&nbsp;');
    $tpl->set('d', 'SHOW_MOUSEOVER', '');
    $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
    $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', '');
    $tpl->set('d', 'TPLDESC', '');
    $tpl->set('d', 'DUPLICATEBUTTON', '&nbsp;');
    $tpl->set('d', 'TEMPLATEBUTTON', '&nbsp;');
    $tpl->set('d', 'MOUSEOVER', '');
    $tpl->set('d', 'SUM_COLUMNS_EDIT', 15 + count($listColumns));
    $tpl->set('d', 'CATID', '');
    $tpl->set('d', 'PARENTID', '');
    $tpl->set('d', 'LEVEL', '');
    $tpl->set('d', 'ACTION_EDIT_URL', '');
    $tpl->set('d', 'INPUT_CATEGORY', '');
    $tpl->set('d', 'LABEL_ALIAS_NAME', '');
    $tpl->set('d', 'HREF_CANCEL', '');
    $tpl->set('d', 'SRC_CANCEL', '');
    $tpl->set('d', 'DIRECTION', '');
    $tpl->set('d', 'SRC_OK', '');
    $tpl->set('d', 'VALUE_ALIAS_NAME', '');
    $tpl->set('d', 'HEIGHT', 'height:15px;');
    $tpl->set('d', 'BORDER_CLASS', 'str-style-b');

    // content rows
    $additionalColumns = array();
    foreach ($listColumns as $content) {
        $additionalColumns[] = '<td class="emptyCell2" nowrap="nowrap">&nbsp;</td>';
    }
    $tpl->set('d', 'ADDITIONALCOLUMNS', implode('', $additionalColumns));

    $tpl->next();
}
getTemplateSelect();

$sess->register("remakeStrTable");
$sess->register("StrTableClient");
$sess->register("StrTableLang");

$cancel = $sess->url("main.php?area=$area&frame=$frame");

if (isset($force) && $force == 1) {
    $remakeStrTable = true;
}

if ($StrTableClient != $client) {
    unset($expandedList);
    $remakeStrTable = true;
}

if ($StrTableLang != $lang) {
    unset($expandedList);
    $remakeStrTable = true;
}

$StrTableClient = $client;
$StrTableLang = $lang;

if (!isset($idcat)) {
    $idcat = 0;
}
if (!isset($action)) {
    $action = 0;
}

/**
 *
 * @param TreeItem $rootItem
 * @param ArrayIterator $itemsIterator
 */
function buildTree(&$rootItem, $itemsIterator) {
    global $nextItem, $perm, $tmp_area;

    while ($itemsIterator->valid()) {
        $key = $itemsIterator->key();
        $item = $itemsIterator->current();
        $itemsIterator->next();

        unset($newItem);

        $bCheck = false;
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_newtree');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_newcat');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_makevisible');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_makepublic');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_deletecat');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_moveupcat');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_movedowncat');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_movesubtree');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action($tmp_area, 'str_renamecat');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_area_action('str_tplcfg', 'str_tplcfg');
        }
        if (!$bCheck) {
            $bCheck = $perm->have_perm_item($tmp_area, $item['idcat']);
        }

        if ($bCheck) {
            $newItem = new TreeItem($item['name'], $item['idcat'], true);
        } else {
            $newItem = new TreeItem($item['name'], $item['idcat'], false);
        }

        $newItem->setCollapsedIcon('images/open_all.gif');
        $newItem->setExpandedIcon('images/close_all.gif');
        $newItem->setCustom('idtree', $item['idtree']);
        $newItem->setCustom('level', $item['level']);
        $newItem->setCustom('idcat', $item['idcat']);
        $newItem->setCustom('idtree', $item['idtree']);
        $newItem->setCustom('parentid', $item['parentid']);
        $newItem->setCustom('alias', $item['alias']);
        $newItem->setCustom('preid', $item['preid']);
        $newItem->setCustom('postid', $item['postid']);
        $newItem->setCustom('visible', $item['visible']);
        $newItem->setCustom('idtplcfg', $item['idtplcfg']);
        $newItem->setCustom('public', $item['public']);

        if ($perm->have_perm_item('str', $item['idcat'])) {
            $newItem->setCustom('forcedisplay', 1);
        }

        if ($itemsIterator->offsetExists($key + 1)) {
            $nextItem = $itemsIterator->offsetGet($key + 1);
        } else {
            $nextItem = 0;
        }

        $rootItem->addItem($newItem);

        if ($nextItem['level'] > $item['level']) {
            $oldRoot = $rootItem;
            buildTree($newItem, $itemsIterator);
            $rootItem = $oldRoot;
        }

        if ($nextItem['level'] < $item['level']) {
            return;
        }
    }
}

if (!$perm->have_perm_area_action($area)) {
    return;
}

$sql = "SELECT
            idtree, A.idcat, level, name, parentid, preid, postid, visible, public, idtplcfg, C.urlname as alias
        FROM
            " . $cfg["tab"]["cat_tree"] . " AS A,
            " . $cfg["tab"]["cat"] . " AS B,
            " . $cfg["tab"]["cat_lang"] . " AS C
        WHERE
            A.idcat     = B.idcat AND
            B.idcat     = C.idcat AND
            C.idlang    = '" . cSecurity::toInteger($lang) . "' AND
            B.idclient  = '" . cSecurity::toInteger($client) . "'
        ORDER BY
            idtree";

$db->query($sql);

if ($db->num_rows() == 0) { // If we have no categories, display warning message
    $additionalheader = $notification->returnNotification("warning", i18n("You have no categories for this client. Please create a new root category with your categories. Without categories, you can't create some articles.")) . "<br />";
} else {

    $bIgnore = false;
    $iIgnoreLevel = 0;

    $items = array();
    while ($db->nextRecord()) {
        $bSkip = false;

        if ($bIgnore == true && $iIgnoreLevel >= $db->f('level')) {
            $bIgnore = false;
        }

        if ($db->f('idcat') == $movesubtreeidcat) {
            $bIgnore = true;
            $iIgnoreLevel = $db->f('level');
            $sMoveSubtreeCatName = $db->f('name');
        }

        if ($iCurLevel == $db->f('level')) {
            if ($iCurParent != $db->f('parentid')) {
                $bSkip = true;
            }
        } else {
            $iCurLevel = $db->f('level');
            $iCurParent = $db->f('parentid');
        }

        if ($bIgnore == false && $bSkip == false) {
            $entry = array();
            $entry['idtree'] = $db->f('idtree');
            $entry['idcat'] = $db->f('idcat');
            $entry['level'] = $db->f('level');
            $entry['name'] = htmldecode($db->f('name'));
            $entry['alias'] = htmldecode($db->f('alias'));
            $entry['parentid'] = $db->f('parentid');
            $entry['preid'] = $db->f('preid');
            $entry['postid'] = $db->f('postid');
            $entry['visible'] = $db->f('visible');
            $entry['public'] = $db->f('public');
            $entry['idtplcfg'] = $db->f('idtplcfg');

            $items[] = $entry;
        }
    }

    $rootStrItem = new TreeItem('root', -1);
    $rootStrItem->setCollapsedIcon('images/open_all.gif');
    $rootStrItem->setExpandedIcon('images/close_all.gif');

    $arrayObj = new ArrayObject($items);
    buildTree($rootStrItem, $arrayObj->getIterator());

    $expandedList = unserialize($currentuser->getUserProperty('system', 'cat_expandstate'));

    if (is_array($expandedList[$client])) {
        $rootStrItem->markExpanded($expandedList[$client]);
    }

    if (isset($collapse) && is_numeric($collapse)) {
        $rootStrItem->markCollapsed($collapse);
    }

    if (isset($expand) && is_numeric($expand)) {
        $rootStrItem->markExpanded($expand);
    }

    if (isset($expand) && $expand == 'all') {
        $rootStrItem->expandAll(-1);
    }

    if (isset($collapse) && $collapse == 'all') {
        $rootStrItem->collapseAll(-1);
    }

    if ($action === 'str_newcat') {
        $rootStrItem->markExpanded($idcat);
    }

    $expandedList[$client] = array();
    $objects = array();

    $rootStrItem->traverse($objects);

    $rootStrItem->getExpandedList($expandedList[$client]);
    $currentuser->setUserProperty('system', 'cat_expandstate', serialize($expandedList));

    // Reset Template
    $tpl->reset();
    $tpl->set('s', 'AREA', $area);
    $tpl->set('s', 'FRAME', $frame);

    $_cecIterator = $_cecRegistry->getIterator('Contenido.CategoryList.Columns');

    $listColumns = array();
    if ($_cecIterator->count() > 0) {
        while ($chainEntry = $_cecIterator->next()) {
            $tmplistColumns = $chainEntry->execute(array());
            if (is_array($tmplistColumns)) {
                $listColumns = array_merge($listColumns, $tmplistColumns);
            }
        }

        foreach ($listColumns as $content) {
            // Header for additional columns
            $additionalheaders[] = '<th class="header nowrap" nowrap="nowrap">' . $content . '</th>';
        }

        $additionalheader = implode('', $additionalheaders);
    } else {
        $additionalheader = '';
    }

}

$tpl->set('s', 'ADDITIONALHEADERS', $additionalheader);

// We don't want to show our root
unset($objects[0]);

$selflink = 'main.php';
$expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=all&syncoptions=$syncoptions");
$collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=all&syncoptions=$syncoptions");
$collapseimg = '<a class="black" href="' . $collapselink . '" alt="' . i18n("Close all categories") . '" title="' . i18n("Close all categories") . '">
        <img src="images/close_all.gif">&nbsp;' . i18n("Close all categories") . '</a>';
$expandimg = '<a class="black" href="' . $expandlink . '" alt="' . i18n("Open all categories") . '" title="' . i18n("Open all categories") . '">
        <img src="images/open_all.gif">&nbsp;' . i18n("Open all categories") . '</a>';

$tpl->set('s', 'COLLAPSE_ALL', $collapseimg);
$tpl->set('s', 'EXPAND_ALL', $expandimg);
$sMouseover = 'onmouseover="str.over(this)" onmouseout="str.out(this)" onclick="str.click(this)"';

// Fill inline edit table row
$tpl->set('s', 'SUM_COLUMNS_EDIT', 15 + count($listColumns));
$tpl->set('s', 'ACTION_EDIT_URL', $sess->url("main.php?frame=$frame"));
$tpl->set('s', 'SRC_CANCEL', $backendUrl . $cfg["path"]["images"] . 'but_cancel.gif');
$tpl->set('s', 'SRC_OK', $backendUrl . $cfg["path"]["images"] . 'but_ok.gif');
$tpl->set('s', 'HREF_CANCEL', "javascript:handleInlineEdit(0)");
$tpl->set('s', 'LABEL_ALIAS_NAME', i18n('Alias'));
$tpl->set('s', 'TEMPLATE_URL', $sess->url("main.php?area=str_tplcfg&frame=$frame"));
$message = addslashes(i18n("Do you really want to duplicate the following category:<br><br><b>%s</b><br><br>Notice: The duplicate process can take up to several minutes, depending on how many subitems and articles you've got."));
$tpl->set('s', 'DUPLICATE_MESSAGE', $message);
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following category:<br><br><b>%s</b>"));
$tpl->set('s', 'OK', i18n('OK'));
$tpl->set('s', 'CANCEL', i18n('Cancel'));
$tpl->set('s', 'MOVE_CONFIRMATION', i18n('Do you really want to move the category?'));

$bAreaAddNewCategory = false;

$aInlineEditData = array();

$sql = "SELECT idtplcfg, idtpl FROM " . $cfg["tab"]["tpl_conf"];
$db->query($sql);
$aTplconfigs = array();
while ($db->nextRecord()) {
    $aTplconfigs[$db->f('idtplcfg')] = $db->f('idtpl');
}

$sql = "SELECT name, description, idtpl FROM " . $cfg["tab"]["tpl"];
$db->query($sql);
$aTemplates = array();
while ($db->nextRecord()) {
    $aTemplates[$db->f('idtpl')] = array(
        'name' => $db->f('name'),
        'description' => $db->f('description')
    );
}

foreach ($objects as $key => $value) {
    // check if there area any permission for this $idcat in the mainarea 6
    // (=str) and there subareas
    $bCheck = false;
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_newtree');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_newcat');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_makevisible');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_makepublic');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_deletecat');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_moveupcat');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_movedowncat');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_movesubtree');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action($tmp_area, 'str_renamecat');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_area_action('str_tplcfg', 'str_tplcfg');
    }
    if (!$bCheck) {
        $bCheck = $perm->have_perm_item($tmp_area, $value->getId());
    }
    if (!$bCheck) {
        $bCheck = $value->isCustomAttributeSet("forcedisplay");
    }

    if ($bCheck) {

        // Insert empty row
        if ($value->getCustom('level') == 0 && $value->getCustom('preid') != 0) {
            insertEmptyStrRow($listColumns);
        }

        $tpl->set('d', 'BGCOLOR', '#FFFFFF');
        $tpl->set('d', 'BGCOLOR_EDIT', '#F1F1F1');
        $tpl->set('d', 'HEIGHT', 'height:25px');
        $tpl->set('d', 'BORDER_CLASS', 'str-style-c tooltip');

        $tpl->set('d', 'INDENT', ($value->getCustom('level') * 16) . "px");
        $sCategoryname = $value->getName();
        if (cString::getStringLength($value->getName()) > 30) {
            $sCategoryname = cString::trimHard($sCategoryname, 30);
        }

        // $tpl->set('d', 'CATEGORY', $sCategoryname);
        if (cString::getStringLength($value->getName()) > 30) {
            $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', 'title="' . htmlspecialchars(cSecurity::unFilter($value->getName())) . '" class="tooltip"');
        } else {
            $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', '');
        }

        $tpl->set('d', 'COLLAPSE_CATEGORY_NAME', getStrExpandCollapseButton($value, $sCategoryname));
        if ($value->getCustom('alias')) {
            $sCategoryalias = $value->getCustom('alias');
            if (cString::getStringLength($value->getCustom('alias')) > 30) {
                $sCategoryalias = cString::trimHard($sCategoryalias, 30);
            }
            $tpl->set('d', 'ALIAS', $sCategoryalias);
            if (cString::getStringLength($value->getCustom('alias')) > 30) {
                $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', 'title="' . $value->getCustom('alias') . '"');
            } else {
                $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
            }
        } else {
            $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
            $tpl->set('d', 'ALIAS', '&nbsp;');
        }

        $template = $aTemplates[$aTplconfigs[$value->getCustom('idtplcfg')]]['name'];
        $templateDescription = $aTemplates[$aTplconfigs[$value->getCustom('idtplcfg')]]['description'];

        $descString = '';

        if ($template == "") {
            $template = '--- ' . i18n("none") . ' ---';
        }

        // Description for hover effect
        $descString = '<b>' . $template . '</b>';

        if (cString::getStringLength($templateDescription) > 0) {
            $descString .= '<br>' . $templateDescription;
        }

        $sTemplatename = $template;
        if (cString::getStringLength($template) > 20) {
            $sTemplatename = cString::trimHard($sTemplatename, 20);
        }

        $tpl->set('d', 'TPLNAME', $sTemplatename);
        $tpl->set('d', 'TPLDESC', $descString);

        if ($perm->have_perm_area_action($tmp_area, 'str_renamecat') || $perm->have_perm_area_action_item($tmp_area, 'str_renamecat', $value->getId())) {
            $bPermRename = 1;
        } else {
            $bPermRename = 0;
        }

        if ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg') || $perm->have_perm_area_action_item('str_tplcfg', 'str_tplcfg', $value->getId())) {
            $bPermTplcfg = 1;
        } else {
            $bPermTplcfg = 0;
        }

        $aRecord = array();
        $sCatName = $value->getName();

        // $aRecord['catn'] = str_replace('\'', '\\\'', $sCatName);
        $aRecord['catn'] = $sCatName;
        $sAlias = $value->getCustom('alias');
        // $aRecord['alias'] = str_replace('\'', '\\\'', $sAlias);
        $aRecord['alias'] = conHtmlSpecialChars($sAlias);
        $aRecord['idtplcfg'] = $value->getCustom('idtplcfg');
        $aRecord['pName'] = $bPermRename;
        $aRecord['pTplcfg'] = $bPermTplcfg;
        $aInlineEditData[$value->getId()] = $aRecord;

        if ($perm->have_perm_area_action($area, "str_renamecat")) {
            $tpl->set('d', 'RENAMEBUTTON', "<a class=\"action\" href=\"javascript:handleInlineEdit(" . $value->getId() . ");\"><img src=\"" . $cfg["path"]["images"] . "but_todo.gif\" id=\"cat_" . $value->getId() . "_image\" alt=\"" . i18n("Edit category") . "\" title=\"" . i18n("Edit category") . "\"></a>");
        } else {
            $tpl->set('d', 'RENAMEBUTTON', "");
        }
        $tpl->set('d', 'CATID', $value->getId());
        $tpl->set('d', 'PARENTID', $value->getCustom('parentid'));
        $tpl->set('d', 'POSTID', $value->getCustom('postid'));
        $tpl->set('d', 'PREID', $value->getCustom('preid'));
        $tpl->set('d', 'LEVEL', $value->getCustom('level'));

        if (cString::getStringLength($template) > 20) {
            $tpl->set('d', 'SHOW_MOUSEOVER', 'title="' . $descString . '"');
        } else {
            $tpl->set('d', 'SHOW_MOUSEOVER', '');
        }

        $tpl->set('d', 'MOUSEOVER', $sMouseover);

        if ($perm->have_perm_area_action($tmp_area, 'str_newcat') || $perm->have_perm_area_action_item($tmp_area, 'str_newcat', $value->getId())) {
            $bAreaAddNewCategory = true;
        }

        if ($perm->have_perm_area_action($tmp_area, 'str_makevisible') || $perm->have_perm_area_action_item($tmp_area, 'str_makevisible', $value->getId())) {
            if ($value->getCustom('visible') == 1) {
                $tpl->set('d', 'VISIBLEBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_makevisible&frame=$frame&idcat=" . $value->getId() . "&visible=" . $value->getCustom('visible')) . "#clickedhere\"><img src=\"images/online.gif\" alt=\"" . i18n("Make offline") . "\" title=\"" . i18n("Make offline") . "\"></a>");
            } else {
                $tpl->set('d', 'VISIBLEBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_makevisible&frame=$frame&idcat=" . $value->getId() . "&visible=" . $value->getCustom('visible')) . "#clickedhere\"><img src=\"images/offline.gif\" alt=\"" . i18n("Make online") . "\" title=\"" . i18n("Make online") . "\"></a>");
            }
        } else {
            $tpl->set('d', 'VISIBLEBUTTON', '&nbsp;');
        }

        if ($perm->have_perm_area_action($tmp_area, 'str_makepublic') || $perm->have_perm_area_action_item($tmp_area, 'str_makepublic', $value->getId())) {
            if ($value->getCustom('public') == 1) {
                $tpl->set('d', 'PUBLICBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_makepublic&frame=$frame&idcat=" . $value->getId() . "&public=" . $value->getCustom('public')) . "#clickedhere\"><img src=\"images/folder_delock.gif\" alt=\"" . i18n("Protect category") . "\" title=\"" . i18n("Protect category") . "\"></a>");
            } else {
                $tpl->set('d', 'PUBLICBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_makepublic&frame=$frame&idcat=" . $value->getId() . "&public=" . $value->getCustom('public')) . "#clickedhere\"><img src=\"images/folder_lock.gif\" alt=\"" . i18n("Unprotect category") . "\" title=\"" . i18n("Unprotect category") . "\"></a>");
            }
        } else {
            $tpl->set('d', 'PUBLICBUTTON', '&nbsp;');
        }

        $hasChildren = strNextDeeper($value->getId());
        $hasArticles = strHasArticles($value->getId());
        if (($hasChildren == 0) && ($hasArticles == false) && ($perm->have_perm_area_action($tmp_area, 'str_deletecat') || $perm->have_perm_area_action_item($tmp_area, 'str_deletecat', $value->getId()))) {
            $delete = '<a href="javascript://" onclick="confDel(' . $value->getId() . ',' . $value->getCustom('parentid') . ', \'' . addslashes(conHtmlSpecialChars($value->getName())) . '\')">' . "<img src=\"" . $cfg["path"]["images"] . "delete.gif\" alt=\"" . i18n("Delete category") . "\" title=\"" . i18n("Delete category") . "\"></a>";
            $tpl->set('d', 'DELETEBUTTON', $delete);
        } else {
            $message = i18n("No permission");

            if ($hasChildren) {
                $button = 'delete_inact_h.gif';
                $alt = i18n("One or more subtrees and one or more articles are existing, unable to delete.");
            }

            if ($hasArticles) {
                $button = 'delete_inact_g.gif';
                $alt = i18n("One or more articles are existing, unable to delete.");
            }
            if ($hasChildren && $hasArticles) {
                $button = 'delete_inact.gif';
                $alt = i18n("One or more articles are existing, unable to delete.");
            }

            $tpl->set('d', 'DELETEBUTTON', '<img src="' . $cfg["path"]["images"] . $button . '" alt="' . $alt . '" title="' . $alt . '">');
        }

        if ($perm->have_perm_area_action($tmp_area, 'str_moveupcat') || $perm->have_perm_area_action_item($tmp_area, 'str_moveupcat', $value->getId())) {
            $rand = rand();
            if ($value->getCustom('parentid') == 0 && $value->getCustom('preid') == 0) {
                $tpl->set('d', 'UPBUTTON', '<img src="images/folder_moveup_inact.gif" title="' . i18n("This category is already at the top") . '">');
            } else {
                if ($value->getCustom('preid') != 0) {
                    $tpl->set('d', 'UPBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_moveupcat&frame=$frame&idcat=" . $value->getId() . "&rand=$rand") . "#clickedhere\"><img src=\"images/folder_moveup.gif\" alt=\"" . i18n("Move category up") . "\" title=\"" . i18n("Move category up") . "\"></a>");
                } else {
                    $tpl->set('d', 'UPBUTTON', '<img src="images/folder_moveup_inact.gif" title="' . i18n("This category is already at the top") . '">');
                }
            }
        } else {
            $tpl->set('d', 'UPBUTTON', '<img src="images/folder_moveup_inact.gif">');
        }

        if ($perm->have_perm_area_action($tmp_area, 'str_movedowncat') || $perm->have_perm_area_action_item($tmp_area, 'str_movedowncat', $value->getId())) {
            $rand = rand();
            if ($value->getCustom('postid') == 0) {
                $tpl->set('d', 'DOWNBUTTON', '<img src="images/folder_movedown_inact.gif" title="' . i18n("This category is already at the bottom") . '">');
            } else {
                $tpl->set('d', 'DOWNBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_movedowncat&frame=$frame&idcat=" . $value->getId() . "&rand=$rand") . "#clickedhere\"><img src=\"images/folder_movedown.gif\" alt=\"" . i18n("Move category down") . "\" title=\"" . i18n("Move category down") . "\"></a>");
            }
        } else {
            $tpl->set('d', 'DOWNBUTTON', '<img src="images/folder_movedown_inact.gif">');
        }

        if (($action === 'str_movesubtree') && (!isset($parentid_new))) {
            if ($perm->have_perm_area_action($tmp_area, 'str_movesubtree') || $perm->have_perm_area_action_item($tmp_area, 'str_movesubtree', $value->getId())) {
                if ($value->getId() == $idcat) {
                    $tpl->set('d', 'MOVEBUTTON', "<a name=#movesubtreehere><a href=\"" . $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=0") . "\"><img src=\"" . $cfg["path"]["images"] . "but_move_subtree_main.gif\" alt=\"" . i18n("Move tree") . "\" title=\"" . i18n("Move tree") . "\"></a>");
                } else {
                    $allowed = strMoveCatTargetallowed($value->getId(), $idcat);
                    if ($allowed == 1) {
                        $tpl->set('d', 'MOVEBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=" . $value->getId()) . "\"><img src=\"" . $cfg["path"]["images"] . "but_move_subtree_target.gif\" alt=\"" . i18n("Place tree here") . "\" title=\"" . i18n("Place tree here") . "\"></a>");
                    } else {
                        $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
                    }
                }
            } else {
                $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
            }
        } else {
            if ($perm->have_perm_area_action($tmp_area, 'str_movesubtree') || $perm->have_perm_area_action_item($tmp_area, 'str_movesubtree', $value->getId())) {
                if ($value->getCustom('parentid') != 0) {
                    $tpl->set('d', 'MOVEBUTTON', "<a href=\"" . $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=" . $value->getId()) . "#movesubtreehere\"><img src=\"" . $cfg["path"]["images"] . "but_move_subtree.gif\" alt=\"" . i18n("Move tree") . "\" title=\"" . i18n("Move tree") . "\"></a>");
                } else {
                    $tpl->set('d', 'MOVEBUTTON', '<img src="' . $cfg["path"]["images"] . 'but_move_subtree_grey.png" title="' . i18n("This category can't be moved since it is already a root category") . '">');
                }
            } else {
                $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
            }
        }

        if ($perm->have_perm_area_action('str', 'str_duplicate') || $perm->have_perm_area_action_item('str', 'str_duplicate', $value->getId())) {
            $duplicate = '<a href="javascript://" onclick="confDupl(' . $value->getId() . ',' . $value->getCustom('parentid') . ', \'' . addslashes(conHtmlSpecialChars($value->getName())) . '\')">' . "<img src=\"" . $cfg["path"]["images"] . "folder_duplicate.gif\" alt=\"" . i18n("Duplicate category") . "\" title=\"" . i18n("Duplicate category") . "\"></a>";
            $tpl->set('d', 'DUPLICATEBUTTON', $duplicate);
        } else {
            $tpl->set('d', 'DUPLICATEBUTTON', '&nbsp;');
        }

        // DIRECTION
        cInclude('includes', 'functions.lang.php');
        $tpl->set('d', 'DIRECTION', 'dir="' . langGetTextDirection($lang, $oDirectionDb) . '"');

        $columns = array();

        foreach ($listColumns as $key => $content) {
            $columnContents = array();
            $_cecIterator = $_cecRegistry->getIterator('Contenido.CategoryList.RenderColumn');
            if ($_cecIterator->count() > 0) {
                while ($chainEntry = $_cecIterator->next()) {
                    $columnContents[] = $chainEntry->execute($value->getId(), $key);
                }
            } else {
                $columnContents[] = '';
            }
            $columns[] = '<td class="str-style-d">' . implode("", $columnContents) . '</td>';
        }

        $tpl->set('d', 'ADDITIONALCOLUMNS', implode("", $columns));
        $tpl->next();
    } // end if -> perm
}

$jsDataArray = "";
foreach ($aInlineEditData as $iIdCat => $aData) {
    $aTmp = array();
    foreach ($aData as $aKey => $aValue) {
        $aTmp[] = $aKey . "':'" . addslashes($aValue);
    }
    $jsDataArray .= "
    strDataObj[$iIdCat] = {'" . implode("', '", $aTmp) . "'};";
}

$tpl->set('s', 'JS_DATA', $jsDataArray);

$string = markSubMenuItem(0, true);

// Set DHTML generic Values
$sImagepath = $cfg["path"]["images"];
$tpl->set('s', 'SUM_COLUMNS', 15 + count($listColumns));
$tpl->set('s', 'HREF_ACTION', $sess->url("main.php?frame=$frame"));
$tpl->set('s', 'CON_IMAGES', $backendUrl . $cfg["path"]["images"]);

// Generate input fields for category new layer and category edit layer
$oSession = new cHTMLHiddenField($sess->name, $sess->id);
$oActionEdit = new cHTMLHiddenField('action', 'str_renamecat');
$oIdcat = new cHTMLHiddenField('idcat');

$tpl->set('s', 'INPUT_SESSION', $oSession->render());
$tpl->set('s', 'INPUT_ACTION_EDIT', $oActionEdit->render());
$tpl->set('s', 'INPUT_IDCAT', $oIdcat->render());

$oVisible = new cHTMLHiddenField('visible', 0, 'visible_input');
$oPublic = new cHTMLHiddenField('public', 1, 'public_input');
$oTemplate = new cHTMLHiddenField('idtplcfg', 0, 'idtplcfg_input');

$tpl->set('s', 'INPUT_VISIBLE', $oVisible->render());
$tpl->set('s', 'INPUT_PUBLIC', $oPublic->render());
$tpl->set('s', 'INPUT_TEMPLATE', $oTemplate->render());

$oCatName = new cHTMLTextbox('categoryname', '', '', '', 'cat_categoryname');
$oCatName->setStyle('width:150px; vertical-align:middle;');
$tpl->set('s', 'INPUT_CATNAME_NEW', $oCatName->render());

$oAlias = new cHTMLTextbox('categoryalias', '', '', '', 'cat_categoryalias');
$oAlias->setStyle('width:150px; vertical-align:middle;');
$tpl->set('s', 'INPUT_ALIAS_NEW', $oAlias->render());

$oNewCatName = new cHTMLTextbox('newcategoryname');
$oNewCatName->setStyle('width:150px; vertical-align:middle;');
$tpl->set('s', 'INPUT_CATNAME_EDIT', $oNewCatName->render());

$oNewAlias = new cHTMLTextbox('newcategoryalias');
$oNewAlias->setStyle('width:150px; vertical-align:middle;');
$tpl->set('s', 'INPUT_ALIAS_EDIT', $oNewAlias->render());

$sCategorySelect = buildCategorySelectRights('idcat', '');

// Show Layerbutton for adding new Cateogries and set options according to
// Permisssions
if (($perm->have_perm_area_action($tmp_area, 'str_newtree') || $perm->have_perm_area_action($tmp_area, 'str_newcat') || $bAreaAddNewCategory) && (int) $client > 0 && (int) $lang > 0) {
    $tpl->set('s', 'NEWCAT', $string . '<a class="black" id="new_tree_button" href="javascript:showNewForm();"><img src="images/folder_new.gif">&nbsp;' . i18n('Create new category') . '</a>');
    if ($perm->have_perm_area_action($tmp_area, 'str_newtree')) {
        if ($perm->have_perm_area_action($tmp_area, 'str_newcat') || $bAreaAddNewCategory) {
            $tpl->set('s', 'PERMISSION_NEWTREE', '');
            $oActionNew = new cHTMLHiddenField('action', 'str_newcat', 'cat_new_action');
        } else {
            $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled checked');
            $oActionNew = new cHTMLHiddenField('action', 'str_newcat', 'str_newtree');
        }
        $tpl->set('s', 'INPUT_ACTION_NEW', $oActionNew->render());
        $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'block');
    } else {
        $oActionNew = new cHTMLHiddenField('action', 'str_newcat', 'cat_new_action');
        $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled');
        $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'none');
        $tpl->set('s', 'NEW_ACTION', 'str_newcat');
        $tpl->set('s', 'INPUT_ACTION_NEW', $oActionNew->render());
    }

    if ($perm->have_perm_area_action($tmp_area, 'str_newcat') || $bAreaAddNewCategory) {
        $tpl->set('s', 'CATEGORY_SELECT', $sCategorySelect);
        $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'block');
    } else {
        $tpl->set('s', 'CATEGORY_SELECT', '');
        $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'none');
    }

    if ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')) {
        $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<a href="javascript:showTemplateSelect();"><img src="' . $sImagepath . 'template_properties.gif" id="cat_category_select_button" title="' . i18n('Configure category') . '" alt="' . i18n('Configure category') . '"></a>');
        $tpl->set('s', 'SELECT_TEMPLATE', getTemplateSelect());
    } else {
        $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<img src="' . $sImagepath . 'template_properties_off.gif" id="cat_category_select_button" title="' . i18n('Configure category') . '" alt="' . i18n('Configure category') . '">');
        $tpl->set('s', 'SELECT_TEMPLATE', '');
    }

    if ($perm->have_perm_area_action($tmp_area, 'str_makevisible')) {
        $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<a href="javascript:changeVisible();"><img src="' . $sImagepath . 'offline.gif" id="visible_image" title="' . i18n('Make online') . '" alt="' . i18n('Make online') . '"></a>');
    } else {
        $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<img src="' . $sImagepath . 'offline_off.gif" id="visible_image" title="' . i18n('Make online') . '" alt="' . i18n('Make online') . '">');
    }

    if ($perm->have_perm_area_action($tmp_area, 'str_makepublic')) {
        $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<a href="javascript:changePublic();"><img src="' . $sImagepath . 'folder_delock.gif" id="public_image" title="' . i18n('Protect category') . '" alt="' . i18n('Protect category') . '"></a>');
    } else {
        $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<img src="' . $sImagepath . 'folder_delocked.gif" id="public_image" title="' . i18n('Protect category') . '" alt="' . i18n('Protect category') . '">');
    }
} else {
    $tpl->set('s', 'NEWCAT', $string);

    $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled');
    $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'none');

    $tpl->set('s', 'CATEGORY_SELECT', '');
    $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'none');

    $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '');
    $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '');
    $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '');

    $tpl->set('s', 'NEW_ACTION', 'str_newcat');
    $tpl->set('s', 'SELECT_TEMPLATE', '');
}

// Generate template
$clang = new cApiLanguage($lang);

if ($movesubtreeidcat != 0) {
    if (cString::getStringLength($sMoveSubtreeCatName) > 30) {
        $sLimiter = "...";
    } else {
        $sLimiter = "";
    }
    $sButtonDesc = sprintf(i18n('Cancel moving %s'), '"' . cString::getPartOfString($sMoveSubtreeCatName, 0, 30) . $sLimiter . '"');
    $tpl->set('s', 'CANCEL_MOVE_TREE', '<a class="black" id="cancel_move_tree_button" href="javascript:cancelMoveTree(\'' . $movesubtreeidcat . '\');"><img src="images/but_cancel.gif" alt="' . $sButtonDesc . '">&nbsp;' . $sButtonDesc . '</a>');
} else {
    $tpl->set('s', 'CANCEL_MOVE_TREE', '');
}

$tpl->setEncoding($clang->get("encoding"));
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['str_overview']);

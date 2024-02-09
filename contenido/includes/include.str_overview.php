<?php

/**
 * This file contains the backend page for displaying structure (category tree)
 * overview.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $notification, $parentid, $StrTableClient, $StrTableLang, $currentuser, $tpl;

cInclude('includes', 'functions.lang.php');

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("str_overview");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

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

$tmp_area = 'str';

$db = cRegistry::getDb();
$perm = cRegistry::getPerm();
$action = cRegistry::getAction();
$idcat = cSecurity::toInteger(cRegistry::getCategoryId());
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();
$sess = cRegistry::getSession();
$frame = cRegistry::getFrame();
$_cecRegistry = cApiCecRegistry::getInstance();

strRemakeTreeTable();

// Duplicate category
if ($action == 'str_duplicate' && ($perm->have_perm_area_action('str', 'str_duplicate') || $perm->have_perm_area_action_item('str', 'str_duplicate', $idcat))) {
    strCopyTree($idcat, $parentid);
}

/**
 * Build a category select box containing all categories which the current
 * user is allowed to create new categories.
 *
 * @return string HTML
 *
 * @throws cDbException
 * @throws cException
 */
function buildCategorySelectRights() {
    global $tmp_area;

    $db = cRegistry::getDb();
    $client = cSecurity::toInteger(cRegistry::getClientId());
    $lang = cSecurity::toInteger(cRegistry::getLanguageId());
    $perm = cRegistry::getPerm();

    $oHtmlSelect = new cHTMLSelectElement('idcat', '', 'new_idcat');

    $oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);

    $sql = "SELECT a.idcat AS idcat, b.name AS name, c.level
            FROM " . cRegistry::getDbTableName('cat') . " AS a
            , " . cRegistry::getDbTableName('cat_lang') . " AS b
            , " . cRegistry::getDbTableName('cat_tree') . " AS c
            WHERE a.idclient = " . $client . "
            AND b.idlang = " . $lang . "
            AND b.idcat = a.idcat
            AND c.idcat = a.idcat
            ORDER BY c.idtree";

    $db->query($sql);

    $categories = [];

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
        } elseif ($aValues['level'] < $iLevel) {
            $iLevel = $aValues['level'];
        } else {
            if (!$aValues['perm']) {
                unset($categories[$aValues["idcat"]]);
            }
        }
    }

    foreach ($categories as $tmpidcat => $props) {
        $spaces = cHTMLOptionElement::indent(cSecurity::toInteger($props['level']));
        $sCategoryName = $props['name'];
        $sCategoryName = cString::trimHard($sCategoryName, 30);
        $oHtmlSelectOption = new cHTMLOptionElement($spaces . ">" . conHtmlSpecialChars($sCategoryName), $tmpidcat, false, !$props['perm']);
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
    $area = cRegistry::getArea();
    $sess = cRegistry::getSession();
    $frame = cRegistry::getFrame();
    $cfg = cRegistry::getConfig();

    $selfLink = 'main.php';

    $img = new cHTMLImage();
    $img->updateAttributes([
        'style' => 'padding:4px;'
    ]);

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
        if ($item->isCollapsed()) {
            $expandLink = $sess->url($selfLink . "?area=$area&frame=$frame&expand=" . $item->getId());
            $img->setSrc($item->getCollapsedIcon());
            $img->setAlt(i18n("Open category"));
            return '<a href="' . $expandLink . '">' . $img->render() . '</a>&nbsp;' . '<a href="' . $expandLink . '"' . $title . '>' . conHtmlSpecialChars($catName) . '</a>';
        } else {
            $collapseLink = $sess->url($selfLink . "?area=$area&frame=$frame&collapse=" . $item->getId());
            $img->setSrc($item->getExpandedIcon());
            $img->setAlt(i18n("Close category"));
            return '<a href="' . $collapseLink . '">' . $img->render() . '</a>&nbsp;' . '<a href="' . $collapseLink . '"' . $title . '>' . conHtmlSpecialChars($catName) . '</a>';
        }
    } else {
        return '<img src="' . $cfg['path']['images'] . 'spacer.gif" width="14" height="7" alt="">&nbsp;<span' . $title . '>' . conHtmlSpecialChars($catName) . '</span>';
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
    $db = cRegistry::getDb();
    $client = cSecurity::toInteger(cRegistry::getClientId());

    $oHtmlSelect = new cHTMLSelectElement('cat_template_select', '', 'cat_template_select');

    $oHtmlSelectOption = new cHTMLOptionElement('--- ' . i18n("none") . ' ---', 0, false);
    $oHtmlSelect->appendOptionElement($oHtmlSelectOption);

    $sql = "SELECT idtpl, name, defaulttemplate
            FROM " . cRegistry::getDbTableName('tpl') . "
            WHERE idclient = " . $client . "
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

    $tpl->set('d', 'CANCEL_LINK', '');

    $tpl->set('d', 'DIRECTION', '');
    $tpl->set('d', 'SRC_OK', '');
    $tpl->set('d', 'VALUE_ALIAS_NAME', '');
    $tpl->set('d', 'HEIGHT', 'height:15px;');
    $tpl->set('d', 'BORDER_CLASS', 'str_style_b');

    // content rows
    $additionalColumns = [];
    foreach ($listColumns as $content) {
        $additionalColumns[] = '<td class="no_wrap">&nbsp;</td>';
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

/**
 * Checks once for common str rights and for the right to access a specific category .
 *
 * @param int $idCat
 * @return bool
 * @throws cDbException
 * @throws cException
 */
function hasStrRights(int $idCat): bool
{
    global $tmp_area;

    $perm = cRegistry::getPerm();

    $hasCommonStrRights = cRegistry::getAppVar('str_overview_has_common_str_rights');

    if (is_null($hasCommonStrRights)) {
        // Check for common str rights
        $hasCommonStrRights = (
            $perm->have_perm_area_action($tmp_area, 'str_newtree') ||
            $perm->have_perm_area_action($tmp_area, 'str_newcat') ||
            $perm->have_perm_area_action($tmp_area, 'str_makevisible') ||
            $perm->have_perm_area_action($tmp_area, 'str_makepublic') ||
            $perm->have_perm_area_action($tmp_area, 'str_deletecat') ||
            $perm->have_perm_area_action($tmp_area, 'str_moveupcat') ||
            $perm->have_perm_area_action($tmp_area, 'str_movedowncat') ||
            $perm->have_perm_area_action($tmp_area, 'str_movesubtree') ||
            $perm->have_perm_area_action($tmp_area, 'str_renamecat') ||
            $perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')
        );
        cRegistry::setAppVar('str_overview_has_common_str_rights', $hasCommonStrRights);
    }

    $bCheck = $hasCommonStrRights;
    if (!$hasCommonStrRights) {
        // Check for specific str right
        $bCheck = $perm->have_perm_item($tmp_area, $idCat);
    }

    return $bCheck;
}

/**
 *
 * @param TreeItem $rootItem
 * @param ArrayIterator $itemsIterator
 */
function buildTree(&$rootItem, $itemsIterator) {
    global $nextItem;

    $perm = cRegistry::getPerm();
    $cfg = cRegistry::getConfig();

    while ($itemsIterator->valid()) {
        $key = $itemsIterator->key();
        $item = $itemsIterator->current();
        $itemsIterator->next();

        unset($newItem);

        $hasStrRights = hasStrRights(cSecurity::toInteger($item['idcat']));
        if ($hasStrRights) {
            $newItem = new TreeItem($item['name'], $item['idcat'], true);
        } else {
            $newItem = new TreeItem($item['name'], $item['idcat'], false);
        }

        $newItem->setCollapsedIcon($cfg['path']['images']. 'open_all.gif');
        $newItem->setExpandedIcon($cfg['path']['images'] . 'close_all.gif');
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

        if (is_array($nextItem) && $nextItem['level'] > $item['level']) {
            $oldRoot = $rootItem;
            buildTree($newItem, $itemsIterator);
            $rootItem = $oldRoot;
        }

        if (!is_array($nextItem) || $nextItem['level'] < $item['level']) {
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
            " . cRegistry::getDbTableName('cat_tree') . " AS A,
            " . cRegistry::getDbTableName('cat') . " AS B,
            " . cRegistry::getDbTableName('cat_lang') . " AS C
        WHERE
            A.idcat     = B.idcat AND
            B.idcat     = C.idcat AND
            C.idlang    = " . $lang . " AND
            B.idclient  = " . $client . "
        ORDER BY
            idtree";

$db->query($sql);

/** @var TreeItem[] $treeItemObjects */
$treeItemObjects = [];

$listColumns = [];

if ($db->numRows() == 0) { // If we have no categories, display warning message
    $additionalHeader = $notification->returnNotification(
        "warning",
        i18n("You have no categories for this client. Please create a new root category with your categories. Without categories, you can't create some articles.")
    ) . "<br />";
} else {
    $bIgnore = false;
    $iIgnoreLevel = 0;
    $iCurLevel = 0;
    $iCurParent = 0;

    $items = [];
    while ($db->nextRecord()) {
        $bSkip = false;

        if ($bIgnore == true && $iIgnoreLevel >= $db->f('level')) {
            $bIgnore = false;
        }

        if (isset($movesubtreeidcat) && $db->f('idcat') == $movesubtreeidcat) {
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

        if (!$bIgnore && !$bSkip) {
            $entry = [];
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
    $rootStrItem->setCollapsedIcon($cfg['path']['images'] . 'open_all.gif');
    $rootStrItem->setExpandedIcon($cfg['path']['images'] . 'close_all.gif');

    $arrayObj = new ArrayObject($items);
    buildTree($rootStrItem, $arrayObj->getIterator());

    $expandedList = unserialize($currentuser->getUserProperty('system', 'cat_expandstate'));
    $expandedList = is_array($expandedList) ? $expandedList : [];

    if (isset($expandedList[$client]) && is_array($expandedList[$client])) {
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

    $expandedList[$client] = [];

    $rootStrItem->traverse($treeItemObjects);

    $rootStrItem->getExpandedList($expandedList[$client]);
    $currentuser->setUserProperty('system', 'cat_expandstate', serialize($expandedList));

    // Reset Template
    $tpl->reset();

    $_cecIterator = $_cecRegistry->getIterator('Contenido.CategoryList.Columns');

    if ($_cecIterator->count() > 0) {
        while ($chainEntry = $_cecIterator->next()) {
            $tmpListColumns = $chainEntry->execute([]);
            if (is_array($tmpListColumns)) {
                $listColumns = array_merge($listColumns, $tmpListColumns);
            }
        }

        $additionalHeaders = [];
        foreach ($listColumns as $content) {
            // Header for additional columns
            $additionalHeaders[] = '<th class="header no_wrap">' . $content . '</th>';
        }

        $additionalHeader = implode('', $additionalHeaders);
    } else {
        $additionalHeader = '';
    }
}

$tpl->set('s', 'ADDITIONALHEADERS', $additionalHeader);

// We don't want to show our root
if (count($treeItemObjects)) {
    unset($treeItemObjects[0]);
}

$syncoptions = $syncoptions ?? '';

$selfLink = 'main.php';
$expandLink = $sess->url($selfLink . "?area=$area&frame=$frame&expand=all&syncoptions=$syncoptions");
$collapseLink = $sess->url($selfLink . "?area=$area&frame=$frame&collapse=all&syncoptions=$syncoptions");
$collapseImg = '<a class="con_func_button" href="' . $collapseLink . '" title="' . i18n("Close all categories") . '">
        <img src="' . $cfg['path']['images'] . 'close_all.gif" alt="">&nbsp;' . i18n("Close all categories") . '</a>';
$expandImg = '<a class="con_func_button" href="' . $expandLink . '" title="' . i18n("Open all categories") . '">
        <img src="' . $cfg['path']['images'] . 'open_all.gif" alt="">&nbsp;' . i18n("Open all categories") . '</a>';

$tpl->set('s', 'COLLAPSE_ALL', $collapseImg);
$tpl->set('s', 'EXPAND_ALL', $expandImg);

// Fill inline edit table row
$tpl->set('s', 'SUM_COLUMNS_EDIT', 15 + count($listColumns));
$tpl->set('s', 'ACTION_EDIT_URL', $sess->url("main.php?frame=$frame"));
$tpl->set('s', 'SRC_OK', $backendUrl . $cfg["path"]["images"] . 'but_ok.gif');

$cancelLink = '<a class="con_img_button" href="javascript:void(0)" data-action="cancel_inline_edit"><img src="' . $cfg["path"]["images"] . 'but_cancel.gif" alt=""></a>';
$tpl->set('s', 'CANCEL_LINK', $cancelLink);


$tpl->set('s', 'LABEL_ALIAS_NAME', i18n('Alias'));
$tpl->set('s', 'TEMPLATE_URL', $sess->url("main.php?area=str_tplcfg&frame=$frame"));
$message = addslashes(i18n("Do you really want to duplicate the following category:<br><br><b>%s</b><br><br>Notice: The duplicate process can take up to several minutes, depending on how many subitems and articles you've got."));
$tpl->set('s', 'DUPLICATE_MESSAGE', $message);
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following category:<br><br><b>%s</b>"));
$tpl->set('s', 'OK', i18n('OK'));
$tpl->set('s', 'CANCEL', i18n('Cancel'));
$tpl->set('s', 'MOVE_CONFIRMATION', i18n('Do you really want to move the category?'));

$bAreaAddNewCategory = false;

$aInlineEditData = [];

$sql = "SELECT `idtplcfg`, `idtpl` FROM `%s`";
$db->query($sql, cRegistry::getDbTableName('tpl_conf'));
$aTplConfigs = [];
while ($db->nextRecord()) {
    $aTplConfigs[$db->f('idtplcfg')] = $db->f('idtpl');
}

$sql = "SELECT `name`, `description`, `idtpl` FROM `%s`";
$db->query($sql, cRegistry::getDbTableName('tpl'));
$aTemplates = [];
while ($db->nextRecord()) {
    $aTemplates[$db->f('idtpl')] = [
        'name' => $db->f('name'),
        'description' => $db->f('description') ?? ''
    ];
}

$spacerButton = '<img class="con_img_button_off" src="' . $cfg["path"]["images"] . 'spacer.gif" alt="" title="">';

$languageDirection = langGetTextDirection($lang, cRegistry::getDb());

$lngEditCategory = i18n("Edit category");
$lngMakeOffline = i18n("Make offline");
$lngMakeOnline = i18n("Make online");
$lngProtectCategory = i18n("Protect category");
$lndUnprotectCategory = i18n("Unprotect category");
$lngDeleteCategory = i18n("Delete category");
$lngTemplateNone = '--- ' . i18n("none") . ' ---';
$lngNoPermissions = i18n("No permission");
$lngUnableToDeleteReasonSubtreeArticleMsg = i18n("One or more subtrees and one or more articles are existing, unable to delete.");
$lngUnableToDeleteReasonArticleMsg = i18n("One or more articles are existing, unable to delete.");
$lngMoveCategoryUp = i18n("Move category up");
$lngMoveCategoryDown = i18n("Move category down");
$lngMoveTree = i18n("Move tree");
$lngPlaceTreeHere = i18n("Place tree here");
$lngDuplicateCategory = i18n("Duplicate category");
$lngCategoryAtTheTopMsg = i18n("This category is already at the top");
$lngCategoryAtTheBottomMsg = i18n("This category is already at the bottom");
$lngRootCategoryCantBeMovedMsg = i18n("This category can't be moved since it is already a root category");

foreach ($treeItemObjects as $key => $value) {
    // check if there is any permission for this $idcat in the mainarea 6
    // (=str) and there subareas

    $hasStrRights = hasStrRights(cSecurity::toInteger($value->getId()));
    if (!$hasStrRights) {
        $hasStrRights = $value->isCustomAttributeSet("forcedisplay");
    }

    if ($hasStrRights) {
        // Insert empty row
        if ($value->getCustom('level') == 0 && $value->getCustom('preid') != 0) {
            insertEmptyStrRow($listColumns);
        }

        $tpl->set('d', 'BGCOLOR', '#FFFFFF');
        $tpl->set('d', 'BGCOLOR_EDIT', '#F1F1F1');
        $tpl->set('d', 'HEIGHT', 'height:25px');
        $tpl->set('d', 'BORDER_CLASS', 'str_style_c align_middle tooltip');

        $tpl->set('d', 'INDENT', ($value->getCustom('level') * 16) . "px");
        $sCategoryName = $value->getName();
        if (cString::getStringLength($value->getName()) > 30) {
            $sCategoryName = cString::trimHard($sCategoryName, 30);
        }

        // $tpl->set('d', 'CATEGORY', $sCategoryName);
        if (cString::getStringLength($value->getName()) > 30) {
            $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', 'title="' . htmlspecialchars(cSecurity::unFilter($value->getName())) . '" class="tooltip"');
        } else {
            $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', '');
        }

        $tpl->set('d', 'COLLAPSE_CATEGORY_NAME', getStrExpandCollapseButton($value, $sCategoryName));
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

        $_idTplCfg = $value->getCustom('idtplcfg');
        if (!empty($_idTplCfg) && isset($aTplConfigs[$_idTplCfg]) && isset($aTemplates[$aTplConfigs[$_idTplCfg]])) {
            $template = $aTemplates[$aTplConfigs[$_idTplCfg]]['name'] ?? '';
            $templateDescription = $aTemplates[$aTplConfigs[$_idTplCfg]]['description'] ?? '';
        } else {
            $template = '';
            $templateDescription = '';
        }

        if ($template == '') {
            $template = $lngTemplateNone;
        }

        // Description for hover effect
        $descString = '<b>' . $template . '</b>';
        if (cString::getStringLength($templateDescription) > 0) {
            $descString .= ': <br>' . $templateDescription;
        }

        $sTemplateName = $template;
        if (cString::getStringLength($template) > 20) {
            $sTemplateName = cString::trimHard($sTemplateName, 20);
        }

        $tpl->set('d', 'TPLNAME', $sTemplateName);
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

        $aRecord = [];
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

        if ($perm->have_perm_area_action($tmp_area, 'str_newcat') || $perm->have_perm_area_action_item($tmp_area, 'str_newcat', $value->getId())) {
            $bAreaAddNewCategory = true;
        }

        $tpl->set('d', 'CATID', $value->getId());
        $tpl->set('d', 'PARENTID', $value->getCustom('parentid'));
        $tpl->set('d', 'POSTID', $value->getCustom('postid'));
        $tpl->set('d', 'PREID', $value->getCustom('preid'));
        $tpl->set('d', 'LEVEL', $value->getCustom('level'));

        // Direction
        $tpl->set('d', 'DIRECTION', 'dir="' . $languageDirection . '"');

        // Mouseover title
        $descStringEncoded = conHtmlentities(strip_tags($descString), ENT_QUOTES, cRegistry::getEncoding());
        if (cString::getStringLength($descStringEncoded) > 20) {
            $title = 'title="' . $descStringEncoded . '"';
        } else {
            $title = '';
        }
        $tpl->set('d', 'SHOW_MOUSEOVER', $title);

        // Button: Rename/edit category
        if ($perm->have_perm_area_action($area, "str_renamecat") || $perm->have_perm_area_action_item($area, "str_renamecat", $value->getId())) {
            $button = '<a class="con_img_button" href="javascript:void(0)" data-action="display_inline_edit" data-id="' . $value->getId() . '" title="' . $lngEditCategory . '">'
                . '<img src="' . $cfg["path"]["images"] . 'but_todo.gif" id="cat_' . $value->getId() . '_image" alt="' . $lngEditCategory . '" title="' . $lngEditCategory . '">'
                . '</a>';
        } else {
            $button = $spacerButton;
        }
        $tpl->set('d', 'RENAMEBUTTON', $button);

        // Button: Online/Offline
        if ($perm->have_perm_area_action($tmp_area, 'str_makevisible') || $perm->have_perm_area_action_item($tmp_area, 'str_makevisible', $value->getId())) {
            $href = $sess->url("main.php?area=$area&action=str_makevisible&frame=$frame&idcat=" . $value->getId() . '&visible=' . $value->getCustom('visible')) . '#clickedhere';
            if ($value->getCustom('visible') == 1) {
                $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngMakeOffline . '"><img src="' . $cfg['path']['images'] . 'online.gif" alt="' . $lngMakeOffline . '" title="' . $lngMakeOffline . '"></a>';
            } else {
                $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngMakeOnline . '"><img src="' . $cfg['path']['images'] . 'offline.gif" alt="' . $lngMakeOnline . '" title="' . $lngMakeOnline . '"></a>';
            }
        } else {
            $button = $spacerButton;
        }

        $tpl->set('d', 'VISIBLEBUTTON', $button);

        // Button: Public access
        if ($perm->have_perm_area_action($tmp_area, 'str_makepublic') || $perm->have_perm_area_action_item($tmp_area, 'str_makepublic', $value->getId())) {
            $href = $sess->url("main.php?area=$area&action=str_makepublic&frame=$frame&idcat=" . $value->getId() . '&public=' . $value->getCustom('public')) . '#clickedhere';
            if ($value->getCustom('public') == 1) {
                $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngProtectCategory . '"><img src="' . $cfg['path']['images'] . 'folder_delock.gif" alt="' . $lngProtectCategory . '" title="' . $lngProtectCategory . '"></a>';
            } else {
                $button = '<a class="con_img_button" href="' . $href . '" title="' . $lndUnprotectCategory . '"><img src="' . $cfg['path']['images'] . 'folder_lock.gif" alt="' . $lndUnprotectCategory . '" title="' . $lndUnprotectCategory . '"></a>';
            }
        } else {
            $button = $spacerButton;
        }
        $tpl->set('d', 'PUBLICBUTTON', $button);

        // Button: Delete
        $hasChildren = strNextDeeper($value->getId());
        $hasArticles = strHasArticles($value->getId());
        if (($hasChildren == 0) && !$hasArticles && ($perm->have_perm_area_action($tmp_area, 'str_deletecat') || $perm->have_perm_area_action_item($tmp_area, 'str_deletecat', $value->getId()))) {
            $button = '<a class="con_img_button" href="javascript:void(0)" data-action="str_deletecat" data-name="' . addslashes(conHtmlSpecialChars($value->getName())) . '" title="' . $lngDeleteCategory . '">'
                . '<img src="' . $cfg["path"]["images"] . 'delete.gif" alt="' . $lngDeleteCategory . '" title="' . $lngDeleteCategory . '">'
                . '</a>';
        } else {
            $alt = $lngNoPermissions;

            if ($hasChildren && $hasArticles) {
                $button = 'delete_inact.gif';
                $alt = $lngUnableToDeleteReasonArticleMsg;
            } elseif ($hasChildren) {
                $button = 'delete_inact_h.gif';
                $alt = $lngUnableToDeleteReasonSubtreeArticleMsg;
            } elseif ($hasArticles) {
                $button = 'delete_inact_g.gif';
                $alt = $lngUnableToDeleteReasonArticleMsg;
            }

            $button = '<img class="con_img_button_off" src="' . $cfg["path"]["images"] . $button . '" alt="' . $alt . '" title="' . $alt . '">';
        }
        $tpl->set('d', 'DELETEBUTTON', $button);

        // Button: Move up
        if ($perm->have_perm_area_action($tmp_area, 'str_moveupcat') || $perm->have_perm_area_action_item($tmp_area, 'str_moveupcat', $value->getId())) {
            $rand = rand();
            if ($value->getCustom('parentid') == 0 && $value->getCustom('preid') == 0) {
                $button = '<img class="con_img_button_off" src="' . $cfg['path']['images'] . 'folder_moveup_inact.gif" title="' . $lngCategoryAtTheTopMsg . '">';
            } else {
                if ($value->getCustom('preid') != 0) {
                    $href = $sess->url("main.php?area=$area&action=str_moveupcat&frame=$frame&idcat=" . $value->getId() . "&rand=$rand") . '#clickedhere';
                    $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngMoveCategoryUp . '"><img src="' . $cfg['path']['images'] . 'folder_moveup.gif" alt="' . $lngMoveCategoryUp . '" title="' . $lngMoveCategoryUp . '"></a>';
                } else {
                    $button ='<img class="con_img_button_off" src="' . $cfg['path']['images'] . 'folder_moveup_inact.gif" title="' . $lngCategoryAtTheTopMsg . '">';
                }
            }
        } else {
            $button = '<img class="con_img_button_off" src="' . $cfg['path']['images'] . 'folder_moveup_inact.gif" alt="">';
        }
        $tpl->set('d', 'UPBUTTON', $button);

        // Button: Move down
        if ($perm->have_perm_area_action($tmp_area, 'str_movedowncat') || $perm->have_perm_area_action_item($tmp_area, 'str_movedowncat', $value->getId())) {
            $rand = rand();
            if ($value->getCustom('postid') == 0) {
                $button = '<img src="' . $cfg['path']['images'] . 'folder_movedown_inact.gif" title="' . $lngCategoryAtTheBottomMsg . '">';
            } else {
                $href = $sess->url("main.php?area=$area&action=str_movedowncat&frame=$frame&idcat=" . $value->getId() . "&rand=$rand") . '#clickedhere';
                $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngMoveCategoryDown . '"><img src="' . $cfg['path']['images'] . 'folder_movedown.gif" alt="' . $lngMoveCategoryDown . '" title="' . $lngMoveCategoryDown . '"></a>';
            }
        } else {
            $button = '<img class="con_img_button_off" src="' . $cfg['path']['images'] . 'folder_movedown_inact.gif" alt="">';
        }
        $tpl->set('d', 'DOWNBUTTON', $button);

        // Button: Move sub tree
        if (($action === 'str_movesubtree') && (!isset($parentid_new))) {
            if ($perm->have_perm_area_action($tmp_area, 'str_movesubtree') || $perm->have_perm_area_action_item($tmp_area, 'str_movesubtree', $value->getId())) {
                if ($value->getId() == $idcat) {
                    $href =  $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=0");
                    $button = '<a id="#movesubtreehere" class="con_img_button" href="' . $href . '" title="' . $lngMoveTree . '"><img src="' . $cfg["path"]["images"] . 'but_move_subtree_main.gif" alt="' . $lngMoveTree . '" title="' . $lngMoveTree . '"></a>';
                } else {
                    $allowed = strMoveCatTargetallowed($value->getId(), $idcat);
                    if ($allowed == 1) {
                        $href = $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=" . $value->getId());
                        $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngPlaceTreeHere . '"><img src="' . $cfg["path"]["images"] . 'but_move_subtree_target.gif" alt="' . $lngPlaceTreeHere . '" title="' . $lngPlaceTreeHere . '"></a>';
                    } else {
                        $button = $spacerButton;
                    }
                }
            } else {
                $button = $spacerButton;
            }
        } else {
            if ($perm->have_perm_area_action($tmp_area, 'str_movesubtree') || $perm->have_perm_area_action_item($tmp_area, 'str_movesubtree', $value->getId())) {
                if ($value->getCustom('parentid') != 0) {
                    $href = $sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=" . $value->getId()) . '#movesubtreehere';
                    $button = '<a class="con_img_button" href="' . $href . '" title="' . $lngMoveTree . '" title="' . $lngMoveTree . '"><img src="' . $cfg["path"]["images"] . 'but_move_subtree.gif" alt="' . $lngMoveTree . '" title="' . $lngMoveTree . '"></a>';
                } else {
                    $button = '<img class="con_img_button_off" src="' . $cfg["path"]["images"] . 'but_move_subtree_grey.png" title="' . $lngRootCategoryCantBeMovedMsg . '">';
                }
            } else {
                $button = $spacerButton;
            }
        }
        $tpl->set('d', 'MOVEBUTTON', $button);

        // Button: Duplicate
        if ($perm->have_perm_area_action('str', 'str_duplicate') || $perm->have_perm_area_action_item('str', 'str_duplicate', $value->getId())) {
            $button = '<a class="con_img_button" href="javascript:void(0)"  data-action="str_duplicate" data-name="' . addslashes(conHtmlSpecialChars($value->getName())) . '" title="' . $lngDuplicateCategory . '">'
                . '<img src="' . $cfg["path"]["images"] . 'folder_duplicate.gif" alt="' . $lngDuplicateCategory . '" title="' . $lngDuplicateCategory . '">'
                . '</a>';
        } else {
            $button = $spacerButton;
        }
        $tpl->set('d', 'DUPLICATEBUTTON', $button);

        // Additional columns
        $columns = [];
        foreach ($listColumns as $cKey => $content) {
            $columnContents = [];
            $_cecIterator = $_cecRegistry->getIterator('Contenido.CategoryList.RenderColumn');
            if ($_cecIterator->count() > 0) {
                while ($chainEntry = $_cecIterator->next()) {
                    $columnContents[] = $chainEntry->execute($value->getId(), $cKey);
                }
            } else {
                $columnContents[] = '';
            }
            $columns[] = '<td class="str_style_d">' . implode("", $columnContents) . '</td>';
        }
        $tpl->set('d', 'ADDITIONALCOLUMNS', implode("", $columns));

        $tpl->next();
    }
}

$jsDataArray = "";
foreach ($aInlineEditData as $iIdCat => $aData) {
    $aTmp = [];
    foreach ($aData as $aKey => $aValue) {
        $aTmp[] = $aKey . "':'" . addslashes($aValue);
    }
    $jsDataArray .= "
    strDataObj[$iIdCat] = {'" . implode("', '", $aTmp) . "'};";
}

$tpl->set('s', 'JS_DATA', $jsDataArray);

$tpl->set('s', 'JS_MARK_SUBMENU_ITEM', markSubMenuItem(0, true));

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
$oCatName->setStyle('width:150px;');
$tpl->set('s', 'INPUT_CATNAME_NEW', $oCatName->render());

$oAlias = new cHTMLTextbox('categoryalias', '', '', '', 'cat_categoryalias');
$oAlias->setStyle('width:150px;');
$tpl->set('s', 'INPUT_ALIAS_NEW', $oAlias->render());

$oNewCatName = new cHTMLTextbox('newcategoryname');
$oNewCatName->setStyle('width:150px;');
$tpl->set('s', 'INPUT_CATNAME_EDIT', $oNewCatName->render());

$oNewAlias = new cHTMLTextbox('newcategoryalias');
$oNewAlias->setStyle('width:150px;');
$tpl->set('s', 'INPUT_ALIAS_EDIT', $oNewAlias->render());

// Show layer-button for adding new categories and set options
// according to permissions
if (($perm->have_perm_area_action($tmp_area, 'str_newtree') || $perm->have_perm_area_action($tmp_area, 'str_newcat') || $bAreaAddNewCategory) && $client > 0 && $lang > 0) {
    $link = '<a id="new_tree_button" class="con_func_button" href="javascript:void(0)" data-action="show_new_form"><img src="' . $cfg['path']['images'] . 'folder_new.gif" alt="">&nbsp;' . i18n('Create new category') . '</a>';
    $tpl->set('s', 'NEWCAT', $link);
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
        $tpl->set('s', 'CATEGORY_SELECT', buildCategorySelectRights());
        $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'block');
    } else {
        $tpl->set('s', 'CATEGORY_SELECT', '');
        $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'none');
    }

    if ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')) {
        $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<a href="javascript:showTemplateSelect();" title="' . i18n('Configure category') . '"><img src="' . $sImagepath . 'template_properties.gif" id="cat_category_select_button" title="' . i18n('Configure category') . '" alt="' . i18n('Configure category') . '"><span>' . i18n('Configure category') . '</span></a>');
        $tpl->set('s', 'SELECT_TEMPLATE', getTemplateSelect());
    } else {
        $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<img src="' . $sImagepath . 'template_properties_off.gif" id="cat_category_select_button" title="' . i18n('Configure category') . '" alt="' . i18n('Configure category') . '"><span>' . i18n('Configure category') . '</span><');
        $tpl->set('s', 'SELECT_TEMPLATE', '');
    }

    if ($perm->have_perm_area_action($tmp_area, 'str_makevisible')) {
        $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<a href="javascript:changeVisible();"><img src="' . $sImagepath . 'offline.gif" id="visible_image" title="' . i18n('Make online') . '" alt="' . i18n('Make online') . '"><span id="visible_label">' . i18n('Make online') . '</span></a>');
    } else {
        $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<img src="' . $sImagepath . 'offline_off.gif" id="visible_image" title="' . i18n('Make online') . '" alt="' . i18n('Make online') . '"><span id="visible_label">' . i18n('Make online') . '</span>');
    }

    if ($perm->have_perm_area_action($tmp_area, 'str_makepublic')) {
        $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<a href="javascript:changePublic();"><img src="' . $sImagepath . 'folder_delock.gif" id="public_image" title="' . i18n('Protect category') . '" alt="' . i18n('Protect category') . '"><span class="public_label">' . i18n('Protect category') . '</span></a>');
    } else {
        $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<img src="' . $sImagepath . 'folder_delocked.gif" id="public_image" title="' . i18n('Protect category') . '" alt="' . i18n('Protect category') . '"><span class="public_label">' . i18n('Protect category') . '</span>');
    }
} else {
    $tpl->set('s', 'NEWCAT', '');

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

if (isset($movesubtreeidcat) && $movesubtreeidcat != 0) {
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

// Page end
$additionalPageEnd = [];
/**
 * @since CONTENIDO 4.10.2 - CEC Hook 'Contenido.CategoryList.PageEnd'
 */
$_cecIterator = $_cecRegistry->getIterator('Contenido.CategoryList.PageEnd');
if ($_cecIterator->count() > 0) {
    while ($chainEntry = $_cecIterator->next()) {
        $additionalPageEnd[] = $chainEntry->execute($value->getId(), $cKey);
    }
}
$tpl->set('s', 'ADDITIONAL_PAGE_END', implode("\n", $additionalPageEnd));

$tpl->setEncoding($clang->get("encoding"));
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['str_overview']);

<?php

/**
 * This file contains the menu frame (overview) backend page for module management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oPage = new cGuiPage('mod_overview');

// display critical error if no valid client is selected
if (cSecurity::toInteger($client) < 1) {
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$requestIdMod = (isset($_REQUEST['idmod'])) ? cSecurity::toInteger($_REQUEST['idmod']) : 0;

// Now build bottom with list
$cApiModuleCollection = new cApiModuleCollection();
$cApiModule = new cApiModule();
$searchOptions = [];

// no value found in request for items per page -> get form db or set default
$oUser = new cApiUser($auth->auth['uid']);
if (!isset($_REQUEST['elemperpage']) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) {
    $_REQUEST['elemperpage'] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST['elemperpage'])) {
    $_REQUEST['elemperpage'] = 0;
}
if ($_REQUEST['elemperpage'] > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back to something more useful
    $oUser->setProperty("itemsperpage", $area, $_REQUEST['elemperpage']);
}
unset($oUser);

if (!isset($_REQUEST['page']) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST['elemperpage'] == 0) {
    $_REQUEST['page'] = 1;
}


// Build list for left_bottom considering filter values
$cGuiMenu = new cGuiMenu();
$sOptionModuleCheck = getSystemProperty("system", "modulecheck");
$sOptionForceCheck = getEffectiveSetting("modules", "force-menu-check", "false");
$iMenu = 0;

$searchOptions['elementPerPage'] = $_REQUEST['elemperpage'];

$searchOptions['orderBy'] = 'name';
if ($_REQUEST['sortby'] == 'type') {
    $searchOptions['orderBy'] = 'type';
}

$searchOptions['sortOrder'] = 'asc';
if ($_REQUEST['sortorder'] == "desc") {
    $searchOptions['sortOrder'] = 'desc';
}

$searchOptions['moduleType'] = '%%';
if ($_REQUEST['filtertype'] == '--wotype--') {
    $searchOptions['moduleType'] = '';
}

if (!empty($_REQUEST['filtertype']) && $_REQUEST['filtertype'] != '--wotype--' && $_REQUEST['filtertype'] != '--all--') {
    $searchOptions['moduleType'] = $db->escape($_REQUEST['filtertype']);
}

$searchOptions['filter'] = $db->escape($_REQUEST['filter']);

//search in
$searchOptions['searchIn'] = 'all';
if ($_REQUEST['searchin'] == 'name' || $_REQUEST['searchin'] == 'description' || $_REQUEST['searchin'] == 'type' || $_REQUEST['searchin'] == 'input' || $_REQUEST['searchin'] == 'output') {
    $searchOptions['searchIn'] = $_REQUEST['searchin'];
}

$searchOptions['selectedPage'] = $_REQUEST['page'];

$cModuleSearch = new cModuleSearch($searchOptions);

$allModules = $cModuleSearch->getModules();

if ($_REQUEST['elemperpage'] > 0) {
    $iItemCount = $cModuleSearch->getModulCount();
} else {
    $iItemCount = 0;
}

foreach ($allModules as $idmod => $module) {

    if ($perm->have_perm_item($area, $idmod) ||
        $perm->have_perm_area_action("mod_translate", "mod_translation_save") ||
        $perm->have_perm_area_action_item("mod_translate", "mod_translation_save", $idmod)
    ) {

        $link = new cHTMLLink();
        $link->setClass('show_item')
            ->setLink('javascript:;')
            ->setAttribute('data-action', 'show_module');

        $moduleName = (cString::getStringLength(trim($module['name'])) > 0) ? $module['name'] : i18n("- Unnamed module -");
        $sName = mb_convert_encoding(cString::stripSlashes(conHtmlSpecialChars($moduleName)), cRegistry::getLanguage()->get('encoding')); //$cApiModule->get("name");
        $descr = mb_convert_encoding(cString::stripSlashes(str_replace("'", "&#39;", conHtmlSpecialChars(nl2br($module ['description'])))), cRegistry::getLanguage()->get('encoding'));

        // Do not check modules (or don't force it) - so, let's take a look into the database
        $sModuleError = $module['error']; //$cApiModule->get("error");

        if ($sModuleError == "none") {
            $colName = $sName;
        } else if ($sModuleError == "input" || $sModuleError == "output") {
            $colName = '<span class="moduleError">' . $sName . '</span>';
        } else {
            $colName = '<span class="moduleCriticalError">' . $sName . '</span>';
        }

        $iMenu++;

        $cGuiMenu->setTitle($iMenu, $colName);
        $cGuiMenu->setId($iMenu, $idmod);
        $cGuiMenu->setTooltip($iMenu, ($descr == "") ? '' : $descr);
        if ($perm->have_perm_area_action_item("mod_edit", "mod_edit", $idmod) ||
            $perm->have_perm_area_action_item("mod_translate", "mod_translation_save", $idmod)) {
            $cGuiMenu->setLink($iMenu, $link);
        }

        $inUse = $cApiModule->moduleInUse($idmod);

        $deleteLink = "";

        if ($inUse) {
            $inUseString = i18n("For more information about usage click on this button");
            $inUseLink = '<a href="javascript:;" data-action="inused_module">'
                       . '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'exclamation.gif" title="' . $inUseString . '" alt="' . $inUseString . '"></a>';
            $delDescription = i18n("Module can not be deleted, because it is already in use!");
        } else {
            $inUseLink = '<img class="vAlignMiddle" src="./images/spacer.gif" alt="" width="16">';
            if ($perm->have_perm_area_action_item('mod', 'mod_delete', $idmod)) {
                if (getEffectiveSetting('client', 'readonly', 'false') == 'true') {
                    $delTitle = i18n('This area is read only! The administrator disabled edits!');
                    $deleteLink = '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete_inact.gif" title="' . $delTitle . '" alt="' . $delTitle . '">';
                } else {
                    $delTitle = i18n("Delete module");
                    $deleteLink = '<a href="javascript:;" data-action="delete_module" title="' . $delTitle . '">'
                                . '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';
                }
            } else {
                $delDescription = i18n("No permissions");
            }
        }

        if ($deleteLink == "") {
            $deleteLink = '<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete_inact.gif" title="' . $delDescription . '" alt="' . $delDescription . '">';
        }

        $todo = new TODOLink("idmod", $idmod, "Module: $sName", "");

        $cGuiMenu->setActions($iMenu, 'inuse', $inUseLink);
        $cGuiMenu->setActions($iMenu, 'todo', $todo->render());
        $cGuiMenu->setActions($iMenu, 'delete', $deleteLink);

        if ($requestIdMod == $idmod) {
            $cGuiMenu->setMarked($iMenu);
        }
    }
}

$oPage->addScript("cfoldingrow.js");
$oPage->addScript("parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e");
$oPage->set("s", "FORM", $cGuiMenu->render(false));
$oPage->set("s", "DELETE_MESSAGE", i18n("Do you really want to delete the following module:<br /><br />%s<br />"));

//generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$pagerl = "pagerlink";
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom('elemperpage', $elemperpage);
$oPagerLink->setCustom("filter", stripslashes($_REQUEST["filter"]));
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cGuiObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $_REQUEST['elemperpage'], $_REQUEST['page'], $oPagerLink, 'page', $pagerl);

//add slashes, to insert in javascript
$sPagerContent = $oPager->render(true);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);
$oPage->set('s', 'PAGER_CONTENT', $sPagerContent);

$oPage->render();

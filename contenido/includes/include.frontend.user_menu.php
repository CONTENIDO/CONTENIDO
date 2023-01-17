<?php

/**
 * This file contains the menu frame backend page in frontend user management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cfg = cRegistry::getConfig();
$sess = cRegistry::getSession();
$auth = cRegistry::getAuth();
$client = cRegistry::getClientId();
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();

$oPage = new cGuiPage("frontend.user_menu");

$oUser = new cApiUser($auth->auth["uid"]);

$requestElemPerPage = cSecurity::toInteger($_REQUEST['elemperpage'] ?? '0');
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestFilter = $_REQUEST['filter'] ?? '';
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestSortOrder = $_REQUEST['sortorder'] ?? '';
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestRestrictGroup = $_REQUEST['restrictgroup'] ?? '';
$requestFrontendUser = $_GET['frontenduser'] ?? '';

// Set default values
if ($requestElemPerPage <= 0) {
    $requestElemPerPage = cSecurity::toInteger($oUser->getProperty("itemsperpage", $area));
}
if (!is_numeric($requestElemPerPage)) {
    $requestElemPerPage = 25;
}
$oUser->setProperty("itemsperpage", $area, $requestElemPerPage);

if ($requestPage <= 0 || $requestElemPerPage == 0) {
    $requestPage = 1;
}

$aFieldSources = [
    "username" => "base",
    "created" => "created",
    "modified" => "modified"
];

$aElementsPerPage = [
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
];

$aSortOrderOptions = [
    "asc" => i18n("Ascending"),
    "desc" => i18n("Descending")
];

$bUsePlugins = getEffectiveSetting("frontendusers", "pluginsearch", "true");
$bUsePlugins = ($bUsePlugins == "false") ? false : true;

$oFEUsers = new cApiFrontendUserCollection();

$databaseFields = [];

// query the collection and fetch the first available item
$oFEUsers->query();
$sampleItem = $oFEUsers->next();

// fetch available fields from database item
if ($sampleItem) {
    $databaseFields = array_keys($sampleItem->toArray());
}

if ($bUsePlugins == true && cHasPlugins('frontendusers')) {
    cIncludePlugins('frontendusers');

    $_sValidPlugins = getEffectiveSetting('frontendusers', 'pluginsearch_valid_plugins', '');
    $_aValidPlugins = [];

    if (cString::getStringLength($_sValidPlugins) > 0) {
        $_aValidPlugins = explode(',', $_sValidPlugins);
    }

    $_iCountValidPlugins = sizeof($_aValidPlugins);

    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        if ($_iCountValidPlugins == 0 || in_array($plugin, $_aValidPlugins)) {
            if (function_exists('frontendusers_' . $plugin . '_wantedVariables')
                && function_exists('frontendusers_' . $plugin . '_canonicalVariables')
                && function_exists('frontendusers_' . $plugin . '_getvalue')) {

                $aVariableNames = call_user_func('frontendusers_' . $plugin . '_canonicalVariables');

                if (is_array($aVariableNames)) {
                    foreach ($aVariableNames as $sVariableName => $name) {
                        if (in_array($sVariableName, $databaseFields)) {
                            $aFieldSources[$sVariableName] = $plugin;
                        }
                    }
                }
            }
        }
    }
}

$oFEUsers->setWhere("cApiFrontendUserCollection.idclient", $client);

if (cString::getStringLength($requestFilter) > 0) {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFieldSources as $variableName => $source) {
            $oFEUsers->setWhereGroup("filter", $variableName, $requestFilter, "LIKE");
        }

        $oFEUsers->setInnerGroupCondition("filter", "OR");
    } else {
        $searchField = 'username';
        if (in_array($requestSearchIn, $databaseFields)) {
            $searchField = $requestSearchIn;
        }

        $oFEUsers->setWhere("cApiFrontendUserCollection." . $searchField, $requestFilter, "LIKE");
    }
}

if ($requestRestrictGroup != "" && $requestRestrictGroup != "--all--") {
    $oFEUsers->link("cApiFrontendGroupMemberCollection", 'idfrontenduser');
    $oFEUsers->setWhere("cApiFrontendGroupMemberCollection.idfrontendgroup", $requestRestrictGroup);
}

$mPage = $requestPage;

$oFEUsers->query();
$fullTableCount = $oFEUsers->count();

if (cString::getStringLength($requestSortBy) !== 0 && in_array($requestSortBy, array_keys($aFieldSources))) {
    $sortBy = $requestSortBy;
} else {
    $sortBy = 'username';
}

if (cString::getStringLength($requestSortOrder) !== 0 && in_array($requestSortOrder, array_keys($aSortOrderOptions))) {
    $sortOrder = $requestSortOrder;
} else {
    $sortOrder = 'asc';
}

$oFEUsers->setOrder("cApiFrontendUserCollection." . $sortBy . " " . $sortOrder);
$oFEUsers->setLimit($requestElemPerPage * ($mPage - 1), $requestElemPerPage);

if ($requestElemPerPage * ($mPage) >= $fullTableCount + $requestElemPerPage && $mPage != 1) {
    $mPage--;
}

$oFEUsers->query();

$aUserTable = [];

while ($feuser = $oFEUsers->next()) {
    foreach ($aFieldSources as $key => $field) {
        $idfrontenduser = $feuser->get("idfrontenduser");

        $aUserTable[$idfrontenduser]['idfrontenduser'] = $idfrontenduser;

        switch ($field) {
            case "base":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("username");
                break;
            case "created":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("created");
                break;
            case "modified":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("modified");
                break;
            default:
                if ($requestFilter != "") {
                    $aUserTable[$idfrontenduser][$key] = call_user_func("frontendusers_" . $field . "_getvalue", $key);
                }
                break;
        }
    }
}

$cGuiMenu = new cGuiMenu();
$iMenu = 0;

foreach ($aUserTable as $mkey => $params) {
    $idfrontenduser = $params["idfrontenduser"];
    $link = new cHTMLLink();
    $link->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'show_frontenduser');

    $iMenu++;

    $delTitle = i18n("Delete user");
    $deleteLink = '
        <a href="javascript:;" data-action="delete_frontenduser" title="' . $delTitle . '">
            <img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '">
        </a>';

    $cGuiMenu->setId($iMenu, $idfrontenduser);
    $cGuiMenu->setTitle($iMenu, conHtmlentities($params["username"]));
    $cGuiMenu->setLink($iMenu, $link);
    $cGuiMenu->setActions($iMenu, "delete", $deleteLink);
    $cGuiMenu->setImage($iMenu, "");

    if ($requestFrontendUser == $idfrontenduser) {
        $cGuiMenu->setMarked($iMenu);
    }
}

$oPage->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');

$message = i18n("Do you really want to delete the user %s?");
$oPage->set("s", "DELETE_MESSAGE", $message);

// generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $requestElemPerPage);
$oPagerLink->setCustom("filter", $requestFilter);
$oPagerLink->setCustom("sortby", $requestSortBy);
$oPagerLink->setCustom("sortorder", $requestSortOrder);
$oPagerLink->setCustom("searchin", $requestSearchIn);
$oPagerLink->setCustom("restrictgroup", $requestRestrictGroup);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagingLink = "paginglink";
$oPager = new cGuiObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $fullTableCount, $requestElemPerPage, $mPage, $oPagerLink, "page", $pagingLink);

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

$oPage->set("s", "MPAGE", $mPage);
$oPage->set("s", "PAGER_CONTENT", $sPagerContent);
$oPage->set("s", "PAGE", $mPage);
$oPage->set("s", "FORM", $cGuiMenu->render(false));
$oPage->render();
